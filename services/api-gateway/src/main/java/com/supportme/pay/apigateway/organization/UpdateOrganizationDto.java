package com.supportme.pay.apigateway.organization;

import java.util.List;

public record UpdateOrganizationDto(String ownerKeycloakSub, String name, String logo, List<String> enabledSections) {
}
