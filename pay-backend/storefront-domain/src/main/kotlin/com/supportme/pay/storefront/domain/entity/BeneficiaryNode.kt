package com.supportme.pay.storefront.domain.entity

import jakarta.persistence.Column
import jakarta.persistence.Convert
import jakarta.persistence.Entity
import jakarta.persistence.GeneratedValue
import jakarta.persistence.GenerationType
import jakarta.persistence.Id
import jakarta.persistence.Table
import org.hibernate.annotations.CreationTimestamp
import org.hibernate.annotations.UpdateTimestamp
import java.time.Instant

/** Blok CMS strony „Wspieramy" — nagłówek + grafika (kadrowana w kole) + tekst. */
@Entity
@Table(name = "beneficiary_nodes")
class BeneficiaryNode(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    val id: Long? = null,

    @Column(nullable = false)
    var heading: String,

    @Column
    var image: String? = null,

    @Convert(converter = SideConverter::class)
    @Column(name = "image_side", nullable = false, length = 10)
    var imageSide: Side = Side.LEFT,

    /** % skali (zoom); 100 = dopasowanie bez powiększenia. */
    @Column(name = "image_scale", nullable = false)
    var imageScale: Int = 100,

    /** % przesunięcia poziomego kadru. */
    @Column(name = "image_x", nullable = false)
    var imageX: Int = 0,

    /** % przesunięcia pionowego kadru. */
    @Column(name = "image_y", nullable = false)
    var imageY: Int = 0,

    @Convert(converter = SideConverter::class)
    @Column(name = "text_align", nullable = false, length = 10)
    var textAlign: Side = Side.LEFT,

    @Column(name = "body_html", columnDefinition = "text")
    var bodyHtml: String? = null,

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
) {
    fun imageRight(): Boolean = imageSide == Side.RIGHT
}
