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
import java.time.Instant
import kotlin.math.roundToInt

/**
 * Produkt sklepu NFC per-konto (`/people/{handle}`). Cena STAŁA `price`
 * z fallbackiem do `minAmount` ([[priceGrosze]]) — logika "tylko jeden
 * is_default per user" (`applyDefault()` w PHP) żyje w warstwie serwisowej
 * Fazy 4, NIE tutaj (nie jest constraintem bazodanowym, tak jak w oryginale).
 */
@Entity
@Table(name = "shop_items")
class ShopItem(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    val id: Long? = null,

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "user_id")
    var owner: User? = null,

    @Column(nullable = false)
    var slug: String,

    @Column(nullable = false)
    var name: String,

    @Column
    var image: String? = null,

    /** Grosze — minimalna kwota (tryb darowiznowy na `/`). */
    @Column(name = "min_amount", nullable = false)
    var minAmount: Int,

    /** Grosze — cena stała (tryb sklepowy); NULL => fallback do [minAmount]. */
    @Column
    var price: Int? = null,

    @Column(columnDefinition = "text")
    var description: String? = null,

    @Column(name = "is_default", nullable = false)
    var isDefault: Boolean = false,

    @Column(name = "tag_uid", unique = true)
    var tagUid: String? = null,

    @Column(nullable = false)
    var active: Boolean = true,

    @Column(nullable = false)
    var sort: Int = 0,

    @CreationTimestamp
    @Column(name = "created_at", nullable = false, updatable = false)
    val createdAt: Instant? = null,

    @UpdateTimestamp
    @Column(name = "updated_at", nullable = false)
    var updatedAt: Instant? = null,
) {
    fun priceGrosze(): Int = price ?: minAmount

    fun pricePln(): Int = (priceGrosze() / 100.0).roundToInt()

    fun minAmountPln(): Int = (minAmount / 100.0).roundToInt()

    fun isSvg(): Boolean = image?.lowercase()?.endsWith(".svg") == true
}
