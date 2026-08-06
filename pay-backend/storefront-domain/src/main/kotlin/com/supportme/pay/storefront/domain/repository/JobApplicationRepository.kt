package com.supportme.pay.storefront.domain.repository

import com.supportme.pay.storefront.domain.entity.JobApplication
import org.springframework.data.jpa.repository.JpaRepository
import org.springframework.data.jpa.repository.Query
import org.springframework.data.domain.Page
import org.springframework.data.domain.Pageable
import java.time.Instant

interface JobApplicationRepository : JpaRepository<JobApplication, Long> {
    /** Odpowiednik `JobApplication::scopeActiveFutureConsent` — zgoda aktywna, jeszcze w oknie 24 mies. */
    @Query(
        "select a from JobApplication a where a.futureRecruitmentConsent = true " +
            "and a.futureRecruitmentConsentAt is not null and a.futureRecruitmentConsentAt > :expiredBefore " +
            "order by a.futureRecruitmentConsentAt desc",
    )
    fun findActiveFutureConsent(expiredBefore: Instant, pageable: Pageable): Page<JobApplication>

    /** Jak PHP `consents()` — BEZ limitu/paginacji (`->get()`), nie ucina listy po 200. */
    @Query(
        "select a from JobApplication a where a.futureRecruitmentConsent = true " +
            "and a.futureRecruitmentConsentAt is not null and a.futureRecruitmentConsentAt > :expiredBefore " +
            "order by a.futureRecruitmentConsentAt desc",
    )
    fun findActiveFutureConsent(expiredBefore: Instant): List<JobApplication>
}
