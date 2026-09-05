package com.supportme.pay.apigateway.shopitem;

import pay.shopitem.v1.ShopItemResponse;

public record ShopItemDto(
        long id,
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
    public static ShopItemDto from(ShopItemResponse response) {
        return new ShopItemDto(
                response.getId(),
                response.hasOrganizationId() ? response.getOrganizationId() : null,
                response.getSlug(),
                response.getName(),
                response.hasImage() ? response.getImage() : null,
                response.getMinAmount(),
                response.hasPrice() ? response.getPrice() : null,
                response.hasDescription() ? response.getDescription() : null,
                response.getIsDefault(),
                response.getActive(),
                response.getSort(),
                response.hasThankYouHeading() ? response.getThankYouHeading() : null,
                response.hasThankYouBody() ? response.getThankYouBody() : null,
                response.hasThankYouImage() ? response.getThankYouImage() : null,
                response.hasMecenasOrganizationId() ? response.getMecenasOrganizationId() : null
        );
    }
}
