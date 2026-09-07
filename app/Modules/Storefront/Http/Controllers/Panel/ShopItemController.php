<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Models\Organization;
use App\Modules\Storefront\Http\Controllers\Controller;
use App\Services\OrgSvcClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Panel: produkty sklepu (NFC). Zarządzanie listą, ceną, opisem,
 * tagiem NFC oraz produktem domyślnym („Serduszko"). Sekcja per‑organizacja
 * (aktywna organizacja usera).
 *
 * Faza 3 migracji: dane i CRUD w org-svc (przez api-gateway) — ShopItem
 * już nie ma lokalnej tabeli w Laravelu. Unikalność slugu per organizacja
 * i niezmiennik "jeden domyślny" egzekwuje teraz org-svc. Sam upload/
 * serwowanie obrazu (GCS) zostaje w Laravelu.
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
        $items = app(OrgSvcClient::class)->listShopItems($this->org->id);
        usort($items, fn ($a, $b) => $a['sort'] <=> $b['sort']);

        return Inertia::render('Panel/ShopItems/Index', [
            'items' => array_values(array_map(fn (array $i) => $this->present($i), $items)),
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
        $data = $this->validated($request);
        $data['organizationId'] = $this->org->id;

        app(OrgSvcClient::class)->createShopItem($data);

        return redirect()->route('panel.shop-items.index')->with('success', 'Produkt dodany.');
    }

    public function edit(int $shopItem)
    {
        $item = $this->ownedItemOrAbort($shopItem);

        return Inertia::render('Panel/ShopItems/Form', [
            'item' => $this->present($item),
            'organizations' => $this->organizationOptions(),
            'storeUrl' => route('panel.shop-items.store'),
            'indexUrl' => route('panel.shop-items.index'),
        ]);
    }

    public function update(Request $request, int $shopItem)
    {
        $current = $this->ownedItemOrAbort($shopItem);
        $data = $this->validated($request, $current);
        $data['organizationId'] = $this->org->id;

        app(OrgSvcClient::class)->updateShopItem($shopItem, $data);

        return redirect()->route('panel.shop-items.index')->with('success', 'Produkt zapisany.');
    }

    public function toggle(int $shopItem)
    {
        $this->ownedItemOrAbort($shopItem);
        $result = app(OrgSvcClient::class)->toggleShopItem($shopItem, $this->org->id);

        return back()->with('success', $result['active'] ? 'Produkt aktywowany.' : 'Produkt dezaktywowany.');
    }

    public function destroy(int $shopItem)
    {
        $this->ownedItemOrAbort($shopItem);
        app(OrgSvcClient::class)->deleteShopItem($shopItem, $this->org->id);

        return redirect()->route('panel.shop-items.index')->with('success', 'Produkt usunięty.');
    }

    /** Ładuje produkt i weryfikuje, że należy do aktywnej organizacji. */
    private function ownedItemOrAbort(int $id): array
    {
        $item = app(OrgSvcClient::class)->getShopItem($id);
        abort_unless((int) $item['organizationId'] === $this->org->id, 403);

        return $item;
    }

    /** Lista organizacji do wyboru mecenasa (dropdown w formularzu). */
    private function organizationOptions(): array
    {
        return collect(Organization::allOrderedByName())
            ->map(fn (Organization $o) => ['id' => $o->id, 'name' => $o->name])
            ->values()->all();
    }

    /** Serializacja produktu (odpowiedź org-svc) dla warstwy React (Inertia). */
    private function present(array $item): array
    {
        $pricePln = (int) round($item['priceGrosze'] / 100);

        return [
            'id' => $item['id'],
            'name' => $item['name'],
            'slug' => $item['slug'],
            'description' => $item['description'],
            'price_pln' => $pricePln,
            'min_amount_pln' => $pricePln,
            'sort' => (int) $item['sort'],
            'is_default' => (bool) $item['isDefault'],
            'active' => (bool) $item['active'],
            'image' => $item['image'] ? asset($item['image']) : null,
            'thank_you_heading' => $item['thankYouHeading'],
            'thank_you_body' => $item['thankYouBody'],
            'thank_you_image' => $item['thankYouImage'] ? asset($item['thankYouImage']) : null,
            'mecenas_organization_id' => $item['mecenasOrganizationId'],
            'update_url' => route('panel.shop-items.update', $item['id']),
            'edit_url' => route('panel.shop-items.edit', $item['id']),
            'toggle_url' => route('panel.shop-items.toggle', $item['id']),
            'destroy_url' => route('panel.shop-items.destroy', $item['id']),
        ];
    }

    /** Walidacja + normalizacja (zł→grosze, slug, upload grafiki). Unikalność slugu egzekwuje org-svc. */
    private function validated(Request $request, ?array $current = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'price_pln' => ['required', 'integer', 'min:1', 'max:5000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'thank_you_heading' => ['nullable', 'string', 'max:255'],
            'thank_you_body' => ['nullable', 'string', 'max:5000'],
            'thank_you_image_file' => ['nullable', 'image', 'max:5120'],
            'remove_thank_you_image' => ['nullable', 'boolean'],
            'mecenas_organization_id' => ['nullable', 'integer', Rule::in(array_column(Organization::allOrderedByName(), 'id'))],
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
            'slug' => $data['slug'] ? Str::slug($data['slug']) : null,
            'priceGrosze' => $priceGr,
            'description' => $data['description'] ?? null,
            'sort' => (int) ($data['sort'] ?? 0),
            'active' => $request->boolean('active'),
            'isDefault' => $request->boolean('is_default'),
            'thankYouHeading' => $data['thank_you_heading'] ?? null,
            'thankYouBody' => $data['thank_you_body'] ?? null,
            'mecenasOrganizationId' => $data['mecenas_organization_id'] ?? null,
            'clearImage' => false,
            'clearThankYouImage' => false,
            'clearMecenasOrganizationId' => empty($data['mecenas_organization_id']),
        ];

        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('shop-items', 'public');
            $out['image'] = 'storage/' . $path;
        }

        if ($request->hasFile('thank_you_image_file')) {
            $this->deleteStoredFile($current['thankYouImage'] ?? null);
            $path = $request->file('thank_you_image_file')->store('shop-items', 'public');
            $out['thankYouImage'] = 'storage/' . $path;
        } elseif ($request->boolean('remove_thank_you_image')) {
            $this->deleteStoredFile($current['thankYouImage'] ?? null);
            $out['clearThankYouImage'] = true;
        }

        return $out;
    }

    /** Usuwa plik zapisany z prefiksem "storage/" z dysku 'public'. */
    private function deleteStoredFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete(Str::after($path, 'storage/'));
        }
    }
}
