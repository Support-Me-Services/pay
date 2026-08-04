package com.supportme.pay.storefront.domain.entity

import jakarta.persistence.Column
import jakarta.persistence.Convert
import jakarta.persistence.Entity
import jakarta.persistence.GeneratedValue
import jakarta.persistence.GenerationType
import jakarta.persistence.Id
import jakarta.persistence.Table
import org.hibernate.annotations.CreationTimestamp
import java.time.Instant

@Entity
@Table(name = "salespeople")
class Salesperson(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    val id: Long? = null,

    @Column(nullable = false)
    var name: String,

    @Column
    var email: String? = null,

    @Column
    var phone: String? = null,

    @Convert(converter = StringListJsonConverter::class)
    @Column(columnDefinition = "text")
    var voivodeships: List<String>? = null,

    @Column(nullable = false)
    var active: Boolean = true,

    @CreationTimestamp
    @Column(name = "created_at", nullable = false, updatable = false)
    val createdAt: Instant? = null,
) {
    companion object {
        /** Lista województw RP — jak `Salesperson::VOIVODESHIPS` w PHP. */
        val VOIVODESHIPS = listOf(
            "dolnośląskie", "kujawsko-pomorskie", "lubelskie", "lubuskie",
            "łódzkie", "małopolskie", "mazowieckie", "opolskie",
            "podkarpackie", "podlaskie", "pomorskie", "śląskie",
            "świętokrzyskie", "warmińsko-mazurskie", "wielkopolskie", "zachodniopomorskie",
        )
    }
}
