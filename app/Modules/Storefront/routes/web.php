<?php

use App\Modules\Storefront\Http\Controllers\CareersController;
use App\Modules\Storefront\Http\Controllers\ContactController;
use App\Modules\Storefront\Http\Controllers\GatewayWebhookController;
use App\Modules\Storefront\Http\Controllers\OrderReturnController;
use App\Modules\Storefront\Http\Controllers\Panel;
use App\Modules\Storefront\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

// Sklep
Route::get('/', [StorefrontController::class, 'index'])->name('home');
Route::get('/kategoria/{slug}', [StorefrontController::class, 'category'])->name('category');
Route::view('/regulamin', 'shop.regulamin')->name('regulamin');
Route::get('/t/{tag_uid}', [StorefrontController::class, 'tag'])->name('tag');
Route::get('/p/{slug}', [StorefrontController::class, 'show'])->name('product.show');
Route::post('/p/{slug}/kup', [StorefrontController::class, 'buy'])->name('product.buy');

// Praca (kariera) — publiczna lista stanowisk
Route::get('/praca', [CareersController::class, 'index'])->name('careers');

// Pojedyncza oferta pracy na osobnej podstronie.
Route::get('/praca/oferta/{position}', [CareersController::class, 'show'])->name('careers.show');

// Formularz aplikacji — spontaniczna (bez oferty). MUSI być przed trasą z {position}.
Route::get('/praca/aplikuj', [CareersController::class, 'applyForm'])->name('careers.apply.general');
Route::post('/praca/aplikuj', [CareersController::class, 'applyStore'])->name('careers.apply.general.store');

// Formularz aplikacji na konkretną ofertę
Route::get('/praca/{position}/aplikuj', [CareersController::class, 'applyForm'])->name('careers.apply');
Route::post('/praca/{position}/aplikuj', [CareersController::class, 'applyStore'])->name('careers.apply.store');

// Formularz kontaktowy
Route::get('/kontakt', [ContactController::class, 'show'])->name('contact.show');
Route::post('/kontakt', [ContactController::class, 'store'])->name('contact.store');

// Powrót z płatności — ekran "Możesz zabrać towar"
Route::get('/zwrot/{order}', [OrderReturnController::class, 'show'])->name('order.return');
Route::get('/zwrot/{order}/status', [OrderReturnController::class, 'status'])->name('order.status');

// Webhook bramki płatności
Route::post('/webhooks/gateway', [GatewayWebhookController::class, 'handle'])->name('webhooks.gateway');

