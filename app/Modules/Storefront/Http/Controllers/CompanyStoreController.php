<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Models\Order;
use App\Modules\Storefront\Models\ShopItem;
use App\Modules\Storefront\Services\GatewayClient;
use Illuminate\Http\Request;

/**
 * Sklep donacyjny (produkty NFC). Każdy produkt ma minimalną kwotę; użytkownik
 * wybiera kwotę ≥ min w modalu i kupuje (KUP) jedną transakcją w bramce.
 * Domyślny produkt („Serduszko", min 1 zł) pokazuje się w modalu po wejściu.
 * Produkty trzymane w tabeli `shop_items` (zarządzane w panelu).
 */
class CompanyStoreController extends Controller
{
    /** GET / — strona sklepu: siatka produktów + auto-modal domyślnego. */
    public function index()
    {
        $items = ShopItem::where('active', true)->orderBy('sort')->orderBy('id')->get();
        $default = $items->firstWhere('is_default', true) ?? $items->first();

        return view('shop.sklep', [
            'items' => $items,
            'default' => $default,
        ]);
    }

    /** POST /sklep/kup/{slug} — utwórz transakcję na wybraną kwotę (≥ min produktu). */
    public function purchase(Request $request, string $slug, GatewayClient $gateway)
    {
        // Poki PayU nie zatwierdzil sklepu: pomijamy platnosc i od razu kierujemy na podziekowanie.
        if (config('payment.bypass')) {
            return redirect()->route('main', ['dzieki' => 1]);
        }

        $item = ShopItem::where('slug', $slug)->where('active', true)->firstOrFail();
        $minPln = (int) max(1, ceil($item->min_amount / 100));

        // Walidacja serwerowa: kwota całkowita w zł, nie niższa niż minimum produktu.
        $validated = $request->validate([
            'amount_pln' => ['required', 'integer', 'min:' . $minPln, 'max:5000'],
        ], [
            'amount_pln.min' => "Minimalna kwota dla „{$item->name}” to {$minPln} zł.",
            'amount_pln.required' => 'Podaj kwotę.',
            'amount_pln.integer' => 'Kwota musi być liczbą całkowitą (zł).',
        ]);

        $amount = $validated['amount_pln'] * 100; // grosze

        $order = Order::create([
            'product_id' => null,        // sklep donacyjny — bez wiązania z tabelą products (parafie)
            'amount' => $amount,
            'status' => 'pending',
        ]);

        try {
            $result = $gateway->createTransaction([
                'product_external_id' => 'shop-' . $item->slug . '-' . $order->id,
                'product_name' => 'Sklep: ' . $item->name,
                'amount' => $amount,
                'currency' => 'PLN',
                'return_url' => route('order.return', $order->id),
                'notify_url' => route('webhooks.gateway'),
                'tag_uid' => $item->tag_uid,
            ]);
        } catch (\Throwable $e) {
            return redirect()->route('home')
                ->with('error', 'Płatność jest chwilowo niedostępna. Spróbuj ponownie za moment.');
        }

        $order->update(['transaction_id' => $result['uuid']]);

        return redirect()->away($result['payment_url']);
    }
}
