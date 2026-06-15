<?php

namespace App\Modules\Gateway\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    // Dane bramki zawsze z bazy nfc_pay, niezależnie od domyślnej bazy hosta.
    protected $connection = 'gateway';

    public $timestamps = false;

    protected $fillable = ['shop_id', 'tag_id', 'transaction_id', 'type', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
