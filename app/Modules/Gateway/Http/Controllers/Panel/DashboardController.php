<?php

namespace App\Modules\Gateway\Http\Controllers\Panel;

use App\Modules\Gateway\Http\Controllers\Controller;
use App\Modules\Gateway\Models\Shop;
use App\Modules\Gateway\Services\StatsService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(StatsService $stats)
    {
        $shops = Shop::withCount('tags')->get();

        // Przychód formatujemy tu (grosze → PLN); reszta metryk to liczby.
        $fmt = fn (array $s) => [...$s, 'revenue' => StatsService::formatPln($s['revenue'])];

        return Inertia::render('Gateway/Dashboard', [
            'shopsCount' => $shops->count(),
            'global' => $fmt($stats->summary()),
            'global30' => $fmt($stats->summary(days: 30)),
            'series' => $stats->dailyPaidSeries(days: 30),
            'perShop' => $shops->map(fn (Shop $shop) => [
                'id' => $shop->id,
                'name' => $shop->name,
                'payment_mode' => $shop->payment_mode,
                'tags_count' => $shop->tags_count,
                'tags_url' => route('panel.tags.index', $shop),
                'stats' => $fmt($stats->summary(shopId: $shop->id)),
            ])->values(),
        ]);
    }
}
