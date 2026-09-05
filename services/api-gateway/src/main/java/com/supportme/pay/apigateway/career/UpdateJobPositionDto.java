package com.supportme.pay.apigateway.career;

public record UpdateJobPositionDto(
        Long organizationId,
        String title,
        String location,
        String employmentType,
        String descriptionHtml,
        String shortDescription,
        boolean active,
        int sort
) {
}
