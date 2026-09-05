package com.supportme.pay.cmssvc.career;

import jakarta.persistence.Column;
import jakarta.persistence.Entity;
import jakarta.persistence.GeneratedValue;
import jakarta.persistence.GenerationType;
import jakarta.persistence.Id;
import jakarta.persistence.Table;
import org.hibernate.annotations.CreationTimestamp;

import java.time.OffsetDateTime;

/**
 * Zgłoszenie rekrutacyjne. jobPositionId = null oznacza aplikację
 * spontaniczną — ta MIMO TO ma właściciela (organizationId ustawiany
 * niezależnie od stanowiska).
 */
@Entity
@Table(name = "job_applications")
public class JobApplication {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "job_position_id")
    private Long jobPositionId;

    @Column(name = "organization_id")
    private Long organizationId;

    @Column(nullable = false)
    private String name;

    @Column(nullable = false)
    private String email;

    @Column
    private String phone;

    @Column(columnDefinition = "text")
    private String message;

    @Column(name = "cv_path")
    private String cvPath;

    @Column(name = "cv_original_name")
    private String cvOriginalName;

    @Column(name = "is_read", nullable = false)
    private boolean isRead = false;

    @Column(nullable = false, length = 20)
    private String status = "pending";

    @Column(name = "future_recruitment_consent", nullable = false)
    private boolean futureRecruitmentConsent = false;

    @Column(name = "future_recruitment_consent_at")
    private OffsetDateTime futureRecruitmentConsentAt;

    @CreationTimestamp
    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt;

    /** Wymagany przez Hibernate. */
    protected JobApplication() {
    }

    public JobApplication(Long jobPositionId, Long organizationId, String name, String email, String phone,
                           String message, String cvPath, String cvOriginalName, boolean futureRecruitmentConsent) {
        this.jobPositionId = jobPositionId;
        this.organizationId = organizationId;
        this.name = name;
        this.email = email;
        this.phone = phone;
        this.message = message;
        this.cvPath = cvPath;
        this.cvOriginalName = cvOriginalName;
        this.futureRecruitmentConsent = futureRecruitmentConsent;
        this.futureRecruitmentConsentAt = futureRecruitmentConsent ? OffsetDateTime.now() : null;
    }

    public Long getId() {
        return id;
    }

    public Long getJobPositionId() {
        return jobPositionId;
    }

    public Long getOrganizationId() {
        return organizationId;
    }

    public String getName() {
        return name;
    }

    public String getEmail() {
        return email;
    }

    public String getPhone() {
        return phone;
    }

    public String getMessage() {
        return message;
    }

    public String getCvPath() {
        return cvPath;
    }

    public String getCvOriginalName() {
        return cvOriginalName;
    }

    public boolean isRead() {
        return isRead;
    }

    public void setRead(boolean read) {
        isRead = read;
    }

    public String getStatus() {
        return status;
    }

    public void setStatus(String status) {
        this.status = status;
    }

    public boolean isFutureRecruitmentConsent() {
        return futureRecruitmentConsent;
    }

    public OffsetDateTime getFutureRecruitmentConsentAt() {
        return futureRecruitmentConsentAt;
    }

    public OffsetDateTime getCreatedAt() {
        return createdAt;
    }
}
