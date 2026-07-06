<?php

namespace App\Modules\Storefront\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Produkt sklepu NFC — np. „Serduszko", „Brelok", „Kubek".
 * Należy do właściciela (`user_id`) — sklepy per‑konto (/user/{handle}).
 * W trybie sklepowym obowiązuje STAŁA cena `price` (z fallbackiem do
 * `min_amount`); `min_amount` używany w prezentacji darowiznowej na `/`.
 */
class ShopItem extends Model
{
    protected $fillable = [
        'user_id', 'slug', 'name', 'image', 'min_amount', 'price', 'description', 'is_default', 'tag_uid', 'active', 'sort',
    ];

    protected $casts = [
        'user_id'    => 'integer',
        'min_amount' => 'integer',
        'price'      => 'integer',
        'is_default' => 'boolean',
        'active'     => 'boolean',
        'sort'       => 'integer',
    ];

    /** Właściciel produktu (konto). */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Produkty danego właściciela. */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
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
