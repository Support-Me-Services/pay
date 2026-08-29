<?php

use App\Modules\Storefront\Http\Controllers\BeneficiariesController;
use App\Modules\Storefront\Http\Controllers\CareersController;
use App\Modules\Storefront\Http\Controllers\CartController;
use App\Modules\Storefront\Http\Controllers\CompanyStoreController;
use App\Modules\Storefront\Http\Controllers\GatewayWebhookController;
use App\Modules\Storefront\Http\Controllers\OrderReturnController;
use App\Modules\Storefront\Http\Controllers\Panel;
use App\Modules\Storefront\Http\Controllers\StorefrontController;
use App\Modules\Storefront\Http\Controllers\UserBeneficiariesController;
use App\Modules\Storefront\Http\Controllers\UserCareersController;
use App\Modules\Storefront\Http\Controllers\UserShopController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Hashowany URL statycznego arkusza CSS z /public (cache-bust przeglądarki).
// Migracja React/Inertia: strony statyczne dokładają własny CSS przez prop `css`.
$css = fn (string $file) => asset("css/{$file}") . '?v=' . substr(md5_file(public_path("css/{$file}")), 0, 10);

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

// Podstrona „Wspieramy" per-konto (odpowiednik globalnej /beneficiaries).
Route::get('/people/{handle}/wspieramy', [UserBeneficiariesController::class, 'index'])->name('user.beneficiaries');

// „Praca" per-konto (odpowiednik globalnego /praca).
Route::get('/people/{handle}/praca', [UserCareersController::class, 'index'])->name('user.careers');
Route::get('/people/{handle}/praca/oferta/{position}', [UserCareersController::class, 'show'])->name('user.careers.show');
Route::get('/people/{handle}/praca/aplikuj', [UserCareersController::class, 'applyForm'])->name('user.careers.apply.general');
Route::post('/people/{handle}/praca/aplikuj', [UserCareersController::class, 'applyStore'])->middleware('throttle:careers-apply')->name('user.careers.apply.general.store');
Route::get('/people/{handle}/praca/{position}/aplikuj', [UserCareersController::class, 'applyForm'])->name('user.careers.apply');
Route::post('/people/{handle}/praca/{position}/aplikuj', [UserCareersController::class, 'applyStore'])->middleware('throttle:careers-apply')->name('user.careers.apply.store');

// Podstrona „Wspieramy" — węzły edytowalne w panelu
Route::get('/beneficiaries', [BeneficiariesController::class, 'index'])->name('beneficiaries');

Route::get('/regulamin', fn () => Inertia::render('Storefront/Regulamin', [
    'css' => $css('subpages.css'),
    'pageTitle' => 'Regulamin — ' . config('shop.name'),
    'pageDescription' => 'Regulamin serwisu internetowego i przekazywania darowizn Fundacji Support Me Haven & Heaven — zasady, platnosci, reklamacje i ochrona danych.',
]))->name('regulamin');
Route::get('/dziekujemy', fn () => Inertia::render('Storefront/Dziekujemy', [
    'css' => $css('subpages.css'),
    'pageTitle' => 'Dziękujemy — ' . config('shop.name'),
    'pageDescription' => 'Dziękujemy za Twoje wsparcie i zaufanie. Twoja wiadomość do nas dotarła — odezwiemy się najszybciej, jak to możliwe.',
]))->name('thanks');

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
Route::get('/fundacje', fn () => Inertia::render('Storefront/Fundacje', [
    'pageTitle' => 'Fundacje — SupportME',
    'pageDescription' => 'Support Me pomaga fundacjom i organizacjom społecznym zwiększać skuteczność pozyskiwania darowizn dzięki tagom NFC i nowoczesnym płatnościom bezgotówkowym.',
]))->name('fundacje');
Route::get('/szkoly', fn () => Inertia::render('Storefront/Szkoly', [
    'pageTitle' => 'Szkoły — SupportME',
    'pageDescription' => 'SupportME rozwiązuje problem braku gotówki podczas szkolnych pikników i wydarzeń. Dzięki technologii NFC uczniowie i szkoły skutecznie zbierają środki bezgotówkowo.',
]))->name('szkoly');
Route::get('/mecenasi/lokalny-rolnik', fn () => Inertia::render('Storefront/MecenasiLokalnyRolnik', [
    'pageTitle' => 'Mecenas: LokalnyRolnik — SupportME',
    'pageDescription' => 'LokalnyRolnik — Mecenas Parafii Żbików. Polska platforma e-commerce łącząca klientów z lokalnymi rolnikami i producentami żywności wspiera, poprzez SupportME, cele parafii.',
]))->name('mecenasi.lokalnyrolnik');
// Inwestorzy i akcjonariusze (statyczna strona marketingowa wg Figmy „Inwestorzy")
Route::get('/inwestorzy', fn () => Inertia::render('Storefront/Inwestorzy', [
    'css' => $css('inwestorzy.css'),
    'pageTitle' => 'Inwestorzy i akcjonariusze — ' . config('shop.name'),
    'pageDescription' => 'Inwestorzy i akcjonariusze SupportMe — wierzymy, że kapitał może służyć dobru.',
]))->name('investors');

