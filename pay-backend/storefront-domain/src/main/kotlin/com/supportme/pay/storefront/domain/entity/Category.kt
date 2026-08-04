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

/** Drzewo kategorii (sekcja „Kogo wspieramy?"), self-referential przez `parent`. */
@Entity
@Table(name = "categories")
class Category(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    val id: Long? = null,

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "parent_id")
    var parent: Category? = null,

    @Column(nullable = false, unique = true)
    var slug: String,

    @Column(nullable = false)
    var label: String,

    @Column(name = "label_html", columnDefinition = "text")
    var labelHtml: String? = null,

    @Column(name = "label_text", nullable = false)
    var labelText: String,

    @Column(columnDefinition = "text")
    var intro: String? = null,

    @Column
    var icon: String? = null,

    @Column(nullable = false)
    var source: CategorySource = CategorySource.NONE,

    @Column(nullable = false)
    var position: Int = 0,

    @Column(nullable = false)
    var active: Boolean = true,

    @CreationTimestamp
    @Column(name = "created_at", nullable = false, updatable = false)
    val createdAt: Instant? = null,

    @UpdateTimestamp
    @Column(name = "updated_at", nullable = false)
    var updatedAt: Instant? = null,
)
