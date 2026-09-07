package com.supportme.pay.orgsvc.shopitem;

import io.grpc.Status;
import io.grpc.stub.StreamObserver;
import net.devh.boot.grpc.server.service.GrpcService;
import pay.shopitem.v1.CreateShopItemRequest;
import pay.shopitem.v1.DeleteShopItemRequest;
import pay.shopitem.v1.DeleteShopItemResponse;
import pay.shopitem.v1.GetShopItemRequest;
import pay.shopitem.v1.ListAllShopItemsRequest;
import pay.shopitem.v1.ListShopItemsRequest;
import pay.shopitem.v1.ListShopItemsResponse;
import pay.shopitem.v1.ShopItemResponse;
import pay.shopitem.v1.ShopItemServiceGrpc;
import pay.shopitem.v1.ToggleShopItemRequest;
import pay.shopitem.v1.UpdateShopItemRequest;

import java.text.Normalizer;
import java.util.List;
import java.util.Locale;

@GrpcService
public class ShopItemGrpcService extends ShopItemServiceGrpc.ShopItemServiceImplBase {

    private final ShopItemRepository repository;

    public ShopItemGrpcService(ShopItemRepository repository) {
        this.repository = repository;
    }

    @Override
    public void create(CreateShopItemRequest request, StreamObserver<ShopItemResponse> responseObserver) {
        String slug = uniqueSlug(request.getOrganizationId(),
                request.hasSlug() ? request.getSlug() : "", request.getName(), null);

        ShopItem item = new ShopItem(request.getOrganizationId(), request.getName(), slug, request.getPriceGrosze());
        if (request.hasImage()) {
            item.setImage(request.getImage());
        }
        if (request.hasDescription()) {
            item.setDescription(request.getDescription());
        }
        item.setSort(request.getSort());
        if (request.hasThankYouHeading()) {
            item.setThankYouHeading(request.getThankYouHeading());
        }
        if (request.hasThankYouBody()) {
            item.setThankYouBody(request.getThankYouBody());
        }
        if (request.hasThankYouImage()) {
            item.setThankYouImage(request.getThankYouImage());
        }
        if (request.hasMecenasOrganizationId()) {
            item.setMecenasOrganizationId(request.getMecenasOrganizationId());
        }
        item.setDefault(request.getIsDefault());

        item = repository.save(item);
        applyDefaultInvariant(item);
        respond(responseObserver, item);
    }

    @Override
    public void update(UpdateShopItemRequest request, StreamObserver<ShopItemResponse> responseObserver) {
        ShopItem existing = ownedEntityOrError(request.getId(), request.getOrganizationId(), responseObserver);
        if (existing == null) {
            return;
        }

        existing.setName(request.getName());
        existing.setSlug(uniqueSlug(request.getOrganizationId(),
                request.hasSlug() ? request.getSlug() : "", request.getName(), existing.getId()));
        existing.setPriceGrosze(request.getPriceGrosze());
        existing.setDescription(request.hasDescription() ? request.getDescription() : null);
        existing.setSort(request.getSort());
        existing.setActive(request.getActive());
        existing.setThankYouHeading(request.hasThankYouHeading() ? request.getThankYouHeading() : null);
        existing.setThankYouBody(request.hasThankYouBody() ? request.getThankYouBody() : null);

        if (request.getClearImage()) {
            existing.setImage(null);
        } else if (request.hasImage()) {
            existing.setImage(request.getImage());
        }
        if (request.getClearThankYouImage()) {
            existing.setThankYouImage(null);
        } else if (request.hasThankYouImage()) {
            existing.setThankYouImage(request.getThankYouImage());
        }
        if (request.getClearMecenasOrganizationId()) {
            existing.setMecenasOrganizationId(null);
        } else if (request.hasMecenasOrganizationId()) {
            existing.setMecenasOrganizationId(request.getMecenasOrganizationId());
        }
        existing.setDefault(request.getIsDefault());

        existing = repository.save(existing);
        applyDefaultInvariant(existing);
        respond(responseObserver, existing);
    }

    @Override
    public void delete(DeleteShopItemRequest request, StreamObserver<DeleteShopItemResponse> responseObserver) {
        ShopItem existing = ownedEntityOrError(request.getId(), request.getOrganizationId(), responseObserver);
        if (existing == null) {
            return;
        }
        repository.delete(existing);
        responseObserver.onNext(DeleteShopItemResponse.newBuilder().setDeleted(true).build());
        responseObserver.onCompleted();
    }

    @Override
    public void toggle(ToggleShopItemRequest request, StreamObserver<ShopItemResponse> responseObserver) {
        ShopItem existing = ownedEntityOrError(request.getId(), request.getOrganizationId(), responseObserver);
        if (existing == null) {
            return;
        }
        existing.setActive(!existing.isActive());
        respond(responseObserver, repository.save(existing));
    }

