package com.supportme.pay.storefront.domain.entity

import jakarta.persistence.Column
import jakarta.persistence.Entity
import jakarta.persistence.GeneratedValue
import jakarta.persistence.GenerationType
import jakarta.persistence.Id
import jakarta.persistence.Table
import org.hibernate.annotations.CreationTimestamp
import java.time.Instant

@Entity
@Table(name = "job_positions")
class JobPosition(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    val id: Long? = null,

    @Column(nullable = false)
    var title: String,

    @Column
    var location: String? = null,

    @Column(name = "employment_type")
    var employmentType: String? = null,

    @Column(name = "description_html", columnDefinition = "text")
    var descriptionHtml: String? = null,

    @Column(nullable = false)
    var active: Boolean = true,

    @Column(nullable = false)
    var sort: Int = 0,

    @CreationTimestamp
    @Column(name = "created_at", nullable = false, updatable = false)
    val createdAt: Instant? = null,
)
