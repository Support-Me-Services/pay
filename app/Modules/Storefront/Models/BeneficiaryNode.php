<?php

namespace App\Modules\Storefront\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Węzeł podstrony „Wspieramy" — nagłówek + grafika (lewo/prawo) + tekst (HTML).
 * Należy do właściciela (`user_id`) — sekcja per‑konto, jak `ShopItem`.
 */
class BeneficiaryNode extends Model
{
    protected $fillable = ['user_id', 'heading', 'image', 'image_side', 'image_scale', 'image_x', 'image_y', 'text_align', 'body_html', 'position', 'active'];

    protected $casts = [
        'user_id' => 'integer',
        'active' => 'boolean',
        'position' => 'integer',
        'image_scale' => 'integer',
        'image_x' => 'integer',
        'image_y' => 'integer',
    ];

    protected $attributes = [
        'image_side' => 'left',
        'text_align' => 'left',
        'image_scale' => 100,
        'image_x' => 0,
        'image_y' => 0,
        'position' => 0,
        'active' => true,
    ];

    /** Właściciel węzła (konto). */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Węzły danego właściciela. */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    /** Czy grafika jest po prawej stronie. */
    public function imageRight(): bool
    {
        return $this->image_side === 'right';
    }
}
