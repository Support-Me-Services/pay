<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\Organization;
use App\Services\OrgSvcClient;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Panel: jeden ekran „Organizacja" — łączy trzy dawniej osobne strony:
 * lista/przełącznik organizacji konta + tworzenie nowej, self-service
 * ustawienia AKTYWNEJ organizacji (nazwa + widoczność 5 sekcji), oraz —
 * wyłącznie dla super-usera — globalny podgląd/nadzór nad WSZYSTKIMI
 * organizacjami z możliwością przepięcia administratora (patrz
 * Panel\UsersController::updateSections/updateOwner, akcje wywoływane
 * z tego samego widoku).
 *
 * Faza 6 migracji: org-svc jest jedynym źródłem prawdy — Organization
 * (patrz app/Modules/Storefront/Models/Organization.php) to już nie model
 * Eloquent, bez lokalnej tabeli.
 */
class OrganizationsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $active = $user->activeOrganization($request);

        $data = [
            'organizations' => collect(Organization::byOwnerOrderedByName($user->id))
                ->map(fn (Organization $o) => ['id' => $o->id, 'name' => $o->name, 'handle' => $o->handle])
                ->values(),
            'activeId' => $active?->id,
            'switchUrl' => route('panel.organizations.switch'),
            'storeUrl' => route('panel.organizations.store'),
            'activeOrg' => null,
            'allOrganizations' => null,
        ];

        if ($active) {
            $data['activeOrg'] = [
                'name' => $active->name,
                'nameUpdateUrl' => route('panel.organizations.name'),
                'sections' => collect(Organization::SECTIONS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
                'enabledSections' => $active->enabled_sections ?? array_keys(Organization::SECTIONS),
                'sectionsUpdateUrl' => route('panel.organizations.settings.update'),
            ];
        }

        if ($user->is_admin) {
            $allOrgs = Organization::allOrderedByName();

            // Faza "Tożsamość z Keycloaka": bez lokalnej tabeli `users` widok
            // pokazuje surowy `user_id` (sub Keycloaka) zamiast e-maila.
            // TODO: Keycloak Admin API — docelowo wyszukiwanie/podgląd usera
            // po e-mailu zamiast surowego sub-a.
            $data['allOrganizations'] = [
                'sections' => collect(Organization::SECTIONS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
                'items' => collect($allOrgs)->map(fn (Organization $o) => [
                    'id' => $o->id,
                    'name' => $o->name,
                    'ownerId' => $o->user_id,
                    'handle' => $o->handle,
                    'enabled_sections' => $o->enabled_sections ?? array_keys(Organization::SECTIONS),
                    'update_url' => route('panel.users.sections', $o->id),
                    'owner_url' => route('panel.users.owner', $o->id),
                ])->values(),
            ];
        }

        return Inertia::render('Panel/Organizations/Index', $data);
    }

    /** Tworzy nową organizację temu samemu kontu i ustawia ją jako aktywną. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [], ['name' => 'nazwa']);

        // Unikalny handle i start z pustą widocznością sekcji — to teraz
        // logika org-svc (OrganizationGrpcService::create), nie Laravela.
        $result = app(OrgSvcClient::class)->create($request->user()->id, $data['name']);

        $request->session()->put('active_organization_id', $result['id']);

        return redirect()->route('panel.organizations.index')
            ->with('success', 'Organizacja „' . $result['name'] . '" założona i ustawiona jako aktywna.');
    }

    /** Przełącza aktywną organizację (musi należeć do zalogowanego konta). */
    public function switchTo(Request $request)
    {
        $data = $request->validate(['organization_id' => ['required', 'integer']]);

        $org = collect(Organization::byOwnerOrderedByName($request->user()->id))
            ->firstWhere('id', $data['organization_id']);
        abort_unless($org, 403);

        $request->session()->put('active_organization_id', $org->id);

        return redirect()->route('panel.dashboard')->with('success', 'Aktywna organizacja: ' . $org->name . '.');
    }

    /** Self-service: zmiana nazwy AKTYWNEJ organizacji (handle/URL publiczny bez zmian). */
    public function updateName(Request $request)
    {
        $org = $request->user()->activeOrganization($request);
        abort_unless($org, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [], ['name' => 'nazwa']);

        app(OrgSvcClient::class)->updateName($org->id, $request->user()->id, $data['name']);

        return back()->with('success', 'Nazwa organizacji zapisana.');
    }

    public function updateSettings(Request $request)
    {
        $org = $request->user()->activeOrganization($request);
        abort_unless($org, 404);

        $data = $request->validate([
            'sections' => ['nullable', 'array'],
            'sections.*' => ['string', 'in:' . implode(',', array_keys(Organization::SECTIONS))],
        ]);

        // "Zaznaczone wszystkie sekcje" -> null (bez ograniczeń) to teraz
        // logika org-svc (OrganizationGrpcService::updateSections).
        $selected = $data['sections'] ?? [];
        app(OrgSvcClient::class)->updateSections($org->id, $request->user()->id, $selected);

        return back()->with('success', 'Widoczność sekcji zapisana.');
    }
}
