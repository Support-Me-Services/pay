<?php

namespace App\Modules\Storefront\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParishNote extends Model
{
    public $timestamps = false;

    /** Typy zdarzeń CRM: klucz => etykieta PL. */
    public const TYPES = [
        'kontakt'   => 'Kontakt',
        'telefon'   => 'Telefon',
        'mail'      => 'Mail',
        'spotkanie' => 'Spotkanie',
        'inne'      => 'Inne',
    ];

    protected $fillable = [
        'product_id', 'body', 'type', 'author', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected $attributes = [
        'type' => 'kontakt',
    ];

    /** Etykieta typu po polsku. */
    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? 'Kontakt';
    }

    /** Parafia (produkt), do której należy notatka. */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
