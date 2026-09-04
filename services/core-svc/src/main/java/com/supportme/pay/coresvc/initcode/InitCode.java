package com.supportme.pay.coresvc.initcode;

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
 * Kod inicjalizacji kontaktu (tag NFC / kod QR) — pierwsza prawdziwa
 * domena core-svc. Właściciel = dokładnie jedno z organizationId /
 * ownerUserId (egzekwowane przez CHECK w schemacie, patrz
 * V1__create_init_codes.sql, i ponownie przez InitCodeGrpcService przy
 * każdej mutacji — obrona w głąb, nie polegamy wyłącznie na bazie).
 */
@Entity
@Table(name = "init_codes")
public class InitCode {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(nullable = false, unique = true, length = 36)
    private String uuid;

    @Column(nullable = false)
    private String label;

    @Column(name = "organization_id")
    private Long organizationId;

    @Column(name = "owner_user_id")
    private Long ownerUserId;

    @Column(name = "shop_item_id")
    private Long shopItemId;

    @Column(name = "target_organization_id")
    private Long targetOrganizationId;

    @Column(nullable = false)
    private boolean active = true;

    @CreationTimestamp
    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt;

    @UpdateTimestamp
    @Column(name = "updated_at", nullable = false)
    private OffsetDateTime updatedAt;

    /** Wymagany przez Hibernate. */
    protected InitCode() {
    }

    public InitCode(String uuid, String label, Long organizationId, Long ownerUserId,
                     Long shopItemId, Long targetOrganizationId) {
        this.uuid = uuid;
        this.label = label;
        this.organizationId = organizationId;
        this.ownerUserId = ownerUserId;
        this.shopItemId = shopItemId;
        this.targetOrganizationId = targetOrganizationId;
        this.active = true;
    }

    public Long getId() {
        return id;
    }

    public String getUuid() {
        return uuid;
    }

    public String getLabel() {
        return label;
    }

    public void setLabel(String label) {
        this.label = label;
    }

    public Long getOrganizationId() {
        return organizationId;
    }

    public Long getOwnerUserId() {
        return ownerUserId;
    }

    public Long getShopItemId() {
        return shopItemId;
    }

    public void setShopItemId(Long shopItemId) {
        this.shopItemId = shopItemId;
    }

    public Long getTargetOrganizationId() {
        return targetOrganizationId;
    }

    public void setTargetOrganizationId(Long targetOrganizationId) {
        this.targetOrganizationId = targetOrganizationId;
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
