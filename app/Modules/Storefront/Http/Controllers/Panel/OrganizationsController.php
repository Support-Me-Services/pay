<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Panel: organizacje zalogowanego konta — lista/przełącznik aktywnej,
 * tworzenie nowej, oraz self-service włącz/wyłącz 5 sekcji AKTYWNEJ
 * organizacji (bez udziału super-usera — patrz też Panel\UsersController,
 * globalny podgląd nad wszystkimi organizacjami).
 */
class OrganizationsController extends Controller
{
    public function index(Request $request)
    {
        $active = $request->user()->activeOrganization($request);

        return Inertia::render('Panel/Organizations/Index', [
            'organizations' => $request->user()->organizations()->orderBy('name')->get()
                ->map(fn (Organization $o) => ['id' => $o->id, 'name' => $o->name, 'handle' => $o->handle])
                ->values(),
            'activeId' => $active?->id,
            'switchUrl' => route('panel.organizations.switch'),
            'storeUrl' => route('panel.organizations.store'),
        ]);
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

    /** Self-service: widoczność 5 sekcji AKTYWNEJ organizacji. */
    public function settings(Request $request)
    {
        $org = $request->user()->activeOrganization($request);
        abort_unless($org, 404);

        return Inertia::render('Panel/Organizations/Settings', [
            'organizationName' => $org->name,
            'sections' => collect(Organization::SECTIONS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'enabledSections' => $org->enabled_sections ?? array_keys(Organization::SECTIONS),
            'updateUrl' => route('panel.organizations.settings.update'),
        ]);
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
