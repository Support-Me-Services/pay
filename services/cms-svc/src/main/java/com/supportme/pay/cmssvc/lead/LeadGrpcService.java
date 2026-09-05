package com.supportme.pay.cmssvc.lead;

import io.grpc.stub.StreamObserver;
import net.devh.boot.grpc.server.service.GrpcService;
import pay.lead.v1.CreateLeadRequest;
import pay.lead.v1.LeadResponse;
import pay.lead.v1.LeadServiceGrpc;
import pay.lead.v1.ListLeadsRequest;
import pay.lead.v1.ListLeadsResponse;

@GrpcService
public class LeadGrpcService extends LeadServiceGrpc.LeadServiceImplBase {

    private final LeadRepository repository;

    public LeadGrpcService(LeadRepository repository) {
        this.repository = repository;
    }

    @Override
    public void create(CreateLeadRequest request, StreamObserver<LeadResponse> responseObserver) {
        Lead entity = new Lead(
                request.getName(),
                request.getEmail(),
                request.getPhone(),
                request.hasCompany() ? request.getCompany() : null,
                request.getMessage()
        );
        Lead saved = repository.save(entity);
        responseObserver.onNext(toResponse(saved));
        responseObserver.onCompleted();
    }

    @Override
    public void list(ListLeadsRequest request, StreamObserver<ListLeadsResponse> responseObserver) {
        ListLeadsResponse.Builder response = ListLeadsResponse.newBuilder();
        repository.findAll().forEach(lead -> response.addLeads(toResponse(lead)));
        responseObserver.onNext(response.build());
        responseObserver.onCompleted();
    }

    private LeadResponse toResponse(Lead entity) {
        LeadResponse.Builder builder = LeadResponse.newBuilder()
                .setId(entity.getId())
                .setName(entity.getName())
                .setEmail(entity.getEmail())
                .setPhone(entity.getPhone())
                .setMessage(entity.getMessage());

        if (entity.getCompany() != null) {
            builder.setCompany(entity.getCompany());
        }

        return builder.build();
    }
}
