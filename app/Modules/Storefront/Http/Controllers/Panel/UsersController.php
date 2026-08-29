<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Models\User;
use App\Modules\Storefront\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Panel: super-user steruje widocznością sekcji (Wspieramy/Sklep/Praca/
 * Aplikacje/Baza kandydatów) per-konto. `is_admin` NIE jest tu edytowalne —
 * nadawane wyłącznie ręcznie w bazie (bez samoobsługowej promocji).
 */
class UsersController extends Controller
{
    public function __construct()
    {
        abort_unless(Auth::user()->is_admin, 403);
    }

    public function index()
    {
        $users = User::orderBy('name')->get();

        return Inertia::render('Panel/Users/Index', [
            'sections' => collect(User::SECTIONS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'items' => $users->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'handle' => $u->handle,
                'is_admin' => $u->is_admin,
                // null = wszystko widoczne -> wszystkie klucze zaznaczone w UI.
                'enabled_sections' => $u->enabled_sections ?? array_keys(User::SECTIONS),
                'update_url' => route('panel.users.sections', $u),
            ])->values(),
        ]);
    }

    public function updateSections(Request $request, User $user)
    {
        $data = $request->validate([
            'sections' => ['nullable', 'array'],
            'sections.*' => ['string', 'in:' . implode(',', array_keys(User::SECTIONS))],
        ]);

        $selected = $data['sections'] ?? [];
        // Zaznaczone wszystkie sekcje == NULL (jawnie "bez ograniczeń"), żeby
        // nie odróżniać w bazie "wszystko włączone" od "właśnie wszystko zaznaczono".
        $allSelected = count($selected) === count(User::SECTIONS);

        $user->update(['enabled_sections' => $allSelected ? null : array_values($selected)]);

        return back()->with('success', 'Widoczność sekcji zapisana dla ' . $user->name . '.');
    }
}
