package com.supportme.pay.storefront.api.panel.product

import com.supportme.pay.storefront.api.storage.FileStorageService
import com.supportme.pay.storefront.domain.entity.ParishNote
import com.supportme.pay.storefront.domain.entity.ParishNoteType
import com.supportme.pay.storefront.domain.entity.Product
import com.supportme.pay.storefront.domain.entity.ProductImage
import com.supportme.pay.storefront.domain.entity.ProductStatus
import com.supportme.pay.storefront.domain.repository.ParishNoteRepository
import com.supportme.pay.storefront.domain.repository.ProductImageRepository
import com.supportme.pay.storefront.domain.repository.ProductRepository
import jakarta.validation.Valid
import jakarta.validation.constraints.NotBlank
import jakarta.validation.constraints.Pattern
import jakarta.validation.constraints.Size
import org.springframework.http.HttpStatus
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.DeleteMapping
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.PathVariable
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.PutMapping
import org.springframework.web.bind.annotation.RequestBody
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RequestParam
import org.springframework.web.bind.annotation.RestController
import org.springframework.web.multipart.MultipartFile

data class ProductPanelSummary(val id: Long, val name: String, val city: String?, val slug: String, val status: String, val active: Boolean)
data class ProductRequest(
    @field:NotBlank @field:Size(max = 255) val name: String,
    @field:Size(max = 255) val city: String? = null,
    val purpose: String? = null,
    val descriptionHtml: String? = null,
    @field:Size(max = 2000) val pickupInstruction: String? = null,
    /** String, jak w PHP (`regex:/^\d{1,5}([.,]\d{1,2})?$/`) — pozwala na grosze (np. "19,99"), nie tylko całe złote. */
    @field:NotBlank @field:Pattern(regexp = "\\d{1,5}([.,]\\d{1,2})?") val price: String,
    @field:NotBlank @field:Size(max = 255) val tagUid: String,
    @field:Size(max = 255) val phone: String? = null,
    @field:Size(max = 255) val website: String? = null,
    @field:Size(max = 255) val voivodeship: String? = null,
    @field:NotBlank val status: String,
)
data class ProductStatsSummaryResponse(val opens: Long, val views: Long, val clicks: Long, val purchases: Long, val revenuePln: String, val conversionPercent: Double)
data class FunnelPoint(val label: String, val value: Long)
data class ProductStatsResponse(
    val total: ProductStatsSummaryResponse,
    val last30: ProductStatsSummaryResponse,
    val funnel: List<FunnelPoint>,
    val series: List<DailyPurchasePoint>,
)
data class NoteRequest(@field:NotBlank @field:Size(max = 5000) val body: String, val type: String = "kontakt", val author: String? = null)
data class NoteSummary(val id: Long, val body: String, val type: String, val author: String?, val createdAt: String?)

