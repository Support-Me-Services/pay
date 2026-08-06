package com.supportme.pay.gateway.api.panel.stats

import com.supportme.pay.gateway.domain.entity.EventType
import com.supportme.pay.gateway.domain.entity.TransactionStatus
import com.supportme.pay.gateway.domain.repository.GatewayEventRepository
import com.supportme.pay.gateway.domain.repository.TransactionRepository
import org.springframework.stereotype.Service
import java.text.DecimalFormat
import java.text.DecimalFormatSymbols
import java.time.Instant
import java.time.ZoneOffset
import java.time.format.DateTimeFormatter
import java.util.Locale

data class StatsSummary(
    val opens: Long,
    val started: Long,
    val paid: Long,
    val failed: Long,
    val revenueGrosze: Long,
    val revenuePln: String,
    val conversionPercent: Double,
)

data class DailyPoint(val label: String, val paidCount: Long, val revenuePln: Double)

/** Port 1:1 z `App\Modules\Gateway\Services\StatsService`. */
@Service
class StatsService(
    private val gatewayEventRepository: GatewayEventRepository,
    private val transactionRepository: TransactionRepository,
) {
    fun summary(shopId: Long?, tagId: Long?, days: Int?): StatsSummary {
        // Jak `Carbon::now()->subDays($days)->startOfDay()` w PHP — przycięte do
        // północy UTC, NIE dokładny czas (inaczej okno "30 dni" dryfuje wg
        // godziny wywołania endpointu).
        val since = days?.let { todayMidnightUtc().minus(it.toLong(), java.time.temporal.ChronoUnit.DAYS) } ?: Instant.EPOCH
        val shopFilter = shopId ?: NO_FILTER
        val tagFilter = tagId ?: NO_FILTER

        val eventCounts = gatewayEventRepository.countByTypeGrouped(shopFilter, tagFilter, since)
            .associate { (it[0] as EventType) to (it[1] as Long) }

        val opens = eventCounts[EventType.TAG_OPEN] ?: 0L
        val started = eventCounts[EventType.PAYMENT_STARTED] ?: 0L
        val failed = eventCounts[EventType.PAYMENT_FAILED] ?: 0L
        val paid = transactionRepository.countByStatus(TransactionStatus.PAID, shopFilter, tagFilter, since)
        val revenue = transactionRepository.sumAmountByStatus(TransactionStatus.PAID, shopFilter, tagFilter, since)

        val conversion = if (opens > 0) (paid.toDouble() / opens.toDouble() * 100.0) else 0.0

        return StatsSummary(
            opens = opens,
            started = started,
            paid = paid,
            failed = failed,
            revenueGrosze = revenue,
            revenuePln = formatPln(revenue),
            conversionPercent = Math.round(conversion * 10) / 10.0,
        )
    }

    fun dailyPaidSeries(shopId: Long?, tagId: Long?, days: Int = 30): List<DailyPoint> {
        // Jak `Carbon::now()->subDays($days - 1)->startOfDay()` w PHP — UWAGA na
        // `days - 1`, NIE `days` (żeby okno obejmowało dokładnie `days` dni
        // KALENDARZOWYCH łącznie z dniem dzisiejszym, nie o jeden za dużo).
        val sinceDate = todayMidnightUtc().atZone(ZoneOffset.UTC).toLocalDate().minusDays((days - 1).toLong())
        val since = sinceDate.atStartOfDay(ZoneOffset.UTC).toInstant()

        val rows = transactionRepository.dailyPaidStats(shopId ?: NO_FILTER, tagId ?: NO_FILTER, since)
            .associate { row ->
                // Hibernate 6 + pgjdbc zwraca TIMESTAMPTZ z natywnego zapytania jako
                // java.time.Instant (nie java.sql.Timestamp) — realny bug złapany
                // przy weryfikacji (ClassCastException).
                val day = (row[0] as Instant).atZone(ZoneOffset.UTC).toLocalDate()
                val cnt = (row[1] as Number).toLong()
                val total = (row[2] as Number).toLong()
                day to (cnt to total)
            }

        // "dd.MM" (zero-padded) jak PHP `format('d.m')` — NIE "d.M" (bez zer, "6.8").
        val labelFormat = DateTimeFormatter.ofPattern("dd.MM")
        return (0 until days).map { offset ->
            val date = sinceDate.plusDays(offset.toLong())
            val (cnt, total) = rows[date] ?: (0L to 0L)
            DailyPoint(label = date.format(labelFormat), paidCount = cnt, revenuePln = total / 100.0)
        }
    }

    private fun todayMidnightUtc(): Instant = Instant.now().atZone(ZoneOffset.UTC).toLocalDate().atStartOfDay(ZoneOffset.UTC).toInstant()

    companion object {
        /** "Brak filtra" — nigdy nie wiążemy `null` do parametru JDBC (patrz TransactionRepository/GatewayEventRepository). */
        private const val NO_FILTER = -1L

        private val PLN_FORMAT = DecimalFormat(
            "#,##0.00",
            DecimalFormatSymbols(Locale.of("pl", "PL")).apply {
                groupingSeparator = ' '
                decimalSeparator = ','
            },
        )

        /** Jak `StatsService::formatPln()` w PHP: `number_format($grosze/100, 2, ',', ' ') . ' zł'`. */
        fun formatPln(grosze: Long): String = "${PLN_FORMAT.format(grosze / 100.0)} zł"
    }
}
