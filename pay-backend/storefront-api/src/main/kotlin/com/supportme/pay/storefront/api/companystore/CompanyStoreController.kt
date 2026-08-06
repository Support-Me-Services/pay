package com.supportme.pay.storefront.api.companystore

import com.supportme.pay.storefront.api.common.PricePlnFormatter
import com.supportme.pay.storefront.api.common.gatewayNotifyUrl
import com.supportme.pay.storefront.api.common.orderReturnUrl
import com.supportme.pay.storefront.api.config.PaymentBypassProperties
import com.supportme.pay.storefront.api.config.StorefrontShopProperties
import com.supportme.pay.storefront.api.gateway.GatewayClient
import com.supportme.pay.storefront.api.gateway.GatewayCreateTransactionRequest
import com.supportme.pay.storefront.domain.entity.Order
import com.supportme.pay.storefront.domain.repository.OrderRepository
import com.supportme.pay.storefront.domain.repository.ShopItemRepository
import com.supportme.pay.storefront.domain.repository.UserRepository
import jakarta.servlet.http.HttpServletRequest
import org.springframework.http.HttpStatus
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.PathVariable
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.RequestBody
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController
import kotlin.math.ceil

data class ShopItemSummary(val slug: String, val name: String, val image: String?, val isSvg: Boolean, val minAmountPln: Int, val pricePln: String, val isDefault: Boolean, val description: String?)
data class CompanyStorePurchaseRequest(val amountPln: Int)
data class PurchaseResponse(val redirectUrl: String? = null, val bypassed: Boolean = false)

/** Port 1:1 z `App\Modules\Storefront\Http\Controllers\CompanyStoreController` — sklep firmowy (`/`). */
@RestController
class CompanyStoreController(
    private val userRepository: UserRepository,
    private val shopItemRepository: ShopItemRepository,
    private val orderRepository: OrderRepository,
    private val gatewayClient: GatewayClient,
    private val shopConfig: StorefrontShopProperties,
    private val paymentBypass: PaymentBypassProperties,
) {

    @GetMapping("/")
    fun index(): ResponseEntity<List<ShopItemSummary>> {
        val owner = owner() ?: return ResponseEntity.ok(emptyList())
        val items = shopItemRepository.findAllByOwnerOrderBySortAscIdAsc(owner).filter { it.active }
            .map { ShopItemSummary(it.slug, it.name, it.image, it.isSvg(), it.minAmountPln(), PricePlnFormatter.format(it.priceGrosze()), it.isDefault, it.description) }
        return ResponseEntity.ok(items)
    }

    @PostMapping("/sklep/kup/{slug}")
    fun purchase(@PathVariable slug: String, @RequestBody body: CompanyStorePurchaseRequest, request: HttpServletRequest): ResponseEntity<Any> {
        // Póki PayU nie zatwierdził sklepu: pomijamy płatność — SPRAWDZANE JAKO
        // PIERWSZE, przed jakimkolwiek lookupem/zapisem (jak w PHP), więc bypass
        // NIE tworzy sierocych wierszy Order.
        if (paymentBypass.bypass) {
            return ResponseEntity.ok(PurchaseResponse(redirectUrl = "/?dzieki=1", bypassed = true))
        }

        val owner = owner()
        val item = (if (owner != null) shopItemRepository.findAllByOwnerOrderBySortAscIdAsc(owner) else emptyList())
            .firstOrNull { it.slug == slug && it.active }
            ?: return ResponseEntity.notFound().build()

        val minPln = maxOf(1, ceil(item.minAmount / 100.0).toInt())
        if (body.amountPln < minPln || body.amountPln > MAX_AMOUNT_PLN) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Kwota musi być co najmniej $minPln zł."))
        }

        val amountGrosze = body.amountPln * 100
        val order = orderRepository.save(Order(product = null, amount = amountGrosze))

        val gatewayResponse = try {
            gatewayClient.createTransaction(
                GatewayCreateTransactionRequest(
                    productExternalId = "shop-${item.slug}-${order.id}",
                    productName = "Wsparcie: ${item.name}",
                    amount = amountGrosze,
                    returnUrl = orderReturnUrl(order.id!!),
                    notifyUrl = gatewayNotifyUrl(),
                    tagUid = item.tagUid,
                ),
            )
        } catch (e: Exception) {
            return ResponseEntity.status(HttpStatus.BAD_GATEWAY).body(mapOf("error" to "Płatność chwilowo niedostępna. Spróbuj ponownie."))
        }

        order.transactionId = java.util.UUID.fromString(gatewayResponse.uuid)
        orderRepository.save(order)

        return ResponseEntity.ok(PurchaseResponse(redirectUrl = gatewayResponse.paymentUrl))
    }

    /** Konto główne (właściciel produktów widocznych na „/") — fallback na pierwszego usera, jak w PHP. */
    private fun owner() = userRepository.findByHandle(shopConfig.mainAccountHandle) ?: userRepository.findFirstByOrderById()

    companion object {
        private const val MAX_AMOUNT_PLN = 5000
    }
}
