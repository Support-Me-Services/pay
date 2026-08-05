package com.supportme.pay.storefront.api.order

import com.fasterxml.jackson.databind.ObjectMapper
import com.supportme.pay.storefront.api.gateway.GatewayClient
import com.supportme.pay.storefront.domain.repository.OrderRepository
import jakarta.servlet.http.HttpServletRequest
import org.springframework.http.HttpStatus
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController
import java.util.UUID

/**
 * Odpowiednik `GatewayWebhookController::handle` — webhook Gateway->Shop
 * (`{uuid, status, paidAt}`, header `X-Signature`, HMAC własnym `gatewayApiKey`).
 * RÓŻNI się od Gateway's `WebhookController::payu` (PayU->Gateway) — to jest
 * "druga strona" `TransactionService::notifyShop`.
 */
@RestController
@RequestMapping("/webhooks/gateway")
class GatewayWebhookController(
    private val orderRepository: OrderRepository,
    private val orderSyncService: OrderSyncService,
    private val gatewayClient: GatewayClient,
    private val objectMapper: ObjectMapper,
) {

    @PostMapping
    fun handle(request: HttpServletRequest): ResponseEntity<Map<String, Any>> {
        val rawBody = request.reader.readText()
        val signature = request.getHeader("X-Signature")

        if (!gatewayClient.verifyWebhookSignature(rawBody, signature)) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).body(mapOf("error" to "Niepoprawny podpis"))
        }

        val json = objectMapper.readTree(rawBody)
        val uuid = json.path("uuid").takeIf { it.isTextual }?.asText()
        val status = json.path("status").takeIf { it.isTextual }?.asText()

        if (uuid != null && status != null) {
            runCatching { UUID.fromString(uuid) }.getOrNull()?.let { transactionId ->
                orderRepository.findByTransactionId(transactionId)?.let { order ->
                    orderSyncService.applyWebhookUpdate(order, status)
                }
            }
        }

        return ResponseEntity.ok(mapOf("ok" to true))
    }
}
