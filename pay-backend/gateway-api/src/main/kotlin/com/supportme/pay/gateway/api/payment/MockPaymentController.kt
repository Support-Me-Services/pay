package com.supportme.pay.gateway.api.payment

import com.supportme.pay.gateway.domain.repository.TransactionRepository
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.PathVariable
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController
import java.util.UUID

data class MockPayResponse(
    /** "final" = transakcja już rozstrzygnięta, klient powinien iść na `redirectUrl`; "open" = pokaż UI mocka. */
    val status: String,
    val mode: String? = null,
    val redirectUrl: String? = null,
    val productName: String? = null,
    val amountPln: String? = null,
    val confirmUrl: String? = null,
    val failUrl: String? = null,
)
data class MockActionResponse(val redirect: String)

/**
 * Symulator dev/demo (`MockProvider`) — odpowiednik `MockPaymentController`.
 * Rozliczenie idzie WPROST przez `TransactionService.markPaid/markFailed`,
 * BEZ webhooka/podpisu (to nie jest ścieżka produkcyjna).
 */
@RestController
@RequestMapping("/mockpay")
class MockPaymentController(
    private val transactionRepository: TransactionRepository,
    private val transactionService: TransactionService,
) {

    @GetMapping("/{uuid}")
    fun show(@PathVariable uuid: UUID): ResponseEntity<MockPayResponse> {
        val transaction = transactionRepository.findById(uuid).orElse(null) ?: return ResponseEntity.notFound().build()

        if (transaction.isFinal()) {
            return ResponseEntity.ok(MockPayResponse(status = "final", redirectUrl = transaction.returnUrl))
        }

        return ResponseEntity.ok(
            MockPayResponse(
                status = "open",
                mode = transaction.mode.dbValue,
                productName = transaction.productName,
                amountPln = formatPln(transaction.amount),
                confirmUrl = "/mockpay/$uuid/confirm",
                failUrl = "/mockpay/$uuid/fail",
            ),
        )
    }

    private fun formatPln(grosze: Int): String = PLN_FORMAT.format(grosze / 100.0)

    @PostMapping("/{uuid}/confirm")
    fun confirm(@PathVariable uuid: UUID): ResponseEntity<MockActionResponse> {
        val transaction = transactionRepository.findById(uuid).orElse(null) ?: return ResponseEntity.notFound().build()
        transactionService.markPaid(transaction)
        return ResponseEntity.ok(MockActionResponse(redirect = "/pay/$uuid/return"))
    }

    @PostMapping("/{uuid}/fail")
    fun fail(@PathVariable uuid: UUID): ResponseEntity<MockActionResponse> {
        val transaction = transactionRepository.findById(uuid).orElse(null) ?: return ResponseEntity.notFound().build()
        transactionService.markFailed(transaction)
        return ResponseEntity.ok(MockActionResponse(redirect = "/pay/$uuid/return"))
    }

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
