package com.supportme.pay.gateway.domain.entity

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
 * Audyt/funnel (opens -> started -> paid/failed). `transactionId` jest
 * CELOWO plain UUID bez `@ManyToOne`/FK — jak w oryginale (luźne powiązanie,
 * eventy loguje się nawet gdy transakcja teoretycznie nie istnieje).
 */
@Entity
@Table(name = "events")
class Event(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    val id: Long? = null,

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "shop_id", nullable = false)
    var shop: Shop,

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "tag_id")
    var tag: Tag? = null,

    @Column(name = "transaction_id")
    var transactionId: UUID? = null,

    @Column(nullable = false)
    var type: EventType,

    @CreationTimestamp
    @Column(name = "created_at", nullable = false, updatable = false)
    val createdAt: Instant? = null,
)
