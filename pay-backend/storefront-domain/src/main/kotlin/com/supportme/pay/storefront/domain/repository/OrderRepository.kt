package com.supportme.pay.storefront.domain.repository

import com.supportme.pay.storefront.domain.entity.Order
import com.supportme.pay.storefront.domain.entity.OrderStatus
import org.springframework.data.jpa.repository.JpaRepository
import org.springframework.data.jpa.repository.Query
import org.springframework.data.repository.query.Param
import java.time.Instant
import java.util.UUID

interface OrderRepository : JpaRepository<Order, UUID> {
    fun findByTransactionId(transactionId: UUID): Order?

    /**
     * Dla `ShopStatsService::summary()`. `productId` = -1 = "brak filtra"
     * (sentinel, nie `null` — ten sam powód co w Gateway `TransactionRepository`).
     */
    @Query(
        "select count(o) from Order o where o.status = :status and " +
            "(:productId = -1 or o.product.id = :productId) and o.createdAt >= :since",
    )
    fun countByStatusSince(@Param("status") status: OrderStatus, @Param("productId") productId: Long, @Param("since") since: Instant): Long

    @Query(
        "select coalesce(sum(o.amount), 0) from Order o where o.status = :status and " +
            "(:productId = -1 or o.product.id = :productId) and o.createdAt >= :since",
    )
    fun sumAmountByStatusSince(@Param("status") status: OrderStatus, @Param("productId") productId: Long, @Param("since") since: Instant): Long

    /** Dla `ShopStatsService::dailyPurchases()` — dzienny szereg (dzień, liczba), tylko dni z danymi. */
    @Query(
        value = "select date_trunc('day', paid_at) as day, count(*) as cnt " +
            "from orders where status = 'paid' and paid_at >= :since " +
            "and (:productId = -1 or product_id = :productId) group by day order by day",
        nativeQuery = true,
    )
    fun dailyPaidCounts(@Param("productId") productId: Long, @Param("since") since: Instant): List<Array<Any>>
}
