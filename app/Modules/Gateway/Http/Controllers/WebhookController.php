<?php

namespace App\Modules\Gateway\Http\Controllers;

use App\Modules\Gateway\Models\Transaction;
use App\Modules\Gateway\Payments\PaymentProviderInterface;
use App\Modules\Gateway\Payments\WebhookResult;
use App\Modules\Gateway\Services\TransactionService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(
        private readonly PaymentProviderInterface $provider,
        private readonly TransactionService $transactions,
    ) {
    }

    /**
     * POST /webhooks/payu — notyfikacje PayU. Po poprawnej weryfikacji
     * podpisu zawsze odpowiadamy 200 (inaczej PayU ponawia).
     */
    public function payu(Request $request)
    {
        $result = $this->provider->handleWebhook($request);

        if (! $result->valid) {
            return response()->json(['error' => 'Niepoprawny podpis'], 401);
        }

        if ($result->transactionId) {
            $transaction = Transaction::find($result->transactionId);

            if ($transaction) {
                match ($result->status) {
                    WebhookResult::STATUS_PAID => $this->transactions->markPaid($transaction),
                    WebhookResult::STATUS_FAILED => $this->transactions->markFailed($transaction),
                    default => null,
                };
            }
        }

        return response()->json(['ok' => true]);
    }
}
