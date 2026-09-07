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

/** CRUD dla aplikacji o pracę — WYŁĄCZNIE do wywołania przez gateway-svc (Laravel). */
@RestController
@RequestMapping("/internal/v1/job-applications")
public class InternalJobApplicationController {

    private final JobApplicationServiceGrpc.JobApplicationServiceBlockingStub stub;

    public InternalJobApplicationController(
            @Qualifier("orgSvcJobApplicationStub") JobApplicationServiceGrpc.JobApplicationServiceBlockingStub stub) {
        this.stub = stub;
    }

    @PostMapping
    public ResponseEntity<?> create(@RequestBody CreateJobApplicationDto body) {
        return handle(() -> {
            var request = CreateJobApplicationRequest.newBuilder()
                    .setOrganizationId(body.organizationId())
                    .setName(body.name())
                    .setEmail(body.email())
                    .setFutureRecruitmentConsent(body.futureRecruitmentConsent());
            if (body.jobPositionId() != null) request.setJobPositionId(body.jobPositionId());
            if (body.phone() != null) request.setPhone(body.phone());
            if (body.message() != null) request.setMessage(body.message());
            if (body.cvPath() != null) request.setCvPath(body.cvPath());
            if (body.cvOriginalName() != null) request.setCvOriginalName(body.cvOriginalName());

            var response = stub.withDeadlineAfter(5, TimeUnit.SECONDS).create(request.build());
            return ResponseEntity.status(HttpStatus.CREATED).body(JobApplicationDto.from(response));
        });
    }

    @GetMapping("/{id}")
    public ResponseEntity<?> get(@PathVariable long id) {
        return handle(() -> {
            var response = stub.withDeadlineAfter(5, TimeUnit.SECONDS).get(GetJobApplicationRequest.newBuilder().setId(id).build());
            return ResponseEntity.ok(JobApplicationDto.from(response));
        });
    }

    @GetMapping
    public ResponseEntity<?> list(@RequestParam long organizationId,
                                   @RequestParam(required = false) Long jobPositionId,
                                   @RequestParam(required = false) String status,
                                   @RequestParam(required = false) Boolean activeFutureConsent) {
        return handle(() -> {
            var requestBuilder = ListJobApplicationsRequest.newBuilder().setOrganizationId(organizationId);
            if (jobPositionId != null) requestBuilder.setJobPositionId(jobPositionId);
            if (status != null) requestBuilder.setStatus(status);
            var request = requestBuilder.build();

            var response = Boolean.TRUE.equals(activeFutureConsent)
                    ? stub.withDeadlineAfter(5, TimeUnit.SECONDS).listActiveFutureConsent(request)
                    : stub.withDeadlineAfter(5, TimeUnit.SECONDS).listByOrganization(request);
            List<JobApplicationDto> applications = response.getApplicationsList().stream().map(JobApplicationDto::from).collect(Collectors.toList());
            return ResponseEntity.ok(applications);
        });
    }

    @PutMapping("/{id}/status")
    public ResponseEntity<?> updateStatus(@PathVariable long id, @RequestParam long organizationId, @RequestBody Map<String, String> body) {
        return handle(() -> {
            var response = stub.withDeadlineAfter(5, TimeUnit.SECONDS).updateStatus(
                    UpdateJobApplicationStatusRequest.newBuilder().setId(id).setOrganizationId(organizationId).setStatus(body.get("status")).build());
            return ResponseEntity.ok(JobApplicationDto.from(response));
        });
    }

    @PostMapping("/{id}/mark-read")
    public ResponseEntity<?> markRead(@PathVariable long id, @RequestParam long organizationId) {
        return handle(() -> {
            var response = stub.withDeadlineAfter(5, TimeUnit.SECONDS).markRead(
                    MarkJobApplicationReadRequest.newBuilder().setId(id).setOrganizationId(organizationId).build());
            return ResponseEntity.ok(JobApplicationDto.from(response));
        });
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<?> delete(@PathVariable long id, @RequestParam long organizationId) {
        return handle(() -> {
            var response = stub.withDeadlineAfter(5, TimeUnit.SECONDS).delete(
                    DeleteJobApplicationRequest.newBuilder().setId(id).setOrganizationId(organizationId).build());
            return ResponseEntity.ok(Map.of(
                    "deleted", response.getDeleted(),
                    "deletedCvPath", response.hasDeletedCvPath() ? response.getDeletedCvPath() : ""));
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
