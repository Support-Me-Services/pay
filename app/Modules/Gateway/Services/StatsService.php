<?php

namespace App\Modules\Gateway\Services;

use App\Modules\Gateway\Models\Event;
use App\Modules\Gateway\Models\Transaction;
use Illuminate\Support\Carbon;

class StatsService
{
    /**
     * Agregaty dla sklepu (lub taga): otwarcia, rozpoczęte, opłacone,
     * nieudane, przychód, konwersja. $days = null → łącznie.
     */
    public function summary(?int $shopId = null, ?int $tagId = null, ?int $days = null): array
    {
        $eventQuery = Event::query();
        $txQuery = Transaction::query();

        if ($shopId !== null) {
            $eventQuery->where('shop_id', $shopId);
            $txQuery->where('shop_id', $shopId);
        }
        if ($tagId !== null) {
            $eventQuery->where('tag_id', $tagId);
            $txQuery->where('tag_id', $tagId);
        }
        if ($days !== null) {
            $since = Carbon::now()->subDays($days)->startOfDay();
            $eventQuery->where('created_at', '>=', $since);
            $txQuery->where('created_at', '>=', $since);
        }

        $eventCounts = $eventQuery->selectRaw('type, COUNT(*) AS c')->groupBy('type')->pluck('c', 'type');

        $opens = (int) ($eventCounts['tag_open'] ?? 0);
        $started = (int) ($eventCounts['payment_started'] ?? 0);
        $failed = (int) ($eventCounts['payment_failed'] ?? 0);

        $paidQuery = (clone $txQuery)->where('status', 'paid');
        $paidCount = (int) $paidQuery->count();
        $revenue = (int) $paidQuery->sum('amount');

        return [
            'opens' => $opens,
            'started' => $started,
            'paid' => $paidCount,
            'failed' => $failed,
            'revenue' => $revenue, // grosze
            'conversion' => $opens > 0 ? round($paidCount / $opens * 100, 1) : 0.0,
        ];
    }

    /**
     * Dzienna seria opłaconych transakcji (liczba + przychód) za $days dni.
     * Zwraca ['labels' => [...], 'counts' => [...], 'revenue' => [...]] (PLN).
     */
    public function dailyPaidSeries(?int $shopId = null, ?int $tagId = null, int $days = 30): array
    {
        $since = Carbon::now()->subDays($days - 1)->startOfDay();

        $query = Transaction::where('status', 'paid')->where('paid_at', '>=', $since);
        if ($shopId !== null) {
            $query->where('shop_id', $shopId);
        }
        if ($tagId !== null) {
            $query->where('tag_id', $tagId);
        }

        $daily = $query->selectRaw('DATE(paid_at) AS d, COUNT(*) AS c, SUM(amount) AS s')
            ->groupBy('d')->get()->keyBy('d');
        $rows = $daily->map(fn ($r) => (int) $r->c)->toArray();
        $sums = $daily->map(fn ($r) => (int) $r->s)->toArray();

        $labels = [];
        $counts = [];
        $revenue = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $since->copy()->addDays($i);
            $key = $day->toDateString();
            $labels[] = $day->format('d.m');
            $counts[] = (int) ($rows[$key] ?? 0);
            $revenue[] = round(($sums[$key] ?? 0) / 100, 2);
        }

        return ['labels' => $labels, 'counts' => $counts, 'revenue' => $revenue];
    }

    public static function formatPln(int $grosze): string
    {
        return number_format($grosze / 100, 2, ',', ' ') . ' zł';
    }
}
