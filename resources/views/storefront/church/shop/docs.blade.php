@extends('layouts.landing')

@section('title', 'Dokumentacja projektu — SupportME')
@section('meta-description', 'Pełna dokumentacja platformy SupportME — wszystkie moduły, funkcje, panel administracyjny, bramka płatności i architektura systemu.')

@push('head')
<link rel="stylesheet" href="{{ asset('css/docs.css') }}?v={{ substr(md5_file(public_path('css/docs.css')), 0, 10) }}">
@endpush

@section('content')
@php
    $stats = [
        ['n' => '2',        'l' => 'moduły aplikacji'],
        ['n' => '32',       'l' => 'kontrolery'],
        ['n' => '19',       'l' => 'modele danych'],
        ['n' => '~25',      'l' => 'tabel w bazach'],
        ['n' => '69',       'l' => 'widoków (Blade)'],
        ['n' => '109',      'l' => 'tras (web · API · webhooki)'],
        ['n' => '~13 500',  'l' => 'linii kodu'],
        ['n' => '3',        'l' => 'bazy danych (multi-tenant)'],
    ];
    $toc = [
        ['1',  'arch',        'Architektura multi-tenant'],
        ['2',  'sklep',       'Sklep donacyjny NFC (/)'],
        ['3',  'taca',        'Cyfrowa Taca — parafie'],
        ['4',  'main',        'Strona główna /main'],
        ['5',  'inwestorzy',  'Inwestorzy i akcjonariusze'],
        ['6',  'rekrutacja',  'Rekrutacja i kariera'],
        ['7',  'kontakt',     'Kontakt i regulamin'],
        ['8',  'powrot',      'Przepływ płatności i powrót'],
        ['9',  'gateway',     'Bramka płatności (PayU)'],
        ['10', 'panel',       'Panel administracyjny'],
        ['11', 'baza',        'Schemat bazy danych'],
        ['12', 'analityka',   'Eventy i analityka'],
        ['13', 'stack',       'Stack technologiczny'],
    ];
@endphp

<section class="dc-hero">
    <h1>Dokumentacja platformy SupportME</h1>
    <p>Kompletny przegląd tego, co zbudowaliśmy — cyfrowa taca NFC, sklep donacyjny, bramka płatności PayU, system CRM dla parafii, rekrutacja oraz rozbudowany panel administracyjny. Każdy moduł opisany: aktywne, demonstracyjne i przygotowane na przyszłość.</p>
    <div class="dc-hero__meta">Aktualizacja: {{ now()->translatedFormat('j F Y') }} · Laravel 12 · architektura multi-tenant</div>
</section>

