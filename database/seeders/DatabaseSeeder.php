<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Dyspozytor seedów zależny od roli wdrożenia (config/platform.php):
 *   - gateway    => GatewaySeeder
 *   - storefront => tylko konto admina (dane demo Zbiórek/„O nas"/Pracy
 *                   pochodzą z własnych migracji, niezależnie od tego seedera)
 *
 * Dzięki temu `php artisan db:seed` działa dla każdej roli bez modyfikacji.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Konto do panelu na lokalnym dev (każda rola/tenant) — żeby na nowym
        // komputerze `php artisan migrate --seed` od razu dawało działające
        // logowanie do /panel/login. TYLKO APP_ENV=local — nigdy na staging/prod,
        // nawet gdyby ktoś tam ręcznie odpalił `db:seed`.
        if (app()->environment('local')) {
            User::updateOrCreate(
                ['email' => 'admin@local'],
                ['name' => 'Marcin Lula', 'handle' => 'lula-marcin', 'password' => Hash::make('admin123')]
            );
        }

        if (config('platform.role') === 'gateway') {
            $this->call(GatewaySeeder::class);
        }
    }
}
