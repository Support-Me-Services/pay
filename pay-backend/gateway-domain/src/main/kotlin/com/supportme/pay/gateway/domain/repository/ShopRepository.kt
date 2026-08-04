package com.supportme.pay.gateway.domain.repository

import com.supportme.pay.gateway.domain.entity.Shop
import org.springframework.data.jpa.repository.JpaRepository

interface ShopRepository : JpaRepository<Shop, Long> {
    /** Odpowiednik `AuthenticateApiKey` middleware — lookup po nagłówku `X-Api-Key`. */
    fun findByApiKey(apiKey: String): Shop?

    fun findBySlug(slug: String): Shop?
}
