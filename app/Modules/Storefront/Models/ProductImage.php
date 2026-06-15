<?php

namespace App\Modules\Storefront\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_id', 'path', 'sort'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
