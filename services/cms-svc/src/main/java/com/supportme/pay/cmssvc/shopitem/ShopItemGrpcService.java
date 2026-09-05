package com.supportme.pay.cmssvc.shopitem;

import io.grpc.Status;
import io.grpc.stub.StreamObserver;
import net.devh.boot.grpc.server.service.GrpcService;
import pay.shopitem.v1.CreateShopItemRequest;
import pay.shopitem.v1.DeleteShopItemRequest;
import pay.shopitem.v1.DeleteShopItemResponse;
import pay.shopitem.v1.GetShopItemBySlugRequest;
import pay.shopitem.v1.GetShopItemRequest;
import pay.shopitem.v1.ListShopItemsRequest;
import pay.shopitem.v1.ListShopItemsResponse;
import pay.shopitem.v1.ShopItemResponse;
import pay.shopitem.v1.ShopItemServiceGrpc;
import pay.shopitem.v1.UpdateShopItemRequest;

import java.util.List;
import java.util.Objects;

@GrpcService
public class ShopItemGrpcService extends ShopItemServiceGrpc.ShopItemServiceImplBase {

    private final ShopItemRepository repository;

    public ShopItemGrpcService(ShopItemRepository repository) {
        this.repository = repository;
    }

    @Override
    public void create(CreateShopItemRequest request, StreamObserver<ShopItemResponse> responseObserver) {
        ShopItem entity = new ShopItem(
                request.hasOrganizationId() ? request.getOrganizationId() : null,
                request.getSlug(),
                request.getName(),
                request.hasImage() ? request.getImage() : null,
                request.getMinAmount(),
                request.hasPrice() ? request.getPrice() : null,
                request.hasDescription() ? request.getDescription() : null,
                request.getIsDefault(),
                request.getSort()
        );
        respond(responseObserver, repository.save(entity));
    }

    @Override
    public void update(UpdateShopItemRequest request, StreamObserver<ShopItemResponse> responseObserver) {
        ShopItem existing = ownedEntityOrError(request.getId(),
                request.hasOrganizationId() ? request.getOrganizationId() : null, responseObserver);
        if (existing == null) {
            return;
        }

        existing.setSlug(request.getSlug());
        existing.setName(request.getName());
        if (request.hasImage()) {
            existing.setImage(request.getImage());
        }
        existing.setMinAmount(request.getMinAmount());
        if (request.hasPrice()) {
            existing.setPrice(request.getPrice());
        }
        if (request.hasDescription()) {
            existing.setDescription(request.getDescription());
        }
        existing.setDefault(request.getIsDefault());
        existing.setActive(request.getActive());
        existing.setSort(request.getSort());
        if (request.hasThankYouHeading()) {
            existing.setThankYouHeading(request.getThankYouHeading());
        }
        if (request.hasThankYouBody()) {
            existing.setThankYouBody(request.getThankYouBody());
        }
        if (request.hasThankYouImage()) {
            existing.setThankYouImage(request.getThankYouImage());
        }
        if (request.hasMecenasOrganizationId()) {
            existing.setMecenasOrganizationId(request.getMecenasOrganizationId());
        }

        respond(responseObserver, repository.save(existing));
    }

    @Override
    public void delete(DeleteShopItemRequest request, StreamObserver<DeleteShopItemResponse> responseObserver) {
        ShopItem existing = ownedEntityOrError(request.getId(),
                request.hasOrganizationId() ? request.getOrganizationId() : null, responseObserver);
        if (existing == null) {
            return;
        }

        repository.delete(existing);
        responseObserver.onNext(DeleteShopItemResponse.newBuilder().setDeleted(true).build());
        responseObserver.onCompleted();
    }

    @Override
    public void get(GetShopItemRequest request, StreamObserver<ShopItemResponse> responseObserver) {
        repository.findById(request.getId())
                .ifPresentOrElse(
                        item -> respond(responseObserver, item),
                        () -> responseObserver.onError(Status.NOT_FOUND.asRuntimeException()));
    }

    @Override
    public void getBySlug(GetShopItemBySlugRequest request, StreamObserver<ShopItemResponse> responseObserver) {
        repository.findByOrganizationIdAndSlug(request.getOrganizationId(), request.getSlug())
                .ifPresentOrElse(
                        item -> respond(responseObserver, item),
                        () -> responseObserver.onError(Status.NOT_FOUND.asRuntimeException()));
    }

    @Override
    public void listByOrganization(ListShopItemsRequest request, StreamObserver<ListShopItemsResponse> responseObserver) {
        Long organizationId = request.hasOrganizationId() ? request.getOrganizationId() : null;
        List<ShopItem> items = request.getActiveOnly()
                ? repository.findByOrganizationIdAndActiveTrue(organizationId)
                : repository.findByOrganizationId(organizationId);

        ListShopItemsResponse.Builder response = ListShopItemsResponse.newBuilder();
        items.forEach(item -> response.addItems(toResponse(item)));
        responseObserver.onNext(response.build());
        responseObserver.onCompleted();
    }

    private <T> ShopItem ownedEntityOrError(long id, Long organizationId, StreamObserver<T> responseObserver) {
        ShopItem existing = repository.findById(id).orElse(null);
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

    private void respond(StreamObserver<ShopItemResponse> responseObserver, ShopItem entity) {
        responseObserver.onNext(toResponse(entity));
        responseObserver.onCompleted();
    }

    private ShopItemResponse toResponse(ShopItem entity) {
        ShopItemResponse.Builder builder = ShopItemResponse.newBuilder()
                .setId(entity.getId())
                .setSlug(entity.getSlug())
                .setName(entity.getName())
                .setMinAmount(entity.getMinAmount())
                .setIsDefault(entity.isDefault())
                .setActive(entity.isActive())
                .setSort(entity.getSort());

        if (entity.getOrganizationId() != null) {
            builder.setOrganizationId(entity.getOrganizationId());
        }
        if (entity.getImage() != null) {
            builder.setImage(entity.getImage());
        }
        if (entity.getPrice() != null) {
            builder.setPrice(entity.getPrice());
        }
        if (entity.getDescription() != null) {
            builder.setDescription(entity.getDescription());
        }
        if (entity.getThankYouHeading() != null) {
            builder.setThankYouHeading(entity.getThankYouHeading());
        }
        if (entity.getThankYouBody() != null) {
            builder.setThankYouBody(entity.getThankYouBody());
        }
        if (entity.getThankYouImage() != null) {
            builder.setThankYouImage(entity.getThankYouImage());
        }
        if (entity.getMecenasOrganizationId() != null) {
            builder.setMecenasOrganizationId(entity.getMecenasOrganizationId());
        }

        return builder.build();
    }
}
