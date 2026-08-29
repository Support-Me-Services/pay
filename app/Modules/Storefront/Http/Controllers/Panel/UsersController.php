<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Models\User;
use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Panel: globalny podgląd super-usera nad WSZYSTKIMI organizacjami (nie
 * kontami) — bezpiecznik/nadzór, NIE główny mechanizm (ten jest self-service,
 * patrz Panel\OrganizationsController::settings — każda organizacja steruje
 * sobą sama). `is_admin` NIE jest tu edytowalne — nadawane wyłącznie ręcznie
 * w bazie (bez samoobsługowej promocji). Tu też jedyne miejsce, gdzie można
 * przepiąć administrującego organizacją użytkownika (patrz updateOwner) —
 * zwykły user nie może sam oddać swojej organizacji komuś innemu.
 */
class UsersController extends Controller
{
    public function __construct()
    {
        abort_unless(Auth::user()->is_admin, 403);
    }

    public function index()
    {
        $organizations = Organization::with('owner')->orderBy('name')->get();

        return Inertia::render('Panel/Users/Index', [
            'sections' => collect(Organization::SECTIONS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'users' => User::orderBy('name')->get(['id', 'name', 'email'])
                ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])
                ->values(),
            'items' => $organizations->map(fn (Organization $o) => [
                'id' => $o->id,
                'name' => $o->name,
                'ownerId' => $o->user_id,
                'ownerEmail' => $o->owner->email,
                'handle' => $o->handle,
                // null = wszystko widoczne -> wszystkie klucze zaznaczone w UI.
                'enabled_sections' => $o->enabled_sections ?? array_keys(Organization::SECTIONS),
                'update_url' => route('panel.users.sections', $o),
                'owner_url' => route('panel.users.owner', $o),
            ])->values(),
        ]);
    }

    public function updateSections(Request $request, Organization $organization)
    {
        $data = $request->validate([
            'sections' => ['nullable', 'array'],
            'sections.*' => ['string', 'in:' . implode(',', array_keys(Organization::SECTIONS))],
        ]);

        $selected = $data['sections'] ?? [];
        $allSelected = count($selected) === count(Organization::SECTIONS);

        $organization->update(['enabled_sections' => $allSelected ? null : array_values($selected)]);

        return back()->with('success', 'Widoczność sekcji zapisana dla ' . $organization->name . '.');
    }

    /** Przepina organizację na innego, istniejącego użytkownika (wyłącznie super-user). */
    public function updateOwner(Request $request, Organization $organization)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $organization->update(['user_id' => $data['user_id']]);

        return back()->with('success', 'Administrator organizacji „' . $organization->name . '" zmieniony.');
    }
}
