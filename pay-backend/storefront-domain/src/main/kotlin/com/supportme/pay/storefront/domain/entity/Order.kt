package com.supportme.pay.storefront.domain.entity

import jakarta.persistence.Column
import jakarta.persistence.Entity
import jakarta.persistence.FetchType
import jakarta.persistence.GeneratedValue
import jakarta.persistence.GenerationType
import jakarta.persistence.Id
import jakarta.persistence.JoinColumn
import jakarta.persistence.ManyToOne
import jakarta.persistence.Table
import org.hibernate.annotations.CreationTimestamp
import java.time.Instant
import java.util.UUID

/**
 * Jeden model zamówienia dla WSZYSTKICH trzech ścieżek zakupu (parafia/Taca,
 * sklep firmowy, koszyk per-user) — `product` jest NULL dla ostatnich dwóch
 * (nie są związane z pojedynczym `Product`). `transactionId` wskazuje na
 * `Transaction` w module Gateway (inna baza) — celowo BEZ FK/relacji JPA,
 * dostęp wyłącznie przez `GatewayClient` REST (Faza 4).
 */
@Entity
@Table(name = "orders")
class Order(
    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    val id: UUID? = null,

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "product_id")
    var product: Product? = null,

    @Column(name = "transaction_id")
    var transactionId: UUID? = null,

    /** Grosze. */
    @Column(nullable = false)
    var amount: Int,

    @Column(nullable = false)
    var status: OrderStatus = OrderStatus.PENDING,

    @Column(name = "paid_at")
    var paidAt: Instant? = null,

    @CreationTimestamp
    @Column(name = "created_at", nullable = false, updatable = false)
    val createdAt: Instant? = null,
)
