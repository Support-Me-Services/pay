<?php

namespace App\Modules\Gateway\Http\Controllers\Panel;

use App\Modules\Gateway\Http\Controllers\Controller;
use App\Modules\Gateway\Models\Shop;
use App\Modules\Gateway\Services\StatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ShopController extends Controller
{
    public function index(StatsService $stats)
    {
        $shops = Shop::withCount('tags')->get()->map(function (Shop $shop) use ($stats) {
            $s = $stats->summary(shopId: $shop->id);

            return [
                'id' => $shop->id,
                'name' => $shop->name,
                'base_url' => $shop->base_url,
                'payment_mode' => $shop->payment_mode,
                'tags_count' => $shop->tags_count,
                'revenue' => StatsService::formatPln($s['revenue']),
                'conversion' => $s['conversion'],
                'tags_url' => route('panel.tags.index', $shop),
                'edit_url' => route('panel.shops.edit', $shop),
            ];
        })->values();

        return Inertia::render('Gateway/Shops/Index', [
            'shops' => $shops,
            'createUrl' => route('panel.shops.create'),
            // Klucz API nowego sklepu — pokazywany jednorazowo (flash z store()).
            'newApiKey' => session('new_api_key'),
        ]);
    }

    public function create()
    {
        return $this->form(new Shop());
    }

    /** Wspólny render formularza sklepu (Inertia). */
    private function form(Shop $shop)
    {
        return Inertia::render('Gateway/Shops/Form', [
            'shop' => [
                'exists' => $shop->exists,
                'name' => $shop->name,
                'base_url' => $shop->base_url,
                'payment_mode' => $shop->payment_mode ?? 'classic',
            ],
            'urls' => [
                'store' => route('panel.shops.store'),
                'update' => $shop->exists ? route('panel.shops.update', $shop) : null,
                'index' => route('panel.shops.index'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'base_url' => ['required', 'url', 'max:255'],
            'payment_mode' => ['required', 'in:classic,app2app'],
        ], [], ['name' => 'nazwa', 'base_url' => 'adres URL', 'payment_mode' => 'tryb płatności']);

        $apiKey = Shop::generateApiKey();

        Shop::create([
            ...$data,
            'slug' => Str::slug($data['name']) . '-' . Str::lower(Str::random(4)),
            'api_key' => $apiKey,
        ]);

        // Klucz API pokazujemy tylko raz — zaraz po utworzeniu.
        return redirect()->route('panel.shops.index')
            ->with('new_api_key', $apiKey)
            ->with('success', 'Sklep dodany. Skopiuj klucz API — nie zostanie ponownie wyświetlony.');
    }

    public function edit(Shop $shop)
    {
        return $this->form($shop);
    }

    public function update(Request $request, Shop $shop)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'base_url' => ['required', 'url', 'max:255'],
            'payment_mode' => ['required', 'in:classic,app2app'],
        ], [], ['name' => 'nazwa', 'base_url' => 'adres URL', 'payment_mode' => 'tryb płatności']);

        $shop->update($data);

        return redirect()->route('panel.shops.index')->with('success', 'Sklep zapisany.');
    }
}
