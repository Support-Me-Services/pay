<?php

namespace App\Modules\Storefront\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Organizacja — byt nad kontem (User). Jedno konto może zarządzać wieloma
 * organizacjami; każda ma własne „O nas"/Zbiórki/Praca/Aplikacje/Baza
 * kandydatów oraz samoobsługową widoczność tych 5 sekcji (enabled_sections).
 */
class Organization extends Model
{
    protected $fillable = ['user_id', 'name', 'handle', 'logo', 'enabled_sections'];

    protected $casts = [
        'enabled_sections' => 'array',
    ];

    /** Klucze sekcji sterowanych widocznością — mirror User::SECTIONS. */
    public const SECTIONS = [
        'beneficiaries' => 'O nas',
        'shop-items' => 'Zbiórki',
        'positions' => 'Praca',
        'applications' => 'Aplikacje / Baza kandydatów',
        'init-codes' => 'Tagi NFC / Kody QR',
    ];

    /** Konto zarządzające tą organizacją. */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function beneficiaryNodes(): HasMany
    {
        return $this->hasMany(BeneficiaryNode::class);
    }

    public function shopItems(): HasMany
    {
        return $this->hasMany(ShopItem::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(JobPosition::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /**
     * Czy TA organizacja ma widoczną daną sekcję. Self-service — bez
     * nadpisania is_admin (to platformowa flaga na User, nie na Organization).
     * `enabled_sections === null` oznacza „wszystko widoczne" (domyślne).
     */
    public function canSee(string $section): bool
    {
        return $this->enabled_sections === null || in_array($section, $this->enabled_sections, true);
    }

    /** Unikalny handle (slug publicznego URL) z nazwy. */
    public static function uniqueHandle(?string $name): string
    {
        $base = Str::slug((string) $name) ?: 'organizacja';
        $handle = $base;
        $i = 2;
        while (static::where('handle', $handle)->exists()) {
            $handle = $base.'-'.$i++;
        }

        return $handle;
    }

    /**
     * Domyślna organizacja na globalnych, wspólnych stronach publicznych
     * (/, /beneficiaries, /praca) — sprzed rejestracji istniało tylko jedno
     * konto/jedna organizacja, więc te strony zostają przypięte do niej.
     */
    public static function rootOrganization(): ?self
    {
        $rootOwner = User::rootOwner();

        return $rootOwner
            ? (static::where('user_id', $rootOwner->id)->orderBy('id')->first() ?? static::orderBy('id')->first())
            : static::orderBy('id')->first();
    }
}
