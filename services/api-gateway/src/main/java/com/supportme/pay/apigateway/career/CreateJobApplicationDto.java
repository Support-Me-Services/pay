package com.supportme.pay.apigateway.career;

public record CreateJobApplicationDto(
        Long jobPositionId,
        Long organizationId,
        String name,
        String email,
        String phone,
        String message,
        String cvPath,
        String cvOriginalName,
        boolean futureRecruitmentConsent
) {
}
