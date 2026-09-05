package com.supportme.pay.apigateway.beneficiary;

import pay.beneficiary.v1.BeneficiaryNodeResponse;

public record BeneficiaryNodeDto(
        long id,
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
    public static BeneficiaryNodeDto from(BeneficiaryNodeResponse response) {
        return new BeneficiaryNodeDto(
                response.getId(),
                response.hasOrganizationId() ? response.getOrganizationId() : null,
                response.getHeading(),
                response.hasImage() ? response.getImage() : null,
                response.getImageSide(),
                response.getImageScale(),
                response.getImageX(),
                response.getImageY(),
                response.getTextAlign(),
                response.hasBodyHtml() ? response.getBodyHtml() : null,
                response.getPosition(),
                response.getActive()
        );
    }
}
