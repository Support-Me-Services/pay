package com.supportme.pay.apigateway.organization;

import io.grpc.Status;
import io.grpc.StatusRuntimeException;
import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.DeleteMapping;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.PutMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;
import pay.organization.v1.CreateOrganizationRequest;
import pay.organization.v1.DeleteOrganizationRequest;
import pay.organization.v1.EnabledSections;
import pay.organization.v1.GetOrganizationByHandleRequest;
import pay.organization.v1.GetOrganizationRequest;
import pay.organization.v1.ListOrganizationsByOwnerRequest;
import pay.organization.v1.OrganizationServiceGrpc;
import pay.organization.v1.UpdateOrganizationRequest;

import java.util.List;
import java.util.Map;
import java.util.concurrent.TimeUnit;
import java.util.function.Supplier;
import java.util.stream.Collectors;

/**
 * CRUD dla organizacji — WYŁĄCZNIE do wywołania przez gateway-svc (Laravel)
 * w trakcie okresu przejściowego, patrz InternalInitCodeController (ten sam
 * wzorzec: prefiks `/internal/`, nagłówek X-Internal-Api-Key).
 */
@RestController
@RequestMapping("/internal/v1/organizations")
public class InternalOrganizationController {

    private final OrganizationServiceGrpc.OrganizationServiceBlockingStub stub;

    public InternalOrganizationController(
            @Qualifier("cmsSvcOrganizationStub") OrganizationServiceGrpc.OrganizationServiceBlockingStub stub) {
        this.stub = stub;
    }

    @PostMapping
    public ResponseEntity<?> create(@RequestBody CreateOrganizationDto body) {
        return handle(() -> {
            CreateOrganizationRequest.Builder request = CreateOrganizationRequest.newBuilder()
                    .setOwnerKeycloakSub(body.ownerKeycloakSub())
                    .setName(body.name())
                    .setHandle(body.handle());
            if (body.logo() != null) {
                request.setLogo(body.logo());
            }

            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).create(request.build());
            return ResponseEntity.status(HttpStatus.CREATED).body(OrganizationDto.from(response));
        });
    }

    @GetMapping("/{id}")
    public ResponseEntity<?> get(@PathVariable long id) {
        return handle(() -> {
            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).get(
                    GetOrganizationRequest.newBuilder().setId(id).build());
            return ResponseEntity.ok(OrganizationDto.from(response));
        });
    }

    @GetMapping("/by-handle/{handle}")
    public ResponseEntity<?> getByHandle(@PathVariable String handle) {
        return handle(() -> {
            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).getByHandle(
                    GetOrganizationByHandleRequest.newBuilder().setHandle(handle).build());
            return ResponseEntity.ok(OrganizationDto.from(response));
        });
    }

    @GetMapping
    public ResponseEntity<?> listByOwner(@RequestParam String ownerKeycloakSub) {
        return handle(() -> {
            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).listByOwner(
                    ListOrganizationsByOwnerRequest.newBuilder().setOwnerKeycloakSub(ownerKeycloakSub).build());
            List<OrganizationDto> organizations = response.getOrganizationsList().stream()
                    .map(OrganizationDto::from)
                    .collect(Collectors.toList());
            return ResponseEntity.ok(organizations);
        });
    }

    @PutMapping("/{id}")
    public ResponseEntity<?> update(@PathVariable long id, @RequestBody UpdateOrganizationDto body) {
        return handle(() -> {
            UpdateOrganizationRequest.Builder request = UpdateOrganizationRequest.newBuilder()
                    .setId(id)
                    .setOwnerKeycloakSub(body.ownerKeycloakSub())
                    .setName(body.name());
            if (body.logo() != null) {
                request.setLogo(body.logo());
            }
            if (body.enabledSections() != null) {
                request.setEnabledSections(EnabledSections.newBuilder().addAllSections(body.enabledSections()).build());
            }

            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).update(request.build());
            return ResponseEntity.ok(OrganizationDto.from(response));
        });
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<?> delete(@PathVariable long id, @RequestParam String ownerKeycloakSub) {
        return handle(() -> {
            stub.withDeadlineAfter(2, TimeUnit.SECONDS).delete(
                    DeleteOrganizationRequest.newBuilder().setId(id).setOwnerKeycloakSub(ownerKeycloakSub).build());
            return ResponseEntity.noContent().build();
        });
    }

    /** Tłumaczy statusy gRPC z cms-svc (egzekwowanie własności, walidacja) na HTTP. */
    private ResponseEntity<?> handle(Supplier<ResponseEntity<?>> block) {
        try {
            return block.get();
        } catch (StatusRuntimeException e) {
            HttpStatus status = switch (e.getStatus().getCode()) {
                case NOT_FOUND -> HttpStatus.NOT_FOUND;
                case PERMISSION_DENIED -> HttpStatus.FORBIDDEN;
                case INVALID_ARGUMENT -> HttpStatus.BAD_REQUEST;
                default -> HttpStatus.BAD_GATEWAY;
            };
            String message = e.getStatus().getDescription() != null
                    ? e.getStatus().getDescription()
                    : e.getStatus().getCode().name();
            return ResponseEntity.status(status).body(Map.of("error", message));
        }
    }
}
