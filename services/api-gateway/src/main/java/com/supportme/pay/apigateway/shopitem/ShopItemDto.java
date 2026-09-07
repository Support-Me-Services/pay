package com.supportme.pay.apigateway.shopitem;

import pay.shopitem.v1.ShopItemResponse;

public record ShopItemDto(
        long id,
        long organizationId,
        String name,
        String slug,
        String image,
        long priceGrosze,
        String description,
        boolean isDefault,
        boolean active,
        int sort,
        String thankYouHeading,
        String thankYouBody,
        String thankYouImage,
        Long mecenasOrganizationId
) {
    public static ShopItemDto from(ShopItemResponse r) {
        return new ShopItemDto(
                r.getId(), r.getOrganizationId(), r.getName(), r.getSlug(),
                r.hasImage() ? r.getImage() : null,
                r.getPriceGrosze(),
                r.hasDescription() ? r.getDescription() : null,
                r.getIsDefault(), r.getActive(), r.getSort(),
                r.hasThankYouHeading() ? r.getThankYouHeading() : null,
                r.hasThankYouBody() ? r.getThankYouBody() : null,
                r.hasThankYouImage() ? r.getThankYouImage() : null,
                r.hasMecenasOrganizationId() ? r.getMecenasOrganizationId() : null
        );
    }
}
