package com.supportme.pay.apigateway.career;

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
import pay.career.v1.CreateJobPositionRequest;
import pay.career.v1.DeleteJobPositionRequest;
import pay.career.v1.GetJobPositionRequest;
import pay.career.v1.JobPositionServiceGrpc;
import pay.career.v1.ListJobPositionsRequest;
import pay.career.v1.UpdateJobPositionRequest;

import java.util.List;
import java.util.Map;
import java.util.concurrent.TimeUnit;
import java.util.function.Supplier;
import java.util.stream.Collectors;

/** CRUD dla ofert pracy — mirror InternalOrganizationController. */
@RestController
@RequestMapping("/internal/v1/job-positions")
public class InternalJobPositionController {

    private final JobPositionServiceGrpc.JobPositionServiceBlockingStub stub;

    public InternalJobPositionController(
            @Qualifier("cmsSvcJobPositionStub") JobPositionServiceGrpc.JobPositionServiceBlockingStub stub) {
        this.stub = stub;
    }

    @PostMapping
    public ResponseEntity<?> create(@RequestBody CreateJobPositionDto body) {
        return handle(() -> {
            CreateJobPositionRequest.Builder request = CreateJobPositionRequest.newBuilder()
                    .setTitle(body.title())
                    .setSort(body.sort());
            if (body.organizationId() != null) {
                request.setOrganizationId(body.organizationId());
            }
            if (body.location() != null) {
                request.setLocation(body.location());
            }
            if (body.employmentType() != null) {
                request.setEmploymentType(body.employmentType());
            }
            if (body.descriptionHtml() != null) {
                request.setDescriptionHtml(body.descriptionHtml());
            }
            if (body.shortDescription() != null) {
                request.setShortDescription(body.shortDescription());
            }

            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).create(request.build());
            return ResponseEntity.status(HttpStatus.CREATED).body(JobPositionDto.from(response));
        });
    }

    @GetMapping("/{id}")
    public ResponseEntity<?> get(@PathVariable long id) {
        return handle(() -> {
            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).get(
                    GetJobPositionRequest.newBuilder().setId(id).build());
            return ResponseEntity.ok(JobPositionDto.from(response));
        });
    }

    @GetMapping
    public ResponseEntity<?> listByOrganization(
            @RequestParam(required = false) Long organizationId,
            @RequestParam(required = false, defaultValue = "false") boolean activeOnly) {
        return handle(() -> {
            ListJobPositionsRequest.Builder request = ListJobPositionsRequest.newBuilder().setActiveOnly(activeOnly);
            if (organizationId != null) {
                request.setOrganizationId(organizationId);
            }

            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).listByOrganization(request.build());
            List<JobPositionDto> positions = response.getPositionsList().stream()
                    .map(JobPositionDto::from)
                    .collect(Collectors.toList());
            return ResponseEntity.ok(positions);
        });
    }

    @PutMapping("/{id}")
    public ResponseEntity<?> update(@PathVariable long id, @RequestBody UpdateJobPositionDto body) {
        return handle(() -> {
            UpdateJobPositionRequest.Builder request = UpdateJobPositionRequest.newBuilder()
                    .setId(id)
                    .setTitle(body.title())
                    .setActive(body.active())
                    .setSort(body.sort());
            if (body.organizationId() != null) {
                request.setOrganizationId(body.organizationId());
            }
            if (body.location() != null) {
                request.setLocation(body.location());
            }
            if (body.employmentType() != null) {
                request.setEmploymentType(body.employmentType());
            }
            if (body.descriptionHtml() != null) {
                request.setDescriptionHtml(body.descriptionHtml());
            }
            if (body.shortDescription() != null) {
                request.setShortDescription(body.shortDescription());
            }

            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).update(request.build());
            return ResponseEntity.ok(JobPositionDto.from(response));
        });
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<?> delete(@PathVariable long id, @RequestParam(required = false) Long organizationId) {
        return handle(() -> {
            DeleteJobPositionRequest.Builder request = DeleteJobPositionRequest.newBuilder().setId(id);
            if (organizationId != null) {
                request.setOrganizationId(organizationId);
            }
            stub.withDeadlineAfter(2, TimeUnit.SECONDS).delete(request.build());
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
            String message = e.getStatus().getDescription() != null
                    ? e.getStatus().getDescription()
                    : e.getStatus().getCode().name();
            return ResponseEntity.status(status).body(Map.of("error", message));
        }
    }
}
