package com.supportme.pay.apigateway.shopitem;

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
import pay.shopitem.v1.CreateShopItemRequest;
import pay.shopitem.v1.DeleteShopItemRequest;
import pay.shopitem.v1.GetShopItemRequest;
import pay.shopitem.v1.ListAllShopItemsRequest;
import pay.shopitem.v1.ListShopItemsRequest;
import pay.shopitem.v1.ShopItemServiceGrpc;
import pay.shopitem.v1.ToggleShopItemRequest;
import pay.shopitem.v1.UpdateShopItemRequest;

import java.util.List;
import java.util.Map;
import java.util.concurrent.TimeUnit;
import java.util.function.Supplier;
import java.util.stream.Collectors;

/** CRUD dla produktów sklepu — WYŁĄCZNIE do wywołania przez gateway-svc (Laravel). */
@RestController
@RequestMapping("/internal/v1/shop-items")
public class InternalShopItemController {

    private final ShopItemServiceGrpc.ShopItemServiceBlockingStub stub;

    public InternalShopItemController(
            @Qualifier("orgSvcShopItemStub") ShopItemServiceGrpc.ShopItemServiceBlockingStub stub) {
        this.stub = stub;
    }

    @PostMapping
    public ResponseEntity<?> create(@RequestBody UpsertShopItemDto body) {
        return handle(() -> {
            var request = CreateShopItemRequest.newBuilder()
                    .setOrganizationId(body.organizationId())
                    .setName(body.name())
                    .setPriceGrosze(body.priceGrosze())
                    .setSort(body.sort() != null ? body.sort() : 0)
                    .setIsDefault(body.isDefault());
            if (body.slug() != null) request.setSlug(body.slug());
            if (body.image() != null) request.setImage(body.image());
            if (body.description() != null) request.setDescription(body.description());
            if (body.thankYouHeading() != null) request.setThankYouHeading(body.thankYouHeading());
            if (body.thankYouBody() != null) request.setThankYouBody(body.thankYouBody());
            if (body.thankYouImage() != null) request.setThankYouImage(body.thankYouImage());
            if (body.mecenasOrganizationId() != null) request.setMecenasOrganizationId(body.mecenasOrganizationId());

            var response = stub.withDeadlineAfter(5, TimeUnit.SECONDS).create(request.build());
            return ResponseEntity.status(HttpStatus.CREATED).body(ShopItemDto.from(response));
        });
    }

    @GetMapping("/{id}")
    public ResponseEntity<?> get(@PathVariable long id) {
        return handle(() -> {
            var response = stub.withDeadlineAfter(5, TimeUnit.SECONDS).get(GetShopItemRequest.newBuilder().setId(id).build());
            return ResponseEntity.ok(ShopItemDto.from(response));
        });
    }

    @GetMapping
    public ResponseEntity<?> list(@RequestParam(required = false) Long organizationId,
                                   @RequestParam(required = false) Boolean activeOnly) {
        return handle(() -> {
            var response = organizationId == null
                    ? stub.withDeadlineAfter(5, TimeUnit.SECONDS).listAll(ListAllShopItemsRequest.newBuilder().build())
                    : Boolean.TRUE.equals(activeOnly)
                        ? stub.withDeadlineAfter(5, TimeUnit.SECONDS).listActiveByOrganization(ListShopItemsRequest.newBuilder().setOrganizationId(organizationId).build())
                        : stub.withDeadlineAfter(5, TimeUnit.SECONDS).listByOrganization(ListShopItemsRequest.newBuilder().setOrganizationId(organizationId).build());
            List<ShopItemDto> items = response.getItemsList().stream().map(ShopItemDto::from).collect(Collectors.toList());
            return ResponseEntity.ok(items);
        });
    }

    @PutMapping("/{id}")
    public ResponseEntity<?> update(@PathVariable long id, @RequestBody UpsertShopItemDto body) {
        return handle(() -> {
            var request = UpdateShopItemRequest.newBuilder()
                    .setId(id)
                    .setOrganizationId(body.organizationId())
                    .setName(body.name())
                    .setPriceGrosze(body.priceGrosze())
                    .setSort(body.sort() != null ? body.sort() : 0)
                    .setActive(body.active() == null || body.active())
                    .setClearImage(body.clearImage())
                    .setClearThankYouImage(body.clearThankYouImage())
                    .setClearMecenasOrganizationId(body.clearMecenasOrganizationId())
                    .setIsDefault(body.isDefault());
            if (body.slug() != null) request.setSlug(body.slug());
            if (body.image() != null) request.setImage(body.image());
            if (body.description() != null) request.setDescription(body.description());
            if (body.thankYouHeading() != null) request.setThankYouHeading(body.thankYouHeading());
            if (body.thankYouBody() != null) request.setThankYouBody(body.thankYouBody());
            if (body.thankYouImage() != null) request.setThankYouImage(body.thankYouImage());
            if (body.mecenasOrganizationId() != null) request.setMecenasOrganizationId(body.mecenasOrganizationId());

            var response = stub.withDeadlineAfter(5, TimeUnit.SECONDS).update(request.build());
            return ResponseEntity.ok(ShopItemDto.from(response));
        });
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<?> delete(@PathVariable long id, @RequestParam long organizationId) {
        return handle(() -> {
            stub.withDeadlineAfter(5, TimeUnit.SECONDS).delete(
                    DeleteShopItemRequest.newBuilder().setId(id).setOrganizationId(organizationId).build());
            return ResponseEntity.noContent().build();
        });
    }

    @PostMapping("/{id}/toggle")
    public ResponseEntity<?> toggle(@PathVariable long id, @RequestParam long organizationId) {
        return handle(() -> {
            var response = stub.withDeadlineAfter(5, TimeUnit.SECONDS).toggle(
                    ToggleShopItemRequest.newBuilder().setId(id).setOrganizationId(organizationId).build());
            return ResponseEntity.ok(ShopItemDto.from(response));
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
