package com.supportme.pay.orgsvc.shopitem;

import jakarta.persistence.Column;
import jakarta.persistence.Entity;
import jakarta.persistence.GeneratedValue;
import jakarta.persistence.GenerationType;
import jakarta.persistence.Id;
import jakarta.persistence.Table;
import org.hibernate.annotations.CreationTimestamp;
import org.hibernate.annotations.UpdateTimestamp;

import java.time.OffsetDateTime;

/** Produkt sklepu — mirror ShopItem.php w Laravelu (Faza 3 migracji). */
@Entity
@Table(name = "shop_items")
public class ShopItem {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "organization_id", nullable = false)
    private Long organizationId;

    @Column(nullable = false)
    private String name;

    @Column(nullable = false)
    private String slug;

    @Column
    private String image;

    @Column(name = "price_grosze", nullable = false)
    private long priceGrosze;

    @Column
    private String description;

    @Column(name = "is_default", nullable = false)
    private boolean isDefault = false;

    @Column(nullable = false)
    private boolean active = true;

    @Column(nullable = false)
    private int sort = 0;

    @Column(name = "thank_you_heading")
    private String thankYouHeading;

    @Column(name = "thank_you_body")
    private String thankYouBody;

    @Column(name = "thank_you_image")
    private String thankYouImage;

    @Column(name = "mecenas_organization_id")
    private Long mecenasOrganizationId;

    @CreationTimestamp
    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt;

    @UpdateTimestamp
    @Column(name = "updated_at", nullable = false)
    private OffsetDateTime updatedAt;

    protected ShopItem() {
    }

    public ShopItem(Long organizationId, String name, String slug, long priceGrosze) {
        this.organizationId = organizationId;
        this.name = name;
        this.slug = slug;
        this.priceGrosze = priceGrosze;
    }

    public Long getId() {
        return id;
    }

    public Long getOrganizationId() {
        return organizationId;
    }

    public String getName() {
        return name;
    }

    public void setName(String name) {
        this.name = name;
    }

    public String getSlug() {
        return slug;
    }

    public void setSlug(String slug) {
        this.slug = slug;
    }

    public String getImage() {
        return image;
    }

    public void setImage(String image) {
        this.image = image;
    }

    public long getPriceGrosze() {
        return priceGrosze;
    }

    public void setPriceGrosze(long priceGrosze) {
        this.priceGrosze = priceGrosze;
    }

    public String getDescription() {
        return description;
    }

    public void setDescription(String description) {
        this.description = description;
    }

    public boolean isDefault() {
        return isDefault;
    }

    public void setDefault(boolean aDefault) {
        isDefault = aDefault;
    }

    public boolean isActive() {
        return active;
    }

    public void setActive(boolean active) {
        this.active = active;
    }

    public int getSort() {
        return sort;
    }

    public void setSort(int sort) {
        this.sort = sort;
    }

    public String getThankYouHeading() {
        return thankYouHeading;
    }

    public void setThankYouHeading(String thankYouHeading) {
        this.thankYouHeading = thankYouHeading;
    }

    public String getThankYouBody() {
        return thankYouBody;
    }

    public void setThankYouBody(String thankYouBody) {
        this.thankYouBody = thankYouBody;
    }

    public String getThankYouImage() {
        return thankYouImage;
    }

    public void setThankYouImage(String thankYouImage) {
        this.thankYouImage = thankYouImage;
    }

    public Long getMecenasOrganizationId() {
        return mecenasOrganizationId;
    }

    public void setMecenasOrganizationId(Long mecenasOrganizationId) {
        this.mecenasOrganizationId = mecenasOrganizationId;
    }
}
