package com.supportme.pay.storefront.domain.repository

import org.springframework.data.jpa.repository.Query
import org.springframework.data.repository.query.Param
import com.supportme.pay.storefront.domain.entity.Event
import org.springframework.data.jpa.repository.JpaRepository
import java.time.Instant

/** Nazwa nie może kolidować z `GatewayEventRepository` — Spring nazywa bean po prostej nazwie klasy, nie FQN. */
interface StorefrontEventRepository : JpaRepository<Event, Long> {
    /** Dla `ShopStatsService::summary()`. `productId` = -1 = "brak filtra" (sentinel). */
    @Query(
        "select e.type, count(e) from Event e where " +
            "(:productId = -1 or e.product.id = :productId) and e.createdAt >= :since group by e.type",
    )
    fun countByTypeGroupedSince(@Param("productId") productId: Long, @Param("since") since: Instant): List<Array<Any>>
}
