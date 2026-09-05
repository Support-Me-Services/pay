package com.supportme.pay.apigateway.beneficiary;

public record CreateBeneficiaryNodeDto(
        Long organizationId,
        String heading,
        String image,
        String imageSide,
        int imageScale,
        int imageX,
        int imageY,
        String textAlign,
        String bodyHtml,
        int position
) {
}
