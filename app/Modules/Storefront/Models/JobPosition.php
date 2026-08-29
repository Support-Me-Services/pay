<?php

namespace App\Modules\Storefront\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Oferta pracy (sekcja „Praca"). Należy do organizacji (`organization_id`) —
 * sekcja per‑organizacja, jak `ShopItem`.
 */
class JobPosition extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id', 'title', 'location', 'employment_type', 'description_html', 'short_description', 'active', 'sort',
    ];

    protected $casts = [
        'organization_id' => 'integer',
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

    /** Organizacja, do której należy oferta. */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** Oferty danej organizacji. */
    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }
}
