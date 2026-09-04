<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

/**
 * Faza 6 — logowanie panelu (Gateway I Storefront) idzie WYŁĄCZNIE przez
 * Keycloak, Laravel nie sprawdza już żadnego hasła samodzielnie. Wspólny
 * kontroler dla obu modułów (wcześniej dwie niezależnie skopiowane
 * implementacje `Auth::attempt()`) — który klient Keycloaka jest użyty
 * ustala `ResolveTenant::applyKeycloakClient()` per bieżący host (patrz
 * `config/tenants.php`), ten kontroler nie wie nic o tenantach.
 *
 * Dopasowanie konta WYŁĄCZNIE po `keycloak_sub`, NIGDY po e-mailu — realm
 * ma `verifyEmail: true`, ale to higiena, nie jedyna linia obrony:
 * auto-logowanie w istniejące konto po samym dopasowaniu e-maila byłoby
 * furtką na przejęcie konta (ktoś rejestruje w Keycloaku e-mail należący
 * do kogoś innego). Brak dopasowania = zawsze NOWE konto (Storefront) albo
 * odmowa (Gateway, bez samoobsługowej rejestracji, jak dziś).
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

        $user = User::where('keycloak_sub', $keycloakUser->getId())->first();

        if (! $user) {
            if (config('platform.role') === 'gateway') {
                abort(403, 'To konto nie ma dostępu do panelu bramki. Poproś administratora o dodanie dostępu.');
            }

            // `email` w tabeli users jest unique — jeśli e-mail tożsamości
            // Keycloaka pokrywa się z KONTEM INNYM niż to dopasowane wyżej po
            // keycloak_sub (a więc innym/bez keycloak_sub), User::create()
            // rzuciłby nieobsłużony UniqueConstraintViolationException (500).
            // Świadomie NIE auto-linkujemy takiego konta po e-mailu (patrz
            // komentarz klasy) — więc zamiast tego czysta odmowa, ten sam
            // wzorzec co odmowa Gateway wyżej.
            if (User::where('email', $keycloakUser->getEmail())->exists()) {
                abort(409, 'Istnieje już konto z tym adresem e-mail, niepowiązane z tą tożsamością Keycloaka. Poproś administratora o pomoc.');
            }

            $user = User::create([
                'name' => $keycloakUser->getName() ?: $keycloakUser->getNickname() ?: $keycloakUser->getEmail(),
                'email' => $keycloakUser->getEmail(),
                'keycloak_sub' => $keycloakUser->getId(),
                'password' => null,
            ]);
        }

        // id_token trzymany w sesji do prawdziwego single-logout — end-session
        // Keycloaka przyjmuje id_token_hint; bez niego SSO-sesja w Keycloaku
        // zostaje żywa mimo wylogowania z Laravela (drugie logowanie ominęłoby
        // ekran logowania Keycloaka).
        $request->session()->put('keycloak_id_token', $keycloakUser->accessTokenResponseBody['id_token'] ?? null);

        Auth::login($user, true);
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
