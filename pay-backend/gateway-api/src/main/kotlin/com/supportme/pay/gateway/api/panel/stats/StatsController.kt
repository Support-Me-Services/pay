package com.supportme.pay.gateway.api.panel.stats

import com.supportme.pay.gateway.domain.repository.ShopRepository
import com.supportme.pay.gateway.domain.repository.TagRepository
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RequestParam
import org.springframework.web.bind.annotation.RestController

data class ShopOption(val id: Long, val name: String)
data class TagOption(val id: Long, val tagUid: String, val label: String?)

data class StatsPageResponse(
    val total: StatsSummary,
    val last30Days: StatsSummary,
    val dailySeries: List<DailyPoint>,
    val shops: List<ShopOption>,
    val tags: List<TagOption>,
)

/** Odpowiednik `Panel\StatsController` — filtrowalny wg `shopId`/`tagId` (tag tylko jeśli należy do wybranego sklepu). */
@RestController
@RequestMapping("/api/gateway/panel/stats")
class StatsController(
    private val shopRepository: ShopRepository,
    private val tagRepository: TagRepository,
    private val statsService: StatsService,
) {

    @GetMapping
    fun index(@RequestParam(required = false) shopId: Long?, @RequestParam(required = false) tagId: Long?): StatsPageResponse {
        val shop = shopId?.let { shopRepository.findById(it).orElse(null) }
        // Tag tylko jeśli sklep jest też wybrany i tag do niego należy (jak w oryginale).
        val tag = if (shop != null) tagId?.let { tagRepository.findByIdAndShop(it, shop) } else null

        return StatsPageResponse(
            total = statsService.summary(shop?.id, tag?.id, null),
            last30Days = statsService.summary(shop?.id, tag?.id, 30),
            dailySeries = statsService.dailyPaidSeries(shop?.id, tag?.id, 30),
            shops = shopRepository.findAllByOrderById().map { ShopOption(it.id!!, it.name) },
            tags = shop?.let { tagRepository.findAllByShopOrderById(it).map { t -> TagOption(t.id!!, t.tagUid, t.label) } } ?: emptyList(),
        )
    }
}
