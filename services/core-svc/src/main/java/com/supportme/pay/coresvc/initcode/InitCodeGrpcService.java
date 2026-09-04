package com.supportme.pay.coresvc.initcode;

import io.grpc.Status;
import io.grpc.stub.StreamObserver;
import net.devh.boot.grpc.server.service.GrpcService;
import pay.initcode.v1.CreateInitCodeRequest;
import pay.initcode.v1.DeleteInitCodeRequest;
import pay.initcode.v1.DeleteInitCodeResponse;
import pay.initcode.v1.InitCodeResponse;
import pay.initcode.v1.InitCodeServiceGrpc;
import pay.initcode.v1.ListInitCodesRequest;
import pay.initcode.v1.ListInitCodesResponse;
import pay.initcode.v1.OwnerScope;
import pay.initcode.v1.ResolveRequest;
import pay.initcode.v1.ResolveResponse;
import pay.initcode.v1.ToggleInitCodeRequest;
import pay.initcode.v1.UpdateInitCodeRequest;

import java.util.List;
import java.util.Optional;
import java.util.UUID;

/**
 * Pierwsza prawdziwa domena core-svc (Faza 5). CRUD + Resolve dla kodów
 * inicjalizacji kontaktu (tag NFC / kod QR) — patrz proto/initcode/v1.
 * Egzekwuje scope właściciela przy KAŻDEJ mutacji (obrona w głąb — nie
 * ufamy ślepo samej obecności nagłówka wewnętrznego na api-gateway).
 */
@GrpcService
public class InitCodeGrpcService extends InitCodeServiceGrpc.InitCodeServiceImplBase {

    private final InitCodeRepository repository;

    public InitCodeGrpcService(InitCodeRepository repository) {
        this.repository = repository;
    }

    @Override
    public void create(CreateInitCodeRequest request, StreamObserver<InitCodeResponse> responseObserver) {
        OwnerIdentity owner = scopeOf(request.getOwner());
        if (owner.organizationId() == null && owner.ownerUserId() == null) {
            responseObserver.onError(Status.INVALID_ARGUMENT.withDescription("owner scope is required").asRuntimeException());
            return;
        }
        if (owner.organizationId() != null && !request.hasShopItemId()) {
            responseObserver.onError(Status.INVALID_ARGUMENT.withDescription("shop_item_id required for organization-owned codes").asRuntimeException());
            return;
        }
        if (owner.ownerUserId() != null && !request.hasTargetOrganizationId()) {
            responseObserver.onError(Status.INVALID_ARGUMENT.withDescription("target_organization_id required for personal codes").asRuntimeException());
            return;
        }

        InitCode entity = new InitCode(
                UUID.randomUUID().toString(),
                request.getLabel(),
                owner.organizationId(),
                owner.ownerUserId(),
                request.hasShopItemId() ? request.getShopItemId() : null,
                request.hasTargetOrganizationId() ? request.getTargetOrganizationId() : null
        );

        respond(responseObserver, repository.save(entity));
    }

    @Override
    public void update(UpdateInitCodeRequest request, StreamObserver<InitCodeResponse> responseObserver) {
        InitCode existing = ownedEntityOrError(request.getId(), request.getOwner(), responseObserver);
        if (existing == null) {
            return;
        }

        existing.setLabel(request.getLabel());
        if (request.hasShopItemId()) {
            existing.setShopItemId(request.getShopItemId());
        }
        if (request.hasTargetOrganizationId()) {
            existing.setTargetOrganizationId(request.getTargetOrganizationId());
        }

        respond(responseObserver, repository.save(existing));
    }

    @Override
    public void delete(DeleteInitCodeRequest request, StreamObserver<DeleteInitCodeResponse> responseObserver) {
        InitCode existing = ownedEntityOrError(request.getId(), request.getOwner(), responseObserver);
        if (existing == null) {
            return;
        }

        repository.delete(existing);
        responseObserver.onNext(DeleteInitCodeResponse.newBuilder().setDeleted(true).build());
        responseObserver.onCompleted();
    }

    @Override
    public void toggle(ToggleInitCodeRequest request, StreamObserver<InitCodeResponse> responseObserver) {
        InitCode existing = ownedEntityOrError(request.getId(), request.getOwner(), responseObserver);
        if (existing == null) {
            return;
        }

        existing.setActive(!existing.isActive());
        respond(responseObserver, repository.save(existing));
    }

