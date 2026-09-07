package com.supportme.pay.apigateway.careers;

import io.grpc.StatusRuntimeException;
import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import pay.careers.v1.*;

import java.util.List;
import java.util.Map;
import java.util.concurrent.TimeUnit;
import java.util.function.Supplier;
import java.util.stream.Collectors;

/** CRUD dla stanowisk pracy — WYŁĄCZNIE do wywołania przez gateway-svc (Laravel). */
@RestController
@RequestMapping("/internal/v1/job-positions")
public class InternalJobPositionController {

    private final JobPositionServiceGrpc.JobPositionServiceBlockingStub stub;

    public InternalJobPositionController(
            @Qualifier("orgSvcJobPositionStub") JobPositionServiceGrpc.JobPositionServiceBlockingStub stub) {
        this.stub = stub;
    }

    @PostMapping
    public ResponseEntity<?> create(@RequestBody UpsertJobPositionDto body) {
        return handle(() -> {
            var request = CreateJobPositionRequest.newBuilder()
                    .setOrganizationId(body.organizationId())
                    .setTitle(body.title())
                    .setSort(body.sort() != null ? body.sort() : 0)
                    .setActive(body.active() == null || body.active());
            if (body.location() != null) request.setLocation(body.location());
            if (body.employmentType() != null) request.setEmploymentType(body.employmentType());
            if (body.descriptionHtml() != null) request.setDescriptionHtml(body.descriptionHtml());
            if (body.shortDescription() != null) request.setShortDescription(body.shortDescription());

            var response = stub.withDeadlineAfter(5, TimeUnit.SECONDS).create(request.build());
            return ResponseEntity.status(HttpStatus.CREATED).body(JobPositionDto.from(response));
        });
    }

    @GetMapping("/{id}")
    public ResponseEntity<?> get(@PathVariable long id) {
        return handle(() -> {
            var response = stub.withDeadlineAfter(5, TimeUnit.SECONDS).get(GetJobPositionRequest.newBuilder().setId(id).build());
            return ResponseEntity.ok(JobPositionDto.from(response));
        });
    }

    @GetMapping
    public ResponseEntity<?> list(@RequestParam long organizationId, @RequestParam(required = false) Boolean activeOnly) {
        return handle(() -> {
            var request = ListJobPositionsRequest.newBuilder().setOrganizationId(organizationId).build();
            var response = Boolean.TRUE.equals(activeOnly)
                    ? stub.withDeadlineAfter(5, TimeUnit.SECONDS).listActiveByOrganization(request)
                    : stub.withDeadlineAfter(5, TimeUnit.SECONDS).listByOrganization(request);
            List<JobPositionDto> positions = response.getPositionsList().stream().map(JobPositionDto::from).collect(Collectors.toList());
            return ResponseEntity.ok(positions);
        });
    }

    @PutMapping("/{id}")
    public ResponseEntity<?> update(@PathVariable long id, @RequestBody UpsertJobPositionDto body) {
        return handle(() -> {
            var request = UpdateJobPositionRequest.newBuilder()
                    .setId(id)
                    .setOrganizationId(body.organizationId())
                    .setTitle(body.title())
                    .setSort(body.sort() != null ? body.sort() : 0)
                    .setActive(body.active() == null || body.active());
            if (body.location() != null) request.setLocation(body.location());
            if (body.employmentType() != null) request.setEmploymentType(body.employmentType());
            if (body.descriptionHtml() != null) request.setDescriptionHtml(body.descriptionHtml());
            if (body.shortDescription() != null) request.setShortDescription(body.shortDescription());

            var response = stub.withDeadlineAfter(5, TimeUnit.SECONDS).update(request.build());
            return ResponseEntity.ok(JobPositionDto.from(response));
        });
    }

    @PostMapping("/{id}/toggle")
    public ResponseEntity<?> toggle(@PathVariable long id, @RequestParam long organizationId) {
        return handle(() -> {
            var response = stub.withDeadlineAfter(5, TimeUnit.SECONDS).toggle(
                    ToggleJobPositionRequest.newBuilder().setId(id).setOrganizationId(organizationId).build());
            return ResponseEntity.ok(JobPositionDto.from(response));
        });
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<?> delete(@PathVariable long id, @RequestParam long organizationId) {
        return handle(() -> {
            stub.withDeadlineAfter(5, TimeUnit.SECONDS).delete(
                    DeleteJobPositionRequest.newBuilder().setId(id).setOrganizationId(organizationId).build());
            return ResponseEntity.noContent().build();
        });
    }

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
            String message = e.getStatus().getDescription() != null ? e.getStatus().getDescription() : e.getStatus().getCode().name();
            return ResponseEntity.status(status).body(Map.of("error", message));
        }
    }
}
