package com.supportme.pay.apigateway.organization;

public record CreateOrganizationDto(String ownerKeycloakSub, String name, String handle, String logo) {
}
