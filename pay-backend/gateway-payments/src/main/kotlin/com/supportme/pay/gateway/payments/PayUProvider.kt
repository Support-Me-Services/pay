package com.supportme.pay.gateway.payments

import com.fasterxml.jackson.databind.JsonNode
import com.fasterxml.jackson.databind.ObjectMapper
import com.github.benmanes.caffeine.cache.Caffeine
import org.springframework.http.HttpHeaders
import org.springframework.http.MediaType
import org.springframework.http.client.JdkClientHttpRequestFactory
import org.springframework.web.client.RestClient
import java.net.http.HttpClient
import java.time.Duration
import java.util.concurrent.TimeUnit

/**
 * Port 1:1 z `app/Modules/Gateway/Payments/PayUProvider.php` — REST API PayU
 * v2.1, ręcznie (brak SDK w oryginale). Szczegóły KRYTYCZNE do zachowania:
 * - odczyt statusu zamówienia z JSON BODY odpowiedzi 302 (`redirects=NEVER`
 *   na `HttpClient`, nie podążamy za `Location`),
 * - dokładna kolejność wariantów payloadu w [buildOrderPayload] (pbl -> blik
 *   -> classic — jak `if/elseif` w PHP, nie niezależne reguły),
 * - syntetyczny e-mail dla BLIK Level 0 (nie zbieramy realnego maila klienta).
 */
