package com.supportme.pay.coresvc.initcode

import io.grpc.Status
import io.grpc.stub.StreamObserver
import net.devh.boot.grpc.server.service.GrpcService
import pay.initcode.v1.CreateInitCodeRequest
import pay.initcode.v1.DeleteInitCodeRequest
import pay.initcode.v1.DeleteInitCodeResponse
import pay.initcode.v1.InitCodeResponse
import pay.initcode.v1.InitCodeServiceGrpc
import pay.initcode.v1.ListInitCodesRequest
import pay.initcode.v1.ListInitCodesResponse
import pay.initcode.v1.OwnerScope
import pay.initcode.v1.ResolveRequest
import pay.initcode.v1.ResolveResponse
import pay.initcode.v1.ToggleInitCodeRequest
import pay.initcode.v1.UpdateInitCodeRequest
import java.util.UUID

/**
 * Pierwsza prawdziwa domena core-svc (Faza 5). CRUD + Resolve dla kodów
 * inicjalizacji kontaktu (tag NFC / kod QR) — patrz proto/initcode/v1.
 * Egzekwuje scope właściciela przy KAŻDEJ mutacji (obrona w głąb — nie
 * ufamy ślepo samej obecności nagłówka wewnętrznego na api-gateway).
 */
@GrpcService
class InitCodeGrpcService(
    private val repository: InitCodeRepository,
) : InitCodeServiceGrpc.InitCodeServiceImplBase() {

    override fun create(request: CreateInitCodeRequest, responseObserver: StreamObserver<InitCodeResponse>) {
        val (organizationId, ownerUserId) = scopeOf(request.owner)
        if (organizationId == null && ownerUserId == null) {
            responseObserver.onError(Status.INVALID_ARGUMENT.withDescription("owner scope is required").asRuntimeException())
            return
        }
        if (organizationId != null && !request.hasShopItemId()) {
            responseObserver.onError(Status.INVALID_ARGUMENT.withDescription("shop_item_id required for organization-owned codes").asRuntimeException())
            return
        }
        if (ownerUserId != null && !request.hasTargetOrganizationId()) {
            responseObserver.onError(Status.INVALID_ARGUMENT.withDescription("target_organization_id required for personal codes").asRuntimeException())
            return
        }

        val entity = InitCode(
            uuid = UUID.randomUUID().toString(),
            label = request.label,
            organizationId = organizationId,
            ownerUserId = ownerUserId,
            shopItemId = if (request.hasShopItemId()) request.shopItemId else null,
            targetOrganizationId = if (request.hasTargetOrganizationId()) request.targetOrganizationId else null,
        )

        respond(responseObserver, repository.save(entity))
    }

    override fun update(request: UpdateInitCodeRequest, responseObserver: StreamObserver<InitCodeResponse>) {
        val existing = ownedEntityOrError(request.id, request.owner, responseObserver) ?: return

        existing.label = request.label
        if (request.hasShopItemId()) existing.shopItemId = request.shopItemId
        if (request.hasTargetOrganizationId()) existing.targetOrganizationId = request.targetOrganizationId

        respond(responseObserver, repository.save(existing))
    }

    override fun delete(request: DeleteInitCodeRequest, responseObserver: StreamObserver<DeleteInitCodeResponse>) {
        val existing = ownedEntityOrError(request.id, request.owner, responseObserver) ?: return

        repository.delete(existing)
        responseObserver.onNext(DeleteInitCodeResponse.newBuilder().setDeleted(true).build())
        responseObserver.onCompleted()
    }

    override fun toggle(request: ToggleInitCodeRequest, responseObserver: StreamObserver<InitCodeResponse>) {
        val existing = ownedEntityOrError(request.id, request.owner, responseObserver) ?: return

        existing.active = !existing.active
        respond(responseObserver, repository.save(existing))
    }

    override fun list(request: ListInitCodesRequest, responseObserver: StreamObserver<ListInitCodesResponse>) {
        val (organizationId, ownerUserId) = scopeOf(request.owner)
        val codes = when {
            organizationId != null -> repository.findByOrganizationId(organizationId)
            ownerUserId != null -> repository.findByOwnerUserId(ownerUserId)
            else -> emptyList()
        }

        val response = ListInitCodesResponse.newBuilder()
            .addAllCodes(codes.map(::toResponse))
            .build()
        responseObserver.onNext(response)
        responseObserver.onCompleted()
    }

    override fun resolve(request: ResolveRequest, responseObserver: StreamObserver<ResolveResponse>) {
        val found = repository.findByUuidAndActiveTrue(request.uuid)

        val response = if (found.isPresent) {
            val code = found.get()
            val builder = ResolveResponse.newBuilder()
                .setFound(true)
                .setUuid(request.uuid)

            when {
                code.shopItemId != null -> builder
                    .setTargetType(ResolveResponse.TargetType.SHOP_ITEM)
                    .setTargetId(code.shopItemId!!)
                code.targetOrganizationId != null -> builder
                    .setTargetType(ResolveResponse.TargetType.ORGANIZATION)
                    .setTargetId(code.targetOrganizationId!!)
                else -> builder.setTargetType(ResolveResponse.TargetType.NONE).setFound(false)
            }
            builder.build()
        } else {
            ResolveResponse.newBuilder().setFound(false).setUuid(request.uuid).build()
        }

        responseObserver.onNext(response)
        responseObserver.onCompleted()
    }

    /** Zwraca (organizationId, ownerUserId) — dokładnie jedno niepuste, drugie null. */
    private fun scopeOf(owner: OwnerScope): Pair<Long?, Long?> = when (owner.scopeCase) {
        OwnerScope.ScopeCase.ORGANIZATION_ID -> owner.organizationId to null
        OwnerScope.ScopeCase.OWNER_USER_ID -> null to owner.ownerUserId
        else -> null to null
    }

    /**
     * Ładuje rekord po id i weryfikuje, że należy do przekazanego scope —
     * to jest egzekwowanie własności przeniesione z dzisiejszego
     * Panel/InitCodeController::guard() w Laravelu. Zwraca null (i już
     * wysłaną odpowiedź błędu) gdy nie znaleziono albo scope się nie zgadza.
     */
    private fun <T> ownedEntityOrError(id: Long, owner: OwnerScope, responseObserver: StreamObserver<T>): InitCode? {
        val existing = repository.findById(id).orElse(null)
        if (existing == null) {
            responseObserver.onError(Status.NOT_FOUND.asRuntimeException())
            return null
        }

        val (organizationId, ownerUserId) = scopeOf(owner)
        val owns = (organizationId != null && organizationId == existing.organizationId) ||
            (ownerUserId != null && ownerUserId == existing.ownerUserId)
        if (!owns) {
            responseObserver.onError(Status.PERMISSION_DENIED.asRuntimeException())
            return null
        }

        return existing
    }

    private fun respond(responseObserver: StreamObserver<InitCodeResponse>, entity: InitCode) {
        responseObserver.onNext(toResponse(entity))
        responseObserver.onCompleted()
    }

    private fun toResponse(entity: InitCode): InitCodeResponse {
        val builder = InitCodeResponse.newBuilder()
            .setId(entity.id)
            .setUuid(entity.uuid)
            .setLabel(entity.label)
            .setActive(entity.active)

        entity.organizationId?.let { builder.organizationId = it }
        entity.ownerUserId?.let { builder.ownerUserId = it }
        entity.shopItemId?.let { builder.shopItemId = it }
        entity.targetOrganizationId?.let { builder.targetOrganizationId = it }

        return builder.build()
    }
}
