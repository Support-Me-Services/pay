package com.supportme.pay.storefront.api.usershop

import com.supportme.pay.storefront.api.common.PricePlnFormatter
import com.supportme.pay.storefront.api.common.gatewayNotifyUrl
import com.supportme.pay.storefront.api.common.orderReturnUrl
import com.supportme.pay.storefront.api.config.PaymentBypassProperties
import com.supportme.pay.storefront.api.gateway.GatewayClient
import com.supportme.pay.storefront.api.gateway.GatewayCreateTransactionRequest
import com.supportme.pay.storefront.domain.entity.Order
import com.supportme.pay.storefront.domain.entity.ShopItem
import com.supportme.pay.storefront.domain.repository.OrderRepository
import com.supportme.pay.storefront.domain.repository.ShopItemRepository
import com.supportme.pay.storefront.domain.repository.UserRepository
import jakarta.servlet.http.HttpServletRequest
import jakarta.servlet.http.HttpSession
import org.springframework.http.HttpStatus
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.PathVariable
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.RequestBody
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController

data class CartLine(val itemId: Long, val name: String, val qty: Int, val unitPricePln: String, val lineTotalPln: String)
data class CartResponse(val lines: List<CartLine>, val subtotalPln: String, val shipCostPln: String, val totalPln: String, val shippingMethod: String?, val shippingPoint: String?)
data class SetShippingRequest(val method: String, val point: String? = null)
data class CartCheckoutResponse(val redirectUrl: String? = null, val bypassed: Boolean = false)

/**
 * Odpowiednik `CartController` — koszyk SESYJNY per sklep (`cart.{handle}`),
 * ceny zawsze re-resolvowane z DB przy checkout (NIGDY nie ufamy cenom
 * z klienta), jak w oryginale.
 */
