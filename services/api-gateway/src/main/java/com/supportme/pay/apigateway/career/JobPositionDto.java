package com.supportme.pay.apigateway.career;

import pay.career.v1.JobPositionResponse;

public record JobPositionDto(
        long id,
        Long organizationId,
        String title,
        String location,
        String employmentType,
        String descriptionHtml,
        String shortDescription,
        boolean active,
        int sort
) {
    public static JobPositionDto from(JobPositionResponse response) {
        return new JobPositionDto(
                response.getId(),
                response.hasOrganizationId() ? response.getOrganizationId() : null,
                response.getTitle(),
                response.hasLocation() ? response.getLocation() : null,
                response.hasEmploymentType() ? response.getEmploymentType() : null,
                response.hasDescriptionHtml() ? response.getDescriptionHtml() : null,
                response.hasShortDescription() ? response.getShortDescription() : null,
                response.getActive(),
                response.getSort()
        );
    }
}
