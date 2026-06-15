<?php

namespace App\Modules\Storefront\Services;

use App\Modules\Storefront\Models\Event;
use App\Modules\Storefront\Models\Order;
use Illuminate\Support\Carbon;

class ShopStatsService
{
    /**
     * Agregaty (opcjonalnie per produkt / za N dni): otwarcia tagów,
     * wejścia na stronę, kliki "Kup", zakupy, przychód, konwersja.
     */
    public function summary(?int $productId = null, ?int $days = null): array
    {
        $eventQuery = Event::query();
        $orderQuery = Order::where('status', 'paid');

        if ($productId !== null) {
            $eventQuery->where('product_id', $productId);
            $orderQuery->where('product_id', $productId);
        }
        if ($days !== null) {
            $since = Carbon::now()->subDays($days)->startOfDay();
            $eventQuery->where('created_at', '>=', $since);
            $orderQuery->where('created_at', '>=', $since);
        }

        $counts = $eventQuery->selectRaw('type, COUNT(*) AS c')->groupBy('type')->pluck('c', 'type');

        $opens = (int) ($counts['tag_open'] ?? 0);
        $views = (int) ($counts['page_view'] ?? 0);
        $clicks = (int) ($counts['buy_click'] ?? 0);
        $purchases = (int) $orderQuery->count();
        $revenue = (int) $orderQuery->sum('amount');

        return [
            'opens' => $opens,
            'views' => $views,
            'clicks' => $clicks,
            'purchases' => $purchases,
            'revenue' => $revenue, // grosze
            'conversion' => $opens > 0 ? round($purchases / $opens * 100, 1) : 0.0,
        ];
    }

    /**
     * Dzienna seria zakupów za $days dni — pod wykres słupkowy.
     */
    public function dailyPurchases(?int $productId = null, int $days = 30): array
    {
        $since = Carbon::now()->subDays($days - 1)->startOfDay();

        $query = Order::where('status', 'paid')->where('paid_at', '>=', $since);
        if ($productId !== null) {
            $query->where('product_id', $productId);
        }

        $daily = $query->selectRaw('DATE(paid_at) AS d, COUNT(*) AS c')
            ->groupBy('d')->pluck('c', 'd')->toArray();

        $labels = [];
        $counts = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $since->copy()->addDays($i);
            $labels[] = $day->format('d.m');
            $counts[] = (int) ($daily[$day->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'counts' => $counts];
    }

    public static function formatPln(int $grosze): string
    {
        return number_format($grosze / 100, 2, ',', ' ') . ' zł';
    }
}
