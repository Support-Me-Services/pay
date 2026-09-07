<?php

namespace App\Auth;

use App\Modules\Storefront\Models\Organization;
use App\Services\OrgSvcClient;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

/**
 * Tożsamość zalogowanego konta — WYŁĄCZNIE z Keycloaka, bez lokalnej tabeli
 * `users`. `sub` Keycloaka (string/UUID) to jedyny identyfikator, ten sam,
 * którego org-svc/core-svc używają jako user_id/owner_user_id (patrz plan
 * migracji "Tożsamość z Keycloaka"). Zachowuje te same właściwości/metody co
 * dawny `App\Models\User`, żeby reszta kodu (dziesiątki `$request->user()->id`
 * itd.) nie musiała się zmieniać — ten sam wzorzec co `Organization` (Faza 6).
 */
class KeycloakIdentity implements Authenticatable
{
    public function __construct(
        public readonly string $sub,
        public readonly string $email,
        public readonly string $name,
        public readonly bool $isAdmin,
    ) {
    }

    public static function fromClaims(array $claims): self
    {
        $roles = $claims['realm_access']['roles'] ?? [];

        return new self(
            sub: $claims['sub'],
            email: $claims['email'] ?? '',
            name: $claims['name'] ?? $claims['preferred_username'] ?? $claims['email'] ?? $claims['sub'],
            isAdmin: in_array('admin', $roles, true),
        );
    }

    /** Do zapisania w sesji (patrz KeycloakSessionProvider) — te same dane, nie trzeba nic dociągać ponownie. */
    public function toSessionArray(): array
    {
        return ['sub' => $this->sub, 'email' => $this->email, 'name' => $this->name, 'isAdmin' => $this->isAdmin];
    }

    public static function fromSessionArray(array $data): self
    {
        return new self($data['sub'], $data['email'], $data['name'], $data['isAdmin']);
    }

    // --- Aliasy zgodne z dawnym Eloquent User (id, is_admin) ---

    public function __get(string $key): mixed
    {
        return match ($key) {
            'id' => $this->sub,
            'is_admin' => $this->isAdmin,
            default => null,
        };
    }

    /**
     * Organizacje zarządzane przez to konto — mirror dawnego User::organizations().
     *
     * @return Organization[]
     */
    public function organizations(): array
    {
        return Organization::byOwnerOrderedByName($this->sub);
    }

    /** Mirror dawnego User::activeOrganization() — logika bez zmian, patrz Faza 6. */
    public function activeOrganization(Request $request): ?Organization
    {
        $mine = $this->organizations();
        if (! $mine) {
            return null;
        }

        $id = $request->session()->get('active_organization_id');
        $active = $id ? current(array_filter($mine, fn (Organization $o) => $o->id === (int) $id)) : null;

        if ($active) {
            return $active;
        }

        usort($mine, fn (Organization $a, Organization $b) => $a->id <=> $b->id);

        return $mine[0];
    }

    public function getAuthIdentifierName(): string
    {
        return 'sub';
    }

    public function getAuthIdentifier(): string
    {
        return $this->sub;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value)
    {
        // Brak "remember me" — sesja Keycloaka/Laravela wystarcza, patrz KeycloakController.
    }

    public function getRememberTokenName(): string
    {
        return '';
    }
}
