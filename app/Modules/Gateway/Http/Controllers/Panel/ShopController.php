<?php

namespace App\Modules\Gateway\Http\Controllers\Panel;

use App\Modules\Gateway\Http\Controllers\Controller;
use App\Modules\Gateway\Models\Shop;
use App\Modules\Gateway\Services\StatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShopController extends Controller
{
    public function index(StatsService $stats)
    {
        $shops = Shop::withCount('tags')->get()->map(fn (Shop $shop) => [
            'shop' => $shop,
            'stats' => $stats->summary(shopId: $shop->id),
        ]);

        return view('panel.shops.index', compact('shops'));
    }

    public function create()
    {
        return view('panel.shops.form', ['shop' => new Shop()]);
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
        return view('panel.shops.form', compact('shop'));
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
