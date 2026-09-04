package com.supportme.pay.apigateway.grpc;

import io.grpc.ManagedChannel;
import io.grpc.ManagedChannelBuilder;
import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import pay.health.v1.HealthCheckServiceGrpc;
import pay.storefront.v1.StorefrontServiceGrpc;

/**
 * Kanał gRPC do gateway-svc (Laravel/RoadRunner, PoC Fazy 1) — osobny stack
 * Dockera od core-svc, stąd osobna konfiguracja hosta/portu.
 */
@Configuration
public class GatewaySvcGrpcConfig {

    private final String host;
    private final int port;

    public GatewaySvcGrpcConfig(
            @Value("${pay.gateway-svc.grpc-host}") String host,
            @Value("${pay.gateway-svc.grpc-port}") int port) {
        this.host = host;
        this.port = port;
    }

    @Bean(destroyMethod = "shutdown")
    public ManagedChannel gatewaySvcChannel() {
        return ManagedChannelBuilder.forAddress(host, port)
                .usePlaintext()
                .build();
    }

    @Bean
    public HealthCheckServiceGrpc.HealthCheckServiceBlockingStub gatewaySvcHealthStub(
            @Qualifier("gatewaySvcChannel") ManagedChannel channel) {
        return HealthCheckServiceGrpc.newBlockingStub(channel);
    }

    @Bean
    public StorefrontServiceGrpc.StorefrontServiceBlockingStub gatewaySvcStorefrontStub(
            @Qualifier("gatewaySvcChannel") ManagedChannel channel) {
        return StorefrontServiceGrpc.newBlockingStub(channel);
    }
}
