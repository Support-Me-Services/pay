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
import org.hibernate.annotations.UpdateTimestamp
import java.math.BigDecimal
import java.time.Instant

/**
 * Lead CRM (cold-outreach) — dataset ~20 tys. parafii z OpenStreetMap
 * (import: `parishes:import`/`parishes:enrich`, poza zakresem tego portu —
 * osobne narzędzie developerskie, nie endpoint aplikacji).
 */
@Entity
@Table(name = "potential_parishes")
class PotentialParish(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    val id: Long? = null,

    @Column(nullable = false)
    var name: String,

    @Column
    var city: String? = null,

    @Column
    var address: String? = null,

    @Column
    var voivodeship: String? = null,

    @Column
    var denomination: String? = null,

    @Column
    var phone: String? = null,

    @Column(nullable = false, precision = 10, scale = 7)
    var lat: BigDecimal,

    @Column(nullable = false, precision = 10, scale = 7)
    var lon: BigDecimal,

    @Column(nullable = false)
    var status: PotentialParishStatus = PotentialParishStatus.NOWA,

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "salesperson_id")
    var salesperson: Salesperson? = null,

    @Column(columnDefinition = "text")
    var note: String? = null,

    @Column(name = "called_at")
    var calledAt: Instant? = null,

    @CreationTimestamp
    @Column(name = "created_at", nullable = false, updatable = false)
    val createdAt: Instant? = null,

    @UpdateTimestamp
    @Column(name = "updated_at", nullable = false)
    var updatedAt: Instant? = null,
)
