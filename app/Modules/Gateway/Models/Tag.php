<?php

namespace App\Modules\Gateway\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tag extends Model
{
    // Dane bramki zawsze z bazy nfc_pay, niezależnie od domyślnej bazy hosta.
    protected $connection = 'gateway';

    public $timestamps = false;

    protected $fillable = ['shop_id', 'tag_uid', 'target_url', 'label', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
