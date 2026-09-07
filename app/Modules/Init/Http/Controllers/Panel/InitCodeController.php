<?php

namespace App\Modules\Init\Http\Controllers\Panel;

use App\Modules\Init\Http\Controllers\Controller;
use App\Modules\Storefront\Models\Organization;
use App\Services\OrgSvcClient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Panel organizacji: tagi/kody organizacyjne — cel to zawsze konkretny
 * produkt ("zbiórka"), zarządzane przez tego, kto administruje aktywną
 * organizacją. Kody OSOBISTE (właściciel = konto, nie organizacja) mają
 * osobny kontroler — patrz Panel\MyInitCodeController ("Moje tagi").
 *
 * Faza 5 migracji: InitCode żyje w core-svc (nie org-svc — inny dom, patrz
 * OrgSvcClient), ShopItem w org-svc (Faza 3) — oba przez ten sam api-gateway.
 */
class InitCodeController extends Controller
{
    private Organization $org;

    public function __construct(Request $request)
    {
        $org = $request->user()->activeOrganization($request);
        abort_unless($org && $org->canSee('init-codes'), 403);
        $this->org = $org;
    }

    public function index()
    {
        $codes = app(OrgSvcClient::class)->listInitCodes(organizationId: $this->org->id);
        usort($codes, fn ($a, $b) => $b['id'] <=> $a['id']);
        $shopItems = collect($this->shopItemOptions())->keyBy('id');

        return Inertia::render('Panel/InitCodes/Index', [
            'items' => array_values(array_map(fn (array $c) => $this->present($c, $shopItems), $codes)),
            'createUrl' => route('panel.init-codes.create'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Panel/InitCodes/Form', [
            'item' => null,
            'shopItems' => $this->shopItemOptions(),
            'storeUrl' => route('panel.init-codes.store'),
            'indexUrl' => route('panel.init-codes.index'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        app(OrgSvcClient::class)->createInitCode(
            ['organizationId' => $this->org->id],
            $data['label'] ?? null,
            $data['shop_item_id'] ?? null,
            null
        );

        return redirect()->route('panel.init-codes.index')->with('success', 'Kod dodany.');
    }

    public function edit(int $initCode)
    {
        $item = $this->ownedCodeOrAbort($initCode);
        $shopItems = collect($this->shopItemOptions())->keyBy('id');

        return Inertia::render('Panel/InitCodes/Form', [
            'item' => $this->present($item, $shopItems),
            'shopItems' => $shopItems->values()->all(),
            'storeUrl' => route('panel.init-codes.store'),
            'indexUrl' => route('panel.init-codes.index'),
        ]);
    }

    public function update(Request $request, int $initCode)
    {
        $this->ownedCodeOrAbort($initCode);
        $data = $this->validated($request);

        app(OrgSvcClient::class)->updateInitCode(
            $initCode,
            ['organizationId' => $this->org->id],
            $data['label'] ?? null,
            $data['shop_item_id'] ?? null,
            null
        );

        return redirect()->route('panel.init-codes.index')->with('success', 'Kod zapisany.');
    }

    public function toggle(int $initCode)
    {
        $this->ownedCodeOrAbort($initCode);
        $result = app(OrgSvcClient::class)->toggleInitCode($initCode, ['organizationId' => $this->org->id]);

        return back()->with('success', $result['active'] ? 'Kod aktywowany.' : 'Kod dezaktywowany.');
    }

    public function destroy(int $initCode)
    {
        $this->ownedCodeOrAbort($initCode);
        app(OrgSvcClient::class)->deleteInitCode($initCode, ['organizationId' => $this->org->id]);

        return redirect()->route('panel.init-codes.index')->with('success', 'Kod usunięty.');
    }

    /** Ładuje kod i weryfikuje, że należy do aktywnej organizacji. */
    private function ownedCodeOrAbort(int $id): array
    {
        $codes = app(OrgSvcClient::class)->listInitCodes(organizationId: $this->org->id);
        $item = collect($codes)->firstWhere('id', $id);
        abort_unless($item, 403);

        return $item;
    }

    /**
     * core-svc.InitCodeService nie ma dziś pola `active` w Create/Update
     * (tylko Toggle) — (de)aktywacja idzie wyłącznie przez `toggle()`.
     */
    private function validated(Request $request): array
    {
        $validIds = array_column($this->shopItemOptions(), 'id');

        return $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'shop_item_id' => ['nullable', 'integer', Rule::in($validIds)],
        ]);
    }

    /** Produkty WŁASNEJ organizacji do przypisania jako cel. */
    private function shopItemOptions(): array
    {
        $items = app(OrgSvcClient::class)->listShopItems($this->org->id);
        usort($items, fn ($a, $b) => $a['sort'] <=> $b['sort']);

        return array_values(array_map(fn (array $i) => ['id' => $i['id'], 'name' => $i['name']], $items));
    }

    private function present(array $code, \Illuminate\Support\Collection $shopItems): array
    {
        return [
            'id' => $code['id'],
            'uuid' => $code['uuid'],
            'label' => $code['label'],
            'shop_item_id' => $code['shopItemId'],
            'shop_item_name' => $code['shopItemId'] ? ($shopItems->get($code['shopItemId'])['name'] ?? null) : null,
            'active' => (bool) $code['active'],
            'tag_url' => route('init.tag', $code['uuid']),
            'qr_url' => route('init.qr', $code['uuid']),
            'update_url' => route('panel.init-codes.update', $code['id']),
            'edit_url' => route('panel.init-codes.edit', $code['id']),
            'toggle_url' => route('panel.init-codes.toggle', $code['id']),
            'destroy_url' => route('panel.init-codes.destroy', $code['id']),
        ];
    }
}
