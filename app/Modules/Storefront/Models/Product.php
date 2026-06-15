<?php

namespace App\Modules\Storefront\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    public $timestamps = false;

    protected $fillable = [
        // 'city', 'purpose' używane w trybie church (Taca) — nieszkodliwe dla products.
        'name', 'city', 'purpose', 'slug', 'description_html', 'pickup_instruction',
        'price', 'tag_uid', 'main_image', 'active',
    ];

    protected $casts = ['active' => 'boolean', 'price' => 'integer'];

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
