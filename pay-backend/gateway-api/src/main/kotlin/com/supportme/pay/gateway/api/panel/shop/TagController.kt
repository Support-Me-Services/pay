package com.supportme.pay.gateway.api.panel.shop

import com.supportme.pay.gateway.api.panel.stats.StatsService
import com.supportme.pay.gateway.domain.entity.Tag
import com.supportme.pay.gateway.domain.repository.ShopRepository
import com.supportme.pay.gateway.domain.repository.TagRepository
import jakarta.validation.Valid
import jakarta.validation.constraints.NotBlank
import jakarta.validation.constraints.Size
import org.hibernate.validator.constraints.URL
import org.springframework.http.HttpStatus
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.PathVariable
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.PutMapping
import org.springframework.web.bind.annotation.RequestBody
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController

data class TagSummary(
    val id: Long,
    val tagUid: String,
    val label: String?,
    val targetUrl: String,
    val active: Boolean,
    val opens: Long,
    val paid: Long,
)

data class TagUpsertRequest(
    @field:NotBlank @field:Size(max = 255) val tagUid: String,
    @field:Size(max = 255) val label: String? = null,
    @field:NotBlank @field:Size(max = 255) @field:URL val targetUrl: String,
    /** Domyślnie `false` gdy pole nieobecne w body — jak `$request->boolean('active')` w PHP. */
    val active: Boolean = false,
)

/** Odpowiednik `Panel\TagController` — zagnieżdżony pod sklepem (`/shops/{shopId}/tags`). */
@RestController
@RequestMapping("/api/gateway/panel/shops/{shopId}/tags")
class TagController(
    private val shopRepository: ShopRepository,
    private val tagRepository: TagRepository,
    private val statsService: StatsService,
) {

    @GetMapping
    fun index(@PathVariable shopId: Long): ResponseEntity<List<TagSummary>> {
        val shop = shopRepository.findById(shopId).orElse(null) ?: return ResponseEntity.notFound().build()
        val tags = tagRepository.findAllByShopOrderById(shop).map { tag ->
            val stats = statsService.summary(shop.id, tag.id, null)
            TagSummary(tag.id!!, tag.tagUid, tag.label, tag.targetUrl, tag.active, stats.opens, stats.paid)
        }
        return ResponseEntity.ok(tags)
    }

    @PostMapping
    fun store(@PathVariable shopId: Long, @Valid @RequestBody body: TagUpsertRequest): ResponseEntity<Any> {
        val shop = shopRepository.findById(shopId).orElse(null) ?: return ResponseEntity.notFound().build()
        if (tagRepository.existsByTagUid(body.tagUid)) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Tag NFC o tym UID już istnieje"))
        }

        val tag = tagRepository.save(Tag(shop = shop, tagUid = body.tagUid, targetUrl = body.targetUrl, label = body.label, active = body.active))
        return ResponseEntity.status(HttpStatus.CREATED).body(mapOf("id" to tag.id!!))
    }

    @PutMapping("/{tagId}")
    fun update(@PathVariable shopId: Long, @PathVariable tagId: Long, @Valid @RequestBody body: TagUpsertRequest): ResponseEntity<Any> {
        val shop = shopRepository.findById(shopId).orElse(null) ?: return ResponseEntity.notFound().build()
        val tag = tagRepository.findByIdAndShop(tagId, shop) ?: return ResponseEntity.notFound().build()

        val duplicateUid = body.tagUid != tag.tagUid && tagRepository.existsByTagUid(body.tagUid)
        if (duplicateUid) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Tag NFC o tym UID już istnieje"))
        }

        tag.tagUid = body.tagUid
        tag.label = body.label
        tag.targetUrl = body.targetUrl
        tag.active = body.active
        tagRepository.save(tag)

        return ResponseEntity.ok(mapOf("id" to tag.id!!))
    }
}
