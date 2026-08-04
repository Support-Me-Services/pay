package com.supportme.pay.gateway.domain.repository

import com.supportme.pay.gateway.domain.entity.Shop
import com.supportme.pay.gateway.domain.entity.Transaction
import com.supportme.pay.gateway.domain.entity.TransactionStatus
import org.springframework.data.jpa.repository.JpaRepository
import java.time.Instant
import java.util.UUID

interface TransactionRepository : JpaRepository<Transaction, UUID> {
    /** Odpowiednik `Transaction::where('shop_id', $shop->id)` — sklep widzi TYLKO swoje transakcje. */
    fun findByIdAndShop(id: UUID, shop: Shop): Transaction?

    /** Kandydaci do `payu:reconcile` — najstarsze pierwsze, jak w oryginalnym scheduled command. */
    fun findByStatusAndCreatedAtAfterOrderByCreatedAtAsc(status: TransactionStatus, createdAfter: Instant): List<Transaction>
}
