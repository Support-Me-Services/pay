<?php

namespace App\Modules\Storefront\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Produkt sklepu NFC — np. „Serduszko", „Brelok", „Kubek".
 * Należy do organizacji (`organization_id`) — sklepy per‑organizacja (/people/{handle}).
 * W trybie sklepowym obowiązuje STAŁA cena `price` (z fallbackiem do
 * `min_amount`); `min_amount` używany w prezentacji darowiznowej na `/`.
 */
class ShopItem extends Model
{
    protected $fillable = [
        'organization_id', 'slug', 'name', 'image', 'min_amount', 'price', 'description', 'is_default', 'active', 'sort',
        'thank_you_heading', 'thank_you_body', 'thank_you_image', 'mecenas_organization_id',
    ];

    protected $casts = [
        'organization_id' => 'integer',
        'min_amount' => 'integer',
        'price'      => 'integer',
        'is_default' => 'boolean',
        'active'     => 'boolean',
        'sort'       => 'integer',
    ];

    /** Organizacja, do której należy produkt. */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** Organizacja-mecenas wybrana dla strony podziękowania (opcjonalna). */
    public function mecenasOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'mecenas_organization_id');
    }

    /** Produkty danej organizacji. */
    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    /** Kolejność: sort, potem id. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort')->orderBy('id');
    }

    /** Cena w groszach (stała); fallback do minimalnej kwoty, jeśli nie ustawiono. */
    public function priceGrosze(): int
    {
        return (int) ($this->price ?? $this->min_amount);
    }

    /** Cena w złotych jako liczba (np. 39). */
    public function pricePln(): int
    {
        return (int) round($this->priceGrosze() / 100);
    }

    /** Minimalna kwota w złotych jako liczba (np. 1, 10). */
    public function minAmountPln(): int
    {
        return (int) round($this->min_amount / 100);
    }

    /** Czy grafika to SVG (serduszko renderowane inline/inną klasą). */
    public function isSvg(): bool
    {
        return (bool) $this->image && str_ends_with(strtolower($this->image), '.svg');
    }
}
