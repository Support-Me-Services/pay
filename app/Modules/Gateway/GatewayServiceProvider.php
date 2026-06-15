<?php

namespace App\Modules\Gateway;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class GatewayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Wybór operatora płatności — payu | mock (PAYMENT_PROVIDER w .env).
        $this->app->singleton(
            \App\Modules\Gateway\Payments\PaymentProviderInterface::class,
            function () {
                return match (config('payment.provider')) {
                    'payu' => new \App\Modules\Gateway\Payments\PayUProvider(),
                    default => new \App\Modules\Gateway\Payments\MockProvider(),
                };
            }
        );
    }

    public function boot(): void
    {
        // Hosty BRAMKI: pełny zestaw tras — landing/panel (web.php), trasy
        // płatności klienta (payment.php) oraz API dla sklepów (api.php).
        // Scope per domena izoluje je od storefrontu — jeden proces, wiele hostów.
        // Widoki/baza przełączane są per żądanie przez ResolveTenant.
        foreach ($this->gatewayHosts() as $host) {
            Route::domain($host)->middleware('web')->group(__DIR__.'/routes/web.php');
            Route::domain($host)->middleware('web')->group(__DIR__.'/routes/payment.php');
            Route::domain($host)->middleware('api')->prefix('api')->group(__DIR__.'/routes/api.php');
        }

        // Hosty SKLEPU: tylko trasy płatności klienta (payment.php) + API.
        // BEZ web.php — landing '/' i panel kolidowałyby z trasami sklepu.
        // Dzięki temu klient wpłacający zostaje na domenie sklepu (np.
        // please-support-me.com/pay/{uuid}). GatewayContext doklejania widoki bramki;
        // dane czytane są z nfc_pay przez połączenie 'gateway' (modele przypięte).
        foreach ($this->storefrontHosts() as $host) {
            Route::domain($host)
                ->middleware(['web', \App\Modules\Gateway\Http\Middleware\GatewayContext::class])
                ->group(__DIR__.'/routes/payment.php');
            Route::domain($host)->middleware('api')->prefix('api')->group(__DIR__.'/routes/api.php');
        }

        // Migracje ładowane bezwarunkowo — `migrate` z TENANT=pay.redai.pl
        // utworzy tabele bramki w wybranej bazie.
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        // Komenda harmonogramu (payu:reconcile) — moduł nie leży w app/Console.
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Modules\Gateway\Console\Commands\ReconcilePendingTransactions::class,
            ]);
        }
    }

    /**
     * Hosty z mapy tenantów obsługiwane przez moduł bramki.
     *
     * @return list<string>
     */
    private function gatewayHosts(): array
    {
        return array_keys(array_filter(
            config('tenants.map', []),
            fn (array $t) => ($t['module'] ?? null) === 'gateway'
        ));
    }

    /**
     * Hosty z mapy tenantów obsługiwane przez moduł sklepu — tu rejestrujemy
     * trasy płatności bramki, by klient zostawał na domenie sklepu.
     *
     * @return list<string>
     */
    private function storefrontHosts(): array
    {
        return array_keys(array_filter(
            config('tenants.map', []),
            fn (array $t) => ($t['module'] ?? null) === 'storefront'
        ));
    }
}
