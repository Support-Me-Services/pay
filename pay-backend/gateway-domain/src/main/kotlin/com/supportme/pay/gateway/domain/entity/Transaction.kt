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
 * Odpowiednik modelu `Transaction` (`HasUuids`) — rdzeń cyklu życia płatności.
 * `mode` dziedziczony ze `Shop.paymentMode` w momencie utworzenia (patrz
 * Faza 3, `TransactionService`) i NIE zmienia się retroaktywnie.
 */
@Entity
@Table(name = "transactions")
class Transaction(
    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    val id: UUID? = null,

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "shop_id", nullable = false)
    var shop: Shop,

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "tag_id")
    var tag: Tag? = null,

    @Column(name = "product_external_id", nullable = false)
    var productExternalId: String,

    @Column(name = "product_name", nullable = false)
    var productName: String,

    /** Grosze (najmniejsza jednostka PLN) — jak w PHP, nigdy złotówki jako float. */
    @Column(nullable = false)
    var amount: Int,

    @Column(nullable = false, length = 3)
    var currency: String = "PLN",

    @Column(nullable = false)
    var status: TransactionStatus = TransactionStatus.CREATED,

    @Column(nullable = false)
    var mode: PaymentMode,

    @Column(name = "return_url", nullable = false, length = 500)
    var returnUrl: String,

    @Column(name = "notify_url", length = 500)
    var notifyUrl: String? = null,

    @Column(name = "provider_order_id")
    var providerOrderId: String? = null,

    @Column(name = "provider_redirect_url", length = 1000)
    var providerRedirectUrl: String? = null,

    @Column(name = "paid_at")
    var paidAt: Instant? = null,

    @CreationTimestamp
    @Column(name = "created_at", nullable = false, updatable = false)
    val createdAt: Instant? = null,
) {
    /** Odpowiednik `Transaction::isFinal()`. */
    fun isFinal(): Boolean = status.isFinal()
}
