<?php

namespace App\Modules\Storefront\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Produkt sklepu donacyjnego (NFC) — np. „Serduszko", „Brelok", „Kubek".
 * Kwota płacona przez użytkownika jest dowolna, ale nie niższa niż `min_amount`.
 */
class ShopItem extends Model
{
    protected $fillable = [
        'slug', 'name', 'image', 'min_amount', 'is_default', 'tag_uid', 'active', 'sort',
    ];

    protected $casts = [
        'min_amount' => 'integer',
        'is_default' => 'boolean',
        'active'     => 'boolean',
        'sort'       => 'integer',
    ];

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
