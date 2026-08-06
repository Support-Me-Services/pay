package com.supportme.pay.gateway.api.payment

import com.supportme.pay.gateway.api.config.GatewayUrlProperties
import com.supportme.pay.gateway.api.config.PaymentConfigProperties
import com.supportme.pay.gateway.domain.entity.EventType
import com.supportme.pay.gateway.domain.entity.PaymentMode
import com.supportme.pay.gateway.domain.entity.Transaction
import com.supportme.pay.gateway.domain.entity.TransactionStatus
import com.supportme.pay.gateway.domain.repository.TransactionRepository
import com.supportme.pay.gateway.payments.BankOption
import com.supportme.pay.gateway.payments.PayUProvider
import com.supportme.pay.gateway.payments.PaymentContext
import com.supportme.pay.gateway.payments.PaymentOrderRequest
import com.supportme.pay.gateway.payments.PaymentProvider
import jakarta.servlet.http.HttpServletRequest
import org.slf4j.LoggerFactory
import org.springframework.http.HttpStatus
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.PathVariable
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.RequestBody
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController
import java.util.UUID

data class TransactionSummary(val id: String, val productName: String, val amountPln: String, val returnUrl: String)

data class PaymentPageResponse(
    val status: String,
    val mode: String,
    val transaction: TransactionSummary,
    val banks: List<BankOption>? = null,
    val continueUrl: String? = null,
    val redirectUrl: String? = null,
)

data class BlikRequest(val code: String)
data class BankRequest(val method: String)
data class PaymentActionResponse(val status: String, val redirect: String? = null, val error: String? = null)
data class PaymentStatusResponse(val status: String, val redirect: String? = null)

/**
 * Port 1:1 (jako JSON, decyzja: redirect po stronie klienta) z
 * `Gateway\Http\Controllers\PaymentController`. Trasy klienta płatności —
 * `/pay/{uuid}` — rejestrowane RÓWNIEŻ na hostach Storefront (patrz plan),
 * dlatego kontroler żyje w `gateway-api` ale mapowany bez prefiksu panelu.
 */
