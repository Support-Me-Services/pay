package com.supportme.pay.apigateway;

import io.grpc.StatusRuntimeException;
import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RestController;
import pay.health.v1.HealthCheckRequest;
import pay.health.v1.HealthCheckResponse;
import pay.health.v1.HealthCheckServiceGrpc;

import java.util.LinkedHashMap;
import java.util.Map;
import java.util.concurrent.TimeUnit;

/**
 * Demonstracja jedynej zasady architektury ekosystemu: REST na brzegu,
 * gRPC w środku. Te wywołania NIE są atrapą — lecą realnym gRPC do core-svc
 * i gateway-svc (PoC Fazy 1, Laravel/RoadRunner).
 */
@RestController
public class HealthController {

    private final HealthCheckServiceGrpc.HealthCheckServiceBlockingStub coreSvcHealthStub;
    private final HealthCheckServiceGrpc.HealthCheckServiceBlockingStub gatewaySvcHealthStub;

    public HealthController(
            @Qualifier("coreSvcHealthStub") HealthCheckServiceGrpc.HealthCheckServiceBlockingStub coreSvcHealthStub,
            @Qualifier("gatewaySvcHealthStub") HealthCheckServiceGrpc.HealthCheckServiceBlockingStub gatewaySvcHealthStub) {
        this.coreSvcHealthStub = coreSvcHealthStub;
        this.gatewaySvcHealthStub = gatewaySvcHealthStub;
    }

    @GetMapping("/api/v1/health")
    public Map<String, Object> health() {
        Map<String, Object> result = new LinkedHashMap<>();
        result.put("apiGateway", "UP");
        result.put("coreSvc", checkGrpc(coreSvcHealthStub));
        result.put("gatewaySvc", checkGrpc(gatewaySvcHealthStub));
        return result;
    }

    private Map<String, Object> checkGrpc(HealthCheckServiceGrpc.HealthCheckServiceBlockingStub stub) {
        HealthCheckRequest request = HealthCheckRequest.newBuilder()
                .setCaller("api-gateway")
                .build();

        Map<String, Object> result = new LinkedHashMap<>();
        try {
            // Faza 5: deadline jawny — bez niego zawieszony peer wisiałby
            // w nieskończoność (dotyczy każdego blocking-stuba w tym serwisie).
            HealthCheckResponse response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).check(request);
            result.put("status", response.getStatus().name());
            result.put("serviceName", response.getServiceName());
            result.put("message", response.getMessage());
        } catch (StatusRuntimeException e) {
            result.put("status", "UNREACHABLE");
            result.put("error", e.getMessage() != null ? e.getMessage() : e.getClass().getSimpleName());
        }
        return result;
    }
}
