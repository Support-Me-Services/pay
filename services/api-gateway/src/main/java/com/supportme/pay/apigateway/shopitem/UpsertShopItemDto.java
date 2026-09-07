package com.supportme.pay.apigateway.shopitem;

public record UpsertShopItemDto(
        long organizationId,
        String name,
        String slug,
        String image,
        boolean clearImage,
        long priceGrosze,
        String description,
        Integer sort,
        Boolean active,
        String thankYouHeading,
        String thankYouBody,
        String thankYouImage,
        boolean clearThankYouImage,
        Long mecenasOrganizationId,
        boolean clearMecenasOrganizationId,
        boolean isDefault
) {
}
