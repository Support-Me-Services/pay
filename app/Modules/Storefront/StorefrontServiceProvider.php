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

        // Migracje ładowane bezwarunkowo — `migrate` z TENANT=shop1/shop2
        // utworzy tabele sklepu w wybranej bazie.
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        // Komendy CLI modułu (parishes:import) — moduł nie leży w app/Console.
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Modules\Storefront\Console\Commands\ImportParishes::class,
            ]);
        }

        // Alias zgodności: część widoków panelu odwołuje się do
        // \App\Services\ShopStatsService — klasa żyje w module Storefront.
        if (! class_exists(\App\Services\ShopStatsService::class, false)) {
            class_alias(Services\ShopStatsService::class, \App\Services\ShopStatsService::class);
        }
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
