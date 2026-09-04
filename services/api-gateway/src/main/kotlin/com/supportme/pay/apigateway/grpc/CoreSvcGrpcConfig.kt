package com.supportme.pay.apigateway.grpc

import io.grpc.ManagedChannel
import io.grpc.ManagedChannelBuilder
import org.springframework.beans.factory.annotation.Qualifier
import org.springframework.beans.factory.annotation.Value
import org.springframework.context.annotation.Bean
import org.springframework.context.annotation.Configuration
import pay.health.v1.HealthCheckServiceGrpc
import pay.initcode.v1.InitCodeServiceGrpc

/** Kanał gRPC do core-svc — jedyne miejsce, gdzie api-gateway zna jego adres sieciowy. */
@Configuration
open class CoreSvcGrpcConfig(
    @Value("\${pay.core-svc.grpc-host}") private val host: String,
    @Value("\${pay.core-svc.grpc-port}") private val port: Int,
) {
    @Bean(destroyMethod = "shutdown")
    open fun coreSvcChannel(): ManagedChannel =
        ManagedChannelBuilder.forAddress(host, port)
            .usePlaintext()
            .build()

    @Bean
    open fun coreSvcHealthStub(
        @Qualifier("coreSvcChannel") channel: ManagedChannel,
    ): HealthCheckServiceGrpc.HealthCheckServiceBlockingStub =
        HealthCheckServiceGrpc.newBlockingStub(channel)

    @Bean
    open fun coreSvcInitCodeStub(
        @Qualifier("coreSvcChannel") channel: ManagedChannel,
    ): InitCodeServiceGrpc.InitCodeServiceBlockingStub =
        InitCodeServiceGrpc.newBlockingStub(channel)
}
