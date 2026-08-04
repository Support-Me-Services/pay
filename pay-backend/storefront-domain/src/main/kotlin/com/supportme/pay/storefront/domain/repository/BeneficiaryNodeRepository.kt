package com.supportme.pay.storefront.domain.repository

import com.supportme.pay.storefront.domain.entity.BeneficiaryNode
import org.springframework.data.jpa.repository.JpaRepository

interface BeneficiaryNodeRepository : JpaRepository<BeneficiaryNode, Long> {
    fun findAllByActiveTrueOrderByPositionAscIdAsc(): List<BeneficiaryNode>

    fun findAllByOrderByPositionAscIdAsc(): List<BeneficiaryNode>
}
