<?php

namespace App\Modules\Gateway\Console\Commands;

use App\Modules\Gateway\Models\Transaction;
use App\Modules\Gateway\Services\TransactionService;
use Illuminate\Console\Command;

class ReconcilePendingTransactions extends Command
{
    protected $signature = 'payu:reconcile';

    protected $description = 'Aktywnie weryfikuje w PayU status wiszących transakcji (pending) — uzupełnia webhooki';

    public function handle(TransactionService $transactions): int
    {
        if (config('payment.provider') !== 'payu') {
            return self::SUCCESS;
        }

        $pending = Transaction::where('status', 'pending')
            ->whereNotNull('provider_order_id')
            ->where('created_at', '>', now()->subDays(11)) // PBL validUntil ~10 dni
            ->orderBy('created_at')
            ->limit(50)
            ->get();

        foreach ($pending as $transaction) {
            $transactions->reconcileWithProvider($transaction);
        }

        $this->info('Sprawdzono: ' . $pending->count());

        return self::SUCCESS;
    }
}
