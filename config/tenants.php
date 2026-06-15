<?php

return [

    // Mapa: nazwa hosta (domena żądania) => tenant.
    //   module => gateway | storefront  (który moduł obsługuje hosta)
    //   kind   => products | church | null  (wariant sklepu; null dla bramki)
    //   db         => nazwa bazy MySQL (ten sam user nfc_pay ma dostęp do wszystkich)
    //   gateway_api_key => klucz API sklepu do bramki (per-host!) — sekret z .env,
    //                      NIE trzymamy go w repo. Dla modułu gateway = null.
    'map' => [
        'pay.redai.pl'          => ['module' => 'gateway',    'kind' => null,       'db' => 'nfc_pay',   'gateway_api_key' => null],
        'shop2.redai.pl'        => ['module' => 'storefront', 'kind' => 'products', 'db' => 'nfc_shop2', 'gateway_api_key' => env('GATEWAY_API_KEY_PRODUCTS')],
        'shop1.redai.pl'        => ['module' => 'storefront', 'kind' => 'church',   'db' => 'nfc_shop1', 'gateway_api_key' => env('GATEWAY_API_KEY_CHURCH')],

        // Produkcja (please-support-me.com) — nowy serwer.
        'please-support-me.com'       => ['module' => 'storefront', 'kind' => 'church',   'db' => 'nfc_shop1', 'gateway_api_key' => env('GATEWAY_API_KEY_CHURCH')],
        'pay.please-support-me.com'   => ['module' => 'gateway',    'kind' => null,       'db' => 'nfc_pay',   'gateway_api_key' => null],
        'shop2.please-support-me.com' => ['module' => 'storefront', 'kind' => 'products', 'db' => 'nfc_shop2', 'gateway_api_key' => env('GATEWAY_API_KEY_PRODUCTS')],
    ],

    // Fallback dla CLI / nieznanego hosta; nadpisywalny przez TENANT
    // (np. `TENANT=shop1.redai.pl php artisan db:seed`).
    'default' => env('TENANT', 'pay.redai.pl'),

];
