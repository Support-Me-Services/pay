package com.supportme.pay.orgsvc.grpc;

import io.grpc.stub.StreamObserver;
import net.devh.boot.grpc.server.service.GrpcService;
import pay.health.v1.HealthCheckRequest;
import pay.health.v1.HealthCheckResponse;
import pay.health.v1.HealthCheckServiceGrpc;

/** Ten sam współdzielony kontrakt co core-svc — patrz proto/health/v1. */
@GrpcService
public class HealthGrpcService extends HealthCheckServiceGrpc.HealthCheckServiceImplBase {

    @Override
    public void check(HealthCheckRequest request, StreamObserver<HealthCheckResponse> responseObserver) {
        HealthCheckResponse response = HealthCheckResponse.newBuilder()
                .setStatus(HealthCheckResponse.Status.SERVING)
                .setServiceName("org-svc")
                .setMessage("org-svc odpowiada na wywołanie od: " + request.getCaller())
                .build();

        responseObserver.onNext(response);
        responseObserver.onCompleted();
    }
}
