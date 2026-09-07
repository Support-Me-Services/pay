package com.supportme.pay.apigateway.organization;

import pay.organization.v1.OrganizationResponse;

import java.util.List;

public record OrganizationDto(
        long id,
        String userId,
        String name,
        String handle,
        String logo,
        List<String> enabledSections
) {
    public static OrganizationDto from(OrganizationResponse response) {
        return new OrganizationDto(
                response.getId(),
                response.getUserId(),
                response.getName(),
                response.getHandle(),
                response.hasLogo() ? response.getLogo() : null,
                response.hasEnabledSections() ? response.getEnabledSections().getKeysList() : null
        );
    }
}
