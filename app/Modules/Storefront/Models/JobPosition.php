<?php

namespace App\Modules\Storefront\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPosition extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'title', 'location', 'employment_type', 'description_html', 'short_description', 'active', 'sort',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Zgłoszenia rekrutacyjne wpływające na to stanowisko.
     */
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'job_position_id');
    }
}
