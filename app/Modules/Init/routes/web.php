<?php

use App\Modules\Init\Http\Controllers\InitController;
use App\Modules\Init\Http\Controllers\Panel\InitCodeController;
use App\Modules\Init\Http\Controllers\Panel\MyInitCodeController;
use Illuminate\Support\Facades\Route;

// Inicjalizacja kontaktu — tag NFC / kod QR. Ten sam kod (ten sam uuid) pod
// obydwoma adresami — kanał tylko informacyjnie; patrz InitController::show().
Route::get('/init/tag/{uuid}', [InitController::class, 'show'])->name('init.tag');
Route::get('/init/qr/{uuid}', [InitController::class, 'show'])->name('init.qr');

// Panel: osobny blok "panel." współistniejący z panelem Storefrontu na tym
// samym hoście (ta sama technika, którą Gateway już stosuje dla własnych
// tras płatności).
Route::prefix('panel')->name('panel.')->middleware('auth')->group(function () {
    // Tagi/QR organizacji — cel: konkretny produkt. Zarządzane w panelu
    // aktywnej organizacji.
    Route::get('/init-codes', [InitCodeController::class, 'index'])->name('init-codes.index');
    Route::get('/init-codes/create', [InitCodeController::class, 'create'])->name('init-codes.create');
    Route::post('/init-codes', [InitCodeController::class, 'store'])->name('init-codes.store');
    Route::get('/init-codes/{initCode}/edit', [InitCodeController::class, 'edit'])->name('init-codes.edit');
    Route::put('/init-codes/{initCode}', [InitCodeController::class, 'update'])->name('init-codes.update');
    Route::post('/init-codes/{initCode}/toggle', [InitCodeController::class, 'toggle'])->name('init-codes.toggle');
    Route::delete('/init-codes/{initCode}', [InitCodeController::class, 'destroy'])->name('init-codes.destroy');

    // Moje tagi — kody OSOBISTE (własność konta) — cel: cała lista zbiórek
    // jednej z własnych organizacji użytkownika. Bez gatingu canSee() —
    // to własność konta, nie organizacji.
    Route::get('/my-init-codes', [MyInitCodeController::class, 'index'])->name('my-init-codes.index');
    Route::get('/my-init-codes/create', [MyInitCodeController::class, 'create'])->name('my-init-codes.create');
    Route::post('/my-init-codes', [MyInitCodeController::class, 'store'])->name('my-init-codes.store');
    Route::get('/my-init-codes/{myInitCode}/edit', [MyInitCodeController::class, 'edit'])->name('my-init-codes.edit');
    Route::put('/my-init-codes/{myInitCode}', [MyInitCodeController::class, 'update'])->name('my-init-codes.update');
    Route::post('/my-init-codes/{myInitCode}/toggle', [MyInitCodeController::class, 'toggle'])->name('my-init-codes.toggle');
    Route::delete('/my-init-codes/{myInitCode}', [MyInitCodeController::class, 'destroy'])->name('my-init-codes.destroy');
});
