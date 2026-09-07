<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

/**
 * Provider bez żadnej bazy danych — odtwarza `KeycloakIdentity` z KLUCZA
 * SESJI ('keycloak_identity'), do którego KeycloakController::callback()
 * zapisuje pełny komplet danych przy Auth::login(). SessionGuard standardowo
 * trzyma w sesji tylko IDENTYFIKATOR (sub) i doczytuje resztę z providera —
 * my "doczytujemy" z tej samej sesji, nie z zewnętrznego magazynu, więc
 * `retrieveById()` nigdy nie odpytuje niczego poza pamięcią requestu.
 *
 * `retrieveByToken`/`retrieveByCredentials`/hasło nie są używane — logowanie
 * idzie wyłącznie przez redirect do Keycloaka (patrz KeycloakController),
 * nigdy przez formularz login/hasło Laravela.
 */
class KeycloakSessionProvider implements UserProvider
{
    public function retrieveById($identifier): ?Authenticatable
    {
        $data = session('keycloak_identity');
        if (! $data || $data['sub'] !== $identifier) {
            return null;
        }

        return KeycloakIdentity::fromSessionArray($data);
    }

    public function retrieveByToken($identifier, #[\SensitiveParameter] $token): ?Authenticatable
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, #[\SensitiveParameter] $token): void
    {
        // no-op — bez "remember me", patrz KeycloakIdentity::setRememberToken().
    }

    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?Authenticatable
    {
        return null;
    }

    public function validateCredentials(Authenticatable $user, #[\SensitiveParameter] array $credentials): bool
    {
        return false;
    }

    public function rehashPasswordIfRequired(Authenticatable $user, #[\SensitiveParameter] array $credentials, bool $force = false): void
    {
        // no-op — nie ma haseł do rehashowania.
    }
}
