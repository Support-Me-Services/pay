package com.supportme.pay.storefront.api.pub

import com.supportme.pay.storefront.domain.repository.BeneficiaryNodeRepository
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.RestController

data class BeneficiaryNodeSummary(
    val heading: String,
    val image: String?,
    val imageSide: String,
    val imageScale: Int,
    val imageX: Int,
    val imageY: Int,
    val textAlign: String,
    val bodyHtml: String?,
)

/** Odpowiednik `BeneficiariesController::index` — read-only strona „Wspieramy". */
@RestController
class BeneficiariesController(private val beneficiaryNodeRepository: BeneficiaryNodeRepository) {

    @GetMapping("/beneficiaries")
    fun index(): List<BeneficiaryNodeSummary> = beneficiaryNodeRepository.findAllByActiveTrueOrderByPositionAscIdAsc().map {
        BeneficiaryNodeSummary(it.heading, it.image, it.imageSide.dbValue, it.imageScale, it.imageX, it.imageY, it.textAlign.dbValue, it.bodyHtml)
    }
}
