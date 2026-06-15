<?php

use App\Modules\Gateway\GatewayServiceProvider;
use App\Modules\Storefront\StorefrontServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    GatewayServiceProvider::class,
    StorefrontServiceProvider::class,
];
