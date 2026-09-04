package com.supportme.pay.coresvc.initcode

import jakarta.persistence.Column
import jakarta.persistence.Entity
import jakarta.persistence.GeneratedValue
import jakarta.persistence.GenerationType
import jakarta.persistence.Id
import jakarta.persistence.Table
import org.hibernate.annotations.CreationTimestamp
import org.hibernate.annotations.UpdateTimestamp
import java.time.OffsetDateTime

/**
 * Kod inicjalizacji kontaktu (tag NFC / kod QR) — pierwsza prawdziwa
 * domena core-svc. Właściciel = dokładnie jedno z [organizationId] /
 * [ownerUserId] (egzekwowane przez CHECK w schemacie, patrz
 * V1__create_init_codes.sql, i ponownie przez [InitCodeGrpcService] przy
 * każdej mutacji — obrona w głąb, nie polegamy wyłącznie na bazie).
 */
@Entity
@Table(name = "init_codes")
class InitCode(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    val id: Long = 0,

    @Column(nullable = false, unique = true, length = 36)
    val uuid: String,

    @Column(nullable = false)
    var label: String,

    @Column(name = "organization_id")
    var organizationId: Long? = null,

    @Column(name = "owner_user_id")
    var ownerUserId: Long? = null,

    @Column(name = "shop_item_id")
    var shopItemId: Long? = null,

    @Column(name = "target_organization_id")
    var targetOrganizationId: Long? = null,

    @Column(nullable = false)
    var active: Boolean = true,

    @CreationTimestamp
    @Column(name = "created_at", nullable = false, updatable = false)
    var createdAt: OffsetDateTime? = null,

    @UpdateTimestamp
    @Column(name = "updated_at", nullable = false)
    var updatedAt: OffsetDateTime? = null,
)
