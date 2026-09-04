<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Faza 6 — bazowe ustawienia Keycloaka wspólne dla obu klientów panelu
    // (client_id/secret/redirect są per-tenant, dokładane przez
    // ResolveTenant::applyKeycloakClient() na "services.keycloak").
    // Keycloak 17+ nie ma już prefiksu /auth w URL-ach — base_url to
    // sam adres serwera, BEZ /realms/{realm}.
    'keycloak_base_url' => env('KEYCLOAK_BASE_URL', 'http://localhost:8180'),
    // Adres, pod którym TEN kontener/pod faktycznie dobija się do Keycloaka
    // (wywołania serwer-serwer: token, userinfo) — w Kubernetesie/Dockerze
    // to NIE to samo co keycloak_base_url (przeglądarka widzi
    // localhost:8180, ale "localhost" wewnątrz poda Laravela to jego WŁASNY
    // loopback, nie kontener Keycloaka). Dokładnie ten sam wzorzec co
    // jwk-set-uri vs issuer w api-gateway/SecurityConfig.kt (Faza 3). Patrz
    // App\Socialite\KeycloakProvider.
    'keycloak_internal_base_url' => env('KEYCLOAK_INTERNAL_BASE_URL', 'http://localhost:8180'),
    // Klucz "realms" (liczba mnoga!) — tego dokładnie oczekuje pakiet
    // socialiteproviders/keycloak (Provider::getBaseUrl()), nie "realm".
    'keycloak_realm' => env('KEYCLOAK_REALM', 'pay'),

];
