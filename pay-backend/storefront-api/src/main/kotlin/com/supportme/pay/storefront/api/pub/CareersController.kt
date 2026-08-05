package com.supportme.pay.storefront.api.pub

import com.supportme.pay.storefront.api.storage.FileStorageService
import com.supportme.pay.storefront.domain.entity.JobApplication
import com.supportme.pay.storefront.domain.repository.JobApplicationRepository
import com.supportme.pay.storefront.domain.repository.JobPositionRepository
import org.springframework.http.HttpStatus
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.PathVariable
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RequestParam
import org.springframework.web.bind.annotation.RestController
import org.springframework.web.multipart.MultipartFile
import java.time.Instant

data class JobPositionSummary(val id: Long, val title: String, val location: String?, val employmentType: String?, val isRemote: Boolean)
data class JobPositionDetail(val id: Long, val title: String, val location: String?, val employmentType: String?, val descriptionHtml: String?, val otherOpenings: List<JobPositionSummary>)

/** Odpowiednik `CareersController` — `/praca`, honeypot antyspam w `applyStore`. */
@RestController
@RequestMapping("/praca")
class CareersController(
    private val jobPositionRepository: JobPositionRepository,
    private val jobApplicationRepository: JobApplicationRepository,
    private val fileStorageService: FileStorageService,
) {

    @GetMapping
    fun index(): List<JobPositionSummary> = jobPositionRepository.findAllByActiveTrueOrderBySortAscIdAsc().map { summarize(it.id!!, it.title, it.location, it.employmentType) }

    @GetMapping("/oferta/{id}")
    fun show(@PathVariable id: Long): ResponseEntity<JobPositionDetail> {
        val position = jobPositionRepository.findById(id).orElse(null)?.takeIf { it.active } ?: return ResponseEntity.notFound().build()
        val others = jobPositionRepository.findAllByActiveTrueOrderBySortAscIdAsc().filter { it.id != id }.take(3)
            .map { summarize(it.id!!, it.title, it.location, it.employmentType) }

        return ResponseEntity.ok(JobPositionDetail(position.id!!, position.title, position.location, position.employmentType, position.descriptionHtml, others))
    }

    /**
     * `positionId=null` = aplikacja ogólna/spontaniczna. Honeypot: pole
     * `website` MUSI być puste (boty je wypełniają) — ciche odrzucenie
     * (201 udawany sukces), żeby nie zdradzać mechanizmu antyspamowego.
     */
    @PostMapping("/aplikuj", "/{positionId}/aplikuj")
    fun applyStore(
        @PathVariable(required = false) positionId: Long?,
        @RequestParam name: String,
        @RequestParam email: String,
        @RequestParam(required = false) phone: String?,
        @RequestParam(required = false) message: String?,
        @RequestParam cv: MultipartFile,
        @RequestParam rodo: Boolean,
        @RequestParam(required = false, defaultValue = "false") futureConsent: Boolean,
        @RequestParam(required = false) website: String?,
    ): ResponseEntity<Map<String, Boolean>> {
        if (!website.isNullOrBlank()) {
            return ResponseEntity.status(HttpStatus.CREATED).body(mapOf("ok" to true)) // honeypot — udawany sukces
        }
        if (!rodo) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to false))
        }
        if (cv.isEmpty || cv.size > MAX_CV_SIZE_BYTES) {
            return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to false))
        }

        val position = positionId?.let { jobPositionRepository.findById(it).orElse(null) }
        val cvPath = fileStorageService.storePrivate(cv, "cv")

        jobApplicationRepository.save(
            JobApplication(
                position = position,
                name = name,
                email = email,
                phone = phone,
                message = message,
                cvPath = cvPath,
                cvOriginalName = cv.originalFilename,
                futureRecruitmentConsent = futureConsent,
                futureRecruitmentConsentAt = if (futureConsent) Instant.now() else null,
            ),
        )

        return ResponseEntity.status(HttpStatus.CREATED).body(mapOf("ok" to true))
    }

    private fun summarize(id: Long, title: String, location: String?, employmentType: String?): JobPositionSummary {
        val haystack = "${location.orEmpty()} ${employmentType.orEmpty()}".lowercase()
        val isRemote = REMOTE_KEYWORDS.any { haystack.contains(it) }
        return JobPositionSummary(id, title, location, employmentType, isRemote)
    }

    companion object {
        private const val MAX_CV_SIZE_BYTES = 5 * 1024 * 1024
        private val REMOTE_KEYWORDS = listOf("zdaln", "remote", "hybryd")
    }
}
