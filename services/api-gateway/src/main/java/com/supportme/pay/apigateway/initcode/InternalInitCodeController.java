package com.supportme.pay.apigateway.initcode;

import io.grpc.Status;
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
import pay.initcode.v1.CreateInitCodeRequest;
import pay.initcode.v1.DeleteInitCodeRequest;
import pay.initcode.v1.InitCodeServiceGrpc;
import pay.initcode.v1.ListInitCodesRequest;
import pay.initcode.v1.ToggleInitCodeRequest;
import pay.initcode.v1.UpdateInitCodeRequest;

import java.util.List;
import java.util.Map;
import java.util.concurrent.TimeUnit;
import java.util.function.Supplier;
import java.util.stream.Collectors;

/**
 * CRUD dla kodów inicjalizacji kontaktu — WYŁĄCZNIE do wywołania przez
 * gateway-svc (Laravel), server-to-server, nigdy z przeglądarki/mobile.
 * Chronione osobnym mechanizmem niż JWT Keycloaka — patrz SecurityConfig
 * (prefiks `/internal/`, nagłówek X-Internal-Api-Key). Dziś nic w produkcyjnym
 * Laravelu tego nie woła — to weryfikacja end-to-end (curl), nie realne
 * podłączenie panelu, patrz plan Fazy 5 w
 * claude/marcin/03-ekosystem-mikroserwisow.md.
 */
@RestController
@RequestMapping("/internal/v1/init-codes")
public class InternalInitCodeController {

    private final InitCodeServiceGrpc.InitCodeServiceBlockingStub stub;

    public InternalInitCodeController(
            @Qualifier("coreSvcInitCodeStub") InitCodeServiceGrpc.InitCodeServiceBlockingStub stub) {
        this.stub = stub;
    }

    @PostMapping
    public ResponseEntity<?> create(@RequestBody CreateInitCodeDto body) {
        return handle(() -> {
            CreateInitCodeRequest.Builder request = CreateInitCodeRequest.newBuilder()
                    .setOwner(body.owner().toProto())
                    .setLabel(body.label());
            if (body.shopItemId() != null) {
                request.setShopItemId(body.shopItemId());
            }
            if (body.targetOrganizationId() != null) {
                request.setTargetOrganizationId(body.targetOrganizationId());
            }

            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).create(request.build());
            return ResponseEntity.status(HttpStatus.CREATED).body(InitCodeDto.from(response));
        });
    }

    @GetMapping
    public ResponseEntity<?> list(
            @RequestParam(required = false) Long organizationId,
            @RequestParam(required = false) Long ownerUserId) {
        return handle(() -> {
            var owner = new OwnerScopeDto(organizationId, ownerUserId).toProto();
            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).list(
                    ListInitCodesRequest.newBuilder().setOwner(owner).build());
            List<InitCodeDto> codes = response.getCodesList().stream()
                    .map(InitCodeDto::from)
                    .collect(Collectors.toList());
            return ResponseEntity.ok(codes);
        });
    }

    @PutMapping("/{id}")
    public ResponseEntity<?> update(@PathVariable long id, @RequestBody UpdateInitCodeDto body) {
        return handle(() -> {
            UpdateInitCodeRequest.Builder request = UpdateInitCodeRequest.newBuilder()
                    .setId(id)
                    .setOwner(body.owner().toProto())
                    .setLabel(body.label());
            if (body.shopItemId() != null) {
                request.setShopItemId(body.shopItemId());
            }
            if (body.targetOrganizationId() != null) {
                request.setTargetOrganizationId(body.targetOrganizationId());
            }

            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).update(request.build());
            return ResponseEntity.ok(InitCodeDto.from(response));
        });
    }

    @PostMapping("/{id}/toggle")
    public ResponseEntity<?> toggle(@PathVariable long id, @RequestBody ScopedRequestDto body) {
        return handle(() -> {
            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).toggle(
                    ToggleInitCodeRequest.newBuilder().setId(id).setOwner(body.owner().toProto()).build());
            return ResponseEntity.ok(InitCodeDto.from(response));
        });
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<?> delete(@PathVariable long id, @RequestBody ScopedRequestDto body) {
        return handle(() -> {
            stub.withDeadlineAfter(2, TimeUnit.SECONDS).delete(
                    DeleteInitCodeRequest.newBuilder().setId(id).setOwner(body.owner().toProto()).build());
            return ResponseEntity.noContent().build();
        });
    }

    /** Tłumaczy statusy gRPC z core-svc (egzekwowanie własności, walidacja) na HTTP. */
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
