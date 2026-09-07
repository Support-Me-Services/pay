package com.supportme.pay.orgsvc.careers;

import jakarta.persistence.Column;
import jakarta.persistence.Entity;
import jakarta.persistence.GeneratedValue;
import jakarta.persistence.GenerationType;
import jakarta.persistence.Id;
import jakarta.persistence.Table;
import org.hibernate.annotations.CreationTimestamp;

import java.time.OffsetDateTime;

/** Aplikacja o pracę — mirror JobApplication.php w Laravelu (Faza 4 migracji). Bez updated_at (jak w źródle). */
@Entity
@Table(name = "job_applications")
public class JobApplication {

    public static final int FUTURE_CONSENT_MONTHS = 24;

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "organization_id", nullable = false)
    private Long organizationId;

    @Column(name = "job_position_id")
    private Long jobPositionId;

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

    protected JobApplication() {
    }

    public JobApplication(Long organizationId, String name, String email) {
        this.organizationId = organizationId;
        this.name = name;
        this.email = email;
    }

    public Long getId() {
        return id;
    }

    public Long getOrganizationId() {
        return organizationId;
    }

    public Long getJobPositionId() {
        return jobPositionId;
    }

    public void setJobPositionId(Long jobPositionId) {
        this.jobPositionId = jobPositionId;
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

    public void setPhone(String phone) {
        this.phone = phone;
    }

    public String getMessage() {
        return message;
    }

    public void setMessage(String message) {
        this.message = message;
    }

    public String getCvPath() {
        return cvPath;
    }

    public void setCvPath(String cvPath) {
        this.cvPath = cvPath;
    }

    public String getCvOriginalName() {
        return cvOriginalName;
    }

    public void setCvOriginalName(String cvOriginalName) {
        this.cvOriginalName = cvOriginalName;
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

    public void setFutureRecruitmentConsent(boolean futureRecruitmentConsent) {
        this.futureRecruitmentConsent = futureRecruitmentConsent;
    }

    public OffsetDateTime getFutureRecruitmentConsentAt() {
        return futureRecruitmentConsentAt;
    }

    public void setFutureRecruitmentConsentAt(OffsetDateTime futureRecruitmentConsentAt) {
        this.futureRecruitmentConsentAt = futureRecruitmentConsentAt;
    }

    public OffsetDateTime getCreatedAt() {
        return createdAt;
    }

    /** Aktywna = zgoda ustawiona i wciąż w oknie 24 miesięcy od udzielenia. */
    public boolean isFutureConsentActive() {
        return futureRecruitmentConsent
                && futureRecruitmentConsentAt != null
                && futureRecruitmentConsentAt.isAfter(OffsetDateTime.now().minusMonths(FUTURE_CONSENT_MONTHS));
    }
}
