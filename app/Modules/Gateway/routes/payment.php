<?php

use App\Modules\Gateway\Http\Controllers\MockPaymentController;
use App\Modules\Gateway\Http\Controllers\PaymentController;
use App\Modules\Gateway\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Trasy płatności skierowane do klienta. Rejestrowane są zarówno na hostach
// bramki, jak i na hostach sklepu (z middleware GatewayContext), aby klient
// dokonujący wpłaty pozostawał na domenie sklepu (np. please-support-me.com/pay/{uuid})
// zamiast być przekierowywanym na subdomenę pay.*. Modele bramki czytają nfc_pay
// przez połączenie 'gateway', więc działa to niezależnie od domyślnej bazy hosta.
// Nazwy tras pozostają bez zmian (pay.* / mockpay.* / webhooks.payu) —
// TenantUrlGenerator rozwiązuje właściwy host przy kolizji nazw.

// Płatność (klient)
Route::get('/pay/{uuid}', [PaymentController::class, 'show'])->name('pay.show');
Route::post('/pay/{uuid}/blik', [PaymentController::class, 'blik'])->name('pay.blik');
Route::post('/pay/{uuid}/bank', [PaymentController::class, 'bank'])->name('pay.bank');
Route::post('/pay/{uuid}/online', [PaymentController::class, 'online'])->name('pay.online');
Route::get('/pay/{uuid}/status', [PaymentController::class, 'status'])->name('pay.status');
Route::get('/pay/{uuid}/return', [PaymentController::class, 'return'])->name('pay.return');

// MockProvider — hostowane strony płatności
Route::get('/mockpay/{uuid}', [MockPaymentController::class, 'show'])->name('mockpay.show');
Route::post('/mockpay/{uuid}/confirm', [MockPaymentController::class, 'confirm'])->name('mockpay.confirm');
Route::post('/mockpay/{uuid}/fail', [MockPaymentController::class, 'fail'])->name('mockpay.fail');

// Webhook PayU
Route::post('/webhooks/payu', [WebhookController::class, 'payu'])->name('webhooks.payu');
