package com.supportme.pay.gateway.domain.entity

import jakarta.persistence.Column
import jakarta.persistence.Entity
import jakarta.persistence.GeneratedValue
import jakarta.persistence.GenerationType
import jakarta.persistence.Id
import jakarta.persistence.Table
import org.hibernate.annotations.CreationTimestamp
import java.time.Instant

/**
 * Odpowiednik modelu `Shop` (`app/Modules/Gateway/Models/Shop.php`) — merchant
 * integrujący się z bramką. `apiKey` jest plaintextem w kolumnie (zgodnie
 * z dzisiejszym zachowaniem — nie dodajemy hashowania/rotacji teraz), warstwa
 * REST/DTO nigdy go nie zwraca poza momentem utworzenia.
 */
@Entity
@Table(name = "shops")
class Shop(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    val id: Long? = null,

    @Column(nullable = false)
    var name: String,

    @Column(nullable = false, unique = true)
    var slug: String,

    @Column(name = "base_url", nullable = false)
    var baseUrl: String,

    @Column(name = "api_key", nullable = false, unique = true, length = 64)
    var apiKey: String,

    @Column(name = "payment_mode", nullable = false)
    var paymentMode: PaymentMode = PaymentMode.CLASSIC,

    @CreationTimestamp
    @Column(name = "created_at", nullable = false, updatable = false)
    val createdAt: Instant? = null,
)
