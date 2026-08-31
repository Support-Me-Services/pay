<?php

namespace App\Modules\Storefront;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class StorefrontServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Trasy sklepu rejestrowane są dla KAŻDEGO hosta z modułem 'storefront'.
        // Scope per domena izoluje je od bramki — jeden proces, wiele hostów.
        // Motyw (products|church) i baza przełączane są per żądanie przez ResolveTenant.
        foreach ($this->storefrontHosts() as $host) {
            Route::domain($host)->middleware('web')->group(__DIR__.'/routes/web.php');
        }

        // Lokalny dev: fallback dla dostępu z innego urządzenia w tej samej
        // sieci (telefon po Wi-Fi pod adresem LAN zamiast "localhost") —
        // bez tego Laravel nie dopasowuje ŻADNEJ trasy dla nieznanego hosta
        // (404, zanim ResolveTenant zdąży cokolwiek zmapować). Rejestrowany
        // JAKO OSTATNI, więc konkretne hosty (dopasowane wyżej) mają
        // pierwszeństwo — to tylko fallback dla reszty. Tylko w local.
        if ($this->app->environment('local')) {
            Route::pattern('host', '.*');
            Route::domain('{host}')->middleware('web')->group(__DIR__.'/routes/web.php');
        }

        // Migracje ładowane bezwarunkowo — `migrate` z TENANT=please-support-me.com
        // utworzy tabele sklepu w wybranej bazie.
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    /**
     * Hosty z mapy tenantów obsługiwane przez moduł sklepu.
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
