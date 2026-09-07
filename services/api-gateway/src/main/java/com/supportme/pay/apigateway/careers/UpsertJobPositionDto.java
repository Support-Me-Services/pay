package com.supportme.pay.apigateway.careers;

public record UpsertJobPositionDto(
        long organizationId,
        String title,
        String location,
        String employmentType,
        String descriptionHtml,
        String shortDescription,
        Integer sort,
        Boolean active
) {
}
