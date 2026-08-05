package com.supportme.pay.storefront.api.panel.product

import com.supportme.pay.storefront.api.storage.FileStorageService
import com.supportme.pay.storefront.domain.entity.ParishNote
import com.supportme.pay.storefront.domain.entity.ParishNoteType
import com.supportme.pay.storefront.domain.entity.Product
import com.supportme.pay.storefront.domain.entity.ProductImage
import com.supportme.pay.storefront.domain.entity.ProductStatus
import com.supportme.pay.storefront.domain.entity.StorefrontEventType
import com.supportme.pay.storefront.domain.repository.ParishNoteRepository
import com.supportme.pay.storefront.domain.repository.ProductImageRepository
import com.supportme.pay.storefront.domain.repository.ProductRepository
import com.supportme.pay.storefront.domain.repository.SalespersonRepository
import com.supportme.pay.storefront.domain.repository.StorefrontEventRepository
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

data class ProductPanelSummary(val id: Long, val name: String, val city: String?, val slug: String, val status: String, val active: Boolean, val salespersonName: String?)
data class ProductRequest(
    val name: String, val city: String? = null, val purpose: String? = null, val descriptionHtml: String? = null,
    val pickupInstruction: String? = null, val pricePln: Int, val tagUid: String, val phone: String? = null,
    val website: String? = null, val voivodeship: String? = null, val salespersonId: Long? = null,
)
data class ProductStatsResponse(val opens: Long, val buyClicks: Long, val purchases: Long)
data class NoteRequest(val body: String, val type: String = "kontakt", val author: String? = null)
data class NoteSummary(val id: Long, val body: String, val type: String, val author: String?, val createdAt: String?)

/** Odpowiednik `Panel\ProductController` — CRM parafii/Taca. `status` steruje `active` (publish gate). */
@RestController
@RequestMapping("/api/storefront/panel/products")
class ProductController(
    private val productRepository: ProductRepository,
    private val productImageRepository: ProductImageRepository,
    private val parishNoteRepository: ParishNoteRepository,
    private val salespersonRepository: SalespersonRepository,
    private val eventRepository: StorefrontEventRepository,
    private val fileStorageService: FileStorageService,
) {

    @GetMapping
    fun index(@RequestParam(required = false) status: String?, @RequestParam(required = false) search: String?): List<ProductPanelSummary> {
        var products = productRepository.findAll()
        status?.let { s -> ProductStatus.entries.firstOrNull { it.dbValue == s }?.let { st -> products = products.filter { it.status == st } } }
        search?.takeIf { it.isNotBlank() }?.let { q ->
            val needle = q.lowercase()
            products = products.filter { it.name.lowercase().contains(needle) || it.city?.lowercase()?.contains(needle) == true || it.voivodeship?.lowercase()?.contains(needle) == true }
        }
        return products.map { ProductPanelSummary(it.id!!, it.name, it.city, it.slug, it.status.dbValue, it.active, it.salesperson?.name) }
    }

    @PostMapping
    fun store(@RequestBody body: ProductRequest): ResponseEntity<Any> {
        if (productRepository.findByTagUid(body.tagUid) != null) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Tag NFC już przypisany do innej parafii."))
        }
        val salesperson = body.salespersonId?.let { salespersonRepository.findById(it).orElse(null) }
        val product = productRepository.save(
            Product(
                name = body.name, city = body.city, purpose = body.purpose, slug = generateUniqueSlug(body.name),
                descriptionHtml = body.descriptionHtml, pickupInstruction = body.pickupInstruction, price = body.pricePln * 100,
                tagUid = body.tagUid, phone = body.phone, website = body.website, voivodeship = body.voivodeship, salesperson = salesperson,
            ),
        )
        return ResponseEntity.status(HttpStatus.CREATED).body(mapOf("id" to product.id!!))
    }

    @PutMapping("/{id}")
    fun update(@PathVariable id: Long, @RequestBody body: ProductRequest): ResponseEntity<Any> {
        val product = productRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        if (body.tagUid != product.tagUid && productRepository.findByTagUid(body.tagUid) != null) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Tag NFC już przypisany do innej parafii."))
        }

        product.name = body.name
        product.city = body.city
        product.purpose = body.purpose
        product.descriptionHtml = body.descriptionHtml
        product.pickupInstruction = body.pickupInstruction
        product.price = body.pricePln * 100
        product.tagUid = body.tagUid
        product.phone = body.phone
        product.website = body.website
        product.voivodeship = body.voivodeship
        product.salesperson = body.salespersonId?.let { salespersonRepository.findById(it).orElse(null) }
        productRepository.save(product)
        return ResponseEntity.ok(mapOf("id" to product.id!!))
    }

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
        val image = productImageRepository.findById(imageId).orElse(null) ?: return ResponseEntity.notFound().build()
        fileStorageService.deletePublic(image.path)
        productImageRepository.delete(image)
        return ResponseEntity.noContent().build()
    }

    /** Zmiana statusu CRM — `active` przełącza się TYLKO przy statusie `aktywna` (publish gate). */
    @PostMapping("/{id}/status")
    fun updateStatus(@PathVariable id: Long, @RequestBody body: Map<String, String>): ResponseEntity<Any> {
        val product = productRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        val status = ProductStatus.entries.firstOrNull { it.dbValue == body["status"] } ?: return ResponseEntity.badRequest().build()
        product.status = status
        product.active = status == ProductStatus.AKTYWNA
        productRepository.save(product)
        return ResponseEntity.ok(mapOf("status" to status.dbValue, "active" to product.active))
    }

    @GetMapping("/{id}/stats")
    fun stats(@PathVariable id: Long): ResponseEntity<ProductStatsResponse> {
        val product = productRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        val events = eventRepository.findAll().filter { it.product?.id == product.id }
        return ResponseEntity.ok(
            ProductStatsResponse(
                opens = events.count { it.type == StorefrontEventType.TAG_OPEN }.toLong(),
                buyClicks = events.count { it.type == StorefrontEventType.BUY_CLICK }.toLong(),
                purchases = events.count { it.type == StorefrontEventType.PURCHASE }.toLong(),
            ),
        )
    }

    @GetMapping("/{id}/notes")
    fun notes(@PathVariable id: Long): ResponseEntity<List<NoteSummary>> {
        val product = productRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        return ResponseEntity.ok(parishNoteRepository.findAllByProductOrderByIdDesc(product).map { NoteSummary(it.id!!, it.body, it.type.dbValue, it.author, it.createdAt?.toString()) })
    }

    @PostMapping("/{id}/notes")
    fun storeNote(@PathVariable id: Long, @RequestBody body: NoteRequest): ResponseEntity<Any> {
        val product = productRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        val type = ParishNoteType.entries.firstOrNull { it.dbValue == body.type } ?: ParishNoteType.KONTAKT
        val note = parishNoteRepository.save(ParishNote(product = product, body = body.body, type = type, author = body.author))
        return ResponseEntity.status(HttpStatus.CREATED).body(mapOf("id" to note.id!!))
    }

    @DeleteMapping("/notes/{noteId}")
    fun destroyNote(@PathVariable noteId: Long): ResponseEntity<Any> {
        val note = parishNoteRepository.findById(noteId).orElse(null) ?: return ResponseEntity.notFound().build()
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
