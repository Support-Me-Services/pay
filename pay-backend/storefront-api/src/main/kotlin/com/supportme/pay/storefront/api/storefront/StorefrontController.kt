package com.supportme.pay.storefront.api.storefront

import com.supportme.pay.platform.tenant.TenantContext
import com.supportme.pay.storefront.api.common.PricePlnFormatter
import com.supportme.pay.storefront.api.common.gatewayNotifyUrl
import com.supportme.pay.storefront.api.common.orderReturnUrl
import com.supportme.pay.storefront.api.gateway.GatewayClient
import com.supportme.pay.storefront.api.gateway.GatewayCreateTransactionRequest
import com.supportme.pay.storefront.domain.entity.CategorySource
import com.supportme.pay.storefront.domain.entity.Event
import com.supportme.pay.storefront.domain.entity.Order
import com.supportme.pay.storefront.domain.entity.Product
import com.supportme.pay.storefront.domain.entity.StorefrontEventType
import com.supportme.pay.storefront.domain.repository.CategoryRepository
import com.supportme.pay.storefront.domain.repository.OrderRepository
import com.supportme.pay.storefront.domain.repository.ProductRepository
import com.supportme.pay.storefront.domain.repository.StorefrontEventRepository
import jakarta.servlet.http.HttpServletRequest
import org.springframework.http.HttpStatus
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.PathVariable
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.RequestBody
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController

data class CategoryNode(val id: Long, val slug: String, val label: String, val labelHtml: String?, val icon: String?, val source: String)
data class ProductSummary(val slug: String, val name: String, val city: String?, val voivodeship: String?, val mainImage: String?, val pricePln: String)
data class ProductDetail(
    val slug: String,
    val name: String,
    val descriptionHtml: String?,
    val mainImage: String?,
    val images: List<String>,
    val pricePln: String,
    val presetAmountsPln: List<Int>,
    val isChurch: Boolean,
)
data class BuyRequest(val amountPln: Int? = null)
data class BuyResponse(val redirectUrl: String)

/** Port 1:1 z `App\Modules\Storefront\Http\Controllers\StorefrontController` (flow parafia/„Taca"). */
@RestController
class StorefrontController(
    private val categoryRepository: CategoryRepository,
    private val productRepository: ProductRepository,
    private val eventRepository: StorefrontEventRepository,
    private val orderRepository: OrderRepository,
    private val gatewayClient: GatewayClient,
) {

    @GetMapping("/main")
    fun index(): List<CategoryNode> = categoryRepository.findAllByParentIsNullAndActiveTrueOrderByPositionAscIdAsc()
        .map { CategoryNode(it.id!!, it.slug, it.label, it.labelHtml, it.icon, it.source.dbValue) }

    @GetMapping("/kategoria/{slug}")
    fun category(@PathVariable slug: String): ResponseEntity<Any> {
        val category = categoryRepository.findBySlug(slug) ?: return ResponseEntity.notFound().build()

        val products = if (category.source == CategorySource.PARISHES) {
            productRepository.findAllByActiveTrue().map { summarize(it) }
        } else {
            emptyList()
        }

        return ResponseEntity.ok(mapOf("category" to CategoryNode(category.id!!, category.slug, category.label, category.labelHtml, category.icon, category.source.dbValue), "products" to products))
    }

    @GetMapping("/t/{tagUid}")
    fun tag(@PathVariable tagUid: String): ResponseEntity<Any> {
        val product = productRepository.findByTagUid(tagUid)
        if (product != null) {
            eventRepository.save(Event(product = product, type = StorefrontEventType.TAG_OPEN))
            gatewayClient.sendEvent("tag_open", tagUid)
            return ResponseEntity.ok(mapOf("redirect" to "/p/${product.slug}"))
        }
        return ResponseEntity.status(HttpStatus.NOT_FOUND).body(mapOf("error" to "Nie znaleziono"))
    }

    @GetMapping("/p/{slug}")
    fun show(@PathVariable slug: String): ResponseEntity<ProductDetail> {
        val product = productRepository.findBySlug(slug) ?: return ResponseEntity.notFound().build()
        eventRepository.save(Event(product = product, type = StorefrontEventType.PAGE_VIEW))

        val isChurch = TenantContext.current().kind == "church"
        val defaultAmount = (product.price / 100).takeIf { it > 0 } ?: DEFAULT_AMOUNT_PLN

        return ResponseEntity.ok(
            ProductDetail(
                slug = product.slug,
                name = product.name,
                descriptionHtml = product.descriptionHtml,
                mainImage = product.mainImage,
                images = emptyList(),
                pricePln = pricePlnString(product.price),
                presetAmountsPln = if (isChurch) PRESET_AMOUNTS_PLN else listOf(defaultAmount),
                isChurch = isChurch,
            ),
        )
    }

    @PostMapping("/p/{slug}/kup")
    fun buy(@PathVariable slug: String, @RequestBody(required = false) body: BuyRequest?, request: HttpServletRequest): ResponseEntity<Any> {
        val product = productRepository.findBySlug(slug) ?: return ResponseEntity.notFound().build()
        eventRepository.save(Event(product = product, type = StorefrontEventType.BUY_CLICK))

        val isChurch = TenantContext.current().kind == "church"
        val amountGrosze = if (isChurch) {
            val amountPln = body?.amountPln ?: return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Podaj kwotę."))
            if (amountPln < MIN_DONATION_PLN || amountPln > MAX_DONATION_PLN) {
                return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Kwota musi być między $MIN_DONATION_PLN a $MAX_DONATION_PLN zł."))
            }
            amountPln * 100
        } else {
            product.price
        }

        val order = orderRepository.save(Order(product = product, amount = amountGrosze))

        val gatewayResponse = try {
            gatewayClient.createTransaction(
                GatewayCreateTransactionRequest(
                    productExternalId = product.id.toString(),
                    productName = product.name,
                    amount = amountGrosze,
                    returnUrl = orderReturnUrl(order.id!!),
                    notifyUrl = gatewayNotifyUrl(),
                    tagUid = product.tagUid,
                ),
            )
        } catch (e: Exception) {
            return ResponseEntity.status(HttpStatus.BAD_GATEWAY).body(mapOf("error" to "Płatność chwilowo niedostępna. Spróbuj ponownie."))
        }

        order.transactionId = java.util.UUID.fromString(gatewayResponse.uuid)
        orderRepository.save(order)

        return ResponseEntity.ok(BuyResponse(redirectUrl = gatewayResponse.paymentUrl))
    }

    private fun summarize(product: Product) = ProductSummary(product.slug, product.name, product.city, product.voivodeship, product.mainImage, pricePlnString(product.price))

    private fun pricePlnString(grosze: Int): String = PricePlnFormatter.format(grosze)

    companion object {
        private const val DEFAULT_AMOUNT_PLN = 20
        private val PRESET_AMOUNTS_PLN = listOf(10, 20, 50, 100, 200)
        private const val MIN_DONATION_PLN = 2
        private const val MAX_DONATION_PLN = 5000
    }
}