    @Override
    public void get(GetShopItemRequest request, StreamObserver<ShopItemResponse> responseObserver) {
        ShopItem existing = repository.findById(request.getId()).orElse(null);
        if (existing == null) {
            responseObserver.onError(Status.NOT_FOUND.asRuntimeException());
            return;
        }
        respond(responseObserver, existing);
    }

    @Override
    public void listByOrganization(ListShopItemsRequest request, StreamObserver<ListShopItemsResponse> responseObserver) {
        respondList(responseObserver, repository.findByOrganizationIdOrderBySortAscIdAsc(request.getOrganizationId()));
    }

    @Override
    public void listActiveByOrganization(ListShopItemsRequest request, StreamObserver<ListShopItemsResponse> responseObserver) {
        respondList(responseObserver, repository.findByOrganizationIdAndActiveTrueOrderBySortAscIdAsc(request.getOrganizationId()));
    }

    @Override
    public void listAll(ListAllShopItemsRequest request, StreamObserver<ListShopItemsResponse> responseObserver) {
        respondList(responseObserver, repository.findAllByOrderByNameAsc());
    }

    /** Tylko jeden domyślny produkt na organizację — mirror ShopItemController::applyDefault(). */
    private void applyDefaultInvariant(ShopItem item) {
        if (item.isDefault()) {
            repository.findByOrganizationIdOrderBySortAscIdAsc(item.getOrganizationId()).stream()
                    .filter(other -> !other.getId().equals(item.getId()) && other.isDefault())
                    .forEach(other -> {
                        other.setDefault(false);
                        repository.save(other);
                    });
        }
    }

    private String uniqueSlug(long organizationId, String requestedSlug, String name, Long excludeId) {
        String base = slug(!requestedSlug.isEmpty() ? requestedSlug : name);
        if (base.isEmpty()) {
            base = "produkt";
        }

        String candidate = base;
        int i = 2;
        while (existsSlugForAnotherItem(organizationId, candidate, excludeId)) {
            candidate = base + "-" + (i++);
        }
        return candidate;
    }

    private boolean existsSlugForAnotherItem(long organizationId, String slug, Long excludeId) {
        return repository.findByOrganizationIdOrderBySortAscIdAsc(organizationId).stream()
                .anyMatch(i -> i.getSlug().equals(slug) && (excludeId == null || !i.getId().equals(excludeId)));
    }

    private static String slug(String input) {
        String normalized = Normalizer.normalize(input, Normalizer.Form.NFKD).replaceAll("\\p{M}", "");
        return normalized.toLowerCase(Locale.ROOT).replaceAll("[^a-z0-9]+", "-").replaceAll("^-+|-+$", "");
    }

    private ShopItem ownedEntityOrError(long id, long organizationId, StreamObserver<?> responseObserver) {
        ShopItem existing = repository.findById(id).orElse(null);
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

    private void respond(StreamObserver<ShopItemResponse> responseObserver, ShopItem item) {
        responseObserver.onNext(toResponse(item));
        responseObserver.onCompleted();
    }

    private void respondList(StreamObserver<ListShopItemsResponse> responseObserver, List<ShopItem> items) {
        ListShopItemsResponse.Builder response = ListShopItemsResponse.newBuilder();
        items.forEach(i -> response.addItems(toResponse(i)));
        responseObserver.onNext(response.build());
        responseObserver.onCompleted();
    }

    private ShopItemResponse toResponse(ShopItem item) {
        ShopItemResponse.Builder builder = ShopItemResponse.newBuilder()
                .setId(item.getId())
                .setOrganizationId(item.getOrganizationId())
                .setName(item.getName())
                .setSlug(item.getSlug())
                .setPriceGrosze(item.getPriceGrosze())
                .setIsDefault(item.isDefault())
                .setActive(item.isActive())
                .setSort(item.getSort());

        if (item.getImage() != null) {
            builder.setImage(item.getImage());
        }
        if (item.getDescription() != null) {
            builder.setDescription(item.getDescription());
        }
        if (item.getThankYouHeading() != null) {
            builder.setThankYouHeading(item.getThankYouHeading());
        }
        if (item.getThankYouBody() != null) {
            builder.setThankYouBody(item.getThankYouBody());
        }
        if (item.getThankYouImage() != null) {
            builder.setThankYouImage(item.getThankYouImage());
        }
        if (item.getMecenasOrganizationId() != null) {
            builder.setMecenasOrganizationId(item.getMecenasOrganizationId());
        }

        return builder.build();
    }
}
