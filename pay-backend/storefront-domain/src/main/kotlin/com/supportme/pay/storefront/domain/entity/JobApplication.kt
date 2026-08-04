package com.supportme.pay.storefront.domain.entity

import jakarta.persistence.Column
import jakarta.persistence.Entity
import jakarta.persistence.FetchType
import jakarta.persistence.GeneratedValue
import jakarta.persistence.GenerationType
import jakarta.persistence.Id
import jakarta.persistence.JoinColumn
import jakarta.persistence.ManyToOne
import jakarta.persistence.Table
import org.hibernate.annotations.CreationTimestamp
import java.time.Instant
import java.time.ZoneOffset

/**
 * Zgłoszenie rekrutacyjne. `position == null` = aplikacja spontaniczna.
 * `futureRecruitmentConsent*` — zgoda GDPR na przyszłe procesy rekrutacyjne,
 * ważna [FUTURE_CONSENT_MONTHS] miesięcy od udzielenia (odpowiednik
 * `JobApplication::futureConsentExpiresAt()`/`futureConsentActive()`).
 */
@Entity
@Table(name = "job_applications")
class JobApplication(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    val id: Long? = null,

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "job_position_id")
    var position: JobPosition? = null,

    @Column(nullable = false)
    var name: String,

    @Column(nullable = false)
    var email: String,

    @Column
    var phone: String? = null,

    @Column(columnDefinition = "text")
    var message: String? = null,

    @Column(name = "cv_path")
    var cvPath: String? = null,

    @Column(name = "cv_original_name")
    var cvOriginalName: String? = null,

    @Column(name = "is_read", nullable = false)
    var isRead: Boolean = false,

    @Column(nullable = false)
    var status: JobApplicationStatus = JobApplicationStatus.PENDING,

    @Column(name = "future_recruitment_consent", nullable = false)
    var futureRecruitmentConsent: Boolean = false,

    @Column(name = "future_recruitment_consent_at")
    var futureRecruitmentConsentAt: Instant? = null,

    @CreationTimestamp
    @Column(name = "created_at", nullable = false, updatable = false)
    val createdAt: Instant? = null,
) {
    /** Arytmetyka kalendarzowa (miesiące, nie dni stałej długości) — jak `Carbon::addMonths()`. */
    fun futureConsentExpiresAt(): Instant? {
        if (!futureRecruitmentConsent || futureRecruitmentConsentAt == null) return null
        return futureRecruitmentConsentAt!!.atZone(ZoneOffset.UTC).plusMonths(FUTURE_CONSENT_MONTHS.toLong()).toInstant()
    }

    fun futureConsentActive(): Boolean {
        val expiry = futureConsentExpiresAt() ?: return false
        return expiry.isAfter(Instant.now())
    }

    companion object {
        const val FUTURE_CONSENT_MONTHS = 24
    }
}
