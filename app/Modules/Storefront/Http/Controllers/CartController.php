<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Models\Order;
use App\Modules\Storefront\Models\Organization;
use App\Modules\Storefront\Models\ShopItem;
use App\Modules\Storefront\Services\GatewayClient;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Koszyk sklepu per‑konto (/user/{handle}). Osobny koszyk dla każdego sklepu:
 * sesja pod kluczem `cart.{handle}` jako [id_produktu => ilość]; pozycje
 * scope’owane do właściciela sklepu. Dostawa (ship.{handle}) jak dotąd.
 */
class CartController extends Controller
{
    /** GET /user/{handle}/koszyk */
    public function show(string $handle)
    {
        $owner = $this->owner($handle);
        [$lines, $subtotal] = $this->resolve($owner, $handle);
        [$shipCode, $shipMethod, $shipPoint] = $this->shipping($handle);
        $shipCost = $lines->isEmpty() ? 0 : (int) $shipMethod['price'];
        $methods = config('shipping.methods');
        $enabledCount = collect($methods)->where('enabled', true)->count();

        return Inertia::render('Storefront/Koszyk', [
            'shopHandle' => $handle,
            'ownerName' => $owner->name,
            'cartCount' => array_sum((array) session("cart.$handle", [])),
            'lines' => $lines->map(fn ($l) => [
                'id' => $l['item']->id,
                'name' => $l['item']->name,
                'image' => asset($l['item']->image),
                'unit_price' => $l['item']->pricePln(),
                'qty' => $l['qty'],
                'line_total' => number_format($l['lineGrosze'] / 100, 2, ',', ' '),
                'update_url' => route('user.cart.update', [$handle, $l['item']->id]),
                'remove_url' => route('user.cart.remove', [$handle, $l['item']->id]),
            ])->values(),
            'methods' => collect($methods)->map(fn ($m, $code) => [
                'code' => $code,
                'label' => $m['label'],
                'price' => $m['price'] ? number_format($m['price'] / 100, 2, ',', ' ') . ' zł' : 'bez kosztów',
                'enabled' => ! empty($m['enabled']),
                'point' => (bool) $m['point'],
            ])->values(),
            'shipCode' => $shipCode,
            'shipPoint' => $shipPoint,
            'shipHasPoint' => (bool) $methods[$shipCode]['point'],
            'shipMultiple' => $enabledCount > 1,
            'subtotal' => number_format($subtotal / 100, 2, ',', ' '),
            'shipLabel' => $methods[$shipCode]['label'],
            'shipCost' => $shipCost ? number_format($shipCost / 100, 2, ',', ' ') . ' zł' : 'bez kosztów',
            'total' => number_format(($subtotal + $shipCost) / 100, 2, ',', ' '),
            'urls' => [
                'shop' => route('user.shop', $handle),
                'shipping' => route('user.cart.shipping', $handle),
                'checkout' => route('user.cart.checkout', $handle),
                'regulamin' => route('regulamin'),
            ],
            'pageTitle' => 'Koszyk — ' . config('shop.name'),
            'pageDescription' => 'Twój koszyk w sklepie SupportMe.',
        ]);
    }

    /** POST /user/{handle}/koszyk/dodaj/{item} */
    public function add(Request $request, string $handle, string $item)
    {
        $owner = $this->owner($handle);
        $shopItem = ShopItem::forOrganization($owner->id)->where('id', (int) $item)->where('active', true)->firstOrFail();

        $cart = $this->cart($handle);
        $cart[$shopItem->id] = min(99, ($cart[$shopItem->id] ?? 0) + max(1, (int) $request->input('qty', 1)));
        session([$this->key($handle) => $cart]);

        return redirect()->back()->with('success', "Dodano do koszyka: {$shopItem->name}.");
    }

    /** POST /user/{handle}/koszyk/aktualizuj/{item} */
    public function update(Request $request, string $handle, string $item)
    {
        $qty = (int) $request->input('qty', 1);
        $cart = $this->cart($handle);

        if ($qty <= 0) {
            unset($cart[$item]);
        } else {
            $cart[$item] = min(99, $qty);
        }
        session([$this->key($handle) => $cart]);

        return redirect()->route('user.cart.show', $handle);
    }

    /** POST /user/{handle}/koszyk/usun/{item} */
    public function remove(string $handle, string $item)
    {
        $cart = $this->cart($handle);
        unset($cart[$item]);
        session([$this->key($handle) => $cart]);

        return redirect()->route('user.cart.show', $handle)->with('success', 'Usunięto z koszyka.');
    }

    /** POST /user/{handle}/koszyk/dostawa */
    public function setShipping(Request $request, string $handle)
    {
        $methods = config('shipping.methods');
        $code = (string) $request->input('ship');

        if (! isset($methods[$code])) {
            return redirect()->route('user.cart.show', $handle)->with('error', 'Nieznana metoda dostawy.');
        }
        if (empty($methods[$code]['enabled'])) {
            return redirect()->route('user.cart.show', $handle)->with('error', 'Ta metoda dostawy będzie dostępna wkrótce.');
        }

        session(["ship.$handle" => $code]);
        session(["ship_point.$handle" => $methods[$code]['point'] ? trim((string) $request->input('ship_point')) : null]);

        return redirect()->route('user.cart.show', $handle)->with('success', 'Zapisano metodę dostawy.');
    }

