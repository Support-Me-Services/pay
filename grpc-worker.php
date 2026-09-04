<?php

/**
 * Worker RoadRunner — PoC Fazy 1 ekosystemu mikroserwisów (serwer gRPC dla
 * gateway-svc). Bootuje pełną aplikację Laravel (te same bootstrappery co
 * `artisan` — config, providery, itd.), żeby handlery gRPC miały dostęp do
 * kontenera/Eloquent tak samo jak zwykły kontroler HTTP.
 *
 * UWAGA: to proces DŁUGOŻYJĄCY (jak Octane/Swoole) — stan aplikacji między
 * wywołaniami NIE resetuje się automatycznie tak jak przy php-fpm/artisan
 * serve. Dla trywialnego health checku bez znaczenia; przy pierwszej realnej
 * domenie trzeba będzie pilnować singletonów trzymających stan per-request.
 */

require __DIR__.'/vendor/autoload.php';

use App\Modules\Gateway\Grpc\HealthGrpcHandler;
use App\Modules\Storefront\Grpc\StorefrontGrpcHandler;
use Illuminate\Contracts\Console\Kernel;
use Pay\Health\V1\HealthCheckServiceInterface;
use Pay\Storefront\V1\StorefrontServiceInterface;
use Spiral\RoadRunner\GRPC\Invoker;
use Spiral\RoadRunner\GRPC\Server;
use Spiral\RoadRunner\Worker;

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$server = new Server(new Invoker(), ['debug' => false]);
$server->registerService(
    HealthCheckServiceInterface::class,
    $app->make(HealthGrpcHandler::class),
);
$server->registerService(
    StorefrontServiceInterface::class,
    $app->make(StorefrontGrpcHandler::class),
);
$server->serve(Worker::create());
