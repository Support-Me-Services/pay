<?php

namespace App\Models;

use App\Modules\Storefront\Models\Organization;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'handle',
        'is_admin',
        'keycloak_sub',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * E-mail zawsze zapisywany małymi literami — logowanie ma być
     * niewrażliwe na wielkość liter, niezależnie skąd przychodzi zapis
     * (rejestracja, panel, tinker).
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtolower(trim($value)),
        );
    }

    /**
     * Domyślny właściciel danych na globalnych, wspólnych stronach publicznych
     * (/, /beneficiaries, /praca) — sprzed wprowadzenia samoobsługowej
     * rejestracji istniało tylko jedno konto, więc te strony pozostają
     * przypięte do niego zamiast pokazywać zbiorczo dane wszystkich kont.
     */
    public static function rootOwner(): ?self
    {
        return static::where('email', 'marcin.lula@please-support-me.com')->first()
            ?? static::where('handle', 'lula-marcin')->first()
            ?? static::orderBy('id')->first();
    }

    protected static function booted(): void
    {
        // Handle (slug sklepu) generowany automatycznie z nazwy, jeśli nie podano.
        static::creating(function (User $user) {
            if (empty($user->handle)) {
                $user->handle = static::uniqueHandle($user->name);
            }
        });

        // Organizacja musi zawsze mieć jakiegoś użytkownika — usuwane konto
        // przepina swoje organizacje na rootOwnera zamiast dopuścić, by
        // zniknęły osierocone (FK na organizations.user_id nie ma już
        // cascadeOnDelete — patrz migracja drop_cascade_from_organizations_user_id).
        static::deleting(function (User $user) {
            $root = static::rootOwner();
            if ($root && $root->isNot($user)) {
                $user->organizations()->update(['user_id' => $root->id]);
            }
        });
    }

    /** Unikalny handle konta (slug sklepu) z nazwy. */
    public static function uniqueHandle(?string $name): string
    {
        $base = Str::slug((string) $name) ?: 'sklep';
        $handle = $base;
        $i = 2;
        while (static::where('handle', $handle)->exists()) {
            $handle = $base.'-'.$i++;
        }

        return $handle;
    }

    /** Organizacje zarządzane przez to konto (jedno konto = wiele organizacji). */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    /**
     * Aktywna organizacja w bieżącej sesji panelu — z niej czytają/piszą
     * wszystkie kontrolery sekcji (O nas/Zbiórki/Praca/Aplikacje). Weryfikuje,
     * że wskazana w sesji organizacja faktycznie należy do TEGO konta (nie da
     * się podmienić session() na cudzą); fallback: pierwsza organizacja konta.
     */
    public function activeOrganization(\Illuminate\Http\Request $request): ?Organization
    {
        $id = $request->session()->get('active_organization_id');
        $org = $id ? $this->organizations()->find($id) : null;

        return $org ?? $this->organizations()->orderBy('id')->first();
    }
}
