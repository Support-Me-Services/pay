<?php

namespace App\Modules\Gateway\Http\Controllers\Panel;

use App\Modules\Gateway\Http\Controllers\Controller;
use App\Modules\Gateway\Models\Shop;
use App\Modules\Gateway\Models\Transaction;
use App\Modules\Gateway\Services\StatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $shops = Shop::orderBy('name')->get(['id', 'name']);

        $shopId = $request->integer('shop_id') ?: null;
        $status = $request->string('status')->toString() ?: null;

        $query = Transaction::query()
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->when($status, fn ($q) => $q->where('status', $status));

        $totalAmount = (int) (clone $query)->where('status', 'paid')->sum('amount');

        $transactions = $query->with(['shop:id,name', 'tag:id,tag_uid,label'])
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString()
            ->through(fn (Transaction $tx) => [
                'id' => $tx->id,
                'created_at' => $tx->created_at?->format('d.m.Y H:i'),
                'shop' => $tx->shop?->name,
                'tag' => $tx->tag?->label ?: $tx->tag?->tag_uid,
                'product_name' => $tx->product_name,
                'amount' => $tx->amountPln() . ' zł',
                'status' => $tx->status,
                'mode' => $tx->mode,
                'buyer' => trim(($tx->buyer_first_name ?? '') . ' ' . ($tx->buyer_last_name ?? '')) ?: null,
                'buyer_email' => $tx->buyer_email,
                'paid_at' => $tx->paid_at?->format('d.m.Y H:i'),
            ]);

        return Inertia::render('Gateway/Transactions', [
            'shops' => $shops,
            'shopId' => $shopId,
            'status' => $status,
            'transactions' => $transactions,
            'totalAmount' => StatsService::formatPln($totalAmount),
            'transactionsUrl' => route('panel.transactions'),
        ]);
    }
}
