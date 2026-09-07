package com.supportme.pay.orgsvc.careers;

import io.grpc.Status;
import io.grpc.stub.StreamObserver;
import net.devh.boot.grpc.server.service.GrpcService;
import pay.careers.v1.CreateJobApplicationRequest;
import pay.careers.v1.DeleteJobApplicationRequest;
import pay.careers.v1.DeleteJobApplicationResponse;
import pay.careers.v1.GetJobApplicationRequest;
import pay.careers.v1.JobApplicationResponse;
import pay.careers.v1.JobApplicationServiceGrpc;
import pay.careers.v1.ListJobApplicationsRequest;
import pay.careers.v1.ListJobApplicationsResponse;
import pay.careers.v1.MarkJobApplicationReadRequest;
import pay.careers.v1.UpdateJobApplicationStatusRequest;

import java.time.OffsetDateTime;
import java.util.List;
import java.util.Set;
import java.util.stream.Collectors;

@GrpcService
public class JobApplicationGrpcService extends JobApplicationServiceGrpc.JobApplicationServiceImplBase {

    private static final Set<String> STATUSES = Set.of("pending", "accepted", "rejected");

    private final JobApplicationRepository repository;

    public JobApplicationGrpcService(JobApplicationRepository repository) {
        this.repository = repository;
    }

    @Override
    public void create(CreateJobApplicationRequest request, StreamObserver<JobApplicationResponse> responseObserver) {
        JobApplication application = new JobApplication(request.getOrganizationId(), request.getName(), request.getEmail());
        if (request.hasJobPositionId()) {
            application.setJobPositionId(request.getJobPositionId());
        }
        if (request.hasPhone()) application.setPhone(request.getPhone());
        if (request.hasMessage()) application.setMessage(request.getMessage());
        if (request.hasCvPath()) application.setCvPath(request.getCvPath());
        if (request.hasCvOriginalName()) application.setCvOriginalName(request.getCvOriginalName());

        if (request.getFutureRecruitmentConsent()) {
            application.setFutureRecruitmentConsent(true);
            application.setFutureRecruitmentConsentAt(OffsetDateTime.now());
        }

        respond(responseObserver, repository.save(application));
    }

    @Override
    public void updateStatus(UpdateJobApplicationStatusRequest request, StreamObserver<JobApplicationResponse> responseObserver) {
        JobApplication existing = ownedEntityOrError(request.getId(), request.getOrganizationId(), responseObserver);
        if (existing == null) {
            return;
        }
        if (!STATUSES.contains(request.getStatus())) {
            responseObserver.onError(Status.INVALID_ARGUMENT.withDescription("unknown status: " + request.getStatus()).asRuntimeException());
            return;
        }
        existing.setStatus(request.getStatus());
        respond(responseObserver, repository.save(existing));
    }

    @Override
    public void markRead(MarkJobApplicationReadRequest request, StreamObserver<JobApplicationResponse> responseObserver) {
        JobApplication existing = ownedEntityOrError(request.getId(), request.getOrganizationId(), responseObserver);
        if (existing == null) {
            return;
        }
        existing.setRead(true);
        respond(responseObserver, repository.save(existing));
    }

    @Override
    public void delete(DeleteJobApplicationRequest request, StreamObserver<DeleteJobApplicationResponse> responseObserver) {
        JobApplication existing = ownedEntityOrError(request.getId(), request.getOrganizationId(), responseObserver);
        if (existing == null) {
            return;
        }
        String cvPath = existing.getCvPath();
        repository.delete(existing);

        DeleteJobApplicationResponse.Builder response = DeleteJobApplicationResponse.newBuilder().setDeleted(true);
        if (cvPath != null) {
            response.setDeletedCvPath(cvPath);
        }
        responseObserver.onNext(response.build());
        responseObserver.onCompleted();
    }

