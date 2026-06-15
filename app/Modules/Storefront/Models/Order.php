<?php

namespace App\Modules\Storefront\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['product_id', 'transaction_id', 'amount', 'status', 'paid_at', 'created_at'];

    protected $casts = ['amount' => 'integer', 'paid_at' => 'datetime', 'created_at' => 'datetime'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function amountPln(): string
    {
        return number_format($this->amount / 100, 2, ',', ' ');
    }
}
