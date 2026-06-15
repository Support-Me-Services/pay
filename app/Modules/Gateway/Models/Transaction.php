<?php

namespace App\Modules\Gateway\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'shop_id', 'tag_id', 'product_external_id', 'product_name', 'amount',
        'currency', 'status', 'mode', 'return_url', 'notify_url',
        'provider_order_id', 'provider_redirect_url', 'paid_at', 'created_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    public function amountPln(): string
    {
        return number_format($this->amount / 100, 2, ',', ' ');
    }

    public function isFinal(): bool
    {
        return in_array($this->status, ['paid', 'failed', 'abandoned']);
    }
}
