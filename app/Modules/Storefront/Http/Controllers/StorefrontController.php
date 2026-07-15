<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Jobs\SendGatewayEvent;
use App\Modules\Storefront\Models\Category;
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
     * Kategorie wsparcia (sekcja „Kogo wspieramy?") czytane z bazy — drzewo
     * edytowalne z panelu. Render zachowuje kontrakt widoku: slug, label_html,
     * intro oraz opcjonalnie icon (ścieżka względem storage lub null).
     */
    public function index()
    {
        $categories = Category::query()
            ->active()->topLevel()->ordered()
            ->get()
            ->map(fn (Category $cat) => [
                'slug' => $cat->slug,
                'label_html' => $cat->label_html ?: e($cat->label_text),
                'intro' => $cat->intro,
                'icon' => $cat->icon,
                // Link kafelka sterowany polem „źródło" (z panelu), nie na sztywno:
                // 'beneficiaries' -> podstrona „Wspieramy", inaczej strona kategorii.
                'url' => $cat->source === 'beneficiaries'
                    ? route('beneficiaries')
                    : route('category', $cat->slug),
            ])
            ->all();

        // Logo mecenasa (LokalnyRolnik) do modala podziękowania — pierwszy istniejący format.
        $mecenasLogo = null;
        foreach (['svg', 'png', 'webp', 'jpg'] as $ext) {
            if (is_file(public_path("img/mecenasi/lokalnyrolnik.$ext"))) {
                $mecenasLogo = asset("img/mecenasi/lokalnyrolnik.$ext");
                break;
            }
        }

        return Inertia::render('Storefront/Home', [
            'categories' => $categories,
            // Modal „Dziękujemy" po powrocie z płatności (redirect ...?dzieki=1).
            'showThanks' => (bool) request('dzieki'),
            'mecenasLogo' => $mecenasLogo,
            'mecenasUrl' => route('mecenasi.lokalnyrolnik'),
            'mainUrl' => route('main'),
        ]);
    }

    /**
     * GET /kategoria/{slug} — strona kategorii. Dla source=='parishes' listuje
     * realne parafie z bazy; pozostałe kategorie mają pusty stan.
     */
    public function category(string $slug)
    {
        $model = Category::query()->active()->where('slug', $slug)->first();

        abort_if($model === null, 404);

        $products = $model->source === 'parishes'
            ? Product::where('active', true)->orderBy('id')->get()
            : collect();

        // 16 województw RP + mapa wybranych miast → województwo (fallback, gdy
        // products.voivodeship puste). Filtr wyszukiwarki działa po stronie React.
        $voivodeships = [
            'dolnośląskie', 'kujawsko-pomorskie', 'lubelskie', 'lubuskie',
            'łódzkie', 'małopolskie', 'mazowieckie', 'opolskie',
            'podkarpackie', 'podlaskie', 'pomorskie', 'śląskie',
            'świętokrzyskie', 'warmińsko-mazurskie', 'wielkopolskie', 'zachodniopomorskie',
        ];
        $cityToVoiv = [
            'kraków' => 'małopolskie', 'tarnów' => 'małopolskie', 'nowy sącz' => 'małopolskie',
            'warszawa' => 'mazowieckie', 'radom' => 'mazowieckie', 'płock' => 'mazowieckie',
            'wrocław' => 'dolnośląskie', 'świdnica' => 'dolnośląskie', 'wałbrzych' => 'dolnośląskie', 'legnica' => 'dolnośląskie', 'jelenia góra' => 'dolnośląskie',
            'poznań' => 'wielkopolskie', 'kalisz' => 'wielkopolskie', 'konin' => 'wielkopolskie', 'licheń stary' => 'wielkopolskie', 'gniezno' => 'wielkopolskie',
            'gdańsk' => 'pomorskie', 'gdynia' => 'pomorskie', 'sopot' => 'pomorskie', 'słupsk' => 'pomorskie',
            'katowice' => 'śląskie', 'częstochowa' => 'śląskie', 'gliwice' => 'śląskie', 'sosnowiec' => 'śląskie', 'bielsko-biała' => 'śląskie',
            'łódź' => 'łódzkie', 'piotrków trybunalski' => 'łódzkie',
            'lublin' => 'lubelskie', 'zamość' => 'lubelskie', 'chełm' => 'lubelskie',
            'szczecin' => 'zachodniopomorskie', 'koszalin' => 'zachodniopomorskie',
            'bydgoszcz' => 'kujawsko-pomorskie', 'toruń' => 'kujawsko-pomorskie', 'włocławek' => 'kujawsko-pomorskie',
            'białystok' => 'podlaskie', 'łomża' => 'podlaskie', 'suwałki' => 'podlaskie',
            'rzeszów' => 'podkarpackie', 'przemyśl' => 'podkarpackie', 'krosno' => 'podkarpackie',
            'kielce' => 'świętokrzyskie', 'ostrowiec świętokrzyski' => 'świętokrzyskie',
            'olsztyn' => 'warmińsko-mazurskie', 'elbląg' => 'warmińsko-mazurskie',
            'opole' => 'opolskie', 'nysa' => 'opolskie',
            'gorzów wielkopolski' => 'lubuskie', 'zielona góra' => 'lubuskie',
        ];
        $voivFor = function (Product $p) use ($cityToVoiv) {
            $v = trim((string) ($p->voivodeship ?? ''));
            if ($v !== '') {
                return mb_strtolower($v, 'UTF-8');
            }

            return $cityToVoiv[mb_strtolower(trim((string) ($p->city ?? '')), 'UTF-8')] ?? '';
        };

        $items = $products->map(fn (Product $p) => [
            'slug' => $p->slug,
            'name' => $p->name,
            'city' => $p->city,
            'voiv' => $voivFor($p),
            'purpose' => $p->purpose,
            'price' => $p->pricePln(),
            'image' => $p->main_image ? asset('storage/' . $p->main_image) : null,
            'url' => route('product.show', $p->slug),
        ])->values();

        return Inertia::render('Storefront/Category', [
            'category' => [
                'label_text' => $model->label_text,
                'intro' => $model->intro,
                'slug' => $model->slug,
                'source' => $model->source,
            ],
            'products' => $items,
            'cities' => $products->pluck('city')->filter()->map(fn ($c) => trim((string) $c))->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'voivodeships' => $voivodeships,
            'mainUrl' => route('main'),
            'cultUrl' => route('category', 'miejsca-kultu'),
            'pageTitle' => $model->label_text . ' — SupportME',
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
            'categoryUrl' => route('category', 'miejsca-kultu'),
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
            'categoryUrl' => route('category', 'miejsca-kultu'),
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
