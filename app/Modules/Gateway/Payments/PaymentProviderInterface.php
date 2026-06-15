<?php

namespace App\Modules\Gateway\Payments;

use App\Modules\Gateway\Models\Transaction;
use Illuminate\Http\Request;

interface PaymentProviderInterface
{
    /**
     * Tworzy płatność u operatora dla istniejącej transakcji bramki.
     *
     * @param Transaction $transaction transakcja bramki (amount, currency, mode, return_url, notify_url)
     * @param string      $customerIp  IP klienta (wymagane przez PayU)
     * @param array       $context     dodatkowe dane, np. ['blik_code' => '123456'] dla BLIK Level 0
     */
    public function createTransaction(Transaction $transaction, string $customerIp, array $context = []): TransactionDto;

    /**
     * URL, na który należy przekierować klienta, by dokończył płatność.
     */
    public function getRedirectUrl(Transaction $transaction): string;

    /**
     * Weryfikuje i interpretuje webhook operatora.
     */
    public function handleWebhook(Request $request): WebhookResult;
}
