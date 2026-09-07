package com.supportme.pay.orgsvc.careers;

import io.grpc.Status;
import io.grpc.stub.StreamObserver;
import net.devh.boot.grpc.server.service.GrpcService;
import pay.careers.v1.CreateJobPositionRequest;
import pay.careers.v1.DeleteJobPositionRequest;
import pay.careers.v1.DeleteJobPositionResponse;
import pay.careers.v1.GetJobPositionRequest;
import pay.careers.v1.JobPositionResponse;
import pay.careers.v1.JobPositionServiceGrpc;
import pay.careers.v1.ListJobPositionsRequest;
import pay.careers.v1.ListJobPositionsResponse;
import pay.careers.v1.ToggleJobPositionRequest;
import pay.careers.v1.UpdateJobPositionRequest;

import java.util.List;

@GrpcService
public class JobPositionGrpcService extends JobPositionServiceGrpc.JobPositionServiceImplBase {

    private final JobPositionRepository repository;
    private final JobApplicationRepository applicationRepository;

    public JobPositionGrpcService(JobPositionRepository repository, JobApplicationRepository applicationRepository) {
        this.repository = repository;
        this.applicationRepository = applicationRepository;
    }

    @Override
    public void create(CreateJobPositionRequest request, StreamObserver<JobPositionResponse> responseObserver) {
        JobPosition position = new JobPosition(request.getOrganizationId(), request.getTitle());
        applyFields(position, request.hasLocation() ? request.getLocation() : null,
                request.hasEmploymentType() ? request.getEmploymentType() : null,
                request.hasDescriptionHtml() ? request.getDescriptionHtml() : null,
                request.hasShortDescription() ? request.getShortDescription() : null,
                request.getSort());
        position.setActive(request.getActive());

        respond(responseObserver, repository.save(position));
    }

    @Override
    public void update(UpdateJobPositionRequest request, StreamObserver<JobPositionResponse> responseObserver) {
        JobPosition existing = ownedEntityOrError(request.getId(), request.getOrganizationId(), responseObserver);
        if (existing == null) {
            return;
        }

        existing.setTitle(request.getTitle());
        applyFields(existing, request.hasLocation() ? request.getLocation() : null,
                request.hasEmploymentType() ? request.getEmploymentType() : null,
                request.hasDescriptionHtml() ? request.getDescriptionHtml() : null,
                request.hasShortDescription() ? request.getShortDescription() : null,
                request.getSort());
        existing.setActive(request.getActive());

        respond(responseObserver, repository.save(existing));
    }

    @Override
    public void toggle(ToggleJobPositionRequest request, StreamObserver<JobPositionResponse> responseObserver) {
        JobPosition existing = ownedEntityOrError(request.getId(), request.getOrganizationId(), responseObserver);
        if (existing == null) {
            return;
        }
        existing.setActive(!existing.isActive());
        respond(responseObserver, repository.save(existing));
    }

    @Override
    public void delete(DeleteJobPositionRequest request, StreamObserver<DeleteJobPositionResponse> responseObserver) {
        JobPosition existing = ownedEntityOrError(request.getId(), request.getOrganizationId(), responseObserver);
        if (existing == null) {
            return;
        }
        repository.delete(existing);
        responseObserver.onNext(DeleteJobPositionResponse.newBuilder().setDeleted(true).build());
        responseObserver.onCompleted();
    }

    @Override
    public void get(GetJobPositionRequest request, StreamObserver<JobPositionResponse> responseObserver) {
        JobPosition existing = repository.findById(request.getId()).orElse(null);
        if (existing == null) {
            responseObserver.onError(Status.NOT_FOUND.asRuntimeException());
            return;
        }
        respond(responseObserver, existing);
    }

    @Override
    public void listByOrganization(ListJobPositionsRequest request, StreamObserver<ListJobPositionsResponse> responseObserver) {
        respondList(responseObserver, repository.findByOrganizationIdOrderBySortAscIdAsc(request.getOrganizationId()));
    }

    @Override
    public void listActiveByOrganization(ListJobPositionsRequest request, StreamObserver<ListJobPositionsResponse> responseObserver) {
        respondList(responseObserver, repository.findByOrganizationIdAndActiveTrueOrderBySortAscIdAsc(request.getOrganizationId()));
    }

    private void applyFields(JobPosition position, String location, String employmentType,
                              String descriptionHtml, String shortDescription, int sort) {
        position.setLocation(location);
        position.setEmploymentType(employmentType);
        position.setDescriptionHtml(descriptionHtml);
        position.setShortDescription(shortDescription);
        position.setSort(sort);
    }

    private JobPosition ownedEntityOrError(long id, long organizationId, StreamObserver<?> responseObserver) {
        JobPosition existing = repository.findById(id).orElse(null);
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

    private void respond(StreamObserver<JobPositionResponse> responseObserver, JobPosition position) {
        responseObserver.onNext(toResponse(position));
        responseObserver.onCompleted();
    }

    private void respondList(StreamObserver<ListJobPositionsResponse> responseObserver, List<JobPosition> positions) {
        ListJobPositionsResponse.Builder response = ListJobPositionsResponse.newBuilder();
        positions.forEach(p -> response.addPositions(toResponse(p)));
        responseObserver.onNext(response.build());
        responseObserver.onCompleted();
    }

    private JobPositionResponse toResponse(JobPosition position) {
        JobPositionResponse.Builder builder = JobPositionResponse.newBuilder()
                .setId(position.getId())
                .setOrganizationId(position.getOrganizationId())
                .setTitle(position.getTitle())
                .setActive(position.isActive())
                .setSort(position.getSort())
                .setApplicationsCount(applicationRepository.countByJobPositionId(position.getId()));

        if (position.getLocation() != null) builder.setLocation(position.getLocation());
        if (position.getEmploymentType() != null) builder.setEmploymentType(position.getEmploymentType());
        if (position.getDescriptionHtml() != null) builder.setDescriptionHtml(position.getDescriptionHtml());
        if (position.getShortDescription() != null) builder.setShortDescription(position.getShortDescription());

        return builder.build();
    }
}
