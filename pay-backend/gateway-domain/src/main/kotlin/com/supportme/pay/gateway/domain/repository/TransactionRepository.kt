package com.supportme.pay.gateway.domain.repository

import com.supportme.pay.gateway.domain.entity.Shop
import com.supportme.pay.gateway.domain.entity.Transaction
import com.supportme.pay.gateway.domain.entity.TransactionStatus
import org.springframework.data.jpa.repository.JpaRepository
import org.springframework.data.jpa.repository.Query
import org.springframework.data.repository.query.Param
import java.time.Instant
import java.util.UUID

interface TransactionRepository : JpaRepository<Transaction, UUID> {
    /** Odpowiednik `Transaction::where('shop_id', $shop->id)` — sklep widzi TYLKO swoje transakcje. */
    fun findByIdAndShop(id: UUID, shop: Shop): Transaction?

    /** Kandydaci do `payu:reconcile` — najstarsze pierwsze, jak w oryginalnym scheduled command. */
    fun findByStatusAndCreatedAtAfterOrderByCreatedAtAsc(status: TransactionStatus, createdAfter: Instant): List<Transaction>

    /** Dla `ActivationStatusController::paid_last_24h`. */
    fun countByStatusAndPaidAtAfter(status: TransactionStatus, paidAfter: Instant): Long

    /** Dla `ActivationStatusController::pending_last_24h`. */
    fun countByStatusAndCreatedAtAfter(status: TransactionStatus, createdAfter: Instant): Long

    /**
     * Odpowiednik `StatsService::summary()`. `shopId`/`tagId` = -1 i `since`
     * = `Instant.EPOCH` = "brak filtra" (sentinel, NIE `null` — patrz
     * [GatewayEventRepository.countByTypeGrouped] po co).
     */
    @Query(
        "select count(t) from Transaction t where t.status = :status and " +
            "(:shopId = -1 or t.shop.id = :shopId) and " +
            "(:tagId = -1 or t.tag.id = :tagId) and " +
            "t.paidAt >= :since",
    )
    fun countByStatus(
        @Param("status") status: TransactionStatus,
        @Param("shopId") shopId: Long,
        @Param("tagId") tagId: Long,
        @Param("since") since: Instant,
    ): Long

    @Query(
        "select coalesce(sum(t.amount), 0) from Transaction t where t.status = :status and " +
            "(:shopId = -1 or t.shop.id = :shopId) and " +
            "(:tagId = -1 or t.tag.id = :tagId) and " +
            "t.paidAt >= :since",
    )
    fun sumAmountByStatus(
        @Param("status") status: TransactionStatus,
        @Param("shopId") shopId: Long,
        @Param("tagId") tagId: Long,
        @Param("since") since: Instant,
    ): Long

    /** Odpowiednik `StatsService::dailyPaidSeries()` — dzienny szereg (dzień, liczba, suma grosze), tylko dni z danymi. */
    @Query(
        value = "select date_trunc('day', paid_at) as day, count(*) as cnt, coalesce(sum(amount), 0) as total " +
            "from transactions where status = 'paid' and paid_at >= :since " +
            "and (:shopId = -1 or shop_id = :shopId) and (:tagId = -1 or tag_id = :tagId) " +
            "group by day order by day",
        nativeQuery = true,
    )
    fun dailyPaidStats(
        @Param("shopId") shopId: Long,
        @Param("tagId") tagId: Long,
        @Param("since") since: Instant,
    ): List<Array<Any>>

    fun countByShop(shop: Shop): Long
}