Route::get('/t/{tag_uid}', [StorefrontController::class, 'tag'])->name('tag');

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

    // Samodzielne zakładanie konta sklepu (konto + własny handle od razu).
    Route::get('/register', [Panel\RegisterController::class, 'show'])->name('register');
    Route::post('/register', [Panel\RegisterController::class, 'store'])
        ->middleware('throttle:panel-register')->name('register.post');

    Route::middleware('auth')->group(function () {
        // Dashboard usunięty — wejście na /panel renderuje wprost „Moje
        // organizacje" (jedyna trasa panelu gwarantowanie dostępna niezależnie
        // od tego, które sekcje organizacja ma dziś włączone). WAŻNE: to musi
        // być bezpośrednie wywołanie kontrolera, nie redirect() na
        // panel.organizations.index — redirect-przez-redirect gubi flash
        // (`->with('success', ...)`) ustawiony przez wywołujących (Register,
        // Login, OrganizationsController::switchTo), bo sesyjny flash
        // przeżywa tylko JEDEN dodatkowy hop.
        Route::get('/', [Panel\OrganizationsController::class, 'index'])->name('dashboard');

        // Zmiana hasła zalogowanego konta.
        Route::get('/password', [Panel\PasswordController::class, 'edit'])->name('password.edit');
        Route::put('/password', [Panel\PasswordController::class, 'update'])->name('password.update');

        Route::post('/upload-editor-image', [Panel\BeneficiaryNodeController::class, 'uploadEditorImage'])->name('editor-upload');

        // Podstrona „Wspieramy" — węzły (nagłówek + grafika + tekst); kolejność drag&drop
        Route::get('/beneficiaries', [Panel\BeneficiaryNodeController::class, 'index'])->name('beneficiaries.index');
        Route::post('/beneficiaries', [Panel\BeneficiaryNodeController::class, 'store'])->name('beneficiaries.store');
        Route::post('/beneficiaries/reorder', [Panel\BeneficiaryNodeController::class, 'reorder'])->name('beneficiaries.reorder');
        Route::post('/beneficiaries/{node}', [Panel\BeneficiaryNodeController::class, 'update'])->name('beneficiaries.update');
        Route::delete('/beneficiaries/{node}', [Panel\BeneficiaryNodeController::class, 'destroy'])->name('beneficiaries.destroy');

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

        // Skrzynka zgłoszeń rekrutacyjnych (aplikacje na oferty pracy)
        Route::get('/applications', [Panel\ApplicationController::class, 'index'])->name('applications.index');
        // Baza kandydatów z AKTYWNĄ zgodą na przyszłe rekrutacje.
        // MUSI być przed /applications/{application}, inaczej „consents" trafi jako {application}.
        Route::get('/applications/consents', [Panel\ApplicationController::class, 'consents'])->name('applications.consents');
        Route::get('/applications/{application}', [Panel\ApplicationController::class, 'show'])->name('applications.show');
        Route::get('/applications/{application}/cv', [Panel\ApplicationController::class, 'cv'])->name('applications.cv');
        Route::post('/applications/{application}/status', [Panel\ApplicationController::class, 'updateStatus'])->name('applications.status');
        Route::delete('/applications/{application}', [Panel\ApplicationController::class, 'destroy'])->name('applications.destroy');

        // Super-user: globalny podgląd/nadzór nad WSZYSTKIMI organizacjami
        // (bezpiecznik, bramkowane is_admin w kontrolerze) — nie główny mechanizm.
        Route::get('/uzytkownicy', [Panel\UsersController::class, 'index'])->name('users.index');
        Route::post('/uzytkownicy/{organization}/sekcje', [Panel\UsersController::class, 'updateSections'])->name('users.sections');
        Route::post('/uzytkownicy/{organization}/wlasciciel', [Panel\UsersController::class, 'updateOwner'])->name('users.owner');

        // Moje organizacje — lista/przełącznik aktywnej, zakładanie nowej,
        // self-service włącz/wyłącz sekcji AKTYWNEJ organizacji.
        Route::get('/organizacje', [Panel\OrganizationsController::class, 'index'])->name('organizations.index');
        Route::post('/organizacje', [Panel\OrganizationsController::class, 'store'])->name('organizations.store');
        Route::post('/organizacje/przelacz', [Panel\OrganizationsController::class, 'switchTo'])->name('organizations.switch');
        Route::get('/organizacje/ustawienia', [Panel\OrganizationsController::class, 'settings'])->name('organizations.settings');
        Route::post('/organizacje/ustawienia', [Panel\OrganizationsController::class, 'updateSettings'])->name('organizations.settings.update');
    });
});
