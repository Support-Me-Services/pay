<?php

namespace App\Modules\Gateway\Http\Controllers\Panel;

use App\Modules\Gateway\Http\Controllers\Controller;
use App\Modules\Gateway\Models\Shop;
use App\Modules\Gateway\Models\Tag;
use App\Modules\Gateway\Services\StatsService;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function index(Request $request, StatsService $stats)
    {
        $shops = Shop::orderBy('name')->get();

        $shopId = $request->integer('shop_id') ?: null;
        $tagId = $request->integer('tag_id') ?: null;

        $shop = $shopId ? Shop::find($shopId) : null;
        $tag = ($tagId && $shop) ? Tag::where('shop_id', $shop->id)->find($tagId) : null;

        $total = $stats->summary(shopId: $shop?->id, tagId: $tag?->id);
        $last30 = $stats->summary(shopId: $shop?->id, tagId: $tag?->id, days: 30);
        $series = $stats->dailyPaidSeries(shopId: $shop?->id, tagId: $tag?->id, days: 30);

        $tags = $shop ? $shop->tags()->orderBy('tag_uid')->get() : collect();

        return view('panel.stats', compact('shops', 'shop', 'tag', 'tags', 'total', 'last30', 'series'));
    }
}
