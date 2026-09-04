package com.supportme.pay.coresvc.grpc;

import io.grpc.stub.StreamObserver;
import net.devh.boot.grpc.server.service.GrpcService;
import pay.health.v1.HealthCheckRequest;
import pay.health.v1.HealthCheckResponse;
import pay.health.v1.HealthCheckServiceGrpc;

/** Druga strona demo REST(api-gateway)->gRPC(core-svc) — realna implementacja, nie atrapa. */
@GrpcService
public class HealthGrpcService extends HealthCheckServiceGrpc.HealthCheckServiceImplBase {

    @Override
    public void check(HealthCheckRequest request, StreamObserver<HealthCheckResponse> responseObserver) {
        HealthCheckResponse response = HealthCheckResponse.newBuilder()
                .setStatus(HealthCheckResponse.Status.SERVING)
                .setServiceName("core-svc")
                .setMessage("core-svc odpowiada na wywołanie od: " + request.getCaller())
                .build();

        responseObserver.onNext(response);
        responseObserver.onCompleted();
    }
}
