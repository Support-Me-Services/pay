package com.supportme.pay.coresvc.initcode

import org.springframework.data.jpa.repository.JpaRepository
import java.util.Optional

interface InitCodeRepository : JpaRepository<InitCode, Long> {
    fun findByUuidAndActiveTrue(uuid: String): Optional<InitCode>
    fun findByOrganizationId(organizationId: Long): List<InitCode>
    fun findByOwnerUserId(ownerUserId: Long): List<InitCode>
}
