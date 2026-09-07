package com.supportme.pay.apigateway.beneficiary;

public record UpsertBeneficiaryNodeDto(
        long organizationId,
        String heading,
        String image,
        String imageSide,
        Integer imageScale,
        Integer imageX,
        Integer imageY,
        String textAlign,
        String bodyHtml
) {
}
