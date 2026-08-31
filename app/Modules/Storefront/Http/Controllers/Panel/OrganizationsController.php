<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Models\User;
use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\Organization;
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
 */
class OrganizationsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $active = $user->activeOrganization($request);

        $data = [
            'organizations' => $user->organizations()->orderBy('name')->get()
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
            $allOrgs = Organization::with('owner')->orderBy('name')->get();

            $data['allOrganizations'] = [
                'sections' => collect(Organization::SECTIONS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
                'users' => User::orderBy('name')->get(['id', 'name', 'email'])
                    ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])
                    ->values(),
                'items' => $allOrgs->map(fn (Organization $o) => [
                    'id' => $o->id,
                    'name' => $o->name,
                    'ownerId' => $o->user_id,
                    'ownerEmail' => $o->owner->email,
                    'handle' => $o->handle,
                    'enabled_sections' => $o->enabled_sections ?? array_keys(Organization::SECTIONS),
                    'update_url' => route('panel.users.sections', $o),
                    'owner_url' => route('panel.users.owner', $o),
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

        $org = Organization::create([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'handle' => Organization::uniqueHandle($data['name']),
        ]);

        $request->session()->put('active_organization_id', $org->id);

        return redirect()->route('panel.organizations.index')
            ->with('success', 'Organizacja „' . $org->name . '" założona i ustawiona jako aktywna.');
    }

    /** Przełącza aktywną organizację (musi należeć do zalogowanego konta). */
    public function switchTo(Request $request)
    {
        $data = $request->validate(['organization_id' => ['required', 'integer']]);

        $org = $request->user()->organizations()->find($data['organization_id']);
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

        $org->update(['name' => $data['name']]);

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

        $selected = $data['sections'] ?? [];
        // Zaznaczone wszystkie sekcje == NULL (jawnie "bez ograniczeń").
        $allSelected = count($selected) === count(Organization::SECTIONS);

        $org->update(['enabled_sections' => $allSelected ? null : array_values($selected)]);

        return back()->with('success', 'Widoczność sekcji zapisana.');
    }
}
