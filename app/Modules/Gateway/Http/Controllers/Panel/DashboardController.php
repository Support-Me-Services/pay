<?php

namespace App\Modules\Gateway\Http\Controllers\Panel;

use App\Modules\Gateway\Http\Controllers\Controller;
use App\Modules\Gateway\Models\Shop;
use App\Modules\Gateway\Services\StatsService;

class DashboardController extends Controller
{
    public function index(StatsService $stats)
    {
        $shops = Shop::withCount('tags')->get();

        $global = $stats->summary();
        $global30 = $stats->summary(days: 30);
        $series = $stats->dailyPaidSeries(days: 30);

        $perShop = $shops->map(fn (Shop $shop) => [
            'shop' => $shop,
            'stats' => $stats->summary(shopId: $shop->id),
        ]);

        return view('panel.dashboard', compact('shops', 'global', 'global30', 'series', 'perShop'));
    }
}
