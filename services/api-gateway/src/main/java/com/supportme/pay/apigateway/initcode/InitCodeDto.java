package com.supportme.pay.apigateway.initcode;

import pay.initcode.v1.InitCodeResponse;

public record InitCodeDto(
        long id,
        String uuid,
        String label,
        Long organizationId,
        Long ownerUserId,
        Long shopItemId,
        Long targetOrganizationId,
        boolean active
) {
    public static InitCodeDto from(InitCodeResponse response) {
        return new InitCodeDto(
                response.getId(),
                response.getUuid(),
                response.getLabel(),
                response.hasOrganizationId() ? response.getOrganizationId() : null,
                response.hasOwnerUserId() ? response.getOwnerUserId() : null,
                response.hasShopItemId() ? response.getShopItemId() : null,
                response.hasTargetOrganizationId() ? response.getTargetOrganizationId() : null,
                response.getActive()
        );
    }
}
