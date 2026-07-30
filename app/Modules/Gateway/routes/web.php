<?php

use App\Modules\Gateway\Http\Controllers\LandingController;
use App\Modules\Gateway\Http\Controllers\Panel;
use Illuminate\Support\Facades\Route;

// UWAGA: trasy płatności klienta (/pay/*, /mockpay/*, /webhooks/payu) przeniesiono
// do routes/payment.php — są rejestrowane także na hostach sklepu, by klient
// pozostawał na domenie sklepu. Ten plik (web.php) trafia TYLKO na hosty bramki,
// bo landing '/' i panel kolidowałyby z trasami sklepu.

// Landing Page
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::post('/lead', [LandingController::class, 'storeLead'])->name('lead.store');

Route::get('/internal/activation-status', [App\Modules\Gateway\Http\Controllers\ActivationStatusController::class, 'show'])->name('activation.status');

// Panel bramki
Route::prefix('panel')->name('panel.')->group(function () {
    Route::get('/login', [Panel\LoginController::class, 'show'])->name('login');
    Route::post('/login', [Panel\LoginController::class, 'login'])->name('login.post');
    Route::post('/logout', [Panel\LoginController::class, 'logout'])->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/', [Panel\DashboardController::class, 'index'])->name('dashboard');

        // Zmiana hasła zalogowanego konta.
        Route::get('/password', [Panel\PasswordController::class, 'edit'])->name('password.edit');
        Route::put('/password', [Panel\PasswordController::class, 'update'])->name('password.update');

        Route::get('/shops', [Panel\ShopController::class, 'index'])->name('shops.index');
        Route::get('/shops/create', [Panel\ShopController::class, 'create'])->name('shops.create');
        Route::post('/shops', [Panel\ShopController::class, 'store'])->name('shops.store');
        Route::get('/shops/{shop}/edit', [Panel\ShopController::class, 'edit'])->name('shops.edit');
        Route::put('/shops/{shop}', [Panel\ShopController::class, 'update'])->name('shops.update');

        Route::get('/shops/{shop}/tags', [Panel\TagController::class, 'index'])->name('tags.index');
        Route::get('/shops/{shop}/tags/create', [Panel\TagController::class, 'create'])->name('tags.create');
        Route::post('/shops/{shop}/tags', [Panel\TagController::class, 'store'])->name('tags.store');
        Route::get('/shops/{shop}/tags/{tag}/edit', [Panel\TagController::class, 'edit'])->name('tags.edit');
        Route::put('/shops/{shop}/tags/{tag}', [Panel\TagController::class, 'update'])->name('tags.update');

        Route::get('/stats', [Panel\StatsController::class, 'index'])->name('stats');

        Route::get('/leads', [Panel\LeadController::class, 'index'])->name('leads');
        Route::get('/leads/export', [Panel\LeadController::class, 'exportCsv'])->name('leads.export');

        Route::get('/antitheft', [Panel\AntiTheftController::class, 'index'])->name('antitheft');
    });
});
