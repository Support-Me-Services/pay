<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Dyspozytor seedów zależny od roli wdrożenia (config/platform.php):
 *   - gateway                    => GatewaySeeder
 *   - storefront + products      => ProductsSeeder
 *   - storefront + church (Taca) => ChurchSeeder
 *
 * Dzięki temu `php artisan db:seed` działa dla każdej roli bez modyfikacji.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (config('platform.role') === 'gateway') {
            $this->call(GatewaySeeder::class);

            return;
        }

        // storefront
        $this->call(config('platform.shop_kind') === 'church'
            ? ChurchSeeder::class
            : ProductsSeeder::class);
    }
}
