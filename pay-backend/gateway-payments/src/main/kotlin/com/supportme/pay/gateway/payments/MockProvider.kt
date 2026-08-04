package com.supportme.pay.gateway.payments

/**
 * Symulator dev/demo (bez realnego PayU) — port `MockProvider.php`.
 * Rozliczenie NIE idzie przez `handleWebhook` (zawsze `valid=false`), tylko
 * przez własne endpointy `/mockpay/{uuid}/confirm|fail` wołające bezpośrednio
 * `TransactionService.markPaid/markFailed` (Faza 3, warstwa REST).
 */
class MockProvider(private val mockPayBaseUrl: (transactionId: String) -> String) : PaymentProvider {

    override fun createTransaction(
        request: PaymentOrderRequest,
        customerIp: String,
        context: PaymentContext,
    ): ProviderResult = ProviderResult(
        providerOrderId = "MOCK-${request.transactionId.take(8).uppercase()}",
        redirectUrl = mockPayBaseUrl(request.transactionId),
    )

    override fun getOrderStatus(providerOrderId: String): String? = null

    override fun capture(providerOrderId: String): Boolean = false

    override fun payByLinks(): List<BankOption> = emptyList()

    override fun handleWebhook(rawBody: String, signatureHeader: String?): WebhookResult =
        WebhookResult(valid = false, transactionId = null, status = WebhookResult.Status.IGNORED)
}
