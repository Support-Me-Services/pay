<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Faza 6 — sam ekran z przyciskiem "Zaloguj przez Keycloak". Rejestracja
 * jest teraz nierozerwalna od logowania (pierwsze logowanie bez konta =
 * nowe konto) — nie ma już osobnego ekranu rejestracji, Keycloak sam
 * pokazuje link "Zarejestruj się" na swojej stronie logowania
 * (`registrationAllowed` w realm). Właściwe logowanie/wylogowanie:
 * App\Http\Controllers\Auth\KeycloakController.
 */
class LoginController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return redirect()->route('panel.dashboard');
        }

        return Inertia::render('Panel/Auth/Login', [
            'brand' => config('shop.name', 'SupportME'),
            'redirectUrl' => route('panel.auth.redirect'),
        ]);
    }
}
