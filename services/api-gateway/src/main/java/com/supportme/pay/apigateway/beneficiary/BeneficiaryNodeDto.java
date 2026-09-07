package com.supportme.pay.apigateway.beneficiary;

import pay.beneficiary.v1.BeneficiaryNodeResponse;

public record BeneficiaryNodeDto(
        long id,
        long organizationId,
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
    public static BeneficiaryNodeDto from(BeneficiaryNodeResponse r) {
        return new BeneficiaryNodeDto(
                r.getId(), r.getOrganizationId(), r.getHeading(),
                r.hasImage() ? r.getImage() : null,
                r.getImageSide(), r.getImageScale(), r.getImageX(), r.getImageY(), r.getTextAlign(),
                r.hasBodyHtml() ? r.getBodyHtml() : null,
                r.getPosition(), r.getActive()
        );
    }
}
