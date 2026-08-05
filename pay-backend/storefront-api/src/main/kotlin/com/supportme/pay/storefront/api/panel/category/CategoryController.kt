package com.supportme.pay.storefront.api.panel.category

import com.supportme.pay.storefront.api.storage.FileStorageService
import com.supportme.pay.storefront.domain.entity.Category
import com.supportme.pay.storefront.domain.entity.CategorySource
import com.supportme.pay.storefront.domain.repository.CategoryRepository
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

data class CategoryPanelNode(val id: Long, val parentId: Long?, val slug: String, val label: String, val intro: String?, val icon: String?, val source: String, val position: Int, val active: Boolean, val children: List<CategoryPanelNode>)

/** Odpowiednik `Panel\CategoryController` — drzewo + reorder-by-swap-sąsiadów. */
@RestController
@RequestMapping("/api/storefront/panel/categories")
class CategoryController(
    private val categoryRepository: CategoryRepository,
    private val fileStorageService: FileStorageService,
) {

    @GetMapping
    fun index(): List<CategoryPanelNode> {
        val roots = categoryRepository.findAllByParentIsNullAndActiveTrueOrderByPositionAscIdAsc()
            .ifEmpty { categoryRepository.findAll().filter { it.parent == null }.sortedWith(compareBy({ it.position }, { it.id })) }
        return roots.map(::buildTree)
    }

    private fun buildTree(category: Category): CategoryPanelNode {
        val children = categoryRepository.findAllByParentOrderByPositionAscIdAsc(category).map(::buildTree)
        return CategoryPanelNode(category.id!!, category.parent?.id, category.slug, category.label, category.intro, category.icon, category.source.dbValue, category.position, category.active, children)
    }

    @PostMapping
    fun store(
        @RequestParam label: String,
        @RequestParam(required = false) parentId: Long?,
        @RequestParam(required = false) slug: String?,
        @RequestParam(required = false) intro: String?,
        @RequestParam(required = false, defaultValue = "none") source: String,
        @RequestParam(required = false) icon: MultipartFile?,
    ): ResponseEntity<Any> {
        val parent = parentId?.let { categoryRepository.findById(it).orElse(null) }
        val finalSlug = generateUniqueSlug(slug?.takeIf { it.isNotBlank() } ?: label)
        val iconPath = icon?.takeIf { !it.isEmpty }?.let { fileStorageService.storePublic(it, "category-icons") }
        val sourceEnum = CategorySource.entries.firstOrNull { it.dbValue == source } ?: CategorySource.NONE
        val siblings = parent?.let { categoryRepository.findAllByParentOrderByPositionAscIdAsc(it) }
            ?: categoryRepository.findAllByParentIsNullAndActiveTrueOrderByPositionAscIdAsc()
        val nextPosition = (siblings.maxOfOrNull { it.position } ?: -1) + 1

        val category = categoryRepository.save(
            Category(parent = parent, slug = finalSlug, label = label, labelText = stripHtml(label), intro = intro, icon = iconPath, source = sourceEnum, position = nextPosition),
        )
        return ResponseEntity.status(HttpStatus.CREATED).body(mapOf("id" to category.id!!))
    }

    @PutMapping("/{id}")
    fun update(
        @PathVariable id: Long,
        @RequestParam label: String,
        @RequestParam(required = false) parentId: Long?,
        @RequestParam(required = false) intro: String?,
        @RequestParam(required = false, defaultValue = "none") source: String,
        @RequestParam(required = false, defaultValue = "true") active: Boolean,
        @RequestParam(required = false) icon: MultipartFile?,
    ): ResponseEntity<Any> {
        val category = categoryRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        val descendantIds = descendantIds(category)
        if (parentId != null && (parentId == id || parentId in descendantIds)) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Nie można ustawić potomka jako rodzica."))
        }

        category.label = label
        category.labelText = stripHtml(label)
        category.parent = parentId?.let { categoryRepository.findById(it).orElse(null) }
        category.intro = intro
        category.source = CategorySource.entries.firstOrNull { it.dbValue == source } ?: category.source
        category.active = active
        icon?.takeIf { !it.isEmpty }?.let {
            category.icon?.let { old -> fileStorageService.deletePublic(old) }
            category.icon = fileStorageService.storePublic(it, "category-icons")
        }
        categoryRepository.save(category)
        return ResponseEntity.ok(mapOf("id" to category.id!!))
    }

    @DeleteMapping("/{id}")
    fun destroy(@PathVariable id: Long): ResponseEntity<Any> {
        val category = categoryRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        category.icon?.let { fileStorageService.deletePublic(it) }
        categoryRepository.delete(category)
        return ResponseEntity.noContent().build()
    }

    /** Zamiana `position` z najbliższym sąsiadem (góra/dół) — jak w oryginale (nie pełny reindex listy). */
    @PostMapping("/{id}/reorder")
    fun reorder(@PathVariable id: Long, @RequestParam direction: String): ResponseEntity<Any> {
        val category = categoryRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        val siblings = (category.parent?.let { categoryRepository.findAllByParentOrderByPositionAscIdAsc(it) }
            ?: categoryRepository.findAllByParentIsNullAndActiveTrueOrderByPositionAscIdAsc())
            .sortedWith(compareBy({ it.position }, { it.id }))

        val index = siblings.indexOfFirst { it.id == id }
        val neighborIndex = if (direction == "up") index - 1 else index + 1
        if (neighborIndex < 0 || neighborIndex >= siblings.size) return ResponseEntity.ok(mapOf("status" to "noop"))

        val neighbor = siblings[neighborIndex]
        val tmp = category.position
        category.position = neighbor.position
        neighbor.position = tmp
        categoryRepository.save(category)
        categoryRepository.save(neighbor)
        return ResponseEntity.ok(mapOf("status" to "ok"))
    }

    private fun descendantIds(category: Category): Set<Long> {
        val direct = categoryRepository.findAllByParentOrderByPositionAscIdAsc(category)
        return direct.flatMap { listOf(it.id!!) + descendantIds(it) }.toSet()
    }

    private fun generateUniqueSlug(base: String): String {
        val slugBase = base.lowercase().replace(Regex("[^a-z0-9]+"), "-").trim('-').ifBlank { "kategoria" }
        var candidate = slugBase
        var i = 2
        while (categoryRepository.findBySlug(candidate) != null) {
            candidate = "$slugBase-${i++}"
        }
        return candidate
    }

    private fun stripHtml(input: String): String = input.replace(Regex("<[^>]*>"), "")
}
