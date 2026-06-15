<?php

return [

    // Mapa: nazwa hosta (domena żądania) => tenant.
    //   module => gateway | storefront  (który moduł obsługuje hosta)
    //   kind   => products | church | null  (wariant sklepu; null dla bramki)
    //   db     => nazwa bazy MySQL (ten sam user nfc_pay ma dostęp do wszystkich)
    'map' => [
        'pay.redai.pl'          => ['module' => 'gateway',    'kind' => null,       'db' => 'nfc_pay'],
        'shop2.redai.pl'        => ['module' => 'storefront', 'kind' => 'products', 'db' => 'nfc_shop2'],
        'shop1.redai.pl'        => ['module' => 'storefront', 'kind' => 'church',   'db' => 'nfc_shop1'],
        'please-support-me.com' => ['module' => 'storefront', 'kind' => 'church',   'db' => 'nfc_shop1'],
    ],

    // Fallback dla CLI / nieznanego hosta; nadpisywalny przez TENANT
    // (np. `TENANT=shop1.redai.pl php artisan db:seed`).
    'default' => env('TENANT', 'pay.redai.pl'),

];
