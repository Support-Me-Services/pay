package com.supportme.pay.apigateway.careers;

public record CreateJobApplicationDto(
        long organizationId,
        Long jobPositionId,
        String name,
        String email,
        String phone,
        String message,
        String cvPath,
        String cvOriginalName,
        boolean futureRecruitmentConsent
) {
}
