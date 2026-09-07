package com.supportme.pay.apigateway.careers;

import pay.careers.v1.JobPositionResponse;

public record JobPositionDto(
        long id,
        long organizationId,
        String title,
        String location,
        String employmentType,
        String descriptionHtml,
        String shortDescription,
        boolean active,
        int sort,
        long applicationsCount
) {
    public static JobPositionDto from(JobPositionResponse r) {
        return new JobPositionDto(
                r.getId(), r.getOrganizationId(), r.getTitle(),
                r.hasLocation() ? r.getLocation() : null,
                r.hasEmploymentType() ? r.getEmploymentType() : null,
                r.hasDescriptionHtml() ? r.getDescriptionHtml() : null,
                r.hasShortDescription() ? r.getShortDescription() : null,
                r.getActive(), r.getSort(), r.getApplicationsCount()
        );
    }
}
