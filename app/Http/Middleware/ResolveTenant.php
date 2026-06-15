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
        static::applyTenant($request->getHost());

        return $next($request);
    }

    /**
     * Rozwiązuje tenant dla danego hosta i ustawia kontekst runtime:
     *  - config('platform.role' / 'platform.shop_kind'),
     *  - aktywną bazę MySQL (purge + reconnect),
     *  - singleton 'tenant' (czytany przez providery/widoki),
     *  - ścieżki widoków modułu (świeży prepend na każde żądanie).
     *
     * @param  string|null  $host  Host żądania; null => fallback z config('tenants.default').
     * @return array{module:string,kind:?string,db:string,host:string}
     */
    public static function applyTenant(?string $host = null): array
    {
        $map = config('tenants.map');

        // Brak/nieznany host => domyślny tenant (CLI lub nieobsługiwana domena).
        if ($host === null || ! isset($map[$host])) {
            $host = config('tenants.default');
        }

        $tenant = $map[$host] ?? reset($map);
        $tenant['host'] = $host;

        // 1) Kontekst platformy — sterownik dla providerów modułów i widoków.
        config([
            'platform.role'      => $tenant['module'],
            'platform.shop_kind' => $tenant['kind'],
        ]);

        // 1a) Klucz API bramki per-host — shop1 i shop2 mają różne klucze,
        //     więc w trybie multi-host musi być ustawiany z mapy tenanta.
        if (! empty($tenant['gateway_api_key'])) {
            config(['shop.gateway_api_key' => $tenant['gateway_api_key']]);
        }

        // 2) Przełączenie bazy danych — MUSI nastąpić przed jakimkolwiek
        //    odczytem DB/sesji (dlatego middleware jest pierwszy w grupie).
        if (config('database.connections.mysql.database') !== $tenant['db']) {
            config(['database.connections.mysql.database' => $tenant['db']]);
            app('db')->purge('mysql');
        }

        // 3) Singleton tenanta — dostępny przez app('tenant') w całym żądaniu.
        app()->instance('tenant', $tenant);

        // 4) Ścieżki widoków per żądanie (jeden proces obsługuje wiele hostów).
        static::applyViewPaths($tenant);

        return $tenant;
    }

    /**
     * Prepend ścieżek widoków dla rozwiązanego tenanta — świeżo na każde
     * żądanie, by hosty nie „przeciekały” ścieżkami między sobą.
     *
     * @param  array{module:string,kind:?string}  $tenant
     */
    public static function applyViewPaths(array $tenant): void
    {
        $finder = app('view')->getFinder();

        // Reset zakumulowanych prependów z poprzedniego żądania.
        $finder->setPaths(config('view.paths'));
        $finder->flush();

        if ($tenant['module'] === 'gateway') {
            // Widoki bramki: view('landing'), view('panel.login') itd.
            $finder->prependLocation(resource_path('views/gateway'));
        } else {
            // Storefront: najpierw motyw (products|church), potem wspólne.
            $theme = $tenant['kind'] === 'church' ? 'church' : 'products';
            $finder->prependLocation(resource_path('views/storefront/common'));
            $finder->prependLocation(resource_path('views/storefront/'.$theme));
        }
    }
}
