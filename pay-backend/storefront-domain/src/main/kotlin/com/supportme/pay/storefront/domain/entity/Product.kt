package com.supportme.pay.storefront.domain.entity

import jakarta.persistence.Column
import jakarta.persistence.Entity
import jakarta.persistence.GeneratedValue
import jakarta.persistence.GenerationType
import jakarta.persistence.Id
import jakarta.persistence.Table
import org.hibernate.annotations.CreationTimestamp
import java.time.Instant

/**
 * Odpowiednik modelu `Product` — parafia/„Taca" (tryb church) z pipeline'em
 * CRM (`status`: kontakt→test→wdrozenie→aktywna — `active` przełącza się
 * tylko przy `aktywna`, logika w warstwie serwisowej Fazy 4).
 */
@Entity
@Table(name = "products")
class Product(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    val id: Long? = null,

    @Column(nullable = false)
    var name: String,

    @Column
    var city: String? = null,

    @Column
    var purpose: String? = null,

    @Column(nullable = false, unique = true)
    var slug: String,

    @Column(name = "description_html", columnDefinition = "text")
    var descriptionHtml: String? = null,

    @Column(name = "pickup_instruction", columnDefinition = "text")
    var pickupInstruction: String? = null,

    /** Grosze. */
    @Column(nullable = false)
    var price: Int,

    @Column(name = "tag_uid", nullable = false, unique = true)
    var tagUid: String,

    @Column(name = "main_image")
    var mainImage: String? = null,

    @Column(nullable = false)
    var active: Boolean = true,

    @Column
    var phone: String? = null,

    @Column
    var website: String? = null,

    @Column
    var voivodeship: String? = null,

    @Column(nullable = false)
    var status: ProductStatus = ProductStatus.KONTAKT,

    @CreationTimestamp
    @Column(name = "created_at", nullable = false, updatable = false)
    val createdAt: Instant? = null,
)
