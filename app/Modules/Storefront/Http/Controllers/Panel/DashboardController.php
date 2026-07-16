<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\ContactMessage;
use App\Modules\Storefront\Services\ShopStatsService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(ShopStatsService $stats)
    {
        return Inertia::render('Panel/Dashboard', [
            'paymentMode' => config('shop.payment_mode'),
            'total' => $this->presentSummary($stats->summary()),
            'last30' => $this->presentSummary($stats->summary(days: 30)),
            'series' => $stats->dailyPurchases(days: 30),
            'unread' => ContactMessage::where('is_read', false)->count(),
            'messagesUrl' => route('panel.messages.index'),
        ]);
    }

    /** Statystyki dla React — przychód sformatowany po stronie serwera. */
    private function presentSummary(array $s): array
    {
        return [
            'revenue' => ShopStatsService::formatPln($s['revenue']),
            'purchases' => $s['purchases'],
            'opens' => $s['opens'],
            'conversion' => $s['conversion'],
        ];
    }
}
