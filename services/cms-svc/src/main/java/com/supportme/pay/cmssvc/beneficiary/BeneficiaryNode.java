package com.supportme.pay.cmssvc.beneficiary;

import jakarta.persistence.Column;
import jakarta.persistence.Entity;
import jakarta.persistence.GeneratedValue;
import jakarta.persistence.GenerationType;
import jakarta.persistence.Id;
import jakarta.persistence.Table;
import org.hibernate.annotations.CreationTimestamp;
import org.hibernate.annotations.UpdateTimestamp;

import java.time.OffsetDateTime;

/**
 * Węzeł podstrony "Wspieramy"/"O nas" — nagłówek + grafika (z kadrowaniem) +
 * rich-text, kolejność (position) ustawiana przeciąganiem w panelu.
 */
@Entity
@Table(name = "beneficiary_nodes")
public class BeneficiaryNode {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "organization_id")
    private Long organizationId;

    @Column(nullable = false)
    private String heading;

    @Column
    private String image;

    @Column(name = "image_side", nullable = false, length = 10)
    private String imageSide = "left";

    @Column(name = "image_scale", nullable = false)
    private short imageScale = 100;

    @Column(name = "image_x", nullable = false)
    private short imageX = 0;

    @Column(name = "image_y", nullable = false)
    private short imageY = 0;

    @Column(name = "text_align", nullable = false, length = 10)
    private String textAlign = "left";

    @Column(name = "body_html", columnDefinition = "text")
    private String bodyHtml;

    @Column(nullable = false)
    private int position = 0;

    @Column(nullable = false)
    private boolean active = true;

    @CreationTimestamp
    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt;

    @UpdateTimestamp
    @Column(name = "updated_at", nullable = false)
    private OffsetDateTime updatedAt;

    /** Wymagany przez Hibernate. */
    protected BeneficiaryNode() {
    }

    public BeneficiaryNode(Long organizationId, String heading, String image, String imageSide,
                            int imageScale, int imageX, int imageY, String textAlign,
                            String bodyHtml, int position) {
        this.organizationId = organizationId;
        this.heading = heading;
        this.image = image;
        this.imageSide = imageSide;
        this.imageScale = (short) imageScale;
        this.imageX = (short) imageX;
        this.imageY = (short) imageY;
        this.textAlign = textAlign;
        this.bodyHtml = bodyHtml;
        this.position = position;
    }

    public Long getId() {
        return id;
    }

    public Long getOrganizationId() {
        return organizationId;
    }

    public String getHeading() {
        return heading;
    }

    public void setHeading(String heading) {
        this.heading = heading;
    }

    public String getImage() {
        return image;
    }

    public void setImage(String image) {
        this.image = image;
    }

    public String getImageSide() {
        return imageSide;
    }

    public void setImageSide(String imageSide) {
        this.imageSide = imageSide;
    }

    public int getImageScale() {
        return imageScale;
    }

    public void setImageScale(int imageScale) {
        this.imageScale = (short) imageScale;
    }

    public int getImageX() {
        return imageX;
    }

    public void setImageX(int imageX) {
        this.imageX = (short) imageX;
    }

    public int getImageY() {
        return imageY;
    }

    public void setImageY(int imageY) {
        this.imageY = (short) imageY;
    }

    public String getTextAlign() {
        return textAlign;
    }

    public void setTextAlign(String textAlign) {
        this.textAlign = textAlign;
    }

    public String getBodyHtml() {
        return bodyHtml;
    }

    public void setBodyHtml(String bodyHtml) {
        this.bodyHtml = bodyHtml;
    }

    public int getPosition() {
        return position;
    }

    public void setPosition(int position) {
        this.position = position;
    }

    public boolean isActive() {
        return active;
    }

    public void setActive(boolean active) {
        this.active = active;
    }

    public OffsetDateTime getCreatedAt() {
        return createdAt;
    }

    public OffsetDateTime getUpdatedAt() {
        return updatedAt;
    }
}
