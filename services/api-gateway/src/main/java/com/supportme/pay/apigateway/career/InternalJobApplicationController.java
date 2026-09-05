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
import pay.career.v1.CreateJobApplicationRequest;
import pay.career.v1.DeleteJobApplicationRequest;
import pay.career.v1.JobApplicationServiceGrpc;
import pay.career.v1.ListJobApplicationsRequest;
import pay.career.v1.UpdateJobApplicationStatusRequest;

import java.util.List;
import java.util.Map;
import java.util.concurrent.TimeUnit;
import java.util.function.Supplier;
import java.util.stream.Collectors;

/**
 * Zgłoszenia rekrutacyjne. `create` jest de facto publiczne (formularz
 * aplikacji na stronie kariery) — ale i tak wchodzi przez `/internal/`, bo
 * dziś WYŁĄCZNIE Laravel woła api-gateway (żadna przeglądarka nie woła stąd
 * bezpośrednio), mirror InternalInitCodeController.
 */
@RestController
@RequestMapping("/internal/v1/job-applications")
public class InternalJobApplicationController {

    private final JobApplicationServiceGrpc.JobApplicationServiceBlockingStub stub;

    public InternalJobApplicationController(
            @Qualifier("cmsSvcJobApplicationStub") JobApplicationServiceGrpc.JobApplicationServiceBlockingStub stub) {
        this.stub = stub;
    }

    @PostMapping
    public ResponseEntity<?> create(@RequestBody CreateJobApplicationDto body) {
        return handle(() -> {
            CreateJobApplicationRequest.Builder request = CreateJobApplicationRequest.newBuilder()
                    .setName(body.name())
                    .setEmail(body.email())
                    .setFutureRecruitmentConsent(body.futureRecruitmentConsent());
            if (body.jobPositionId() != null) {
                request.setJobPositionId(body.jobPositionId());
            }
            if (body.organizationId() != null) {
                request.setOrganizationId(body.organizationId());
            }
            if (body.phone() != null) {
                request.setPhone(body.phone());
            }
            if (body.message() != null) {
                request.setMessage(body.message());
            }
            if (body.cvPath() != null) {
                request.setCvPath(body.cvPath());
            }
            if (body.cvOriginalName() != null) {
                request.setCvOriginalName(body.cvOriginalName());
            }

            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).create(request.build());
            return ResponseEntity.status(HttpStatus.CREATED).body(JobApplicationDto.from(response));
        });
    }

    @GetMapping
    public ResponseEntity<?> listByOrganization(@RequestParam(required = false) Long organizationId) {
        return handle(() -> {
            ListJobApplicationsRequest.Builder request = ListJobApplicationsRequest.newBuilder();
            if (organizationId != null) {
                request.setOrganizationId(organizationId);
            }

            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).listByOrganization(request.build());
            List<JobApplicationDto> applications = response.getApplicationsList().stream()
                    .map(JobApplicationDto::from)
                    .collect(Collectors.toList());
            return ResponseEntity.ok(applications);
        });
    }

    @PutMapping("/{id}/status")
    public ResponseEntity<?> updateStatus(@PathVariable long id, @RequestBody UpdateJobApplicationStatusDto body) {
        return handle(() -> {
            UpdateJobApplicationStatusRequest.Builder request = UpdateJobApplicationStatusRequest.newBuilder()
                    .setId(id)
                    .setStatus(body.status())
                    .setIsRead(body.isRead());
            if (body.organizationId() != null) {
                request.setOrganizationId(body.organizationId());
            }

            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).updateStatus(request.build());
            return ResponseEntity.ok(JobApplicationDto.from(response));
        });
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<?> delete(@PathVariable long id, @RequestParam(required = false) Long organizationId) {
        return handle(() -> {
            DeleteJobApplicationRequest.Builder request = DeleteJobApplicationRequest.newBuilder().setId(id);
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
