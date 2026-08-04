package com.supportme.pay.storefront.domain.entity

import jakarta.persistence.Column
import jakarta.persistence.Entity
import jakarta.persistence.GeneratedValue
import jakarta.persistence.GenerationType
import jakarta.persistence.Id
import jakarta.persistence.Table
import org.hibernate.annotations.CreationTimestamp
import org.hibernate.annotations.UpdateTimestamp
import java.time.Instant

/**
 * Odpowiednik `App\Models\User` — konto panelu (Storefront). `handle` to slug
 * sklepu użytkownika pod `/people/{handle}` ([[ShopItem.userId]]), generowany
 * automatycznie z nazwy jeśli puste (logika w warstwie serwisowej Fazy 2/4,
 * nie w encji — odpowiednik `User::uniqueHandle()`).
 */
@Entity
@Table(name = "users")
class User(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    val id: Long? = null,

    @Column(nullable = false)
    var name: String,

    @Column(unique = true)
    var handle: String? = null,

    @Column(nullable = false, unique = true)
    var email: String,

    @Column(name = "email_verified_at")
    var emailVerifiedAt: Instant? = null,

    /** Hash bcrypt — kompatybilny z Laravel `'hashed'` cast (też bcrypt). */
    @Column(nullable = false)
    var password: String,

    @Column(name = "remember_token", length = 100)
    var rememberToken: String? = null,

    @CreationTimestamp
    @Column(name = "created_at", nullable = false, updatable = false)
    val createdAt: Instant? = null,

    @UpdateTimestamp
    @Column(name = "updated_at", nullable = false)
    var updatedAt: Instant? = null,
)
