package com.supportme.pay.apigateway.career;

public record UpdateJobApplicationStatusDto(Long organizationId, String status, boolean isRead) {
}
