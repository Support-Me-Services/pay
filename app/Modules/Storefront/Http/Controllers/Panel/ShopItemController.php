<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\ShopItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Panel: produkty sklepu (NFC). Zarządzanie listą, ceną, opisem,
 * tagiem NFC oraz produktem domyślnym („Serduszko").
 */
class ShopItemController extends Controller
{
    public function index()
    {
        $items = ShopItem::forUser(Auth::id())->ordered()->get();

        return Inertia::render('Panel/ShopItems/Index', [
            'items' => $items->map(fn (ShopItem $i) => $this->present($i))->values(),
            'createUrl' => route('panel.shop-items.create'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Panel/ShopItems/Form', [
            'item' => null,
            'storeUrl' => route('panel.shop-items.store'),
            'indexUrl' => route('panel.shop-items.index'),
        ]);
    }

    public function store(Request $request)
    {
        $item = ShopItem::create($this->validated($request) + ['user_id' => Auth::id()]);
        $this->applyDefault($request, $item);

        return redirect()->route('panel.shop-items.index')->with('success', 'Produkt dodany.');
    }

    public function edit(ShopItem $shopItem)
    {
        $this->guard($shopItem);

        return Inertia::render('Panel/ShopItems/Form', [
            'item' => $this->present($shopItem),
            'storeUrl' => route('panel.shop-items.store'),
            'indexUrl' => route('panel.shop-items.index'),
        ]);
    }

    public function update(Request $request, ShopItem $shopItem)
    {
        $this->guard($shopItem);
        $shopItem->update($this->validated($request, $shopItem));
        $this->applyDefault($request, $shopItem);

        return redirect()->route('panel.shop-items.index')->with('success', 'Produkt zapisany.');
    }

    public function toggle(ShopItem $shopItem)
    {
        $this->guard($shopItem);
        $shopItem->update(['active' => ! $shopItem->active]);

        return back()->with('success', $shopItem->active ? 'Produkt aktywowany.' : 'Produkt dezaktywowany.');
    }

    public function destroy(ShopItem $shopItem)
    {
        $this->guard($shopItem);
        $shopItem->delete();

        return redirect()->route('panel.shop-items.index')->with('success', 'Produkt usunięty.');
    }

    /** Tylko właściciel może edytować/usuwać swój produkt. */
    private function guard(ShopItem $item): void
    {
        abort_unless((int) $item->user_id === (int) Auth::id(), 403);
    }

    /** Serializacja produktu dla warstwy React (Inertia). */
    private function present(ShopItem $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'slug' => $item->slug,
            'description' => $item->description,
            'price_pln' => $item->pricePln(),
            'min_amount_pln' => $item->minAmountPln(),
            'tag_uid' => $item->tag_uid,
            'sort' => (int) $item->sort,
            'is_default' => (bool) $item->is_default,
            'active' => (bool) $item->active,
            'image' => $item->image ? asset($item->image) : null,
            'update_url' => route('panel.shop-items.update', $item),
            'edit_url' => route('panel.shop-items.edit', $item),
            'toggle_url' => route('panel.shop-items.toggle', $item),
            'destroy_url' => route('panel.shop-items.destroy', $item),
        ];
    }

    /** Walidacja + normalizacja (zł→grosze, slug, upload grafiki). */
    private function validated(Request $request, ?ShopItem $current = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('shop_items', 'slug')->where('user_id', Auth::id())->ignore($current?->id)],
            'price_pln' => ['required', 'integer', 'min:1', 'max:5000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'tag_uid' => ['nullable', 'string', 'max:255', Rule::unique('shop_items', 'tag_uid')->ignore($current?->id)],
            'sort' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'active' => ['nullable', 'boolean'],
        ], [], [
            'name' => 'nazwa',
            'price_pln' => 'cena',
            'tag_uid' => 'tag NFC',
            'image_file' => 'grafika',
        ]);

        $priceGr = (int) $data['price_pln'] * 100;

        $out = [
            'name' => $data['name'],
            'slug' => Str::slug($data['slug'] ?? '') ?: Str::slug($data['name']),
            'price' => $priceGr,
            'min_amount' => $priceGr,   // w trybie sklepu min = cena (spójność z modelem darowiznowym)
            'description' => $data['description'] ?? null,
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
            ShopItem::where('user_id', $item->user_id)->where('id', '!=', $item->id)->update(['is_default' => false]);
            $item->update(['is_default' => true]);
        } elseif ($item->is_default) {
            // odznaczono domyślny — pozostaw bez domyślnego (lub wymuś inny w UI)
            $item->update(['is_default' => false]);
        }
    }
}
