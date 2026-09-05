package com.supportme.pay.apigateway.beneficiary;

public record UpdateBeneficiaryNodeDto(
        Long organizationId,
        String heading,
        String image,
        String imageSide,
        int imageScale,
        int imageX,
        int imageY,
        String textAlign,
        String bodyHtml,
        int position,
        boolean active
) {
}
