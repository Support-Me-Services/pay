package com.supportme.pay.apigateway.lead;

import pay.lead.v1.LeadResponse;

public record LeadDto(long id, String name, String email, String phone, String company, String message) {
    public static LeadDto from(LeadResponse response) {
        return new LeadDto(
                response.getId(),
                response.getName(),
                response.getEmail(),
                response.getPhone(),
                response.hasCompany() ? response.getCompany() : null,
                response.getMessage()
        );
    }
}
