<?php

namespace App\Modules\Gateway\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'email', 'phone', 'company', 'message', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];
}