<div class="dc-wrap">

    {{-- STATYSTYKI --}}
    <div class="dc-stats">
        @foreach($stats as $s)
            <div class="dc-stat">
                <div class="dc-stat__num">{{ $s['n'] }}</div>
                <div class="dc-stat__label">{{ $s['l'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- SPIS TREŚCI --}}
    <nav class="dc-toc" id="gora">
        <h2>Spis treści</h2>
        <ol>
            @foreach($toc as $t)
                <li><span class="dc-toc__n">{{ $t[0] }}.</span> <a href="#{{ $t[1] }}">{{ $t[2] }}</a></li>
            @endforeach
        </ol>
    </nav>

    <p class="dc-note">
        <strong>Legenda statusów:</strong>
        <span class="dc-badge dc-badge--on">Aktywny</span> — działa na produkcji ·
        <span class="dc-badge dc-badge--demo">Demo</span> — zbudowany, w trybie pokazowym/testowym ·
        <span class="dc-badge dc-badge--off">Osobny tenant</span> — działa pod inną domeną ·
        <span class="dc-badge dc-badge--soon">Gotowy</span> — kod gotowy, włączany na żądanie.
    </p>

    {{-- 1. ARCHITEKTURA --}}
    <section class="dc-section" id="arch">
        <div class="dc-section__head"><h2>1. Architektura multi-tenant</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-section__lead">Jedna aplikacja Laravel obsługuje wiele niezależnych serwisów. Host żądania (domena) decyduje, który moduł i która baza danych zostaną użyte — rozstrzyga to middleware <code>ResolveTenant</code>, jako pierwszy w łańcuchu (przed sesją i CSRF). Dzięki temu ten sam kod napędza bramkę płatności, cyfrową tacę parafialną i sklep z gadżetami — każde na własnej bazie i z własnym wyglądem.</p>
        <div class="dc-arch">
            <div class="dc-arch__node">
                <h4>Bramka płatności</h4>
                <code>pay.please-support-me.com → nfc_pay</code>
                <p>Moduł <strong>Gateway</strong>. Centralne przetwarzanie transakcji, integracja z PayU, webhooki, panel zarządzania tagami i sklepami.</p>
            </div>
            <div class="dc-arch__node">
                <h4>Cyfrowa Taca (parafie)</h4>
                <code>please-support-me.com → nfc_shop1</code>
                <p>Moduł <strong>Storefront</strong>, tryb <code>church</code>. Strona główna, sklep donacyjny, parafie, CRM, rekrutacja. Główny serwis produkcyjny.</p>
            </div>
            <div class="dc-arch__node">
                <h4>Sklep gadżetów</h4>
                <code>shop2.please-support-me.com → nfc_shop2</code>
                <p>Moduł <strong>Storefront</strong>, tryb <code>products</code>. Sprzedaż merchu (kubki, koszulki, piny) ze stałą ceną. Osobny tenant.</p>
            </div>
        </div>
        <div class="dc-cards" style="margin-top:20px">
            <div class="dc-card">
                <h3>Jak działa rozwiązywanie tenanta</h3>
                <p>Middleware czyta host żądania, mapuje go na tenant (moduł + baza + klucz API bramki), dynamicznie przełącza połączenie MySQL i ścieżki widoków — wszystko per żądanie, bez restartu.</p>
                <div class="dc-card__files">app/Http/Middleware/ResolveTenant.php · config/tenants.php · config/platform.php</div>
            </div>
            <div class="dc-card">
                <h3>Dwa niezależne połączenia DB</h3>
                <p>Połączenie <code>mysql</code> przełączane per host (sklepy), oraz dedykowane <code>gateway</code> → <code>nfc_pay</code>, z którego modele bramki zawsze czytają niezależnie od tenanta.</p>
                <div class="dc-card__files">config/database.php · 3 bazy: nfc_pay · nfc_shop1 · nfc_shop2</div>
            </div>
        </div>
    </section>

    {{-- 2. SKLEP NFC --}}
    <section class="dc-section" id="sklep">
        <div class="dc-section__head"><h2>2. Sklep donacyjny NFC</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-section__lead">Strona główna serwisu (<code>/</code>). Lista produktów oznaczonych tagami NFC, każdy z własną minimalną kwotą. Domyślny produkt „Serduszko” (min. 1 zł) otwiera się automatycznie w modalu. Kwota jest edytowalna, a walidacja minimum działa po stronie przeglądarki i serwera.</p>
        <div class="dc-cards">
            <div class="dc-card">
                <div class="dc-card__top"><h3>Lista produktów + modal KUP</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <span class="dc-card__route">GET /</span>
                <p>Siatka produktów (Serduszko, Kubek, Koszulka, Pin, Brelok). Klik karty → modal z edytowalną kwotą i przyciskiem KUP. Auto-otwarcie domyślnego produktu raz na sesję lub przez <code>?produkt={slug}</code>.</p>
                <ul>
                    <li>Edytowalna kwota „1 zł” z walidacją ≥ minimum</li>
                    <li>Domyślne „Serduszko” (flaga <code>is_default</code>)</li>
                    <li>Grafika produktu / serce SVG w modalu</li>
                </ul>
                <div class="dc-card__files">CompanyStoreController · shop/sklep.blade.php · sklep.css</div>
            </div>
            <div class="dc-card">
                <div class="dc-card__top"><h3>Zakup → płatność</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <span class="dc-card__route">POST /sklep/kup/{slug}</span>
                <p>Walidacja kwoty (≥ min produktu, ≤ 5000 zł) po stronie serwera, utworzenie zamówienia i jedna transakcja w bramce PayU. Ceny liczone wyłącznie serwerowo.</p>
                <ul>
                    <li>Walidacja front (JS, komunikat) + back (blokująca)</li>
                    <li>Tag NFC produktu kieruje wprost na sklep z modalem</li>
                    <li>Łatwe dodawanie produktów z panelu</li>
                </ul>
                <div class="dc-card__files">Model: ShopItem · tabela shop_items · panel: ShopItemController</div>
            </div>
        </div>
    </section>

    {{-- 3. TACA --}}
    <section class="dc-section" id="taca">
        <div class="dc-section__head"><h2>3. Cyfrowa Taca — parafie</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-section__lead">Serce platformy. Darczyńca zbliża telefon do znacznika NFC w kościele, trafia na stronę parafii, wybiera kwotę i płaci, a na końcu widzi ekran „Bóg zapłać”. Parafie, ich opisy, zdjęcia i cele zbiórek zarządzane są w panelu CRM.</p>
        <div class="dc-cards">
            <div class="dc-card">
                <div class="dc-card__top"><h3>Wejście z tagu NFC</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <span class="dc-card__route">GET /t/{tag_uid}</span>
                <p>Inteligentny routing: szuka parafii po <code>tag_uid</code> → przekierowuje na jej stronę; jeśli to tag produktu sklepu → otwiera modal sklepu; nieznany tag → strona błędu. Każde wejście loguje event <code>tag_open</code> (lokalnie i do bramki).</p>
                <div class="dc-card__files">StorefrontController::tag() · Job: SendGatewayEvent</div>
            </div>
            <div class="dc-card">
                <div class="dc-card__top"><h3>Strona parafii + wybór kwoty</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <span class="dc-card__route">GET /p/{slug} · POST /p/{slug}/kup</span>
                <p>Zdjęcie parafii, cel zbiórki, opis. Presety kwot (10/20/50/100/200 zł) + pole „inna kwota”. Walidacja 2–5000 zł, utworzenie zamówienia i transakcji PayU.</p>
                <div class="dc-card__files">shop/product.blade.php · Model: Product, Order, Event</div>
            </div>
            <div class="dc-card">
                <div class="dc-card__top"><h3>Kategorie „Kogo wspieramy?”</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <span class="dc-card__route">GET /kategoria/{slug}</span>
                <p>Lista parafii z wyszukiwarką (nazwa, miasto z autouzupełnianiem, województwo). Filtry zapisywane w URL (deeplink). Drzewo kategorii budowane dynamicznie z bazy.</p>
                <div class="dc-card__files">shop/category.blade.php · Model: Category</div>
            </div>
            <div class="dc-card">
                <div class="dc-card__top"><h3>Ekran „Bóg zapłać”</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <span class="dc-card__route">GET /zwrot/{order}</span>
                <p>Po płatności: animacja świecy + potwierdzenie, kwota i nazwa parafii. Stany: sukces, oczekiwanie (polling co 2 s), niepowodzenie z opcją ponowienia.</p>
                <div class="dc-card__files">OrderReturnController · return-success / -pending / -failure</div>
            </div>
        </div>
    </section>

    {{-- 4. MAIN --}}
    <section class="dc-section" id="main">
        <div class="dc-section__head"><h2>4. Strona główna /main</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-section__lead">Landing „Technologia, która pomaga czynić dobro” — zbudowany pixel-perfect z makiet Figmy. Sekcje dynamiczne z bazy danych.</p>
        <div class="dc-cards">
            <div class="dc-card">
                <div class="dc-card__top"><h3>Sekcje landingu</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <span class="dc-card__route">GET /main</span>
                <p>Hero z misją, sekcja „Kogo wspieramy?” (kafelki kategorii z bazy), „Jak to działa?” w 4 krokach (NFC → wybór → płatność → podziękowanie), tekst zamykający, stopka.</p>
                <ul>
                    <li>Pixel-perfect z Figmy (desktop + mobile)</li>
                    <li>Kategorie i ikony zarządzane z panelu</li>
                </ul>
                <div class="dc-card__files">StorefrontController::index() · shop/home.blade.php · landing.css</div>
            </div>
            <div class="dc-card">
                <div class="dc-card__top"><h3>Modal „Wesprzyj” + podgląd linku (OG)</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <span class="dc-card__route">nagłówek globalny</span>
                <p>Przycisk „Wesprzyj” w nagłówku otwiera modal domyślnego produktu. Tagi Open Graph / Twitter z grafiką serca z logo — ładny podgląd przy udostępnianiu na WhatsApp / Facebook.</p>
                <div class="dc-card__files">layouts/landing.blade.php · img/og-supportme.png</div>
            </div>
        </div>
    </section>

    {{-- 5. INWESTORZY --}}
    <section class="dc-section" id="inwestorzy">
        <div class="dc-section__head"><h2>5. Inwestorzy i akcjonariusze</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-section__lead">Strona prezentująca inwestorów i strukturę kapitałową — „Wierzymy, że kapitał może służyć dobru”. Zbudowana 1:1 z makiety Figmy.</p>
        <div class="dc-cards">
            <div class="dc-card">
                <div class="dc-card__top"><h3>Akcjonariusze</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <span class="dc-card__route">GET /inwestorzy</span>
                <p>Hero, sekcja misji kapitałowej i karty akcjonariuszy z kwotami inwestycji (kapitałowa + wsparcie usługowe). Responsywny układ, pastelowe avatary.</p>
                <div class="dc-card__files">shop/inwestorzy.blade.php · inwestorzy.css</div>
            </div>
        </div>
    </section>

    {{-- 6. REKRUTACJA --}}
    <section class="dc-section" id="rekrutacja">
        <div class="dc-section__head"><h2>6. Rekrutacja i kariera</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-section__lead">Pełny system rekrutacyjny: publiczna lista ofert zarządzana z panelu, strony ofert, formularz aplikacji z uploadem CV (przechowywanym na prywatnym dysku) i powiadomienie e-mail z załączonym CV.</p>
        <div class="dc-cards">
            <div class="dc-card">
                <div class="dc-card__top"><h3>Lista i strony ofert</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <span class="dc-card__route">GET /praca · /praca/oferta/{position}</span>
                <p>Lista aktywnych stanowisk (z panelu), karty z typem zatrudnienia i lokalizacją, pełny opis oferty (WYSIWYG), sekcja „inne oferty”.</p>
                <div class="dc-card__files">CareersController · Model: JobPosition · praca/oferta.blade.php</div>
            </div>
            <div class="dc-card">
                <div class="dc-card__top"><h3>Aplikacja z CV</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <span class="dc-card__route">POST /praca/{position}/aplikuj</span>
                <p>Formularz z uploadem CV (PDF/DOC/DOCX, do 5 MB, walidacja MIME), zgoda RODO. Zapis do bazy, CV na prywatnym dysku, podgląd i pobranie w panelu.</p>
                <ul>
                    <li>Aplikacja na ofertę lub spontaniczna</li>
                    <li>Statusy: do sprawdzenia / zaakceptowany / odrzucony</li>
                </ul>
                <div class="dc-card__files">Model: JobApplication · Mail: JobApplicationReceived</div>
            </div>
        </div>
        <p class="dc-note" style="margin-top:20px"><strong>Powiadomienia e-mail z CV</strong> <span class="dc-badge dc-badge--soon">Gotowy</span> — kod wysyłki maila (z załączonym CV na adres rekrutacyjny) jest gotowy; na produkcji działa tryb „tylko panel” (zgłoszenia trafiają do skrzynki w panelu). Wysyłkę mailem włącza się konfiguracją mailera.</p>
    </section>

    {{-- 7. KONTAKT --}}
    <section class="dc-section" id="kontakt">
        <div class="dc-section__head"><h2>7. Kontakt i regulamin</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-section__lead">Formularz kontaktowy z zapisem wiadomości do panelu oraz pełny regulamin sklepu internetowego przeniesiony 1:1 z dokumentu prawnego.</p>
        <div class="dc-cards">
            <div class="dc-card">
                <div class="dc-card__top"><h3>Formularz kontaktowy</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <span class="dc-card__route">GET /kontakt · POST /kontakt</span>
                <p>Imię, e-mail, telefon, temat (auto-uzupełniany z linku z oferty pracy), wiadomość. Walidacja serwerowa, zapis do bazy, skrzynka w panelu z licznikiem nieprzeczytanych.</p>
                <div class="dc-card__files">ContactController · Model: ContactMessage</div>
            </div>
            <div class="dc-card">
                <div class="dc-card__top"><h3>Regulamin</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <span class="dc-card__route">GET /regulamin</span>
                <p>Kompletny regulamin (12 paragrafów + wzór odstąpienia): postanowienia, zamówienia, płatności, zwroty (14 dni), RODO, własność intelektualna, ODR.</p>
                <div class="dc-card__files">shop/regulamin.blade.php · subpages.css</div>
            </div>
        </div>
    </section>

    {{-- 8. PRZEPŁYW PŁATNOŚCI --}}
    <section class="dc-section" id="powrot">
        <div class="dc-section__head"><h2>8. Przepływ płatności i powrót</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-section__lead">Pełny, wielowarstwowy cykl płatności od zbliżenia telefonu do potwierdzenia — z webhookami, aktywnym pollingiem i podpisem kryptograficznym dla bezpieczeństwa.</p>
        <table class="dc-table">
            <thead><tr><th>Krok</th><th>Co się dzieje</th><th>Technika</th></tr></thead>
            <tbody>
                <tr><td>1. Inicjacja</td><td>Sklep tworzy transakcję w bramce na wybraną kwotę</td><td><code>POST /api/v1/transactions</code> (X-Api-Key)</td></tr>
                <tr><td>2. Autoryzacja</td><td>Klient płaci BLIK-iem lub pay-by-link (app banku)</td><td>PayU REST v2.1 · BLIK · 3DS</td></tr>
                <tr><td>3. Webhook PayU</td><td>PayU powiadamia bramkę o wyniku</td><td><code>POST /webhooks/payu</code> · OpenPayu-Signature</td></tr>
                <tr><td>4. Webhook do sklepu</td><td>Bramka powiadamia sklep, zamówienie → opłacone</td><td><code>POST /webhooks/gateway</code> · HMAC-SHA256</td></tr>
                <tr><td>5. Polling (gwarancja)</td><td>Ekran zwrotu odpytuje status, bramka rekonsyliuje z PayU</td><td><code>GET /zwrot/{order}/status</code></td></tr>
            </tbody>
        </table>
    </section>

    {{-- 9. GATEWAY --}}
    <section class="dc-section" id="gateway">
        <div class="dc-section__head"><h2>9. Bramka płatności (PayU)</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-section__lead">Osobny moduł <strong>Gateway</strong> — samodzielna bramka płatnicza działająca jako odrębna aplikacja (<code>pay.please-support-me.com</code>, baza <code>nfc_pay</code>). Integruje PayU, obsługuje wiele sklepów, tagi NFC, statystyki i webhooki. 15 kontrolerów, własne API i panel.</p>
        <div class="dc-cards">
            <div class="dc-card">
                <div class="dc-card__top"><h3>Integracja PayU</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <p>Pełna integracja z PayU REST API v2.1: tworzenie zamówień (OAuth), pobieranie statusu (rekonsyliacja), capture (potwierdzenie), pay-by-links, BLIK Level 0. Tryby: classic (3DS) i app2app (BLIK/PBL).</p>
                <div class="dc-card__files">Payments/PayUProvider.php · PaymentProviderInterface · TransactionService</div>
            </div>
            <div class="dc-card">
                <div class="dc-card__top"><h3>API + webhooki</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <span class="dc-card__route">POST /api/v1/transactions · /api/v1/events</span>
                <p>REST API z autoryzacją kluczem, webhooki wychodzące do sklepów podpisane HMAC-SHA256, weryfikacja podpisu OpenPayu od PayU. Idempotentna aktualizacja statusów.</p>
                <div class="dc-card__files">Api/TransactionController · Api/EventController · WebhookController</div>
            </div>
            <div class="dc-card">
                <div class="dc-card__top"><h3>Panel bramki</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <p>Zarządzanie sklepami i ich kluczami API, tagami NFC (przypisania), statystyki transakcji i przychodu per sklep, dashboard.</p>
                <div class="dc-card__files">Panel/ShopController · TagController · StatsController · DashboardController</div>
            </div>
            <div class="dc-card">
                <div class="dc-card__top"><h3>Leady z landingu</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <span class="dc-card__route">POST /lead · /panel/leads/export</span>
                <p>Formularz kontaktowy na stronie bramki zapisuje leady (nazwa, e-mail, firma, wiadomość). Panel z eksportem do CSV.</p>
                <div class="dc-card__files">LandingController · Panel/LeadController · Model: Lead</div>
            </div>
            <div class="dc-card">
                <div class="dc-card__top"><h3>Tryb testowy płatności</h3><span class="dc-badge dc-badge--demo">Demo</span></div>
                <p>Alternatywny <code>MockProvider</code> i ekran mock-płatności pozwalają testować cały przepływ bez realnej bramki PayU — przydatne na środowiskach testowych.</p>
                <div class="dc-card__files">Payments/MockProvider.php · MockPaymentController</div>
            </div>
            <div class="dc-card">
                <div class="dc-card__top"><h3>Moduł anti-theft</h3><span class="dc-badge dc-badge--demo">Demo</span></div>
                <p>Szkielet wykrywania obcych tagów NFC (kontrola integralności znaczników). Obecnie tryb pokazowy (zwraca status OK) — gotowy do rozbudowy.</p>
                <div class="dc-card__files">Panel/AntiTheftController · Model: AntitheftCheck</div>
            </div>
        </div>
    </section>

    {{-- 10. PANEL --}}
    <section class="dc-section" id="panel">
        <div class="dc-section__head"><h2>10. Panel administracyjny</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-section__lead">Rozbudowany panel (<code>/panel</code>) z logowaniem — 11 sekcji obejmujących sprzedaż, CRM parafii z mapą pokrycia, zarządzanie produktami, rekrutację i skrzynki wiadomości. Ponad 50 tras chronionych autoryzacją.</p>
        <div class="dc-cards">
            <div class="dc-card">
                <div class="dc-card__top"><h3>Dashboard</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <p>Pulpit z metrykami: otwarcia tagów NFC, wyświetlenia, kliknięcia „Kup”, opłacone zamówienia, przychód, konwersja %, wykres dziennej sprzedaży (30 dni).</p>
                <div class="dc-card__files">Panel/DashboardController · ShopStatsService</div>
            </div>
            <div class="dc-card">
                <div class="dc-card__top"><h3>Parafie + CRM</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <p>Pełny CRUD parafii: galeria zdjęć, opis WYSIWYG, statystyki per parafia. CRM: statusy (kontakt → test → wdrożenie → aktywna), notatki chronologiczne, przypisany handlowiec.</p>
                <div class="dc-card__files">Panel/ProductController · Model: Product, ParishNote, ProductImage</div>
            </div>
            <div class="dc-card">
                <div class="dc-card__top"><h3>Parafie do obdzwonienia + mapa</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <p>Lista leadów parafialnych z filtrami (województwo, status, handlowiec, telefon) i lejkiem obdzwaniania. Interaktywna <strong>mapa pokrycia</strong> (Leaflet + klastrowanie), liczniki per województwo i status.</p>
                <div class="dc-card__files">Panel/PotentialParishController · coverage/map.blade.php · Model: PotentialParish</div>
            </div>
            <div class="dc-card">
                <div class="dc-card__top"><h3>Kategorie wsparcia</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <p>Drzewo hierarchiczne „Kogo wspieramy?” — zagnieżdżanie, zmiana kolejności (reorder), ikony, ochrona przed cyklami. Steruje sekcjami na stronie głównej.</p>
                <div class="dc-card__files">Panel/CategoryController · Model: Category</div>
            </div>
            <div class="dc-card">
                <div class="dc-card__top"><h3>Handlowcy</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <p>CRUD handlowców z przypisaniem obsługiwanych województw (16 woj.) i licznikiem przypisanych parafii. Integracja z CRM i mapą.</p>
                <div class="dc-card__files">Panel/SalespersonController · Model: Salesperson</div>
            </div>
            <div class="dc-card">
                <div class="dc-card__top"><h3>Sklep — produkty NFC</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <p>CRUD produktów donacyjnych: minimalna kwota, tag NFC, produkt domyślny (jeden), upload grafiki, kolejność i aktywność.</p>
                <div class="dc-card__files">Panel/ShopItemController · Model: ShopItem</div>
            </div>
            <div class="dc-card">
                <div class="dc-card__top"><h3>Stanowiska pracy</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <p>CRUD ofert pracy z opisem WYSIWYG, lokalizacją, typem zatrudnienia, kolejnością i licznikiem aplikacji per oferta.</p>
                <div class="dc-card__files">Panel/PositionController · Model: JobPosition</div>
            </div>
            <div class="dc-card">
                <div class="dc-card__top"><h3>Skrzynki: aplikacje i wiadomości</h3><span class="dc-badge dc-badge--on">Aktywny</span></div>
                <p>Zgłoszenia rekrutacyjne (pobieranie CV, statusy, filtry) oraz wiadomości z formularza kontaktowego — z licznikami nieprzeczytanych.</p>
                <div class="dc-card__files">Panel/ApplicationController · Panel/MessageController</div>
            </div>
        </div>
    </section>

    {{-- 11. BAZA --}}
    <section class="dc-section" id="baza">
        <div class="dc-section__head"><h2>11. Schemat bazy danych</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-section__lead">18 migracji budujących ~25 tabel w trzech bazach. Poniżej najważniejsze tabele i co przechowują.</p>
        <table class="dc-table">
            <thead><tr><th>Tabela</th><th>Baza</th><th>Przechowuje</th></tr></thead>
            <tbody>
                <tr><td><code>shops</code></td><td>nfc_pay</td><td>Sklepy w bramce — klucz API, tryb płatności</td></tr>
                <tr><td><code>tags</code></td><td>nfc_pay</td><td>Tagi NFC — przypisanie do sklepu, etykieta, aktywność</td></tr>
                <tr><td><code>transactions</code></td><td>nfc_pay</td><td>Transakcje PayU — kwota, status, ID u dostawcy, daty</td></tr>
                <tr><td><code>events</code></td><td>nfc_pay</td><td>Zdarzenia transakcji (otwarcie, start, sukces, błąd)</td></tr>
                <tr><td><code>leads</code></td><td>nfc_pay</td><td>Leady z landingu bramki</td></tr>
                <tr><td><code>antitheft_checks</code></td><td>nfc_pay</td><td>Kontrole integralności tagów (demo)</td></tr>
                <tr><td><code>products</code></td><td>nfc_shop1</td><td>Parafie — nazwa, miasto, cel, opis, tag NFC, pola CRM</td></tr>
                <tr><td><code>product_images</code></td><td>nfc_shop1</td><td>Galeria zdjęć parafii</td></tr>
                <tr><td><code>orders</code></td><td>nfc_shop1</td><td>Zamówienia/wpłaty — kwota, status, transakcja</td></tr>
                <tr><td><code>events</code></td><td>nfc_shop1</td><td>Analityka sklepu (tag_open, page_view, buy_click, purchase)</td></tr>
                <tr><td><code>shop_items</code></td><td>nfc_shop1</td><td>Produkty sklepu NFC — min. kwota, domyślny, tag</td></tr>
                <tr><td><code>categories</code></td><td>nfc_shop1</td><td>Drzewo kategorii „Kogo wspieramy?”</td></tr>
                <tr><td><code>salespeople</code></td><td>nfc_shop1</td><td>Handlowcy + obsługiwane województwa</td></tr>
                <tr><td><code>potential_parishes</code></td><td>nfc_shop1</td><td>Leady parafii (OSM) — CRM, współrzędne, status</td></tr>
                <tr><td><code>parish_notes</code></td><td>nfc_shop1</td><td>Notatki CRM do parafii (historia kontaktów)</td></tr>
                <tr><td><code>job_positions</code></td><td>nfc_shop1</td><td>Oferty pracy</td></tr>
                <tr><td><code>job_applications</code></td><td>nfc_shop1</td><td>Zgłoszenia rekrutacyjne + CV + status</td></tr>
                <tr><td><code>contact_messages</code></td><td>nfc_shop1</td><td>Wiadomości z formularza kontaktowego</td></tr>
            </tbody>
        </table>
    </section>

    {{-- 12. ANALITYKA --}}
    <section class="dc-section" id="analityka">
        <div class="dc-section__head"><h2>12. Eventy i analityka</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-section__lead">System rejestruje każde istotne zdarzenie — od zbliżenia telefonu po finalną wpłatę — co zasila statystyki w panelu i pozwala mierzyć konwersję.</p>
        <div class="dc-cards">
            <div class="dc-card">
                <h3>Lejek zdarzeń (sklep)</h3>
                <ul>
                    <li><code>tag_open</code> — zbliżenie telefonu do tagu NFC</li>
                    <li><code>page_view</code> — wyświetlenie strony parafii/produktu</li>
                    <li><code>buy_click</code> — kliknięcie „Wesprzyj / Kup”</li>
                    <li><code>purchase</code> — potwierdzona wpłata</li>
                </ul>
                <div class="dc-card__files">Model: Event · ShopStatsService · indeks (product_id, type, created_at)</div>
            </div>
            <div class="dc-card">
                <h3>Metryki w panelu</h3>
                <ul>
                    <li>Otwarcia, wyświetlenia, kliknięcia, wpłaty</li>
                    <li>Przychód łączny i z 30 dni</li>
                    <li>Współczynnik konwersji %</li>
                    <li>Wykres dziennej sprzedaży</li>
                </ul>
                <div class="dc-card__files">Dashboard · statystyki per parafia</div>
            </div>
        </div>
    </section>

    {{-- 13. STACK --}}
    <section class="dc-section" id="stack">
        <div class="dc-section__head"><h2>13. Stack technologiczny</h2><span class="dc-badge dc-badge--on">Aktywny</span></div>
        <p class="dc-section__lead">Nowoczesny, lekki stos oparty o Laravel 12, bez zbędnych zależności frontendowych — szybki i łatwy w utrzymaniu.</p>
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
            <span class="dc-pill">Libre Baskerville · Inter · Fraunces</span>
            <span class="dc-pill">Deploy: rsync + SSL</span>
        </div>
        <table class="dc-table" style="margin-top:24px">
            <thead><tr><th>Warstwa</th><th>Liczby</th></tr></thead>
            <tbody>
                <tr><td>Moduły aplikacji</td><td>2 (Gateway · Storefront)</td></tr>
                <tr><td>Kontrolery</td><td>32</td></tr>
                <tr><td>Modele danych</td><td>19</td></tr>
                <tr><td>Migracje / tabele</td><td>18 / ~25</td></tr>
                <tr><td>Widoki Blade</td><td>69</td></tr>
                <tr><td>Trasy (web · API · webhooki)</td><td>109</td></tr>
                <tr><td>Arkusze stylów CSS</td><td>6</td></tr>
                <tr><td>Bazy danych</td><td>3 (multi-tenant)</td></tr>
                <tr><td>Linie kodu (PHP + Blade + CSS)</td><td>~13 500</td></tr>
            </tbody>
        </table>
    </section>

    <p class="dc-note" style="margin-top:8px">Dokument wygenerowany automatycznie na podstawie analizy kodu źródłowego. Wszystkie moduły oznaczone „Aktywny” działają na produkcji <strong>please-support-me.com</strong>.</p>

</div>

<a href="#gora" class="dc-top" aria-label="Do góry">↑</a>
@endsection
