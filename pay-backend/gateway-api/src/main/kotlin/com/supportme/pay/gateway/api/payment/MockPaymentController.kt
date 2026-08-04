package com.supportme.pay.gateway.api.payment

import com.supportme.pay.gateway.domain.repository.TransactionRepository
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.PathVariable
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController
import java.util.UUID

data class MockPayResponse(val mode: String, val redirectUrl: String)
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
        return ResponseEntity.ok(MockPayResponse(mode = transaction.mode.dbValue, redirectUrl = "/pay/$uuid/return"))
    }

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
}
