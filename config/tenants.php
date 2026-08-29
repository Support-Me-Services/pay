<?php

return [

    // Mapa: nazwa hosta (domena żądania) => tenant.
    //   module => gateway | storefront  (który moduł obsługuje hosta)
    //   kind   => church | null  (wariant sklepu; null dla bramki)
    //   db         => nazwa bazy MySQL (ten sam user nfc_pay ma dostęp do wszystkich)
    //   gateway_api_key => klucz API sklepu do bramki (per-host!) — sekret z .env,
    //                      NIE trzymamy go w repo. Dla modułu gateway = null.
    'map' => [
        // Rozwój lokalny (docker/) — localhost trafia na sklep church (baza nfc_shop1).
        // Klucz z GATEWAY_API_KEY_CHURCH (patrz .env.docker) — do pelnego mock-flow
        // platnosci lokalnie (bez niego "Wesprzyj" konczy sie 401 z bramki).
        'localhost'                   => ['module' => 'storefront', 'kind' => 'church', 'db' => 'nfc_shop1', 'gateway_api_key' => env('GATEWAY_API_KEY_CHURCH')],
        'please-support-me.com'       => ['module' => 'storefront', 'kind' => 'church', 'db' => 'nfc_shop1', 'gateway_api_key' => env('GATEWAY_API_KEY_CHURCH')],
        'stage.please-support-me.com' => ['module' => 'storefront', 'kind' => 'church', 'db' => 'nfc_shop1', 'gateway_api_key' => env('GATEWAY_API_KEY_CHURCH')],
        'pay.please-support-me.com'   => ['module' => 'gateway',    'kind' => null,     'db' => 'nfc_pay',   'gateway_api_key' => null],
    ],

    // Fallback dla CLI / nieznanego hosta; nadpisywalny przez TENANT
    // (np. `TENANT=please-support-me.com php artisan db:seed`).
    'default' => env('TENANT', 'pay.please-support-me.com'),

];
