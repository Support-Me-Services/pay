<?php

namespace App\Providers;

use App\Routing\TenantUrlGenerator;
use App\Socialite\KeycloakProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Logika specyficzna dla modułu żyje w GatewayServiceProvider /
        // StorefrontServiceProvider (rejestrowanych w bootstrap/providers.php).

        // Podmiana generatora URL na świadomy hosta — przy kolizji nazw tras
        // między modułami (panel.login, home itp.) route() wybiera trasę
        // pasującą do domeny bieżącego żądania. Patrz App\Routing\TenantUrlGenerator.
        $this->app->extend('url', function ($url, $app) {
            $routes = $app['routes'];

            $generator = new TenantUrlGenerator(
                $routes,
                $app->rebinding('request', function ($app, $request) {
                    $app['url']->setRequest($request);
                }),
                $app['config']['app.asset_url']
            );

            $generator->setRequest($app['request']);

            $generator->setSessionResolver(fn () => $app['session'] ?? null);

            $generator->setKeyResolver(function () use ($app) {
                $config = $app->make('config');

                return [$config->get('app.key'), ...($config->get('app.previous_keys') ?? [])];
            });

            $app->rebinding('routes', function ($app, $routes) {
                $app['url']->setRoutes($routes);
            });

            return $generator;
        });
    }

    public function boot(): void
    {
        // Ochrona przed masowym wysyłaniem zgłoszeń rekrutacyjnych — każde
        // żądanie zapisuje plik CV, wpis w bazie i wysyła mail z załącznikiem.
        // Limit per IP: krótkofalowy (burst) + dzienny.
        RateLimiter::for('careers-apply', function (Request $request) {
            return [
                Limit::perMinute(10)->by($request->ip()),
                Limit::perDay(20)->by($request->ip()),
            ];
        });

        // Faza 6 — rejestracja sterownika Keycloaka dla Socialite. WŁASNA
        // podklasa (App\Socialite\KeycloakProvider), NIE gotowy listener
        // pakietu (SocialiteProviders\Keycloak\KeycloakExtendSocialite) —
        // ten wskazywałby na bazowy Provider::class, który nie rozróżnia
        // publicznego adresu Keycloaka (przeglądarka) od wewnętrznego
        // adresu klastra (wywołania serwer-serwer), patrz KeycloakProvider.
        Event::listen(SocialiteWasCalled::class, function (SocialiteWasCalled $event) {
            $event->extendSocialite('keycloak', KeycloakProvider::class);
        });
    }
}
