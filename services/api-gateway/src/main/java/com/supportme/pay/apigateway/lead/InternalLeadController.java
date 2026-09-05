package com.supportme.pay.apigateway.lead;

import io.grpc.StatusRuntimeException;
import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;
import pay.lead.v1.CreateLeadRequest;
import pay.lead.v1.LeadServiceGrpc;
import pay.lead.v1.ListLeadsRequest;

import java.util.List;
import java.util.Map;
import java.util.concurrent.TimeUnit;
import java.util.function.Supplier;
import java.util.stream.Collectors;

/** Leady ze strony lądowania — tylko Create (formularz publiczny) + List (panel), mirror wzorca. */
@RestController
@RequestMapping("/internal/v1/leads")
public class InternalLeadController {

    private final LeadServiceGrpc.LeadServiceBlockingStub stub;

    public InternalLeadController(@Qualifier("cmsSvcLeadStub") LeadServiceGrpc.LeadServiceBlockingStub stub) {
        this.stub = stub;
    }

    @PostMapping
    public ResponseEntity<?> create(@RequestBody CreateLeadDto body) {
        return handle(() -> {
            CreateLeadRequest.Builder request = CreateLeadRequest.newBuilder()
                    .setName(body.name())
                    .setEmail(body.email())
                    .setPhone(body.phone())
                    .setMessage(body.message());
            if (body.company() != null) {
                request.setCompany(body.company());
            }

            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).create(request.build());
            return ResponseEntity.status(HttpStatus.CREATED).body(LeadDto.from(response));
        });
    }

    @GetMapping
    public ResponseEntity<?> list() {
        return handle(() -> {
            var response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).list(ListLeadsRequest.newBuilder().build());
            List<LeadDto> leads = response.getLeadsList().stream()
                    .map(LeadDto::from)
                    .collect(Collectors.toList());
            return ResponseEntity.ok(leads);
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
