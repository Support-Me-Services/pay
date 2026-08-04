package com.supportme.pay.gateway.api.clientapi

import com.supportme.pay.gateway.api.payment.TransactionService
import com.supportme.pay.gateway.domain.entity.Shop
import com.supportme.pay.gateway.domain.entity.Tag
import com.supportme.pay.gateway.domain.entity.Transaction
import com.supportme.pay.gateway.domain.repository.TagRepository
import com.supportme.pay.gateway.domain.repository.TransactionRepository
import jakarta.validation.Valid
import jakarta.validation.constraints.Min
import jakarta.validation.constraints.NotBlank
import org.springframework.http.HttpStatus
import org.springframework.http.ResponseEntity
import org.springframework.security.core.annotation.AuthenticationPrincipal
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.PathVariable
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.RequestBody
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController
import java.time.format.DateTimeFormatter
import java.util.UUID

data class CreateTransactionRequest(
    @field:NotBlank val productExternalId: String,
    @field:NotBlank val productName: String,
    @field:Min(1) val amount: Int,
    val currency: String? = null,
    @field:NotBlank val returnUrl: String,
    val notifyUrl: String? = null,
    val tagUid: String? = null,
)

data class CreateTransactionResponse(val uuid: String, val paymentUrl: String)

data class TransactionStatusResponse(
    val uuid: String,
    val status: String,
    val amount: Int,
    val currency: String,
    val mode: String,
    val paidAt: String?,
    val createdAt: String?,
)

/**
 * Odpowiednik `Api\TransactionController` — REST client-facing (`/api/gateway/v1/transactions`),
 * uwierzytelnione `X-Api-Key` (patrz [com.supportme.pay.gateway.api.security.ShopApiKeySecurityConfig]).
 */
@RestController
@RequestMapping("/api/gateway/v1/transactions")
class TransactionController(
    private val transactionRepository: TransactionRepository,
    private val tagRepository: TagRepository,
    private val transactionService: TransactionService,
) {

    @PostMapping
    fun store(
        @AuthenticationPrincipal shop: Shop,
        @Valid @RequestBody body: CreateTransactionRequest,
    ): ResponseEntity<CreateTransactionResponse> {
        if (body.currency != null && body.currency != "PLN") {
            return ResponseEntity.badRequest().build()
        }

        // Lookup tagu SCOPED do sklepu — po cichu ignorowany jeśli nie istnieje (jak w oryginale).
        val tag: Tag? = body.tagUid?.let { tagRepository.findByTagUidAndShop(it, shop) }

        val transaction = Transaction(
            shop = shop,
            tag = tag,
            productExternalId = body.productExternalId,
            productName = body.productName,
            amount = body.amount,
            currency = body.currency ?: "PLN",
            // mode dziedziczony ze Shop.paymentMode w momencie utworzenia — NIE wybieralny przez klienta API.
            mode = shop.paymentMode,
            returnUrl = body.returnUrl,
            notifyUrl = body.notifyUrl,
        )
        val saved = transactionRepository.save(transaction)

        return ResponseEntity.status(HttpStatus.CREATED).body(
            CreateTransactionResponse(uuid = saved.id.toString(), paymentUrl = "/pay/${saved.id}"),
        )
    }

    @GetMapping("/{uuid}")
    fun show(@AuthenticationPrincipal shop: Shop, @PathVariable uuid: UUID): ResponseEntity<Any> {
        val transaction = transactionRepository.findByIdAndShop(uuid, shop)
            ?: return ResponseEntity.status(HttpStatus.NOT_FOUND).body(mapOf("error" to "Transakcja nie istnieje"))

        // Efekt uboczny: polling tego endpointu wyzwala aktywną rekoncyliację (throttlowaną 3s).
        transactionService.reconcileWithProvider(transaction)

        return ResponseEntity.ok(
            TransactionStatusResponse(
                uuid = transaction.id.toString(),
                status = transaction.status.dbValue,
                amount = transaction.amount,
                currency = transaction.currency,
                mode = transaction.mode.dbValue,
                paidAt = transaction.paidAt?.let { DateTimeFormatter.ISO_INSTANT.format(it) },
                createdAt = transaction.createdAt?.let { DateTimeFormatter.ISO_INSTANT.format(it) },
            ),
        )
    }
}
