<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server Side Rendering (SSR)
    |--------------------------------------------------------------------------
    |
    | Renderowanie po stronie serwera przez osobny proces Node
    | (`node bootstrap/ssr/ssr.js`). URL wskazuje serwer SSR; lokalnie
    | (docker) to kontener `pay-ssr`, na prod `127.0.0.1:13714`.
    |
    */

    'ssr' => [
        'enabled' => true,
        'url' => env('INERTIA_SSR_URL', 'http://127.0.0.1:13714'),
    ],

];
