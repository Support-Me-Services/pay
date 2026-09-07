package com.supportme.pay.orgsvc.organization;

import io.grpc.Status;
import io.grpc.stub.StreamObserver;
import net.devh.boot.grpc.server.service.GrpcService;
import pay.organization.v1.CreateOrganizationRequest;
import pay.organization.v1.EnabledSections;
import pay.organization.v1.GetOrganizationRequest;
import pay.organization.v1.ListAllOrganizationsRequest;
import pay.organization.v1.ListOrganizationsByOwnerRequest;
import pay.organization.v1.ListOrganizationsResponse;
import pay.organization.v1.OrganizationResponse;
import pay.organization.v1.OrganizationServiceGrpc;
import pay.organization.v1.UpdateOrganizationNameRequest;
import pay.organization.v1.UpdateOrganizationOwnerRequest;
import pay.organization.v1.UpdateOrganizationSectionsRequest;

import java.text.Normalizer;
import java.util.Arrays;
import java.util.List;
import java.util.Locale;

/**
 * Pierwsza prawdziwa domena org-svc. CRUD dla organizacji — patrz
 * proto/organization/v1 i OrganizationsController w Laravelu, skąd ta
 * domena się wywodzi. Egzekwuje własność (userId rekordu == userId
 * wołającego) przy każdej mutacji — obrona w głąb, tak jak
 * InitCodeGrpcService w core-svc.
 */
@GrpcService
public class OrganizationGrpcService extends OrganizationServiceGrpc.OrganizationServiceImplBase {

    /** Mirror Organization::SECTIONS (kluczy) w Laravelu — kolejność bez znaczenia. */
    private static final List<String> SECTION_KEYS = List.of(
            "beneficiaries", "shop-items", "positions", "applications", "init-codes");

    private final OrganizationRepository repository;

    public OrganizationGrpcService(OrganizationRepository repository) {
        this.repository = repository;
    }

    @Override
    public void create(CreateOrganizationRequest request, StreamObserver<OrganizationResponse> responseObserver) {
        String name = request.getName().trim();
        if (name.isEmpty()) {
            responseObserver.onError(Status.INVALID_ARGUMENT.withDescription("name is required").asRuntimeException());
            return;
        }

        String handle = uniqueHandle(name);
        // Nowa organizacja startuje z pustą widocznością sekcji (nic
        // zaznaczone) — mirror OrganizationsController::store() w Laravelu.
        Organization entity = new Organization(request.getUserId(), name, handle, new String[0]);

        respond(responseObserver, repository.save(entity));
    }

    @Override
    public void updateName(UpdateOrganizationNameRequest request, StreamObserver<OrganizationResponse> responseObserver) {
        Organization existing = ownedEntityOrError(request.getId(), request.getUserId(), responseObserver);
        if (existing == null) {
            return;
        }

        String name = request.getName().trim();
        if (name.isEmpty()) {
            responseObserver.onError(Status.INVALID_ARGUMENT.withDescription("name is required").asRuntimeException());
            return;
        }

        existing.setName(name);
        respond(responseObserver, repository.save(existing));
    }

    @Override
    public void updateSections(UpdateOrganizationSectionsRequest request, StreamObserver<OrganizationResponse> responseObserver) {
        Organization existing = ownedEntityOrError(request.getId(), request.getUserId(), responseObserver);
        if (existing == null) {
            return;
        }

        if (!request.hasSections()) {
            // Brak pola == jawnie "wszystko widoczne", mirror `null` w Laravelu.
            existing.setEnabledSections(null);
            respond(responseObserver, repository.save(existing));
            return;
        }

        List<String> selected = request.getSections().getKeysList();
        for (String key : selected) {
            if (!SECTION_KEYS.contains(key)) {
                responseObserver.onError(Status.INVALID_ARGUMENT
                        .withDescription("unknown section key: " + key).asRuntimeException());
                return;
            }
        }

        // Zaznaczone wszystkie sekcje == NULL (jawnie "bez ograniczeń") —
        // mirror OrganizationsController::updateSettings() w Laravelu.
        boolean allSelected = selected.size() == SECTION_KEYS.size();
        existing.setEnabledSections(allSelected ? null : selected.toArray(new String[0]));

        respond(responseObserver, repository.save(existing));
    }

