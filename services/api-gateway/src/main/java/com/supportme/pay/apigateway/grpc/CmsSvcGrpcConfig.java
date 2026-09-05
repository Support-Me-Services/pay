package com.supportme.pay.apigateway.grpc;

import io.grpc.ManagedChannel;
import io.grpc.ManagedChannelBuilder;
import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import pay.beneficiary.v1.BeneficiaryNodeServiceGrpc;
import pay.career.v1.JobApplicationServiceGrpc;
import pay.career.v1.JobPositionServiceGrpc;
import pay.lead.v1.LeadServiceGrpc;
import pay.organization.v1.OrganizationServiceGrpc;
import pay.shopitem.v1.ShopItemServiceGrpc;

/** Kanał gRPC do cms-svc — jedyne miejsce, gdzie api-gateway zna jego adres sieciowy. */
@Configuration
public class CmsSvcGrpcConfig {

    private final String host;
    private final int port;

    public CmsSvcGrpcConfig(
            @Value("${pay.cms-svc.grpc-host}") String host,
            @Value("${pay.cms-svc.grpc-port}") int port) {
        this.host = host;
        this.port = port;
    }

    @Bean(destroyMethod = "shutdown")
    public ManagedChannel cmsSvcChannel() {
        return ManagedChannelBuilder.forAddress(host, port)
                .usePlaintext()
                .build();
    }

    @Bean
    public OrganizationServiceGrpc.OrganizationServiceBlockingStub cmsSvcOrganizationStub(
            @Qualifier("cmsSvcChannel") ManagedChannel channel) {
        return OrganizationServiceGrpc.newBlockingStub(channel);
    }

    @Bean
    public BeneficiaryNodeServiceGrpc.BeneficiaryNodeServiceBlockingStub cmsSvcBeneficiaryNodeStub(
            @Qualifier("cmsSvcChannel") ManagedChannel channel) {
        return BeneficiaryNodeServiceGrpc.newBlockingStub(channel);
    }

    @Bean
    public JobPositionServiceGrpc.JobPositionServiceBlockingStub cmsSvcJobPositionStub(
            @Qualifier("cmsSvcChannel") ManagedChannel channel) {
        return JobPositionServiceGrpc.newBlockingStub(channel);
    }

    @Bean
    public JobApplicationServiceGrpc.JobApplicationServiceBlockingStub cmsSvcJobApplicationStub(
            @Qualifier("cmsSvcChannel") ManagedChannel channel) {
        return JobApplicationServiceGrpc.newBlockingStub(channel);
    }

    @Bean
    public ShopItemServiceGrpc.ShopItemServiceBlockingStub cmsSvcShopItemStub(
            @Qualifier("cmsSvcChannel") ManagedChannel channel) {
        return ShopItemServiceGrpc.newBlockingStub(channel);
    }

    @Bean
    public LeadServiceGrpc.LeadServiceBlockingStub cmsSvcLeadStub(
            @Qualifier("cmsSvcChannel") ManagedChannel channel) {
        return LeadServiceGrpc.newBlockingStub(channel);
    }
}
