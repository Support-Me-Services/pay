package com.supportme.pay.apigateway.initcode;

public record UpdateInitCodeDto(OwnerScopeDto owner, String label, Long shopItemId, Long targetOrganizationId) {
}