    @Override
    public void updateOwner(UpdateOrganizationOwnerRequest request, StreamObserver<OrganizationResponse> responseObserver) {
        Organization existing = repository.findById(request.getId()).orElse(null);
        if (existing == null) {
            responseObserver.onError(Status.NOT_FOUND.asRuntimeException());
            return;
        }

        existing.setUserId(request.getNewUserId());
        respond(responseObserver, repository.save(existing));
    }

    @Override
    public void get(GetOrganizationRequest request, StreamObserver<OrganizationResponse> responseObserver) {
        Organization existing = repository.findById(request.getId()).orElse(null);
        if (existing == null) {
            responseObserver.onError(Status.NOT_FOUND.asRuntimeException());
            return;
        }

        respond(responseObserver, existing);
    }

    @Override
    public void listByOwner(ListOrganizationsByOwnerRequest request, StreamObserver<ListOrganizationsResponse> responseObserver) {
        List<Organization> organizations = repository.findByUserIdOrderByNameAsc(request.getUserId());
        respondList(responseObserver, organizations);
    }

    @Override
    public void listAll(ListAllOrganizationsRequest request, StreamObserver<ListOrganizationsResponse> responseObserver) {
        List<Organization> organizations = repository.findAllByOrderByNameAsc();
        respondList(responseObserver, organizations);
    }

    /**
     * Ładuje rekord po id i weryfikuje, że należy do przekazanego userId —
     * egzekwowanie własności przeniesione z dzisiejszego
     * $request->user()->activeOrganization() w Laravelu. Zwraca null (i już
     * wysłaną odpowiedź błędu) gdy nie znaleziono albo właściciel się nie zgadza.
     */
    private <T> Organization ownedEntityOrError(long id, String userId, StreamObserver<T> responseObserver) {
        Organization existing = repository.findById(id).orElse(null);
        if (existing == null) {
            responseObserver.onError(Status.NOT_FOUND.asRuntimeException());
            return null;
        }

        if (!existing.getUserId().equals(userId)) {
            responseObserver.onError(Status.PERMISSION_DENIED.asRuntimeException());
            return null;
        }

        return existing;
    }

    private String uniqueHandle(String name) {
        String base = slug(name);
        if (base.isEmpty()) {
            base = "organizacja";
        }

        String handle = base;
        int i = 2;
        while (repository.existsByHandle(handle)) {
            handle = base + "-" + (i++);
        }

        return handle;
    }

    /** Odpowiednik Str::slug() z Laravela — bez dodatkowej zależności. */
    private static String slug(String input) {
        String normalized = Normalizer.normalize(input, Normalizer.Form.NFKD)
                .replaceAll("\\p{M}", "");
        String slug = normalized.toLowerCase(Locale.ROOT)
                .replaceAll("[^a-z0-9]+", "-")
                .replaceAll("^-+|-+$", "");
        return slug;
    }

    private void respond(StreamObserver<OrganizationResponse> responseObserver, Organization entity) {
        responseObserver.onNext(toResponse(entity));
        responseObserver.onCompleted();
    }

    private void respondList(StreamObserver<ListOrganizationsResponse> responseObserver, List<Organization> organizations) {
        ListOrganizationsResponse.Builder response = ListOrganizationsResponse.newBuilder();
        organizations.forEach(org -> response.addOrganizations(toResponse(org)));
        responseObserver.onNext(response.build());
        responseObserver.onCompleted();
    }

    private OrganizationResponse toResponse(Organization entity) {
        OrganizationResponse.Builder builder = OrganizationResponse.newBuilder()
                .setId(entity.getId())
                .setUserId(entity.getUserId())
                .setName(entity.getName())
                .setHandle(entity.getHandle());

        if (entity.getLogo() != null) {
            builder.setLogo(entity.getLogo());
        }
        if (entity.getEnabledSections() != null) {
            builder.setEnabledSections(EnabledSections.newBuilder()
                    .addAllKeys(Arrays.asList(entity.getEnabledSections()))
                    .build());
        }

        return builder.build();
    }
}
