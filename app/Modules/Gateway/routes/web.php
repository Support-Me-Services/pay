<?php

use App\Modules\Gateway\Http\Controllers\LandingController;
use App\Modules\Gateway\Http\Controllers\MockPaymentController;
use App\Modules\Gateway\Http\Controllers\PaymentController;
use App\Modules\Gateway\Http\Controllers\Panel;
use App\Modules\Gateway\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Landing Page
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::post('/lead', [LandingController::class, 'storeLead'])->name('lead.store');

// Płatność (klient)
Route::get('/pay/{uuid}', [PaymentController::class, 'show'])->name('pay.show');
Route::post('/pay/{uuid}/blik', [PaymentController::class, 'blik'])->name('pay.blik');
Route::post('/pay/{uuid}/bank', [PaymentController::class, 'bank'])->name('pay.bank');
Route::get('/pay/{uuid}/status', [PaymentController::class, 'status'])->name('pay.status');
Route::get('/pay/{uuid}/return', [PaymentController::class, 'return'])->name('pay.return');

// MockProvider — hostowane strony płatności
Route::get('/mockpay/{uuid}', [MockPaymentController::class, 'show'])->name('mockpay.show');
Route::post('/mockpay/{uuid}/confirm', [MockPaymentController::class, 'confirm'])->name('mockpay.confirm');
Route::post('/mockpay/{uuid}/fail', [MockPaymentController::class, 'fail'])->name('mockpay.fail');

// Webhook PayU
Route::post('/webhooks/payu', [WebhookController::class, 'payu'])->name('webhooks.payu');
Route::get('/internal/activation-status', [App\Modules\Gateway\Http\Controllers\ActivationStatusController::class, 'show'])->name('activation.status');

// Panel bramki
Route::prefix('panel')->name('panel.')->group(function () {
    Route::get('/login', [Panel\LoginController::class, 'show'])->name('login');
    Route::post('/login', [Panel\LoginController::class, 'login'])->name('login.post');
    Route::post('/logout', [Panel\LoginController::class, 'logout'])->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/', [Panel\DashboardController::class, 'index'])->name('dashboard');

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
