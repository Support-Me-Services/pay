<?php

namespace App\Modules\Gateway\Services;

use App\Modules\Gateway\Models\Event;
use App\Modules\Gateway\Models\Transaction;
use App\Modules\Gateway\Payments\PayUProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    /**
     * Aktywna rekonsyliacja z PayU: webhooki bywają opóźnione, więc przy
     * pollingu statusu sami pytamy operatora. Zamówienie czekające na odbiór
     * sprzedawcy (WAITING_FOR_CONFIRMATION) przyjmujemy automatycznie.
     */
    public function reconcileWithProvider(Transaction $transaction): void
    {
        if ($transaction->status !== 'pending' || ! $transaction->provider_order_id) {
            return;
        }
        if (config('payment.provider') !== 'payu') {
            return; // MockProvider rozlicza się sam
        }
        // throttle — polling leci co 2 s z kilku miejsc naraz
        if (! Cache::add('reconcile:' . $transaction->id, 1, 3)) {
            return;
        }

        try {
            $provider = new PayUProvider();
            $status = $provider->getOrderStatus($transaction->provider_order_id);

            match ($status) {
                'COMPLETED' => $this->markPaid($transaction),
                'CANCELED' => $this->markFailed($transaction),
                'WAITING_FOR_CONFIRMATION' => $provider->capture($transaction->provider_order_id)
                    ? $this->markPaid($transaction)
                    : null,
                default => null, // PENDING / NEW — czekamy dalej
            };
        } catch (\Throwable $e) {
            Log::warning('Rekonsyliacja z PayU nieudana', [
                'transaction' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Oznacza transakcję jako opłaconą (idempotentnie), loguje event
     * payment_success i wysyła webhook do sklepu.
     */
    public function markPaid(Transaction $transaction): void
    {
        if ($transaction->isFinal()) {
            return;
        }

        $transaction->update(['status' => 'paid', 'paid_at' => now()]);
        $this->logEvent($transaction, 'payment_success');
        $this->notifyShop($transaction);
    }

    /**
     * Oznacza transakcję jako nieudaną (idempotentnie), loguje event
     * payment_failed i wysyła webhook do sklepu.
     */
    public function markFailed(Transaction $transaction): void
    {
        if ($transaction->isFinal()) {
            return;
        }

        $transaction->update(['status' => 'failed']);
        $this->logEvent($transaction, 'payment_failed');
        $this->notifyShop($transaction);
    }

    public function logEvent(Transaction $transaction, string $type): void
    {
        Event::create([
            'shop_id' => $transaction->shop_id,
            'tag_id' => $transaction->tag_id,
            'transaction_id' => $transaction->id,
            'type' => $type,
        ]);
    }

    /**
     * Webhook wychodzący do sklepu: POST {notify_url} z {uuid, status, paid_at},
     * podpis HMAC-SHA256 z api_key sklepu w nagłówku X-Signature.
     */
    public function notifyShop(Transaction $transaction): void
    {
        if (! $transaction->notify_url) {
            return;
        }

        $payload = [
            'uuid' => $transaction->id,
            'status' => $transaction->status,
            'paid_at' => $transaction->paid_at?->toIso8601String(),
        ];

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = hash_hmac('sha256', $body, $transaction->shop->api_key);

        try {
            Http::withBody($body, 'application/json')
                ->withHeaders(['X-Signature' => $signature])
                ->timeout(5)
                ->post($transaction->notify_url);
        } catch (\Throwable $e) {
            // Sklep i tak weryfikuje status odpytując GET /api/v1/transactions/{uuid}.
            Log::warning('Webhook do sklepu nieudany', [
                'transaction' => $transaction->id,
                'notify_url' => $transaction->notify_url,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
