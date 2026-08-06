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
        @RequestParam(required = false, defaultValue = "false") active: Boolean,
        @RequestParam(required = false, defaultValue = "false") isDefault: Boolean,
        @RequestParam(required = false) imageFile: MultipartFile?,
    ): ResponseEntity<Any> {
        val userId = currentUserId() ?: return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build()
        val owner = userRepository.findById(userId).orElseThrow()

        if (pricePln < MIN_PRICE_PLN || pricePln > MAX_PRICE_PLN) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Cena musi być między $MIN_PRICE_PLN a $MAX_PRICE_PLN zł."))
        }
        if (imageFile != null && !imageFile.isEmpty && imageFile.size > MAX_IMAGE_BYTES) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Grafika może mieć maksymalnie 5 MB."))
        }

        val finalSlug = (slug?.takeIf { it.isNotBlank() } ?: name).let(::slugify)
        if (shopItemRepository.findAllByOwnerOrderBySortAscIdAsc(owner).any { it.slug == finalSlug }) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Produkt o tym slugu już istnieje."))
        }
        val finalTagUid = tagUid?.ifBlank { null }
        // Unikalność tag_uid jest GLOBALNA (nie per-user) — jak `Rule::unique('shop_items','tag_uid')`
        // bez `->where('user_id',...)`, w odróżnieniu od sluga.
        if (finalTagUid != null && shopItemRepository.findByTagUid(finalTagUid) != null) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Ten tag NFC jest już przypisany do innego produktu."))
        }

        val priceGrosze = pricePln * 100
        val image = imageFile?.takeIf { !it.isEmpty }?.let { fileStorageService.storePublic(it, "shop-items") }

        val item = shopItemRepository.save(
            ShopItem(owner = owner, slug = finalSlug, name = name, image = image, minAmount = priceGrosze, price = priceGrosze, description = description, tagUid = finalTagUid, active = active, sort = sort, isDefault = isDefault),
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
        @RequestParam(required = false, defaultValue = "false") active: Boolean,
        @RequestParam(required = false, defaultValue = "false") isDefault: Boolean,
        @RequestParam(required = false) imageFile: MultipartFile?,
    ): ResponseEntity<Any> {
        val userId = currentUserId() ?: return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build()
        val item = findOwned(id, userId) ?: return notFoundOrForbidden(id)

        if (pricePln < MIN_PRICE_PLN || pricePln > MAX_PRICE_PLN) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Cena musi być między $MIN_PRICE_PLN a $MAX_PRICE_PLN zł."))
        }
        if (imageFile != null && !imageFile.isEmpty && imageFile.size > MAX_IMAGE_BYTES) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Grafika może mieć maksymalnie 5 MB."))
        }

        val finalSlug = slug?.takeIf { it.isNotBlank() }?.let(::slugify) ?: item.slug
        if (finalSlug != item.slug && shopItemRepository.findAllByOwnerOrderBySortAscIdAsc(item.owner!!).any { it.id != id && it.slug == finalSlug }) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Produkt o tym slugu już istnieje."))
        }
        val finalTagUid = tagUid?.ifBlank { null }
        if (finalTagUid != null && finalTagUid != item.tagUid && shopItemRepository.findByTagUid(finalTagUid) != null) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Ten tag NFC jest już przypisany do innego produktu."))
        }

        item.name = name
        item.slug = finalSlug
        val priceGrosze = pricePln * 100
        item.price = priceGrosze
        item.minAmount = priceGrosze
        item.description = description
        item.tagUid = finalTagUid
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
        val item = findOwned(id, userId) ?: return notFoundOrForbidden(id)
        item.active = !item.active
        shopItemRepository.save(item)
        return ResponseEntity.ok(mapOf("active" to item.active))
    }

    @DeleteMapping("/{id}")
    fun destroy(@PathVariable id: Long): ResponseEntity<Any> {
        val userId = currentUserId() ?: return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build()
        val item = findOwned(id, userId) ?: return notFoundOrForbidden(id)
        shopItemRepository.delete(item)
        return ResponseEntity.noContent().build()
    }

    private fun findOwned(id: Long, userId: Long): ShopItem? =
        shopItemRepository.findByOwnerAndId(userRepository.findById(userId).orElseThrow(), id)

    /** 404 dla nieistniejącego id, 403 dla istniejącego-ale-cudzego — jak route-model-binding + `guard()` w PHP. */
    private fun notFoundOrForbidden(id: Long): ResponseEntity<Any> =
        if (shopItemRepository.existsById(id)) ResponseEntity.status(HttpStatus.FORBIDDEN).build() else ResponseEntity.notFound().build()

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

    companion object {
        private const val MIN_PRICE_PLN = 1
        private const val MAX_PRICE_PLN = 5000
        private const val MAX_IMAGE_BYTES = 5 * 1024 * 1024L
    }
}
