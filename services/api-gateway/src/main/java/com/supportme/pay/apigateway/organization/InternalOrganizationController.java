package com.supportme.pay.apigateway.organization;

import io.grpc.Status;
import io.grpc.StatusRuntimeException;
import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.PutMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;
import pay.organization.v1.CreateOrganizationRequest;
import pay.organization.v1.EnabledSections;
import pay.organization.v1.GetOrganizationRequest;
import pay.organization.v1.ListAllOrganizationsRequest;
import pay.organization.v1.ListOrganizationsByOwnerRequest;
import pay.organization.v1.OrganizationServiceGrpc;
import pay.organization.v1.UpdateOrganizationNameRequest;
import pay.organization.v1.UpdateOrganizationOwnerRequest;
import pay.organization.v1.UpdateOrganizationSectionsRequest;

import java.util.List;
import java.util.Map;
import java.util.concurrent.TimeUnit;
import java.util.function.Supplier;
import java.util.stream.Collectors;

/**
 * CRUD dla organizacji — WYŁĄCZNIE do wywołania przez gateway-svc (Laravel),
 * server-to-server, tak jak InternalInitCodeController. Chronione przez
 * prefiks `/internal/` (nagłówek X-Internal-Api-Key), patrz SecurityConfig.
 */
@RestController
@RequestMapping("/internal/v1/organizations")
public class InternalOrganizationController {

    private final OrganizationServiceGrpc.OrganizationServiceBlockingStub stub;

    public InternalOrganizationController(
            @Qualifier("orgSvcOrganizationStub") OrganizationServiceGrpc.OrganizationServiceBlockingStub stub) {
        this.stub = stub;
    }

    @PostMapping
    public ResponseEntity<?> create(@RequestBody CreateOrganizationDto body) {
        return handle(() -> {
            var response = stub.withDeadlineAfter(5, TimeUnit.SECONDS).create(
                    CreateOrganizationRequest.newBuilder()
                            .setUserId(body.userId())
                            .setName(body.name())
                            .build());
            return ResponseEntity.status(HttpStatus.CREATED).body(OrganizationDto.from(response));
        });
    }

    @GetMapping("/{id}")
    public ResponseEntity<?> get(@PathVariable long id) {
        return handle(() -> {
            var response = stub.withDeadlineAfter(5, TimeUnit.SECONDS).get(
                    GetOrganizationRequest.newBuilder().setId(id).build());
            return ResponseEntity.ok(OrganizationDto.from(response));
        });
    }

    /** {@code userId} obecny -> organizacje TEGO konta; brak -> wszystkie (widok admina). */
    @GetMapping
    public ResponseEntity<?> list(@RequestParam(required = false) String userId) {
        return handle(() -> {
            var response = userId != null
                    ? stub.withDeadlineAfter(5, TimeUnit.SECONDS).listByOwner(
                            ListOrganizationsByOwnerRequest.newBuilder().setUserId(userId).build())
                    : stub.withDeadlineAfter(5, TimeUnit.SECONDS).listAll(
                            ListAllOrganizationsRequest.newBuilder().build());
            List<OrganizationDto> organizations = response.getOrganizationsList().stream()
                    .map(OrganizationDto::from)
                    .collect(Collectors.toList());
            return ResponseEntity.ok(organizations);
        });
    }

    @PutMapping("/{id}/name")
    public ResponseEntity<?> updateName(@PathVariable long id, @RequestBody UpdateOrganizationNameDto body) {
        return handle(() -> {
            var response = stub.withDeadlineAfter(5, TimeUnit.SECONDS).updateName(
                    UpdateOrganizationNameRequest.newBuilder()
                            .setId(id)
                            .setUserId(body.userId())
                            .setName(body.name())
                            .build());
            return ResponseEntity.ok(OrganizationDto.from(response));
        });
    }

    @PutMapping("/{id}/sections")
    public ResponseEntity<?> updateSections(@PathVariable long id, @RequestBody UpdateOrganizationSectionsDto body) {
        return handle(() -> {
            UpdateOrganizationSectionsRequest.Builder request = UpdateOrganizationSectionsRequest.newBuilder()
                    .setId(id)
                    .setUserId(body.userId());
            if (body.sections() != null) {
                request.setSections(EnabledSections.newBuilder().addAllKeys(body.sections()).build());
            }

            var response = stub.withDeadlineAfter(5, TimeUnit.SECONDS).updateSections(request.build());
            return ResponseEntity.ok(OrganizationDto.from(response));
        });
    }

    @PutMapping("/{id}/owner")
    public ResponseEntity<?> updateOwner(@PathVariable long id, @RequestBody UpdateOrganizationOwnerDto body) {
        return handle(() -> {
            var response = stub.withDeadlineAfter(5, TimeUnit.SECONDS).updateOwner(
                    UpdateOrganizationOwnerRequest.newBuilder()
                            .setId(id)
                            .setNewUserId(body.newUserId())
                            .build());
            return ResponseEntity.ok(OrganizationDto.from(response));
        });
    }

    /** Tłumaczy statusy gRPC z org-svc (egzekwowanie własności, walidacja) na HTTP. */
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
