package com.supportme.pay.gateway.api.panel.shop

import com.supportme.pay.gateway.api.panel.stats.StatsService
import com.supportme.pay.gateway.domain.entity.PaymentMode
import com.supportme.pay.gateway.domain.entity.Shop
import com.supportme.pay.gateway.domain.repository.ShopRepository
import com.supportme.pay.gateway.domain.repository.TagRepository
import jakarta.validation.Valid
import jakarta.validation.constraints.NotBlank
import org.springframework.http.HttpStatus
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.PathVariable
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.PutMapping
import org.springframework.web.bind.annotation.RequestBody
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController
import java.security.SecureRandom
import kotlin.random.Random

data class ShopSummary(
    val id: Long,
    val name: String,
    val slug: String,
    val baseUrl: String,
    val paymentMode: String,
    val tagsCount: Long,
    val revenuePln: String,
    val conversionPercent: Double,
)

data class ShopUpsertRequest(
    @field:NotBlank val name: String,
    @field:NotBlank val baseUrl: String,
    @field:NotBlank val paymentMode: String,
)

/** Odpowiednik `Panel\ShopController` — WYŁĄCZNIE `apiKey` zwracane raz, przy tworzeniu (patrz `store`). */
@RestController
@RequestMapping("/api/gateway/panel/shops")
class ShopController(
    private val shopRepository: ShopRepository,
    private val tagRepository: TagRepository,
    private val statsService: StatsService,
) {

    @GetMapping
    fun index(): List<ShopSummary> = shopRepository.findAllByOrderById().map { shop ->
        val summary = statsService.summary(shop.id, null, null)
        ShopSummary(
            id = shop.id!!,
            name = shop.name,
            slug = shop.slug,
            baseUrl = shop.baseUrl,
            paymentMode = shop.paymentMode.dbValue,
            tagsCount = tagRepository.countByShop(shop),
            revenuePln = summary.revenuePln,
            conversionPercent = summary.conversionPercent,
        )
    }

    /** Odpowiedź zawiera `apiKey` w PLAINTEXT — TYLKO tutaj, nigdy w `index`/`GET {id}`. Brak funkcji regeneracji (jak w oryginale). */
    @PostMapping
    fun store(@Valid @RequestBody body: ShopUpsertRequest): ResponseEntity<Map<String, Any>> {
        val mode = PaymentMode.entries.firstOrNull { it.dbValue == body.paymentMode }
            ?: return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Nieprawidłowy tryb płatności"))

        val slug = generateUniqueSlug(body.name)
        val apiKey = generateApiKey()
        val shop = shopRepository.save(Shop(name = body.name, slug = slug, baseUrl = body.baseUrl, apiKey = apiKey, paymentMode = mode))

        return ResponseEntity.status(HttpStatus.CREATED).body(
            mapOf("id" to shop.id!!, "name" to shop.name, "slug" to shop.slug, "apiKey" to apiKey),
        )
    }

    @PutMapping("/{id}")
    fun update(@PathVariable id: Long, @Valid @RequestBody body: ShopUpsertRequest): ResponseEntity<Map<String, Any>> {
        val shop = shopRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        val mode = PaymentMode.entries.firstOrNull { it.dbValue == body.paymentMode }
            ?: return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Nieprawidłowy tryb płatności"))

        shop.name = body.name
        shop.baseUrl = body.baseUrl
        shop.paymentMode = mode
        shopRepository.save(shop)

        return ResponseEntity.ok(mapOf("id" to shop.id!!, "name" to shop.name))
    }

    /** `Str::slug(name) + '-' + 4 losowe małe znaki` — jak w oryginale (unikalność sprawdzana, nie zakładana). */
    private fun generateUniqueSlug(name: String): String {
        val base = slugify(name)
        var attempt: String
        do {
            attempt = "$base-${randomLowercase(4)}"
        } while (shopRepository.existsBySlug(attempt))
        return attempt
    }

    private fun slugify(input: String): String = input.lowercase()
        .replace(Regex("[^a-z0-9]+"), "-")
        .trim('-')
        .ifBlank { "shop" }

    private fun randomLowercase(length: Int): String {
        val chars = "abcdefghijklmnopqrstuvwxyz"
        return (1..length).map { chars[Random.nextInt(chars.length)] }.joinToString("")
    }

    /** `bin2hex(random_bytes(32))` w PHP -> 64 znaki hex. `SecureRandom`, NIE `kotlin.random.Random` (nie-CSPRNG). */
    private fun generateApiKey(): String {
        val bytes = ByteArray(32)
        SECURE_RANDOM.nextBytes(bytes)
        return bytes.joinToString("") { "%02x".format(it) }
    }

    companion object {
        private val SECURE_RANDOM = SecureRandom()
    }
}
