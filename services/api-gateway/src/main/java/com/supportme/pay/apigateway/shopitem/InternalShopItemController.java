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
import pay.shopitem.v1.GetShopItemBySlugRequest;
import pay.shopitem.v1.GetShopItemRequest;
import pay.shopitem.v1.ListShopItemsRequest;
import pay.shopitem.v1.ShopItemServiceGrpc;
import pay.shopitem.v1.UpdateShopItemRequest;

import java.util.List;
import java.util.Map;
import java.util.concurrent.TimeUnit;
import java.util.function.Supplier;
import java.util.stream.Collectors;

/**
 * CRUD dla produktów sklepu — mirror InternalOrganizationController.
 * `getBySlug` publiczne strony sklepu i rozwiązywanie skanów NFC/QR (target
 * SHOP_ITEM z core-svc/InitCode::Resolve) będą wołać po szczegóły produktu.
 */
@RestController
@RequestMapping("/internal/v1/shop-items")
public class InternalShopItemController {

    private final ShopItemServiceGrpc.ShopItemServiceBlockingStub stub;

    public InternalShopItemController(
            @Qualifier("cmsSvcShopItemStub") ShopItemServiceGrpc.ShopItemServiceBlockingStub stub) {
        this.stub = stub;
    }

    @PostMapping
    public ResponseEntity<?> create(@RequestBody CreateShopItemDto body) {
        return handle(() -> {
            CreateShopItemRequest.Builder request = CreateShopItemRequest.newBuilder()
                    .setSlug(body.slug())
                    .setName(body.name())
                    .setMinAmount(body.minAmount())
                    .setIsDefault(body.isDefault())
                    .setSort(body.sort());
            if (body.organizationId() != null) {
                request.setOrganizationId(body.organizationId());
            }
            if (body.image() != null) {
                request.setImage(body.image());
            }
            if (body.price() != null) {
                request.setPrice(body.price());
            }
            if (body.description() != null) {
                request.setDescription(body.description());
            }

            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).create(request.build());
            return ResponseEntity.status(HttpStatus.CREATED).body(ShopItemDto.from(response));
        });
    }

    @GetMapping("/{id}")
    public ResponseEntity<?> get(@PathVariable long id) {
        return handle(() -> {
            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).get(
                    GetShopItemRequest.newBuilder().setId(id).build());
            return ResponseEntity.ok(ShopItemDto.from(response));
        });
    }

    @GetMapping("/by-slug")
    public ResponseEntity<?> getBySlug(@RequestParam long organizationId, @RequestParam String slug) {
        return handle(() -> {
            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).getBySlug(
                    GetShopItemBySlugRequest.newBuilder().setOrganizationId(organizationId).setSlug(slug).build());
            return ResponseEntity.ok(ShopItemDto.from(response));
        });
    }

    @GetMapping
    public ResponseEntity<?> listByOrganization(
            @RequestParam(required = false) Long organizationId,
            @RequestParam(required = false, defaultValue = "false") boolean activeOnly) {
        return handle(() -> {
            ListShopItemsRequest.Builder request = ListShopItemsRequest.newBuilder().setActiveOnly(activeOnly);
            if (organizationId != null) {
                request.setOrganizationId(organizationId);
            }

            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).listByOrganization(request.build());
            List<ShopItemDto> items = response.getItemsList().stream()
                    .map(ShopItemDto::from)
                    .collect(Collectors.toList());
            return ResponseEntity.ok(items);
        });
    }

    @PutMapping("/{id}")
    public ResponseEntity<?> update(@PathVariable long id, @RequestBody UpdateShopItemDto body) {
        return handle(() -> {
            UpdateShopItemRequest.Builder request = UpdateShopItemRequest.newBuilder()
                    .setId(id)
                    .setSlug(body.slug())
                    .setName(body.name())
                    .setMinAmount(body.minAmount())
                    .setIsDefault(body.isDefault())
                    .setActive(body.active())
                    .setSort(body.sort());
            if (body.organizationId() != null) {
                request.setOrganizationId(body.organizationId());
            }
            if (body.image() != null) {
                request.setImage(body.image());
            }
            if (body.price() != null) {
                request.setPrice(body.price());
            }
            if (body.description() != null) {
                request.setDescription(body.description());
            }
            if (body.thankYouHeading() != null) {
                request.setThankYouHeading(body.thankYouHeading());
            }
            if (body.thankYouBody() != null) {
                request.setThankYouBody(body.thankYouBody());
            }
            if (body.thankYouImage() != null) {
                request.setThankYouImage(body.thankYouImage());
            }
            if (body.mecenasOrganizationId() != null) {
                request.setMecenasOrganizationId(body.mecenasOrganizationId());
            }

            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).update(request.build());
            return ResponseEntity.ok(ShopItemDto.from(response));
        });
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<?> delete(@PathVariable long id, @RequestParam(required = false) Long organizationId) {
        return handle(() -> {
            DeleteShopItemRequest.Builder request = DeleteShopItemRequest.newBuilder().setId(id);
            if (organizationId != null) {
                request.setOrganizationId(organizationId);
            }
            stub.withDeadlineAfter(2, TimeUnit.SECONDS).delete(request.build());
            return ResponseEntity.noContent().build();
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
            String message = e.getStatus().getDescription() != null
                    ? e.getStatus().getDescription()
                    : e.getStatus().getCode().name();
            return ResponseEntity.status(status).body(Map.of("error", message));
        }
    }
}
