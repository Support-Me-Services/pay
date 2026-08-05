package com.supportme.pay.gateway.api.panel.stats

import com.supportme.pay.gateway.domain.repository.ShopRepository
import com.supportme.pay.gateway.domain.repository.TagRepository
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController

data class ShopBreakdown(val id: Long, val name: String, val paymentMode: String, val tagsCount: Long, val revenuePln: String, val conversionPercent: Double)

data class DashboardResponse(
    val shopsCount: Long,
    val allTime: StatsSummary,
    val last30Days: StatsSummary,
    val dailySeries: List<DailyPoint>,
    val shops: List<ShopBreakdown>,
)

/** Odpowiednik `Panel\DashboardController`. */
@RestController
@RequestMapping("/api/gateway/panel/dashboard")
class GatewayDashboardController(
    private val shopRepository: ShopRepository,
    private val tagRepository: TagRepository,
    private val statsService: StatsService,
) {

    @GetMapping
    fun index(): DashboardResponse {
        val shops = shopRepository.findAllByOrderById()
        val breakdown = shops.map { shop ->
            val summary = statsService.summary(shop.id, null, null)
            ShopBreakdown(shop.id!!, shop.name, shop.paymentMode.dbValue, tagRepository.countByShop(shop), summary.revenuePln, summary.conversionPercent)
        }

        return DashboardResponse(
            shopsCount = shops.size.toLong(),
            allTime = statsService.summary(null, null, null),
            last30Days = statsService.summary(null, null, 30),
            dailySeries = statsService.dailyPaidSeries(null, null, 30),
            shops = breakdown,
        )
    }
}
