<?php

namespace App\Modules\Init\Models;

use App\Models\User;
use App\Modules\Storefront\Models\Organization;
use App\Modules\Storefront\Models\ShopItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Kod inicjalizacji kontaktu (tag NFC / kod QR — ten sam byt, dwa równoległe
 * adresy: /init/tag/{uuid} i /init/qr/{uuid}, kanał tylko informacyjnie).
 * Cel przekierowania jest ZAWSZE dynamiczny — zwykłe, edytowalne w panelu
 * pole, odczytywane na świeżo przy każdym skanie.
 *
 * Właściciel = dokładnie jedno z dwóch:
 *  - organization: zarządzany w panelu organizacji, cel = shopItem.
 *  - ownerUser: kod osobisty konta, zarządzany w "Moje tagi", cel = targetOrganization.
 */
class InitCode extends Model
{
    protected $fillable = [
        'organization_id', 'owner_user_id', 'uuid', 'label',
        'shop_item_id', 'target_organization_id', 'active',
    ];

    protected $casts = [
        'organization_id' => 'integer',
        'owner_user_id' => 'integer',
        'shop_item_id' => 'integer',
        'target_organization_id' => 'integer',
        'active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (InitCode $code) {
            $code->uuid ??= (string) Str::uuid();
        });
    }

    /** Organizacja-właściciel (kod zarządzany w jej panelu) — jeśli to kod organizacyjny. */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** Konto-właściciel (kod osobisty) — jeśli to kod użytkownika. */
    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /** Cel dla kodu organizacyjnego — konkretny produkt. */
    public function shopItem(): BelongsTo
    {
        return $this->belongsTo(ShopItem::class);
    }

    /** Cel dla kodu osobistego — cała lista zbiórek wybranej organizacji użytkownika. */
    public function targetOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'target_organization_id');
    }

    /** Kody należące do danej organizacji (panel organizacji). */
    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    /** Kody osobiste danego konta ("Moje tagi"). */
    public function scopeForOwner(Builder $query, int $userId): Builder
    {
        return $query->where('owner_user_id', $userId);
    }
}
