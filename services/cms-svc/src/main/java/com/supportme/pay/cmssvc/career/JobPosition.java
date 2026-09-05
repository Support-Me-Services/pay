package com.supportme.pay.cmssvc.career;

import jakarta.persistence.Column;
import jakarta.persistence.Entity;
import jakarta.persistence.GeneratedValue;
import jakarta.persistence.GenerationType;
import jakarta.persistence.Id;
import jakarta.persistence.Table;
import org.hibernate.annotations.CreationTimestamp;

import java.time.OffsetDateTime;

/** Stanowisko pracy (sekcja "Praca") — zarządzane z panelu organizacji. */
@Entity
@Table(name = "job_positions")
public class JobPosition {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "organization_id")
    private Long organizationId;

    @Column(nullable = false)
    private String title;

    @Column
    private String location;

    @Column(name = "employment_type")
    private String employmentType;

    @Column(name = "description_html", columnDefinition = "text")
    private String descriptionHtml;

    @Column(name = "short_description", columnDefinition = "text")
    private String shortDescription;

    @Column(nullable = false)
    private boolean active = true;

    @Column(nullable = false)
    private int sort = 0;

    @CreationTimestamp
    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt;

    /** Wymagany przez Hibernate. */
    protected JobPosition() {
    }

    public JobPosition(Long organizationId, String title, String location, String employmentType,
                        String descriptionHtml, String shortDescription, int sort) {
        this.organizationId = organizationId;
        this.title = title;
        this.location = location;
        this.employmentType = employmentType;
        this.descriptionHtml = descriptionHtml;
        this.shortDescription = shortDescription;
        this.sort = sort;
    }

    public Long getId() {
        return id;
    }

    public Long getOrganizationId() {
        return organizationId;
    }

    public String getTitle() {
        return title;
    }

    public void setTitle(String title) {
        this.title = title;
    }

    public String getLocation() {
        return location;
    }

    public void setLocation(String location) {
        this.location = location;
    }

    public String getEmploymentType() {
        return employmentType;
    }

    public void setEmploymentType(String employmentType) {
        this.employmentType = employmentType;
    }

    public String getDescriptionHtml() {
        return descriptionHtml;
    }

    public void setDescriptionHtml(String descriptionHtml) {
        this.descriptionHtml = descriptionHtml;
    }

    public String getShortDescription() {
        return shortDescription;
    }

    public void setShortDescription(String shortDescription) {
        this.shortDescription = shortDescription;
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

    public OffsetDateTime getCreatedAt() {
        return createdAt;
    }
}
