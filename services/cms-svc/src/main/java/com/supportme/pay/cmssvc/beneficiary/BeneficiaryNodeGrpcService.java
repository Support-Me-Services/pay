package com.supportme.pay.cmssvc.beneficiary;

import io.grpc.Status;
import io.grpc.stub.StreamObserver;
import net.devh.boot.grpc.server.service.GrpcService;
import pay.beneficiary.v1.BeneficiaryNodeResponse;
import pay.beneficiary.v1.BeneficiaryNodeServiceGrpc;
import pay.beneficiary.v1.CreateBeneficiaryNodeRequest;
import pay.beneficiary.v1.DeleteBeneficiaryNodeRequest;
import pay.beneficiary.v1.DeleteBeneficiaryNodeResponse;
import pay.beneficiary.v1.ListBeneficiaryNodesRequest;
import pay.beneficiary.v1.ListBeneficiaryNodesResponse;
import pay.beneficiary.v1.UpdateBeneficiaryNodeRequest;

import java.util.List;
import java.util.Objects;

@GrpcService
public class BeneficiaryNodeGrpcService extends BeneficiaryNodeServiceGrpc.BeneficiaryNodeServiceImplBase {

    private final BeneficiaryNodeRepository repository;

    public BeneficiaryNodeGrpcService(BeneficiaryNodeRepository repository) {
        this.repository = repository;
    }

    @Override
    public void create(CreateBeneficiaryNodeRequest request, StreamObserver<BeneficiaryNodeResponse> responseObserver) {
        BeneficiaryNode entity = new BeneficiaryNode(
                request.hasOrganizationId() ? request.getOrganizationId() : null,
                request.getHeading(),
                request.hasImage() ? request.getImage() : null,
                request.getImageSide(),
                request.getImageScale(),
                request.getImageX(),
                request.getImageY(),
                request.getTextAlign(),
                request.hasBodyHtml() ? request.getBodyHtml() : null,
                request.getPosition()
        );
        respond(responseObserver, repository.save(entity));
    }

    @Override
    public void update(UpdateBeneficiaryNodeRequest request, StreamObserver<BeneficiaryNodeResponse> responseObserver) {
        BeneficiaryNode existing = ownedEntityOrError(request.getId(),
                request.hasOrganizationId() ? request.getOrganizationId() : null, responseObserver);
        if (existing == null) {
            return;
        }

        existing.setHeading(request.getHeading());
        if (request.hasImage()) {
            existing.setImage(request.getImage());
        }
        existing.setImageSide(request.getImageSide());
        existing.setImageScale(request.getImageScale());
        existing.setImageX(request.getImageX());
        existing.setImageY(request.getImageY());
        existing.setTextAlign(request.getTextAlign());
        if (request.hasBodyHtml()) {
            existing.setBodyHtml(request.getBodyHtml());
        }
        existing.setPosition(request.getPosition());
        existing.setActive(request.getActive());

        respond(responseObserver, repository.save(existing));
    }

    @Override
    public void delete(DeleteBeneficiaryNodeRequest request, StreamObserver<DeleteBeneficiaryNodeResponse> responseObserver) {
        BeneficiaryNode existing = ownedEntityOrError(request.getId(),
                request.hasOrganizationId() ? request.getOrganizationId() : null, responseObserver);
        if (existing == null) {
            return;
        }

        repository.delete(existing);
        responseObserver.onNext(DeleteBeneficiaryNodeResponse.newBuilder().setDeleted(true).build());
        responseObserver.onCompleted();
    }

    @Override
    public void listByOrganization(ListBeneficiaryNodesRequest request, StreamObserver<ListBeneficiaryNodesResponse> responseObserver) {
        Long organizationId = request.hasOrganizationId() ? request.getOrganizationId() : null;
        List<BeneficiaryNode> nodes = request.getActiveOnly()
                ? repository.findByOrganizationIdAndActiveTrueOrderByPosition(organizationId)
                : repository.findByOrganizationIdOrderByPosition(organizationId);

        ListBeneficiaryNodesResponse.Builder response = ListBeneficiaryNodesResponse.newBuilder();
        nodes.forEach(node -> response.addNodes(toResponse(node)));
        responseObserver.onNext(response.build());
        responseObserver.onCompleted();
    }

    private <T> BeneficiaryNode ownedEntityOrError(long id, Long organizationId, StreamObserver<T> responseObserver) {
        BeneficiaryNode existing = repository.findById(id).orElse(null);
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

    private void respond(StreamObserver<BeneficiaryNodeResponse> responseObserver, BeneficiaryNode entity) {
        responseObserver.onNext(toResponse(entity));
        responseObserver.onCompleted();
    }

    private BeneficiaryNodeResponse toResponse(BeneficiaryNode entity) {
        BeneficiaryNodeResponse.Builder builder = BeneficiaryNodeResponse.newBuilder()
                .setId(entity.getId())
                .setHeading(entity.getHeading())
                .setImageSide(entity.getImageSide())
                .setImageScale(entity.getImageScale())
                .setImageX(entity.getImageX())
                .setImageY(entity.getImageY())
                .setTextAlign(entity.getTextAlign())
                .setPosition(entity.getPosition())
                .setActive(entity.isActive());

        if (entity.getOrganizationId() != null) {
            builder.setOrganizationId(entity.getOrganizationId());
        }
        if (entity.getImage() != null) {
            builder.setImage(entity.getImage());
        }
        if (entity.getBodyHtml() != null) {
            builder.setBodyHtml(entity.getBodyHtml());
        }

        return builder.build();
    }
}
