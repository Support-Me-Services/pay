package com.supportme.pay.apigateway.beneficiary;

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
import pay.beneficiary.v1.UpdateBeneficiaryNodeRequest;

import java.util.List;
import java.util.Map;
import java.util.concurrent.TimeUnit;
import java.util.function.Supplier;
import java.util.stream.Collectors;

/** CRUD dla węzłów "Wspieramy"/"O nas" — mirror InternalOrganizationController. */
@RestController
@RequestMapping("/internal/v1/beneficiary-nodes")
public class InternalBeneficiaryNodeController {

    private final BeneficiaryNodeServiceGrpc.BeneficiaryNodeServiceBlockingStub stub;

    public InternalBeneficiaryNodeController(
            @Qualifier("cmsSvcBeneficiaryNodeStub") BeneficiaryNodeServiceGrpc.BeneficiaryNodeServiceBlockingStub stub) {
        this.stub = stub;
    }

    @PostMapping
    public ResponseEntity<?> create(@RequestBody CreateBeneficiaryNodeDto body) {
        return handle(() -> {
            CreateBeneficiaryNodeRequest.Builder request = CreateBeneficiaryNodeRequest.newBuilder()
                    .setHeading(body.heading())
                    .setImageSide(body.imageSide())
                    .setImageScale(body.imageScale())
                    .setImageX(body.imageX())
                    .setImageY(body.imageY())
                    .setTextAlign(body.textAlign())
                    .setPosition(body.position());
            if (body.organizationId() != null) {
                request.setOrganizationId(body.organizationId());
            }
            if (body.image() != null) {
                request.setImage(body.image());
            }
            if (body.bodyHtml() != null) {
                request.setBodyHtml(body.bodyHtml());
            }

            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).create(request.build());
            return ResponseEntity.status(HttpStatus.CREATED).body(BeneficiaryNodeDto.from(response));
        });
    }

    @GetMapping
    public ResponseEntity<?> listByOrganization(
            @RequestParam(required = false) Long organizationId,
            @RequestParam(required = false, defaultValue = "false") boolean activeOnly) {
        return handle(() -> {
            ListBeneficiaryNodesRequest.Builder request = ListBeneficiaryNodesRequest.newBuilder().setActiveOnly(activeOnly);
            if (organizationId != null) {
                request.setOrganizationId(organizationId);
            }

            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).listByOrganization(request.build());
            List<BeneficiaryNodeDto> nodes = response.getNodesList().stream()
                    .map(BeneficiaryNodeDto::from)
                    .collect(Collectors.toList());
            return ResponseEntity.ok(nodes);
        });
    }

    @PutMapping("/{id}")
    public ResponseEntity<?> update(@PathVariable long id, @RequestBody UpdateBeneficiaryNodeDto body) {
        return handle(() -> {
            UpdateBeneficiaryNodeRequest.Builder request = UpdateBeneficiaryNodeRequest.newBuilder()
                    .setId(id)
                    .setHeading(body.heading())
                    .setImageSide(body.imageSide())
                    .setImageScale(body.imageScale())
                    .setImageX(body.imageX())
                    .setImageY(body.imageY())
                    .setTextAlign(body.textAlign())
                    .setPosition(body.position())
                    .setActive(body.active());
            if (body.organizationId() != null) {
                request.setOrganizationId(body.organizationId());
            }
            if (body.image() != null) {
                request.setImage(body.image());
            }
            if (body.bodyHtml() != null) {
                request.setBodyHtml(body.bodyHtml());
            }

            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).update(request.build());
            return ResponseEntity.ok(BeneficiaryNodeDto.from(response));
        });
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<?> delete(@PathVariable long id, @RequestParam(required = false) Long organizationId) {
        return handle(() -> {
            DeleteBeneficiaryNodeRequest.Builder request = DeleteBeneficiaryNodeRequest.newBuilder().setId(id);
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
