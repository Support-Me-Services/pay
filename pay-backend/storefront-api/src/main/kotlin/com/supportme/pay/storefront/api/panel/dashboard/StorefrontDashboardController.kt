package com.supportme.pay.storefront.api.panel.dashboard

import com.supportme.pay.storefront.api.common.PricePlnFormatter
import com.supportme.pay.storefront.domain.entity.OrderStatus
import com.supportme.pay.storefront.domain.repository.ContactMessageRepository
import com.supportme.pay.storefront.domain.repository.OrderRepository
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController
import java.time.Instant
import java.time.ZoneOffset
import java.time.format.DateTimeFormatter
import java.time.temporal.ChronoUnit

data class StorefrontStatsSummary(val purchases: Long, val revenuePln: String)
data class StorefrontDailyPoint(val label: String, val purchases: Long, val revenuePln: Double)
data class StorefrontDashboardResponse(val allTime: StorefrontStatsSummary, val last30Days: StorefrontStatsSummary, val dailySeries: List<StorefrontDailyPoint>, val unreadMessages: Long)

/**
 * Odpowiednik `Panel\DashboardController` (`ShopStatsService::summary`) —
 * agregacja w pamięci (skala danych na tym etapie nieduża); do rozważenia
 * przejście na zapytania agregujące w Fazie 5/6, z tymi samymi środkami
 * ostrożności co w StatsService Gateway (wartości-wartowniki zamiast null).
 */
@RestController
@RequestMapping("/api/storefront/panel/dashboard")
class StorefrontDashboardController(
    private val orderRepository: OrderRepository,
    private val contactMessageRepository: ContactMessageRepository,
) {

    @GetMapping
    fun index(): StorefrontDashboardResponse {
        val paidOrders = orderRepository.findAll().filter { it.status == OrderStatus.PAID }
        val since30 = Instant.now().minus(30, ChronoUnit.DAYS)

        val allTime = summarize(paidOrders)
        val last30 = summarize(paidOrders.filter { (it.paidAt ?: Instant.EPOCH).isAfter(since30) })

        val labelFormat = DateTimeFormatter.ofPattern("d.M")
        val byDay = paidOrders.filter { it.paidAt != null }
            .groupBy { it.paidAt!!.atZone(ZoneOffset.UTC).toLocalDate() }

        val series = (29 downTo 0).map { offset ->
            val date = Instant.now().atZone(ZoneOffset.UTC).toLocalDate().minusDays(offset.toLong())
            val dayOrders = byDay[date] ?: emptyList()
            StorefrontDailyPoint(date.format(labelFormat), dayOrders.size.toLong(), dayOrders.sumOf { it.amount } / 100.0)
        }

        val unread = contactMessageRepository.findAll().count { !it.isRead }.toLong()

        return StorefrontDashboardResponse(allTime, last30, series, unread)
    }

    private fun summarize(orders: List<com.supportme.pay.storefront.domain.entity.Order>): StorefrontStatsSummary {
        val revenue = orders.sumOf { it.amount }
        return StorefrontStatsSummary(orders.size.toLong(), PricePlnFormatter.format(revenue) + " zł")
    }
}
