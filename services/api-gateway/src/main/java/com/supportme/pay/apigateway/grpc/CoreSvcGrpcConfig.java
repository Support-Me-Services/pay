package com.supportme.pay.apigateway.grpc;

import io.grpc.ManagedChannel;
import io.grpc.ManagedChannelBuilder;
import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import pay.health.v1.HealthCheckServiceGrpc;
import pay.initcode.v1.InitCodeServiceGrpc;

/** Kanał gRPC do core-svc — jedyne miejsce, gdzie api-gateway zna jego adres sieciowy. */
@Configuration
public class CoreSvcGrpcConfig {

    private final String host;
    private final int port;

    public CoreSvcGrpcConfig(
            @Value("${pay.core-svc.grpc-host}") String host,
            @Value("${pay.core-svc.grpc-port}") int port) {
        this.host = host;
        this.port = port;
    }

    @Bean(destroyMethod = "shutdown")
    public ManagedChannel coreSvcChannel() {
        return ManagedChannelBuilder.forAddress(host, port)
                .usePlaintext()
                .build();
    }

    @Bean
    public HealthCheckServiceGrpc.HealthCheckServiceBlockingStub coreSvcHealthStub(
            @Qualifier("coreSvcChannel") ManagedChannel channel) {
        return HealthCheckServiceGrpc.newBlockingStub(channel);
    }

    @Bean
    public InitCodeServiceGrpc.InitCodeServiceBlockingStub coreSvcInitCodeStub(
            @Qualifier("coreSvcChannel") ManagedChannel channel) {
        return InitCodeServiceGrpc.newBlockingStub(channel);
    }
}
