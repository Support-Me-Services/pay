<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Models\User;
use App\Modules\Storefront\Models\Order;
use App\Modules\Storefront\Models\ShopItem;
use App\Modules\Storefront\Services\GatewayClient;
use Illuminate\Http\Request;

/**
 * Strona główna „/" — model DAROWIZNOWY (paywin „Wesprzyj — X zł").
 * Pokazuje produkty konta głównego (admin, handle „lula-marcin"); zamiast
 * stałej ceny obowiązuje kwota wybrana przez użytkownika, nie niższa niż
 * `min_amount` produktu. Sklep ze stałą ceną + koszyk jest pod /user/{handle}.
 */
class CompanyStoreController extends Controller
{
    /** GET / — darowiznowy storefront produktów konta głównego. */
    public function index()
    {
        $owner = $this->owner();
        $items = $owner
            ? ShopItem::forUser($owner->id)->where('active', true)->ordered()->get()
            : collect();
        $default = $items->firstWhere('is_default', true) ?? $items->first();

        return view('shop.storefront', ['items' => $items, 'default' => $default]);
    }

    /** POST /sklep/kup/{slug} — darowizna na wybraną kwotę (≥ min produktu). */
    public function purchase(Request $request, string $slug, GatewayClient $gateway)
    {
        // Poki PayU nie zatwierdzil sklepu: pomijamy platnosc i kierujemy na podziekowanie.
        if (config('payment.bypass')) {
            return redirect()->route('main', ['dzieki' => 1]);
        }

        $owner = $this->owner();
        $item = ShopItem::query()
            ->when($owner, fn ($q) => $q->forUser($owner->id))
            ->where('slug', $slug)->where('active', true)->firstOrFail();
        $minPln = (int) max(1, ceil($item->min_amount / 100));

        $validated = $request->validate([
            'amount_pln' => ['required', 'integer', 'min:'.$minPln, 'max:5000'],
        ], [
            'amount_pln.min' => "Minimalna kwota dla „{$item->name}” to {$minPln} zł.",
            'amount_pln.required' => 'Podaj kwotę.',
            'amount_pln.integer' => 'Kwota musi być liczbą całkowitą (zł).',
        ]);

        $amount = $validated['amount_pln'] * 100; // grosze

        $order = Order::create(['product_id' => null, 'amount' => $amount, 'status' => 'pending']);

        try {
            $result = $gateway->createTransaction([
                'product_external_id' => 'shop-'.$item->slug.'-'.$order->id,
                'product_name' => 'Wsparcie: '.$item->name,
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

    /** Konto główne (właściciel produktów widocznych na „/"). */
    private function owner(): ?User
    {
        return User::where('handle', 'lula-marcin')->first() ?? User::orderBy('id')->first();
    }
}
