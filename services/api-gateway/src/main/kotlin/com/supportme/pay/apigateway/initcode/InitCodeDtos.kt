package com.supportme.pay.apigateway.initcode

import pay.initcode.v1.InitCodeResponse
import pay.initcode.v1.OwnerScope

/** Scope właściciela z JSON — dokładnie jedno z dwóch pól niepuste, egzekwowane w [toProto]. */
data class OwnerScopeDto(
    val organizationId: Long? = null,
    val ownerUserId: Long? = null,
) {
    fun toProto(): OwnerScope {
        val builder = OwnerScope.newBuilder()
        when {
            organizationId != null -> builder.organizationId = organizationId
            ownerUserId != null -> builder.ownerUserId = ownerUserId
        }
        return builder.build()
    }
}

data class CreateInitCodeDto(
    val owner: OwnerScopeDto,
    val label: String,
    val shopItemId: Long? = null,
    val targetOrganizationId: Long? = null,
)

data class UpdateInitCodeDto(
    val owner: OwnerScopeDto,
    val label: String,
    val shopItemId: Long? = null,
    val targetOrganizationId: Long? = null,
)

data class ScopedRequestDto(
    val owner: OwnerScopeDto,
)

data class InitCodeDto(
    val id: Long,
    val uuid: String,
    val label: String,
    val organizationId: Long?,
    val ownerUserId: Long?,
    val shopItemId: Long?,
    val targetOrganizationId: Long?,
    val active: Boolean,
) {
    companion object {
        fun from(response: InitCodeResponse) = InitCodeDto(
            id = response.id,
            uuid = response.uuid,
            label = response.label,
            organizationId = if (response.hasOrganizationId()) response.organizationId else null,
            ownerUserId = if (response.hasOwnerUserId()) response.ownerUserId else null,
            shopItemId = if (response.hasShopItemId()) response.shopItemId else null,
            targetOrganizationId = if (response.hasTargetOrganizationId()) response.targetOrganizationId else null,
            active = response.active,
        )
    }
}
