<?php

namespace App\Modules\Init\Http\Controllers\Panel;

use App\Modules\Init\Http\Controllers\Controller;
use App\Modules\Init\Models\InitCode;
use App\Modules\Storefront\Models\Organization;
use App\Modules\Storefront\Models\ShopItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Panel organizacji: tagi/kody organizacyjne — cel to zawsze konkretny
 * produkt ("zbiórka"), zarządzane przez tego, kto administruje aktywną
 * organizacją. Kody OSOBISTE (właściciel = konto, nie organizacja) mają
 * osobny kontroler — patrz Panel\MyInitCodeController ("Moje tagi").
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
        $codes = InitCode::forOrganization($this->org->id)->with('shopItem')->latest()->get();

        return Inertia::render('Panel/InitCodes/Index', [
            'items' => $codes->map(fn (InitCode $c) => $this->present($c))->values(),
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

        InitCode::create([
            'organization_id' => $this->org->id,
            'label' => $data['label'] ?? null,
            'shop_item_id' => $data['shop_item_id'] ?? null,
            'active' => $request->boolean('active', true),
        ]);

        return redirect()->route('panel.init-codes.index')->with('success', 'Kod dodany.');
    }

    public function edit(InitCode $initCode)
    {
        $this->guard($initCode);

        return Inertia::render('Panel/InitCodes/Form', [
            'item' => $this->present($initCode),
            'shopItems' => $this->shopItemOptions(),
            'storeUrl' => route('panel.init-codes.store'),
            'indexUrl' => route('panel.init-codes.index'),
        ]);
    }

    public function update(Request $request, InitCode $initCode)
    {
        $this->guard($initCode);
        $data = $this->validated($request);

        $initCode->update([
            'label' => $data['label'] ?? null,
            'shop_item_id' => $data['shop_item_id'] ?? null,
            'active' => $request->boolean('active'),
        ]);

        return redirect()->route('panel.init-codes.index')->with('success', 'Kod zapisany.');
    }

    public function toggle(InitCode $initCode)
    {
        $this->guard($initCode);
        $initCode->update(['active' => ! $initCode->active]);

        return back()->with('success', $initCode->active ? 'Kod aktywowany.' : 'Kod dezaktywowany.');
    }

    public function destroy(InitCode $initCode)
    {
        $this->guard($initCode);
        $initCode->delete();

        return redirect()->route('panel.init-codes.index')->with('success', 'Kod usunięty.');
    }

    /** Tylko aktywna organizacja może edytować/usuwać swój kod. */
    private function guard(InitCode $code): void
    {
        abort_unless((int) $code->organization_id === $this->org->id, 403);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'shop_item_id' => ['nullable', 'integer', Rule::exists('shop_items', 'id')->where('organization_id', $this->org->id)],
            'active' => ['nullable', 'boolean'],
        ]);
    }

    /** Produkty WŁASNEJ organizacji do przypisania jako cel. */
    private function shopItemOptions(): array
    {
        return ShopItem::forOrganization($this->org->id)->ordered()->get()
            ->map(fn (ShopItem $i) => ['id' => $i->id, 'name' => $i->name])
            ->values()->all();
    }

    private function present(InitCode $code): array
    {
        return [
            'id' => $code->id,
            'uuid' => $code->uuid,
            'label' => $code->label,
            'shop_item_id' => $code->shop_item_id,
            'shop_item_name' => $code->shopItem?->name,
            'active' => (bool) $code->active,
            'tag_url' => route('init.tag', $code->uuid),
            'qr_url' => route('init.qr', $code->uuid),
            'update_url' => route('panel.init-codes.update', $code),
            'edit_url' => route('panel.init-codes.edit', $code),
            'toggle_url' => route('panel.init-codes.toggle', $code),
            'destroy_url' => route('panel.init-codes.destroy', $code),
        ];
    }
}
