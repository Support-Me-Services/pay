package com.supportme.pay.storefront.api.gateway

import com.fasterxml.jackson.databind.ObjectMapper
import com.supportme.pay.platform.http.currentRequestBaseUrl
import com.supportme.pay.platform.tenant.TenantContext
import org.slf4j.LoggerFactory
import org.springframework.boot.context.properties.ConfigurationProperties
import org.springframework.http.MediaType
import org.springframework.stereotype.Component
import org.springframework.web.client.RestClient
import org.springframework.web.client.RestClientResponseException
import java.security.MessageDigest
import java.time.Duration
import javax.crypto.Mac
import javax.crypto.spec.SecretKeySpec

data class GatewayCreateTransactionRequest(
    val productExternalId: String,
    val productName: String,
    val amount: Int,
    val returnUrl: String,
    val notifyUrl: String? = null,
    val tagUid: String? = null,
)

data class GatewayCreateTransactionResponse(val uuid: String, val paymentUrl: String)

data class GatewayTransactionStatus(
    val uuid: String,
    val status: String,
    val amount: Int,
    val currency: String,
    val mode: String,
    val paidAt: String?,
    val createdAt: String?,
)

/** Odpowiednik `config('shop.gateway_url')` — fallback POZA kontekstem HTTP (praktycznie nieużywany tu). */
@ConfigurationProperties(prefix = "shop")
data class ShopConfigProperties(val gatewayUrl: String = "http://localhost:8080")

/**
 * Port 1:1 z `App\Modules\Storefront\Services\GatewayClient` — Storefront
 * rozmawia z Gateway WYŁĄCZNIE przez REST (nawet w monolicie, ta sama JVM),
 * NIGDY przez współdzielone encje JPA — utrzymuje granicę modułów widoczną
 * w kompilacji (storefront-api nie zależy od gateway-domain/gateway-api).
 * `baseUrl` = host BIEŻĄCEGO żądania (nie stała subdomena) — klient płatności
 * zostaje na domenie sklepu zamiast być przekierowanym na pay.*.
 */
@Component
class GatewayClient(
    private val shopConfig: ShopConfigProperties,
    private val objectMapper: ObjectMapper,
) {
    private val log = LoggerFactory.getLogger(GatewayClient::class.java)

    private val restClient = RestClient.builder()
        .requestFactory(
            org.springframework.http.client.JdkClientHttpRequestFactory(
                java.net.http.HttpClient.newBuilder().connectTimeout(Duration.ofSeconds(5)).build(),
            ),
        )
        .build()

    private fun baseUrl(): String = currentRequestBaseUrl(shopConfig.gatewayUrl)

    private fun apiKey(): String = TenantContext.current().gatewayApiKey
        ?: error("Brak gateway-api-key skonfigurowanego dla tego hosta")

    fun createTransaction(request: GatewayCreateTransactionRequest): GatewayCreateTransactionResponse {
        val responseText = restClient.post()
            .uri("${baseUrl()}/api/gateway/v1/transactions")
            .header("X-Api-Key", apiKey())
            .contentType(MediaType.APPLICATION_JSON)
            .body(objectMapper.writeValueAsString(request))
            .retrieve()
            .body(String::class.java)
            ?: throw GatewayClientException("Brak odpowiedzi z Gateway przy tworzeniu transakcji")

        return objectMapper.readValue(responseText, GatewayCreateTransactionResponse::class.java)
    }

    fun getTransaction(uuid: String): GatewayTransactionStatus? {
        return try {
            val responseText = restClient.get()
                .uri("${baseUrl()}/api/gateway/v1/transactions/$uuid")
                .header("X-Api-Key", apiKey())
                .retrieve()
                .body(String::class.java) ?: return null
            objectMapper.readValue(responseText, GatewayTransactionStatus::class.java)
        } catch (e: RestClientResponseException) {
            if (e.statusCode.value() == 404) null else throw e
        }
    }

    /** Fire-and-forget (3s timeout), błędy tylko logowane — jak w oryginale. */
    fun sendEvent(type: String, tagUid: String?) {
        try {
            restClient.post()
                .uri("${baseUrl()}/api/gateway/v1/events")
                .header("X-Api-Key", apiKey())
                .contentType(MediaType.APPLICATION_JSON)
                .body(objectMapper.writeValueAsString(mapOf("type" to type, "tagUid" to tagUid)))
                .retrieve()
                .toBodilessEntity()
        } catch (e: Exception) {
            log.warn("GatewayClient.sendEvent nieudane: {}", e.message)
        }
    }

    /** HMAC-SHA256 nad body, kluczem = własny `gatewayApiKey` sklepu (odpowiednik podpisu webhooka Gateway->Shop). */
    fun verifyWebhookSignature(body: String, signatureHeader: String?): Boolean {
        if (signatureHeader.isNullOrBlank()) return false
        val mac = Mac.getInstance("HmacSHA256")
        mac.init(SecretKeySpec(apiKey().toByteArray(Charsets.UTF_8), "HmacSHA256"))
        val expected = mac.doFinal(body.toByteArray(Charsets.UTF_8)).joinToString("") { "%02x".format(it) }
        return MessageDigest.isEqual(expected.toByteArray(Charsets.UTF_8), signatureHeader.lowercase().toByteArray(Charsets.UTF_8))
    }
}

class GatewayClientException(message: String) : RuntimeException(message)
