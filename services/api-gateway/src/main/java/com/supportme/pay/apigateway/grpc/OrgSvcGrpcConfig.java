package com.supportme.pay.apigateway.grpc;

import io.grpc.ManagedChannel;
import io.grpc.ManagedChannelBuilder;
import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import pay.beneficiary.v1.BeneficiaryNodeServiceGrpc;
import pay.careers.v1.JobApplicationServiceGrpc;
import pay.careers.v1.JobPositionServiceGrpc;
import pay.organization.v1.OrganizationServiceGrpc;
import pay.shopitem.v1.ShopItemServiceGrpc;

/** Kanał gRPC do org-svc — jedyne miejsce, gdzie api-gateway zna jego adres sieciowy. */
@Configuration
public class OrgSvcGrpcConfig {

    private final String host;
    private final int port;

    public OrgSvcGrpcConfig(
            @Value("${pay.org-svc.grpc-host}") String host,
            @Value("${pay.org-svc.grpc-port}") int port) {
        this.host = host;
        this.port = port;
    }

    @Bean(destroyMethod = "shutdown")
    public ManagedChannel orgSvcChannel() {
        return ManagedChannelBuilder.forAddress(host, port)
                .usePlaintext()
                .build();
    }

    @Bean
    public OrganizationServiceGrpc.OrganizationServiceBlockingStub orgSvcOrganizationStub(
            @Qualifier("orgSvcChannel") ManagedChannel channel) {
        return OrganizationServiceGrpc.newBlockingStub(channel);
    }

    @Bean
    public BeneficiaryNodeServiceGrpc.BeneficiaryNodeServiceBlockingStub orgSvcBeneficiaryNodeStub(
            @Qualifier("orgSvcChannel") ManagedChannel channel) {
        return BeneficiaryNodeServiceGrpc.newBlockingStub(channel);
    }

    @Bean
    public ShopItemServiceGrpc.ShopItemServiceBlockingStub orgSvcShopItemStub(
            @Qualifier("orgSvcChannel") ManagedChannel channel) {
        return ShopItemServiceGrpc.newBlockingStub(channel);
    }

    @Bean
    public JobPositionServiceGrpc.JobPositionServiceBlockingStub orgSvcJobPositionStub(
            @Qualifier("orgSvcChannel") ManagedChannel channel) {
        return JobPositionServiceGrpc.newBlockingStub(channel);
    }

    @Bean
    public JobApplicationServiceGrpc.JobApplicationServiceBlockingStub orgSvcJobApplicationStub(
            @Qualifier("orgSvcChannel") ManagedChannel channel) {
        return JobApplicationServiceGrpc.newBlockingStub(channel);
    }
}
