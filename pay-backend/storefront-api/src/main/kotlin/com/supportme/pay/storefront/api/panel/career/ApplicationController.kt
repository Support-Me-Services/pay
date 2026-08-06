package com.supportme.pay.storefront.api.panel.career

import com.supportme.pay.storefront.api.storage.FileStorageService
import com.supportme.pay.storefront.domain.entity.JobApplicationStatus
import com.supportme.pay.storefront.domain.repository.JobApplicationRepository
import org.springframework.http.HttpHeaders
import org.springframework.http.HttpStatus
import org.springframework.http.MediaType
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.DeleteMapping
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.PathVariable
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.RequestBody
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RequestParam
import org.springframework.web.bind.annotation.RestController
import java.time.Instant

data class ApplicationSummary(val id: Long, val positionTitle: String?, val name: String, val email: String, val phone: String?, val status: String, val isRead: Boolean, val createdAt: String?)
data class ApplicationStatusRequest(val status: String)

/**
 * Odpowiednik `Panel\ApplicationController`. UWAGA kolejności tras w oryginale:
 * `/applications/consents` musi być zarejestrowane PRZED `/applications/{id}`
 * (Spring rozstrzyga to inaczej niż Laravel — jawne osobne ścieżki, nie
 * kolejność deklaracji — więc tu problem nie występuje, ale zachowujemy
 * komentarz dla kontekstu historycznego przy migracji).
 */
@RestController
@RequestMapping("/api/storefront/panel/applications")
class ApplicationController(
    private val jobApplicationRepository: JobApplicationRepository,
    private val fileStorageService: FileStorageService,
) {

    @GetMapping
    fun index(@RequestParam(required = false) positionId: Long?, @RequestParam(required = false) status: String?): List<ApplicationSummary> {
        val statusFilter = status?.let { s -> JobApplicationStatus.entries.firstOrNull { it.dbValue == s } }
        return jobApplicationRepository.findAll()
            .filter { positionId == null || it.position?.id == positionId }
            .filter { statusFilter == null || it.status == statusFilter }
            .sortedByDescending { it.createdAt }
            .map(::summarize)
    }

    @GetMapping("/consents")
    fun consents(): List<ApplicationSummary> {
        val cutoff = Instant.now().minusSeconds(60L * 60 * 24 * 30 * 24) // 24 miesiące (przybliżenie 30-dniowych miesięcy dla filtra listy)
        return jobApplicationRepository.findActiveFutureConsent(cutoff).map(::summarize)
    }

    @GetMapping("/{id}")
    fun show(@PathVariable id: Long): ResponseEntity<ApplicationSummary> {
        val application = jobApplicationRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        if (!application.isRead) {
            application.isRead = true
            jobApplicationRepository.save(application)
        }
        return ResponseEntity.ok(summarize(application))
    }

    @GetMapping("/{id}/cv")
    fun downloadCv(@PathVariable id: Long): ResponseEntity<ByteArray> {
        val application = jobApplicationRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        val path = application.cvPath?.takeIf { fileStorageService.existsPrivate(it) } ?: return ResponseEntity.notFound().build()
        val bytes = fileStorageService.readPrivate(path)
        return ResponseEntity.ok()
            .contentType(MediaType.APPLICATION_OCTET_STREAM)
            .header(HttpHeaders.CONTENT_DISPOSITION, "attachment; filename=\"${application.cvOriginalName ?: "cv.pdf"}\"")
            .body(bytes)
    }

    @PostMapping("/{id}/status")
    fun updateStatus(@PathVariable id: Long, @RequestBody body: ApplicationStatusRequest): ResponseEntity<Any> {
        val application = jobApplicationRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        val status = JobApplicationStatus.entries.firstOrNull { it.dbValue == body.status } ?: return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).build()
        application.status = status
        jobApplicationRepository.save(application)
        return ResponseEntity.ok(mapOf("status" to status.dbValue))
    }

    @DeleteMapping("/{id}")
    fun destroy(@PathVariable id: Long): ResponseEntity<Any> {
        val application = jobApplicationRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        application.cvPath?.let { fileStorageService.deletePrivate(it) }
        jobApplicationRepository.delete(application)
        return ResponseEntity.noContent().build()
    }

    private fun summarize(a: com.supportme.pay.storefront.domain.entity.JobApplication) =
        ApplicationSummary(a.id!!, a.position?.title, a.name, a.email, a.phone, a.status.dbValue, a.isRead, a.createdAt?.toString())
}