/** Odpowiednik `Panel\ProductController` — CRM parafii/Taca. `status` steruje `active` (publish gate). */
@RestController
@RequestMapping("/api/storefront/panel/products")
class ProductController(
    private val productRepository: ProductRepository,
    private val productImageRepository: ProductImageRepository,
    private val parishNoteRepository: ParishNoteRepository,
    private val fileStorageService: FileStorageService,
    private val statsService: StorefrontStatsService,
) {

    @GetMapping
    fun index(@RequestParam(required = false) status: String?, @RequestParam(required = false) search: String?): List<ProductPanelSummary> {
        var products = productRepository.findAll()
        status?.let { s -> ProductStatus.entries.firstOrNull { it.dbValue == s }?.let { st -> products = products.filter { it.status == st } } }
        search?.takeIf { it.isNotBlank() }?.let { q ->
            val needle = q.lowercase()
            products = products.filter { it.name.lowercase().contains(needle) || it.city?.lowercase()?.contains(needle) == true || it.voivodeship?.lowercase()?.contains(needle) == true }
        }
        return products.map { ProductPanelSummary(it.id!!, it.name, it.city, it.slug, it.status.dbValue, it.active) }
    }

    @PostMapping
    fun store(@Valid @RequestBody body: ProductRequest): ResponseEntity<Any> {
        val status = ProductStatus.entries.firstOrNull { it.dbValue == body.status }
            ?: return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Nieprawidłowy status."))
        if (productRepository.findByTagUid(body.tagUid) != null) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Tag NFC już przypisany do innej parafii."))
        }
        val product = productRepository.save(
            Product(
                name = body.name, city = body.city, purpose = body.purpose, slug = generateUniqueSlug(body.name),
                descriptionHtml = body.descriptionHtml, pickupInstruction = body.pickupInstruction, price = parsePriceGrosze(body.price),
                tagUid = body.tagUid, phone = body.phone, website = body.website, voivodeship = body.voivodeship,
                status = status, active = status == ProductStatus.AKTYWNA,
            ),
        )
        return ResponseEntity.status(HttpStatus.CREATED).body(mapOf("id" to product.id!!))
    }

    @PutMapping("/{id}")
    fun update(@PathVariable id: Long, @Valid @RequestBody body: ProductRequest): ResponseEntity<Any> {
        val product = productRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        val status = ProductStatus.entries.firstOrNull { it.dbValue == body.status }
            ?: return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Nieprawidłowy status."))
        if (body.tagUid != product.tagUid && productRepository.findByTagUid(body.tagUid) != null) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Tag NFC już przypisany do innej parafii."))
        }

        product.name = body.name
        product.city = body.city
        product.purpose = body.purpose
        product.descriptionHtml = body.descriptionHtml
        product.pickupInstruction = body.pickupInstruction
        product.price = parsePriceGrosze(body.price)
        product.tagUid = body.tagUid
        product.phone = body.phone
        product.website = body.website
        product.voivodeship = body.voivodeship
        // Status steruje publikacją: aktywna => publiczna, pozostałe => lead (ukryta) — jak w PHP.
        product.status = status
        product.active = status == ProductStatus.AKTYWNA
        productRepository.save(product)
        return ResponseEntity.ok(mapOf("id" to product.id!!))
    }

    /** Jak `(int) round(((float) str_replace(',', '.', $price)) * 100)` w PHP. */
    private fun parsePriceGrosze(price: String): Int = Math.round(price.replace(',', '.').toDouble() * 100).toInt()

    @PostMapping("/{id}/main-image")
    fun uploadMainImage(@PathVariable id: Long, @RequestParam file: MultipartFile): ResponseEntity<Any> {
        val product = productRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        product.mainImage?.let { fileStorageService.deletePublic(it) }
        product.mainImage = fileStorageService.storePublic(file, "products")
        productRepository.save(product)
        return ResponseEntity.ok(mapOf("mainImage" to product.mainImage!!))
    }

    @PostMapping("/{id}/gallery")
    fun uploadGalleryImages(@PathVariable id: Long, @RequestParam files: List<MultipartFile>): ResponseEntity<Any> {
        val product = productRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        val maxSort = productImageRepository.findAllByProductOrderBySortAsc(product).maxOfOrNull { it.sort } ?: -1
        files.forEachIndexed { index, file ->
            val path = fileStorageService.storePublic(file, "products")
            productImageRepository.save(ProductImage(product = product, path = path, sort = maxSort + 1 + index))
        }
        return ResponseEntity.status(HttpStatus.CREATED).body(mapOf("status" to "ok"))
    }

    @DeleteMapping("/{id}/images/{imageId}")
    fun deleteImage(@PathVariable id: Long, @PathVariable imageId: Long): ResponseEntity<Any> {
        // Scope po product_id — jak `$product->images()->where('id',$imageId)` w PHP,
        // inaczej DELETE ...{A}/images/{imageIdNależącyDoB} usuwałby zdjęcie produktu B.
        val image = productImageRepository.findById(imageId).orElse(null)?.takeIf { it.product.id == id }
            ?: return ResponseEntity.notFound().build()
        fileStorageService.deletePublic(image.path)
        productImageRepository.delete(image)
        return ResponseEntity.noContent().build()
    }

    /** Zmiana statusu CRM — `active` przełącza się TYLKO przy statusie `aktywna` (publish gate). */
    @PostMapping("/{id}/status")
    fun updateStatus(@PathVariable id: Long, @RequestBody body: Map<String, String>): ResponseEntity<Any> {
        val product = productRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        val status = ProductStatus.entries.firstOrNull { it.dbValue == body["status"] }
            ?: return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).build()
        product.status = status
        product.active = status == ProductStatus.AKTYWNA
        productRepository.save(product)
        return ResponseEntity.ok(mapOf("status" to status.dbValue, "active" to product.active))
    }

    @GetMapping("/{id}/stats")
    fun stats(@PathVariable id: Long): ResponseEntity<ProductStatsResponse> {
        productRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()

        val total = statsService.summary(id, null)
        val last30 = statsService.summary(id, 30)
        val series = statsService.dailyPurchases(id, 30)

        return ResponseEntity.ok(
            ProductStatsResponse(
                total = total.toResponse(),
                last30 = last30.toResponse(),
                funnel = listOf(
                    FunnelPoint("Otwarcia tagów", total.opens),
                    FunnelPoint("Kliki „Kup”", total.clicks),
                    FunnelPoint("Zakupy", total.purchases),
                ),
                series = series,
            ),
        )
    }

    private fun ShopStatsSummary.toResponse() = ProductStatsSummaryResponse(opens, views, clicks, purchases, revenuePln, conversionPercent)

    @GetMapping("/{id}/notes")
    fun notes(@PathVariable id: Long): ResponseEntity<List<NoteSummary>> {
        val product = productRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        return ResponseEntity.ok(parishNoteRepository.findAllByProductOrderByIdDesc(product).map { NoteSummary(it.id!!, it.body, it.type.dbValue, it.author, it.createdAt?.toString()) })
    }

    @PostMapping("/{id}/notes")
    fun storeNote(@PathVariable id: Long, @Valid @RequestBody body: NoteRequest): ResponseEntity<Any> {
        val product = productRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        val type = ParishNoteType.entries.firstOrNull { it.dbValue == body.type }
            ?: return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Nieprawidłowy typ notatki."))
        val note = parishNoteRepository.save(ParishNote(product = product, body = body.body, type = type, author = body.author))
        return ResponseEntity.status(HttpStatus.CREATED).body(mapOf("id" to note.id!!))
    }

    @DeleteMapping("/{id}/notes/{noteId}")
    fun destroyNote(@PathVariable id: Long, @PathVariable noteId: Long): ResponseEntity<Any> {
        // Scope po product_id — jak `abort_unless($note->product_id === $product->id, 404)` w PHP.
        val note = parishNoteRepository.findById(noteId).orElse(null)?.takeIf { it.product.id == id }
            ?: return ResponseEntity.notFound().build()
        parishNoteRepository.delete(note)
        return ResponseEntity.noContent().build()
    }

    /** Generyczny upload obrazka edytora WYSIWYG — współdzielony przez BeneficiaryNode i tu. */
    @PostMapping("/upload-editor-image")
    fun uploadEditorImage(@RequestParam file: MultipartFile): ResponseEntity<Any> {
        val path = fileStorageService.storePublic(file, "products/editor")
        return ResponseEntity.ok(mapOf("path" to path))
    }

    private fun generateUniqueSlug(name: String): String {
        val base = name.lowercase().replace(Regex("[^a-z0-9]+"), "-").trim('-').ifBlank { "parafia" }
        var candidate = base
        var i = 2
        while (productRepository.findBySlug(candidate) != null) candidate = "$base-${i++}"
        return candidate
    }
}
