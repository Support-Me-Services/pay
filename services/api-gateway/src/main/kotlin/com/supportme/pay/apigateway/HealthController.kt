package com.supportme.pay.apigateway

import io.grpc.StatusRuntimeException
import org.springframework.beans.factory.annotation.Qualifier
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.RestController
import pay.health.v1.HealthCheckRequest
import pay.health.v1.HealthCheckServiceGrpc
import java.util.concurrent.TimeUnit

/**
 * Demonstracja jedynej zasady architektury ekosystemu: REST na brzegu,
 * gRPC w środku. Te wywołania NIE są atrapą — lecą realnym gRPC do core-svc
 * i gateway-svc (PoC Fazy 1, Laravel/RoadRunner).
 */
@RestController
class HealthController(
    @Qualifier("coreSvcHealthStub") private val coreSvcHealthStub: HealthCheckServiceGrpc.HealthCheckServiceBlockingStub,
    @Qualifier("gatewaySvcHealthStub") private val gatewaySvcHealthStub: HealthCheckServiceGrpc.HealthCheckServiceBlockingStub,
) {
    @GetMapping("/api/v1/health")
    fun health(): Map<String, Any> {
        return mapOf(
            "apiGateway" to "UP",
            "coreSvc" to checkGrpc(coreSvcHealthStub),
            "gatewaySvc" to checkGrpc(gatewaySvcHealthStub),
        )
    }

    private fun checkGrpc(stub: HealthCheckServiceGrpc.HealthCheckServiceBlockingStub): Map<String, Any> {
        val request = HealthCheckRequest.newBuilder()
            .setCaller("api-gateway")
            .build()

        return try {
            // Faza 5: deadline jawny — bez niego zawieszony peer wisiałby
            // w nieskończoność (dotyczy każdego blocking-stuba w tym serwisie).
            val response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).check(request)
            mapOf(
                "status" to response.status.name,
                "serviceName" to response.serviceName,
                "message" to response.message,
            )
        } catch (e: StatusRuntimeException) {
            mapOf(
                "status" to "UNREACHABLE",
                "error" to (e.message ?: e.javaClass.simpleName),
            )
        }
    }
}
