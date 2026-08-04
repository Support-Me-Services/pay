package com.supportme.pay.gateway.payments

/**
 * Dane potrzebne dostawcy do stworzenia zamówienia — celowo NIE encja JPA
 * `Transaction` (gateway-domain), żeby ten moduł zostawał testowalny bez
 * Springa/JPA/bazy (odpowiednik parametrów przekazywanych do
 * `PayUProvider::createTransaction($transaction, $ip, $context)` w PHP,
 * spłaszczonych do tego, co faktycznie jest odczytywane z modelu).
 */
data class PaymentOrderRequest(
    val transactionId: String,
    val productName: String,
    val amountGrosze: Int,
    val currency: String,
    val returnUrl: String,
    val notifyUrl: String?,
)

data class ProviderResult(
    val providerOrderId: String,
    val redirectUrl: String?,
    val status: String = "pending",
)

data class BankOption(val value: String, val name: String, val image: String?)

data class WebhookResult(val valid: Boolean, val transactionId: String?, val status: Status) {
    enum class Status { PAID, FAILED, IGNORED }
}

/** Odpowiednik `App\Modules\Gateway\Payments\PaymentProviderInterface`. */
interface PaymentProvider {
    fun createTransaction(
        request: PaymentOrderRequest,
        customerIp: String,
        context: PaymentContext = PaymentContext.None,
    ): ProviderResult

    fun getOrderStatus(providerOrderId: String): String?

    /** `true` jeśli capture się powiódł (zamówienie było w WAITING_FOR_CONFIRMATION). */
    fun capture(providerOrderId: String): Boolean

    fun payByLinks(): List<BankOption>

    fun handleWebhook(rawBody: String, signatureHeader: String?): WebhookResult
}