@RestController
@RequestMapping("/pay")
class PaymentController(
    private val transactionRepository: TransactionRepository,
    private val transactionService: TransactionService,
    private val paymentProvider: PaymentProvider,
    private val paymentConfig: PaymentConfigProperties,
    private val gatewayUrlProperties: GatewayUrlProperties,
) {
    private val log = LoggerFactory.getLogger(PaymentController::class.java)

    @GetMapping("/{uuid}")
    fun show(@PathVariable uuid: UUID, request: HttpServletRequest): ResponseEntity<Any> {
        val transaction = transactionRepository.findById(uuid).orElse(null)
            ?: return ResponseEntity.notFound().build()

        if (transaction.status == TransactionStatus.PAID) {
            return ResponseEntity.ok(
                PaymentPageResponse(status = "paid", mode = transaction.mode.dbValue, transaction = summarize(transaction), redirectUrl = transaction.returnUrl),
            )
        }

        // Hostowany app2app: wybór banku (PBL) lub kod BLIK — zamówienie u operatora
        // powstaje dopiero po wyborze metody (/blik lub /bank), NIE tutaj.
        if (transaction.mode == PaymentMode.APP2APP && paymentProvider is PayUProvider) {
            val banks = try {
                paymentProvider.payByLinks()
            } catch (e: Exception) {
                log.warn("PayU: pobranie metod PBL nieudane: {}", e.message)
                emptyList()
            }

            return ResponseEntity.ok(
                PaymentPageResponse(
                    status = transaction.status.dbValue,
                    mode = transaction.mode.dbValue,
                    transaction = summarize(transaction),
                    banks = banks,
                    continueUrl = if (transaction.status == TransactionStatus.PENDING) transaction.providerRedirectUrl else null,
                ),
            )
        }

        if (transaction.status == TransactionStatus.CREATED) {
            try {
                val result = paymentProvider.createTransaction(toOrderRequest(transaction), request.remoteAddr, PaymentContext.Classic)
                transaction.status = TransactionStatus.PENDING
                transaction.providerOrderId = result.providerOrderId
                transaction.providerRedirectUrl = result.redirectUrl
                transactionRepository.save(transaction)
                transactionService.logEvent(transaction, EventType.PAYMENT_STARTED)
            } catch (e: Exception) {
                log.error("Utworzenie płatności u operatora nieudane dla {}: {}", transaction.id, e.message)
                return ResponseEntity.status(HttpStatus.BAD_GATEWAY).body(
                    PaymentPageResponse(status = "error", mode = transaction.mode.dbValue, transaction = summarize(transaction)),
                )
            }
        }

        // classic -> redirectUrl do nawigacji klienta wprost na stronę operatora;
        // app2app -> redirectUrl do ekranu przejściowego (trwa nawiązywanie z bankiem).
        return ResponseEntity.ok(
            PaymentPageResponse(
                status = transaction.status.dbValue,
                mode = transaction.mode.dbValue,
                transaction = summarize(transaction),
                redirectUrl = transaction.providerRedirectUrl,
            ),
        )
    }

    @PostMapping("/{uuid}/blik")
    fun blik(@PathVariable uuid: UUID, @RequestBody body: BlikRequest, request: HttpServletRequest): ResponseEntity<PaymentActionResponse> {
        val transaction = transactionRepository.findById(uuid).orElse(null)
            ?: return ResponseEntity.notFound().build()

        if (transaction.isFinal()) {
            return ResponseEntity.ok(PaymentActionResponse(status = transaction.status.dbValue, redirect = "/pay/${transaction.id}/return"))
        }
        if (!body.code.matches(Regex("^\\d{6}$"))) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(PaymentActionResponse(status = "error", error = "Kod BLIK to 6 cyfr."))
        }

        val wasCreated = transaction.status == TransactionStatus.CREATED
        try {
            val result = paymentProvider.createTransaction(toOrderRequest(transaction), request.remoteAddr, PaymentContext.BlikCode(body.code))
            if (wasCreated) transactionService.logEvent(transaction, EventType.PAYMENT_STARTED)
            transaction.status = TransactionStatus.PENDING
            transaction.providerOrderId = result.providerOrderId
            transaction.providerRedirectUrl = result.redirectUrl
            transactionRepository.save(transaction)
        } catch (e: Exception) {
            log.warn("BLIK Level 0: autoryzacja nieudana dla {}: {}", transaction.id, e.message)
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(
                PaymentActionResponse(status = "error", error = "Płatność nie została zautoryzowana. Sprawdź kod i spróbuj ponownie."),
            )
        }

        return ResponseEntity.ok(PaymentActionResponse(status = "pending"))
    }

    @PostMapping("/{uuid}/bank")
    fun bank(@PathVariable uuid: UUID, @RequestBody body: BankRequest, request: HttpServletRequest): ResponseEntity<PaymentActionResponse> {
        val transaction = transactionRepository.findById(uuid).orElse(null)
            ?: return ResponseEntity.notFound().build()

        if (transaction.isFinal()) {
            return ResponseEntity.ok(PaymentActionResponse(status = transaction.status.dbValue, redirect = "/pay/${transaction.id}/return"))
        }
        if (transaction.status == TransactionStatus.PENDING && transaction.providerRedirectUrl != null) {
            return ResponseEntity.ok(PaymentActionResponse(status = "pending", redirect = transaction.providerRedirectUrl))
        }

        val allowed = if (paymentProvider is PayUProvider) paymentProvider.payByLinks().map { it.value } else emptyList()
        if (body.method !in allowed) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(PaymentActionResponse(status = "error", error = "Wybierz bank z listy."))
        }

        val wasCreated = transaction.status == TransactionStatus.CREATED
        try {
            val result = paymentProvider.createTransaction(toOrderRequest(transaction), request.remoteAddr, PaymentContext.Pbl(body.method))
            if (wasCreated) transactionService.logEvent(transaction, EventType.PAYMENT_STARTED)
            transaction.status = TransactionStatus.PENDING
            transaction.providerOrderId = result.providerOrderId
            transaction.providerRedirectUrl = result.redirectUrl
            transactionRepository.save(transaction)
        } catch (e: Exception) {
            log.error("PBL: utworzenie płatności nieudane dla {}: {}", transaction.id, e.message)
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(
                PaymentActionResponse(status = "error", error = "Nie udało się rozpocząć płatności. Spróbuj ponownie."),
            )
        }

        return ResponseEntity.ok(PaymentActionResponse(status = "pending", redirect = transaction.providerRedirectUrl))
    }

    @PostMapping("/{uuid}/online")
    fun online(@PathVariable uuid: UUID, request: HttpServletRequest): ResponseEntity<PaymentActionResponse> {
        val transaction = transactionRepository.findById(uuid).orElse(null)
            ?: return ResponseEntity.notFound().build()

        if (transaction.isFinal()) {
            return ResponseEntity.ok(PaymentActionResponse(status = transaction.status.dbValue, redirect = "/pay/${transaction.id}/return"))
        }
        if (transaction.status == TransactionStatus.PENDING && transaction.providerRedirectUrl != null) {
            return ResponseEntity.ok(PaymentActionResponse(status = "pending", redirect = transaction.providerRedirectUrl))
        }

        val wasCreated = transaction.status == TransactionStatus.CREATED
        try {
            val result = paymentProvider.createTransaction(toOrderRequest(transaction), request.remoteAddr, PaymentContext.Classic)
            if (wasCreated) transactionService.logEvent(transaction, EventType.PAYMENT_STARTED)
            transaction.status = TransactionStatus.PENDING
            transaction.providerOrderId = result.providerOrderId
            transaction.providerRedirectUrl = result.redirectUrl
            transactionRepository.save(transaction)
        } catch (e: Exception) {
            log.error("Płatność online: utworzenie nieudane dla {}: {}", transaction.id, e.message)
            return ResponseEntity.status(HttpStatus.BAD_GATEWAY).body(PaymentActionResponse(status = "error"))
        }

        return ResponseEntity.ok(PaymentActionResponse(status = "pending", redirect = transaction.providerRedirectUrl))
    }

    @GetMapping("/{uuid}/status")
    fun status(@PathVariable uuid: UUID): ResponseEntity<PaymentStatusResponse> {
        val transaction = transactionRepository.findById(uuid).orElse(null)
            ?: return ResponseEntity.notFound().build()

        transactionService.reconcileWithProvider(transaction)

        return ResponseEntity.ok(
            PaymentStatusResponse(
                status = transaction.status.dbValue,
                redirect = if (transaction.isFinal()) "/pay/${transaction.id}/return" else null,
            ),
        )
    }

    @GetMapping("/{uuid}/return")
    fun returnToShop(@PathVariable uuid: UUID): ResponseEntity<Map<String, String>> {
        val transaction = transactionRepository.findById(uuid).orElse(null)
            ?: return ResponseEntity.notFound().build()

        // Sklep i tak weryfikuje status przez własne API — to tylko wskazanie dokąd wrócić.
        return ResponseEntity.ok(mapOf("redirectUrl" to transaction.returnUrl))
    }

    /**
     * `notifyUrl` to zawsze webhook BRAMKI (`route('webhooks.payu')` w PHP —
     * PayU woła TEN adres, niezależnie od `transaction.notifyUrl`, który jest
     * OSOBNYM konceptem: callbackiem sklepu-klienta, używanym dalej przez
     * `TransactionService.notifyShop`, nie tutaj).
     */
    private fun toOrderRequest(transaction: Transaction) = PaymentOrderRequest(
        transactionId = transaction.id.toString(),
        productName = transaction.productName,
        amountGrosze = transaction.amount,
        currency = transaction.currency,
        returnUrl = transaction.returnUrl,
        notifyUrl = "${gatewayUrlProperties.publicBaseUrl}/webhooks/payu",
    )

    private fun summarize(transaction: Transaction) = TransactionSummary(
        id = transaction.id.toString(),
        productName = transaction.productName,
        amountPln = formatPln(transaction.amount),
        returnUrl = transaction.returnUrl,
    )

    /**
     * Jak `number_format($x/100, 2, ',', ' ')` w PHP — spacja jako separator
     * tysięcy, przecinek jako dziesiętny. Jawne `DecimalFormatSymbols` (nie
     * poleganie na domyślnym separatorze locale `pl_PL`, który w Javie bywa
     * NBSP zamiast zwykłej spacji) dla bit-for-bit zgodności.
     */
    private fun formatPln(grosze: Int): String = PLN_FORMAT.format(grosze / 100.0)

    companion object {
        private val PLN_FORMAT = java.text.DecimalFormat(
            "#,##0.00",
            java.text.DecimalFormatSymbols(java.util.Locale.of("pl", "PL")).apply {
                groupingSeparator = ' '
                decimalSeparator = ','
            },
        )
    }
}
