<?php

namespace App\Models;

use App\Modules\Storefront\Models\ShopItem;
use Database\Factories\UserFactory;
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
        'enabled_sections',
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
            'enabled_sections' => 'array',
        ];
    }

    /**
     * Sekcje panelu sterowane widocznością przez super-usera (poza tym
     * zakresem: „Parafie” i „Zmiana hasła” — zawsze widoczne).
     */
    public const SECTIONS = [
        'beneficiaries' => 'O nas',
        'shop-items' => 'Zbiórki',
        'positions' => 'Praca',
        'applications' => 'Aplikacje / Baza kandydatów',
    ];

    /**
     * Czy to konto widzi daną sekcję panelu. `enabled_sections === null`
     * oznacza „wszystko widoczne” (domyślne, dotychczasowe zachowanie).
     */
    public function canSee(string $section): bool
    {
        return $this->is_admin
            || $this->enabled_sections === null
            || in_array($section, $this->enabled_sections, true);
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

    /** Produkty (sklep) tego konta. */
    public function shopItems(): HasMany
    {
        return $this->hasMany(ShopItem::class);
    }
}
