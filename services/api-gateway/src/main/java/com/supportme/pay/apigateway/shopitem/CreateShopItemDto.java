package com.supportme.pay.apigateway.shopitem;

public record CreateShopItemDto(
        Long organizationId,
        String slug,
        String name,
        String image,
        int minAmount,
        Integer price,
        String description,
        boolean isDefault,
        int sort
) {
}
