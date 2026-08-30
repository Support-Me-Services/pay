<?php

use App\Http\Middleware\HandleInertiaRequests;
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

        // Inertia (React): dzieli dane i obsługuje odpowiedzi X-Inertia.
        // Na końcu grupy web — ResolveTenant (baza/tenant) musi zadziałać wcześniej.
        $middleware->appendToGroup('web', HandleInertiaRequests::class);

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
        // Wygasła sesja/token CSRF (np. formularz otwarty dłużej niż
        // session.lifetime, albo zakładka sprzed deployu) kończyła się
        // brzydkim, białym ekranem Laravela „419 | PAGE EXPIRED". Zamiast
        // tego wracamy tam, skąd przyszło żądanie, z komunikatem flash —
        // strona ładuje się na nowo ze świeżym tokenem, a komunikat trafia
        // w istniejący slot flash.error (patrz np. Storefront/Storefront.jsx).
        // UWAGA: Handler::prepareException() zamienia TokenMismatchException
        // na HttpException(419, ...) ZANIM sprawdzane są te renderery — więc
        // łapiemy HttpException i filtrujemy po kodzie (zwrot null dla
        // innych kodów oddaje obsługę z powrotem domyślnemu mechanizmowi).
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            return redirect()->back()
                ->with('error', 'Sesja wygasła — spróbuj ponownie.');
        });
    })->create();
