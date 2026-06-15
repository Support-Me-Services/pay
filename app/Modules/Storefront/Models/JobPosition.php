<?php

namespace App\Modules\Storefront\Models;

use Illuminate\Database\Eloquent\Model;

class JobPosition extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'title', 'location', 'employment_type', 'description_html', 'active', 'sort',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort' => 'integer',
        'created_at' => 'datetime',
    ];
}
