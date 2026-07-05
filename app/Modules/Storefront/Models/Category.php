<?php

namespace App\Modules\Storefront\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** Źródła pozycji listowanych na stronie kategorii: klucz => etykieta PL. */
    public const SOURCES = [
        'none'          => 'Brak (pusty stan)',
        'parishes'      => 'Parafie',
        'beneficiaries' => 'Strona „Wspieramy"',
    ];

    protected $fillable = [
        'parent_id', 'slug', 'label', 'label_html', 'label_text',
        'intro', 'icon', 'source', 'position', 'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'position' => 'integer',
        'parent_id' => 'integer',
    ];

    protected $attributes = [
        'source' => 'none',
        'position' => 0,
        'active' => true,
    ];

    /** Kategoria-rodzic (zagnieżdżenie). */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** Podkategorie — uporządkowane po pozycji. */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->ordered();
    }

    /** Tylko aktywne kategorie. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /** Pozycje top-level (bez rodzica). */
    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /** Kolejność wg position, potem id (stabilnie). */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    /** Etykieta źródła po polsku. */
    public function sourceLabel(): string
    {
        return self::SOURCES[$this->source] ?? self::SOURCES['none'];
    }
}
