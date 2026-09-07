package com.supportme.pay.orgsvc.beneficiary;

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
import pay.beneficiary.v1.ReorderBeneficiaryNodesRequest;
import pay.beneficiary.v1.ReorderBeneficiaryNodesResponse;
import pay.beneficiary.v1.UpdateBeneficiaryNodeRequest;

import java.util.HashMap;
import java.util.List;
import java.util.Map;

@GrpcService
public class BeneficiaryNodeGrpcService extends BeneficiaryNodeServiceGrpc.BeneficiaryNodeServiceImplBase {

    private final BeneficiaryNodeRepository repository;

    public BeneficiaryNodeGrpcService(BeneficiaryNodeRepository repository) {
        this.repository = repository;
    }

    @Override
    public void create(CreateBeneficiaryNodeRequest request, StreamObserver<BeneficiaryNodeResponse> responseObserver) {
        BeneficiaryNode node = new BeneficiaryNode(request.getOrganizationId(), request.getHeading());
        applyFields(node, request.hasImage() ? request.getImage() : null, request.getImageSide(),
                request.getImageScale(), request.getImageX(), request.getImageY(), request.getTextAlign(),
                request.hasBodyHtml() ? request.getBodyHtml() : null);

        // Nowy węzeł na końcu listy — mirror BeneficiaryNodeController::store() w Laravelu.
        List<BeneficiaryNode> existing = repository.findByOrganizationIdOrderByPositionAscIdAsc(request.getOrganizationId());
        int maxPosition = existing.stream().mapToInt(BeneficiaryNode::getPosition).max().orElse(-1);
        node.setPosition(maxPosition + 1);

        respond(responseObserver, repository.save(node));
    }

    @Override
    public void update(UpdateBeneficiaryNodeRequest request, StreamObserver<BeneficiaryNodeResponse> responseObserver) {
        BeneficiaryNode existing = ownedEntityOrError(request.getId(), request.getOrganizationId(), responseObserver);
        if (existing == null) {
            return;
        }

        existing.setHeading(request.getHeading());
        applyFields(existing, request.hasImage() ? request.getImage() : null, request.getImageSide(),
                request.getImageScale(), request.getImageX(), request.getImageY(), request.getTextAlign(),
                request.hasBodyHtml() ? request.getBodyHtml() : null);

        respond(responseObserver, repository.save(existing));
    }

    @Override
    public void delete(DeleteBeneficiaryNodeRequest request, StreamObserver<DeleteBeneficiaryNodeResponse> responseObserver) {
        BeneficiaryNode existing = ownedEntityOrError(request.getId(), request.getOrganizationId(), responseObserver);
        if (existing == null) {
            return;
        }

        String image = existing.getImage();
        repository.delete(existing);

        DeleteBeneficiaryNodeResponse.Builder response = DeleteBeneficiaryNodeResponse.newBuilder().setDeleted(true);
        if (image != null) {
            response.setDeletedImage(image);
        }
        responseObserver.onNext(response.build());
        responseObserver.onCompleted();
    }

    @Override
    public void reorder(ReorderBeneficiaryNodesRequest request, StreamObserver<ReorderBeneficiaryNodesResponse> responseObserver) {
        List<BeneficiaryNode> nodes = repository.findByOrganizationIdOrderByPositionAscIdAsc(request.getOrganizationId());
        Map<Long, BeneficiaryNode> byId = new HashMap<>();
        nodes.forEach(n -> byId.put(n.getId(), n));

        int position = 0;
        for (Long id : request.getOrderedIdsList()) {
            BeneficiaryNode node = byId.get(id);
            // id spoza tej organizacji jest ignorowane — mirror bezpiecznego
            // no-opu w Laravelu (forOrganization()->whereKey($id)->update(...)).
            if (node != null) {
                node.setPosition(position++);
                repository.save(node);
            }
        }

        responseObserver.onNext(ReorderBeneficiaryNodesResponse.newBuilder().setReordered(true).build());
        responseObserver.onCompleted();
    }

    @Override
    public void listByOrganization(ListBeneficiaryNodesRequest request, StreamObserver<ListBeneficiaryNodesResponse> responseObserver) {
        respondList(responseObserver, repository.findByOrganizationIdOrderByPositionAscIdAsc(request.getOrganizationId()));
    }

    @Override
    public void listActiveByOrganization(ListBeneficiaryNodesRequest request, StreamObserver<ListBeneficiaryNodesResponse> responseObserver) {
        respondList(responseObserver, repository.findByOrganizationIdAndActiveTrueOrderByPositionAscIdAsc(request.getOrganizationId()));
    }

    private void applyFields(BeneficiaryNode node, String image, String imageSide, int imageScale,
                              int imageX, int imageY, String textAlign, String bodyHtml) {
        node.setImage(image);
        node.setImageSide(imageSide);
        node.setImageScale(imageScale);
        node.setImageX(imageX);
        node.setImageY(imageY);
        node.setTextAlign(textAlign);
        node.setBodyHtml(bodyHtml);
    }

    private BeneficiaryNode ownedEntityOrError(long id, long organizationId, StreamObserver<?> responseObserver) {
        BeneficiaryNode existing = repository.findById(id).orElse(null);
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

    private void respond(StreamObserver<BeneficiaryNodeResponse> responseObserver, BeneficiaryNode node) {
        responseObserver.onNext(toResponse(node));
        responseObserver.onCompleted();
    }

    private void respondList(StreamObserver<ListBeneficiaryNodesResponse> responseObserver, List<BeneficiaryNode> nodes) {
        ListBeneficiaryNodesResponse.Builder response = ListBeneficiaryNodesResponse.newBuilder();
        nodes.forEach(n -> response.addNodes(toResponse(n)));
        responseObserver.onNext(response.build());
        responseObserver.onCompleted();
    }

    private BeneficiaryNodeResponse toResponse(BeneficiaryNode node) {
        BeneficiaryNodeResponse.Builder builder = BeneficiaryNodeResponse.newBuilder()
                .setId(node.getId())
                .setOrganizationId(node.getOrganizationId())
                .setHeading(node.getHeading())
                .setImageSide(node.getImageSide())
                .setImageScale(node.getImageScale())
                .setImageX(node.getImageX())
                .setImageY(node.getImageY())
                .setTextAlign(node.getTextAlign())
                .setPosition(node.getPosition())
                .setActive(node.isActive());

        if (node.getImage() != null) {
            builder.setImage(node.getImage());
        }
        if (node.getBodyHtml() != null) {
            builder.setBodyHtml(node.getBodyHtml());
        }

        return builder.build();
    }
}
