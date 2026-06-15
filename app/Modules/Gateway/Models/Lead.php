<?php

namespace App\Modules\Gateway\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    // Dane bramki zawsze z bazy nfc_pay, niezależnie od domyślnej bazy hosta.
    protected $connection = 'gateway';

    public $timestamps = false;

    protected $fillable = ['name', 'email', 'phone', 'company', 'message', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];
}
