<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Services\ShopStatsService;

class DashboardController extends Controller
{
    public function index(ShopStatsService $stats)
    {
        $total = $stats->summary();
        $last30 = $stats->summary(days: 30);
        $series = $stats->dailyPurchases(days: 30);

        return view('panel.dashboard', compact('total', 'last30', 'series'));
    }
}
