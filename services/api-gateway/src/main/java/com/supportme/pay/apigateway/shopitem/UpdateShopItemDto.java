package com.supportme.pay.apigateway.shopitem;

public record UpdateShopItemDto(
        Long organizationId,
        String slug,
        String name,
        String image,
        int minAmount,
        Integer price,
        String description,
        boolean isDefault,
        boolean active,
        int sort,
        String thankYouHeading,
        String thankYouBody,
        String thankYouImage,
        Long mecenasOrganizationId
) {
}
