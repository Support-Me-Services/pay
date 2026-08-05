package com.supportme.pay.storefront.api.usershop

import com.supportme.pay.storefront.api.common.PricePlnFormatter
import com.supportme.pay.storefront.domain.repository.ShopItemRepository
import com.supportme.pay.storefront.domain.repository.UserRepository
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.PathVariable
import org.springframework.web.bind.annotation.RestController

data class UserShopItem(val id: Long, val slug: String, val name: String, val image: String?, val isSvg: Boolean, val pricePln: String)

/** Odpowiednik `UserShopController::index` — sklep z koszykiem per-konto (`/people/{handle}`). */
@RestController
class UserShopController(
    private val userRepository: UserRepository,
    private val shopItemRepository: ShopItemRepository,
) {

    @GetMapping("/people/{handle}")
    fun index(@PathVariable handle: String): ResponseEntity<List<UserShopItem>> {
        val owner = userRepository.findByHandle(handle) ?: return ResponseEntity.notFound().build()
        val items = shopItemRepository.findAllByOwnerOrderBySortAscIdAsc(owner).filter { it.active }
            .map { UserShopItem(it.id!!, it.slug, it.name, it.image, it.isSvg(), PricePlnFormatter.format(it.priceGrosze())) }
        return ResponseEntity.ok(items)
    }
}
