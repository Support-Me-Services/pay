<?php

namespace App\Modules\Gateway\Http\Controllers\Panel;

use App\Modules\Gateway\Http\Controllers\Controller;
use App\Modules\Gateway\Models\Shop;
use App\Modules\Gateway\Models\Tag;
use App\Modules\Gateway\Services\StatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StatsController extends Controller
{
    public function index(Request $request, StatsService $stats)
    {
        $shops = Shop::orderBy('name')->get(['id', 'name']);

        $shopId = $request->integer('shop_id') ?: null;
        $tagId = $request->integer('tag_id') ?: null;

        $shop = $shopId ? Shop::find($shopId) : null;
        $tag = ($tagId && $shop) ? Tag::where('shop_id', $shop->id)->find($tagId) : null;

        $fmt = fn (array $s) => [...$s, 'revenue' => StatsService::formatPln($s['revenue'])];
        $tags = $shop ? $shop->tags()->orderBy('tag_uid')->get(['id', 'tag_uid', 'label']) : collect();

        return Inertia::render('Gateway/Stats', [
            'shops' => $shops,
            'tags' => $tags->map(fn (Tag $t) => ['id' => $t->id, 'tag_uid' => $t->tag_uid, 'label' => $t->label])->values(),
            'shop' => $shop ? ['id' => $shop->id, 'name' => $shop->name] : null,
            'tag' => $tag ? ['id' => $tag->id, 'tag_uid' => $tag->tag_uid] : null,
            'total' => $fmt($stats->summary(shopId: $shop?->id, tagId: $tag?->id)),
            'last30' => $fmt($stats->summary(shopId: $shop?->id, tagId: $tag?->id, days: 30)),
            'series' => $stats->dailyPaidSeries(shopId: $shop?->id, tagId: $tag?->id, days: 30),
            'statsUrl' => route('panel.stats'),
        ]);
    }
}
