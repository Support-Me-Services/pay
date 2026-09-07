package com.supportme.pay.orgsvc.organization;

import jakarta.persistence.Column;
import jakarta.persistence.Entity;
import jakarta.persistence.GeneratedValue;
import jakarta.persistence.GenerationType;
import jakarta.persistence.Id;
import jakarta.persistence.Table;
import org.hibernate.annotations.CreationTimestamp;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.annotations.UpdateTimestamp;
import org.hibernate.type.SqlTypes;

import java.time.OffsetDateTime;

/**
 * Organizacja — byt nad kontem (User, w Laravelu — org-svc nie zna tej
 * tabeli, tylko jej klucz {@code userId}, tak jak InitCode w core-svc nie
 * zna organization_id/owner_user_id jako FK). Mirror
 * app/Modules/Storefront/Models/Organization.php.
 */
@Entity
@Table(name = "organizations")
public class Organization {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "user_id", nullable = false)
    private String userId;

    @Column(nullable = false)
    private String name;

    @Column(nullable = false, unique = true)
    private String handle;

    @Column
    private String logo;

    /** NULL = wszystkie sekcje widoczne — patrz Organization::canSee() w Laravelu. */
    @JdbcTypeCode(SqlTypes.ARRAY)
    @Column(name = "enabled_sections", columnDefinition = "text[]")
    private String[] enabledSections;

    @CreationTimestamp
    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt;

    @UpdateTimestamp
    @Column(name = "updated_at", nullable = false)
    private OffsetDateTime updatedAt;

    /** Wymagany przez Hibernate. */
    protected Organization() {
    }

    public Organization(String userId, String name, String handle, String[] enabledSections) {
        this.userId = userId;
        this.name = name;
        this.handle = handle;
        this.enabledSections = enabledSections;
    }

    public Long getId() {
        return id;
    }

    public String getUserId() {
        return userId;
    }

    public void setUserId(String userId) {
        this.userId = userId;
    }

    public String getName() {
        return name;
    }

    public void setName(String name) {
        this.name = name;
    }

    public String getHandle() {
        return handle;
    }

    public String getLogo() {
        return logo;
    }

    public void setLogo(String logo) {
        this.logo = logo;
    }

    public String[] getEnabledSections() {
        return enabledSections;
    }

    public void setEnabledSections(String[] enabledSections) {
        this.enabledSections = enabledSections;
    }

    public OffsetDateTime getCreatedAt() {
        return createdAt;
    }

    public OffsetDateTime getUpdatedAt() {
        return updatedAt;
    }
}
