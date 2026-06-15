<?php

namespace App\Modules\Gateway\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    // Dane bramki zawsze z bazy nfc_pay, niezależnie od domyślnej bazy hosta.
    protected $connection = 'gateway';

    public $timestamps = false;

    protected $fillable = ['name', 'slug', 'base_url', 'api_key', 'payment_mode'];

    protected $hidden = ['api_key'];

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public static function generateApiKey(): string
    {
        return bin2hex(random_bytes(32)); // 64 znaki hex
    }
}
