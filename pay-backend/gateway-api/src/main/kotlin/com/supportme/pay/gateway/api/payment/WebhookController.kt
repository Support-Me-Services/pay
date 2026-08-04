package com.supportme.pay.gateway.api.payment

import com.supportme.pay.gateway.domain.repository.TransactionRepository
import com.supportme.pay.gateway.payments.PaymentProvider
import com.supportme.pay.gateway.payments.WebhookResult
import jakarta.servlet.http.HttpServletRequest
import org.springframework.http.HttpStatus
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController
import java.util.UUID

/**
 * Odpowiednik `WebhookController::payu` — notyfikacje PayU. Po poprawnej
 * weryfikacji podpisu ZAWSZE 200 (inaczej PayU ponawia w nieskończoność),
 * nawet gdy transakcja nie zostanie znaleziona.
 */
@RestController
@RequestMapping("/webhooks")
class WebhookController(
    private val paymentProvider: PaymentProvider,
    private val transactionRepository: TransactionRepository,
    private val transactionService: TransactionService,
) {

    @PostMapping("/payu")
    fun payu(request: HttpServletRequest): ResponseEntity<Map<String, Any>> {
        val rawBody = request.reader.readText()
        val signatureHeader = request.getHeader("OpenPayu-Signature")

        val result = paymentProvider.handleWebhook(rawBody, signatureHeader)
        if (!result.valid) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).body(mapOf("error" to "Niepoprawny podpis"))
        }

        result.transactionId?.let { txId ->
            runCatching { UUID.fromString(txId) }.getOrNull()?.let { uuid ->
                transactionRepository.findById(uuid).ifPresent { transaction ->
                    when (result.status) {
                        WebhookResult.Status.PAID -> transactionService.markPaid(transaction)
                        WebhookResult.Status.FAILED -> transactionService.markFailed(transaction)
                        WebhookResult.Status.IGNORED -> Unit
                    }
                }
            }
        }

        return ResponseEntity.ok(mapOf("ok" to true))
    }
}
