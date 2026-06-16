<?php

return [

    // Identyfikator instancji sklepu (shop1 | shop2) — różni się tylko w .env
    'id' => env('SHOP_ID', 'shop1'),
    'name' => env('SHOP_NAME', 'Sklep Demo'),

    // classic — redirect na hostowaną stronę płatności bramki
    // app2app — wymuszony BLIK / aplikacja banku
    'payment_mode' => env('PAYMENT_MODE', 'classic'),

    // Bramka płatności (pay.redai.pl)
    'gateway_url' => rtrim(env('GATEWAY_URL', 'https://pay.redai.pl'), '/'),
    'gateway_api_key' => env('GATEWAY_API_KEY', ''),

    // Adres e-mail, na który trafiają zgłoszenia rekrutacyjne (z CV w załączniku).
    // Konfigurowalny przez .env: CAREERS_EMAIL=...
    'careers_email' => env('CAREERS_EMAIL', 'office@please-support-me.com'),

    // Sklep firmowy (gadżety charytatywne) — statyczna lista produktów.
    // Cena w groszach (liczona serwerowo; klient nie przesyła ceny).
    'merch' => [
        ['slug' => 'kubek-supportme',    'name' => 'Kubek SupportMe',    'price' => 1000, 'image' => 'img/sklep/kubek.jpg'],
        ['slug' => 'koszulka-supportme', 'name' => 'Koszulka SupportMe', 'price' => 3000, 'image' => 'img/sklep/koszulka.jpg'],
        ['slug' => 'pin-supportme',      'name' => 'Pin SupportMe',      'price' => 1000, 'image' => 'img/sklep/pin.jpg'],
        ['slug' => 'brelok-supportme',   'name' => 'Brelok SupportMe',   'price' => 1000, 'image' => 'img/sklep/brelok.jpg'],
    ],

];
