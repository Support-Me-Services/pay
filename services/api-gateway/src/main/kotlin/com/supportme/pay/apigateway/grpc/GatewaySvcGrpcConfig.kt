package com.supportme.pay.apigateway.grpc

import io.grpc.ManagedChannel
import io.grpc.ManagedChannelBuilder
import org.springframework.beans.factory.annotation.Qualifier
import org.springframework.beans.factory.annotation.Value
import org.springframework.context.annotation.Bean
import org.springframework.context.annotation.Configuration
import pay.health.v1.HealthCheckServiceGrpc

/**
 * Kanał gRPC do gateway-svc (Laravel/RoadRunner, PoC Fazy 1) — osobny stack
 * Dockera od core-svc, stąd osobna konfiguracja hosta/portu.
 */
@Configuration
open class GatewaySvcGrpcConfig(
    @Value("\${pay.gateway-svc.grpc-host}") private val host: String,
    @Value("\${pay.gateway-svc.grpc-port}") private val port: Int,
) {
    @Bean(destroyMethod = "shutdown")
    open fun gatewaySvcChannel(): ManagedChannel =
        ManagedChannelBuilder.forAddress(host, port)
            .usePlaintext()
            .build()

    @Bean
    open fun gatewaySvcHealthStub(
        @Qualifier("gatewaySvcChannel") channel: ManagedChannel,
    ): HealthCheckServiceGrpc.HealthCheckServiceBlockingStub =
        HealthCheckServiceGrpc.newBlockingStub(channel)
}