    @Override
    public void get(GetJobApplicationRequest request, StreamObserver<JobApplicationResponse> responseObserver) {
        JobApplication existing = repository.findById(request.getId()).orElse(null);
        if (existing == null) {
            responseObserver.onError(Status.NOT_FOUND.asRuntimeException());
            return;
        }
        respond(responseObserver, existing);
    }

    @Override
    public void listByOrganization(ListJobApplicationsRequest request, StreamObserver<ListJobApplicationsResponse> responseObserver) {
        List<JobApplication> results;
        boolean hasPosition = request.hasJobPositionId();
        boolean hasStatus = request.hasStatus();

        if (hasPosition && hasStatus) {
            results = repository.findByOrganizationIdAndJobPositionIdAndStatusOrderByIdDesc(
                    request.getOrganizationId(), request.getJobPositionId(), request.getStatus());
        } else if (hasPosition) {
            results = repository.findByOrganizationIdAndJobPositionIdOrderByIdDesc(request.getOrganizationId(), request.getJobPositionId());
        } else if (hasStatus) {
            results = repository.findByOrganizationIdAndStatusOrderByIdDesc(request.getOrganizationId(), request.getStatus());
        } else {
            results = repository.findByOrganizationIdOrderByIdDesc(request.getOrganizationId());
        }

        respondList(responseObserver, results);
    }

    @Override
    public void listActiveFutureConsent(ListJobApplicationsRequest request, StreamObserver<ListJobApplicationsResponse> responseObserver) {
        List<JobApplication> results = repository.findByOrganizationIdOrderByIdDesc(request.getOrganizationId()).stream()
                .filter(JobApplication::isFutureConsentActive)
                .sorted((a, b) -> b.getFutureRecruitmentConsentAt().compareTo(a.getFutureRecruitmentConsentAt()))
                .collect(Collectors.toList());

        respondList(responseObserver, results);
    }

    private JobApplication ownedEntityOrError(long id, long organizationId, StreamObserver<?> responseObserver) {
        JobApplication existing = repository.findById(id).orElse(null);
        if (existing == null) {
            responseObserver.onError(Status.NOT_FOUND.asRuntimeException());
            return null;
        }
        if (!existing.getOrganizationId().equals(organizationId)) {
            responseObserver.onError(Status.PERMISSION_DENIED.asRuntimeException());
            return null;
        }
        return existing;
    }

    private void respond(StreamObserver<JobApplicationResponse> responseObserver, JobApplication application) {
        responseObserver.onNext(toResponse(application));
        responseObserver.onCompleted();
    }

    private void respondList(StreamObserver<ListJobApplicationsResponse> responseObserver, List<JobApplication> applications) {
        ListJobApplicationsResponse.Builder response = ListJobApplicationsResponse.newBuilder();
        applications.forEach(a -> response.addApplications(toResponse(a)));
        responseObserver.onNext(response.build());
        responseObserver.onCompleted();
    }

    private JobApplicationResponse toResponse(JobApplication a) {
        JobApplicationResponse.Builder builder = JobApplicationResponse.newBuilder()
                .setId(a.getId())
                .setOrganizationId(a.getOrganizationId())
                .setName(a.getName())
                .setEmail(a.getEmail())
                .setIsRead(a.isRead())
                .setStatus(a.getStatus())
                .setFutureRecruitmentConsent(a.isFutureRecruitmentConsent())
                .setFutureConsentActive(a.isFutureConsentActive())
                .setCreatedAt(a.getCreatedAt() != null ? a.getCreatedAt().toString() : "");

        if (a.getJobPositionId() != null) builder.setJobPositionId(a.getJobPositionId());
        if (a.getPhone() != null) builder.setPhone(a.getPhone());
        if (a.getMessage() != null) builder.setMessage(a.getMessage());
        if (a.getCvPath() != null) builder.setCvPath(a.getCvPath());
        if (a.getCvOriginalName() != null) builder.setCvOriginalName(a.getCvOriginalName());
        if (a.getFutureRecruitmentConsentAt() != null) builder.setFutureRecruitmentConsentAt(a.getFutureRecruitmentConsentAt().toString());

        return builder.build();
    }
}
