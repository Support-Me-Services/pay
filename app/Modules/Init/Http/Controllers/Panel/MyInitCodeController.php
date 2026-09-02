<?php

namespace App\Modules\Init\Http\Controllers\Panel;

use App\Models\User;
use App\Modules\Init\Http\Controllers\Controller;
use App\Modules\Init\Models\InitCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Panel: "Moje tagi" — kody OSOBISTE, należące bezpośrednio do konta (nie do
 * organizacji). Cel to zawsze cała lista zbiórek jednej z WŁASNYCH organizacji
 * użytkownika (nie pojedynczy produkt — to zastrzeżone dla kodów
 * organizacyjnych, patrz Panel\InitCodeController).
 */
class MyInitCodeController extends Controller
{
    private User $user;

    public function __construct(Request $request)
    {
        $this->user = $request->user();
    }

    public function index()
    {
        $codes = InitCode::forOwner($this->user->id)->with('targetOrganization')->latest()->get();

        return Inertia::render('Panel/MyInitCodes/Index', [
            'items' => $codes->map(fn (InitCode $c) => $this->present($c))->values(),
            'createUrl' => route('panel.my-init-codes.create'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Panel/MyInitCodes/Form', [
            'item' => null,
            'organizations' => $this->organizationOptions(),
            'storeUrl' => route('panel.my-init-codes.store'),
            'indexUrl' => route('panel.my-init-codes.index'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        InitCode::create([
            'owner_user_id' => $this->user->id,
            'label' => $data['label'] ?? null,
            'target_organization_id' => $data['target_organization_id'] ?? null,
            'active' => $request->boolean('active', true),
        ]);

        return redirect()->route('panel.my-init-codes.index')->with('success', 'Tag dodany.');
    }

    public function edit(InitCode $myInitCode)
    {
        $this->guard($myInitCode);

        return Inertia::render('Panel/MyInitCodes/Form', [
            'item' => $this->present($myInitCode),
            'organizations' => $this->organizationOptions(),
            'storeUrl' => route('panel.my-init-codes.store'),
            'indexUrl' => route('panel.my-init-codes.index'),
        ]);
    }

    public function update(Request $request, InitCode $myInitCode)
    {
        $this->guard($myInitCode);
        $data = $this->validated($request);

        $myInitCode->update([
            'label' => $data['label'] ?? null,
            'target_organization_id' => $data['target_organization_id'] ?? null,
            'active' => $request->boolean('active'),
        ]);

        return redirect()->route('panel.my-init-codes.index')->with('success', 'Tag zapisany.');
    }

    public function toggle(InitCode $myInitCode)
    {
        $this->guard($myInitCode);
        $myInitCode->update(['active' => ! $myInitCode->active]);

        return back()->with('success', $myInitCode->active ? 'Tag aktywowany.' : 'Tag dezaktywowany.');
    }

    public function destroy(InitCode $myInitCode)
    {
        $this->guard($myInitCode);
        $myInitCode->delete();

        return redirect()->route('panel.my-init-codes.index')->with('success', 'Tag usunięty.');
    }

    /** Tylko właściciel (to samo konto) może edytować/usuwać swój tag. */
    private function guard(InitCode $code): void
    {
        abort_unless((int) $code->owner_user_id === $this->user->id, 403);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'target_organization_id' => ['nullable', 'integer', Rule::exists('organizations', 'id')->where('user_id', $this->user->id)],
            'active' => ['nullable', 'boolean'],
        ]);
    }

    /** WŁASNE organizacje użytkownika do wyboru jako cel. */
    private function organizationOptions(): array
    {
        return $this->user->organizations()->orderBy('name')->get()
            ->map(fn ($o) => ['id' => $o->id, 'name' => $o->name])
            ->values()->all();
    }

    private function present(InitCode $code): array
    {
        return [
            'id' => $code->id,
            'uuid' => $code->uuid,
            'label' => $code->label,
            'target_organization_id' => $code->target_organization_id,
            'target_organization_name' => $code->targetOrganization?->name,
            'active' => (bool) $code->active,
            'tag_url' => route('init.tag', $code->uuid),
            'qr_url' => route('init.qr', $code->uuid),
            'update_url' => route('panel.my-init-codes.update', $code),
            'edit_url' => route('panel.my-init-codes.edit', $code),
            'toggle_url' => route('panel.my-init-codes.toggle', $code),
            'destroy_url' => route('panel.my-init-codes.destroy', $code),
        ];
    }
}
