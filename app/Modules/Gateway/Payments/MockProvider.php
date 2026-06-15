<?php

namespace App\Modules\Gateway\Payments;

use App\Modules\Gateway\Models\Transaction;
use Illuminate\Http\Request;

/**
 * Symulator płatności (dev/demo). Classic — hostowana strona bramki z polem
 * na 6-cyfrowy kod; app2app — animowany ekran "aplikacji banku", sukces po 3 s.
 * Rozliczenie następuje przez wewnętrzne endpointy /mockpay (bez webhooka).
 */
class MockProvider implements PaymentProviderInterface
{
    public function createTransaction(Transaction $transaction, string $customerIp, array $context = []): TransactionDto
    {
        return new TransactionDto(
            providerOrderId: 'MOCK-' . strtoupper(substr($transaction->id, 0, 8)),
            redirectUrl: route('mockpay.show', $transaction->id),
        );
    }

    public function getRedirectUrl(Transaction $transaction): string
    {
        return $transaction->provider_redirect_url ?: route('mockpay.show', $transaction->id);
    }

    public function handleWebhook(Request $request): WebhookResult
    {
        // MockProvider rozlicza transakcje wewnętrznie — webhook nie występuje.
        return new WebhookResult(valid: false);
    }
}
