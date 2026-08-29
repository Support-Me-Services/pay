<?php

namespace App\Modules\Storefront\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Węzeł podstrony „O nas" — nagłówek + grafika (lewo/prawo) + tekst (HTML).
 * Należy do organizacji (`organization_id`) — sekcja per‑organizacja, jak `ShopItem`.
 */
class BeneficiaryNode extends Model
{
    protected $fillable = ['organization_id', 'heading', 'image', 'image_side', 'image_scale', 'image_x', 'image_y', 'text_align', 'body_html', 'position', 'active'];

    protected $casts = [
        'organization_id' => 'integer',
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

    /** Organizacja, do której należy węzeł. */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** Węzły danej organizacji. */
    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
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
