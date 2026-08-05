package com.supportme.pay.gateway.domain.repository

import com.supportme.pay.gateway.domain.entity.Event
import org.springframework.data.jpa.repository.JpaRepository
import org.springframework.data.jpa.repository.Query
import org.springframework.data.repository.query.Param
import java.time.Instant

/** Nazwa nie może kolidować z `StorefrontEventRepository` — Spring nazywa bean po prostej nazwie klasy, nie FQN. */
interface GatewayEventRepository : JpaRepository<Event, Long> {
    /**
     * Odpowiednik `StatsService::summary()` — liczby eventów per typ,
     * filtrowane opcjonalnie shop/tag/od-daty. `shopId`/`tagId` = -1 i
     * `since` = `Instant.EPOCH` oznaczają "brak filtra" (sentinel), NIE
     * `null` — Postgres nie potrafi wywnioskować typu parametru dla wzorca
     * `:param is null or field = :param`, bo Hibernate generuje DWA osobne
     * `?` dla powtórzonego parametru nazwanego, a samo `? is null` nie ma
     * kontekstu typu (realny błąd JDBC złapany przy weryfikacji: "could not
     * determine data type of parameter $5"). Wołający (StatsService) mapuje
     * `null` na te wartości-wartowniki.
     */
    @Query(
        "select e.type, count(e) from Event e where " +
            "(:shopId = -1 or e.shop.id = :shopId) and " +
            "(:tagId = -1 or e.tag.id = :tagId) and " +
            "e.createdAt >= :since " +
            "group by e.type",
    )
    fun countByTypeGrouped(
        @Param("shopId") shopId: Long,
        @Param("tagId") tagId: Long,
        @Param("since") since: Instant,
    ): List<Array<Any>>
}
