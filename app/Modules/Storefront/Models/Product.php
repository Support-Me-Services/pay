<?php

namespace App\Modules\Storefront\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    public $timestamps = false;

    /** Statusy parafii (lead → wdrożenie): klucz => etykieta PL. */
    public const STATUSES = [
        'kontakt'   => 'Kontakt',
        'test'      => 'Test',
        'wdrozenie' => 'Wdrożenie',
        'aktywna'   => 'Aktywna',
    ];

    protected $fillable = [
        // 'city', 'purpose' używane w trybie church (Taca) — nieszkodliwe dla products.
        'name', 'city', 'purpose', 'slug', 'description_html', 'pickup_instruction',
        'price', 'tag_uid', 'main_image', 'active',
        // Pola CRM (parafie):
        'phone', 'website', 'voivodeship', 'status', 'salesperson_id',
    ];

    protected $casts = ['active' => 'boolean', 'price' => 'integer'];

    /** Etykieta statusu po polsku. */
    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? 'Kontakt';
    }

    /** Kolory plakietki statusu [tło, tekst] — bez zależności od theme.css. */
    public function statusColors(): array
    {
        return match ($this->status) {
            'test'      => ['#dbeafe', '#1e40af'], // niebieski
            'wdrozenie' => ['#ede9fe', '#5b21b6'], // fiolet
            'aktywna'   => ['#def0e4', '#1f7a4d'], // zielony
            default     => ['#fdf3d7', '#9a7011'], // kontakt — amber
        };
    }

    /** Handlowiec opiekujący się parafią. */
    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(Salesperson::class, 'salesperson_id');
    }

    /** Notatki CRM — najnowsze na górze. */
    public function notes(): HasMany
    {
        return $this->hasMany(ParishNote::class)->orderByDesc('id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function pricePln(): string
    {
        return number_format($this->price / 100, 2, ',', ' ');
    }
}
