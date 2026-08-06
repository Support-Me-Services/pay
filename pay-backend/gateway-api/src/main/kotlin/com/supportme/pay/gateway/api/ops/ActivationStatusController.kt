package com.supportme.pay.gateway.api.ops

import com.fasterxml.jackson.databind.ObjectMapper
import com.supportme.pay.gateway.api.config.PayUNewPosConfigProperties
import com.supportme.pay.gateway.api.config.PaymentConfigProperties
import com.supportme.pay.gateway.domain.entity.TransactionStatus
import com.supportme.pay.gateway.domain.repository.TransactionRepository
import com.supportme.pay.gateway.payments.PayUOAuthTokenCache
import org.slf4j.LoggerFactory
import org.springframework.http.MediaType
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RequestParam
import org.springframework.web.bind.annotation.RestController
import org.springframework.web.client.RestClient
import java.security.MessageDigest
import java.time.Instant
import java.time.temporal.ChronoUnit

data class NewPosStatus(val methods: Int? = null, val blikEnabled: Boolean? = null, val error: String? = null)
data class ActivationStatusResponse(
    val checkedAt: String,
    val newPos: NewPosStatus,
    val paidLast24h: Long,
    val pendingLast24h: Long,
    val activationLikelyDone: Boolean,
)

/**
 * Odpowiednik `ActivationStatusController` — tokenowany endpoint dla
 * zewnętrznego monitoringu aktywacji konta PayU. Sygnał aktywacji: PayU
 * provisionuje metody płatności na nowym POS-ie. ZAWSZE bije w PRODUKCYJNE
 * `secure.payu.com`, niezależnie od `payu.env` (sandbox/production) —
 * to jest osobny, docelowo produkcyjny POS, nie ten obsługujący ruch.
 */
@RestController
@RequestMapping("/activation-status")
class ActivationStatusController(
    private val paymentConfig: PaymentConfigProperties,
    private val newPosConfig: PayUNewPosConfigProperties,
    private val transactionRepository: TransactionRepository,
) {
    private val log = LoggerFactory.getLogger(ActivationStatusController::class.java)
    private val objectMapper = ObjectMapper()
    private val tokenCache = PayUOAuthTokenCache()
    private val restClient = RestClient.builder().baseUrl(PRODUCTION_BASE_URL).build()

    @GetMapping
    fun show(@RequestParam token: String?): ResponseEntity<Any> {
        if (!MessageDigest.isEqual(paymentConfig.activationCheckToken.toByteArray(), (token ?: "").toByteArray())) {
            return ResponseEntity.status(401).body(mapOf("error" to "unauthorized"))
        }

        val newPos = fetchNewPosStatus()
        val since24h = Instant.now().minus(1, ChronoUnit.DAYS)

        return ResponseEntity.ok(
            ActivationStatusResponse(
                checkedAt = Instant.now().toString(),
                newPos = newPos,
                paidLast24h = transactionRepository.countByStatusAndPaidAtAfter(TransactionStatus.PAID, since24h),
                pendingLast24h = transactionRepository.countByStatusAndCreatedAtAfter(TransactionStatus.PENDING, since24h),
                activationLikelyDone = (newPos.methods ?: 0) > 0,
            ),
        )
    }

    private fun fetchNewPosStatus(): NewPosStatus {
        val token = try {
            tokenCache.getOrFetch(PayUOAuthTokenCache.NEW_POS_KEY) { fetchAccessToken() }
        } catch (e: Exception) {
            log.warn("ActivationStatus: OAuth PayU (newpos) nieudane: {}", e.message)
            return NewPosStatus(error = "oauth_failed")
        }

        return try {
            val responseText = restClient.get()
                .uri("/api/v2_1/paymethods?lang=pl")
                .headers { it.setBearerAuth(token) }
                .retrieve()
                .body(String::class.java) ?: "{}"

            val enabled = objectMapper.readTree(responseText).path("payByLinks")
                .filter { it.path("status").asText() == "ENABLED" }

            NewPosStatus(
                methods = enabled.size,
                blikEnabled = enabled.any { it.path("value").asText() == "blik" },
            )
        } catch (e: Exception) {
            log.warn("ActivationStatus: pobranie paymethods (newpos) nieudane: {}", e.message)
            NewPosStatus(error = "exception")
        }
    }

    private fun fetchAccessToken(): Pair<String, Long> {
        val form = "grant_type=client_credentials" +
            "&client_id=${newPosConfig.clientId}" +
            "&client_secret=${newPosConfig.clientSecret}"

        val bodyText = restClient.post()
            .uri("/pl/standard/user/oauth/authorize")
            .contentType(MediaType.APPLICATION_FORM_URLENCODED)
            .body(form)
            .retrieve()
            .body(String::class.java) ?: throw IllegalStateException("Brak odpowiedzi z endpointu OAuth PayU (newpos)")

        val json = objectMapper.readTree(bodyText)
        val token = json.path("access_token").takeIf { it.isTextual }?.asText()
            ?: throw IllegalStateException("Odpowiedź OAuth PayU (newpos) bez access_token")
        val expiresIn = json.path("expires_in").takeIf { it.isIntegralNumber }?.asLong() ?: 43199L
        return token to expiresIn
    }

    companion object {
        private const val PRODUCTION_BASE_URL = "https://secure.payu.com"
    }
}