// Panel sklepu
Route::prefix('panel')->name('panel.')->group(function () {
    Route::get('/login', [Panel\LoginController::class, 'show'])->name('login');
    Route::post('/login', [Panel\LoginController::class, 'login'])->name('login.post');
    Route::post('/logout', [Panel\LoginController::class, 'logout'])->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/', [Panel\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/products', [Panel\ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [Panel\ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [Panel\ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [Panel\ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [Panel\ProductController::class, 'update'])->name('products.update');
        Route::post('/products/{product}/toggle', [Panel\ProductController::class, 'toggle'])->name('products.toggle');
        Route::delete('/products/{product}/images/{imageId}', [Panel\ProductController::class, 'deleteImage'])->name('products.images.delete');
        Route::get('/products/{product}/stats', [Panel\ProductController::class, 'stats'])->name('products.stats');
        Route::post('/upload-editor-image', [Panel\ProductController::class, 'uploadEditorImage'])->name('products.editor-upload');

        // CRM parafii — szybka zmiana statusu i notatki (AJAX). Aliasy panel.parishes.*.
        Route::post('/parishes/{product}/status', [Panel\ProductController::class, 'status'])->name('parishes.status');
        Route::post('/parishes/{product}/notes', [Panel\ProductController::class, 'storeNote'])->name('parishes.notes.store');
        Route::delete('/parishes/{product}/notes/{note}', [Panel\ProductController::class, 'destroyNote'])->name('parishes.notes.destroy');

        // Kategorie wsparcia (sekcja „Kogo wspieramy?") — drzewo edytowalne
        Route::get('/categories', [Panel\CategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [Panel\CategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [Panel\CategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit', [Panel\CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{category}', [Panel\CategoryController::class, 'update'])->name('categories.update');
        Route::post('/categories/{category}/reorder', [Panel\CategoryController::class, 'reorder'])->name('categories.reorder');
        Route::delete('/categories/{category}', [Panel\CategoryController::class, 'destroy'])->name('categories.destroy');

        // Parafie do obdzwonienia (CRM — lista potencjalnych z OSM)
        Route::get('/potential-parishes', [Panel\PotentialParishController::class, 'index'])->name('potential-parishes.index');
        Route::post('/potential-parishes/{potentialParish}/status', [Panel\PotentialParishController::class, 'updateStatus'])->name('potential-parishes.status');

        // Mapa pokrycia (osadzony Leaflet) — licznik parafii per województwo z bazy.
        Route::get('/coverage', function () {
            $byVoivodeship = \App\Modules\Storefront\Models\PotentialParish::query()
                ->selectRaw('voivodeship, COUNT(*) as c')
                ->groupBy('voivodeship')->orderByDesc('c')->pluck('c', 'voivodeship');
            $total = \App\Modules\Storefront\Models\PotentialParish::count();

            return view('panel.coverage.map', compact('byVoivodeship', 'total'));
        })->name('coverage.map');

        // Handlowcy (CRM)
        Route::get('/salespeople', [Panel\SalespersonController::class, 'index'])->name('salespeople.index');
        Route::get('/salespeople/create', [Panel\SalespersonController::class, 'create'])->name('salespeople.create');
        Route::post('/salespeople', [Panel\SalespersonController::class, 'store'])->name('salespeople.store');
        Route::get('/salespeople/{salesperson}/edit', [Panel\SalespersonController::class, 'edit'])->name('salespeople.edit');
        Route::put('/salespeople/{salesperson}', [Panel\SalespersonController::class, 'update'])->name('salespeople.update');
        Route::delete('/salespeople/{salesperson}', [Panel\SalespersonController::class, 'destroy'])->name('salespeople.destroy');

        // Stanowiska pracy (sekcja „Praca")
        Route::get('/positions', [Panel\PositionController::class, 'index'])->name('positions.index');
        Route::get('/positions/create', [Panel\PositionController::class, 'create'])->name('positions.create');
        Route::post('/positions', [Panel\PositionController::class, 'store'])->name('positions.store');
        Route::get('/positions/{position}/edit', [Panel\PositionController::class, 'edit'])->name('positions.edit');
        Route::put('/positions/{position}', [Panel\PositionController::class, 'update'])->name('positions.update');
        Route::post('/positions/{position}/toggle', [Panel\PositionController::class, 'toggle'])->name('positions.toggle');
        Route::delete('/positions/{position}', [Panel\PositionController::class, 'destroy'])->name('positions.destroy');

        // Skrzynka wiadomości z formularza kontaktowego
        Route::get('/messages', [Panel\MessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/{message}', [Panel\MessageController::class, 'show'])->name('messages.show');
        Route::delete('/messages/{message}', [Panel\MessageController::class, 'destroy'])->name('messages.destroy');

        // Skrzynka zgłoszeń rekrutacyjnych (aplikacje na oferty pracy)
        Route::get('/applications', [Panel\ApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/{application}', [Panel\ApplicationController::class, 'show'])->name('applications.show');
        Route::get('/applications/{application}/cv', [Panel\ApplicationController::class, 'cv'])->name('applications.cv');
        Route::post('/applications/{application}/status', [Panel\ApplicationController::class, 'updateStatus'])->name('applications.status');
        Route::delete('/applications/{application}', [Panel\ApplicationController::class, 'destroy'])->name('applications.destroy');
    });
});
