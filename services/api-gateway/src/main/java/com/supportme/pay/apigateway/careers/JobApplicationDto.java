package com.supportme.pay.apigateway.careers;

import pay.careers.v1.JobApplicationResponse;

public record JobApplicationDto(
        long id,
        long organizationId,
        Long jobPositionId,
        String name,
        String email,
        String phone,
        String message,
        String cvPath,
        String cvOriginalName,
        boolean isRead,
        String status,
        boolean futureRecruitmentConsent,
        String futureRecruitmentConsentAt,
        boolean futureConsentActive,
        String createdAt
) {
    public static JobApplicationDto from(JobApplicationResponse r) {
        return new JobApplicationDto(
                r.getId(), r.getOrganizationId(),
                r.hasJobPositionId() ? r.getJobPositionId() : null,
                r.getName(), r.getEmail(),
                r.hasPhone() ? r.getPhone() : null,
                r.hasMessage() ? r.getMessage() : null,
                r.hasCvPath() ? r.getCvPath() : null,
                r.hasCvOriginalName() ? r.getCvOriginalName() : null,
                r.getIsRead(), r.getStatus(), r.getFutureRecruitmentConsent(),
                r.hasFutureRecruitmentConsentAt() ? r.getFutureRecruitmentConsentAt() : null,
                r.getFutureConsentActive(), r.getCreatedAt()
        );
    }
}