@RestController
@RequestMapping("/people/{handle}/koszyk")
class CartController(
    private val userRepository: UserRepository,
    private val shopItemRepository: ShopItemRepository,
    private val orderRepository: OrderRepository,
    private val gatewayClient: GatewayClient,
    private val paymentBypass: PaymentBypassProperties,
) {

    @GetMapping
    fun show(@PathVariable handle: String, request: HttpServletRequest): ResponseEntity<CartResponse> {
        val owner = userRepository.findByHandle(handle) ?: return ResponseEntity.notFound().build()
        return ResponseEntity.ok(buildCartResponse(request.getSession(true), handle, owner.id!!))
    }

    @PostMapping("/dodaj/{itemId}")
    fun add(@PathVariable handle: String, @PathVariable itemId: Long, request: HttpServletRequest): ResponseEntity<CartResponse> {
        val owner = userRepository.findByHandle(handle) ?: return ResponseEntity.notFound().build()
        val cart = cartMap(request.getSession(true), handle)
        cart[itemId] = ((cart[itemId] ?: 0) + 1).coerceIn(1, MAX_QTY)
        return ResponseEntity.ok(buildCartResponse(request.getSession(true), handle, owner.id!!))
    }

    @PostMapping("/aktualizuj/{itemId}")
    fun update(@PathVariable handle: String, @PathVariable itemId: Long, @RequestBody body: Map<String, Int>, request: HttpServletRequest): ResponseEntity<CartResponse> {
        val owner = userRepository.findByHandle(handle) ?: return ResponseEntity.notFound().build()
        val qty = (body["qty"] ?: 1).coerceIn(1, MAX_QTY)
        val cart = cartMap(request.getSession(true), handle)
        cart[itemId] = qty
        return ResponseEntity.ok(buildCartResponse(request.getSession(true), handle, owner.id!!))
    }

    @PostMapping("/usun/{itemId}")
    fun remove(@PathVariable handle: String, @PathVariable itemId: Long, request: HttpServletRequest): ResponseEntity<CartResponse> {
        val owner = userRepository.findByHandle(handle) ?: return ResponseEntity.notFound().build()
        cartMap(request.getSession(true), handle).remove(itemId)
        return ResponseEntity.ok(buildCartResponse(request.getSession(true), handle, owner.id!!))
    }

    @PostMapping("/dostawa")
    fun setShipping(@PathVariable handle: String, @RequestBody body: SetShippingRequest, request: HttpServletRequest): ResponseEntity<Any> {
        val method = ShippingMethods.find(body.method)
        if (method == null || !method.enabled) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Wybierz dostępną metodę dostawy."))
        }
        if (method.requiresPoint && body.point.isNullOrBlank()) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Wybierz punkt odbioru."))
        }

        val session = request.getSession(true)
        session.setAttribute("ship.$handle", method.code)
        session.setAttribute("ship_point.$handle", body.point)
        return ResponseEntity.ok(mapOf("status" to "ok"))
    }

    @PostMapping("/kup")
    fun checkout(@PathVariable handle: String, request: HttpServletRequest): ResponseEntity<Any> {
        val owner = userRepository.findByHandle(handle) ?: return ResponseEntity.notFound().build()
        val session = request.getSession(true)
        val cart = cartMap(session, handle)
        if (cart.isEmpty()) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Koszyk jest pusty."))
        }

        val shippingCode = session.getAttribute("ship.$handle") as? String ?: "pickup"
        val shippingMethod = ShippingMethods.find(shippingCode) ?: ShippingMethods.find("pickup")!!
        val shippingPoint = session.getAttribute("ship_point.$handle") as? String
        if (shippingMethod.requiresPoint && shippingPoint.isNullOrBlank()) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Wybierz punkt odbioru."))
        }

        // Re-resolve cen z DB — NIGDY nie ufamy cenom z klienta.
        val items = resolveItems(owner.id!!, cart)
        val subtotal = items.sumOf { (item, qty) -> item.priceGrosze() * qty }
        val total = subtotal + shippingMethod.priceGrosze

        val description = items.joinToString(", ") { (item, qty) -> "${item.name} x$qty" } + " (${shippingMethod.label})"

        if (paymentBypass.bypass) {
            cart.clear()
            return ResponseEntity.ok(CartCheckoutResponse(redirectUrl = "/people/$handle?dzieki=1", bypassed = true))
        }

        val order = orderRepository.save(Order(product = null, amount = total))

        val gatewayResponse = try {
            gatewayClient.createTransaction(
                GatewayCreateTransactionRequest(
                    productExternalId = "cart-$handle-${order.id}",
                    productName = description,
                    amount = total,
                    returnUrl = orderReturnUrl(order.id!!),
                    notifyUrl = gatewayNotifyUrl(),
                ),
            )
        } catch (e: Exception) {
            return ResponseEntity.status(HttpStatus.BAD_GATEWAY).body(mapOf("error" to "Płatność chwilowo niedostępna. Spróbuj ponownie."))
        }

        order.transactionId = java.util.UUID.fromString(gatewayResponse.uuid)
        orderRepository.save(order)
        cart.clear()

        return ResponseEntity.ok(CartCheckoutResponse(redirectUrl = gatewayResponse.paymentUrl))
    }

    @Suppress("UNCHECKED_CAST")
    private fun cartMap(session: HttpSession, handle: String): MutableMap<Long, Int> {
        val key = "cart.$handle"
        var map = session.getAttribute(key) as? MutableMap<Long, Int>
        if (map == null) {
            map = mutableMapOf()
            session.setAttribute(key, map)
        }
        return map
    }

    private fun resolveItems(ownerId: Long, cart: Map<Long, Int>): List<Pair<ShopItem, Int>> =
        shopItemRepository.findAllById(cart.keys).filter { it.owner?.id == ownerId }.map { it to (cart[it.id] ?: 0) }

    private fun buildCartResponse(session: HttpSession, handle: String, ownerId: Long): CartResponse {
        val cart = cartMap(session, handle)
        val items = resolveItems(ownerId, cart)
        val lines = items.map { (item, qty) ->
            CartLine(item.id!!, item.name, qty, PricePlnFormatter.format(item.priceGrosze()), PricePlnFormatter.format(item.priceGrosze() * qty))
        }
        val subtotal = items.sumOf { (item, qty) -> item.priceGrosze() * qty }
        val shippingCode = session.getAttribute("ship.$handle") as? String ?: "pickup"
        val shippingMethod = ShippingMethods.find(shippingCode) ?: ShippingMethods.find("pickup")!!
        val shippingPoint = session.getAttribute("ship_point.$handle") as? String

        return CartResponse(
            lines = lines,
            subtotalPln = PricePlnFormatter.format(subtotal),
            shipCostPln = PricePlnFormatter.format(shippingMethod.priceGrosze),
            totalPln = PricePlnFormatter.format(subtotal + shippingMethod.priceGrosze),
            shippingMethod = shippingMethod.code,
            shippingPoint = shippingPoint,
        )
    }

    companion object {
        private const val MAX_QTY = 99
    }
}
