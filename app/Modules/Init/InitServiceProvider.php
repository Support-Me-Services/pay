<?php

namespace App\Modules\Init;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class InitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Init współdzieli hosty z modułem sklepu (module === 'storefront') —
        // to samo zdarzenie "inicjalizacji kontaktu" (tag NFC / kod QR) ma
        // żyć na tej samej domenie co reszta sklepu, bez własnego wpisu w
        // config/tenants.php.
        foreach ($this->storefrontHosts() as $host) {
            Route::domain($host)->middleware('web')->group(__DIR__.'/routes/web.php');
        }

        // Lokalny dev: fallback dla dostępu z innego urządzenia w tej samej
        // sieci (telefon po Wi-Fi pod adresem LAN) — istotne akurat tutaj,
        // bo testowanie kodu QR wprost wymaga telefonu. Tak samo jak
        // StorefrontServiceProvider, rejestrowany JAKO OSTATNI.
        if ($this->app->environment('local')) {
            Route::pattern('host', '.*');
            Route::domain('{host}')->middleware('web')->group(__DIR__.'/routes/web.php');
        }

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
