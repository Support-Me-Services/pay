package com.supportme.pay.storefront.api.order

import com.supportme.pay.storefront.api.gateway.GatewayClient
import com.supportme.pay.storefront.domain.entity.Event
import com.supportme.pay.storefront.domain.entity.Order
import com.supportme.pay.storefront.domain.entity.OrderStatus
import com.supportme.pay.storefront.domain.entity.StorefrontEventType
import com.supportme.pay.storefront.domain.repository.OrderRepository
import com.supportme.pay.storefront.domain.repository.StorefrontEventRepository
import org.springframework.stereotype.Service
import java.time.Instant

/**
 * Logika WSPÓLNA dla `OrderReturnController::syncStatusFromGateway` i
 * `GatewayWebhookController::handle` — IDEMPOTENTNA (tylko `pending` z
 * ustawionym `transactionId`), bo webhook i aktywny polling mogą trafić
 * współbieżnie na to samo zamówienie.
 */
@Service
class OrderSyncService(
    private val orderRepository: OrderRepository,
    private val eventRepository: StorefrontEventRepository,
    private val gatewayClient: GatewayClient,
) {
    fun syncFromGateway(order: Order): Order {
        if (order.status != OrderStatus.PENDING || order.transactionId == null) return order

        val remote = gatewayClient.getTransaction(order.transactionId.toString()) ?: return order

        when (remote.status) {
            "paid" -> markPaid(order)
            "failed", "abandoned" -> markFailed(order)
            else -> Unit
        }
        return order
    }

    fun applyWebhookUpdate(order: Order, status: String) {
        if (order.status != OrderStatus.PENDING) return
        when (status) {
            "paid" -> markPaid(order)
            "failed", "abandoned" -> markFailed(order)
            else -> Unit
        }
    }

    private fun markPaid(order: Order) {
        order.status = OrderStatus.PAID
        order.paidAt = Instant.now()
        orderRepository.save(order)
        eventRepository.save(Event(product = order.product, type = StorefrontEventType.PURCHASE))
    }

    private fun markFailed(order: Order) {
        order.status = OrderStatus.FAILED
        orderRepository.save(order)
    }
}
