<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    /**
     * Pierwszy middleware w grupach web/api — wybiera tenant na podstawie
     * hosta żądania, ZANIM zadziała sesja/CSRF (te czytają z bazy danych).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = static::applyTenant($request->getHost());
        static::applyKeycloakClient($tenant, $request);

        return $next($request);
    }

    /**
     * Rozwiązuje tenant dla danego hosta i ustawia kontekst runtime:
     *  - config('platform.role' / 'platform.shop_kind'),
     *  - aktywną bazę MySQL (purge + reconnect),
     *  - singleton 'tenant' (czytany przez providery).
     *
     * @param  string|null  $host  Host żądania; null => fallback z config('tenants.default').
     * @return array{module:string,kind:?string,db:string,host:string}
     */
    public static function applyTenant(?string $host = null): array
    {
        $map = config('tenants.map');

        if ($host === null) {
            // Brak hosta (CLI) => domyślny tenant.
            $host = config('tenants.default');
        } elseif (! isset($map[$host])) {
            // Nieznany host: w środowisku lokalnym traktujemy to jak dostęp
            // do sklepu z innego urządzenia w tej samej sieci (telefon po
            // Wi-Fi pod adresem LAN, np. 192.168.x.x, zamiast "localhost") —
            // zamiast 404/złego tenanta, mapujemy na ten sam tenant co
            // localhost. Produkcja nigdy nie dostaje żądania na nieznany
            // host (DNS wskazuje tylko znane domeny), więc to bezpieczne
            // tylko w local. Poza local, nieznany host => domyślny tenant.
            $host = app()->environment('local') && isset($map['localhost'])
                ? 'localhost'
                : config('tenants.default');
        }

        $tenant = $map[$host] ?? reset($map);
        $tenant['host'] = $host;

        // 1) Kontekst platformy — sterownik dla providerów modułów i widoków.
        config([
            'platform.role'      => $tenant['module'],
            'platform.shop_kind' => $tenant['kind'],
        ]);

        // 1a) Klucz API bramki per-host — sklep (church) ma własny klucz,
        //     ustawiany z mapy tenanta.
        if (! empty($tenant['gateway_api_key'])) {
            config(['shop.gateway_api_key' => $tenant['gateway_api_key']]);
        }

        // 2) Przełączenie bazy danych — MUSI nastąpić przed jakimkolwiek
        //    odczytem DB/sesji (dlatego middleware jest pierwszy w grupie).
        //    Technika "ten sam użytkownik, wiele nazwanych baz" jest z natury
        //    specyficzna dla MySQL — testy (phpunit.xml: DB_CONNECTION=sqlite,
        //    DB_DATABASE=:memory:) NIE mają osobnych baz per tenant, więc
        //    przełączanie tu połamałoby połączenie sqlite (próba otwarcia
        //    pliku o nazwie tenanta, np. "nfc_shop1", zamiast :memory:).
        $conn = config('database.default');               // 'mysql' albo 'pgsql'
        if ($conn === 'mysql' && config("database.connections.{$conn}.database") !== $tenant['db']) {
            config(["database.connections.{$conn}.database" => $tenant['db']]);
            app('db')->purge($conn);
        }

        // 3) Singleton tenanta — dostępny przez app('tenant') w całym żądaniu.
        app()->instance('tenant', $tenant);

        return $tenant;
    }

    /**
     * Faza 6 — logowanie panelu idzie WYŁĄCZNIE przez Keycloak, osobny
     * klient per moduł (patrz config/tenants.php). Ustawia config Socialite
     * (`services.keycloak`) na klienta bieżącego tenanta, ten sam wzorzec co
     * `shop.gateway_api_key` wyżej — jeden choke point, KeycloakController
     * nie musi wiedzieć nic o tenantach.
     */
    private static function applyKeycloakClient(array $tenant, Request $request): void
    {
        if (empty($tenant['keycloak_client_id'])) {
            return;
        }

        config([
            'services.keycloak' => [
                'client_id' => $tenant['keycloak_client_id'],
                'client_secret' => $tenant['keycloak_client_secret'],
                'redirect' => $request->getSchemeAndHttpHost().'/panel/auth/callback',
                'base_url' => config('services.keycloak_base_url'),
                'internal_base_url' => config('services.keycloak_internal_base_url'),
                // "realms" (liczba mnoga) — tak nazywa ten klucz konfiguracji
                // pakiet socialiteproviders/keycloak, patrz Provider::getBaseUrl().
                'realms' => config('services.keycloak_realm'),
            ],
        ]);
    }
}
