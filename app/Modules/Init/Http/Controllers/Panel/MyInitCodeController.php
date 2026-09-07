<?php

namespace App\Modules\Init\Http\Controllers\Panel;

use App\Auth\KeycloakIdentity;
use App\Modules\Init\Http\Controllers\Controller;
use App\Modules\Storefront\Models\Organization;
use App\Services\OrgSvcClient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Panel: "Moje tagi" — kody OSOBISTE, należące bezpośrednio do konta (nie do
 * organizacji). Cel to zawsze cała lista zbiórek jednej z WŁASNYCH organizacji
 * użytkownika (nie pojedynczy produkt — to zastrzeżone dla kodów
 * organizacyjnych, patrz Panel\InitCodeController).
 *
 * Faza 5 migracji: dane żyją w core-svc (nie org-svc — ta jedna domena ma
 * inny dom, patrz OrgSvcClient), wywoływane przez ten sam api-gateway.
 */
class MyInitCodeController extends Controller
{
    private KeycloakIdentity $user;

    public function __construct(Request $request)
    {
        $this->user = $request->user();
    }

    public function index()
    {
        $codes = app(OrgSvcClient::class)->listInitCodes(ownerUserId: $this->user->id);
        usort($codes, fn ($a, $b) => $b['id'] <=> $a['id']);

        return Inertia::render('Panel/MyInitCodes/Index', [
            'items' => array_values(array_map(fn (array $c) => $this->present($c), $codes)),
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

        app(OrgSvcClient::class)->createInitCode(
            ['ownerUserId' => $this->user->id],
            $data['label'] ?? null,
            null,
            $data['target_organization_id'] ?? null
        );

        return redirect()->route('panel.my-init-codes.index')->with('success', 'Tag dodany.');
    }

    public function edit(int $myInitCode)
    {
        $item = $this->ownedCodeOrAbort($myInitCode);

        return Inertia::render('Panel/MyInitCodes/Form', [
            'item' => $this->present($item),
            'organizations' => $this->organizationOptions(),
            'storeUrl' => route('panel.my-init-codes.store'),
            'indexUrl' => route('panel.my-init-codes.index'),
        ]);
    }

    public function update(Request $request, int $myInitCode)
    {
        $this->ownedCodeOrAbort($myInitCode);
        $data = $this->validated($request);

        app(OrgSvcClient::class)->updateInitCode(
            $myInitCode,
            ['ownerUserId' => $this->user->id],
            $data['label'] ?? null,
            null,
            $data['target_organization_id'] ?? null
        );

        return redirect()->route('panel.my-init-codes.index')->with('success', 'Tag zapisany.');
    }

    public function toggle(int $myInitCode)
    {
        $this->ownedCodeOrAbort($myInitCode);
        $result = app(OrgSvcClient::class)->toggleInitCode($myInitCode, ['ownerUserId' => $this->user->id]);

        return back()->with('success', $result['active'] ? 'Tag aktywowany.' : 'Tag dezaktywowany.');
    }

    public function destroy(int $myInitCode)
    {
        $this->ownedCodeOrAbort($myInitCode);
        app(OrgSvcClient::class)->deleteInitCode($myInitCode, ['ownerUserId' => $this->user->id]);

        return redirect()->route('panel.my-init-codes.index')->with('success', 'Tag usunięty.');
    }

    /** Ładuje kod i weryfikuje, że należy do tego konta. */
    private function ownedCodeOrAbort(int $id): array
    {
        $codes = app(OrgSvcClient::class)->listInitCodes(ownerUserId: $this->user->id);
        $item = collect($codes)->firstWhere('id', $id);
        abort_unless($item, 403);

        return $item;
    }

    /**
     * core-svc.InitCodeService nie ma dziś pola `active` w Create/Update (tylko
     * Toggle) — świadomie pomijamy je tutaj, zamiast pisać wpół działający kod;
     * (de)aktywacja idzie wyłącznie przez `toggle()`.
     */
    private function validated(Request $request): array
    {
        $validIds = array_column($this->user->organizations(), 'id');

        return $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'target_organization_id' => ['nullable', 'integer', Rule::in($validIds)],
        ]);
    }

    /** WŁASNE organizacje użytkownika do wyboru jako cel. */
    private function organizationOptions(): array
    {
        return collect($this->user->organizations())
            ->map(fn ($o) => ['id' => $o->id, 'name' => $o->name])
            ->values()->all();
    }

    private function present(array $code): array
    {
        $targetOrg = $code['targetOrganizationId'] ? Organization::find($code['targetOrganizationId']) : null;

        return [
            'id' => $code['id'],
            'uuid' => $code['uuid'],
            'label' => $code['label'],
            'target_organization_id' => $code['targetOrganizationId'],
            'target_organization_name' => $targetOrg?->name,
            'active' => (bool) $code['active'],
            'tag_url' => route('init.tag', $code['uuid']),
            'qr_url' => route('init.qr', $code['uuid']),
            'update_url' => route('panel.my-init-codes.update', $code['id']),
            'edit_url' => route('panel.my-init-codes.edit', $code['id']),
            'toggle_url' => route('panel.my-init-codes.toggle', $code['id']),
            'destroy_url' => route('panel.my-init-codes.destroy', $code['id']),
        ];
    }
}
