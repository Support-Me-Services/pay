package com.supportme.pay.storefront.api.panel.beneficiary

import com.supportme.pay.storefront.api.storage.FileStorageService
import com.supportme.pay.storefront.domain.entity.BeneficiaryNode
import com.supportme.pay.storefront.domain.entity.Side
import com.supportme.pay.storefront.domain.repository.BeneficiaryNodeRepository
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

data class BeneficiaryNodePanelItem(
    val id: Long, val heading: String, val image: String?, val imageSide: String, val imageScale: Int, val imageX: Int, val imageY: Int,
    val textAlign: String, val bodyHtml: String?, val position: Int, val active: Boolean,
)

/** Odpowiednik `Panel\BeneficiaryNodeController` — CRUD + drag&drop reorder (AJAX, `order[]`). */
@RestController
@RequestMapping("/api/storefront/panel/beneficiaries")
class BeneficiaryNodeController(
    private val beneficiaryNodeRepository: BeneficiaryNodeRepository,
    private val fileStorageService: FileStorageService,
) {

    @GetMapping
    fun index(): List<BeneficiaryNodePanelItem> = beneficiaryNodeRepository.findAllByOrderByPositionAscIdAsc().map(::summarize)

    @PostMapping
    fun store(
        @RequestParam heading: String,
        @RequestParam(required = false, defaultValue = "left") imageSide: String,
        @RequestParam(required = false, defaultValue = "left") textAlign: String,
        @RequestParam(required = false, defaultValue = "100") imageScale: Int,
        @RequestParam(required = false, defaultValue = "0") imageX: Int,
        @RequestParam(required = false, defaultValue = "0") imageY: Int,
        @RequestParam(required = false) bodyHtml: String?,
        @RequestParam(required = false, defaultValue = "true") active: Boolean,
        @RequestParam(required = false) image: MultipartFile?,
    ): ResponseEntity<Any> {
        val maxPosition = beneficiaryNodeRepository.findAllByOrderByPositionAscIdAsc().maxOfOrNull { it.position } ?: -1
        val imagePath = image?.takeIf { !it.isEmpty }?.let { fileStorageService.storePublic(it, "beneficiaries") }

        val node = beneficiaryNodeRepository.save(
            BeneficiaryNode(
                heading = heading,
                image = imagePath,
                imageSide = side(imageSide),
                textAlign = side(textAlign),
                imageScale = imageScale,
                imageX = imageX,
                imageY = imageY,
                bodyHtml = bodyHtml,
                position = maxPosition + 1,
                active = active,
            ),
        )
        return ResponseEntity.status(HttpStatus.CREATED).body(mapOf("id" to node.id!!))
    }

    @PutMapping("/{id}")
    fun update(
        @PathVariable id: Long,
        @RequestParam heading: String,
        @RequestParam(required = false, defaultValue = "left") imageSide: String,
        @RequestParam(required = false, defaultValue = "left") textAlign: String,
        @RequestParam(required = false, defaultValue = "100") imageScale: Int,
        @RequestParam(required = false, defaultValue = "0") imageX: Int,
        @RequestParam(required = false, defaultValue = "0") imageY: Int,
        @RequestParam(required = false) bodyHtml: String?,
        @RequestParam(required = false, defaultValue = "true") active: Boolean,
        @RequestParam(required = false, defaultValue = "false") removeImage: Boolean,
        @RequestParam(required = false) image: MultipartFile?,
    ): ResponseEntity<Any> {
        val node = beneficiaryNodeRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()

        node.heading = heading
        node.imageSide = side(imageSide)
        node.textAlign = side(textAlign)
        node.imageScale = imageScale
        node.imageX = imageX
        node.imageY = imageY
        node.bodyHtml = bodyHtml
        node.active = active

        if (removeImage) {
            node.image?.let { fileStorageService.deletePublic(it) }
            node.image = null
        }
        image?.takeIf { !it.isEmpty }?.let {
            node.image?.let { old -> fileStorageService.deletePublic(old) }
            node.image = fileStorageService.storePublic(it, "beneficiaries")
        }

        beneficiaryNodeRepository.save(node)
        return ResponseEntity.ok(mapOf("id" to node.id!!))
    }

    @DeleteMapping("/{id}")
    fun destroy(@PathVariable id: Long): ResponseEntity<Any> {
        val node = beneficiaryNodeRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        node.image?.let { fileStorageService.deletePublic(it) }
        beneficiaryNodeRepository.delete(node)
        return ResponseEntity.noContent().build()
    }

    @PostMapping("/reorder")
    fun reorder(@RequestBody body: Map<String, List<Long>>): ResponseEntity<Any> {
        val order = body["order"] ?: return ResponseEntity.badRequest().build()
        order.forEachIndexed { index, id ->
            beneficiaryNodeRepository.findById(id).orElse(null)?.let { it.position = index; beneficiaryNodeRepository.save(it) }
        }
        return ResponseEntity.ok(mapOf("status" to "ok"))
    }

    private fun side(value: String): Side = Side.entries.firstOrNull { it.dbValue == value } ?: Side.LEFT

    private fun summarize(node: BeneficiaryNode) = BeneficiaryNodePanelItem(
        node.id!!, node.heading, node.image, node.imageSide.dbValue, node.imageScale, node.imageX, node.imageY, node.textAlign.dbValue, node.bodyHtml, node.position, node.active,
    )
}
