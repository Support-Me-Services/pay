package com.supportme.pay.storefront.domain.repository

import com.supportme.pay.storefront.domain.entity.JobPosition
import org.springframework.data.jpa.repository.JpaRepository

interface JobPositionRepository : JpaRepository<JobPosition, Long> {
    fun findAllByActiveTrueOrderBySortAscIdAsc(): List<JobPosition>
}
