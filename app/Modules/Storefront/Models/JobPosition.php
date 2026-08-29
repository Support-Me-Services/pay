<?php

namespace App\Modules\Storefront\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Oferta pracy (sekcja „Praca"). Należy do właściciela (`user_id`) —
 * sekcja per‑konto, jak `ShopItem`.
 */
class JobPosition extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'title', 'location', 'employment_type', 'description_html', 'short_description', 'active', 'sort',
    ];

    protected $casts = [
        'user_id' => 'integer',
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

    /** Właściciel oferty (konto). */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Oferty danego właściciela. */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
