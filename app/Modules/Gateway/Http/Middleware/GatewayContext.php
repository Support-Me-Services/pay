<?php

namespace App\Modules\Gateway\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kontekst bramki na hoście sklepu.
 *
 * Trasy płatności (/pay/*) rejestrujemy także na domenach sklepu, by klient
 * nie był przekierowywany na subdomenę pay.*. Na takim hoście ResolveTenant
 * ustawił już ścieżki widoków sklepu — tutaj doklejamy ścieżkę widoków bramki,
 * aby view('payment.app2app') / view('payment.error') itd. rozwiązały się do
 * widoków bramki w obrębie TEGO żądania.
 *
 * Bazy nie przełączamy — modele bramki są przypięte do połączenia 'gateway'
 * (czyt. nfc_pay), więc dane bramki są dostępne niezależnie od domyślnej bazy hosta.
 */
class GatewayContext
{
    public function handle(Request $request, Closure $next): Response
    {
        app('view')->getFinder()->prependLocation(resource_path('views/gateway'));

        return $next($request);
    }
}
