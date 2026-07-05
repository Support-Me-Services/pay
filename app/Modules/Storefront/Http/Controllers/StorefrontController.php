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

        return view('shop.home', ['categories' => $categories]);
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

        $category = [
            'label_text' => $model->label_text,
            'intro' => $model->intro,
            'slug' => $model->slug,
            'source' => $model->source,
        ];

        return view('shop.category', compact('category', 'products'));
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

        return response()->view('shop.tag-not-found', [], 404);
    }

    /**
     * GET /p/{slug} — strona produktu.
     */
    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)->where('active', true)->firstOrFail();

        Event::create(['product_id' => $product->id, 'type' => 'page_view']);

        return view('shop.product', compact('product'));
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
