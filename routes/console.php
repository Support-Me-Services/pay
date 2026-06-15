<?php

use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// W CLI nie ma hosta — rozwiąż tenant z config('tenants.default') (TENANT env),
// aby ustawić bazę (DB_DATABASE) i platform.role przed migracjami/seedami.
//   np. TENANT=shop1.redai.pl php artisan db:seed  => baza nfc_shop1, motyw church
$tenant = ResolveTenant::applyTenant(null);

// Harmonogram bramki — aktywny tylko gdy aktywnym tenantem CLI jest bramka.
// Komenda payu:reconcile rejestrowana jest przez moduł Gateway (Console\Commands).
if (($tenant['module'] ?? null) === 'gateway') {
    Schedule::command('payu:reconcile')->everyMinute();
}
