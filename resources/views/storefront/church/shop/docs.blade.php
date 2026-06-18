@extends('layouts.landing')

@section('title', 'Dokumentacja techniczna — SupportME')
@section('meta-description', 'Pełna dokumentacja techniczna platformy SupportME — moduł po module: trasy, metody, pola, tabele i przepływy.')

@push('head')
<link rel="stylesheet" href="{{ asset('css/docs.css') }}?v={{ substr(md5_file(public_path('css/docs.css')), 0, 10) }}">
@endpush

@section('content')
@php
    $stats = [
        ['n' => '2',       'l' => 'moduły aplikacji'],
        ['n' => '32',      'l' => 'kontrolery'],
        ['n' => '19',      'l' => 'modele danych'],
        ['n' => '~25',     'l' => 'tabel w bazach'],
        ['n' => '69',      'l' => 'widoków (Blade)'],
        ['n' => '109',     'l' => 'tras (web · API · webhooki)'],
        ['n' => '~13 500', 'l' => 'linii kodu'],
        ['n' => '3',       'l' => 'bazy danych (multi-tenant)'],
    ];
    $toc = [
        ['1','arch','Architektura multi-tenant'],
        ['2','platnosci','Warstwa płatności — przegląd'],
        ['3','sklep','Sklep donacyjny NFC (/)'],
        ['4','taca','Cyfrowa Taca — wejście NFC i parafie'],
        ['5','main','Strona główna /main'],
        ['6','inwestorzy','Inwestorzy i akcjonariusze'],
        ['7','rekrutacja','Rekrutacja i kariera'],
        ['8','kontakt','Kontakt'],
        ['9','regulamin','Regulamin'],
        ['10','powrot','Powrót z płatności'],
        ['11','client','Klient bramki i webhooki (sklep)'],
        ['12','gateway','Moduł Gateway — bramka PayU'],
        ['13','gw-api','Gateway: API i webhooki'],
        ['14','gw-panel','Gateway: panel zarządzania'],
        ['15','panel-auth','Panel: logowanie i dashboard'],
        ['16','panel-parafie','Panel: Parafie + CRM'],
        ['17','panel-kat','Panel: Kategorie'],
        ['18','panel-hand','Panel: Handlowcy'],
        ['19','panel-leady','Panel: Parafie do obdzwonienia + mapa'],
        ['20','panel-sklep','Panel: Sklep — produkty NFC'],
        ['21','panel-praca','Panel: Praca — stanowiska'],
        ['22','panel-apl','Panel: Aplikacje rekrutacyjne'],
        ['23','panel-wiad','Panel: Wiadomości'],
        ['24','analityka','Eventy i analityka'],
        ['25','baza','Pełny schemat bazy danych'],
        ['26','stack','Stack technologiczny i statystyki'],
    ];
@endphp

<section class="dc-hero">
    <h1>Dokumentacja techniczna platformy SupportME</h1>
    <p>Pełny opis systemu moduł po module: co robi każdy element, jakie udostępnia trasy, jakie metody zawiera, jakie ma pola i z których tabel korzysta oraz co dokładnie dzieje się podczas działania. Dokument wygenerowany na podstawie analizy kodu źródłowego.</p>
    <div class="dc-hero__meta">Aktualizacja: {{ now()->translatedFormat('j F Y') }} · Laravel 12 · PHP 8.2+ · architektura multi-tenant</div>
</section>

