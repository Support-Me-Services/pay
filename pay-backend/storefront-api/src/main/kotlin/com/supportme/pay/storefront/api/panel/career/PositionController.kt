package com.supportme.pay.storefront.api.panel.career

import com.supportme.pay.storefront.domain.entity.JobPosition
import com.supportme.pay.storefront.domain.repository.JobApplicationRepository
import com.supportme.pay.storefront.domain.repository.JobPositionRepository
import jakarta.validation.Valid
import jakarta.validation.constraints.Max
import jakarta.validation.constraints.Min
import jakarta.validation.constraints.NotBlank
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
import org.springframework.web.bind.annotation.RestController

data class PositionRequest(
    @field:NotBlank @field:Size(max = 255) val title: String,
    @field:Size(max = 255) val location: String? = null,
    @field:Size(max = 255) val employmentType: String? = null,
    val descriptionHtml: String? = null,
    @field:Size(max = 500) val shortDescription: String? = null,
    @field:Min(0) @field:Max(65535) val sort: Int = 0,
    /** Domyślnie `false` gdy pole nieobecne w body — jak `$request->boolean('active')` w PHP. */
    val active: Boolean = false,
)
data class PositionPanelSummary(val id: Long, val title: String, val location: String?, val employmentType: String?, val active: Boolean, val sort: Int, val applicationsCount: Long)

/** Odpowiednik `Panel\PositionController` — CRUD + toggle, `withCount('applications')`. */
@RestController
@RequestMapping("/api/storefront/panel/positions")
class PositionController(
    private val jobPositionRepository: JobPositionRepository,
    private val jobApplicationRepository: JobApplicationRepository,
) {

    @GetMapping
    fun index(): List<PositionPanelSummary> {
        val allApplications = jobApplicationRepository.findAll()
        return jobPositionRepository.findAll().sortedWith(compareBy({ it.sort }, { it.id })).map { position ->
            val count = allApplications.count { it.position?.id == position.id }
            PositionPanelSummary(position.id!!, position.title, position.location, position.employmentType, position.active, position.sort, count.toLong())
        }
    }

    @PostMapping
    fun store(@Valid @RequestBody body: PositionRequest): ResponseEntity<Any> {
        val position = jobPositionRepository.save(JobPosition(title = body.title, location = body.location, employmentType = body.employmentType, descriptionHtml = body.descriptionHtml, shortDescription = body.shortDescription, sort = body.sort, active = body.active))
        return ResponseEntity.status(HttpStatus.CREATED).body(mapOf("id" to position.id!!))
    }

    @PutMapping("/{id}")
    fun update(@PathVariable id: Long, @Valid @RequestBody body: PositionRequest): ResponseEntity<Any> {
        val position = jobPositionRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        position.title = body.title
        position.location = body.location
        position.employmentType = body.employmentType
        position.descriptionHtml = body.descriptionHtml
        position.shortDescription = body.shortDescription
        position.sort = body.sort
        position.active = body.active
        jobPositionRepository.save(position)
        return ResponseEntity.ok(mapOf("id" to position.id!!))
    }

    @PostMapping("/{id}/toggle")
    fun toggle(@PathVariable id: Long): ResponseEntity<Any> {
        val position = jobPositionRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        position.active = !position.active
        jobPositionRepository.save(position)
        return ResponseEntity.ok(mapOf("active" to position.active))
    }

    @DeleteMapping("/{id}")
    fun destroy(@PathVariable id: Long): ResponseEntity<Any> {
        val position = jobPositionRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        jobPositionRepository.delete(position)
        return ResponseEntity.noContent().build()
    }
}
