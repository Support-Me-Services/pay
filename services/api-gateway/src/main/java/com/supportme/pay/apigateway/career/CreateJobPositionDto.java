package com.supportme.pay.apigateway.career;

public record CreateJobPositionDto(
        Long organizationId,
        String title,
        String location,
        String employmentType,
        String descriptionHtml,
        String shortDescription,
        int sort
) {
}
