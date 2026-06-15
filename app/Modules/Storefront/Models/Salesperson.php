<?php

namespace App\Modules\Storefront\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Salesperson extends Model
{
    public $timestamps = false;

    protected $table = 'salespeople';

    /** Lista województw RP — wspólna dla parafii i handlowców. */
    public const VOIVODESHIPS = [
        'dolnośląskie', 'kujawsko-pomorskie', 'lubelskie', 'lubuskie',
        'łódzkie', 'małopolskie', 'mazowieckie', 'opolskie',
        'podkarpackie', 'podlaskie', 'pomorskie', 'śląskie',
        'świętokrzyskie', 'warmińsko-mazurskie', 'wielkopolskie', 'zachodniopomorskie',
    ];

    protected $fillable = [
        'name', 'email', 'phone', 'voivodeships', 'active', 'created_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        // Województwa obsługiwane przez handlowca przechowujemy jako JSON.
        'voivodeships' => 'array',
        'created_at' => 'datetime',
    ];

    protected $attributes = [
        'active' => true,
    ];

    /** Parafie (produkty) przypisane do tego handlowca. */
    public function parishes(): HasMany
    {
        return $this->hasMany(Product::class, 'salesperson_id');
    }
}
