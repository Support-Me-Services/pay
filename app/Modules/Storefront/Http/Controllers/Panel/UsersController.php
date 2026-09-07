<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\Organization;
use App\Services\OrgSvcClient;
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
 *
 * Faza 6 migracji: Organization to już nie model Eloquent (bez lokalnej
 * tabeli) — bez route model bindingu, {organization} to zwykłe int ID.
 */
class UsersController extends Controller
{
    public function __construct()
    {
        abort_unless(Auth::user()->is_admin, 403);
    }

    public function updateSections(Request $request, int $organization)
    {
        $org = Organization::findOrFail($organization);
        $data = $request->validate([
            'sections' => ['nullable', 'array'],
            'sections.*' => ['string', 'in:' . implode(',', array_keys(Organization::SECTIONS))],
        ]);

        // Admin zmienia sekcje CUDZEJ organizacji — org-svc weryfikuje
        // własność względem WŁAŚCICIELA organizacji (user_id), nie wołającego
        // admina, więc przekazujemy user_id organizacji, nie Auth::id().
        $selected = $data['sections'] ?? [];
        app(OrgSvcClient::class)->updateSections($org->id, $org->user_id, $selected);

        return back()->with('success', 'Widoczność sekcji zapisana dla ' . $org->name . '.');
    }

    /**
     * Przepina organizację na innego, istniejącego użytkownika (wyłącznie super-user).
     *
     * Faza "Tożsamość z Keycloaka": bez lokalnej tabeli `users` nie ma czego
     * walidować `exists:` — admin wkleja surowy `sub` Keycloaka wprost.
     * TODO: Keycloak Admin API — docelowo wyszukiwanie usera po e-mailu
     * zamiast wklejania sub-a ręcznie.
     */
    public function updateOwner(Request $request, int $organization)
    {
        $org = Organization::findOrFail($organization);
        $data = $request->validate([
            'user_id' => ['required', 'string'],
        ]);

        app(OrgSvcClient::class)->updateOwner($org->id, $data['user_id']);

        return back()->with('success', 'Administrator organizacji „' . $org->name . '" zmieniony.');
    }
}
