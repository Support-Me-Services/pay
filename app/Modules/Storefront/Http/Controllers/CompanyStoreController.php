<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Models\Order;
use App\Modules\Storefront\Models\Organization;
use App\Modules\Storefront\Models\ShopItem;
use App\Modules\Storefront\Services\GatewayClient;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Strona główna „/" — model DAROWIZNOWY (paywin „Wesprzyj — X zł").
 * Pokazuje produkty konta głównego (admin, handle „lula-marcin"); zamiast
 * stałej ceny obowiązuje kwota wybrana przez użytkownika, nie niższa niż
 * `min_amount` produktu. Sklep ze stałą ceną + koszyk jest pod /user/{handle}.
 */
class CompanyStoreController extends Controller
{
    /** GET / — darowiznowy storefront produktów konta głównego. */
    public function index(Request $request)
    {
        $owner = $this->owner();
        $items = $owner
            ? ShopItem::forOrganization($owner->id)->where('active', true)->ordered()->get()->values()
            : collect();
        $default = $items->firstWhere('is_default', true) ?? $items->first();

        // Indeks startowy: produkt z ?produkt= (link z tagu NFC/podstron), inaczej domyślny.
        $startSlug = $request->query('produkt');
        $start = $items->firstWhere('slug', $startSlug) ?? $default ?? $items->first();
        $startIdx = $items->search(fn ($i) => $i->slug === optional($start)->slug);
        $startIdx = $startIdx === false ? 0 : $startIdx;

        // Fundacje wspierane (karuzela) — logo z public/img/fundacje/<slug>.(svg|png|webp|jpg).
        $foundations = collect([
            ['slug' => 'legalsight', 'name' => 'LegalSight Polska'],
            ['slug' => 'twoja-fundacja', 'name' => 'Twoja Fundacja'],
        ])->map(function (array $f) {
            $logo = null;
            foreach (['svg', 'png', 'webp', 'jpg'] as $e) {
                if (is_file(public_path("img/fundacje/{$f['slug']}.$e"))) {
                    $logo = asset("img/fundacje/{$f['slug']}.$e");
                    break;
                }
            }

            return $f + ['logo' => $logo];
        })->values();

        return Inertia::render('Storefront/Storefront', [
            'items' => $items->map(fn (ShopItem $i) => [
                'slug' => $i->slug,
                'name' => $i->name,
                'min' => $i->minAmountPln(),
                'image' => asset($i->image),
                'is_svg' => $i->isSvg(),
                'action' => route('shop.buy', $i->slug),
            ])->values(),
            'startIdx' => $startIdx,
            'foundations' => $foundations,
            'mainUrl' => route('main'),
            'regulaminUrl' => route('regulamin'),
            'pageTitle' => 'Wesprzyj — ' . config('shop.name'),
            'pageDescription' => 'Wesprzyj SupportMe — wybierz produkt i wpłać dowolną kwotę.',
            'css' => asset('css/sklep.css') . '?v=' . substr(md5_file(public_path('css/sklep.css')), 0, 10),
        ]);
    }

    /** POST /sklep/kup/{slug} — darowizna na wybraną kwotę (≥ min produktu). */
    public function purchase(Request $request, string $slug, GatewayClient $gateway)
    {
        $owner = $this->owner();
        $item = ShopItem::query()
            ->when($owner, fn ($q) => $q->forOrganization($owner->id))
            ->where('slug', $slug)->where('active', true)->firstOrFail();

        // Poki PayU nie zatwierdzil sklepu: pomijamy platnosc i kierujemy na
        // podziekowanie — ale nadal ze znanym produktem (wlasna tresc podziekowania).
        if (config('payment.bypass')) {
            return redirect()->route('main', ['thank-you-page' => $item->slug]);
        }

        $minPln = (int) max(1, ceil($item->min_amount / 100));

        $validated = $request->validate([
            'amount_pln' => ['required', 'integer', 'min:'.$minPln, 'max:5000'],
        ], [
            'amount_pln.min' => "Minimalna kwota dla „{$item->name}” to {$minPln} zł.",
            'amount_pln.required' => 'Podaj kwotę.',
            'amount_pln.integer' => 'Kwota musi być liczbą całkowitą (zł).',
        ]);

        $amount = $validated['amount_pln'] * 100; // grosze

        $order = Order::create(['product_id' => null, 'shop_item_id' => $item->id, 'amount' => $amount, 'status' => 'pending']);

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

    /** Organizacja główna (właściciel produktów widocznych na „/"). */
    private function owner(): ?Organization
    {
        return Organization::rootOrganization();
    }
}
