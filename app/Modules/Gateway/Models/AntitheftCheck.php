<?php

namespace App\Modules\Gateway\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// FIKCYJNE — moduł demo, brak realnej detekcji
class AntitheftCheck extends Model
{
    public $timestamps = false;

    protected $fillable = ['shop_id', 'status', 'foreign_tags_found', 'checked_at'];

    protected $casts = ['checked_at' => 'datetime'];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