class PayUProvider(
    private val properties: PayUProperties,
    private val tokenCache: PayUOAuthTokenCache,
    private val objectMapper: ObjectMapper = ObjectMapper(),
) : PaymentProvider {

    private val httpClient: HttpClient = HttpClient.newBuilder()
        .followRedirects(HttpClient.Redirect.NEVER)
        .connectTimeout(Duration.ofSeconds(10))
        .build()

    private val restClient: RestClient = RestClient.builder()
        .requestFactory(JdkClientHttpRequestFactory(httpClient))
        .baseUrl(properties.baseUrl)
        .build()

    private val payByLinksCache = Caffeine.newBuilder()
        .expireAfterWrite(3600, TimeUnit.SECONDS)
        .build<String, List<BankOption>>()

    private fun accessToken(): String = tokenCache.getOrFetch(PayUOAuthTokenCache.MAIN_POS_KEY) { fetchAccessToken() }

    private fun fetchAccessToken(): Pair<String, Long> {
        val form = "grant_type=client_credentials" +
            "&client_id=${properties.effectiveClientId}" +
            "&client_secret=${properties.clientSecret}"

        val bodyText = restClient.post()
            .uri("/pl/standard/user/oauth/authorize")
            .contentType(MediaType.APPLICATION_FORM_URLENCODED)
            .body(form)
            .retrieve()
            .body(String::class.java)
            ?: throw PayUException("Brak odpowiedzi z endpointu OAuth PayU")

        val json = objectMapper.readTree(bodyText)
        val token = json.path("access_token").takeIf { it.isTextual }?.asText()
            ?: throw PayUException("Odpowiedź OAuth PayU bez access_token: $bodyText")
        val expiresIn = json.path("expires_in").takeIf { it.isIntegralNumber }?.asLong() ?: 43199L
        return token to expiresIn
    }

    override fun createTransaction(
        request: PaymentOrderRequest,
        customerIp: String,
        context: PaymentContext,
    ): ProviderResult {
        val payload = buildOrderPayload(request, customerIp, context)

        // .retrieve() (nie .exchange) — PayU odpowiada 302 z JSON-em w body (nie
        // Location), a domyślny error handler RestClient traktuje jako błąd
        // tylko 4xx/5xx, więc 302 przechodzi normalnie przez .body(...).
        val responseText: String = restClient.post()
            .uri("/api/v2_1/orders")
            .headers { it.setBearerAuth(accessToken()) }
            .contentType(MediaType.APPLICATION_JSON)
            .body(objectMapper.writeValueAsString(payload))
            .retrieve()
            .body(String::class.java)
            ?: throw PayUException("Brak odpowiedzi z endpointu tworzenia zamówienia PayU")

        val json = objectMapper.readTree(responseText)
        val statusCode = json.path("status").path("statusCode").asText(null)
        val orderId = json.path("orderId").asText(null)
        val redirectUri = json.path("redirectUri").takeIf { it.isTextual }?.asText()

        val isBlik = context is PaymentContext.BlikCode
        if (isBlik && statusCode == "SUCCESS") {
            // BLIK Level 0: SUCCESS bez redirectUri jest poprawny — płatność
            // czeka na push w aplikacji banku, status przyjdzie webhookiem/pollingiem.
            return ProviderResult(providerOrderId = orderId ?: throw PayUException("Brak orderId w odpowiedzi PayU"), redirectUrl = redirectUri)
        }

        val acceptableStatuses = setOf("SUCCESS", "WARNING_CONTINUE_3DS", "WARNING_CONTINUE_REDIRECT")
        if (statusCode !in acceptableStatuses || redirectUri.isNullOrBlank()) {
            throw PayUException("Utworzenie zamówienia PayU nieudane: statusCode=$statusCode")
        }

        return ProviderResult(providerOrderId = orderId!!, redirectUrl = redirectUri)
    }

    /**
     * Kolejność MA znaczenie biznesowe — dokładnie jak `if/elseif` w PHP:
     * 1) `pbl` ustawiony -> app2app przez konkretny bank,
     * 2) `blik_code` ustawiony -> BLIK Level 0,
     * 3) tryb app2app bez wyboru metody -> wymuszony PBL "blik" (ekran wpisania kodu),
     * 4) w przeciwnym razie -> klasyczna hostowana strona PayU.
     */
    private fun buildOrderPayload(request: PaymentOrderRequest, customerIp: String, context: PaymentContext): Map<String, Any?> {
        val base = linkedMapOf<String, Any?>(
            "merchantPosId" to properties.posId,
            "extOrderId" to request.transactionId,
            "customerIp" to customerIp.ifBlank { "127.0.0.1" },
            "description" to request.productName,
            "currencyCode" to request.currency,
            "totalAmount" to request.amountGrosze.toString(),
            "continueUrl" to request.returnUrl,
            "notifyUrl" to request.notifyUrl,
            "products" to listOf(
                linkedMapOf(
                    "name" to request.productName,
                    "unitPrice" to request.amountGrosze.toString(),
                    "quantity" to "1",
                ),
            ),
        )

        return when (context) {
            is PaymentContext.Pbl -> base + mapOf(
                "payMethods" to mapOf("payMethod" to mapOf("type" to "PBL", "value" to context.bankCode)),
            )

            is PaymentContext.BlikCode -> base + mapOf(
                "buyer" to mapOf("email" to syntheticBlikEmail(request.transactionId)),
                "payMethods" to mapOf(
                    "payMethod" to mapOf("type" to "BLIK_AUTHORIZATION_CODE", "value" to context.code),
                ),
            )

            PaymentContext.Classic -> base

            PaymentContext.None -> base
        }
    }

    /** Syntetyczny e-mail wymagany przez PayU dla BLIK — nie zbieramy realnego maila klienta. */
    private fun syntheticBlikEmail(transactionId: String): String =
        "klient+${transactionId.take(8)}@pay.please-support-me.com"

    override fun getOrderStatus(providerOrderId: String): String? {
        val responseText: String = restClient.get()
            .uri("/api/v2_1/orders/{orderId}", providerOrderId)
            .headers { it.setBearerAuth(accessToken()) }
            .retrieve()
            .body(String::class.java)
            ?: return null

        val json = objectMapper.readTree(responseText)
        return json.path("orders").firstOrNull()?.path("status")?.takeIf { it.isTextual }?.asText()
    }

    override fun capture(providerOrderId: String): Boolean {
        val status = restClient.put()
            .uri("/api/v2_1/orders/{orderId}/status", providerOrderId)
            .headers { it.setBearerAuth(accessToken()) }
            .contentType(MediaType.APPLICATION_JSON)
            .body(objectMapper.writeValueAsString(mapOf("orderStatus" to "COMPLETED")))
            .retrieve()
            .toBodilessEntity()
            .statusCode

        return status.is2xxSuccessful
    }

    override fun payByLinks(): List<BankOption> = payByLinksCache.get(CACHE_KEY_PAYBYLINKS) {
        val responseText = restClient.get()
            .uri("/api/v2_1/paymethods?lang=pl")
            .headers { it.setBearerAuth(accessToken()) }
            .retrieve()
            .body(String::class.java) ?: "{}"

        val json = objectMapper.readTree(responseText)
        json.path("payByLinks").filter { entry ->
            entry.path("status").asText() == "ENABLED" &&
                entry.path("value").asText() !in EXCLUDED_PBL_VALUES
        }.map { entry ->
            BankOption(
                value = entry.path("value").asText(),
                name = entry.path("name").asText(),
                image = entry.path("brandImageUrl").takeIf { it.isTextual }?.asText(),
            )
        }
    }

    override fun handleWebhook(rawBody: String, signatureHeader: String?): WebhookResult {
        if (!PayUSignatureVerifier.verify(rawBody, signatureHeader, properties.secondKey)) {
            return WebhookResult(valid = false, transactionId = null, status = WebhookResult.Status.IGNORED)
        }

        val json = objectMapper.readTree(rawBody)
        val extOrderId = PayUSignatureVerifier.extractExtOrderId(json)
        val orderStatus = PayUSignatureVerifier.extractOrderStatus(json)

        val status = when (orderStatus) {
            "COMPLETED" -> WebhookResult.Status.PAID
            "CANCELED" -> WebhookResult.Status.FAILED
            else -> WebhookResult.Status.IGNORED
        }

        return WebhookResult(valid = true, transactionId = extOrderId, status = status)
    }

    private fun JsonNode.firstOrNull(): JsonNode? = if (this.isArray && this.size() > 0) this[0] else null

    companion object {
        private const val CACHE_KEY_PAYBYLINKS = "paybylinks"

        /** `blik` ma dedykowaną ścieżkę osobno; `b` to wolny tradycyjny przelew (celowo pomijany). */
        private val EXCLUDED_PBL_VALUES = setOf("blik", "b", "dpp", "dpt", "c")
    }
}

class PayUException(message: String) : RuntimeException(message)