    /** POST /user/{handle}/koszyk/kup — finalizacja koszyka tego sklepu. */
    public function checkout(string $handle, GatewayClient $gateway)
    {
        $owner = $this->owner($handle);
        [$lines, $subtotal] = $this->resolve($owner, $handle);

        if ($lines->isEmpty()) {
            return redirect()->route('user.cart.show', $handle)->with('error', 'Koszyk jest pusty.');
        }

        [$shipCode, $shipMethod, $shipPoint] = $this->shipping($handle);
        if ($shipMethod['point'] && ($shipPoint === null || $shipPoint === '')) {
            return redirect()->route('user.cart.show', $handle)
                ->with('error', 'Wybierz i podaj numer paczkomatu / punktu odbioru.');
        }
        $total = $subtotal + (int) $shipMethod['price'];

        // Koszyk z DOKŁADNIE jednym produktem -> wiadomo, czyją stronę
        // podziękowania pokazać; wieloproduktowy koszyk zostaje bez zmian
        // (fallback ogólny — nie zgadujemy, który produkt "wygrywa").
        $distinctItems = $lines->pluck('item')->unique('id');
        $singleItem = $distinctItems->count() === 1 ? $distinctItems->first() : null;

        // Poki PayU nie zatwierdzil sklepu: pomijamy platnosc i kierujemy na podziekowanie.
        if (config('payment.bypass')) {
            $this->clear($handle);

            return redirect()->route('main', ['thank-you-page' => $singleItem?->slug ?? 1]);
        }

        $order = Order::create(['product_id' => null, 'shop_item_id' => $singleItem?->id, 'amount' => $total, 'status' => 'pending']);
        $names = $lines->map(fn ($l) => $l['item']->name.' ×'.$l['qty'])->implode(', ');
        $ship = $shipMethod['label'].($shipPoint ? ' ('.$shipPoint.')' : '');

        try {
            $result = $gateway->createTransaction([
                'product_external_id' => 'cart-'.$handle.'-'.$order->id,
                'product_name' => 'Zamówienie ('.$handle.'): '.mb_substr($names, 0, 80).' | Dostawa: '.$ship,
                'amount' => $total,
                'currency' => 'PLN',
                'return_url' => route('order.return', $order->id),
                'notify_url' => route('webhooks.gateway'),
                'tag_uid' => null,
            ]);
        } catch (\Throwable $e) {
            return redirect()->route('user.cart.show', $handle)
                ->with('error', 'Płatność jest chwilowo niedostępna. Spróbuj ponownie za moment.');
        }

        $order->update(['transaction_id' => $result['uuid']]);
        $this->clear($handle);

        return redirect()->away($result['payment_url']);
    }

    /** Organizacja-właściciel sklepu po handle. */
    private function owner(string $handle): Organization
    {
        return Organization::where('handle', $handle)->firstOrFail();
    }

    /** Klucz sesji koszyka danego sklepu. */
    private function key(string $handle): string
    {
        return "cart.$handle";
    }

    /** Aktualny koszyk sklepu (id => ilość, tylko dodatnie). */
    private function cart(string $handle): array
    {
        return array_filter((array) session($this->key($handle), []), fn ($q) => (int) $q > 0);
    }

    /**
     * Pozycje koszyka z aktualnych danych produktów danego sklepu.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: int} [$lines, $totalGrosze]
     */
    private function resolve(Organization $owner, string $handle): array
    {
        $cart = $this->cart($handle);
        if (! $cart) {
            return [collect(), 0];
        }

        $items = ShopItem::forOrganization($owner->id)->whereIn('id', array_keys($cart))->where('active', true)->get()->keyBy('id');

        $lines = collect();
        foreach ($cart as $id => $qty) {
            if (! $item = $items->get($id)) {
                continue;
            }
            $lines->push([
                'item' => $item,
                'qty' => (int) $qty,
                'lineGrosze' => $item->priceGrosze() * (int) $qty,
            ]);
        }

        return [$lines, (int) $lines->sum('lineGrosze')];
    }

    /**
     * Wybrana metoda dostawy (per sklep) z fallbackiem do domyślnej/aktywnej.
     *
     * @return array{0:string,1:array,2:?string} [$code, $method, $point]
     */
    private function shipping(string $handle): array
    {
        $methods = config('shipping.methods');
        $code = (string) session("ship.$handle", config('shipping.default'));
        if (! isset($methods[$code]) || empty($methods[$code]['enabled'])) {
            $code = config('shipping.default');
        }

        return [$code, $methods[$code], session("ship_point.$handle")];
    }

    /** Wyczyść koszyk i punkt odbioru sklepu po złożeniu zamówienia. */
    private function clear(string $handle): void
    {
        session()->forget([$this->key($handle), "ship_point.$handle"]);
    }
}
