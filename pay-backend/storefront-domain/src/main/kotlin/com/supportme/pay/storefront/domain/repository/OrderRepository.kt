package com.supportme.pay.storefront.domain.repository

import com.supportme.pay.storefront.domain.entity.Order
import org.springframework.data.jpa.repository.JpaRepository
import java.util.UUID

interface OrderRepository : JpaRepository<Order, UUID> {
    fun findByTransactionId(transactionId: UUID): Order?
}
