package com.supportme.pay.cmssvc.career;

import io.grpc.Status;
import io.grpc.stub.StreamObserver;
import net.devh.boot.grpc.server.service.GrpcService;
import pay.career.v1.CreateJobApplicationRequest;
import pay.career.v1.DeleteJobApplicationRequest;
import pay.career.v1.DeleteJobApplicationResponse;
import pay.career.v1.JobApplicationResponse;
import pay.career.v1.JobApplicationServiceGrpc;
import pay.career.v1.ListJobApplicationsRequest;
import pay.career.v1.ListJobApplicationsResponse;
import pay.career.v1.UpdateJobApplicationStatusRequest;

import java.util.List;
import java.util.Objects;

@GrpcService
public class JobApplicationGrpcService extends JobApplicationServiceGrpc.JobApplicationServiceImplBase {

    private final JobApplicationRepository repository;

    public JobApplicationGrpcService(JobApplicationRepository repository) {
        this.repository = repository;
    }

    /** Publiczne — formularz aplikacji na stronie kariery, bez ownership check (nic jeszcze nie istnieje do sprawdzenia). */
    @Override
    public void create(CreateJobApplicationRequest request, StreamObserver<JobApplicationResponse> responseObserver) {
        JobApplication entity = new JobApplication(
                request.hasJobPositionId() ? request.getJobPositionId() : null,
                request.hasOrganizationId() ? request.getOrganizationId() : null,
                request.getName(),
                request.getEmail(),
                request.hasPhone() ? request.getPhone() : null,
                request.hasMessage() ? request.getMessage() : null,
                request.hasCvPath() ? request.getCvPath() : null,
                request.hasCvOriginalName() ? request.getCvOriginalName() : null,
                request.getFutureRecruitmentConsent()
        );
        respond(responseObserver, repository.save(entity));
    }

    @Override
    public void updateStatus(UpdateJobApplicationStatusRequest request, StreamObserver<JobApplicationResponse> responseObserver) {
        JobApplication existing = ownedEntityOrError(request.getId(),
                request.hasOrganizationId() ? request.getOrganizationId() : null, responseObserver);
        if (existing == null) {
            return;
        }

        existing.setStatus(request.getStatus());
        existing.setRead(request.getIsRead());

        respond(responseObserver, repository.save(existing));
    }

    @Override
    public void delete(DeleteJobApplicationRequest request, StreamObserver<DeleteJobApplicationResponse> responseObserver) {
        JobApplication existing = ownedEntityOrError(request.getId(),
                request.hasOrganizationId() ? request.getOrganizationId() : null, responseObserver);
        if (existing == null) {
            return;
        }

        repository.delete(existing);
        responseObserver.onNext(DeleteJobApplicationResponse.newBuilder().setDeleted(true).build());
        responseObserver.onCompleted();
    }

    @Override
    public void listByOrganization(ListJobApplicationsRequest request, StreamObserver<ListJobApplicationsResponse> responseObserver) {
        Long organizationId = request.hasOrganizationId() ? request.getOrganizationId() : null;
        List<JobApplication> applications = repository.findByOrganizationId(organizationId);

        ListJobApplicationsResponse.Builder response = ListJobApplicationsResponse.newBuilder();
        applications.forEach(app -> response.addApplications(toResponse(app)));
        responseObserver.onNext(response.build());
        responseObserver.onCompleted();
    }

    private <T> JobApplication ownedEntityOrError(long id, Long organizationId, StreamObserver<T> responseObserver) {
        JobApplication existing = repository.findById(id).orElse(null);
        if (existing == null) {
            responseObserver.onError(Status.NOT_FOUND.asRuntimeException());
            return null;
        }
        if (!Objects.equals(existing.getOrganizationId(), organizationId)) {
            responseObserver.onError(Status.PERMISSION_DENIED.asRuntimeException());
            return null;
        }
        return existing;
    }

    private void respond(StreamObserver<JobApplicationResponse> responseObserver, JobApplication entity) {
        responseObserver.onNext(toResponse(entity));
        responseObserver.onCompleted();
    }

    private JobApplicationResponse toResponse(JobApplication entity) {
        JobApplicationResponse.Builder builder = JobApplicationResponse.newBuilder()
                .setId(entity.getId())
                .setName(entity.getName())
                .setEmail(entity.getEmail())
                .setIsRead(entity.isRead())
                .setStatus(entity.getStatus())
                .setFutureRecruitmentConsent(entity.isFutureRecruitmentConsent());

        if (entity.getJobPositionId() != null) {
            builder.setJobPositionId(entity.getJobPositionId());
        }
        if (entity.getOrganizationId() != null) {
            builder.setOrganizationId(entity.getOrganizationId());
        }
        if (entity.getPhone() != null) {
            builder.setPhone(entity.getPhone());
        }
        if (entity.getMessage() != null) {
            builder.setMessage(entity.getMessage());
        }
        if (entity.getCvPath() != null) {
            builder.setCvPath(entity.getCvPath());
        }
        if (entity.getCvOriginalName() != null) {
            builder.setCvOriginalName(entity.getCvOriginalName());
        }

        return builder.build();
    }
}
