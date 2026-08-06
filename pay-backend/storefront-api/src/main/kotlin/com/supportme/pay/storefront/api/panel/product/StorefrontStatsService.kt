package com.supportme.pay.storefront.api.panel.product

import com.supportme.pay.storefront.domain.entity.OrderStatus
import com.supportme.pay.storefront.domain.entity.StorefrontEventType
import com.supportme.pay.storefront.domain.repository.OrderRepository
import com.supportme.pay.storefront.domain.repository.StorefrontEventRepository
import org.springframework.stereotype.Service
import java.text.DecimalFormat
import java.text.DecimalFormatSymbols
import java.time.Instant
import java.time.ZoneOffset
import java.time.format.DateTimeFormatter
import java.util.Locale

data class ShopStatsSummary(
    val opens: Long,
    val views: Long,
    val clicks: Long,
    val purchases: Long,
    val revenueGrosze: Long,
    val revenuePln: String,
    val conversionPercent: Double,
)

data class DailyPurchasePoint(val label: String, val count: Long)

/** Port 1:1 z `App\Modules\Storefront\Services\ShopStatsService`. */
@Service
class StorefrontStatsService(
    private val eventRepository: StorefrontEventRepository,
    private val orderRepository: OrderRepository,
) {
    fun summary(productId: Long?, days: Int?): ShopStatsSummary {
        // Jak `Carbon::now()->subDays($days)->startOfDay()` w PHP — przycięte do
        // północy UTC (patrz też Gateway StatsService, ten sam wzorzec/pułapka).
        val since = days?.let { todayMidnightUtc().minus(it.toLong(), java.time.temporal.ChronoUnit.DAYS) } ?: Instant.EPOCH
        val productFilter = productId ?: NO_FILTER

        val eventCounts = eventRepository.countByTypeGroupedSince(productFilter, since)
            .associate { (it[0] as StorefrontEventType) to (it[1] as Long) }

        val opens = eventCounts[StorefrontEventType.TAG_OPEN] ?: 0L
        val views = eventCounts[StorefrontEventType.PAGE_VIEW] ?: 0L
        val clicks = eventCounts[StorefrontEventType.BUY_CLICK] ?: 0L
        val purchases = orderRepository.countByStatusSince(OrderStatus.PAID, productFilter, since)
        val revenue = orderRepository.sumAmountByStatusSince(OrderStatus.PAID, productFilter, since)

        val conversion = if (opens > 0) (purchases.toDouble() / opens.toDouble() * 100.0) else 0.0

        return ShopStatsSummary(
            opens = opens,
            views = views,
            clicks = clicks,
            purchases = purchases,
            revenueGrosze = revenue,
            revenuePln = formatPln(revenue),
            conversionPercent = Math.round(conversion * 10) / 10.0,
        )
    }

    fun dailyPurchases(productId: Long?, days: Int = 30): List<DailyPurchasePoint> {
        val sinceDate = todayMidnightUtc().atZone(ZoneOffset.UTC).toLocalDate().minusDays((days - 1).toLong())
        val since = sinceDate.atStartOfDay(ZoneOffset.UTC).toInstant()

        val rows = orderRepository.dailyPaidCounts(productId ?: NO_FILTER, since)
            .associate { row ->
                val day = (row[0] as Instant).atZone(ZoneOffset.UTC).toLocalDate()
                day to (row[1] as Number).toLong()
            }

        val labelFormat = DateTimeFormatter.ofPattern("dd.MM")
        return (0 until days).map { offset ->
            val date = sinceDate.plusDays(offset.toLong())
            DailyPurchasePoint(label = date.format(labelFormat), count = rows[date] ?: 0L)
        }
    }

    private fun todayMidnightUtc(): Instant = Instant.now().atZone(ZoneOffset.UTC).toLocalDate().atStartOfDay(ZoneOffset.UTC).toInstant()

    companion object {
        private const val NO_FILTER = -1L

        private val PLN_FORMAT = DecimalFormat(
            "#,##0.00",
            DecimalFormatSymbols(Locale.of("pl", "PL")).apply {
                groupingSeparator = ' '
                decimalSeparator = ','
            },
        )

        fun formatPln(grosze: Long): String = "${PLN_FORMAT.format(grosze / 100.0)} zł"
    }
}
