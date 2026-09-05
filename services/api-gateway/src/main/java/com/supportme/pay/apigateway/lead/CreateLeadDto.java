package com.supportme.pay.apigateway.lead;

public record CreateLeadDto(String name, String email, String phone, String company, String message) {
}
