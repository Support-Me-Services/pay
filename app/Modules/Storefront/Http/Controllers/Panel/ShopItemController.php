<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Models\Organization;
use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\ShopItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Panel: produkty sklepu (NFC). Zarządzanie listą, ceną, opisem,
 * tagiem NFC oraz produktem domyślnym („Serduszko"). Sekcja per‑organizacja
 * (aktywna organizacja usera).
 */
class ShopItemController extends Controller
{
    private Organization $org;

    public function __construct(Request $request)
    {
        $org = $request->user()->activeOrganization($request);
        abort_unless($org && $org->canSee('shop-items'), 403);
        $this->org = $org;
    }

    public function index()
    {
        $items = ShopItem::forOrganization($this->org->id)->ordered()->get();

        return Inertia::render('Panel/ShopItems/Index', [
            'items' => $items->map(fn (ShopItem $i) => $this->present($i))->values(),
            'createUrl' => route('panel.shop-items.create'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Panel/ShopItems/Form', [
            'item' => null,
            'organizations' => $this->organizationOptions(),
            'storeUrl' => route('panel.shop-items.store'),
            'indexUrl' => route('panel.shop-items.index'),
        ]);
    }

    public function store(Request $request)
    {
        $item = ShopItem::create($this->validated($request) + ['organization_id' => $this->org->id]);
        $this->applyDefault($request, $item);

        return redirect()->route('panel.shop-items.index')->with('success', 'Produkt dodany.');
    }

    public function edit(ShopItem $shopItem)
    {
        $this->guard($shopItem);

        return Inertia::render('Panel/ShopItems/Form', [
            'item' => $this->present($shopItem),
            'organizations' => $this->organizationOptions(),
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

    /** Tylko aktywna organizacja może edytować/usuwać swój produkt. */
    private function guard(ShopItem $item): void
    {
        abort_unless((int) $item->organization_id === $this->org->id, 403);
    }

    /** Lista organizacji do wyboru mecenasa (dropdown w formularzu). */
    private function organizationOptions(): array
    {
        return Organization::orderBy('name')->get()
            ->map(fn (Organization $o) => ['id' => $o->id, 'name' => $o->name])
            ->values()->all();
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
            'sort' => (int) $item->sort,
            'is_default' => (bool) $item->is_default,
            'active' => (bool) $item->active,
            'image' => $item->image ? asset($item->image) : null,
            'thank_you_heading' => $item->thank_you_heading,
            'thank_you_body' => $item->thank_you_body,
            'thank_you_image' => $item->thank_you_image ? asset($item->thank_you_image) : null,
            'mecenas_organization_id' => $item->mecenas_organization_id,
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
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('shop_items', 'slug')->where('organization_id', $this->org->id)->ignore($current?->id)],
            'price_pln' => ['required', 'integer', 'min:1', 'max:5000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'active' => ['nullable', 'boolean'],
            'thank_you_heading' => ['nullable', 'string', 'max:255'],
            'thank_you_body' => ['nullable', 'string', 'max:5000'],
            'thank_you_image_file' => ['nullable', 'image', 'max:5120'],
            'remove_thank_you_image' => ['nullable', 'boolean'],
            'mecenas_organization_id' => ['nullable', 'integer', Rule::exists('organizations', 'id')],
        ], [], [
            'name' => 'nazwa',
            'price_pln' => 'cena',
            'image_file' => 'grafika',
            'thank_you_heading' => 'nagłówek podziękowania',
            'thank_you_body' => 'treść podziękowania',
            'thank_you_image_file' => 'grafika podziękowania',
            'mecenas_organization_id' => 'mecenas',
        ]);

        $priceGr = (int) $data['price_pln'] * 100;

        $out = [
            'name' => $data['name'],
            'slug' => Str::slug($data['slug'] ?? '') ?: Str::slug($data['name']),
            'price' => $priceGr,
            'min_amount' => $priceGr,   // w trybie sklepu min = cena (spójność z modelem darowiznowym)
            'description' => $data['description'] ?? null,
            'sort' => (int) ($data['sort'] ?? 0),
            'active' => $request->boolean('active'),
            'thank_you_heading' => $data['thank_you_heading'] ?? null,
            'thank_you_body' => $data['thank_you_body'] ?? null,
            'mecenas_organization_id' => $data['mecenas_organization_id'] ?? null,
        ];

        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('shop-items', 'public');
            $out['image'] = 'storage/' . $path;
        }

        if ($request->hasFile('thank_you_image_file')) {
            $this->deleteStoredFile($current?->thank_you_image);
            $path = $request->file('thank_you_image_file')->store('shop-items', 'public');
            $out['thank_you_image'] = 'storage/' . $path;
        } elseif ($request->boolean('remove_thank_you_image')) {
            $this->deleteStoredFile($current?->thank_you_image);
            $out['thank_you_image'] = null;
        }

        return $out;
    }

    /** Usuwa plik zapisany z prefiksem "storage/" (patrz $out['image'] itd.) z dysku 'public'. */
    private function deleteStoredFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete(Str::after($path, 'storage/'));
        }
    }

    /** Tylko jeden produkt może być domyślny — ustaw/wyłącz pozostałe. */
    private function applyDefault(Request $request, ShopItem $item): void
    {
        if ($request->boolean('is_default')) {
            ShopItem::where('organization_id', $item->organization_id)->where('id', '!=', $item->id)->update(['is_default' => false]);
            $item->update(['is_default' => true]);
        } elseif ($item->is_default) {
            // odznaczono domyślny — pozostaw bez domyślnego (lub wymuś inny w UI)
            $item->update(['is_default' => false]);
        }
    }
}