<div class="dc-wrap">

    <div class="dc-stats">
        @foreach($stats as $s)
            <div class="dc-stat"><div class="dc-stat__num">{{ $s['n'] }}</div><div class="dc-stat__label">{{ $s['l'] }}</div></div>
        @endforeach
    </div>

    <nav class="dc-toc" id="gora">
        <h2>Spis treści</h2>
        <ol>
            @foreach($toc as $t)
                <li><span class="dc-toc__n">{{ $t[0] }}.</span> <a href="#{{ $t[1] }}">{{ $t[2] }}</a></li>
            @endforeach
        </ol>
    </nav>

    <p class="dc-note"><strong>Legenda statusów:</strong>
        <span class="dc-badge dc-badge--on">Aktywny</span> — działa na produkcji ·
        <span class="dc-badge dc-badge--demo">Demo</span> — zbudowany, tryb pokazowy/testowy ·
        <span class="dc-badge dc-badge--off">Osobny tenant</span> — pod inną domeną ·
        <span class="dc-badge dc-badge--soon">Gotowy</span> — kod gotowy, włączany konfiguracją.
        Kwoty pieniężne w bazie przechowywane są w <strong>groszach</strong> (liczby całkowite).
    </p>

    {{-- ============ 1. ARCHITEKTURA ============ --}}
    <section class="dc-module" id="arch">
        <div class="dc-module__head"><span class="dc-module__num">1.</span><h2>Architektura multi-tenant</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">Jedna aplikacja Laravel obsługuje kilka niezależnych serwisów. To, który moduł i która baza danych obsłużą żądanie, zależy od <strong>hostu (domeny)</strong>. Rozstrzyga to middleware <code>ResolveTenant</code>, uruchamiany jako pierwszy — przed sesją i tokenem CSRF — bo musi ustawić bazę i kontekst zanim cokolwiek innego się wykona.</p>

        <div class="dc-block">
            <span class="dc-block__label">Mapowanie hostów → tenant</span>
            <table class="dc-table">
                <thead><tr><th>Host (domena)</th><th>Moduł</th><th>Tryb</th><th>Baza</th><th>Rola</th></tr></thead>
                <tbody>
                    <tr><td><code>pay.please-support-me.com</code></td><td>Gateway</td><td>—</td><td><code>nfc_pay</code></td><td>Bramka płatności, panel tagów/sklepów</td></tr>
                    <tr><td><code>please-support-me.com</code></td><td>Storefront</td><td><code>church</code></td><td><code>nfc_shop1</code></td><td>Cyfrowa Taca, sklep, CRM, rekrutacja (główny serwis)</td></tr>
                    <tr><td><code>shop2.please-support-me.com</code></td><td>Storefront</td><td><code>products</code></td><td><code>nfc_shop2</code></td><td>Sklep gadżetów ze stałą ceną (osobny tenant)</td></tr>
                </tbody>
            </table>
        </div>

        <div class="dc-block">
            <span class="dc-block__label">Co robi ResolveTenant</span>
            <ul>
                <li>Czyta host żądania i mapuje go na tenant (moduł + tryb + baza + klucz API bramki).</li>
                <li>Dynamicznie podmienia bazę połączenia MySQL (<code>config.database.connections.mysql.database</code>) — per żądanie, bez restartu.</li>
                <li>Przełącza ścieżki widoków Blade zależnie od trybu (church / products), więc ten sam kontroler renderuje inny wygląd.</li>
                <li>Rejestruje singleton <code>app('tenant')</code> dostępny w całej aplikacji oraz klucz API bramki w <code>config('shop.gateway_api_key')</code>.</li>
            </ul>
        </div>

        <div class="dc-block">
            <span class="dc-block__label">Połączenia bazy</span>
            <p>Połączenie <code>mysql</code> jest przełączane per host (sklepy), a dedykowane połączenie <code>gateway</code> wskazuje zawsze na <code>nfc_pay</code> — modele bramki czytają z niego niezależnie od tego, jaki tenant obsługuje bieżące żądanie.</p>
        </div>

        <div class="dc-block"><span class="dc-block__label">Pliki</span>
            <div class="dc-files">app/Http/Middleware/ResolveTenant.php · config/tenants.php · config/platform.php · config/database.php</div>
        </div>
    </section>

    {{-- ============ 2. PŁATNOŚCI PRZEGLĄD ============ --}}
    <section class="dc-module" id="platnosci">
        <div class="dc-module__head"><span class="dc-module__num">2.</span><h2>Warstwa płatności — przegląd</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">Płatności obsługuje osobny moduł <strong>Gateway</strong> (własna baza <code>nfc_pay</code>), z którym sklepy komunikują się przez REST API i webhooki. Sklep nigdy nie rozmawia bezpośrednio z PayU — robi to bramka, co pozwala obsłużyć wiele sklepów jednym, bezpiecznym połączeniem z operatorem. Pełny cykl jest wielowarstwowy: webhook + aktywny polling + podpisy kryptograficzne.</p>
        <div class="dc-block">
            <span class="dc-block__label">Przepływ end-to-end</span>
            <ol class="dc-flow">
                <li><strong>Inicjacja.</strong> Sklep tworzy transakcję w bramce na wybraną kwotę — <code>POST /api/v1/transactions</code> z nagłówkiem <code>X-Api-Key</code>. Bramka zwraca <code>uuid</code> i <code>payment_url</code>.</li>
                <li><strong>Autoryzacja.</strong> Klient na stronie bramki płaci BLIK-iem (kod 6-cyfrowy) lub pay-by-link (przekierowanie do aplikacji banku). Bramka tworzy zamówienie w PayU (REST v2.1, OAuth).</li>
                <li><strong>Webhook PayU → bramka.</strong> PayU wysyła powiadomienie <code>POST /webhooks/payu</code> z nagłówkiem <code>OpenPayu-Signature</code> (weryfikacja MD5/SHA256). Bramka oznacza transakcję jako opłaconą/nieudaną.</li>
                <li><strong>Webhook bramka → sklep.</strong> Bramka woła <code>notify_url</code> sklepu z podpisem <code>HMAC-SHA256</code>. Sklep weryfikuje podpis i aktualizuje zamówienie (<code>status = paid</code>, <code>paid_at</code>).</li>
                <li><strong>Polling (gwarancja).</strong> Ekran powrotu odpytuje status; bramka aktywnie rekonsyliuje z PayU (<code>getOrderStatus</code>), a transakcje „oczekujące na potwierdzenie” domyka przez <code>capture()</code>.</li>
            </ol>
        </div>
        <div class="dc-block"><span class="dc-block__label">Tryby płatności</span>
            <p><code>classic</code> — klasyczny przepływ z 3DS; <code>app2app</code> — BLIK / pay-by-link z przekierowaniem do aplikacji bankowej. Tryb ustawiany per sklep w polu <code>payment_mode</code>.</p>
        </div>
    </section>

    {{-- ============ 3. SKLEP NFC ============ --}}
    <section class="dc-module" id="sklep">
        <div class="dc-module__head"><span class="dc-module__num">3.</span><h2>Sklep donacyjny NFC (/)</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">Strona główna serwisu. Wyświetla produkty oznaczone tagami NFC (Serduszko, Kubek, Koszulka, Pin, Brelok). Każdy produkt ma własną <strong>minimalną kwotę</strong>. Domyślny produkt („Serduszko”, min. 1 zł) otwiera się automatycznie w modalu po wejściu. Kwota jest edytowalna w modalu, a walidacja minimum działa zarówno w przeglądarce, jak i na serwerze. Obsługiwane przez <code>CompanyStoreController</code>.</p>

        <div class="dc-block"><span class="dc-block__label">Trasy</span>
            <table class="dc-table">
                <thead><tr><th>Metoda</th><th>URL</th><th>Nazwa</th><th>Opis</th></tr></thead>
                <tbody>
                    <tr><td>GET</td><td><code>/</code></td><td><code>home</code></td><td>Lista produktów + modal KUP</td></tr>
                    <tr><td>POST</td><td><code>/sklep/kup/{slug}</code></td><td><code>shop.buy</code></td><td>Zakup produktu na wybraną kwotę</td></tr>
                </tbody>
            </table>
        </div>

        <div class="dc-block"><span class="dc-block__label">Metody kontrolera</span>
            <ul class="dc-fns">
                <li><code>index()</code><span>Pobiera aktywne produkty (sortowanie po <code>sort</code>), wyznacza domyślny (<code>is_default</code>) i renderuje stronę z siatką oraz danymi produktów dla modala (JSON).</span></li>
                <li><code>purchase($slug)</code><span>Waliduje kwotę (≥ minimum produktu, ≤ 5000 zł) po stronie serwera, tworzy zamówienie (<code>product_id = null</code>), woła bramkę i przekierowuje do płatności. Komunikat błędu zawiera nazwę produktu i jego minimum.</span></li>
            </ul>
        </div>

        <div class="dc-block"><span class="dc-block__label">Pola produktu (model ShopItem → tabela shop_items)</span>
            <table class="dc-table">
                <thead><tr><th>Pole</th><th>Typ</th><th>Opis</th></tr></thead>
                <tbody>
                    <tr><td><code>slug</code></td><td>string, unikalny</td><td>Identyfikator URL (np. <code>serduszko</code>)</td></tr>
                    <tr><td><code>name</code></td><td>string</td><td>Nazwa produktu</td></tr>
                    <tr><td><code>image</code></td><td>string</td><td>Ścieżka do grafiki (raster lub SVG serca)</td></tr>
                    <tr><td><code>min_amount</code></td><td>int (grosze)</td><td>Minimalna kwota wpłaty (100 = 1 zł)</td></tr>
                    <tr><td><code>is_default</code></td><td>bool</td><td>Czy produkt domyślny (auto-modal); tylko jeden naraz</td></tr>
                    <tr><td><code>tag_uid</code></td><td>string, nullable</td><td>UID taga NFC kierującego wprost na ten produkt</td></tr>
                    <tr><td><code>active</code></td><td>bool</td><td>Widoczność w sklepie</td></tr>
                    <tr><td><code>sort</code></td><td>int</td><td>Kolejność na liście</td></tr>
                </tbody>
            </table>
        </div>

        <div class="dc-block"><span class="dc-block__label">Co się dzieje (przepływ zakupu)</span>
            <ol class="dc-flow">
                <li>Użytkownik wchodzi na <code>/</code> — domyślny produkt otwiera się w modalu (raz na sesję, przez <code>sessionStorage</code>), albo otwiera się produkt wskazany w <code>?produkt={slug}</code>.</li>
                <li>Klik karty produktu otwiera modal z edytowalnym polem kwoty (wstępnie = minimum produktu).</li>
                <li>Walidacja w przeglądarce: przy kwocie poniżej minimum przycisk KUP jest blokowany i pojawia się komunikat.</li>
                <li><code>POST /sklep/kup/{slug}</code> — serwer ponownie waliduje kwotę (warstwa nie do obejścia), tworzy <code>Order</code> i transakcję w bramce.</li>
                <li>Przekierowanie 302 na <code>payment_url</code> bramki (PayU).</li>
            </ol>
        </div>

        <div class="dc-block"><span class="dc-block__label">Tabele</span><div class="dc-tables"><code>shop_items</code><code>orders</code></div></div>
        <div class="dc-block"><span class="dc-block__label">Pliki</span>
            <div class="dc-files">CompanyStoreController.php · Models/ShopItem.php · views/…/shop/sklep.blade.php · public/css/sklep.css · public/css/landing.css (modal)</div>
        </div>
    </section>

    {{-- ============ 4. TACA ============ --}}
    <section class="dc-module" id="taca">
        <div class="dc-module__head"><span class="dc-module__num">4.</span><h2>Cyfrowa Taca — wejście NFC i parafie</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">Główna funkcja platformy. Darczyńca zbliża telefon do znacznika NFC w kościele, trafia na stronę parafii, wybiera kwotę i płaci, a na końcu widzi ekran „Bóg zapłać”. Wszystkim steruje <code>StorefrontController</code>; dane parafii zarządzane są w panelu (sekcja 16). Każdy etap loguje zdarzenie do analityki.</p>

        <div class="dc-block"><span class="dc-block__label">Trasy</span>
            <table class="dc-table">
                <thead><tr><th>Metoda</th><th>URL</th><th>Nazwa</th><th>Opis</th></tr></thead>
                <tbody>
                    <tr><td>GET</td><td><code>/main</code></td><td><code>main</code></td><td>Landing (sekcja 5)</td></tr>
                    <tr><td>GET</td><td><code>/kategoria/{slug}</code></td><td><code>category</code></td><td>Lista parafii w kategorii + wyszukiwarka</td></tr>
                    <tr><td>GET</td><td><code>/t/{tag_uid}</code></td><td><code>tag</code></td><td>Wejście z taga NFC → przekierowanie</td></tr>
                    <tr><td>GET</td><td><code>/p/{slug}</code></td><td><code>product.show</code></td><td>Strona parafii + wybór kwoty</td></tr>
                    <tr><td>POST</td><td><code>/p/{slug}/kup</code></td><td><code>product.buy</code></td><td>Utworzenie zamówienia i transakcji</td></tr>
                </tbody>
            </table>
        </div>

        <div class="dc-block"><span class="dc-block__label">Metody kontrolera</span>
            <ul class="dc-fns">
                <li><code>index()</code><span>Renderuje landing: kategorie wsparcia z bazy (aktywne, najwyższy poziom) z ikoną, etykietą i opisem.</span></li>
                <li><code>category($slug)</code><span>Pokazuje parafie danej kategorii (gdy <code>source = parishes</code>) lub pusty stan. Po stronie klienta działa wyszukiwarka po nazwie, mieście i województwie.</span></li>
                <li><code>tag($tagUid)</code><span>Szuka parafii po <code>tag_uid</code> → przekierowuje na jej stronę i loguje <code>tag_open</code>. Jeśli to tag produktu sklepu → przekierowuje na sklep z modalem produktu. Nieznany tag → strona 404 „tabliczka nieprzypisana”.</span></li>
                <li><code>show($slug)</code><span>Strona parafii; loguje <code>page_view</code>; renderuje formularz wyboru kwoty z presetami.</span></li>
                <li><code>buy($slug)</code><span>Waliduje kwotę (2–5000 zł), loguje <code>buy_click</code>, tworzy <code>Order</code> powiązany z parafią i transakcję w bramce, przekierowuje do płatności.</span></li>
            </ul>
        </div>

        <div class="dc-block"><span class="dc-block__label">Pola parafii (model Product → tabela products)</span>
            <table class="dc-table">
                <thead><tr><th>Pole</th><th>Typ</th><th>Opis</th></tr></thead>
                <tbody>
                    <tr><td><code>name</code></td><td>string</td><td>Nazwa parafii</td></tr>
                    <tr><td><code>city</code></td><td>string</td><td>Miasto</td></tr>
                    <tr><td><code>purpose</code></td><td>string</td><td>Cel zbiórki (np. „Remont dachu”)</td></tr>
                    <tr><td><code>slug</code></td><td>string, unikalny</td><td>Identyfikator URL</td></tr>
                    <tr><td><code>description_html</code></td><td>text</td><td>Opis parafii (WYSIWYG)</td></tr>
                    <tr><td><code>price</code></td><td>int (grosze)</td><td>Sugerowana kwota tacy (preset bazowy)</td></tr>
                    <tr><td><code>tag_uid</code></td><td>string, unikalny</td><td>UID taga NFC przypisanego do parafii</td></tr>
                    <tr><td><code>main_image</code></td><td>string</td><td>Zdjęcie główne parafii</td></tr>
                    <tr><td><code>active</code></td><td>bool</td><td>Publikacja (sterowana statusem CRM)</td></tr>
                    <tr><td><code>phone, website, voivodeship</code></td><td>string</td><td>Dane kontaktowe (CRM)</td></tr>
                    <tr><td><code>status</code></td><td>enum</td><td>CRM: kontakt · test · wdrożenie · aktywna</td></tr>
                    <tr><td><code>salesperson_id</code></td><td>FK</td><td>Handlowiec opiekujący się parafią</td></tr>
                </tbody>
            </table>
        </div>

        <div class="dc-block"><span class="dc-block__label">Presety kwot (strona parafii)</span>
            <p>Domyślne przyciski: <strong>10, 20, 50, 100, 200 zł</strong> + kafelek „inna kwota” zamieniający się w pole liczbowe. Zakres dozwolony: 2–5000 zł. Wybrana kwota aktualizuje etykietę przycisku „Wesprzyj — X zł”.</p>
        </div>

        <div class="dc-block"><span class="dc-block__label">Co się dzieje (pełny przepływ tacy)</span>
            <ol class="dc-flow">
                <li>Telefon przy tagu NFC otwiera <code>/t/{tag_uid}</code>.</li>
                <li><code>tag()</code> znajduje parafię, loguje <code>tag_open</code> (lokalnie + asynchronicznie do bramki) i przekierowuje na <code>/p/{slug}</code>.</li>
                <li><code>show()</code> loguje <code>page_view</code> i pokazuje stronę z presetami.</li>
                <li>Po wyborze kwoty <code>POST /p/{slug}/kup</code>: <code>buy()</code> loguje <code>buy_click</code>, tworzy zamówienie i transakcję, przekierowuje do PayU.</li>
                <li>Po płatności następuje powrót na ekran zwrotu (sekcja 10) — „Bóg zapłać”.</li>
            </ol>
        </div>

        <div class="dc-block"><span class="dc-block__label">Tabele</span><div class="dc-tables"><code>products</code><code>categories</code><code>orders</code><code>events</code><code>product_images</code></div></div>
        <div class="dc-block"><span class="dc-block__label">Pliki</span>
            <div class="dc-files">StorefrontController.php · Models/Product.php, Category.php, Order.php, Event.php · views/…/shop/{home,category,product}.blade.php · Jobs/SendGatewayEvent.php</div>
        </div>
    </section>

    {{-- ============ 5. MAIN ============ --}}
    <section class="dc-module" id="main">
        <div class="dc-module__head"><span class="dc-module__num">5.</span><h2>Strona główna /main</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">Landing „Technologia, która pomaga czynić dobro” — zbudowany pixel-perfect z makiet Figmy (desktop i mobile). Sekcje są dynamiczne: kategorie „Kogo wspieramy?” pobierane są z bazy i zarządzane z panelu.</p>
        <div class="dc-block"><span class="dc-block__label">Sekcje</span>
            <ul>
                <li><strong>Hero</strong> — nagłówek z misją platformy.</li>
                <li><strong>Kogo wspieramy?</strong> — kafelki kategorii (ikona, etykieta HTML, opis); linki do <code>/kategoria/{slug}</code>.</li>
                <li><strong>Jak to działa?</strong> — 4 kroki: zbliż telefon do NFC → wybierz wsparcie → zapłać → otrzymaj podziękowanie.</li>
                <li><strong>Tekst zamykający</strong> + stopka z danymi firmy i linkami.</li>
            </ul>
        </div>
        <div class="dc-block"><span class="dc-block__label">Modal „Wesprzyj” i podgląd linku (OG)</span>
            <p>Przycisk „Wesprzyj” w nagłówku otwiera modal domyślnego produktu (<code>/?produkt=serduszko</code>). W <code>&lt;head&gt;</code> ustawione są tagi Open Graph i Twitter z grafiką serca z logo — przy udostępnianiu linku na WhatsApp / Facebook pojawia się ładny podgląd z serduszkiem.</p>
        </div>
        <div class="dc-block"><span class="dc-block__label">Tabele</span><div class="dc-tables"><code>categories</code></div></div>
        <div class="dc-block"><span class="dc-block__label">Pliki</span><div class="dc-files">StorefrontController::index() · views/…/shop/home.blade.php · layouts/landing.blade.php · public/css/landing.css · public/img/og-supportme.png</div></div>
    </section>

    {{-- ============ 6. INWESTORZY ============ --}}
    <section class="dc-module" id="inwestorzy">
        <div class="dc-module__head"><span class="dc-module__num">6.</span><h2>Inwestorzy i akcjonariusze</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">Statyczna strona marketingowa (<code>GET /inwestorzy</code>, nazwa trasy <code>investors</code>) zbudowana 1:1 z makiety Figmy. Hero „Inwestorzy i akcjonariusze”, sekcja misji kapitałowej („Wierzymy, że kapitał może służyć dobru”) oraz karty akcjonariuszy z kwotami inwestycji (kapitałowa + wsparcie usługowe). Renderowana bez kontrolera (<code>Route::view</code>).</p>
        <div class="dc-block"><span class="dc-block__label">Pliki</span><div class="dc-files">routes/web.php (Route::view) · views/…/shop/inwestorzy.blade.php · public/css/inwestorzy.css</div></div>
    </section>

    {{-- ============ 7. REKRUTACJA ============ --}}
    <section class="dc-module" id="rekrutacja">
        <div class="dc-module__head"><span class="dc-module__num">7.</span><h2>Rekrutacja i kariera</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">Pełny system rekrutacyjny: publiczna lista ofert (zarządzana z panelu), strony ofert, formularz aplikacji z uploadem CV oraz powiadomienie e-mail z załączonym CV. Obsługiwany przez <code>CareersController</code>. CV trafia na <strong>prywatny dysk</strong> (niedostępny publicznie), a pobrać je można tylko z panelu.</p>

        <div class="dc-block"><span class="dc-block__label">Trasy</span>
            <table class="dc-table">
                <thead><tr><th>Metoda</th><th>URL</th><th>Nazwa</th><th>Opis</th></tr></thead>
                <tbody>
                    <tr><td>GET</td><td><code>/praca</code></td><td><code>careers</code></td><td>Lista aktywnych ofert</td></tr>
                    <tr><td>GET</td><td><code>/praca/oferta/{position}</code></td><td><code>careers.show</code></td><td>Szczegóły oferty</td></tr>
                    <tr><td>GET/POST</td><td><code>/praca/aplikuj</code></td><td><code>careers.apply.general</code></td><td>Aplikacja spontaniczna (bez oferty)</td></tr>
                    <tr><td>GET/POST</td><td><code>/praca/{position}/aplikuj</code></td><td><code>careers.apply</code></td><td>Aplikacja na konkretną ofertę</td></tr>
                </tbody>
            </table>
        </div>

        <div class="dc-block"><span class="dc-block__label">Metody kontrolera</span>
            <ul class="dc-fns">
                <li><code>index()</code><span>Lista aktywnych stanowisk; karty z typem zatrudnienia i lokalizacją; wykrywa „pracę zdalną” z treści.</span></li>
                <li><code>show($position)</code><span>Pełny opis oferty (WYSIWYG), chipy meta, sekcja „inne oferty”.</span></li>
                <li><code>applyForm($position?)</code><span>Renderuje formularz aplikacji (na ofertę lub spontaniczny).</span></li>
                <li><code>applyStore($position?)</code><span>Waliduje dane i plik CV, zapisuje zgłoszenie, przenosi CV na prywatny dysk, wysyła e-mail (jeśli skonfigurowany mailer), pokazuje podziękowanie.</span></li>
            </ul>
        </div>

        <div class="dc-block"><span class="dc-block__label">Pola oferty (JobPosition → job_positions)</span>
            <table class="dc-table">
                <thead><tr><th>Pole</th><th>Typ</th><th>Opis</th></tr></thead>
                <tbody>
                    <tr><td><code>title</code></td><td>string</td><td>Nazwa stanowiska</td></tr>
                    <tr><td><code>location</code></td><td>string</td><td>Lokalizacja</td></tr>
                    <tr><td><code>employment_type</code></td><td>string</td><td>Rodzaj zatrudnienia</td></tr>
                    <tr><td><code>description_html</code></td><td>text</td><td>Opis (WYSIWYG)</td></tr>
                    <tr><td><code>active</code></td><td>bool</td><td>Widoczność publiczna</td></tr>
                    <tr><td><code>sort</code></td><td>int</td><td>Kolejność</td></tr>
                </tbody>
            </table>
        </div>

        <div class="dc-block"><span class="dc-block__label">Pola zgłoszenia (JobApplication → job_applications)</span>
            <table class="dc-table">
                <thead><tr><th>Pole</th><th>Typ</th><th>Opis</th></tr></thead>
                <tbody>
                    <tr><td><code>job_position_id</code></td><td>FK, nullable</td><td>Oferta (null = aplikacja spontaniczna)</td></tr>
                    <tr><td><code>name, email, phone</code></td><td>string</td><td>Dane kandydata</td></tr>
                    <tr><td><code>message</code></td><td>text, nullable</td><td>List motywacyjny</td></tr>
                    <tr><td><code>cv_path</code></td><td>string</td><td>Ścieżka pliku CV na prywatnym dysku</td></tr>
                    <tr><td><code>cv_original_name</code></td><td>string</td><td>Oryginalna nazwa pliku</td></tr>
                    <tr><td><code>is_read</code></td><td>bool</td><td>Czy odczytane w panelu</td></tr>
                    <tr><td><code>status</code></td><td>enum</td><td>pending · accepted · rejected</td></tr>
                </tbody>
            </table>
        </div>

        <div class="dc-block"><span class="dc-block__label">Walidacja i e-mail</span>
            <p>CV: wymagane, do 5 MB, typy <code>pdf/doc/docx</code> (sprawdzane rozszerzenie i MIME). Zgoda RODO: wymagana. Po zapisie generowany jest mail <code>JobApplicationReceived</code> na <code>config('shop.careers_email')</code> z CV w załączniku i adresem kandydata w Reply-To.</p>
        </div>

        <div class="dc-note"><strong>Powiadomienia e-mail</strong> <span class="dc-badge dc-badge--soon">Gotowy</span> — kod wysyłki maila jest kompletny; na produkcji działa tryb „tylko panel” (zgłoszenia + CV trafiają do skrzynki w panelu). Wysyłkę mailem włącza się konfiguracją mailera.</div>

        <div class="dc-block"><span class="dc-block__label">Tabele</span><div class="dc-tables"><code>job_positions</code><code>job_applications</code></div></div>
        <div class="dc-block"><span class="dc-block__label">Pliki</span><div class="dc-files">CareersController.php · Models/JobPosition.php, JobApplication.php · Mail/JobApplicationReceived.php · views/…/shop/{praca,oferta,aplikuj}.blade.php · views/emails/job-application.blade.php</div></div>
    </section>

    {{-- ============ 8. KONTAKT ============ --}}
    <section class="dc-module" id="kontakt">
        <div class="dc-module__head"><span class="dc-module__num">8.</span><h2>Kontakt</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">Publiczny formularz kontaktowy (<code>ContactController</code>). Wiadomości zapisywane są do bazy i trafiają do skrzynki w panelu z licznikiem nieprzeczytanych. Temat może być wstępnie wypełniony z linku z oferty pracy (<code>?stanowisko=…</code>).</p>
        <div class="dc-block"><span class="dc-block__label">Trasy i metody</span>
            <ul class="dc-fns">
                <li><code>show()</code><span><code>GET /kontakt</code> (nazwa <code>contact.show</code>) — formularz.</span></li>
                <li><code>store()</code><span><code>POST /kontakt</code> (nazwa <code>contact.store</code>) — walidacja i zapis wiadomości.</span></li>
            </ul>
        </div>
        <div class="dc-block"><span class="dc-block__label">Pola (ContactMessage → contact_messages)</span>
            <table class="dc-table">
                <thead><tr><th>Pole</th><th>Typ</th><th>Walidacja</th></tr></thead>
                <tbody>
                    <tr><td><code>name</code></td><td>string</td><td>wymagane, max 255</td></tr>
                    <tr><td><code>email</code></td><td>string</td><td>wymagane, e-mail, max 255</td></tr>
                    <tr><td><code>phone</code></td><td>string, nullable</td><td>max 50</td></tr>
                    <tr><td><code>subject</code></td><td>string, nullable</td><td>max 255</td></tr>
                    <tr><td><code>message</code></td><td>text</td><td>wymagane, max 5000</td></tr>
                    <tr><td><code>is_read</code></td><td>bool</td><td>oznaczane w panelu</td></tr>
                </tbody>
            </table>
        </div>
        <div class="dc-block"><span class="dc-block__label">Tabele</span><div class="dc-tables"><code>contact_messages</code></div></div>
        <div class="dc-block"><span class="dc-block__label">Pliki</span><div class="dc-files">ContactController.php · Models/ContactMessage.php · views/…/shop/kontakt.blade.php</div></div>
    </section>

    {{-- ============ 9. REGULAMIN ============ --}}
    <section class="dc-module" id="regulamin">
        <div class="dc-module__head"><span class="dc-module__num">9.</span><h2>Regulamin</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">Statyczny dokument prawny (<code>GET /regulamin</code>, nazwa <code>regulamin</code>) — kompletny regulamin sklepu internetowego przeniesiony 1:1 z dokumentu źródłowego. 12 paragrafów + wzór formularza odstąpienia.</p>
        <div class="dc-block"><span class="dc-block__label">Zawartość</span>
            <ul>
                <li>§1 Postanowienia ogólne · §2 Definicje · §3 Usługi elektroniczne · §4 Zamówienia</li>
                <li>§5 Ceny i płatności · §6 Wymagania techniczne · §7 Odstąpienie (14 dni) · §8 Reklamacje i zwroty</li>
                <li>§9 Ochrona danych (RODO) · §10 Własność intelektualna · §11 ODR · §12 Postanowienia końcowe</li>
                <li>Załącznik: wzór formularza odstąpienia; dane firmy: MLI – Marcin Lula Informatyka, NIP 8741624637</li>
            </ul>
        </div>
        <div class="dc-block"><span class="dc-block__label">Pliki</span><div class="dc-files">routes/web.php (Route::view) · views/…/shop/regulamin.blade.php · public/css/subpages.css</div></div>
    </section>

    {{-- ============ 10. POWRÓT ============ --}}
    <section class="dc-module" id="powrot">
        <div class="dc-module__head"><span class="dc-module__num">10.</span><h2>Powrót z płatności</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">Po powrocie z bramki <code>OrderReturnController</code> synchronizuje status zamówienia i pokazuje właściwy ekran: sukces („Bóg zapłać”), oczekiwanie (z pollingiem) lub niepowodzenie (z opcją ponowienia). To zabezpiecza przed sytuacją, gdy klient wróci wcześniej niż dotrze webhook.</p>
        <div class="dc-block"><span class="dc-block__label">Trasy i metody</span>
            <ul class="dc-fns">
                <li><code>show($order)</code><span><code>GET /zwrot/{order}</code> (nazwa <code>order.return</code>) — synchronizuje status z bramki; jeśli opłacone, loguje <code>purchase</code>; renderuje ekran wg statusu.</span></li>
                <li><code>status($order)</code><span><code>GET /zwrot/{order}/status</code> (nazwa <code>order.status</code>) — zwraca JSON <code>{status}</code> dla pollingu.</span></li>
            </ul>
        </div>
        <div class="dc-block"><span class="dc-block__label">Ekrany (wg statusu zamówienia)</span>
            <table class="dc-table">
                <thead><tr><th>Status</th><th>Widok</th><th>Zachowanie</th></tr></thead>
                <tbody>
                    <tr><td><code>paid</code></td><td>return-success</td><td>Animacja świecy, kwota, nazwa parafii, nr potwierdzenia</td></tr>
                    <tr><td><code>pending</code></td><td>return-pending</td><td>Spinner + polling co 2 s (do ~60 s); po zmianie statusu przeładowanie</td></tr>
                    <tr><td><code>failed</code></td><td>return-failure</td><td>Komunikat o niepowodzeniu, przyciski „spróbuj ponownie” / „inna parafia”</td></tr>
                </tbody>
            </table>
        </div>
        <div class="dc-block"><span class="dc-block__label">Pola zamówienia (Order → orders)</span>
            <table class="dc-table">
                <thead><tr><th>Pole</th><th>Typ</th><th>Opis</th></tr></thead>
                <tbody>
                    <tr><td><code>id</code></td><td>UUID</td><td>Identyfikator zamówienia</td></tr>
                    <tr><td><code>product_id</code></td><td>FK, nullable</td><td>Parafia (null dla sklepu donacyjnego)</td></tr>
                    <tr><td><code>transaction_id</code></td><td>UUID</td><td>Transakcja w bramce</td></tr>
                    <tr><td><code>amount</code></td><td>int (grosze)</td><td>Kwota wpłaty</td></tr>
                    <tr><td><code>status</code></td><td>enum</td><td>pending · paid · failed</td></tr>
                    <tr><td><code>paid_at</code></td><td>timestamp, nullable</td><td>Czas potwierdzenia</td></tr>
                </tbody>
            </table>
        </div>
        <div class="dc-block"><span class="dc-block__label">Tabele</span><div class="dc-tables"><code>orders</code><code>events</code></div></div>
        <div class="dc-block"><span class="dc-block__label">Pliki</span><div class="dc-files">OrderReturnController.php · views/…/shop/return-{success,pending,failure}.blade.php</div></div>
    </section>

    {{-- ============ 11. GATEWAY CLIENT ============ --}}
    <section class="dc-module" id="client">
        <div class="dc-module__head"><span class="dc-module__num">11.</span><h2>Klient bramki i webhooki (po stronie sklepu)</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">Sklep komunikuje się z bramką przez serwis <code>GatewayClient</code> oraz odbiera powiadomienia zwrotne (webhooki) o opłaceniu. Zdarzenia analityczne (np. <code>tag_open</code>) wysyłane są asynchronicznie, by nie spowalniać odpowiedzi.</p>
        <div class="dc-block"><span class="dc-block__label">Metody GatewayClient</span>
            <ul class="dc-fns">
                <li><code>createTransaction($data)</code><span>POST do API bramki z danymi: <code>product_external_id</code>, <code>product_name</code>, <code>amount</code>, <code>currency</code>, <code>return_url</code>, <code>notify_url</code>, <code>tag_uid</code>. Zwraca <code>uuid</code> i <code>payment_url</code>.</span></li>
                <li><code>getTransaction($uuid)</code><span>Pobiera bieżący status transakcji (używane przy synchronizacji ekranu zwrotu).</span></li>
                <li><code>sendEvent($type, $tagUid)</code><span>Wysyła zdarzenie do bramki (np. <code>tag_open</code>).</span></li>
                <li><code>verifyWebhookSignature($payload, $sig)</code><span>Weryfikuje podpis <code>HMAC-SHA256</code> przychodzącego webhooka kluczem API sklepu.</span></li>
            </ul>
        </div>
        <div class="dc-block"><span class="dc-block__label">Odbiór webhooka</span>
            <p><code>GatewayWebhookController::handle()</code> (<code>POST /webhooks/gateway</code>) weryfikuje podpis, a następnie ustawia <code>Order.status = paid</code> + <code>paid_at</code> i loguje zdarzenie <code>purchase</code>. <code>SendGatewayEvent</code> (job kolejkowy, <code>dispatchAfterResponse</code>) wysyła eventy do bramki już po odesłaniu odpowiedzi do użytkownika.</p>
        </div>
        <div class="dc-block"><span class="dc-block__label">Pliki</span><div class="dc-files">Services/GatewayClient.php · Http/Controllers/GatewayWebhookController.php · Jobs/SendGatewayEvent.php</div></div>
    </section>

    {{-- ============ 12. GATEWAY ============ --}}
    <section class="dc-module" id="gateway">
        <div class="dc-module__head"><span class="dc-module__num">12.</span><h2>Moduł Gateway — bramka PayU</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">Samodzielna bramka płatnicza działająca jako odrębna aplikacja (<code>pay.please-support-me.com</code>, baza <code>nfc_pay</code>). Integruje PayU, obsługuje wiele sklepów, tagi NFC, transakcje, zdarzenia i statystyki. Zawiera 15 kontrolerów, własne API, panel oraz warstwę dostawców płatności (provider).</p>

        <div class="dc-block"><span class="dc-block__label">Dostawca płatności — PayUProvider</span>
            <ul class="dc-fns">
                <li><code>createTransaction()</code><span>Tworzy zamówienie w PayU (POST <code>/api/v2_1/orders</code>, OAuth client_credentials); zwraca <code>provider_order_id</code> i URL przekierowania.</span></li>
                <li><code>getOrderStatus()</code><span>Pobiera status zamówienia z PayU (aktywna rekonsyliacja).</span></li>
                <li><code>capture()</code><span>Domyka płatność oczekującą na potwierdzenie (PUT statusu → COMPLETED).</span></li>
                <li><code>payByLinks()</code><span>Zwraca listę banków do ekranu wyboru (pay-by-link).</span></li>
                <li><code>handleWebhook()</code><span>Weryfikuje <code>OpenPayu-Signature</code> i parsuje powiadomienie. Obsługa BLIK Level 0 i pay-by-link.</span></li>
            </ul>
            <p>Interfejs <code>PaymentProviderInterface</code> pozwala podmienić dostawcę; alternatywą jest <code>MockProvider</code> (tryb testowy).</p>
        </div>

        <div class="dc-block"><span class="dc-block__label">Logika transakcji — TransactionService</span>
            <ul class="dc-fns">
                <li><code>reconcileWithProvider()</code><span>Aktywnie sprawdza status u PayU (gdy webhook się spóźnia).</span></li>
                <li><code>markPaid() / markFailed()</code><span>Idempotentnie ustawia status transakcji.</span></li>
                <li><code>logEvent()</code><span>Zapisuje zdarzenie transakcji do bazy.</span></li>
                <li><code>notifyShop()</code><span>Wysyła webhook wychodzący do sklepu, podpisany <code>HMAC-SHA256</code>.</span></li>
            </ul>
        </div>

        <div class="dc-block"><span class="dc-block__label">Pola transakcji (Transaction → transactions)</span>
            <table class="dc-table">
                <thead><tr><th>Pole</th><th>Typ</th><th>Opis</th></tr></thead>
                <tbody>
                    <tr><td><code>id</code></td><td>UUID</td><td>Identyfikator transakcji (= uuid u sklepu)</td></tr>
                    <tr><td><code>shop_id, tag_id</code></td><td>FK</td><td>Sklep i opcjonalny tag NFC</td></tr>
                    <tr><td><code>product_external_id</code></td><td>string</td><td>Identyfikator produktu po stronie sklepu</td></tr>
                    <tr><td><code>product_name</code></td><td>string</td><td>Nazwa pozycji (np. „Taca — Parafia X”)</td></tr>
                    <tr><td><code>amount, currency</code></td><td>int / string</td><td>Kwota (grosze) i waluta (PLN)</td></tr>
                    <tr><td><code>status</code></td><td>enum</td><td>created · pending · paid · failed · abandoned</td></tr>
                    <tr><td><code>mode</code></td><td>string</td><td>classic / app2app</td></tr>
                    <tr><td><code>return_url, notify_url</code></td><td>string</td><td>Powrót i webhook sklepu</td></tr>
                    <tr><td><code>provider_order_id</code></td><td>string</td><td>ID zamówienia w PayU</td></tr>
                    <tr><td><code>paid_at</code></td><td>timestamp</td><td>Czas opłacenia</td></tr>
                </tbody>
            </table>
        </div>
        <div class="dc-block"><span class="dc-block__label">Tabele</span><div class="dc-tables"><code>shops</code><code>tags</code><code>transactions</code><code>events</code><code>leads</code><code>antitheft_checks</code></div></div>
        <div class="dc-block"><span class="dc-block__label">Pliki</span><div class="dc-files">Modules/Gateway/Payments/{PayUProvider,MockProvider,PaymentProviderInterface,TransactionDto,WebhookResult}.php · Services/{TransactionService,StatsService}.php · Models/{Shop,Tag,Transaction,Event,Lead,AntitheftCheck}.php</div></div>
    </section>

    {{-- ============ 13. GATEWAY API ============ --}}
    <section class="dc-module" id="gw-api">
        <div class="dc-module__head"><span class="dc-module__num">13.</span><h2>Gateway: API i webhooki</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">Bramka udostępnia REST API dla sklepów (autoryzacja kluczem) oraz endpointy płatności i webhooki. Wszystkie powiadomienia są podpisywane i weryfikowane kryptograficznie.</p>
        <div class="dc-block"><span class="dc-block__label">Trasy API i płatności</span>
            <table class="dc-table">
                <thead><tr><th>Metoda</th><th>URL</th><th>Opis</th></tr></thead>
                <tbody>
                    <tr><td>POST</td><td><code>/api/v1/transactions</code></td><td>Utworzenie transakcji (nagłówek <code>X-Api-Key</code>)</td></tr>
                    <tr><td>GET</td><td><code>/api/v1/transactions/{uuid}</code></td><td>Status transakcji</td></tr>
                    <tr><td>POST</td><td><code>/api/v1/events</code></td><td>Rejestracja zdarzenia (np. tag_open)</td></tr>
                    <tr><td>GET</td><td><code>/pay/{uuid}</code></td><td>Ekran płatności (wybór metody)</td></tr>
                    <tr><td>POST</td><td><code>/pay/{uuid}/confirm</code></td><td>Potwierdzenie płatności (BLIK / pay-by-link)</td></tr>
                    <tr><td>GET</td><td><code>/pay/{uuid}/return</code></td><td>Powrót z PayU</td></tr>
                    <tr><td>POST</td><td><code>/webhooks/payu</code></td><td>Webhook PayU (OpenPayu-Signature)</td></tr>
                </tbody>
            </table>
        </div>
        <div class="dc-block"><span class="dc-block__label">Kontrolery</span><div class="dc-files">Api/TransactionController.php · Api/EventController.php · PaymentController.php · WebhookController.php · ActivationStatusController.php</div></div>
    </section>

    {{-- ============ 14. GATEWAY PANEL ============ --}}
    <section class="dc-module" id="gw-panel">
        <div class="dc-module__head"><span class="dc-module__num">14.</span><h2>Gateway: panel zarządzania</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">Panel bramki (osobne logowanie) służy do zarządzania sklepami, tagami NFC, statystykami i leadami. Zawiera też moduły demonstracyjne (anti-theft, tryb testowy płatności).</p>
        <div class="dc-block"><span class="dc-block__label">Sekcje panelu bramki</span>
            <ul class="dc-fns">
                <li><code>ShopController</code><span>CRUD sklepów: nazwa, slug, klucz API, tryb płatności (classic/app2app), URL bazowy.</span></li>
                <li><code>TagController</code><span>Zarządzanie tagami NFC: przypisanie do sklepu, etykieta, aktywność.</span></li>
                <li><code>StatsController</code><span>Statystyki płatności per sklep — transakcje, przychód.</span></li>
                <li><code>LeadController</code><span>Leady z landingu bramki + eksport do CSV.</span></li>
                <li><code>DashboardController</code><span>Pulpit startowy panelu bramki.</span></li>
                <li><code>LandingController</code><span>Strona główna bramki (<code>GET /</code>) + zapis leada (<code>POST /lead</code>).</span></li>
            </ul>
        </div>
        <div class="dc-block"><span class="dc-block__label">Moduły demonstracyjne</span>
            <p><span class="dc-badge dc-badge--demo">Demo</span> <strong>Anti-theft</strong> (<code>AntiTheftController</code>, model <code>AntitheftCheck</code>) — szkielet kontroli integralności tagów NFC; obecnie zwraca status OK, gotowy do rozbudowy.</p>
            <p style="margin-top:8px"><span class="dc-badge dc-badge--demo">Demo</span> <strong>Tryb testowy płatności</strong> (<code>MockPaymentController</code>, <code>MockProvider</code>) — pozwala przejść cały przepływ bez realnej bramki PayU (środowiska testowe).</p>
        </div>
        <div class="dc-block"><span class="dc-block__label">Pola sklepu i taga</span>
            <table class="dc-table">
                <thead><tr><th>Model</th><th>Pola</th></tr></thead>
                <tbody>
                    <tr><td><code>Shop</code></td><td>name, slug, base_url, api_key, payment_mode (classic/app2app)</td></tr>
                    <tr><td><code>Tag</code></td><td>shop_id, tag_uid, target_url, label, active</td></tr>
                    <tr><td><code>Lead</code></td><td>name, email, phone, company, message</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- ============ 15. PANEL AUTH + DASHBOARD ============ --}}
    <section class="dc-module" id="panel-auth">
        <div class="dc-module__head"><span class="dc-module__num">15.</span><h2>Panel: logowanie i dashboard</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">Panel sklepu (<code>/panel</code>) chroniony jest logowaniem (middleware <code>auth</code>). Po zalogowaniu dashboard pokazuje kondycję sprzedaży. Łącznie panel udostępnia ponad 50 tras.</p>
        <div class="dc-block"><span class="dc-block__label">Logowanie (LoginController)</span>
            <ul class="dc-fns">
                <li><code>show()</code><span><code>GET /panel/login</code> — formularz (przekierowanie do dashboardu, jeśli zalogowany).</span></li>
                <li><code>login()</code><span><code>POST /panel/login</code> — walidacja e-mail + hasło, sesja.</span></li>
                <li><code>logout()</code><span><code>POST /panel/logout</code> — wylogowanie, unieważnienie sesji, regeneracja CSRF.</span></li>
            </ul>
        </div>
        <div class="dc-block"><span class="dc-block__label">Dashboard (DashboardController)</span>
            <p><code>GET /panel</code> (nazwa <code>panel.dashboard</code>). Pokazuje metryki łączne i z 30 dni oraz wykres dziennej sprzedaży, korzystając z <code>ShopStatsService</code>:</p>
            <ul>
                <li>otwarcia tagów NFC (<code>tag_open</code>), wyświetlenia (<code>page_view</code>), kliknięcia „Kup” (<code>buy_click</code>)</li>
                <li>opłacone zamówienia, przychód łączny (suma <code>amount</code>), konwersja % (zakupy / otwarcia)</li>
                <li>seria dziennych zakupów (30 dni) do wykresu słupkowego</li>
            </ul>
        </div>
        <div class="dc-block"><span class="dc-block__label">Tabele</span><div class="dc-tables"><code>users</code><code>events</code><code>orders</code></div></div>
    </section>

    {{-- ============ 16. PANEL PARAFIE ============ --}}
    <section class="dc-module" id="panel-parafie">
        <div class="dc-module__head"><span class="dc-module__num">16.</span><h2>Panel: Parafie + CRM</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">Najbogatsza sekcja panelu (<code>ProductController</code>): pełny CRUD parafii wraz z galerią zdjęć, opisem WYSIWYG, statystykami oraz wbudowanym CRM (statusy leada, notatki, przypisany handlowiec). Publikacja parafii sterowana jest statusem — ustawienie „aktywna” włącza widoczność publiczną.</p>
        <div class="dc-block"><span class="dc-block__label">Metody</span>
            <ul class="dc-fns">
                <li><code>index()</code><span>Lista parafii z filtrem statusu i wyszukiwarką (nazwa/miasto/województwo) + liczniki statusów.</span></li>
                <li><code>create() / store()</code><span>Dodawanie parafii.</span></li>
                <li><code>edit() / update()</code><span>Edycja (z notatkami CRM).</span></li>
                <li><code>toggle()</code><span>Włącz/wyłącz widoczność.</span></li>
                <li><code>deleteImage()</code><span>Usunięcie zdjęcia z galerii.</span></li>
                <li><code>stats()</code><span>Statystyki konwersji parafii (eventy, wykres).</span></li>
                <li><code>status()</code><span>Szybka zmiana statusu CRM (AJAX) — steruje publikacją.</span></li>
                <li><code>storeNote() / destroyNote()</code><span>Notatki CRM (AJAX, JSON).</span></li>
                <li><code>uploadEditorImage()</code><span>Upload obrazu z edytora WYSIWYG (zwraca URL).</span></li>
            </ul>
        </div>
        <div class="dc-block"><span class="dc-block__label">Statusy CRM (lejek wdrożenia)</span>
            <p><code>kontakt</code> → <code>test</code> → <code>wdrożenie</code> → <code>aktywna</code> (każdy z własnym kolorem plakietki; „aktywna” = publikacja).</p>
        </div>
        <div class="dc-block"><span class="dc-block__label">Notatki CRM (ParishNote → parish_notes)</span>
            <table class="dc-table">
                <thead><tr><th>Pole</th><th>Opis</th></tr></thead>
                <tbody>
                    <tr><td><code>product_id</code></td><td>Parafia, której dotyczy</td></tr>
                    <tr><td><code>type</code></td><td>kontakt · telefon · mail · spotkanie · inne</td></tr>
                    <tr><td><code>body</code></td><td>Treść notatki</td></tr>
                    <tr><td><code>author</code></td><td>Autor</td></tr>
                </tbody>
            </table>
        </div>
        <div class="dc-block"><span class="dc-block__label">Galeria (ProductImage → product_images)</span>
            <p>Pola: <code>product_id</code>, <code>path</code>, <code>sort</code>. Wielokrotny upload, sortowanie, usuwanie pojedynczych zdjęć.</p>
        </div>
        <div class="dc-block"><span class="dc-block__label">Tabele</span><div class="dc-tables"><code>products</code><code>parish_notes</code><code>product_images</code><code>salespeople</code><code>events</code><code>orders</code></div></div>
        <div class="dc-block"><span class="dc-block__label">Pliki</span><div class="dc-files">Panel/ProductController.php · views/…/panel/products/{index,form,stats}.blade.php · ShopStatsService.php</div></div>
    </section>

    {{-- ============ 17. PANEL KATEGORIE ============ --}}
    <section class="dc-module" id="panel-kat">
        <div class="dc-module__head"><span class="dc-module__num">17.</span><h2>Panel: Kategorie</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">Drzewo kategorii „Kogo wspieramy?” (<code>CategoryController</code>) sterujące sekcjami na stronie głównej. Obsługuje zagnieżdżanie (parent/child), zmianę kolejności i ikony, z ochroną przed cyklami.</p>
        <div class="dc-block"><span class="dc-block__label">Metody</span>
            <ul class="dc-fns">
                <li><code>index()</code><span>Spłaszczone drzewo z wcięciami (rekurencja).</span></li>
                <li><code>store() / update() / destroy()</code><span>CRUD; usunięcie przenosi dzieci na poziom wyższy.</span></li>
                <li><code>reorder()</code><span>Zamiana kolejności (swap pozycji) w górę/dół.</span></li>
            </ul>
        </div>
        <div class="dc-block"><span class="dc-block__label">Pola (Category → categories)</span>
            <table class="dc-table">
                <thead><tr><th>Pole</th><th>Opis</th></tr></thead>
                <tbody>
                    <tr><td><code>parent_id</code></td><td>Kategoria nadrzędna (zagnieżdżenie)</td></tr>
                    <tr><td><code>label / label_html / label_text</code></td><td>Etykiety (tekst + wersja HTML)</td></tr>
                    <tr><td><code>slug</code></td><td>Identyfikator URL (auto z nazwy)</td></tr>
                    <tr><td><code>intro</code></td><td>Opis sekcji</td></tr>
                    <tr><td><code>icon</code></td><td>Ikona (upload)</td></tr>
                    <tr><td><code>source</code></td><td><code>none</code> (pusta) lub <code>parishes</code> (lista parafii)</td></tr>
                    <tr><td><code>position</code></td><td>Kolejność w drzewie</td></tr>
                    <tr><td><code>active</code></td><td>Widoczność</td></tr>
                </tbody>
            </table>
        </div>
        <div class="dc-block"><span class="dc-block__label">Tabele</span><div class="dc-tables"><code>categories</code></div></div>
    </section>

    {{-- ============ 18. PANEL HANDLOWCY ============ --}}
    <section class="dc-module" id="panel-hand">
        <div class="dc-module__head"><span class="dc-module__num">18.</span><h2>Panel: Handlowcy</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">CRUD handlowców (<code>SalespersonController</code>) z przypisaniem obsługiwanych województw i licznikiem przypisanych parafii. Integruje się z CRM parafii i mapą pokrycia.</p>
        <div class="dc-block"><span class="dc-block__label">Pola (Salesperson → salespeople)</span>
            <table class="dc-table">
                <thead><tr><th>Pole</th><th>Opis</th></tr></thead>
                <tbody>
                    <tr><td><code>name</code></td><td>Imię i nazwisko</td></tr>
                    <tr><td><code>email, phone</code></td><td>Kontakt (opcjonalne)</td></tr>
                    <tr><td><code>voivodeships</code></td><td>Tablica obsługiwanych województw (JSON, 16 do wyboru)</td></tr>
                    <tr><td><code>active</code></td><td>Czy aktywny</td></tr>
                </tbody>
            </table>
        </div>
        <div class="dc-block"><span class="dc-block__label">Tabele</span><div class="dc-tables"><code>salespeople</code><code>products</code><code>potential_parishes</code></div></div>
    </section>

    {{-- ============ 19. PANEL LEADY + MAPA ============ --}}
    <section class="dc-module" id="panel-leady">
        <div class="dc-module__head"><span class="dc-module__num">19.</span><h2>Panel: Parafie do obdzwonienia + mapa pokrycia</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">Lista leadów parafialnych (<code>PotentialParishController</code>) zaimportowanych z OpenStreetMap, z lejkiem obdzwaniania i interaktywną mapą pokrycia (Leaflet). Filtry: województwo, status, handlowiec, obecność telefonu. Paginacja po 50.</p>
        <div class="dc-block"><span class="dc-block__label">Metody</span>
            <ul class="dc-fns">
                <li><code>index()</code><span>Lista z filtrami i paginacją; liczniki per status.</span></li>
                <li><code>updateStatus()</code><span>Zmiana statusu/handlowca/notatki/telefonu (AJAX); ustawia <code>called_at</code> przy pierwszym kontakcie.</span></li>
                <li><code>coverageData()</code><span>Zwraca punkty (JSON) do mapy — tylko rekordy ze współrzędnymi, z kolorem wg statusu.</span></li>
            </ul>
        </div>
        <div class="dc-block"><span class="dc-block__label">Mapa pokrycia</span>
            <p><code>GET /panel/coverage</code> — mapa Leaflet z klastrowaniem markerów, licznikami per województwo i status, popupami (nazwa, miasto, telefon, status, handlowiec) i filtrami przeładowującymi dane AJAX-em.</p>
        </div>
        <div class="dc-block"><span class="dc-block__label">Lejek obdzwaniania (status)</span>
            <p><code>nowa</code> → <code>do_obdzwonienia</code> → <code>zadzwoniono</code> → <code>zainteresowana</code> → <code>dodana</code> / <code>odrzucona</code>.</p>
        </div>
        <div class="dc-block"><span class="dc-block__label">Pola (PotentialParish → potential_parishes)</span>
            <table class="dc-table">
                <thead><tr><th>Pole</th><th>Opis</th></tr></thead>
                <tbody>
                    <tr><td><code>name, city, address, voivodeship</code></td><td>Dane adresowe</td></tr>
                    <tr><td><code>denomination</code></td><td>Wyznanie</td></tr>
                    <tr><td><code>phone</code></td><td>Telefon</td></tr>
                    <tr><td><code>lat, lon</code></td><td>Współrzędne (mapa)</td></tr>
                    <tr><td><code>status</code></td><td>Status leada (lejek)</td></tr>
                    <tr><td><code>salesperson_id</code></td><td>Prowadzący handlowiec</td></tr>
                    <tr><td><code>note</code></td><td>Notatka</td></tr>
                    <tr><td><code>called_at</code></td><td>Data pierwszego kontaktu</td></tr>
                </tbody>
            </table>
        </div>
        <div class="dc-block"><span class="dc-block__label">Tabele</span><div class="dc-tables"><code>potential_parishes</code><code>salespeople</code></div></div>
    </section>

    {{-- ============ 20. PANEL SKLEP ============ --}}
    <section class="dc-module" id="panel-sklep">
        <div class="dc-module__head"><span class="dc-module__num">20.</span><h2>Panel: Sklep — produkty NFC</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">CRUD produktów sklepu donacyjnego (<code>ShopItemController</code>): minimalna kwota, tag NFC, produkt domyślny (tylko jeden), upload grafiki, kolejność i aktywność. Cena wpisywana w złotych, zapisywana w groszach.</p>
        <div class="dc-block"><span class="dc-block__label">Metody</span>
            <ul class="dc-fns">
                <li><code>index()</code><span>Lista produktów (sort, miniatura, min. kwota, tag, domyślny, status).</span></li>
                <li><code>store() / update()</code><span>Zapis; ustawienie „domyślny” zdejmuje flagę z pozostałych produktów.</span></li>
                <li><code>toggle() / destroy()</code><span>Aktywacja / usunięcie.</span></li>
            </ul>
        </div>
        <div class="dc-block"><span class="dc-block__label">Pola formularza</span>
            <p>nazwa, slug (auto), <code>min_amount_pln</code> (→ grosze), <code>tag_uid</code>, kolejność, grafika (do 5 MB), <code>is_default</code>, <code>active</code>.</p>
        </div>
        <div class="dc-block"><span class="dc-block__label">Tabele</span><div class="dc-tables"><code>shop_items</code></div></div>
    </section>

    {{-- ============ 21. PANEL PRACA ============ --}}
    <section class="dc-module" id="panel-praca">
        <div class="dc-module__head"><span class="dc-module__num">21.</span><h2>Panel: Praca — stanowiska</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">CRUD ofert pracy (<code>PositionController</code>) z opisem WYSIWYG, lokalizacją, typem zatrudnienia, kolejnością i licznikiem aplikacji per oferta. Oferty pojawiają się publicznie na <code>/praca</code>.</p>
        <div class="dc-block"><span class="dc-block__label">Metody</span>
            <ul class="dc-fns">
                <li><code>index()</code><span>Lista stanowisk z liczbą aplikacji.</span></li>
                <li><code>store() / update() / toggle() / destroy()</code><span>Pełny CRUD + włącz/wyłącz.</span></li>
            </ul>
        </div>
        <div class="dc-block"><span class="dc-block__label">Tabele</span><div class="dc-tables"><code>job_positions</code><code>job_applications</code></div></div>
    </section>

    {{-- ============ 22. PANEL APLIKACJE ============ --}}
    <section class="dc-module" id="panel-apl">
        <div class="dc-module__head"><span class="dc-module__num">22.</span><h2>Panel: Aplikacje rekrutacyjne</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">Skrzynka zgłoszeń rekrutacyjnych (<code>ApplicationController</code>) z pobieraniem CV z prywatnego dysku, statusami i filtrami. Licznik nieprzeczytanych widoczny w nawigacji panelu.</p>
        <div class="dc-block"><span class="dc-block__label">Metody</span>
            <ul class="dc-fns">
                <li><code>index()</code><span>Skrzynka (najnowsze na górze), filtry po ofercie i statusie, liczniki.</span></li>
                <li><code>show()</code><span>Szczegóły zgłoszenia; oznacza jako przeczytane.</span></li>
                <li><code>cv()</code><span>Pobranie pliku CV z prywatnego dysku.</span></li>
                <li><code>updateStatus()</code><span>Zmiana statusu: do sprawdzenia / zaakceptowany / odrzucony.</span></li>
                <li><code>destroy()</code><span>Usunięcie zgłoszenia wraz z plikiem CV.</span></li>
            </ul>
        </div>
        <div class="dc-block"><span class="dc-block__label">Tabele</span><div class="dc-tables"><code>job_applications</code><code>job_positions</code></div></div>
    </section>

    {{-- ============ 23. PANEL WIADOMOŚCI ============ --}}
    <section class="dc-module" id="panel-wiad">
        <div class="dc-module__head"><span class="dc-module__num">23.</span><h2>Panel: Wiadomości</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">Skrzynka wiadomości z formularza kontaktowego (<code>MessageController</code>) z licznikiem nieprzeczytanych.</p>
        <div class="dc-block"><span class="dc-block__label">Metody</span>
            <ul class="dc-fns">
                <li><code>index()</code><span>Lista wiadomości (najnowsze na górze).</span></li>
                <li><code>show()</code><span>Szczegóły; oznacza jako przeczytane.</span></li>
                <li><code>destroy()</code><span>Usunięcie.</span></li>
            </ul>
        </div>
        <div class="dc-block"><span class="dc-block__label">Tabele</span><div class="dc-tables"><code>contact_messages</code></div></div>
    </section>

    {{-- ============ 24. ANALITYKA ============ --}}
    <section class="dc-module" id="analityka">
        <div class="dc-module__head"><span class="dc-module__num">24.</span><h2>Eventy i analityka</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">System rejestruje każde istotne zdarzenie — od zbliżenia telefonu po finalną wpłatę — co zasila statystyki w panelu i pozwala mierzyć konwersję. Zdarzenia trzymane są w tabeli <code>events</code> (z indeksem po typie i dacie), a agregacją zajmuje się <code>ShopStatsService</code>.</p>
        <div class="dc-block"><span class="dc-block__label">Typy zdarzeń (sklep)</span>
            <table class="dc-table">
                <thead><tr><th>Typ</th><th>Kiedy</th></tr></thead>
                <tbody>
                    <tr><td><code>tag_open</code></td><td>Zbliżenie telefonu do tagu NFC</td></tr>
                    <tr><td><code>page_view</code></td><td>Wyświetlenie strony parafii / produktu</td></tr>
                    <tr><td><code>buy_click</code></td><td>Kliknięcie „Wesprzyj / Kup”</td></tr>
                    <tr><td><code>purchase</code></td><td>Potwierdzona wpłata</td></tr>
                </tbody>
            </table>
        </div>
        <div class="dc-block"><span class="dc-block__label">Metody ShopStatsService</span>
            <ul class="dc-fns">
                <li><code>summary($productId, $days)</code><span>Agregaty: otwarcia, wyświetlenia, kliknięcia, wpłaty, przychód, konwersja %.</span></li>
                <li><code>dailyPurchases($productId, $days)</code><span>Seria dziennych zakupów do wykresu.</span></li>
                <li><code>formatPln($grosze)</code><span>Formatowanie kwoty (grosze → zł).</span></li>
            </ul>
        </div>
        <div class="dc-block"><span class="dc-block__label">Tabele</span><div class="dc-tables"><code>events</code><code>orders</code></div></div>
    </section>

    {{-- ============ 25. SCHEMAT BAZY ============ --}}
    <section class="dc-module" id="baza">
        <div class="dc-module__head"><span class="dc-module__num">25.</span><h2>Pełny schemat bazy danych</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">18 migracji budujących ~25 tabel w trzech bazach (multi-tenant). Poniżej wszystkie tabele z kluczowymi kolumnami.</p>
        <div class="dc-block"><span class="dc-block__label">Baza nfc_pay (bramka)</span>
            <table class="dc-table">
                <thead><tr><th>Tabela</th><th>Kluczowe kolumny</th></tr></thead>
                <tbody>
                    <tr><td><code>shops</code></td><td>name, slug, base_url, api_key, payment_mode</td></tr>
                    <tr><td><code>tags</code></td><td>shop_id, tag_uid, target_url, label, active</td></tr>
                    <tr><td><code>transactions</code></td><td>id (uuid), shop_id, tag_id, product_external_id, amount, currency, status, mode, provider_order_id, paid_at</td></tr>
                    <tr><td><code>events</code></td><td>shop_id, tag_id, transaction_id, type, created_at</td></tr>
                    <tr><td><code>leads</code></td><td>name, email, phone, company, message</td></tr>
                    <tr><td><code>antitheft_checks</code></td><td>shop_id, status, foreign_tags_found, checked_at</td></tr>
                </tbody>
            </table>
        </div>
        <div class="dc-block"><span class="dc-block__label">Baza nfc_shop1 (Taca / church) — także nfc_shop2 (products)</span>
            <table class="dc-table">
                <thead><tr><th>Tabela</th><th>Kluczowe kolumny</th></tr></thead>
                <tbody>
                    <tr><td><code>products</code></td><td>name, city, purpose, slug, description_html, price, tag_uid, main_image, active, phone, website, voivodeship, status, salesperson_id</td></tr>
                    <tr><td><code>product_images</code></td><td>product_id, path, sort</td></tr>
                    <tr><td><code>orders</code></td><td>id (uuid), product_id (nullable), transaction_id, amount, status, paid_at</td></tr>
                    <tr><td><code>events</code></td><td>product_id, type, created_at</td></tr>
                    <tr><td><code>shop_items</code></td><td>slug, name, image, min_amount, is_default, tag_uid, active, sort</td></tr>
                    <tr><td><code>categories</code></td><td>parent_id, slug, label, label_html, intro, icon, source, position, active</td></tr>
                    <tr><td><code>salespeople</code></td><td>name, email, phone, voivodeships (JSON), active</td></tr>
                    <tr><td><code>potential_parishes</code></td><td>name, city, address, voivodeship, denomination, phone, lat, lon, status, salesperson_id, note, called_at</td></tr>
                    <tr><td><code>parish_notes</code></td><td>product_id, type, body, author</td></tr>
                    <tr><td><code>job_positions</code></td><td>title, location, employment_type, description_html, active, sort</td></tr>
                    <tr><td><code>job_applications</code></td><td>job_position_id, name, email, phone, message, cv_path, cv_original_name, is_read, status</td></tr>
                    <tr><td><code>contact_messages</code></td><td>name, email, phone, subject, message, is_read</td></tr>
                </tbody>
            </table>
        </div>
        <div class="dc-block"><span class="dc-block__label">Tabele systemowe (Laravel)</span>
            <div class="dc-tables"><code>users</code><code>cache</code><code>cache_locks</code><code>jobs</code></div>
        </div>
    </section>

    {{-- ============ 26. STACK ============ --}}
    <section class="dc-module" id="stack">
        <div class="dc-module__head"><span class="dc-module__num">26.</span><h2>Stack technologiczny i statystyki</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-lead">Lekki, nowoczesny stos oparty o Laravel 12 — bez ciężkiego frontendu SPA, co przekłada się na szybkość i prostotę utrzymania.</p>
        <div class="dc-block"><span class="dc-block__label">Technologie</span>
            <div class="dc-pills">
                <span class="dc-pill">Laravel 12 (PHP 8.2+)</span>
                <span class="dc-pill">MySQL / MariaDB (3 bazy)</span>
                <span class="dc-pill">Architektura multi-tenant</span>
                <span class="dc-pill">Blade + CSS (bez SPA)</span>
                <span class="dc-pill">Vite 7 + Tailwind 4</span>
                <span class="dc-pill">PayU REST API v2.1</span>
                <span class="dc-pill">BLIK · pay-by-link · 3DS</span>
                <span class="dc-pill">Leaflet (mapy)</span>
                <span class="dc-pill">Quill (WYSIWYG)</span>
                <span class="dc-pill">Kolejki + Joby (async)</span>
                <span class="dc-pill">Webhooki HMAC-SHA256</span>
                <span class="dc-pill">Open Graph / SEO</span>
                <span class="dc-pill">Deploy: rsync + SSL</span>
            </div>
        </div>
        <div class="dc-block"><span class="dc-block__label">Statystyki kodu</span>
            <table class="dc-table">
                <thead><tr><th>Warstwa</th><th>Liczby</th></tr></thead>
                <tbody>
                    <tr><td>Moduły aplikacji</td><td>2 (Gateway · Storefront)</td></tr>
                    <tr><td>Kontrolery</td><td>32 (15 Gateway + 17 Storefront)</td></tr>
                    <tr><td>Modele danych</td><td>19</td></tr>
                    <tr><td>Serwisy i dostawcy płatności</td><td>GatewayClient, ShopStatsService, TransactionService, StatsService + PayUProvider, MockProvider</td></tr>
                    <tr><td>Migracje / tabele</td><td>18 / ~25</td></tr>
                    <tr><td>Widoki Blade</td><td>69</td></tr>
                    <tr><td>Trasy (web · API · webhooki)</td><td>109</td></tr>
                    <tr><td>Arkusze stylów CSS</td><td>6</td></tr>
                    <tr><td>Bazy danych</td><td>3 (multi-tenant)</td></tr>
                    <tr><td>Linie kodu (PHP + Blade + CSS)</td><td>~13 500</td></tr>
                </tbody>
            </table>
        </div>
        <p class="dc-note">Dokument wygenerowany automatycznie na podstawie analizy kodu źródłowego. Moduły oznaczone „Aktywny” działają na produkcji <strong>please-support-me.com</strong>; „Demo” są zbudowane i działają w trybie pokazowym/testowym.</p>
    </section>

</div>

<a href="#gora" class="dc-top" aria-label="Do góry">↑</a>
@endsection
