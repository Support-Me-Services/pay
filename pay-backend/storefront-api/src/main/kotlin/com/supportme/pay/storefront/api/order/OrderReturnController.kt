package com.supportme.pay.storefront.api.order

import com.supportme.pay.platform.tenant.TenantContext
import com.supportme.pay.storefront.api.common.PricePlnFormatter
import com.supportme.pay.storefront.api.config.PaymentBypassProperties
import com.supportme.pay.storefront.domain.entity.OrderStatus
import com.supportme.pay.storefront.domain.repository.OrderRepository
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.PathVariable
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController
import java.util.UUID
import kotlin.random.Random

data class OrderReturnResponse(
    val status: String,
    val amountPln: String? = null,
    val redirectUrl: String? = null,
    val pickupInstruction: String? = null,
    val standNumber: Int? = null,
    val showTether: Boolean = false,
)

/**
 * Odpowiednik `OrderReturnController` — `show`/`status` obie wołają
 * [OrderSyncService.syncFromGateway] (idempotentne), bo webhook Gateway->Shop
 * może jeszcze nie dotrzeć.
 */
@RestController
@RequestMapping("/zwrot")
class OrderReturnController(
    private val orderRepository: OrderRepository,
    private val orderSyncService: OrderSyncService,
    private val paymentBypass: PaymentBypassProperties,
) {

    @GetMapping("/{orderId}")
    fun show(@PathVariable orderId: UUID): ResponseEntity<Any> {
        // PayU podpięte, ale pomijamy weryfikację statusu — od razu podziękowanie.
        if (paymentBypass.returnBypass) {
            return ResponseEntity.ok(OrderReturnResponse(status = "paid", redirectUrl = "/main?dzieki=1"))
        }

        val found = orderRepository.findById(orderId).orElse(null) ?: return ResponseEntity.notFound().build()
        val order = orderSyncService.syncFromGateway(found)
        val isChurch = TenantContext.current().kind == "church"

        val body = when (order.status) {
            OrderStatus.PAID -> if (isChurch) {
                OrderReturnResponse(status = "paid", amountPln = PricePlnFormatter.format(order.amount), redirectUrl = "/main?dzieki=1")
            } else {
                val instruction = order.product?.pickupInstruction.orEmpty()
                val lowerInstruction = instruction.lowercase()
                OrderReturnResponse(
                    status = "paid",
                    amountPln = PricePlnFormatter.format(order.amount),
                    pickupInstruction = order.product?.pickupInstruction,
                    // Numer stanowiska TYLKO gdy instrukcja o nim wspomina — jak w PHP,
                    // NIE dla każdej płatności (np. instrukcje "linkowe" go nie mają).
                    standNumber = if (instruction.contains("stojaka nr") || lowerInstruction.contains("stanowis")) Random.nextInt(1, 7) else null,
                    showTether = lowerInstruction.contains("link"),
                )
            }
            OrderStatus.FAILED -> OrderReturnResponse(status = "failed", amountPln = PricePlnFormatter.format(order.amount))
            OrderStatus.PENDING -> OrderReturnResponse(status = "pending", amountPln = PricePlnFormatter.format(order.amount))
        }
        return ResponseEntity.ok(body)
    }

    @GetMapping("/{orderId}/status")
    fun status(@PathVariable orderId: UUID): ResponseEntity<Map<String, String>> {
        val found = orderRepository.findById(orderId).orElse(null) ?: return ResponseEntity.notFound().build()
        val order = orderSyncService.syncFromGateway(found)
        return ResponseEntity.ok(mapOf("status" to order.status.dbValue))
    }
}
