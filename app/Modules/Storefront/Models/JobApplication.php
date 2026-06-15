<?php

namespace App\Modules\Storefront\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    public $timestamps = false;

    /** Statusy rekrutacyjne: klucz => etykieta PL. */
    public const STATUSES = [
        'pending'  => 'Do sprawdzenia',
        'accepted' => 'Zaakceptowany',
        'rejected' => 'Odrzucony',
    ];

    protected $fillable = [
        'job_position_id', 'name', 'email', 'phone', 'message',
        'cv_path', 'cv_original_name', 'is_read', 'status', 'created_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
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
}
