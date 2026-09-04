<?php

namespace App\Modules\Gateway\Http\Controllers\Panel;

use App\Modules\Gateway\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Faza 6 — sam ekran z przyciskiem "Zaloguj przez Keycloak". Właściwe
 * logowanie/wylogowanie: App\Http\Controllers\Auth\KeycloakController.
 */
class LoginController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return redirect()->route('panel.dashboard');
        }

        return Inertia::render('Gateway/Login', [
            'redirectUrl' => route('panel.auth.redirect'),
        ]);
    }
}
