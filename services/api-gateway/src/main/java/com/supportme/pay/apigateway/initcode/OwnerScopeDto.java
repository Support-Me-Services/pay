package com.supportme.pay.apigateway.initcode;

import pay.initcode.v1.OwnerScope;

/** Scope właściciela z JSON — dokładnie jedno z dwóch pól niepuste, egzekwowane w toProto(). */
public record OwnerScopeDto(Long organizationId, Long ownerUserId) {

    public OwnerScope toProto() {
        OwnerScope.Builder builder = OwnerScope.newBuilder();
        if (organizationId != null) {
            builder.setOrganizationId(organizationId);
        } else if (ownerUserId != null) {
            builder.setOwnerUserId(ownerUserId);
        }
        return builder.build();
    }
}
