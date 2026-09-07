<?php

namespace App\Modules\Storefront\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * shop_item_id nie jest już kluczem obcym (Faza 3 migracji — ShopItem żyje
 * w org-svc, bez lokalnej tabeli) — to zwykła referencja do ID tam. Pobierz
 * przez app(OrgSvcClient::class)->getShopItem($order->shop_item_id) tam,
 * gdzie potrzebna nazwa/slug/organizacja produktu (patrz OrderReturnController).
 */
class Order extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['shop_item_id', 'transaction_id', 'amount', 'status', 'paid_at', 'created_at'];

    protected $casts = ['amount' => 'integer', 'paid_at' => 'datetime', 'created_at' => 'datetime'];

    public function amountPln(): string
    {
        return number_format($this->amount / 100, 2, ',', ' ');
    }
}
