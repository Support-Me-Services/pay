package com.supportme.pay.gateway.api.payment

import com.fasterxml.jackson.databind.ObjectMapper
import com.github.benmanes.caffeine.cache.Caffeine
import com.supportme.pay.gateway.domain.entity.Event
import com.supportme.pay.gateway.domain.entity.EventType
import com.supportme.pay.gateway.domain.entity.Transaction
import com.supportme.pay.gateway.domain.entity.TransactionStatus
import com.supportme.pay.gateway.domain.repository.GatewayEventRepository
import com.supportme.pay.gateway.domain.repository.TransactionRepository
import com.supportme.pay.gateway.payments.PaymentProvider
import org.slf4j.LoggerFactory
import org.springframework.http.MediaType
import org.springframework.stereotype.Service
import org.springframework.transaction.annotation.Transactional
import org.springframework.web.client.RestClient
import java.time.Duration
import java.time.Instant
import java.util.concurrent.TimeUnit
import javax.crypto.Mac
import javax.crypto.spec.SecretKeySpec

/**
 * Port 1:1 z `App\Modules\Gateway\Services\TransactionService` — rdzeń
 * cyklu życia płatności. `markPaid`/`markFailed` IDEMPOTENTNE (guard
 * `isFinal()`) — krytyczne, bo webhook + poller + mockpay mogą próbować
 * rozliczyć tę samą transakcję współbieżnie.
 */
@Service
class TransactionService(
    private val transactionRepository: TransactionRepository,
    private val gatewayEventRepository: GatewayEventRepository,
    private val paymentProvider: PaymentProvider,
    private val objectMapper: ObjectMapper,
) {
    private val log = LoggerFactory.getLogger(TransactionService::class.java)

    private val notifyClient = RestClient.builder()
        .requestFactory(
            org.springframework.http.client.JdkClientHttpRequestFactory(
                java.net.http.HttpClient.newBuilder().connectTimeout(Duration.ofSeconds(5)).build(),
            ).also { it.setReadTimeout(Duration.ofSeconds(5)) },
        )
        .build()

    /** Dedupe współbieżnych pollerów w oknie 3s — odpowiednik `Cache::add('reconcile:'.id, 1, 3)`. */
    private val reconcileThrottle = Caffeine.newBuilder()
        .expireAfterWrite(3, TimeUnit.SECONDS)
        .build<String, Boolean>()

    fun logEvent(transaction: Transaction, type: EventType) {
        gatewayEventRepository.save(
            Event(shop = transaction.shop, tag = transaction.tag, transactionId = transaction.id, type = type),
        )
    }

    @Transactional("gatewayTransactionManager")
    fun markPaid(transaction: Transaction) {
        if (transaction.isFinal()) return
        transaction.status = TransactionStatus.PAID
        transaction.paidAt = Instant.now()
        transactionRepository.save(transaction)
        logEvent(transaction, EventType.PAYMENT_SUCCESS)
        notifyShop(transaction)
    }

    @Transactional("gatewayTransactionManager")
    fun markFailed(transaction: Transaction) {
        if (transaction.isFinal()) return
        transaction.status = TransactionStatus.FAILED
        transactionRepository.save(transaction)
        logEvent(transaction, EventType.PAYMENT_FAILED)
        notifyShop(transaction)
    }

    /**
     * Fire-and-forget webhook do sklepu-klienta — BEZ retry/kolejki (zamierzone,
     * sklep i tak ma pollingiem sprawdzać `GET /api/v1/transactions/{uuid}`).
     */
    fun notifyShop(transaction: Transaction) {
        val notifyUrl = transaction.notifyUrl ?: return
        try {
            val body = objectMapper.writeValueAsString(
                mapOf(
                    "uuid" to transaction.id.toString(),
                    "status" to transaction.status.dbValue,
                    "paid_at" to transaction.paidAt?.toString(),
                ),
            )
            val signature = hmacSha256Hex(body, transaction.shop.apiKey)
            notifyClient.post()
                .uri(notifyUrl)
                .header("X-Signature", signature)
                .contentType(MediaType.APPLICATION_JSON)
                .body(body)
                .retrieve()
                .toBodilessEntity()
        } catch (e: Exception) {
            log.warn("notifyShop nieudany dla transakcji {}: {}", transaction.id, e.message)
        }
    }

    /**
     * Aktywna rekoncyliacja — wywoływana z pollingu klienta/sklepu. No-op
     * poza `pending` z ustawionym `providerOrderId`; throttlowana 3s.
     */
    fun reconcileWithProvider(transaction: Transaction) {
        if (transaction.status != TransactionStatus.PENDING) return
        val orderId = transaction.providerOrderId ?: return

        val key = transaction.id.toString()
        if (reconcileThrottle.getIfPresent(key) != null) return
        reconcileThrottle.put(key, true)

        try {
            when (paymentProvider.getOrderStatus(orderId)) {
                "COMPLETED" -> markPaid(transaction)
                "CANCELED" -> markFailed(transaction)
                "WAITING_FOR_CONFIRMATION" -> if (paymentProvider.capture(orderId)) markPaid(transaction)
                else -> Unit
            }
        } catch (e: Exception) {
            log.warn("Rekoncyliacja nieudana dla transakcji {}: {}", transaction.id, e.message)
        }
    }

    private fun hmacSha256Hex(body: String, key: String): String {
        val mac = Mac.getInstance("HmacSHA256")
        mac.init(SecretKeySpec(key.toByteArray(Charsets.UTF_8), "HmacSHA256"))
        return mac.doFinal(body.toByteArray(Charsets.UTF_8)).joinToString("") { "%02x".format(it) }
    }
}
