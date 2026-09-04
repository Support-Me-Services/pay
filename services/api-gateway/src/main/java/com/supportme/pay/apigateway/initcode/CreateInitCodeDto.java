package com.supportme.pay.apigateway.initcode;

public record CreateInitCodeDto(OwnerScopeDto owner, String label, Long shopItemId, Long targetOrganizationId) {
}
