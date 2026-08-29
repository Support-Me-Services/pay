<?php

namespace App\Modules\Storefront\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Zgłoszenie rekrutacyjne. Należy do organizacji (`organization_id`) —
 * ustawiony bezpośrednio (nie tylko przez `job_position_id`), bo aplikacja
 * spontaniczna (bez oferty) też musi mieć właściciela.
 */
class JobApplication extends Model
{
    public $timestamps = false;

    /** Statusy rekrutacyjne: klucz => etykieta PL. */
    public const STATUSES = [
        'pending'  => 'Do sprawdzenia',
        'accepted' => 'Zaakceptowany',
        'rejected' => 'Odrzucony',
    ];

    /** Okres ważności zgody na przyszłe rekrutacje (w miesiącach). */
    public const FUTURE_CONSENT_MONTHS = 24;

    protected $fillable = [
        'organization_id', 'job_position_id', 'name', 'email', 'phone', 'message',
        'cv_path', 'cv_original_name', 'is_read', 'status', 'created_at',
        'future_recruitment_consent', 'future_recruitment_consent_at',
    ];

    protected $casts = [
        'organization_id' => 'integer',
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'future_recruitment_consent' => 'boolean',
        'future_recruitment_consent_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    /** Etykieta statusu po polsku. */
    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? 'Do sprawdzenia';
    }

    /** Kolory plakietki statusu [tło, tekst] — bez zależności od theme.css. */
    public function statusColors(): array
    {
        return match ($this->status) {
            'accepted' => ['#def0e4', '#1f7a4d'],
            'rejected' => ['#f7dede', '#b23b3b'],
            default    => ['#fdf3d7', '#9a7011'], // pending
        };
    }

    /**
     * Oferta, na którą wpłynęła aplikacja (null = aplikacja spontaniczna).
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class, 'job_position_id');
    }

    /** Organizacja, do której należy zgłoszenie. */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** Zgłoszenia danej organizacji. */
    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    /** Data wygaśnięcia zgody na przyszłe rekrutacje (null = brak zgody). */
    public function futureConsentExpiresAt(): ?Carbon
    {
        if (! $this->future_recruitment_consent || ! $this->future_recruitment_consent_at) {
            return null;
        }

        return $this->future_recruitment_consent_at->copy()->addMonths(self::FUTURE_CONSENT_MONTHS);
    }

    /** Czy zgoda na przyszłe rekrutacje jest nadal aktywna (w okresie 24 mies.). */
    public function futureConsentActive(): bool
    {
        $expiry = $this->futureConsentExpiresAt();

        return $expiry !== null && $expiry->isFuture();
    }

    /**
     * Scope: aplikacje z AKTYWNĄ zgodą na przyszłe rekrutacje.
     * Zgoda udzielona i wciąż w okresie ważności (24 miesiące).
     */
    public function scopeActiveFutureConsent(Builder $query): Builder
    {
        return $query
            ->where('future_recruitment_consent', true)
            ->whereNotNull('future_recruitment_consent_at')
            ->where('future_recruitment_consent_at', '>', now()->subMonths(self::FUTURE_CONSENT_MONTHS));
    }
}
