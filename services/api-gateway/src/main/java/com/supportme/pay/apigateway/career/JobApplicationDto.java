package com.supportme.pay.apigateway.career;

import pay.career.v1.JobApplicationResponse;

public record JobApplicationDto(
        long id,
        Long jobPositionId,
        Long organizationId,
        String name,
        String email,
        String phone,
        String message,
        String cvPath,
        String cvOriginalName,
        boolean isRead,
        String status,
        boolean futureRecruitmentConsent
) {
    public static JobApplicationDto from(JobApplicationResponse response) {
        return new JobApplicationDto(
                response.getId(),
                response.hasJobPositionId() ? response.getJobPositionId() : null,
                response.hasOrganizationId() ? response.getOrganizationId() : null,
                response.getName(),
                response.getEmail(),
                response.hasPhone() ? response.getPhone() : null,
                response.hasMessage() ? response.getMessage() : null,
                response.hasCvPath() ? response.getCvPath() : null,
                response.hasCvOriginalName() ? response.getCvOriginalName() : null,
                response.getIsRead(),
                response.getStatus(),
                response.getFutureRecruitmentConsent()
        );
    }
}
