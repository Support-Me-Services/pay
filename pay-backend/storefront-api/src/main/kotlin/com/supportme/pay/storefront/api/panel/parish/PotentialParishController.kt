package com.supportme.pay.storefront.api.panel.parish

import com.supportme.pay.storefront.domain.entity.PotentialParish
import com.supportme.pay.storefront.domain.entity.PotentialParishStatus
import com.supportme.pay.storefront.domain.repository.PotentialParishRepository
import com.supportme.pay.storefront.domain.repository.SalespersonRepository
import jakarta.persistence.criteria.Predicate
import org.springframework.data.domain.PageRequest
import org.springframework.data.domain.Sort
import org.springframework.data.jpa.domain.Specification
import org.springframework.http.HttpStatus
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.PathVariable
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.RequestBody
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RequestParam
import org.springframework.web.bind.annotation.RestController
import java.time.Instant

data class PotentialParishSummary(
    val id: Long, val name: String, val city: String?, val voivodeship: String?, val phone: String?,
    val status: String, val salespersonName: String?, val calledAt: String?,
)
data class PotentialParishStatusRequest(val status: String)
data class PotentialParishPage(val items: List<PotentialParishSummary>, val page: Int, val totalPages: Int, val statusCounts: Map<String, Long>)
data class CoveragePoint(val id: Long, val name: String, val lat: Double, val lon: Double, val status: String)

/**
 * Odpowiednik `Panel\PotentialParishController` — filtr tri-state `hasPhone`
 * (with/without/all, domyślnie "with"), AJAX auto-save statusu, mapa coverage.
 */
@RestController
@RequestMapping("/api/storefront/panel/potential-parishes")
class PotentialParishController(
    private val potentialParishRepository: PotentialParishRepository,
    private val salespersonRepository: SalespersonRepository,
) {

    @GetMapping
    fun index(
        @RequestParam(required = false) voivodeship: String?,
        @RequestParam(required = false) status: String?,
        @RequestParam(required = false) salespersonId: Long?,
        @RequestParam(required = false) search: String?,
        @RequestParam(required = false, defaultValue = "with") hasPhone: String,
        @RequestParam(required = false, defaultValue = "0") page: Int,
    ): PotentialParishPage {
        val spec = buildSpecification(voivodeship, status, salespersonId, search, hasPhone)
        val result = potentialParishRepository.findAll(spec, PageRequest.of(page, 50, Sort.by("id")))

        val allForCounts = potentialParishRepository.findAll(buildSpecification(voivodeship, null, salespersonId, search, hasPhone))
        val statusCounts = PotentialParishStatus.entries.associate { s -> s.dbValue to allForCounts.count { it.status == s }.toLong() }

        return PotentialParishPage(
            items = result.content.map(::summarize),
            page = page,
            totalPages = result.totalPages,
            statusCounts = statusCounts,
        )
    }

    @PostMapping("/{id}/status")
    fun updateStatus(@PathVariable id: Long, @RequestBody body: PotentialParishStatusRequest): ResponseEntity<Any> {
        val parish = potentialParishRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        val status = PotentialParishStatus.entries.firstOrNull { it.dbValue == body.status }
            ?: return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Nieprawidłowy status"))

        if (status == PotentialParishStatus.ZADZWONIONO && parish.calledAt == null) {
            parish.calledAt = Instant.now()
        }
        parish.status = status
        potentialParishRepository.save(parish)
        return ResponseEntity.ok(mapOf("id" to parish.id!!, "status" to status.dbValue))
    }

    @GetMapping("/coverage")
    fun coverageData(): List<CoveragePoint> = potentialParishRepository.findAll()
        .map { CoveragePoint(it.id!!, it.name, it.lat.toDouble(), it.lon.toDouble(), it.status.dbValue) }

    private fun buildSpecification(voivodeship: String?, status: String?, salespersonId: Long?, search: String?, hasPhone: String): Specification<PotentialParish> =
        Specification { root, _, cb ->
            val predicates = mutableListOf<Predicate>()
            voivodeship?.let { predicates += cb.equal(root.get<String>("voivodeship"), it) }
            status?.let { s -> PotentialParishStatus.entries.firstOrNull { it.dbValue == s }?.let { predicates += cb.equal(root.get<PotentialParishStatus>("status"), it) } }
            salespersonId?.let { predicates += cb.equal(root.get<Any>("salesperson").get<Long>("id"), it) }
            search?.takeIf { it.isNotBlank() }?.let {
                val like = "%${it.lowercase()}%"
                predicates += cb.or(
                    cb.like(cb.lower(root.get("name")), like),
                    cb.like(cb.lower(root.get<String>("city")), like),
                )
            }
            when (hasPhone) {
                "with" -> predicates += cb.and(cb.isNotNull(root.get<String>("phone")), cb.notEqual(root.get<String>("phone"), ""))
                "without" -> predicates += cb.or(cb.isNull(root.get<String>("phone")), cb.equal(root.get<String>("phone"), ""))
                else -> Unit // "all" — brak filtra
            }
            cb.and(*predicates.toTypedArray())
        }

    private fun summarize(p: PotentialParish) = PotentialParishSummary(p.id!!, p.name, p.city, p.voivodeship, p.phone, p.status.dbValue, p.salesperson?.name, p.calledAt?.toString())
}
