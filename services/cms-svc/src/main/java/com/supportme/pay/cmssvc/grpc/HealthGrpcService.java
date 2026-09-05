package com.supportme.pay.cmssvc.grpc;

import io.grpc.stub.StreamObserver;
import net.devh.boot.grpc.server.service.GrpcService;
import pay.health.v1.HealthCheckRequest;
import pay.health.v1.HealthCheckResponse;
import pay.health.v1.HealthCheckServiceGrpc;

/** Mirror core-svc's HealthGrpcService — pozwala api-gateway zweryfikować kanał gRPC do cms-svc. */
@GrpcService
public class HealthGrpcService extends HealthCheckServiceGrpc.HealthCheckServiceImplBase {

    @Override
    public void check(HealthCheckRequest request, StreamObserver<HealthCheckResponse> responseObserver) {
        HealthCheckResponse response = HealthCheckResponse.newBuilder()
                .setStatus(HealthCheckResponse.Status.SERVING)
                .setServiceName("cms-svc")
                .setMessage("cms-svc odpowiada na wywołanie od: " + request.getCaller())
                .build();

        responseObserver.onNext(response);
        responseObserver.onCompleted();
    }
}
