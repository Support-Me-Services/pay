<?php

namespace App\Http\Controllers\Auth;

use App\Auth\KeycloakIdentity;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

/**
 * Logowanie panelu (Gateway I Storefront) idzie WYŁĄCZNIE przez Keycloak.
 * Który klient Keycloaka jest użyty ustala `ResolveTenant::applyKeycloakClient()`
 * per bieżący host (patrz `config/tenants.php`), ten kontroler nie wie nic
 * o tenantach.
 *
 * Tożsamość z Keycloaka: zero lokalnej tabeli `users` — patrz plan migracji
 * "Tożsamość z Keycloaka zamiast lokalnej tabeli users". Nic tu nie jest
 * "tworzone" ani "dopasowywane" — każde logowanie buduje `KeycloakIdentity`
 * na świeżo z tokenu, bo to jedyne źródło prawdy (org-svc/core-svc i tak
 * referencują `sub` Keycloaka wprost, nie żaden lokalny numeryczny ID).
 *
 * Świadomie uproszczone (poza zakresem tej fazy): dawna reguła "Gateway
 * wymaga wcześniej założonego lokalnego konta" (brak samoobsługowej
 * rejestracji do bramki) zniknęła razem z tabelą `users` — każde konto z
 * realmu Keycloaka ma dziś dostęp do obu paneli. Gateway to i tak PoC płatności
 * poza zakresem tej migracji; właściwe różnicowanie dostępu (np. osobna rola
 * Keycloaka per panel) to osobna, świadomie odłożona decyzja.
 */
class KeycloakController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('keycloak')->redirect();
    }

    public function callback(Request $request)
    {
        $keycloakUser = Socialite::driver('keycloak')->user();
        $identity = KeycloakIdentity::fromClaims($keycloakUser->getRaw());

        $request->session()->put('keycloak_identity', $identity->toSessionArray());
        // id_token trzymany w sesji do prawdziwego single-logout — end-session
        // Keycloaka przyjmuje id_token_hint; bez niego SSO-sesja w Keycloaku
        // zostaje żywa mimo wylogowania z Laravela (drugie logowanie ominęłoby
        // ekran logowania Keycloaka).
        $request->session()->put('keycloak_id_token', $keycloakUser->accessTokenResponseBody['id_token'] ?? null);

        Auth::login($identity, true);
        $request->session()->regenerate();

        return redirect()->intended(route('panel.dashboard'));
    }

    public function logout(Request $request)
    {
        $idToken = $request->session()->pull('keycloak_id_token');
        $postLogoutUrl = route('panel.login');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Prawdziwe single-logout: koniec sesji SSO w Keycloaku, nie tylko
        // lokalnej sesji Laravela — inaczej drugie logowanie ominęłoby ekran
        // Keycloaka (SSO-sesja zostałaby żywa). getLogoutUrl() to gotowa
        // metoda pakietu socialiteproviders/keycloak.
        $logoutUrl = Socialite::driver('keycloak')->getLogoutUrl($postLogoutUrl, null, $idToken);

        return redirect()->away($logoutUrl);
    }
}
