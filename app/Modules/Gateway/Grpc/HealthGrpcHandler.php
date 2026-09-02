<?php

namespace App\Modules\Gateway\Grpc;

use Pay\Health\V1\HealthCheckRequest;
use Pay\Health\V1\HealthCheckResponse;
use Pay\Health\V1\HealthCheckResponse\Status;
use Pay\Health\V1\HealthCheckServiceInterface;
use Spiral\RoadRunner\GRPC\ContextInterface;

/**
 * PoC Fazy 1: gRPC server dla gateway-svc (Laravel), pod RoadRunner.
 * Świadomie trywialny — dowodzi, że łańcuch api-gateway (Kotlin) -> gRPC ->
 * Laravel działa, zanim popłynie przez niego realna domena (transakcje).
 * Patrz proto/README.md i dokument architektury, sekcja "Kontrakty gRPC".
 */
class HealthGrpcHandler implements HealthCheckServiceInterface
{
    public function Check(ContextInterface $ctx, HealthCheckRequest $in): HealthCheckResponse
    {
        return new HealthCheckResponse([
            'status' => Status::SERVING,
            'service_name' => 'gateway-svc',
            'message' => 'gateway-svc (Laravel/RoadRunner) odpowiada na wywołanie od: ' . $in->getCaller(),
        ]);
    }
}
