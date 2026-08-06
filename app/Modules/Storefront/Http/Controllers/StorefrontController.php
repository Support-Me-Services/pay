<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Jobs\SendGatewayEvent;
use App\Modules\Storefront\Models\Event;
use App\Modules\Storefront\Models\Order;
use App\Modules\Storefront\Models\Product;
use App\Modules\Storefront\Models\ShopItem;
use App\Modules\Storefront\Services\GatewayClient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StorefrontController extends Controller
{
    /**
     * GET / — strona główna (landing SupportME wg Figmy).
     *
     * Sekcja „Kogo wspieramy?" to statyczne kafelki (ikona + etykieta, na sztywno
     * w Storefront/Home.jsx — system Kategorie usunięty, kafelki nie są już
     * edytowalne z panelu), wszystkie prowadzące do podstrony „Wspieramy" (/beneficiaries).
     */
    public function index()
    {
        // Logo mecenasa (LokalnyRolnik) do modala podziękowania — pierwszy istniejący format.
        $mecenasLogo = null;
        foreach (['svg', 'png', 'webp', 'jpg'] as $ext) {
            if (is_file(public_path("img/mecenasi/lokalnyrolnik.$ext"))) {
                $mecenasLogo = asset("img/mecenasi/lokalnyrolnik.$ext");
                break;
            }
        }

        return Inertia::render('Storefront/Home', [
            'beneficiariesUrl' => route('beneficiaries'),
            // Modal „Dziękujemy" po powrocie z płatności (redirect ...?dzieki=1).
            'showThanks' => (bool) request('dzieki'),
            'mecenasLogo' => $mecenasLogo,
            'mecenasUrl' => route('mecenasi.lokalnyrolnik'),
            'mainUrl' => route('main'),
        ]);
    }

    /**
     * GET /t/{tag_uid} — wejście z taga NFC: event tag_open (lokalnie +
     * asynchronicznie do bramki) i redirect 302 na stronę produktu.
     */
    public function tag(string $tagUid)
    {
        $product = Product::where('tag_uid', $tagUid)->where('active', true)->first();

        if ($product) {
            Event::create(['product_id' => $product->id, 'type' => 'tag_open']);
            SendGatewayEvent::dispatchAfterResponse('tag_open', $tagUid);

            return redirect()->route('product.show', $product->slug, 302);
        }

        // Tag może też wskazywać produkt sklepu donacyjnego (NFC) — wtedy
        // kierujemy na stronę sklepu z auto-otwarciem modala tego produktu.
        $item = ShopItem::where('tag_uid', $tagUid)->where('active', true)->first();

        if ($item) {
            SendGatewayEvent::dispatchAfterResponse('tag_open', $tagUid);

            return redirect()->route('home', ['produkt' => $item->slug], 302);
        }

        return Inertia::render('Storefront/TagNotFound', [
            // Strona kategorii usunięta — powrót prowadzi na podstronę „Wspieramy".
            'categoryUrl' => route('beneficiaries'),
        ])->toResponse(request())->setStatusCode(404);
    }

    /**
     * GET /p/{slug} — strona produktu.
     */
    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)->where('active', true)->firstOrFail();

        Event::create(['product_id' => $product->id, 'type' => 'page_view']);

        // Sugerowane kwoty (zł); domyślna = cena bazowa parafii, fallback 20 zł.
        $presets = [10, 20, 50, 100, 200];
        $default = (int) round($product->price / 100);
        if (! in_array($default, $presets, true)) {
            $default = 20;
        }

        return Inertia::render('Storefront/Product', [
            'product' => [
                'name' => $product->name,
                'city' => $product->city,
                'purpose' => $product->purpose,
                'image' => $product->main_image ? asset('storage/' . $product->main_image) : null,
                'description_html' => $product->description_html,
            ],
            'presets' => $presets,
            'default' => $default,
            'buyUrl' => route('product.buy', $product->slug),
            // Strona kategorii usunięta — powrót prowadzi na podstronę „Wspieramy".
            'categoryUrl' => route('beneficiaries'),
            'css' => asset('css/subpages.css') . '?v=' . substr(md5_file(public_path('css/subpages.css')), 0, 10),
            'pageTitle' => 'Wesprzyj: ' . $product->name . ' — ' . config('shop.name'),
            'pageDescription' => 'Złóż cyfrową tacę na rzecz parafii ' . $product->name . '. Szybka wpłata BLIK, bez gotówki.',
        ]);
    }

    /**
     * POST /p/{slug}/kup — event buy_click, transakcja w bramce, redirect 302
     * na payment_url.
     */
    public function buy(Request $request, string $slug, GatewayClient $gateway)
    {
        $product = Product::where('slug', $slug)->where('active', true)->firstOrFail();

        Event::create(['product_id' => $product->id, 'type' => 'buy_click']);

        $isChurch = config('platform.shop_kind') === 'church';

        if ($isChurch) {
            // Kwota tacy wybrana przez darczyńcę (zł) — od 2 do 5000 zł.
            $validated = $request->validate([
                'amount_pln' => ['required', 'integer', 'min:2', 'max:5000'],
            ]);
            $amount = $validated['amount_pln'] * 100; // grosze
        } else {
            // Sklep produktowy — cena stała.
            $amount = $product->price;
        }

        $order = Order::create([
            'product_id' => $product->id,
            'amount' => $amount,
            'status' => 'pending',
        ]);

        try {
            $result = $gateway->createTransaction([
                'product_external_id' => (string) $product->id,
                'product_name' => $isChurch ? ('Taca — ' . $product->name) : $product->name,
                'amount' => $amount,
                'currency' => 'PLN',
                'return_url' => route('order.return', $order->id),
                'notify_url' => route('webhooks.gateway'),
                'tag_uid' => $product->tag_uid,
            ]);
        } catch (\Throwable $e) {
            return redirect()->route('product.show', $product->slug)
                ->with('error', 'Płatność jest chwilowo niedostępna. Spróbuj ponownie za moment.');
        }

        $order->update(['transaction_id' => $result['uuid']]);

        return redirect()->away($result['payment_url']);
    }
}
