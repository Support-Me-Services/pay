<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\ShopItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Panel: produkty sklepu donacyjnego (NFC). Zarządzanie listą, minimalną kwotą,
 * tagiem NFC oraz produktem domyślnym („Serduszko").
 */
class ShopItemController extends Controller
{
    public function index()
    {
        $items = ShopItem::orderBy('sort')->orderBy('id')->get();

        return view('panel.shop-items.index', compact('items'));
    }

    public function create()
    {
        return view('panel.shop-items.form', ['item' => new ShopItem(['min_amount' => 100, 'active' => true])]);
    }

    public function store(Request $request)
    {
        $item = ShopItem::create($this->validated($request));
        $this->applyDefault($request, $item);

        return redirect()->route('panel.shop-items.index')->with('success', 'Produkt dodany.');
    }

    public function edit(ShopItem $shopItem)
    {
        return view('panel.shop-items.form', ['item' => $shopItem]);
    }

    public function update(Request $request, ShopItem $shopItem)
    {
        $shopItem->update($this->validated($request, $shopItem));
        $this->applyDefault($request, $shopItem);

        return redirect()->route('panel.shop-items.index')->with('success', 'Produkt zapisany.');
    }

    public function toggle(ShopItem $shopItem)
    {
        $shopItem->update(['active' => ! $shopItem->active]);

        return back()->with('success', $shopItem->active ? 'Produkt aktywowany.' : 'Produkt dezaktywowany.');
    }

    public function destroy(ShopItem $shopItem)
    {
        $shopItem->delete();

        return redirect()->route('panel.shop-items.index')->with('success', 'Produkt usunięty.');
    }

    /** Walidacja + normalizacja (zł→grosze, slug, upload grafiki). */
    private function validated(Request $request, ?ShopItem $current = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('shop_items', 'slug')->ignore($current?->id)],
            'min_amount_pln' => ['required', 'integer', 'min:1', 'max:5000'],
            'tag_uid' => ['nullable', 'string', 'max:255', Rule::unique('shop_items', 'tag_uid')->ignore($current?->id)],
            'sort' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'active' => ['nullable', 'boolean'],
        ], [], [
            'name' => 'nazwa',
            'min_amount_pln' => 'minimalna kwota',
            'tag_uid' => 'tag NFC',
            'image_file' => 'grafika',
        ]);

        $out = [
            'name' => $data['name'],
            'slug' => Str::slug($data['slug'] ?? '') ?: Str::slug($data['name']),
            'min_amount' => (int) $data['min_amount_pln'] * 100,
            'tag_uid' => $data['tag_uid'] ?? null,
            'sort' => (int) ($data['sort'] ?? 0),
            'active' => $request->boolean('active'),
        ];

        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('shop-items', 'public');
            $out['image'] = 'storage/' . $path;
        }

        return $out;
    }

    /** Tylko jeden produkt może być domyślny — ustaw/wyłącz pozostałe. */
    private function applyDefault(Request $request, ShopItem $item): void
    {
        if ($request->boolean('is_default')) {
            ShopItem::where('id', '!=', $item->id)->update(['is_default' => false]);
            $item->update(['is_default' => true]);
        } elseif ($item->is_default) {
            // odznaczono domyślny — pozostaw bez domyślnego (lub wymuś inny w UI)
            $item->update(['is_default' => false]);
        }
    }
}
