<?php

namespace App\Modules\Storefront\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Produkt sklepu NFC — np. „Serduszko", „Brelok", „Kubek".
 * W trybie sklepowym (branch sklep-payu) obowiązuje STAŁA cena `price`
 * (z fallbackiem do `min_amount`); `min_amount` pozostaje dla zgodności
 * z modelem darowiznowym na pozostałych gałęziach.
 */
class ShopItem extends Model
{
    protected $fillable = [
        'slug', 'name', 'image', 'min_amount', 'price', 'description', 'is_default', 'tag_uid', 'active', 'sort',
    ];

    protected $casts = [
        'min_amount' => 'integer',
        'price'      => 'integer',
        'is_default' => 'boolean',
        'active'     => 'boolean',
        'sort'       => 'integer',
    ];

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
