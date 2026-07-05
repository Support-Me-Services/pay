<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Models\Order;
use App\Modules\Storefront\Models\ShopItem;
use App\Modules\Storefront\Services\GatewayClient;
use Illuminate\Http\Request;

/**
 * Sklep internetowy (produkty NFC). Każdy produkt ma STAŁĄ cenę; użytkownik
 * klika „Kupuję i płacę" i finalizuje zakup jedną transakcją w bramce.
 * Domyślny produkt pokazuje się na stronie sklepu po wejściu.
 * Produkty trzymane w tabeli `shop_items` (zarządzane w panelu).
 */
class CompanyStoreController extends Controller
{
    /** GET / — strona sklepu: siatka produktów + auto-podgląd domyślnego. */
    public function index()
    {
        $items = ShopItem::where('active', true)->orderBy('sort')->orderBy('id')->get();
        $default = $items->firstWhere('is_default', true) ?? $items->first();

        return view('shop.sklep', [
            'items' => $items,
            'default' => $default,
        ]);
    }

    /** POST /sklep/kup/{slug} — utwórz transakcję na stałą cenę produktu. */
    public function purchase(Request $request, string $slug, GatewayClient $gateway)
    {
        // Poki PayU nie zatwierdzil sklepu: pomijamy platnosc i od razu kierujemy na podziekowanie.
        if (config('payment.bypass')) {
            return redirect()->route('main', ['dzieki' => 1]);
        }

        $item = ShopItem::where('slug', $slug)->where('active', true)->firstOrFail();

        // Cena jest stała (po stronie serwera) — kwota nie pochodzi od użytkownika.
        $amount = $item->priceGrosze(); // grosze

        $order = Order::create([
            'product_id' => null,        // sklep gadżetów — bez wiązania z tabelą products (parafie)
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
