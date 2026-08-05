package com.supportme.pay.storefront.api.panel.shopitem

import com.supportme.pay.storefront.api.storage.FileStorageService
import com.supportme.pay.storefront.domain.auth.PanelAuthService
import com.supportme.pay.storefront.domain.entity.ShopItem
import com.supportme.pay.storefront.domain.repository.ShopItemRepository
import com.supportme.pay.storefront.domain.repository.UserRepository
import org.springframework.http.HttpStatus
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.DeleteMapping
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.PathVariable
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.PutMapping
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RequestParam
import org.springframework.web.bind.annotation.RestController
import org.springframework.web.multipart.MultipartFile

data class ShopItemPanelSummary(
    val id: Long, val name: String, val slug: String, val description: String?, val pricePln: Int, val minAmountPln: Int,
    val tagUid: String?, val sort: Int, val isDefault: Boolean, val active: Boolean, val image: String?,
)

/**
 * Odpowiednik `Panel\ShopItemController` — JEDYNY kontroler panelu Storefront
 * scoped przez `Auth::id()` zamiast przez host/bazę (sklepy per-konto).
 */
@RestController
@RequestMapping("/api/storefront/panel/shop-items")
class ShopItemController(
    private val userRepository: UserRepository,
    private val shopItemRepository: ShopItemRepository,
    private val panelAuthService: PanelAuthService,
    private val fileStorageService: FileStorageService,
) {

    private fun currentUserId(): Long? = panelAuthService.currentPrincipal()?.user?.id

    @GetMapping
    fun index(): ResponseEntity<List<ShopItemPanelSummary>> {
        val userId = currentUserId() ?: return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build()
        val owner = userRepository.findById(userId).orElseThrow()
        val items = shopItemRepository.findAllByOwnerOrderBySortAscIdAsc(owner).map(::summarize)
        return ResponseEntity.ok(items)
    }

    @PostMapping
    fun store(
        @RequestParam name: String,
        @RequestParam(required = false) slug: String?,
        @RequestParam pricePln: Int,
        @RequestParam(required = false) description: String?,
        @RequestParam(required = false) tagUid: String?,
        @RequestParam(required = false, defaultValue = "0") sort: Int,
        @RequestParam(required = false, defaultValue = "true") active: Boolean,
        @RequestParam(required = false, defaultValue = "false") isDefault: Boolean,
        @RequestParam(required = false) imageFile: MultipartFile?,
    ): ResponseEntity<Any> {
        val userId = currentUserId() ?: return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build()
        val owner = userRepository.findById(userId).orElseThrow()

        val finalSlug = (slug?.takeIf { it.isNotBlank() } ?: name).let(::slugify)
        if (shopItemRepository.findAllByOwnerOrderBySortAscIdAsc(owner).any { it.slug == finalSlug }) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Produkt o tym slugu już istnieje."))
        }

        val priceGrosze = pricePln * 100
        val image = imageFile?.takeIf { !it.isEmpty }?.let { fileStorageService.storePublic(it, "shop-items") }

        val item = shopItemRepository.save(
            ShopItem(owner = owner, slug = finalSlug, name = name, image = image, minAmount = priceGrosze, price = priceGrosze, description = description, tagUid = tagUid?.ifBlank { null }, active = active, sort = sort, isDefault = isDefault),
        )
        applyDefault(owner.id!!, item.id!!, isDefault)

        return ResponseEntity.status(HttpStatus.CREATED).body(mapOf("id" to item.id!!))
    }

    @PutMapping("/{id}")
    fun update(
        @PathVariable id: Long,
        @RequestParam name: String,
        @RequestParam(required = false) slug: String?,
        @RequestParam pricePln: Int,
        @RequestParam(required = false) description: String?,
        @RequestParam(required = false) tagUid: String?,
        @RequestParam(required = false, defaultValue = "0") sort: Int,
        @RequestParam(required = false, defaultValue = "true") active: Boolean,
        @RequestParam(required = false, defaultValue = "false") isDefault: Boolean,
        @RequestParam(required = false) imageFile: MultipartFile?,
    ): ResponseEntity<Any> {
        val userId = currentUserId() ?: return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build()
        val item = shopItemRepository.findByOwnerAndId(userRepository.findById(userId).orElseThrow(), id)
            ?: return ResponseEntity.status(HttpStatus.FORBIDDEN).build()

        item.name = name
        slug?.takeIf { it.isNotBlank() }?.let { item.slug = slugify(it) }
        val priceGrosze = pricePln * 100
        item.price = priceGrosze
        item.minAmount = priceGrosze
        item.description = description
        item.tagUid = tagUid?.ifBlank { null }
        item.sort = sort
        item.active = active
        imageFile?.takeIf { !it.isEmpty }?.let { item.image = fileStorageService.storePublic(it, "shop-items") }
        shopItemRepository.save(item)
        applyDefault(userId, id, isDefault)

        return ResponseEntity.ok(mapOf("id" to item.id!!))
    }

    @PostMapping("/{id}/toggle")
    fun toggle(@PathVariable id: Long): ResponseEntity<Any> {
        val userId = currentUserId() ?: return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build()
        val item = shopItemRepository.findByOwnerAndId(userRepository.findById(userId).orElseThrow(), id)
            ?: return ResponseEntity.status(HttpStatus.FORBIDDEN).build()
        item.active = !item.active
        shopItemRepository.save(item)
        return ResponseEntity.ok(mapOf("active" to item.active))
    }

    @DeleteMapping("/{id}")
    fun destroy(@PathVariable id: Long): ResponseEntity<Any> {
        val userId = currentUserId() ?: return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build()
        val item = shopItemRepository.findByOwnerAndId(userRepository.findById(userId).orElseThrow(), id)
            ?: return ResponseEntity.status(HttpStatus.FORBIDDEN).build()
        shopItemRepository.delete(item)
        return ResponseEntity.noContent().build()
    }

    /** Tylko jeden produkt może być domyślny — odpowiednik `ShopItemController::applyDefault`. */
    private fun applyDefault(ownerId: Long, itemId: Long, isDefault: Boolean) {
        val owner = userRepository.findById(ownerId).orElseThrow()
        val allItems = shopItemRepository.findAllByOwnerOrderBySortAscIdAsc(owner)
        if (isDefault) {
            allItems.filter { it.id != itemId && it.isDefault }.forEach { it.isDefault = false; shopItemRepository.save(it) }
            allItems.firstOrNull { it.id == itemId }?.let { it.isDefault = true; shopItemRepository.save(it) }
        } else {
            allItems.firstOrNull { it.id == itemId && it.isDefault }?.let { it.isDefault = false; shopItemRepository.save(it) }
        }
    }

    private fun slugify(input: String): String = input.lowercase().replace(Regex("[^a-z0-9]+"), "-").trim('-').ifBlank { "produkt" }

    private fun summarize(item: ShopItem) = ShopItemPanelSummary(
        item.id!!, item.name, item.slug, item.description, item.pricePln(), item.minAmountPln(), item.tagUid, item.sort, item.isDefault, item.active, item.image,
    )
}
