<?php

use App\Modules\Storefront\Http\Controllers\BeneficiariesController;
use App\Modules\Storefront\Http\Controllers\CareersController;
use App\Modules\Storefront\Http\Controllers\CartController;
use App\Modules\Storefront\Http\Controllers\CompanyStoreController;
use App\Modules\Storefront\Http\Controllers\ContactController;
use App\Modules\Storefront\Http\Controllers\GatewayWebhookController;
use App\Modules\Storefront\Http\Controllers\OrderReturnController;
use App\Modules\Storefront\Http\Controllers\Panel;
use App\Modules\Storefront\Http\Controllers\StorefrontController;
use App\Modules\Storefront\Http\Controllers\UserShopController;
use Illuminate\Support\Facades\Route;

// Strona główna „/" — model darowiznowy (produkty konta głównego, kwota ≥ min).
Route::get('/', [CompanyStoreController::class, 'index'])->name('home');
// Landing „Technologia, która pomaga czynić dobro" — pod /main.
Route::get('/main', [StorefrontController::class, 'index'])->name('main');

// Darowizna z „/" — wpłata na wybraną kwotę (≥ min produktu).
Route::post('/sklep/kup/{slug}', [CompanyStoreController::class, 'purchase'])->name('shop.buy');

// Sklep per‑konto (stała cena + koszyk) pod /people/{handle}
Route::get('/people/{handle}', [UserShopController::class, 'index'])->name('user.shop');
Route::get('/people/{handle}/koszyk', [CartController::class, 'show'])->name('user.cart.show');
Route::post('/people/{handle}/koszyk/dodaj/{item}', [CartController::class, 'add'])->name('user.cart.add');
Route::post('/people/{handle}/koszyk/aktualizuj/{item}', [CartController::class, 'update'])->name('user.cart.update');
Route::post('/people/{handle}/koszyk/usun/{item}', [CartController::class, 'remove'])->name('user.cart.remove');
Route::post('/people/{handle}/koszyk/dostawa', [CartController::class, 'setShipping'])->name('user.cart.shipping');
Route::post('/people/{handle}/koszyk/kup', [CartController::class, 'checkout'])->name('user.cart.checkout');

// Podstrona „Wspieramy" — węzły edytowalne w panelu
Route::get('/beneficiaries', [BeneficiariesController::class, 'index'])->name('beneficiaries');

// Parafie (cyfrowa taca) — kategorie i strony produktów
Route::get('/kategoria/{slug}', [StorefrontController::class, 'category'])->name('category');
Route::view('/regulamin', 'shop.regulamin')->name('regulamin');
Route::view('/dziekujemy', 'shop.dziekujemy')->name('thanks');

// [Pilotaż migracji na React/Inertia] — strona dowodowa, nie rusza istniejących widoków.
Route::get('/react-pilot', function () {
    return inertia('Pilot', [
        'message' => 'Ta strona jest renderowana przez React (Inertia), a dane przychodzą z kontrolera Laravel.',
        'tenant' => config('tenants.default') ?: request()->getHost(),
        'items' => \App\Modules\Storefront\Models\ShopItem::query()
            ->orderBy('sort')->orderBy('id')->limit(5)->get()
            ->map(fn ($i) => ['id' => $i->id, 'name' => $i->name, 'price' => $i->pricePln().' zł'])
            ->values(),
    ]);
})->name('react.pilot');

// Podstrony segmentów (treść z Figmy)
Route::view('/fundacje', 'shop.fundacje')->name('fundacje');
Route::view('/parafie', 'shop.parafie')->name('parafie');
Route::view('/szkoly', 'shop.szkoly')->name('szkoly');
Route::view('/mecenasi/lokalny-rolnik', 'shop.mecenasi-lokalny-rolnik')->name('mecenasi.lokalnyrolnik');
// Inwestorzy i akcjonariusze (statyczna strona marketingowa wg Figmy „Inwestorzy")
Route::view('/inwestorzy', 'shop.inwestorzy')->name('investors');

// Dokumentacja projektu — przegląd wszystkich modułów i funkcji
Route::view('/docs', 'shop.docs')->name('docs');

// Samouczek — jak modyfikować podstrony z Claude (dostęp, Figma, prompty)
Route::view('/samouczek', 'shop.samouczek')->name('samouczek');
Route::get('/t/{tag_uid}', [StorefrontController::class, 'tag'])->name('tag');
Route::get('/p/{slug}', [StorefrontController::class, 'show'])->name('product.show');
Route::post('/p/{slug}/kup', [StorefrontController::class, 'buy'])->name('product.buy');

// Praca (kariera) — publiczna lista stanowisk
Route::get('/praca', [CareersController::class, 'index'])->name('careers');

// Pojedyncza oferta pracy na osobnej podstronie.
Route::get('/praca/oferta/{position}', [CareersController::class, 'show'])->name('careers.show');

