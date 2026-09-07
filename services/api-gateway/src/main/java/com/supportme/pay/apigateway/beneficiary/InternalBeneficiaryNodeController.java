package com.supportme.pay.apigateway.beneficiary;

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
import pay.beneficiary.v1.BeneficiaryNodeServiceGrpc;
import pay.beneficiary.v1.CreateBeneficiaryNodeRequest;
import pay.beneficiary.v1.DeleteBeneficiaryNodeRequest;
import pay.beneficiary.v1.ListBeneficiaryNodesRequest;
import pay.beneficiary.v1.ReorderBeneficiaryNodesRequest;
import pay.beneficiary.v1.UpdateBeneficiaryNodeRequest;

import java.util.List;
import java.util.Map;
import java.util.concurrent.TimeUnit;
import java.util.function.Supplier;
import java.util.stream.Collectors;

/** CRUD dla węzłów "O nas" — WYŁĄCZNIE do wywołania przez gateway-svc (Laravel), server-to-server. */
@RestController
@RequestMapping("/internal/v1/beneficiary-nodes")
public class InternalBeneficiaryNodeController {

    private final BeneficiaryNodeServiceGrpc.BeneficiaryNodeServiceBlockingStub stub;

    public InternalBeneficiaryNodeController(
            @Qualifier("orgSvcBeneficiaryNodeStub") BeneficiaryNodeServiceGrpc.BeneficiaryNodeServiceBlockingStub stub) {
        this.stub = stub;
    }

    @PostMapping
    public ResponseEntity<?> create(@RequestBody UpsertBeneficiaryNodeDto body) {
        return handle(() -> {
            var request = CreateBeneficiaryNodeRequest.newBuilder()
                    .setOrganizationId(body.organizationId())
                    .setHeading(body.heading())
                    .setImageSide(body.imageSide())
                    .setImageScale(body.imageScale() != null ? body.imageScale() : 100)
                    .setImageX(body.imageX() != null ? body.imageX() : 0)
                    .setImageY(body.imageY() != null ? body.imageY() : 0)
                    .setTextAlign(body.textAlign());
            if (body.image() != null) {
                request.setImage(body.image());
            }
            if (body.bodyHtml() != null) {
                request.setBodyHtml(body.bodyHtml());
            }
            var response = stub.withDeadlineAfter(5, TimeUnit.SECONDS).create(request.build());
            return ResponseEntity.status(HttpStatus.CREATED).body(BeneficiaryNodeDto.from(response));
        });
    }

    @GetMapping
    public ResponseEntity<?> list(@RequestParam long organizationId, @RequestParam(required = false) Boolean activeOnly) {
        return handle(() -> {
            var request = ListBeneficiaryNodesRequest.newBuilder().setOrganizationId(organizationId).build();
            var response = Boolean.TRUE.equals(activeOnly)
                    ? stub.withDeadlineAfter(5, TimeUnit.SECONDS).listActiveByOrganization(request)
                    : stub.withDeadlineAfter(5, TimeUnit.SECONDS).listByOrganization(request);
            List<BeneficiaryNodeDto> nodes = response.getNodesList().stream()
                    .map(BeneficiaryNodeDto::from)
                    .collect(Collectors.toList());
            return ResponseEntity.ok(nodes);
        });
    }

    @PutMapping("/{id}")
    public ResponseEntity<?> update(@PathVariable long id, @RequestBody UpsertBeneficiaryNodeDto body) {
        return handle(() -> {
            var request = UpdateBeneficiaryNodeRequest.newBuilder()
                    .setId(id)
                    .setOrganizationId(body.organizationId())
                    .setHeading(body.heading())
                    .setImageSide(body.imageSide())
                    .setImageScale(body.imageScale() != null ? body.imageScale() : 100)
                    .setImageX(body.imageX() != null ? body.imageX() : 0)
                    .setImageY(body.imageY() != null ? body.imageY() : 0)
                    .setTextAlign(body.textAlign());
            if (body.image() != null) {
                request.setImage(body.image());
            }
            if (body.bodyHtml() != null) {
                request.setBodyHtml(body.bodyHtml());
            }
            var response = stub.withDeadlineAfter(5, TimeUnit.SECONDS).update(request.build());
            return ResponseEntity.ok(BeneficiaryNodeDto.from(response));
        });
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<?> delete(@PathVariable long id, @RequestParam long organizationId) {
        return handle(() -> {
            var response = stub.withDeadlineAfter(5, TimeUnit.SECONDS).delete(
                    DeleteBeneficiaryNodeRequest.newBuilder().setId(id).setOrganizationId(organizationId).build());
            return ResponseEntity.ok(Map.of(
                    "deleted", response.getDeleted(),
                    "deletedImage", response.hasDeletedImage() ? response.getDeletedImage() : ""));
        });
    }

    @PostMapping("/reorder")
    public ResponseEntity<?> reorder(@RequestBody ReorderBeneficiaryNodesDto body) {
        return handle(() -> {
            stub.withDeadlineAfter(5, TimeUnit.SECONDS).reorder(
                    ReorderBeneficiaryNodesRequest.newBuilder()
                            .setOrganizationId(body.organizationId())
                            .addAllOrderedIds(body.orderedIds())
                            .build());
            return ResponseEntity.ok(Map.of("reordered", true));
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
