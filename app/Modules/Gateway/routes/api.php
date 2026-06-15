<?php

use App\Modules\Gateway\Http\Controllers\Api\EventController;
use App\Modules\Gateway\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

// API bramki dla sklepów — auth nagłówkiem X-Api-Key
Route::prefix('v1')->middleware('apikey')->group(function () {
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/transactions/{uuid}', [TransactionController::class, 'show']);
    Route::post('/events', [EventController::class, 'store']);
});