// Formularz aplikacji — spontaniczna (bez oferty). MUSI być przed trasą z {position}.
Route::get('/praca/aplikuj', [CareersController::class, 'applyForm'])->name('careers.apply.general');
Route::post('/praca/aplikuj', [CareersController::class, 'applyStore'])->middleware('throttle:careers-apply')->name('careers.apply.general.store');

// Formularz aplikacji na konkretną ofertę
Route::get('/praca/{position}/aplikuj', [CareersController::class, 'applyForm'])->name('careers.apply');
Route::post('/praca/{position}/aplikuj', [CareersController::class, 'applyStore'])->middleware('throttle:careers-apply')->name('careers.apply.store');

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

        // Podstrona „Wspieramy" — węzły (nagłówek + grafika + tekst); kolejność drag&drop
        Route::get('/beneficiaries', [Panel\BeneficiaryNodeController::class, 'index'])->name('beneficiaries.index');
        Route::post('/beneficiaries', [Panel\BeneficiaryNodeController::class, 'store'])->name('beneficiaries.store');
        Route::post('/beneficiaries/reorder', [Panel\BeneficiaryNodeController::class, 'reorder'])->name('beneficiaries.reorder');
        Route::post('/beneficiaries/{node}', [Panel\BeneficiaryNodeController::class, 'update'])->name('beneficiaries.update');
        Route::delete('/beneficiaries/{node}', [Panel\BeneficiaryNodeController::class, 'destroy'])->name('beneficiaries.destroy');

        // Parafie do obdzwonienia (CRM — lista potencjalnych z OSM)
        Route::get('/potential-parishes', [Panel\PotentialParishController::class, 'index'])->name('potential-parishes.index');
        Route::post('/potential-parishes/{potentialParish}/status', [Panel\PotentialParishController::class, 'updateStatus'])->name('potential-parishes.status');

        // Mapa pokrycia (osadzony Leaflet) — licznik parafii per województwo z bazy.
        Route::get('/coverage', function () {
            $byVoivodeship = \App\Modules\Storefront\Models\PotentialParish::query()
                ->selectRaw('voivodeship, COUNT(*) as c')
                ->groupBy('voivodeship')->orderByDesc('c')->pluck('c', 'voivodeship');
            $total = \App\Modules\Storefront\Models\PotentialParish::count();
            // Liczniki per status — pasek statusów nad mapą (te same statusy co w liście CRM).
            $statusCounts = \App\Modules\Storefront\Models\PotentialParish::query()
                ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
            // Handlowcy do selecta w filtrach mapy i w popupie edycji markera.
            $salespeople = \App\Modules\Storefront\Models\Salesperson::orderBy('name')->get();

            return view('panel.coverage.map', compact('byVoivodeship', 'total', 'statusCounts', 'salespeople'));
        })->name('coverage.map');

        // Dane markerów do interaktywnej mapy (JSON, ładowane AJAX-em po starcie mapy).
        Route::get('/coverage/data', [Panel\PotentialParishController::class, 'coverageData'])->name('coverage.data');

        // Handlowcy (CRM)
        Route::get('/salespeople', [Panel\SalespersonController::class, 'index'])->name('salespeople.index');
        Route::get('/salespeople/create', [Panel\SalespersonController::class, 'create'])->name('salespeople.create');
        Route::post('/salespeople', [Panel\SalespersonController::class, 'store'])->name('salespeople.store');
        Route::get('/salespeople/{salesperson}/edit', [Panel\SalespersonController::class, 'edit'])->name('salespeople.edit');
        Route::put('/salespeople/{salesperson}', [Panel\SalespersonController::class, 'update'])->name('salespeople.update');
        Route::delete('/salespeople/{salesperson}', [Panel\SalespersonController::class, 'destroy'])->name('salespeople.destroy');

        // Produkty sklepu donacyjnego (NFC) — min. kwota, tag NFC, domyślny
        Route::get('/shop-items', [Panel\ShopItemController::class, 'index'])->name('shop-items.index');
        Route::get('/shop-items/create', [Panel\ShopItemController::class, 'create'])->name('shop-items.create');
        Route::post('/shop-items', [Panel\ShopItemController::class, 'store'])->name('shop-items.store');
        Route::get('/shop-items/{shopItem}/edit', [Panel\ShopItemController::class, 'edit'])->name('shop-items.edit');
        Route::put('/shop-items/{shopItem}', [Panel\ShopItemController::class, 'update'])->name('shop-items.update');
        Route::post('/shop-items/{shopItem}/toggle', [Panel\ShopItemController::class, 'toggle'])->name('shop-items.toggle');
        Route::delete('/shop-items/{shopItem}', [Panel\ShopItemController::class, 'destroy'])->name('shop-items.destroy');

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
