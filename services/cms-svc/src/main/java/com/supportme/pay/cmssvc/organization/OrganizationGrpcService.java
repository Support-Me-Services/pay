package com.supportme.pay.cmssvc.organization;

import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.core.type.TypeReference;
import com.fasterxml.jackson.databind.ObjectMapper;
import io.grpc.Status;
import io.grpc.stub.StreamObserver;
import net.devh.boot.grpc.server.service.GrpcService;
import pay.organization.v1.CreateOrganizationRequest;
import pay.organization.v1.DeleteOrganizationRequest;
import pay.organization.v1.DeleteOrganizationResponse;
import pay.organization.v1.EnabledSections;
import pay.organization.v1.GetOrganizationByHandleRequest;
import pay.organization.v1.GetOrganizationRequest;
import pay.organization.v1.ListOrganizationsByOwnerRequest;
import pay.organization.v1.ListOrganizationsResponse;
import pay.organization.v1.OrganizationResponse;
import pay.organization.v1.OrganizationServiceGrpc;
import pay.organization.v1.UpdateOrganizationRequest;

import java.util.List;

/**
 * Pierwsza dziedzina cms-svc — Organization (root encja, od której zależą
 * BeneficiaryNode/JobPosition/JobApplication/ShopItem). Egzekwuje własność
 * (ownerKeycloakSub) przy każdej mutacji.
 */
@GrpcService
public class OrganizationGrpcService extends OrganizationServiceGrpc.OrganizationServiceImplBase {

    private final OrganizationRepository repository;
    private final ObjectMapper objectMapper;

    public OrganizationGrpcService(OrganizationRepository repository, ObjectMapper objectMapper) {
        this.repository = repository;
        this.objectMapper = objectMapper;
    }

    @Override
    public void create(CreateOrganizationRequest request, StreamObserver<OrganizationResponse> responseObserver) {
        Organization entity = new Organization(
                request.getOwnerKeycloakSub(),
                request.getName(),
                request.getHandle(),
                request.hasLogo() ? request.getLogo() : null
        );
        respond(responseObserver, repository.save(entity));
    }

    @Override
    public void update(UpdateOrganizationRequest request, StreamObserver<OrganizationResponse> responseObserver) {
        Organization existing = ownedEntityOrError(request.getId(), request.getOwnerKeycloakSub(), responseObserver);
        if (existing == null) {
            return;
        }

        existing.setName(request.getName());
        if (request.hasLogo()) {
            existing.setLogo(request.getLogo());
        }
        if (request.hasEnabledSections()) {
            String json = toJson(request.getEnabledSections(), responseObserver);
            if (json == null) {
                return; // toJson already sent an onError
            }
            existing.setEnabledSectionsJson(json);
        }

        respond(responseObserver, repository.save(existing));
    }

    @Override
    public void delete(DeleteOrganizationRequest request, StreamObserver<DeleteOrganizationResponse> responseObserver) {
        Organization existing = ownedEntityOrError(request.getId(), request.getOwnerKeycloakSub(), responseObserver);
        if (existing == null) {
            return;
        }

        repository.delete(existing);
        responseObserver.onNext(DeleteOrganizationResponse.newBuilder().setDeleted(true).build());
        responseObserver.onCompleted();
    }

    @Override
    public void get(GetOrganizationRequest request, StreamObserver<OrganizationResponse> responseObserver) {
        repository.findById(request.getId())
                .ifPresentOrElse(
                        org -> respond(responseObserver, org),
                        () -> responseObserver.onError(Status.NOT_FOUND.asRuntimeException()));
    }

    @Override
    public void getByHandle(GetOrganizationByHandleRequest request, StreamObserver<OrganizationResponse> responseObserver) {
        repository.findByHandle(request.getHandle())
                .ifPresentOrElse(
                        org -> respond(responseObserver, org),
                        () -> responseObserver.onError(Status.NOT_FOUND.asRuntimeException()));
    }

    @Override
    public void listByOwner(ListOrganizationsByOwnerRequest request, StreamObserver<ListOrganizationsResponse> responseObserver) {
        List<Organization> organizations = repository.findByOwnerKeycloakSub(request.getOwnerKeycloakSub());
        ListOrganizationsResponse.Builder response = ListOrganizationsResponse.newBuilder();
        organizations.forEach(org -> response.addOrganizations(toResponse(org)));
        responseObserver.onNext(response.build());
        responseObserver.onCompleted();
    }

    /**
     * Ładuje rekord po id i weryfikuje, że należy do przekazanego właściciela —
     * egzekwowanie własności przeniesione z dzisiejszego Laravela. Zwraca
     * null (i już wysłaną odpowiedź błędu) gdy nie znaleziono/nie zgadza się.
     */
    private <T> Organization ownedEntityOrError(long id, String ownerKeycloakSub, StreamObserver<T> responseObserver) {
        Organization existing = repository.findById(id).orElse(null);
        if (existing == null) {
            responseObserver.onError(Status.NOT_FOUND.asRuntimeException());
            return null;
        }
        if (!existing.getOwnerKeycloakSub().equals(ownerKeycloakSub)) {
            responseObserver.onError(Status.PERMISSION_DENIED.asRuntimeException());
            return null;
        }
        return existing;
    }

    private void respond(StreamObserver<OrganizationResponse> responseObserver, Organization entity) {
        responseObserver.onNext(toResponse(entity));
        responseObserver.onCompleted();
    }

    private OrganizationResponse toResponse(Organization entity) {
        OrganizationResponse.Builder builder = OrganizationResponse.newBuilder()
                .setId(entity.getId())
                .setOwnerKeycloakSub(entity.getOwnerKeycloakSub())
                .setName(entity.getName())
                .setHandle(entity.getHandle());

        if (entity.getLogo() != null) {
            builder.setLogo(entity.getLogo());
        }
        if (entity.getEnabledSectionsJson() != null) {
            try {
                List<String> sections = objectMapper.readValue(
                        entity.getEnabledSectionsJson(), new TypeReference<List<String>>() {
                        });
                builder.setEnabledSections(EnabledSections.newBuilder().addAllSections(sections).build());
            } catch (JsonProcessingException e) {
                // Nie powinno się zdarzyć (jedyny pisarz tej kolumny to toJson() niżej) —
                // lepiej zwrócić encję bez sekcji niż wysypać całe wywołanie.
            }
        }

        return builder.build();
    }

    private <T> String toJson(EnabledSections sections, StreamObserver<T> responseObserver) {
        try {
            return objectMapper.writeValueAsString(sections.getSectionsList());
        } catch (JsonProcessingException e) {
            responseObserver.onError(Status.INTERNAL.withDescription("failed to serialize enabled_sections").asRuntimeException());
            return null;
        }
    }
}
