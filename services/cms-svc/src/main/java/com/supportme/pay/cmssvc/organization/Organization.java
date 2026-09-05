package com.supportme.pay.cmssvc.organization;

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
 * Organizacja — byt nad kontem: jedno konto (Keycloak) może zarządzać
 * wieloma organizacjami. Właściciel = ownerKeycloakSub, NIE numeryczny
 * user_id z tabeli Laravela (ta ma docelowo zniknąć).
 */
@Entity
@Table(name = "organizations")
public class Organization {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "owner_keycloak_sub", nullable = false)
    private String ownerKeycloakSub;

    @Column(nullable = false)
    private String name;

    @Column(nullable = false, unique = true)
    private String handle;

    @Column
    private String logo;

    /** JSON zserializowany w warstwie gRPC (EnabledSections <-> tekst) — patrz OrganizationGrpcService. */
    @Column(name = "enabled_sections", columnDefinition = "text")
    private String enabledSectionsJson;

    @CreationTimestamp
    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt;

    @UpdateTimestamp
    @Column(name = "updated_at", nullable = false)
    private OffsetDateTime updatedAt;

    /** Wymagany przez Hibernate. */
    protected Organization() {
    }

    public Organization(String ownerKeycloakSub, String name, String handle, String logo) {
        this.ownerKeycloakSub = ownerKeycloakSub;
        this.name = name;
        this.handle = handle;
        this.logo = logo;
    }

    public Long getId() {
        return id;
    }

    public String getOwnerKeycloakSub() {
        return ownerKeycloakSub;
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

    public String getEnabledSectionsJson() {
        return enabledSectionsJson;
    }

    public void setEnabledSectionsJson(String enabledSectionsJson) {
        this.enabledSectionsJson = enabledSectionsJson;
    }

    public OffsetDateTime getCreatedAt() {
        return createdAt;
    }

    public OffsetDateTime getUpdatedAt() {
        return updatedAt;
    }
}
