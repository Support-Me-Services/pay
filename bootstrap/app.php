<?php

use App\Http\Middleware\ResolveTenant;
use App\Modules\Gateway\Http\Middleware\AuthenticateApiKey;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // Domyślne pliki tras są minimalne — faktyczne trasy modułów ładują
        // GatewayServiceProvider / StorefrontServiceProvider, scopowane przez
        // Route::domain() per host. Rejestracja web/api tutaj tworzy grupy
        // middleware ('web' i 'api'), z których korzystają providery modułów.
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ResolveTenant MUSI być pierwszy w grupach web/api — wybiera moduł,
        // motyw i bazę na podstawie hosta ZANIM zadziała sesja/CSRF (czytają z DB).
        $middleware->prependToGroup('web', ResolveTenant::class);
        $middleware->prependToGroup('api', ResolveTenant::class);

        // Alias używany przez trasy API bramki (X-Api-Key).
        $middleware->alias([
            'apikey' => AuthenticateApiKey::class,
        ]);

        // Webhooki przychodzą spoza przeglądarki — bez tokena CSRF.
        //   - bramka: webhooks/payu, mockpay/*
        //   - sklep:  webhooks/gateway
        $middleware->validateCsrfTokens(except: [
            'webhooks/payu',
            'mockpay/*',
            'webhooks/gateway',
        ]);

        $middleware->redirectGuestsTo('/panel/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
