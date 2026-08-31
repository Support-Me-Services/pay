<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Panel: akcje super-usera nad WSZYSTKIMI organizacjami (nie kontami) —
 * bezpiecznik/nadzór, NIE główny mechanizm (ten jest self-service, patrz
 * Panel\OrganizationsController — każda organizacja steruje sobą sama).
 * Dane do widoku (jeden ekran „Organizacja", sekcja tylko dla admina) są
 * renderowane przez OrganizationsController::index(); ten kontroler
 * zostaje wyłącznie jako cel akcji POST. `is_admin` NIE jest tu edytowalne
 * — nadawane wyłącznie ręcznie w bazie (bez samoobsługowej promocji).
 * Tu też jedyne miejsce, gdzie można przepiąć administrującego organizacją
 * użytkownika (patrz updateOwner) — zwykły user nie może sam oddać swojej
 * organizacji komuś innemu.
 */
class UsersController extends Controller
{
    public function __construct()
    {
        abort_unless(Auth::user()->is_admin, 403);
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