    @Override
    public void list(ListInitCodesRequest request, StreamObserver<ListInitCodesResponse> responseObserver) {
        OwnerIdentity owner = scopeOf(request.getOwner());
        List<InitCode> codes;
        if (owner.organizationId() != null) {
            codes = repository.findByOrganizationId(owner.organizationId());
        } else if (owner.ownerUserId() != null) {
            codes = repository.findByOwnerUserId(owner.ownerUserId());
        } else {
            codes = List.of();
        }

        ListInitCodesResponse.Builder response = ListInitCodesResponse.newBuilder();
        codes.forEach(code -> response.addCodes(toResponse(code)));
        responseObserver.onNext(response.build());
        responseObserver.onCompleted();
    }

    @Override
    public void resolve(ResolveRequest request, StreamObserver<ResolveResponse> responseObserver) {
        Optional<InitCode> found = repository.findByUuidAndActiveTrue(request.getUuid());

        ResolveResponse response;
        if (found.isPresent()) {
            InitCode code = found.get();
            ResolveResponse.Builder builder = ResolveResponse.newBuilder()
                    .setFound(true)
                    .setUuid(request.getUuid());

            if (code.getShopItemId() != null) {
                builder.setTargetType(ResolveResponse.TargetType.SHOP_ITEM).setTargetId(code.getShopItemId());
            } else if (code.getTargetOrganizationId() != null) {
                builder.setTargetType(ResolveResponse.TargetType.ORGANIZATION).setTargetId(code.getTargetOrganizationId());
            } else {
                builder.setTargetType(ResolveResponse.TargetType.NONE).setFound(false);
            }
            response = builder.build();
        } else {
            response = ResolveResponse.newBuilder().setFound(false).setUuid(request.getUuid()).build();
        }

        responseObserver.onNext(response);
        responseObserver.onCompleted();
    }

    /** Dokładnie jedno pole niepuste, drugie null. */
    private record OwnerIdentity(Long organizationId, Long ownerUserId) {
    }

    private OwnerIdentity scopeOf(OwnerScope owner) {
        return switch (owner.getScopeCase()) {
            case ORGANIZATION_ID -> new OwnerIdentity(owner.getOrganizationId(), null);
            case OWNER_USER_ID -> new OwnerIdentity(null, owner.getOwnerUserId());
            default -> new OwnerIdentity(null, null);
        };
    }

    /**
     * Ładuje rekord po id i weryfikuje, że należy do przekazanego scope —
     * to jest egzekwowanie własności przeniesione z dzisiejszego
     * Panel/InitCodeController::guard() w Laravelu. Zwraca null (i już
     * wysłaną odpowiedź błędu) gdy nie znaleziono albo scope się nie zgadza.
     */
    private <T> InitCode ownedEntityOrError(long id, OwnerScope owner, StreamObserver<T> responseObserver) {
        InitCode existing = repository.findById(id).orElse(null);
        if (existing == null) {
            responseObserver.onError(Status.NOT_FOUND.asRuntimeException());
            return null;
        }

        OwnerIdentity requested = scopeOf(owner);
        boolean owns = (requested.organizationId() != null && requested.organizationId().equals(existing.getOrganizationId()))
                || (requested.ownerUserId() != null && requested.ownerUserId().equals(existing.getOwnerUserId()));
        if (!owns) {
            responseObserver.onError(Status.PERMISSION_DENIED.asRuntimeException());
            return null;
        }

        return existing;
    }

    private void respond(StreamObserver<InitCodeResponse> responseObserver, InitCode entity) {
        responseObserver.onNext(toResponse(entity));
        responseObserver.onCompleted();
    }

    private InitCodeResponse toResponse(InitCode entity) {
        InitCodeResponse.Builder builder = InitCodeResponse.newBuilder()
                .setId(entity.getId())
                .setUuid(entity.getUuid())
                .setLabel(entity.getLabel())
                .setActive(entity.isActive());

        if (entity.getOrganizationId() != null) {
            builder.setOrganizationId(entity.getOrganizationId());
        }
        if (entity.getOwnerUserId() != null) {
            builder.setOwnerUserId(entity.getOwnerUserId());
        }
        if (entity.getShopItemId() != null) {
            builder.setShopItemId(entity.getShopItemId());
        }
        if (entity.getTargetOrganizationId() != null) {
            builder.setTargetOrganizationId(entity.getTargetOrganizationId());
        }

        return builder.build();
    }
}
