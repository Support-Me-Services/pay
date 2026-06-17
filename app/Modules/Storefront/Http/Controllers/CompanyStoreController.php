<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Models\Order;
use App\Modules\Storefront\Services\GatewayClient;
use Illuminate\Http\Request;

/**
 * Sklep firmowy — gadżety charytatywne (Kubek, Koszulka, Pin, Brelok).
 * Produkty: statyczna lista z config('shop.merch'). Koszyk: sesyjny
 * (slug => ilość); ceny liczone WYŁĄCZNIE serwerowo. Checkout: jedna
 * transakcja w bramce na sumę koszyka.
 */
class CompanyStoreController extends Controller
{
    private const CART_KEY = 'merch_cart';

    /** Lista produktów z configu, kluczowana slugiem. */
    private function products(): array
    {
        $out = [];
        foreach ((array) config('shop.merch', []) as $p) {
            $out[$p['slug']] = $p;
        }

        return $out;
    }

    /** Koszyk z sesji: [slug => qty] (tylko istniejące produkty, qty 1..99). */
    private function cart(): array
    {
        $valid = $this->products();
        $cart = [];
        foreach ((array) session(self::CART_KEY, []) as $slug => $qty) {
            $qty = (int) $qty;
            if (isset($valid[$slug]) && $qty > 0) {
                $cart[$slug] = min($qty, 99);
            }
        }

        return $cart;
    }

    /** Pozycje koszyka z danymi produktu + sumy. */
    private function cartLines(): array
    {
        $products = $this->products();
        $lines = [];
        $total = 0;
        $count = 0;
        foreach ($this->cart() as $slug => $qty) {
            $p = $products[$slug];
            $sub = $p['price'] * $qty;
            $total += $sub;
            $count += $qty;
            $lines[] = ['product' => $p, 'qty' => $qty, 'subtotal' => $sub];
        }

        return ['lines' => $lines, 'total' => $total, 'count' => $count];
    }

    /** GET / — Sklep firmowy (strona główna serwisu). */
    public function index()
    {
        return view('shop.sklep', [
            'products' => array_values($this->products()),
            'cartCount' => $this->cartLines()['count'],
        ]);
    }

    /** POST /sklep/koszyk/{slug} — dodaj produkt do koszyka. */
    public function add(Request $request, string $slug)
    {
        abort_unless(isset($this->products()[$slug]), 404);

        $cart = (array) session(self::CART_KEY, []);
        $cart[$slug] = min(($cart[$slug] ?? 0) + 1, 99);
        session([self::CART_KEY => $cart]);

        $count = $this->cartLines()['count'];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'count' => $count, 'name' => $this->products()[$slug]['name']]);
        }

        return back()->with('success', 'Dodano do koszyka: ' . $this->products()[$slug]['name']);
    }

    /** POST /wesprzyj — szybkie wsparcie (10 zł) przez bramkę (modal „WESPRZYJ"). */
    public function donate(GatewayClient $gateway)
    {
        $amount = 1000; // 10 zł

        $order = Order::create([
            'product_id' => null,
            'amount' => $amount,
            'status' => 'pending',
        ]);

        try {
            $result = $gateway->createTransaction([
                'product_external_id' => 'support-' . $order->id,
                'product_name' => 'Wsparcie SupportMe',
                'amount' => $amount,
                'currency' => 'PLN',
                'return_url' => route('order.return', $order->id),
                'notify_url' => route('webhooks.gateway'),
                'tag_uid' => null,
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('error', 'Płatność jest chwilowo niedostępna. Spróbuj ponownie za moment.');
        }

        $order->update(['transaction_id' => $result['uuid']]);

        return redirect()->away($result['payment_url']);
    }

    /** GET /koszyk — podgląd koszyka. */
    public function cartView()
    {
        $data = $this->cartLines();

        return view('shop.koszyk', $data);
    }

    /** POST /koszyk/{slug} — ustaw ilość (0 = usuń). */
    public function update(Request $request, string $slug)
    {
        $qty = (int) $request->input('qty', 1);
        $cart = (array) session(self::CART_KEY, []);

        if ($qty <= 0) {
            unset($cart[$slug]);
        } elseif (isset($this->products()[$slug])) {
            $cart[$slug] = min($qty, 99);
        }
        session([self::CART_KEY => $cart]);

        return back();
    }

    /** POST /koszyk/checkout — utwórz transakcję w bramce na sumę koszyka. */
    public function checkout(GatewayClient $gateway)
    {
        $data = $this->cartLines();
        if ($data['count'] === 0) {
            return redirect()->route('cart')->with('error', 'Koszyk jest pusty.');
        }

        $summary = collect($data['lines'])
            ->map(fn ($l) => $l['product']['name'] . ' ×' . $l['qty'])
            ->implode(', ');

        $order = Order::create([
            'product_id' => null,                  // koszyk — bez pojedynczego produktu
            'amount' => $data['total'],
            'status' => 'pending',
        ]);

        try {
            $result = $gateway->createTransaction([
                'product_external_id' => 'cart-' . $order->id,
                'product_name' => 'Sklep firmowy: ' . $summary,
                'amount' => $data['total'],
                'currency' => 'PLN',
                'return_url' => route('order.return', $order->id),
                'notify_url' => route('webhooks.gateway'),
                'tag_uid' => null,
            ]);
        } catch (\Throwable $e) {
            return redirect()->route('cart')
                ->with('error', 'Płatność jest chwilowo niedostępna. Spróbuj ponownie za moment.');
        }

        $order->update(['transaction_id' => $result['uuid']]);
        session()->forget(self::CART_KEY);   // czyść koszyk po przekierowaniu do płatności

        return redirect()->away($result['payment_url']);
    }
}
