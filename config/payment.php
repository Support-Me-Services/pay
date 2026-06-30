<?php

return [

    // payu | mock — przełączane w .env (PAYMENT_PROVIDER)
    'provider' => env('PAYMENT_PROVIDER', 'mock'),

    // Poki PayU nie zatwierdzi sklepu: pomija platnosc i kieruje od razu na ekran podziekowania.
    'bypass' => (bool) env('PAYMENT_BYPASS', false),

    // PayU podpiete, ale powrot pomija weryfikacje statusu i idzie od razu na podziekowanie.
    'return_bypass' => (bool) env('PAYMENT_RETURN_BYPASS', false),

    'payu' => [
        // sandbox | production
        'env' => env('PAYU_ENV', 'sandbox'),
        'base_url' => env('PAYU_ENV', 'sandbox') === 'production'
            ? 'https://secure.payu.com'
            : 'https://secure.snd.payu.com',
        'merchant_id' => env('PAYU_MERCHANT_ID'),
        'pos_id' => env('PAYU_POS_ID'),
        'client_id' => env('PAYU_CLIENT_ID'),
        'client_secret' => env('PAYU_CLIENT_SECRET'),
        'second_key' => env('PAYU_SECOND_KEY'),
    ],

    // Drugi POS (nowy sklep "NFC Pay - sklep demo" / shop1.redai.pl) — używany
    // tylko do sprawdzania, czy PayU sprovisionował metody (sygnał aktywacji).
    'payu_newpos' => [
        'client_id' => env('PAYU_NEWPOS_CLIENT_ID'),
        'client_secret' => env('PAYU_NEWPOS_CLIENT_SECRET'),
    ],

    'activation_check_token' => env('ACTIVATION_CHECK_TOKEN'),

];
