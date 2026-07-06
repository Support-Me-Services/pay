<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Models\Order;
use App\Modules\Storefront\Models\ShopItem;
use App\Modules\Storefront\Services\GatewayClient;
use Illuminate\Http\Request;

/**
 * Koszyk sklepu (sesyjny). Pozwala dodać wiele produktów, zmienić ilości
 * i sfinalizować całość jedną transakcją „Kupuję i płacę".
 * Zawartość koszyka trzymana w sesji jako [slug => ilość].
 */
class CartController extends Controller
{
    private const KEY = 'cart';

    /** GET /koszyk — podgląd koszyka. */
    public function show()
    {
        [$lines, $subtotal] = $this->resolve();
        [$shipCode, $shipMethod, $shipPoint] = $this->shipping();
        $shipCost = $lines->isEmpty() ? 0 : (int) $shipMethod['price'];

        return view('shop.koszyk', [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'methods' => config('shipping.methods'),
            'shipCode' => $shipCode,
            'shipCost' => $shipCost,
            'shipPoint' => $shipPoint,
            'total' => $subtotal + $shipCost,
        ]);
    }

    /** POST /koszyk/dostawa — wybór metody dostawy (+ punkt dla paczkomatu). */
    public function setShipping(Request $request)
    {
        $methods = config('shipping.methods');
        $code = (string) $request->input('ship');

        if (! isset($methods[$code])) {
            return redirect()->route('cart.show')->with('error', 'Nieznana metoda dostawy.');
        }
        if (empty($methods[$code]['enabled'])) {
            return redirect()->route('cart.show')->with('error', 'Ta metoda dostawy będzie dostępna wkrótce.');
        }

        session(['ship' => $code]);
        session(['ship_point' => $methods[$code]['point'] ? trim((string) $request->input('ship_point')) : null]);

        return redirect()->route('cart.show')->with('success', 'Zapisano metodę dostawy.');
    }

    /** POST /koszyk/dodaj/{slug} — dodaj produkt (domyślnie +1). */
    public function add(Request $request, string $slug)
    {
        $item = ShopItem::where('slug', $slug)->where('active', true)->firstOrFail();

        $cart = $this->cart();
        $cart[$slug] = min(99, ($cart[$slug] ?? 0) + max(1, (int) $request->input('qty', 1)));
        session([self::KEY => $cart]);

        return redirect()->back()->with('success', "Dodano do koszyka: {$item->name}.");
    }

    /** POST /koszyk/aktualizuj/{slug} — ustaw ilość (0 = usuń). */
    public function update(Request $request, string $slug)
    {
        $qty = (int) $request->input('qty', 1);
        $cart = $this->cart();

        if ($qty <= 0) {
            unset($cart[$slug]);
        } else {
            $cart[$slug] = min(99, $qty);
        }
        session([self::KEY => $cart]);

        return redirect()->route('cart.show');
    }

    /** POST /koszyk/usun/{slug} — usuń pozycję. */
    public function remove(string $slug)
    {
        $cart = $this->cart();
        unset($cart[$slug]);
        session([self::KEY => $cart]);

        return redirect()->route('cart.show')->with('success', 'Usunięto z koszyka.');
    }

    /** POST /koszyk/kup — finalizacja całego koszyka jedną transakcją. */
    public function checkout(GatewayClient $gateway)
    {
        [$lines, $subtotal] = $this->resolve();

        if ($lines->isEmpty()) {
            return redirect()->route('cart.show')->with('error', 'Koszyk jest pusty.');
        }

        [$shipCode, $shipMethod, $shipPoint] = $this->shipping();
        if ($shipMethod['point'] && ($shipPoint === null || $shipPoint === '')) {
            return redirect()->route('cart.show')
                ->with('error', 'Wybierz i podaj numer paczkomatu / punktu odbioru.');
        }
        $total = $subtotal + (int) $shipMethod['price'];

        // Poki PayU nie zatwierdzil sklepu: pomijamy platnosc i kierujemy na podziekowanie.
        if (config('payment.bypass')) {
            $this->clear();

            return redirect()->route('main', ['dzieki' => 1]);
        }

        $order = Order::create(['product_id' => null, 'amount' => $total, 'status' => 'pending']);
        $names = $lines->map(fn ($l) => $l['item']->name.' ×'.$l['qty'])->implode(', ');
        $ship = $shipMethod['label'].($shipPoint ? ' ('.$shipPoint.')' : '');

        try {
            $result = $gateway->createTransaction([
                'product_external_id' => 'cart-'.$order->id,
                'product_name' => 'Zamówienie: '.mb_substr($names, 0, 90).' | Dostawa: '.$ship,
                'amount' => $total,
                'currency' => 'PLN',
                'return_url' => route('order.return', $order->id),
                'notify_url' => route('webhooks.gateway'),
                'tag_uid' => null,
            ]);
        } catch (\Throwable $e) {
            return redirect()->route('cart.show')
                ->with('error', 'Płatność jest chwilowo niedostępna. Spróbuj ponownie za moment.');
        }

        $order->update(['transaction_id' => $result['uuid']]);
        $this->clear();

        return redirect()->away($result['payment_url']);
    }

    /**
     * Wybrana metoda dostawy z sesji (z walidacją i fallbackiem do domyślnej).
     *
     * @return array{0:string,1:array,2:?string} [$code, $method, $point]
     */
    private function shipping(): array
    {
        $methods = config('shipping.methods');
        $code = (string) session('ship', config('shipping.default'));
        if (! isset($methods[$code]) || empty($methods[$code]['enabled'])) {
            $code = config('shipping.default');
        }

        return [$code, $methods[$code], session('ship_point')];
    }

    /** Wyczyść koszyk i punkt odbioru po złożeniu zamówienia. */
    private function clear(): void
    {
        session()->forget([self::KEY, 'ship_point']);
    }

    /**
     * Rozwiązanie koszyka na pozycje z aktualnych danych produktów.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: int} [$lines, $totalGrosze]
     */
    private function resolve(): array
    {
        $cart = $this->cart();
        if (! $cart) {
            return [collect(), 0];
        }

        $items = ShopItem::whereIn('slug', array_keys($cart))->where('active', true)->get()->keyBy('slug');

        $lines = collect();
        foreach ($cart as $slug => $qty) {
            if (! $item = $items->get($slug)) {
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

    /** Aktualny koszyk z sesji (tylko dodatnie ilości). */
    private function cart(): array
    {
        return array_filter((array) session(self::KEY, []), fn ($q) => (int) $q > 0);
    }
}
