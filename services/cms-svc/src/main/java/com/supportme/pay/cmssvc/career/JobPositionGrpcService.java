package com.supportme.pay.cmssvc.career;

import io.grpc.Status;
import io.grpc.stub.StreamObserver;
import net.devh.boot.grpc.server.service.GrpcService;
import pay.career.v1.CreateJobPositionRequest;
import pay.career.v1.DeleteJobPositionRequest;
import pay.career.v1.DeleteJobPositionResponse;
import pay.career.v1.GetJobPositionRequest;
import pay.career.v1.JobPositionResponse;
import pay.career.v1.JobPositionServiceGrpc;
import pay.career.v1.ListJobPositionsRequest;
import pay.career.v1.ListJobPositionsResponse;
import pay.career.v1.UpdateJobPositionRequest;

import java.util.List;
import java.util.Objects;

@GrpcService
public class JobPositionGrpcService extends JobPositionServiceGrpc.JobPositionServiceImplBase {

    private final JobPositionRepository repository;

    public JobPositionGrpcService(JobPositionRepository repository) {
        this.repository = repository;
    }

    @Override
    public void create(CreateJobPositionRequest request, StreamObserver<JobPositionResponse> responseObserver) {
        JobPosition entity = new JobPosition(
                request.hasOrganizationId() ? request.getOrganizationId() : null,
                request.getTitle(),
                request.hasLocation() ? request.getLocation() : null,
                request.hasEmploymentType() ? request.getEmploymentType() : null,
                request.hasDescriptionHtml() ? request.getDescriptionHtml() : null,
                request.hasShortDescription() ? request.getShortDescription() : null,
                request.getSort()
        );
        respond(responseObserver, repository.save(entity));
    }

    @Override
    public void update(UpdateJobPositionRequest request, StreamObserver<JobPositionResponse> responseObserver) {
        JobPosition existing = ownedEntityOrError(request.getId(),
                request.hasOrganizationId() ? request.getOrganizationId() : null, responseObserver);
        if (existing == null) {
            return;
        }

        existing.setTitle(request.getTitle());
        if (request.hasLocation()) {
            existing.setLocation(request.getLocation());
        }
        if (request.hasEmploymentType()) {
            existing.setEmploymentType(request.getEmploymentType());
        }
        if (request.hasDescriptionHtml()) {
            existing.setDescriptionHtml(request.getDescriptionHtml());
        }
        if (request.hasShortDescription()) {
            existing.setShortDescription(request.getShortDescription());
        }
        existing.setActive(request.getActive());
        existing.setSort(request.getSort());

        respond(responseObserver, repository.save(existing));
    }

    @Override
    public void delete(DeleteJobPositionRequest request, StreamObserver<DeleteJobPositionResponse> responseObserver) {
        JobPosition existing = ownedEntityOrError(request.getId(),
                request.hasOrganizationId() ? request.getOrganizationId() : null, responseObserver);
        if (existing == null) {
            return;
        }

        repository.delete(existing);
        responseObserver.onNext(DeleteJobPositionResponse.newBuilder().setDeleted(true).build());
        responseObserver.onCompleted();
    }

    @Override
    public void get(GetJobPositionRequest request, StreamObserver<JobPositionResponse> responseObserver) {
        repository.findById(request.getId())
                .ifPresentOrElse(
                        pos -> respond(responseObserver, pos),
                        () -> responseObserver.onError(Status.NOT_FOUND.asRuntimeException()));
    }

    @Override
    public void listByOrganization(ListJobPositionsRequest request, StreamObserver<ListJobPositionsResponse> responseObserver) {
        Long organizationId = request.hasOrganizationId() ? request.getOrganizationId() : null;
        List<JobPosition> positions = request.getActiveOnly()
                ? repository.findByOrganizationIdAndActiveTrue(organizationId)
                : repository.findByOrganizationId(organizationId);

        ListJobPositionsResponse.Builder response = ListJobPositionsResponse.newBuilder();
        positions.forEach(pos -> response.addPositions(toResponse(pos)));
        responseObserver.onNext(response.build());
        responseObserver.onCompleted();
    }

    private <T> JobPosition ownedEntityOrError(long id, Long organizationId, StreamObserver<T> responseObserver) {
        JobPosition existing = repository.findById(id).orElse(null);
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

    private void respond(StreamObserver<JobPositionResponse> responseObserver, JobPosition entity) {
        responseObserver.onNext(toResponse(entity));
        responseObserver.onCompleted();
    }

    private JobPositionResponse toResponse(JobPosition entity) {
        JobPositionResponse.Builder builder = JobPositionResponse.newBuilder()
                .setId(entity.getId())
                .setTitle(entity.getTitle())
                .setActive(entity.isActive())
                .setSort(entity.getSort());

        if (entity.getOrganizationId() != null) {
            builder.setOrganizationId(entity.getOrganizationId());
        }
        if (entity.getLocation() != null) {
            builder.setLocation(entity.getLocation());
        }
        if (entity.getEmploymentType() != null) {
            builder.setEmploymentType(entity.getEmploymentType());
        }
        if (entity.getDescriptionHtml() != null) {
            builder.setDescriptionHtml(entity.getDescriptionHtml());
        }
        if (entity.getShortDescription() != null) {
            builder.setShortDescription(entity.getShortDescription());
        }

        return builder.build();
    }
}
