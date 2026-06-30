@extends('layouts.landing')

@section('title', 'Samouczek — modyfikacja stron z Claude · SupportME')
@section('meta-description', 'Jak modyfikować podstrony SupportME z pomocą Claude: dostęp do serwera (SSH/Google Cloud), konfiguracja Figmy (Dev Mode + MCP) i pisanie promptów.')

@push('head')
<link rel="stylesheet" href="{{ asset('css/docs.css') }}?v={{ substr(md5_file(public_path('css/docs.css')), 0, 10) }}">
@endpush

@push('head')
<style>
/* Page-scoped: siatka kart przeglądu modułów (sekcja 15). Tylko ten plik. */
.dc-modgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:14px;margin:1.4rem 0 .4rem}
.dc-modcard{border:1px solid rgba(0,0,0,.10);border-radius:12px;padding:14px 16px;background:rgba(255,255,255,.55);box-shadow:0 1px 2px rgba(0,0,0,.04)}
.dc-modcard h4{margin:.5rem 0 .35rem;font-size:1rem;line-height:1.25}
.dc-modcard p{margin:0;font-size:.9rem;opacity:.85;line-height:1.45}
.dc-modcard code{font-size:.82em}
.dc-modcard__tag{display:inline-block;font-size:.7rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;padding:.18em .6em;border-radius:999px;line-height:1.4}
.dc-modcard__tag--pub{background:#e8f3ec;color:#1f7a45}
.dc-modcard__tag--adm{background:#eceaf6;color:#4a3f9e}
@media (prefers-color-scheme:dark){
  .dc-modcard{border-color:rgba(255,255,255,.12);background:rgba(255,255,255,.04)}
  .dc-modcard__tag--pub{background:rgba(40,160,90,.18);color:#7fd6a3}
  .dc-modcard__tag--adm{background:rgba(120,100,230,.18);color:#b6acf2}
}
</style>
@endpush

@section('content')
@php
    $toc = [
        ['1','fakty','Co jest gdzie — szybkie fakty'],
        ['2','dostep','Dostęp do serwera (SSH / Google Cloud)'],
        ['3','deploy','Wdrażanie zmian — 2 warianty'],
        ['4','figma','Konfiguracja Figmy (Dev Mode + MCP)'],
        ['5','prompty','Jak pisać prompty do Claude'],
            ['6','infra','Infrastruktura — minimalna i zalecana'],
        ['7','serwer','Stawianie serwera od zera (Debian 12)'],
        ['8','nginx','Nginx + PHP-FPM'],
        ['9','baza-setup','Baza danych — MySQL / MariaDB'],
        ['10','laravel','Wdrożenie aplikacji Laravel'],
        ['11','uslugi','Usługi w tle: kolejki, scheduler, Redis'],
        ['12','ssl-backup','SSL, backupy, monitoring i bezpieczeństwo'],
        ['13','bezpieczenstwo','Bezpieczeństwo, zasady i zastrzeżenia'],
        ['14','figma-pro','Praca z Figmą — pełny przewodnik'],
        ['15','moduly','Moduły systemu — co robią i jak korzystać'],
];
@endphp

<section class="dc-hero">
    <h1>Samouczek: modyfikacja stron z Claude</h1>
    <p>Krok po kroku: jak skonfigurować dostęp do serwera na Google Cloud, podłączyć Figmę w trybie Dev przez MCP i jak formułować polecenia, żeby Claude sprawnie zmieniał kolejne podstrony i wdrażał je na produkcję.</p>
    <div class="dc-hero__meta">please-support-me.com · serwer Google Cloud · Laravel 12</div>
</section>

<div class="dc-wrap">

    <nav class="dc-toc" id="gora">
        <h2>Spis treści</h2>
        <ol>
            @foreach($toc as $t)
                <li><span class="dc-toc__n">{{ $t[0] }}.</span> <a href="#{{ $t[1] }}">{{ $t[2] }}</a></li>
            @endforeach
        </ol>
    </nav>

    {{-- 1. FAKTY --}}
    <section class="dc-module" id="fakty">
        <div class="dc-module__head"><span class="dc-module__num">1.</span><h2>Co jest gdzie — szybkie fakty</h2></div>
        <p class="dc-lead">Zanim cokolwiek zmienisz, warto wiedzieć, gdzie żyje projekt. Te dane podajesz Claude (albo trzyma je w pamięci).</p>
        <table class="dc-table">
            <thead><tr><th>Element</th><th>Wartość</th></tr></thead>
            <tbody>
                <tr><td data-label="Element">Adres IP serwera</td><td data-label="Wartość"><span class="dc-inline">34.118.46.252</span></td></tr>
                <tr><td data-label="Element">Użytkownik SSH</td><td data-label="Wartość"><span class="dc-inline">root</span></td></tr>
                <tr><td data-label="Element">Projekt Google Cloud</td><td data-label="Wartość"><span class="dc-inline">please-support-me-499509</span></td></tr>
                <tr><td data-label="Element">Nazwa instancji (VM)</td><td data-label="Wartość"><span class="dc-inline">instance-20260615-112018</span></td></tr>
                <tr><td data-label="Element">Strefa (zone)</td><td data-label="Wartość"><span class="dc-inline">europe-central2-a</span></td></tr>
                <tr><td data-label="Element">Katalog aplikacji na serwerze</td><td data-label="Wartość"><span class="dc-inline">/var/www/support-me</span></td></tr>
                <tr><td data-label="Element">System / PHP</td><td data-label="Wartość">Debian 12 · PHP 8.2 · MySQL/MariaDB</td></tr>
                <tr><td data-label="Element">Domeny</td><td data-label="Wartość">please-support-me.com (Taca/sklep) · pay.please-support-me.com (bramka) · shop2.please-support-me.com</td></tr>
                <tr><td data-label="Element">Repozytorium (lokalnie)</td><td data-label="Wartość"><span class="dc-inline">/var/www/pay/unified</span> → GitHub</td></tr>
            </tbody>
        </table>
        <div class="dc-note">Modyfikacje robi się <strong>lokalnie</strong> w repozytorium, a następnie <strong>wysyła na serwer</strong> (deploy — sekcja 3). Nie edytuje się plików bezpośrednio na produkcji.</div>
    </section>

    {{-- 2. DOSTĘP --}}
    <section class="dc-module" id="dostep">
        <div class="dc-module__head"><span class="dc-module__num">2.</span><h2>Dostęp do serwera (SSH / Google Cloud)</h2></div>
        <p class="dc-lead">Serwer stoi na Google Cloud (Compute Engine). Logujesz się po SSH jako <span class="dc-inline">root</span>. „Certyfikaty”, o których myślisz, to w praktyce <strong>klucze SSH</strong> — para plików: prywatny (zostaje na Twoim komputerze) i publiczny (ląduje na serwerze). Poniżej dwie drogi: prostsza przez narzędzie Google (<code>gcloud</code>) i ręczna (własny klucz).</p>

        <h3 class="dc-sub">Sposób A — przez Google Cloud SDK (zalecany)</h3>
        <p>Google samo wygeneruje i wgra klucz. Najmniej ręcznej roboty.</p>
        <ol class="dc-steps">
            <li><strong>Zainstaluj Google Cloud CLI</strong> na swoim komputerze — pakiet <code>gcloud</code> ze strony cloud.google.com/sdk/docs/install (Windows/macOS/Linux).</li>
            <li><strong>Zaloguj się kontem Google</strong>, które ma dostęp do projektu:
                <pre class="dc-pre"><span class="p">$</span> gcloud auth login
<span class="p">$</span> gcloud config set project <i>please-support-me-499509</i></pre>
            </li>
            <li><strong>Połącz się z maszyną</strong> (gcloud sam utworzy klucz <code>~/.ssh/google_compute_engine</code> i wgra go do VM):
                <pre class="dc-pre"><span class="p">$</span> gcloud compute ssh <i>instance-20260615-112018</i> \
      --zone <i>europe-central2-a</i> --project <i>please-support-me-499509</i></pre>
            </li>
            <li><strong>Przejdź na root</strong> (gcloud loguje jako Twój użytkownik, nie root):
                <pre class="dc-pre"><span class="p">$</span> sudo -i
<span class="c"># jesteś rootem — sprawdź aplikację:</span>
<span class="p">#</span> cd <i>/var/www/support-me</i> && ls</pre>
            </li>
        </ol>

        <h3 class="dc-sub">Sposób B — własny klucz SSH + konsola Google Cloud</h3>
        <p>Gdy chcesz logować się bezpośrednio <span class="dc-inline">ssh root@34.118.46.252</span> bez gcloud.</p>
        <ol class="dc-steps">
            <li><strong>Wygeneruj parę kluczy</strong> na swoim komputerze (terminal / PowerShell):
                <pre class="dc-pre"><span class="p">$</span> ssh-keygen -t ed25519 -C "twoj-email@example.com" -f <i>~/.ssh/support-me</i>
<span class="c"># powstaną: ~/.ssh/support-me (prywatny) i ~/.ssh/support-me.pub (publiczny)</span></pre>
            </li>
            <li><strong>Skopiuj klucz publiczny:</strong>
                <pre class="dc-pre"><span class="p">$</span> cat <i>~/.ssh/support-me.pub</i></pre>
            </li>
            <li><strong>Wgraj go w konsoli Google Cloud:</strong>
                <p>Google Cloud Console → <em>Compute Engine</em> → <em>VM instances</em> → klik w <code>instance-20260615-112018</code> → <em>Edit</em> → sekcja <em>SSH Keys</em> → <em>Add item</em> → wklej zawartość <code>.pub</code> → <em>Save</em>.</p>
            </li>
            <li><strong>Połącz się:</strong>
                <pre class="dc-pre"><span class="p">$</span> ssh -i <i>~/.ssh/support-me</i> root@<i>34.118.46.252</i></pre>
                <p>Jeśli logowanie na <code>root</code> jest zablokowane, połącz się na swojego użytkownika i zrób <code>sudo -i</code> (jak w sposobie A, krok 4).</p>
            </li>
        </ol>

        <div class="dc-note dc-note--warn"><strong>Bezpieczeństwo:</strong> plik <strong>prywatny</strong> klucza (<code>~/.ssh/support-me</code>, bez <code>.pub</code>) nigdy nie opuszcza Twojego komputera — nie wysyłaj go nikomu ani do repo. Na serwer/konsolę wgrywasz tylko klucz <strong>publiczny</strong> (<code>.pub</code>).</div>

        <h3 class="dc-sub">Szybki test połączenia</h3>
        <pre class="dc-pre"><span class="p">$</span> ssh root@<i>34.118.46.252</i> 'hostname; php -v | head -1'
<span class="c"># powinno zwrócić: instance-20260615-112018 oraz wersję PHP 8.2</span></pre>
    </section>

    {{-- 3. DEPLOY --}}
    <section class="dc-module" id="deploy">
        <div class="dc-module__head"><span class="dc-module__num">3.</span><h2>Wdrażanie zmian — 2 warianty</h2></div>
        <p class="dc-lead">Zmiany możesz wprowadzać na dwa sposoby. <strong>Wariant 1</strong> — Claude pracuje na Twoim komputerze (lokalne repo) i wysyła gotowe pliki na serwer. <strong>Wariant 2</strong> — logujesz się na serwer i uruchamiasz Claude bezpośrednio tam, a on edytuje pliki od razu na miejscu. Oba prowadzą do tego samego efektu na produkcji.</p>

        <h3 class="dc-sub">Część 1 — z lokalnego środowiska (deploy przez SSH)</h3>
        <p>Claude edytuje pliki w lokalnym repo (<code>/var/www/pay/unified</code>), a potem wysyła je na serwer przez <strong>rsync po SSH</strong> i czyści cache. Potrzebny jest dostęp z sekcji 2. Schemat, który Claude wykonuje automatycznie:</p>
        <pre class="dc-pre"><span class="c"># 1) wyślij zmienione pliki na serwer (przykład: jedna podstrona + jej CSS)</span>
<span class="p">$</span> rsync -az resources/views/.../inwestorzy.blade.php public/css/inwestorzy.css \
        root@<i>34.118.46.252</i>:<i>/var/www/support-me/</i>

<span class="c"># 2) wyczyść cache widoków/tras na serwerze</span>
<span class="p">$</span> ssh root@<i>34.118.46.252</i> 'cd /var/www/support-me && php artisan view:clear && php artisan route:clear'

<span class="c"># 3) (jeśli zmiana w bazie) migracja dla właściwego tenanta</span>
<span class="p">$</span> ssh root@<i>34.118.46.252</i> 'cd /var/www/support-me && TENANT=please-support-me.com php artisan migrate --force'</pre>
        <div class="dc-note">Nie musisz tego robić ręcznie — wystarczy, że napiszesz „wdróż na prod”, a Claude wykona wysyłkę, migracje i wyczyści cache, a na końcu sprawdzi, czy strona zwraca <strong>200</strong>.</div>
        <div class="dc-note">Zaleta: produkcja zmienia się dopiero po świadomym „wdróż”, a pliki masz wersjonowane w repo (GitHub) — łatwo cofnąć zmianę.</div>

        <h3 class="dc-sub">Część 2 — bezpośrednio na serwerze (Claude uruchomiony na serwerze)</h3>
        <p>Tu nie ma osobnego deployu: logujesz się na serwer, odpalasz na nim Claude i wpisujesz prompty — Claude edytuje pliki od razu w <code>/var/www/support-me</code>, a zmiany są natychmiast na żywo (cache czyści sam).</p>
        <ol class="dc-steps">
            <li><strong>Zaloguj się na serwer</strong> po SSH (jak w sekcji 2) i wejdź do katalogu projektu:
                <pre class="dc-pre"><span class="p">$</span> ssh root@<i>34.118.46.252</i>
<span class="p">#</span> cd <i>/var/www/support-me</i></pre>
            </li>
            <li><strong>Uruchom Claude</strong> w katalogu projektu:
                <pre class="dc-pre"><span class="p">#</span> claude</pre>
                <p>Przy pierwszym uruchomieniu Claude poprosi o jednorazowe zalogowanie (konto Anthropic / klucz API).</p>
            </li>
            <li><strong>Wpisuj prompty wprost w terminalu</strong> — np. <em>„na stronie /kontakt zmień nagłówek na »Napisz do nas«”</em>. Claude od razu edytuje pliki na serwerze, czyści cache i (na prośbę) sprawdza, czy strona zwraca <strong>200</strong>. Zmiana jest natychmiast widoczna na <span class="dc-inline">please-support-me.com</span> — bez rsync i bez „wdróż”.</li>
        </ol>
        <div class="dc-note dc-note--warn"><strong>Uwaga — to praca bezpośrednio na produkcji.</strong> Nie ma kroku „wdróż”: każda zmiana jest od razu na żywo. Przy większych zmianach poproś Claude o <strong>kopię pliku przed edycją</strong> i o <strong>sprawdzenie strony po zmianie</strong>.</div>
        <div class="dc-note"><strong>Status na dziś:</strong> Claude nie jest jeszcze zainstalowany na tym serwerze — krok „uruchom <code>claude</code>” na razie nie zadziała. Gdy przekażesz <strong>login i hasło</strong> (lub dostęp SSH), podłączymy go tam: instalacja CLI + jednorazowe logowanie. Powyższy opis jest „na zapas”, żeby było wiadomo, jak to będzie wyglądać po podłączeniu.</div>
    </section>

    {{-- 4. FIGMA --}}
    <section class="dc-module" id="figma">
        <div class="dc-module__head"><span class="dc-module__num">4.</span><h2>Konfiguracja Figmy (Dev Mode + MCP)</h2></div>
        <p class="dc-lead">Żeby Claude robił podstrony „1:1 z Figmy”, musi <strong>czytać projekt z Figmy</strong>. Działa to przez serwer <strong>MCP Figmy</strong>, a plik musi być dostępny w <strong>trybie Dev</strong>.</p>
        <ol class="dc-steps">
            <li><strong>Włącz tryb Dev w Figmie.</strong> Otwórz plik projektu w aplikacji Figma i przełącz <em>Dev Mode</em> (przełącznik w prawym górnym rogu). Konto musi mieć miejsce (seat) <strong>Dev</strong> lub <strong>Full</strong> na tym pliku — inaczej MCP zwróci limit.</li>
            <li><strong>Autoryzacja przez MCP.</strong> Połączenie Claude ↔ Figma idzie przez oficjalny serwer <em>Figma MCP</em>. Zaloguj się w nim kontem, które ma dostęp do pliku (to konto autoryzuje odczyt). Bez aktywnej autoryzacji MCP Claude nie zobaczy designu.</li>
            <li><strong>Podaj Claude link albo node-id.</strong> Najlepiej wkleić URL widoku z Figmy (zawiera <code>node-id</code>), np. zaznacz ramkę → <em>Copy link to selection</em>. Claude wyciągnie z tego klucz pliku i identyfikator węzła.</li>
        </ol>
        <div class="dc-note"><strong>Awaryjnie, gdy MCP zwróci limit</strong> (konto ma tylko seat View = 6 odczytów/miesiąc): działa też <strong>Figma REST API</strong> z osobistym tokenem. Token (z uprawnieniem <em>File content: read</em>) zapisuje się w <code>unified/.env</code> jako <code>FIGMA_TOKEN</code> — Claude pobierze wtedy węzły i rendery bez MCP. Token to sekret: trafia tylko do <code>.env</code> (jest w <code>.gitignore</code>), nigdy do repo.</div>
    </section>

    {{-- 5. PROMPTY --}}
    <section class="dc-module" id="prompty">
        <div class="dc-module__head"><span class="dc-module__num">5.</span><h2>Jak pisać prompty do Claude</h2></div>
        <p class="dc-lead">Im konkretniej, tym lepszy efekt. Dobry prompt zawiera trzy rzeczy: <strong>którą podstronę</strong> zmienić, <strong>co</strong> zmienić, i <strong>źródło</strong> (Figma + link/node-id albo opis). Na końcu poproś o wdrożenie i weryfikację.</p>

        <div class="dc-block"><span class="dc-block__label">Szablon</span>
            <pre class="dc-pre">Na stronie <b>/adres-podstrony</b> zmień <b>[co dokładnie]</b>
wg Figmy: <b>[wklejony link / node-id]</b>.
Zrób to <b>1:1 (pixel-perfect)</b>, wdróż na prod i pokaż porównanie.</pre>
        </div>

        <div class="dc-block"><span class="dc-block__label">Przykłady</span>
            <ul>
                <li><em>„Na stronie <strong>/inwestorzy</strong> zmień nagłówek na »Nasi partnerzy« i dodaj trzeciego akcjonariusza wg Figmy: [link]. Wdróż na prod.”</em></li>
                <li><em>„Na <strong>/kontakt</strong> zrób formularz 1:1 z tą ramką Figmy: [node-id]. Pixel-perfect, potem nakładka Figma↔live.”</em></li>
                <li><em>„Na stronie głównej <strong>/</strong> zmień kolor przycisku KUP na granatowy i powiększ czcionkę kwoty. Wdróż i pokaż na telefonie.”</em></li>
                <li><em>„Dodaj nową podstronę <strong>/faq</strong> wg Figmy [link], dorzuć link w stopce, wdróż.”</em></li>
            </ul>
        </div>

        <div class="dc-block"><span class="dc-block__label">Wskazówki</span>
            <ul>
                <li><strong>Adres podstrony</strong> podawaj wprost (np. <code>/praca</code>, <code>/main</code>) — Claude od razu wie, który plik edytować.</li>
                <li><strong>„wg Figmy”</strong> + link działa najlepiej; sam opis słowny też zadziała, ale będzie mniej dokładny.</li>
                <li>Dopisz <strong>„wdróż na prod”</strong>, jeśli zmiana ma od razu pójść na żywo (inaczej Claude może zrobić tylko lokalnie).</li>
                <li>Poproś o <strong>„pokaż pixel-diff / nakładkę”</strong> albo <strong>„sprawdź na telefonie”</strong>, gdy zależy Ci na dokładności wizualnej.</li>
                <li>Możesz prosić o całe akcje naraz: <em>„zmień X, dodaj link w nagłówku, wdróż i scommituj”</em>.</li>
            </ul>
        </div>

        <div class="dc-note">Pełny przegląd wszystkich modułów i podstron znajdziesz w <a href="{{ route('docs') }}">dokumentacji technicznej (/docs)</a> — przyda się, gdy chcesz wskazać Claude konkretny element do zmiany.</div>
    </section>


    {{-- 6. INFRASTRUKTURA --}}
    <section class="dc-module" id="infra">
        <div class="dc-module__head"><span class="dc-module__num">6.</span><h2>Infrastruktura — wariant minimalny i zalecany</h2></div>
        <p class="dc-lead">SupportME to aplikacja <strong>Laravel 12 (PHP 8.2)</strong> z bazą <strong>MySQL/MariaDB</strong> i serwerem <strong>Nginx</strong>. Całość spokojnie mieści się na jednej maszynie wirtualnej. Poniżej dwa profile sprzętowe: <em>minimalny</em> (pilotaż / kilka parafii) oraz <em>zalecany</em> (produkcja z ruchem z tagów NFC i kampaniami).</p>

        <h3 class="dc-sub">Profile maszyny (Google Cloud Compute Engine)</h3>
        <table class="dc-table">
            <thead><tr><th>Parametr</th><th>Minimalny (pilotaż)</th><th>Zalecany (produkcja)</th></tr></thead>
            <tbody>
                <tr><td data-label="Parametr">Typ maszyny</td><td data-label="Minimalny (pilotaż)"><span class="dc-inline">e2-small</span></td><td data-label="Zalecany (produkcja)"><span class="dc-inline">e2-medium</span> / <span class="dc-inline">e2-standard-2</span></td></tr>
                <tr><td data-label="Parametr">vCPU</td><td data-label="Minimalny (pilotaż)">2 (współdzielone)</td><td data-label="Zalecany (produkcja)">2–4 (dedykowane)</td></tr>
                <tr><td data-label="Parametr">RAM</td><td data-label="Minimalny (pilotaż)">2 GB</td><td data-label="Zalecany (produkcja)">4–8 GB</td></tr>
                <tr><td data-label="Parametr">Dysk</td><td data-label="Minimalny (pilotaż)">20 GB SSD (pd-balanced)</td><td data-label="Zalecany (produkcja)">40–80 GB SSD</td></tr>
                <tr><td data-label="Parametr">System</td><td data-label="Minimalny (pilotaż)" colspan="2">Debian 12 „bookworm” (64-bit)</td></tr>
                <tr><td data-label="Parametr">Strefa</td><td data-label="Minimalny (pilotaż)" colspan="2"><span class="dc-inline">europe-central2-a</span> (Warszawa — najniższe opóźnienie dla PL)</td></tr>
                <tr><td data-label="Parametr">Adres IP</td><td data-label="Minimalny (pilotaż)" colspan="2">statyczny zewnętrzny (zarezerwuj, by nie zmienił się po restarcie)</td></tr>
                <tr><td data-label="Parametr">Szacowany koszt</td><td data-label="Minimalny (pilotaż)">~30–45 zł / mc</td><td data-label="Zalecany (produkcja)">~90–180 zł / mc</td></tr>
            </tbody>
        </table>
        <div class="dc-note">Aplikacja jest lekka — wąskim gardłem nie jest CPU, lecz <strong>pamięć</strong> (PHP-FPM + MySQL) i <strong>I/O dysku</strong> przy generowaniu obrazów OG. Na 2 GB RAM koniecznie skonfiguruj <strong>swap</strong> (sekcja 7) — inaczej <code>composer install</code> potrafi paść z błędem braku pamięci.</div>

        <h3 class="dc-sub">Co działa na tej jednej maszynie</h3>
        <ul>
            <li><strong>Nginx</strong> — serwer WWW, terminacja TLS, serwowanie statyków, reverse-proxy do PHP-FPM.</li>
            <li><strong>PHP 8.2 FPM</strong> — wykonywanie aplikacji Laravel (pula procesów).</li>
            <li><strong>MariaDB/MySQL</strong> — bazy multi-tenant: <code>nfc_pay</code> (bramka), <code>nfc_shop1</code> (Taca/church), <code>nfc_shop2</code> (sklep produktowy).</li>
            <li><strong>Redis</strong> (zalecane) — cache, sesje i kolejki zamiast sterownika bazodanowego.</li>
            <li><strong>Supervisor</strong> — utrzymuje proces kolejki (<code>queue:work</code>) przy życiu.</li>
            <li><strong>Cron</strong> — uruchamia harmonogram Laravela (<code>schedule:run</code>) co minutę.</li>
            <li><strong>Certbot</strong> — darmowe certyfikaty Let’s Encrypt + automatyczne odnawianie.</li>
        </ul>

        <h3 class="dc-sub">Kiedy skalować</h3>
        <table class="dc-table">
            <thead><tr><th>Sygnał</th><th>Reakcja</th></tr></thead>
            <tbody>
                <tr><td data-label="Sygnał">RAM stale &gt; 85% / aktywny swap</td><td data-label="Reakcja">przejdź na <span class="dc-inline">e2-medium</span> → <span class="dc-inline">e2-standard-2</span></td></tr>
                <tr><td data-label="Sygnał">Skoki ruchu z kampanii NFC</td><td data-label="Reakcja">zwiększ pulę PHP-FPM (sekcja 8) i włącz Redis</td></tr>
                <tr><td data-label="Sygnał">Baza &gt; 60% dysku</td><td data-label="Reakcja">powiększ dysk (online resize) + przegląd retencji eventów</td></tr>
                <tr><td data-label="Sygnał">Wiele tenantów / domen</td><td data-label="Reakcja">rozważ osobny serwer bazy (Cloud SQL) i CDN na statyki</td></tr>
            </tbody>
        </table>
    </section>

    {{-- 7. SERWER OD ZERA --}}
    <section class="dc-module" id="serwer">
        <div class="dc-module__head"><span class="dc-module__num">7.</span><h2>Stawianie serwera od zera (Debian 12)</h2></div>
        <p class="dc-lead">Kompletna procedura od pustej maszyny do utwardzonego (hardened) serwera gotowego pod aplikację. Wykonuj po kolei jako <code>root</code> (albo z <code>sudo</code>).</p>

        <h3 class="dc-sub">7.1 Pierwsze logowanie i aktualizacja</h3>
        <pre class="dc-pre"><span class="c"># aktualny system to podstawa bezpieczeństwa</span>
<span class="p">#</span> apt update && apt -y full-upgrade
<span class="p">#</span> apt -y install curl wget git unzip htop ufw fail2ban ca-certificates lsb-release apt-transport-https gnupg2

<span class="c"># strefa czasowa i locale (PL)</span>
<span class="p">#</span> timedatectl set-timezone Europe/Warsaw
<span class="p">#</span> apt -y install locales && sed -i 's/# pl_PL.UTF-8/pl_PL.UTF-8/' /etc/locale.gen && locale-gen</pre>

        <h3 class="dc-sub">7.2 Swap (obowiązkowo przy 2 GB RAM)</h3>
        <pre class="dc-pre"><span class="p">#</span> fallocate -l 2G /swapfile && chmod 600 /swapfile
<span class="p">#</span> mkswap /swapfile && swapon /swapfile
<span class="p">#</span> echo '/swapfile none swap sw 0 0' >> /etc/fstab
<span class="c"># mniej agresywne wypychanie na swap</span>
<span class="p">#</span> sysctl -w vm.swappiness=10 && echo 'vm.swappiness=10' >> /etc/sysctl.conf</pre>

        <h3 class="dc-sub">7.3 Użytkownik wdrożeniowy (zamiast pracy na root)</h3>
        <pre class="dc-pre"><span class="p">#</span> adduser --disabled-password --gecos "" deploy
<span class="p">#</span> usermod -aG sudo deploy
<span class="c"># skopiuj swój klucz publiczny na konto deploy</span>
<span class="p">#</span> mkdir -p /home/deploy/.ssh && cp ~/.ssh/authorized_keys /home/deploy/.ssh/
<span class="p">#</span> chown -R deploy:deploy /home/deploy/.ssh && chmod 700 /home/deploy/.ssh && chmod 600 /home/deploy/.ssh/authorized_keys</pre>

        <h3 class="dc-sub">7.4 Utwardzenie SSH</h3>
        <p>W pliku <code>/etc/ssh/sshd_config</code> ustaw:</p>
        <pre class="dc-pre"><span class="i">PermitRootLogin</span> prohibit-password   <span class="c"># root tylko kluczem (lub 'no')</span>
<span class="i">PasswordAuthentication</span> no            <span class="c"># wyłącz hasła — tylko klucze SSH</span>
<span class="i">PubkeyAuthentication</span> yes
<span class="i">X11Forwarding</span> no
<span class="i">MaxAuthTries</span> 3</pre>
        <pre class="dc-pre"><span class="p">#</span> systemctl restart ssh</pre>
        <div class="dc-note dc-note--warn"><strong>Zanim się wylogujesz:</strong> w drugim terminalu sprawdź, że logowanie kluczem nadal działa. Jeśli zablokujesz sobie dostęp, ratunkiem jest konsola szeregowa Google Cloud (Compute Engine → instancja → „Serial console”).</div>

        <h3 class="dc-sub">7.5 Firewall (ufw)</h3>
        <pre class="dc-pre"><span class="p">#</span> ufw default deny incoming
<span class="p">#</span> ufw default allow outgoing
<span class="p">#</span> ufw allow OpenSSH        <span class="c"># port 22</span>
<span class="p">#</span> ufw allow 'Nginx Full'   <span class="c"># porty 80 + 443</span>
<span class="p">#</span> ufw enable && ufw status verbose</pre>
        <div class="dc-note">W Google Cloud działają <strong>dwie warstwy</strong> zapory: <em>VPC firewall</em> (w konsoli — reguły <code>default-allow-http/https/ssh</code>) oraz <em>ufw</em> na samej maszynie. Ruch musi przejść przez obie.</div>

        <h3 class="dc-sub">7.6 fail2ban (ochrona przed bruteforce)</h3>
        <pre class="dc-pre"><span class="p">#</span> cp /etc/fail2ban/jail.{conf,local}
<span class="c"># w /etc/fail2ban/jail.local włącz jaile sshd i nginx; bantime = 1h</span>
<span class="p">#</span> systemctl enable --now fail2ban
<span class="p">#</span> fail2ban-client status sshd</pre>
    </section>

    {{-- 8. NGINX + PHP-FPM --}}
    <section class="dc-module" id="nginx">
        <div class="dc-module__head"><span class="dc-module__num">8.</span><h2>Nginx + PHP-FPM (krok po kroku)</h2></div>
        <p class="dc-lead">Nginx przyjmuje żądania i przekazuje PHP do procesu <strong>PHP-FPM</strong> przez gniazdo uniksowe. Poniżej instalacja, kompletny plik konfiguracyjny domeny (multi-domena: <code>please-support-me.com</code>, <code>pay.*</code>, <code>shop2.*</code>) oraz strojenie puli.</p>

        <h3 class="dc-sub">8.1 Instalacja</h3>
        <pre class="dc-pre"><span class="p">#</span> apt -y install nginx php8.2-fpm php8.2-cli \
        php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip \
        php8.2-gd php8.2-bcmath php8.2-intl php8.2-redis php8.2-opcache
<span class="p">#</span> systemctl enable --now nginx php8.2-fpm
<span class="p">#</span> php -v | head -1   <span class="c"># PHP 8.2.x</span></pre>

        <h3 class="dc-sub">8.2 Pula PHP-FPM (/etc/php/8.2/fpm/pool.d/www.conf)</h3>
        <p>Dla 2 GB RAM dobry punkt startowy (każdy proces ~40–60 MB):</p>
        <pre class="dc-pre"><span class="i">pm</span> = dynamic
<span class="i">pm.max_children</span> = 12
<span class="i">pm.start_servers</span> = 3
<span class="i">pm.min_spare_servers</span> = 2
<span class="i">pm.max_spare_servers</span> = 5
<span class="i">pm.max_requests</span> = 500      <span class="c"># recykling procesu — chroni przed wyciekami pamięci</span></pre>
        <p>OPcache w <code>/etc/php/8.2/fpm/conf.d/10-opcache.ini</code> (mocno przyspiesza Laravela):</p>
        <pre class="dc-pre"><span class="i">opcache.enable</span>=1
<span class="i">opcache.memory_consumption</span>=128
<span class="i">opcache.max_accelerated_files</span>=20000
<span class="i">opcache.validate_timestamps</span>=0   <span class="c"># PROD: 0 = max wydajność; po deployu rób reload FPM</span></pre>

        <h3 class="dc-sub">8.3 Plik domeny (/etc/nginx/sites-available/support-me)</h3>
        <pre class="dc-pre"><span class="c"># HTTP → przekierowanie na HTTPS</span>
server {
    listen 80;
    server_name please-support-me.com pay.please-support-me.com shop2.please-support-me.com www.please-support-me.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name please-support-me.com pay.please-support-me.com shop2.please-support-me.com;
    root /var/www/support-me/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/please-support-me.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/please-support-me.com/privkey.pem;

    client_max_body_size 25m;            <span class="c"># upload CV / grafik produktów</span>
    add_header X-Frame-Options SAMEORIGIN;
    add_header X-Content-Type-Options nosniff;
    add_header Referrer-Policy strict-origin-when-cross-origin;

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param HTTPS on;
    }

    <span class="c"># długi cache na statyki (CSS/JS/obrazy)</span>
    location ~* \.(css|js|jpg|jpeg|png|svg|webp|woff2?)$ {
        expires 30d; access_log off; add_header Cache-Control "public, immutable";
    }

    location ~ /\.(?!well-known) { deny all; }   <span class="c"># blokuj .env, .git itd.</span>
    gzip on; gzip_types text/css application/javascript image/svg+xml application/json;
}</pre>
        <pre class="dc-pre"><span class="p">#</span> ln -s /etc/nginx/sites-available/support-me /etc/nginx/sites-enabled/
<span class="p">#</span> rm -f /etc/nginx/sites-enabled/default
<span class="p">#</span> nginx -t && systemctl reload nginx</pre>
        <div class="dc-note">Każda z domen rozwiązuje się do tego samego <code>root</code>, a <strong>aplikacja sama wybiera tenant</strong> po nazwie hosta (patrz <a href="{{ route('docs') }}#arch">/docs → Architektura multi-tenant</a>). Dlatego jeden blok <code>server</code> obsługuje sklep, bramkę i sklep produktowy.</div>
    </section>

    {{-- 9. MYSQL / MARIADB --}}
    <section class="dc-module" id="baza-setup">
        <div class="dc-module__head"><span class="dc-module__num">9.</span><h2>Baza danych — MySQL / MariaDB</h2></div>
        <p class="dc-lead">Trzy bazy w modelu multi-tenant, jeden użytkownik aplikacyjny z dostępem do wszystkich. Poniżej instalacja, utwardzenie, założenie baz i strojenie.</p>

        <h3 class="dc-sub">9.1 Instalacja i utwardzenie</h3>
        <pre class="dc-pre"><span class="p">#</span> apt -y install mariadb-server
<span class="p">#</span> systemctl enable --now mariadb
<span class="p">#</span> mysql_secure_installation   <span class="c"># ustaw hasło root, usuń anonimowych, testową bazę</span></pre>

        <h3 class="dc-sub">9.2 Bazy i użytkownik aplikacyjny</h3>
        <pre class="dc-pre"><span class="p">#</span> mysql -u root -p</pre>
        <pre class="dc-pre">CREATE DATABASE nfc_pay   CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE nfc_shop1 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE nfc_shop2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

<span class="c">-- jeden użytkownik aplikacyjny do wszystkich tenantów</span>
CREATE USER 'nfc_pay'@'127.0.0.1' IDENTIFIED BY '<i>SILNE_HASLO</i>';
GRANT ALL PRIVILEGES ON nfc_pay.*   TO 'nfc_pay'@'127.0.0.1';
GRANT ALL PRIVILEGES ON nfc_shop1.* TO 'nfc_pay'@'127.0.0.1';
GRANT ALL PRIVILEGES ON nfc_shop2.* TO 'nfc_pay'@'127.0.0.1';
FLUSH PRIVILEGES;</pre>
        <div class="dc-note">Te same nazwy (<code>nfc_pay</code>, <code>nfc_shop1</code>, <code>nfc_shop2</code>) trafiają do <code>.env</code> aplikacji (<code>DB_DATABASE</code>, <code>DB_GATEWAY_DATABASE</code>) i do mapy tenantów <code>config/tenants.php</code>. Każdy host → swoja baza domyślna, a modele bramki zawsze czytają <code>nfc_pay</code> przez połączenie <code>gateway</code>.</div>

        <h3 class="dc-sub">9.3 Strojenie (/etc/mysql/mariadb.conf.d/60-tuning.cnf)</h3>
        <pre class="dc-pre">[mysqld]
innodb_buffer_pool_size = 512M    <span class="c"># ~25–40% RAM</span>
innodb_flush_log_at_trx_commit = 2
max_connections = 100
slow_query_log = 1
long_query_time = 1               <span class="c"># loguj zapytania &gt; 1 s</span></pre>
        <pre class="dc-pre"><span class="p">#</span> systemctl restart mariadb</pre>

        <h3 class="dc-sub">9.4 Szybki test połączenia z aplikacji</h3>
        <pre class="dc-pre"><span class="p">#</span> cd /var/www/support-me && php artisan tinker --execute='echo DB::connection()->getPdo() ? "DB OK\n" : "FAIL\n";'</pre>
    </section>



    {{-- 10. WDROŻENIE LARAVEL --}}
    <section class="dc-module" id="laravel">
        <div class="dc-module__head"><span class="dc-module__num">10.</span><h2>Wdrożenie aplikacji Laravel (krok po kroku)</h2></div>
        <p class="dc-lead">Maszyna gotowa (sekcje 7–9). Teraz wgrywamy kod, instalujemy zależności, konfigurujemy <code>.env</code>, uruchamiamy migracje dla każdego tenanta i budujemy assety. Pracujemy w katalogu <span class="dc-inline">/var/www/support-me</span>.</p>

        <h3 class="dc-sub">10.1 Kod na serwer</h3>
        <pre class="dc-pre"><span class="p">#</span> mkdir -p /var/www/support-me && cd /var/www/support-me
<span class="c"># z repo (zalecane):</span>
<span class="p">#</span> git clone <i>git@github.com:&lt;org&gt;/unified.git</i> .
<span class="c"># albo z lokalnego srodowiska przez rsync (patrz sekcja 3, Czesc 1)</span></pre>

        <h3 class="dc-sub">10.2 Zależności PHP (Composer)</h3>
        <pre class="dc-pre"><span class="p">#</span> php -r "copy('https://getcomposer.org/installer','composer-setup.php');"
<span class="p">#</span> php composer-setup.php --install-dir=/usr/local/bin --filename=composer
<span class="p">#</span> composer install --no-dev --optimize-autoloader --no-interaction</pre>
        <div class="dc-note dc-note--warn"><strong>2 GB RAM?</strong> <code>composer install</code> bywa pamięciożerne — jeśli zabije proces, upewnij się, że masz swap (sekcja 7.2) albo uruchom z <code>COMPOSER_MEMORY_LIMIT=-1</code>.</div>

        <h3 class="dc-sub">10.3 Konfiguracja .env</h3>
        <pre class="dc-pre"><span class="p">#</span> cp .env.example .env && php artisan key:generate
<span class="c"># kluczowe wpisy (.env):</span>
<span class="i">APP_ENV</span>=production
<span class="i">APP_DEBUG</span>=false
<span class="i">APP_URL</span>=https://please-support-me.com

<span class="i">DB_CONNECTION</span>=mysql
<span class="i">DB_HOST</span>=127.0.0.1
<span class="i">DB_DATABASE</span>=nfc_pay
<span class="i">DB_USERNAME</span>=nfc_pay
<span class="i">DB_PASSWORD</span>=<i>***</i>
<span class="i">DB_GATEWAY_DATABASE</span>=nfc_pay      <span class="c"># bramka zawsze tu</span>

<span class="i">CACHE_STORE</span>=database              <span class="c"># lub redis (sekcja 11)</span>
<span class="i">PAYMENT_PROVIDER</span>=payu             <span class="c"># payu | mock</span>
<span class="i">PAYU_ENV</span>=production
<span class="i">PAYU_POS_ID</span>=<i>***</i>
<span class="i">PAYU_CLIENT_ID</span>=<i>***</i>
<span class="i">PAYU_CLIENT_SECRET</span>=<i>***</i>
<span class="i">PAYU_SECOND_KEY</span>=<i>***</i></pre>
        <div class="dc-note">Plik <code>.env</code> to <strong>sekret</strong> — jest w <code>.gitignore</code>, nigdy nie trafia do repo. Po każdej zmianie rób <code>php artisan config:clear</code> (a na produkcji <code>config:cache</code>).</div>

        <h3 class="dc-sub">10.4 Migracje — dla każdego tenanta osobno</h3>
        <pre class="dc-pre"><span class="c"># bramka (nfc_pay) + sklepy (nfc_shop1, nfc_shop2)</span>
<span class="p">#</span> TENANT=pay.please-support-me.com   php artisan migrate --force
<span class="p">#</span> TENANT=please-support-me.com       php artisan migrate --force
<span class="p">#</span> TENANT=shop2.please-support-me.com php artisan migrate --force
<span class="c"># produkty sklepu donacyjnego (Serduszko, Brelok...) — seed:</span>
<span class="p">#</span> TENANT=please-support-me.com php artisan db:seed --class=ShopItemSeeder --force</pre>

        <h3 class="dc-sub">10.5 Uprawnienia, linki, cache, assety</h3>
        <pre class="dc-pre"><span class="p">#</span> php artisan storage:link
<span class="p">#</span> chown -R www-data:www-data storage bootstrap/cache
<span class="p">#</span> find storage bootstrap/cache -type d -exec chmod 775 {} \;

<span class="c"># cache produkcyjny (przyspiesza znaczaco):</span>
<span class="p">#</span> php artisan config:cache && php artisan route:cache && php artisan view:cache

<span class="c"># assety front (Node 20 + Vite/Tailwind):</span>
<span class="p">#</span> curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && apt -y install nodejs
<span class="p">#</span> npm ci && npm run build</pre>
        <div class="dc-note">Po każdym deployu pamiętaj o <code>php artisan config:cache</code> + <strong>reload PHP-FPM</strong> (<code>systemctl reload php8.2-fpm</code>) — przy <code>opcache.validate_timestamps=0</code> bez reloadu serwer trzyma stary bajtkod.</div>
    </section>

    {{-- 11. USŁUGI W TLE --}}
    <section class="dc-module" id="uslugi">
        <div class="dc-module__head"><span class="dc-module__num">11.</span><h2>Usługi w tle: kolejki, scheduler, Redis</h2></div>
        <p class="dc-lead">Webhooki PayU, wysyłka maili i rekonsyliacja transakcji idą przez <strong>kolejkę</strong>. Harmonogram Laravela (czyszczenie, przypomnienia) odpala <strong>cron</strong>. <strong>Redis</strong> to szybki cache/kolejka zamiast bazy.</p>

        <h3 class="dc-sub">11.1 Redis</h3>
        <pre class="dc-pre"><span class="p">#</span> apt -y install redis-server
<span class="c"># /etc/redis/redis.conf: maxmemory 256mb, maxmemory-policy allkeys-lru</span>
<span class="p">#</span> systemctl enable --now redis-server
<span class="c"># w .env przelacz sterowniki:</span>
<span class="i">CACHE_STORE</span>=redis
<span class="i">SESSION_DRIVER</span>=redis
<span class="i">QUEUE_CONNECTION</span>=redis</pre>

        <h3 class="dc-sub">11.2 Worker kolejki pod Supervisorem</h3>
        <pre class="dc-pre"><span class="p">#</span> apt -y install supervisor
<span class="c"># /etc/supervisor/conf.d/support-me-worker.conf</span>
[program:support-me-worker]
command=php /var/www/support-me/artisan queue:work --sleep=3 --tries=3 --max-time=3600
directory=/var/www/support-me
user=www-data
autostart=true
autorestart=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/support-me-worker.log
stopwaitsecs=3600</pre>
        <pre class="dc-pre"><span class="p">#</span> supervisorctl reread && supervisorctl update && supervisorctl status</pre>
        <div class="dc-note dc-note--warn"><strong>Po każdym deployu</strong> zrestartuj workery (<code>php artisan queue:restart</code>) — inaczej trzymają stary kod w pamięci.</div>

        <h3 class="dc-sub">11.3 Scheduler (cron)</h3>
        <pre class="dc-pre"><span class="c"># crontab -e -u www-data — jedna linia uruchamia caly harmonogram Laravela</span>
* * * * * cd /var/www/support-me && php artisan schedule:run >> /dev/null 2>&1</pre>
    </section>

    {{-- 12. SSL / BACKUP / BEZPIECZEŃSTWO --}}
    <section class="dc-module" id="ssl-backup">
        <div class="dc-module__head"><span class="dc-module__num">12.</span><h2>SSL, backupy, monitoring i bezpieczeństwo</h2></div>
        <p class="dc-lead">Ostatni etap: szyfrowanie HTTPS, automatyczne kopie zapasowe, monitoring dostępności i checklista hardeningu. Bez tego nie ma „produkcji”.</p>

        <h3 class="dc-sub">12.1 Certyfikat SSL (Let’s Encrypt)</h3>
        <pre class="dc-pre"><span class="p">#</span> apt -y install certbot python3-certbot-nginx
<span class="p">#</span> certbot --nginx -d please-support-me.com -d www.please-support-me.com \
        -d pay.please-support-me.com -d shop2.please-support-me.com
<span class="c"># auto-odnawianie — sprawdz timer:</span>
<span class="p">#</span> systemctl status certbot.timer && certbot renew --dry-run</pre>

        <h3 class="dc-sub">12.2 Automatyczny backup (bazy + pliki)</h3>
        <pre class="dc-pre"><span class="c"># /usr/local/bin/support-me-backup.sh</span>
#!/usr/bin/env bash
set -euo pipefail
TS=$(date +%F_%H%M)
DEST=/var/backups/support-me; mkdir -p "$DEST"
for db in nfc_pay nfc_shop1 nfc_shop2; do
  mysqldump --single-transaction "$db" | gzip > "$DEST/${db}_${TS}.sql.gz"
done
tar czf "$DEST/storage_${TS}.tar.gz" -C /var/www/support-me storage/app
<span class="c"># retencja: trzymaj 14 dni</span>
find "$DEST" -type f -mtime +14 -delete</pre>
        <pre class="dc-pre"><span class="p">#</span> chmod +x /usr/local/bin/support-me-backup.sh
<span class="c"># cron: codziennie 03:30</span>
30 3 * * * /usr/local/bin/support-me-backup.sh >> /var/log/support-me-backup.log 2>&1</pre>
        <div class="dc-note">Kopie trzymaj <strong>poza maszyną</strong> — dorzuć <code>gsutil rsync</code> do bucketa Google Cloud Storage albo <code>rclone</code> na zewnętrzny dysk. Backup, którego nie odtworzyłeś próbnie, nie istnieje.</div>

        <h3 class="dc-sub">12.3 Rotacja logów</h3>
        <pre class="dc-pre"><span class="c"># /etc/logrotate.d/support-me</span>
/var/www/support-me/storage/logs/*.log {
    daily; rotate 14; compress; delaycompress; missingok; notifempty; copytruncate;
}</pre>

        <h3 class="dc-sub">12.4 Checklista bezpieczeństwa</h3>
        <table class="dc-table">
            <thead><tr><th>Obszar</th><th>Stan docelowy</th></tr></thead>
            <tbody>
                <tr><td data-label="Obszar">SSH</td><td data-label="Stan docelowy">tylko klucze, <code>PasswordAuthentication no</code>, fail2ban aktywny</td></tr>
                <tr><td data-label="Obszar">Firewall</td><td data-label="Stan docelowy">ufw: tylko 22/80/443; reguły VPC w GCP</td></tr>
                <tr><td data-label="Obszar">Laravel</td><td data-label="Stan docelowy"><code>APP_DEBUG=false</code>, <code>APP_ENV=production</code>, klucz aplikacji ustawiony</td></tr>
                <tr><td data-label="Obszar">Sekrety</td><td data-label="Stan docelowy"><code>.env</code> poza repo, prawa 600, brak haseł w gicie</td></tr>
                <tr><td data-label="Obszar">HTTPS</td><td data-label="Stan docelowy">certyfikat ważny, auto-renew przetestowany, redirect 80→443</td></tr>
                <tr><td data-label="Obszar">PayU</td><td data-label="Stan docelowy">weryfikacja podpisu webhooka (OpenPayu-Signature), <code>second_key</code> w <code>.env</code></td></tr>
                <tr><td data-label="Obszar">Backup</td><td data-label="Stan docelowy">codzienny, kopia poza VM, próbne odtworzenie raz/mc</td></tr>
                <tr><td data-label="Obszar">Aktualizacje</td><td data-label="Stan docelowy"><code>unattended-upgrades</code> dla łatek bezpieczeństwa</td></tr>
            </tbody>
        </table>
        <div class="dc-note">Pełna mapa modułów, tabel i pól aplikacji: <a href="{{ route('docs') }}">dokumentacja techniczna /docs</a>. Tam znajdziesz, który formularz zapisuje co i do której kolumny w bazie.</div>
    </section>



    {{-- 13. BEZPIECZEŃSTWO, ZASADY I ZASTRZEŻENIA --}}
    <section class="dc-module" id="bezpieczenstwo">
        <div class="dc-module__head"><span class="dc-module__num">13.</span><h2>Bezpieczeństwo, zasady postępowania i zastrzeżenia</h2></div>
        <p class="dc-lead">Ta sekcja jest <strong>obowiązkowa do przeczytania</strong> przed jakąkolwiek ingerencją w system. Platforma przetwarza wpłaty od darczyńców i dane osobowe — błąd operacyjny to nie „zepsuta podstrona”, tylko ryzyko finansowe i prawne (RODO). Stosuj poniższe zasady bezwzględnie.</p>

        <div class="dc-note dc-note--warn"><strong>Zasada nadrzędna:</strong> jeśli czegoś nie rozumiesz — <strong>nie ruszaj produkcji</strong>. Każda zmiana na żywej bramce płatności może przerwać transakcje w toku. W razie wątpliwości: backup → kopia testowa → konsultacja z właścicielem systemu.</p></div>

        <h3 class="dc-sub">13.1 Kontrola dostępu (zasada najmniejszych uprawnień)</h3>
        <ul>
            <li><strong>Dostęp imienny.</strong> Każda osoba ma własny klucz SSH i własne konto w konsoli Google Cloud — żadnych współdzielonych haseł.</li>
            <li><strong>Tylko klucze, nigdy hasła.</strong> <code>PasswordAuthentication no</code> (sekcja 7.4). Klucz prywatny nie opuszcza komputera właściciela.</li>
            <li><strong>Minimum uprawnień.</strong> Codzienna praca z konta <code>deploy</code>, <code>root</code> tylko gdy konieczne (<code>sudo -i</code>). W GCP role nadawaj per-osoba, nie „Owner dla wszystkich”.</li>
            <li><strong>MFA</strong> na koncie Google z dostępem do projektu <span class="dc-inline">please-support-me-499509</span> — obowiązkowo.</li>
            <li><strong>Offboarding natychmiastowy.</strong> Osoba kończąca współpracę: usuń jej klucz z <code>~/.ssh/authorized_keys</code> i konsoli GCP, zrotuj sekrety (sekcja 13.4) tego samego dnia.</li>
            <li><strong>Rejestr dostępu.</strong> Logowania SSH i konsoli GCP są logowane; przeglądaj <code>journalctl -u ssh</code> oraz Cloud Audit Logs okresowo.</li>
        </ul>

        <h3 class="dc-sub">13.2 Dyscyplina pracy z produkcją</h3>
        <table class="dc-table">
            <thead><tr><th>Zasada</th><th>Dlaczego</th></tr></thead>
            <tbody>
                <tr><td data-label="Zasada">Backup <strong>przed</strong> każdą zmianą</td><td data-label="Dlaczego">kopia pliku/bazy = jedyna pewna ścieżka rollbacku</td></tr>
                <tr><td data-label="Zasada">Najpierw kopia testowa, potem prod</td><td data-label="Dlaczego">nie sprawdzaj hipotez na żywej bramce płatności</td></tr>
                <tr><td data-label="Zasada">Weryfikacja po deployu (HTTP 200)</td><td data-label="Dlaczego">„wgrałem” ≠ „działa” — zawsze sprawdź user-flow</td></tr>
                <tr><td data-label="Zasada">Plan rollbacku gotowy przed startem</td><td data-label="Dlaczego">wiesz, jak cofnąć, zanim zaczniesz</td></tr>
                <tr><td data-label="Zasada">Zero sekretów w repo i logach</td><td data-label="Dlaczego">wyciek <code>.env</code> = kompromitacja płatności</td></tr>
                <tr><td data-label="Zasada">Okna serwisowe poza szczytem</td><td data-label="Dlaczego">najmniej transakcji = najmniejsze ryzyko</td></tr>
                <tr><td data-label="Zasada">Jedna zmiana = jeden, opisany krok</td><td data-label="Dlaczego">łatwiej zlokalizować źródło awarii</td></tr>
            </tbody>
        </table>

        <h3 class="dc-sub">13.3 Ochrona danych darczyńców (RODO)</h3>
        <p>Platforma <strong>nie przechowuje danych kart płatniczych</strong> — pełną obsługę płatności i dane wrażliwe trzyma operator <strong>PayU</strong> (zgodnie z PCI-DSS). Po naszej stronie żyją wyłącznie dane minimalne (kwota, identyfikator transakcji, status, ewentualnie e-mail kontaktowy/aplikacyjny). Mimo to obowiązują nas zasady RODO:</p>
        <ul>
            <li><strong>Minimalizacja.</strong> Zbieramy tylko to, co konieczne do realizacji wpłaty i kontaktu.</li>
            <li><strong>Szyfrowanie.</strong> W tranzycie — TLS (sekcja 12.1). W spoczynku — backupy trzymane w bezpiecznej lokalizacji o ograniczonym dostępie.</li>
            <li><strong>Retencja.</strong> Dane operacyjne i logi przechowuj nie dłużej niż to uzasadnione; eventy analityczne czyść okresowo.</li>
            <li><strong>Prawa osób.</strong> Obsłuż żądania dostępu/usunięcia danych w terminach RODO.</li>
            <li><strong>Powierzenie przetwarzania.</strong> Z PayU (i innymi podprocesorami) musi obowiązywać odpowiednia umowa.</li>
            <li><strong>Naruszenia.</strong> Podejrzenie wycieku zgłoś zgodnie z procedurą i prawem (co do zasady do <strong>72 h</strong> do organu nadzorczego).</li>
        </ul>
        <div class="dc-note">To <strong>nie jest porada prawna.</strong> Zakres obowiązków RODO ustal z osobą odpowiedzialną za ochronę danych w organizacji. Powyższe to operacyjne minimum higieny.</div>

        <h3 class="dc-sub">13.4 Sekrety, klucze i tokeny</h3>
        <ul>
            <li><strong>Gdzie żyją:</strong> wyłącznie w <code>.env</code> (poza repo, w <code>.gitignore</code>, prawa <code>600</code>). Klucze SSH w <code>~/.ssh</code>.</li>
            <li><strong>Czego nie wolno:</strong> wklejać sekretów do repo, ticketów, czatów, logów ani zrzutów ekranu.</li>
            <li><strong>Rotacja:</strong> dane PayU (<code>PAYU_*</code>), tokeny Figma i hasła baz rotuj po offboardingu i przy każdym podejrzeniu wycieku.</li>
            <li><strong>Po rotacji:</strong> <code>php artisan config:clear</code> i odśwież cache tokenów (per-tenant, patrz architektura) — inaczej aplikacja użyje starych danych.</li>
        </ul>

        <h3 class="dc-sub">13.5 Procedura awaryjna (incident response)</h3>
        <ol class="dc-steps">
            <li><strong>Wykryj i nazwij.</strong> Co się dzieje, od kiedy, czego dotyczy (płatności / dane / dostępność).</li>
            <li><strong>Ogranicz szkodę.</strong> W razie kompromitacji odetnij dostęp (zablokuj klucze, zrotuj sekrety), w razie awarii — przełącz w tryb serwisowy.</li>
            <li><strong>Oceń zakres.</strong> Sprawdź logi (<code>storage/logs/laravel.log</code>, nginx, MySQL slow log), ustal co i kiedy.</li>
            <li><strong>Przywróć.</strong> Z ostatniego sprawnego backupu (sekcja 12.2). Najpierw na kopii, potem na prod.</li>
            <li><strong>Zakomunikuj.</strong> Poinformuj właściciela i — jeśli dotyczy danych — zastosuj obowiązki zgłoszeniowe.</li>
            <li><strong>Post-mortem.</strong> Spisz przyczynę źródłową i działania zapobiegawcze. Bez szukania winnych — szukamy systemu, który dopuścił błąd.</li>
        </ol>

        <h3 class="dc-sub">13.6 Zasady zachowania — rób / nie rób</h3>
        <table class="dc-table">
            <thead><tr><th style="color:#1a7f4b">RÓB</th><th style="color:#c0392b">NIE RÓB</th></tr></thead>
            <tbody>
                <tr><td data-label="RÓB">Backup przed zmianą</td><td data-label="NIE RÓB">Edycji prod „na żywo” bez kopii</td></tr>
                <tr><td data-label="RÓB">Testuj na kopii / lokalnie</td><td data-label="NIE RÓB">Eksperymentów na bramce płatności</td></tr>
                <tr><td data-label="RÓB">Sprawdzaj 200 i user-flow po zmianie</td><td data-label="NIE RÓB">„Wgrałem i tyle”</td></tr>
                <tr><td data-label="RÓB">Trzymaj sekrety w <code>.env</code></td><td data-label="NIE RÓB">Commituj/loguj sekretów</td></tr>
                <tr><td data-label="RÓB">Dokumentuj co i dlaczego zmieniłeś</td><td data-label="NIE RÓB">Wyłączaj firewalla/SSL „na chwilę”</td></tr>
                <tr><td data-label="RÓB">Pytaj, gdy nie wiesz</td><td data-label="NIE RÓB">Usuwaj logów i backupów</td></tr>
            </tbody>
        </table>

        <h3 class="dc-sub">13.7 Zarządzanie kluczami i sekretami</h3>
        <p>Sekret to każdy ciąg, który daje dostęp: hasła baz, klucze SSH, dane PayU (<code>PAYU_*</code>), tokeny Figma, klucz aplikacji Laravel (<code>APP_KEY</code>), hasła do konsoli. Zasada brzmi: <strong>jeden sekret żyje w jednym, znanym miejscu</strong> — nie krąży po czatach, mailach i zrzutach.</p>
        <table class="dc-table">
            <thead><tr><th>Sekret</th><th>Gdzie żyje</th><th>Rotacja</th></tr></thead>
            <tbody>
                <tr><td data-label="Sekret">Hasła baz (<code>DB_PASSWORD</code>)</td><td data-label="Gdzie żyje"><code>.env</code> (prawa 600)</td><td data-label="Rotacja">po offboardingu / podejrzeniu wycieku</td></tr>
                <tr><td data-label="Sekret">Dane PayU (POS, client, second_key)</td><td data-label="Gdzie żyje"><code>.env</code> → <code>config/payment.php</code></td><td data-label="Rotacja">z panelu PayU + update <code>.env</code> + clear cache</td></tr>
                <tr><td data-label="Sekret">Token Figma</td><td data-label="Gdzie żyje"><code>.env</code> (<code>FIGMA_TOKEN</code>)</td><td data-label="Rotacja">okresowo; natychmiast po wycieku</td></tr>
                <tr><td data-label="Sekret"><code>APP_KEY</code></td><td data-label="Gdzie żyje"><code>.env</code></td><td data-label="Rotacja">tylko świadomie (unieważnia zaszyfrowane dane/sesje)</td></tr>
                <tr><td data-label="Sekret">Klucz prywatny SSH</td><td data-label="Gdzie żyje">komputer właściciela (<code>~/.ssh</code>)</td><td data-label="Rotacja">przy zmianie zespołu</td></tr>
                <tr><td data-label="Sekret">Dostęp do konsoli GCP</td><td data-label="Gdzie żyje">konto Google + MFA</td><td data-label="Rotacja">natychmiast przy offboardingu</td></tr>
            </tbody>
        </table>
        <ul>
            <li><strong>Generowanie:</strong> hasła min. 24 znaki, losowe (menedżer haseł / <code>openssl rand -base64 24</code>). Klucze SSH: <code>ed25519</code>.</li>
            <li><strong>Przechowywanie zespołowe:</strong> używaj menedżera haseł (np. Bitwarden/1Password) albo <em>GCP Secret Manager</em> — nigdy arkusza, notatnika czy czatu.</li>
            <li><strong>Po rotacji:</strong> <code>php artisan config:clear</code> + wyczyść cache tokenów per-tenant, zrestartuj workery (<code>queue:restart</code>) i reload PHP-FPM.</li>
            <li><strong>Wyciek = incydent.</strong> Zrotuj natychmiast, sprawdź logi dostępu, postępuj wg 13.5.</li>
        </ul>

        <h3 class="dc-sub">13.8 Mapa danych — gdzie co zapisywać</h3>
        <p>Każdy rodzaj danych ma swoje stałe miejsce. Trzymanie się tej mapy to połowa bezpieczeństwa — wiesz, co backupować, co szyfrować i czego nie wrzucać do repo.</p>
        <table class="dc-table">
            <thead><tr><th>Rodzaj danych</th><th>Lokalizacja</th><th>Repo?</th><th>Backup?</th></tr></thead>
            <tbody>
                <tr><td data-label="Rodzaj danych">Kod aplikacji</td><td data-label="Lokalizacja"><code>/var/www/support-me</code></td><td data-label="Repo?">TAK (git)</td><td data-label="Backup?">git + serwer</td></tr>
                <tr><td data-label="Rodzaj danych">Sekrety / konfiguracja</td><td data-label="Lokalizacja"><code>.env</code></td><td data-label="Repo?"><strong>NIE</strong> (gitignore)</td><td data-label="Backup?">osobno, szyfrowane</td></tr>
                <tr><td data-label="Rodzaj danych">Pliki użytkowników (CV, grafiki)</td><td data-label="Lokalizacja"><code>storage/app</code></td><td data-label="Repo?">NIE</td><td data-label="Backup?">TAK (tar)</td></tr>
                <tr><td data-label="Rodzaj danych">Assety publiczne (CSS/JS/img)</td><td data-label="Lokalizacja"><code>public/</code></td><td data-label="Repo?">TAK</td><td data-label="Backup?">git</td></tr>
                <tr><td data-label="Rodzaj danych">Dane transakcji / zamówień</td><td data-label="Lokalizacja">bazy <code>nfc_pay</code> / <code>nfc_shop1/2</code></td><td data-label="Repo?">NIE</td><td data-label="Backup?">TAK (mysqldump)</td></tr>
                <tr><td data-label="Rodzaj danych">Logi aplikacji</td><td data-label="Lokalizacja"><code>storage/logs</code></td><td data-label="Repo?">NIE</td><td data-label="Backup?">rotacja 14 dni</td></tr>
                <tr><td data-label="Rodzaj danych">Logi systemowe (nginx/mysql)</td><td data-label="Lokalizacja"><code>/var/log</code></td><td data-label="Repo?">NIE</td><td data-label="Backup?">rotacja</td></tr>
                <tr><td data-label="Rodzaj danych">Cache / sesje / kolejki</td><td data-label="Lokalizacja">Redis lub tabela <code>cache</code></td><td data-label="Repo?">NIE</td><td data-label="Backup?">nietrwałe (do odtworzenia)</td></tr>
                <tr><td data-label="Rodzaj danych">Kopie zapasowe</td><td data-label="Lokalizacja"><code>/var/backups/support-me</code> + offsite</td><td data-label="Repo?">NIE</td><td data-label="Backup?">to JEST backup</td></tr>
            </tbody>
        </table>
        <div class="dc-note dc-note--warn">Dane wrażliwe płatności (karty) <strong>nie istnieją po naszej stronie</strong> — trzyma je PayU. Nie próbuj ich zapisywać ani logować. Logowanie pełnych payloadów webhooków też ograniczaj (mogą zawierać dane osobowe).</div>

        <h3 class="dc-sub">13.9 Dostępy i sekrety „dla Claude” (nie z poziomu root)</h3>
        <p>Gdy Claude pracuje na serwerze (sekcja 3, Część 2), powinien działać <strong>z konta o ograniczonych uprawnieniach</strong>, a nie jako <code>root</code>. Root to ostateczność do zadań administracyjnych — codzienna edycja kodu i deploy nie wymagają pełni władzy nad maszyną.</p>
        <ul>
            <li><strong>Konto robocze:</strong> uruchamiaj Claude jako <code>deploy</code> (lub dedykowany użytkownik aplikacji), z dostępem do <code>/var/www/support-me</code> i prawem zapisu tam, gdzie trzeba — bez globalnego roota.</li>
            <li><strong>Własny katalog domowy / konfiguracja:</strong> Claude trzyma swój stan i logowanie w swoim <code>HOME</code> (np. <code>~/.claude</code> danego użytkownika), osobno od konta root. Dzięki temu rotacja/odebranie dostępu Claude nie rusza reszty systemu.</li>
            <li><strong>Sekrety aplikacyjne, nie systemowe:</strong> Claude do pracy z aplikacją używa danych z <code>.env</code> (poziom aplikacji), a nie haseł roota czy kluczy prywatnych administratora.</li>
            <li><strong>Kontekst projektu:</strong> stałe reguły (ścieżki, nazwy baz, konwencje, czego nie ruszać) trzymaj w pliku <code>CLAUDE.md</code> w katalogu projektu — to „pamięć” widoczna dla Claude przy każdej sesji, zamiast podawania tego za każdym razem.</li>
            <li><strong>Najmniejszy potrzebny zakres:</strong> jeśli Claude ma tylko edytować widoki — nie potrzebuje dostępu do produkcyjnej bazy roota; jeśli ma robić migracje — wystarczy użytkownik aplikacyjny bazy (<code>nfc_pay</code>), nie root MySQL.</li>
        </ul>

        <h3 class="dc-sub">13.10 Co mówić Claude o przechowywaniu (instrukcje robocze)</h3>
        <p>Claude robi to, o co poprosisz — dlatego warto jasno zakomunikować zasady przechowywania, żeby nie improwizował. Poniżej „powiedz / nie podawaj”.</p>
        <table class="dc-table">
            <thead><tr><th style="color:#1a7f4b">POWIEDZ / POPROŚ</th><th style="color:#c0392b">NIE PODAWAJ / NIE KAŻ</th></tr></thead>
            <tbody>
                <tr><td data-label="POWIEDZ / POPROŚ">„sekrety zawsze do <code>.env</code>, nigdy do kodu/repo”</td><td data-label="NIE PODAWAJ / NIE KAŻ">haseł i tokenów w treści promptu „na stałe do pliku”</td></tr>
                <tr><td data-label="POWIEDZ / POPROŚ">„nowe pliki użytkowników → <code>storage/app</code>”</td><td data-label="NIE PODAWAJ / NIE KAŻ">zapisu danych osobowych do <code>public/</code></td></tr>
                <tr><td data-label="POWIEDZ / POPROŚ">„grafiki/statyki → <code>public/img/...</code>”</td><td data-label="NIE PODAWAJ / NIE KAŻ">commitowania <code>.env</code> / kluczy prywatnych</td></tr>
                <tr><td data-label="POWIEDZ / POPROŚ">„zrób backup pliku/bazy przed zmianą”</td><td data-label="NIE PODAWAJ / NIE KAŻ">„usuń logi/stare backupy, żeby zrobić miejsce”</td></tr>
                <tr><td data-label="POWIEDZ / POPROŚ">„używaj połączenia per-tenant (gateway = nfc_pay)”</td><td data-label="NIE PODAWAJ / NIE KAŻ">hasła root MySQL „do wygody”</td></tr>
                <tr><td data-label="POWIEDZ / POPROŚ">„po zmianie wyczyść właściwy cache i sprawdź 200”</td><td data-label="NIE PODAWAJ / NIE KAŻ">wyłączania firewalla/SSL „na chwilę testu”</td></tr>
            </tbody>
        </table>
        <div class="dc-note">Najlepsza praktyka: te reguły wpisz <strong>raz</strong> do <code>CLAUDE.md</code> projektu. Wtedy obowiązują w każdej sesji bez powtarzania, a Ty dyktujesz tylko zadanie.</div>

        <h3 class="dc-sub">13.11 Kontakty i eskalacja</h3>
        <p>W razie awarii lub incydentu liczy się, kto czym się zajmuje i jak szybko reaguje. Utrzymuj aktualną listę ról i kanałów.</p>
        <table class="dc-table">
            <thead><tr><th>Rola</th><th>Zakres</th><th>Kontakt</th></tr></thead>
            <tbody>
                <tr><td data-label="Rola">Właściciel systemu</td><td data-label="Zakres">decyzje, dostępy, zgody na zmiany prod</td><td data-label="Kontakt"><span class="dc-inline">kontakt@please-support-me.com</span></td></tr>
                <tr><td data-label="Rola">Administrator serwera</td><td data-label="Zakres">maszyna, nginx, baza, backupy</td><td data-label="Kontakt">wg listy zespołu</td></tr>
                <tr><td data-label="Rola">Operator płatności</td><td data-label="Zakres">rozliczenia, spory, zwroty</td><td data-label="Kontakt">PayU (panel + wsparcie); podmiot: Support Me Services Marcin Lula, NIP 8741624637</td></tr>
                <tr><td data-label="Rola">Ochrona danych (RODO)</td><td data-label="Zakres">żądania osób, naruszenia</td><td data-label="Kontakt">osoba wskazana w organizacji</td></tr>
            </tbody>
        </table>
        <div class="dc-note">Aktualizuj kontakty przy każdej zmianie zespołu. Kontakt awaryjny trzymaj też poza systemem (gdyby system był niedostępny).</div>

        <h3 class="dc-sub">13.12 Audyt i przegląd okresowy</h3>
        <p>Bezpieczeństwo to nie jednorazowy setup, tylko rutyna. Minimalny rytm przeglądów:</p>
        <ul>
            <li><strong>Co miesiąc:</strong> przegląd dostępów (kto ma klucze/role), próbne odtworzenie backupu, przegląd logów błędów i nietypowych logowań.</li>
            <li><strong>Co kwartał:</strong> rotacja wybranych sekretów, aktualizacja zależności (<code>composer/npm audit</code>), test <code>certbot renew --dry-run</code>.</li>
            <li><strong>Po każdym incydencie:</strong> post-mortem + wdrożenie wniosków.</li>
            <li><strong>Ciągle:</strong> <code>unattended-upgrades</code> dla łatek bezpieczeństwa systemu.</li>
        </ul>

        <h3 class="dc-sub">13.13 Zastrzeżenia</h3>
        <div class="dc-note dc-note--warn">
            <strong>Zastrzeżenie odpowiedzialności.</strong> Wszelkie zmiany w infrastrukturze, kodzie i konfiguracji wykonujesz <strong>na własną odpowiedzialność</strong>. Przed modyfikacją produkcji wymagana jest zgoda właściciela systemu. Dokumentacja opisuje stan środowiska na dzień jej aktualizacji i może odbiegać od bieżącej konfiguracji — przed działaniem zweryfikuj stan faktyczny na serwerze. Nieautoryzowany dostęp do systemu, danych darczyńców lub kluczy jest zabroniony i może podlegać odpowiedzialności prawnej. Autor dokumentacji nie ponosi odpowiedzialności za szkody wynikłe z modyfikacji wykonanych niezgodnie z powyższymi zasadami.
        </div>
        <div class="dc-note">Powiązane: konfiguracja dostępu (<a href="#dostep">sekcja 2</a>), hardening serwera (<a href="#serwer">sekcja 7</a>), backup i SSL (<a href="#ssl-backup">sekcja 12</a>), pełna mapa danych (<a href="{{ route('docs') }}#baza">/docs → schemat bazy</a>).</div>
    </section>



    {{-- 14. PRACA Z FIGMĄ --}}
    <section class="dc-module" id="figma-pro">
        <div class="dc-module__head"><span class="dc-module__num">14.</span><h2>Praca z Figmą — pełny przewodnik (1:1, MCP, problemy)</h2></div>
        <p class="dc-lead">Najtrudniejsza część „robienia stron z Claude” to <strong>wierne odwzorowanie projektu z Figmy</strong>. Teoretycznie „przepisz design na kod” brzmi prosto — w praktyce piksele rzadko zgadzają się od pierwszego strzału, a projekty bywają niespójne lub niedokończone. Ta sekcja opisuje cały proces: od konfiguracji dostępu, przez realne problemy i ich przyczyny, po workflow „pixel-perfect” i checklisty. To najdłuższy rozdział samouczka — bo tu najłatwiej stracić godziny.</p>

        <h3 class="dc-sub">14.1 Po co w ogóle Figma w tym procesie</h3>
        <p>Figma jest <strong>źródłem prawdy</strong> dla wyglądu. Gdy Claude „widzi” projekt (przez MCP albo REST API), nie zgaduje kolorów, odstępów i typografii — odczytuje je z pliku: dokładne wartości HEX, rozmiary w px, font-family, font-weight, line-height, letter-spacing, promienie zaokrągleń, cienie, siatkę autolayoutu. Dzięki temu kod powstaje na liczbach, a nie „na oko”. Bez Figmy Claude robi wersję <em>przybliżoną</em> z opisu słownego — szybciej, ale mniej wiernie.</p>
        <div class="dc-note">Reguła: <strong>masz Figmę → podaj link/node-id.</strong> Im konkretniejsze źródło, tym mniej iteracji poprawek. Sam opis słowny zostaw na drobne zmiany („zmień kolor przycisku na granatowy”).</div>

        <h3 class="dc-sub">14.2 Tryby i uprawnienia — to one decydują o limitach</h3>
        <p>Zanim cokolwiek zadziała, konto musi mieć właściwy <strong>seat</strong> (miejsce) na pliku. To najczęstsza przyczyna „dlaczego MCP nie czyta projektu”.</p>
        <table class="dc-table">
            <thead><tr><th>Seat / tryb</th><th>Co daje</th><th>Limit odczytu</th></tr></thead>
            <tbody>
                <tr><td data-label="Seat / tryb"><strong>View</strong></td><td data-label="Co daje">podgląd pliku</td><td data-label="Limit odczytu">bardzo ograniczony (rzędu kilku odczytów/mc przez MCP) — szybko „limit”</td></tr>
                <tr><td data-label="Seat / tryb"><strong>Dev</strong></td><td data-label="Co daje">Dev Mode: inspekcja, miary, eksport, tokeny</td><td data-label="Limit odczytu">pełny odczyt designu dla Claude</td></tr>
                <tr><td data-label="Seat / tryb"><strong>Full</strong></td><td data-label="Co daje">edycja + Dev Mode</td><td data-label="Limit odczytu">pełny odczyt</td></tr>
            </tbody>
        </table>
        <div class="dc-note dc-note--warn"><strong>Najczęstszy błąd:</strong> konto ma tylko seat <em>View</em>. MCP zwróci wtedy komunikat o przekroczeniu limitu po kilku zapytaniach. Rozwiązanie: nadaj kontu seat <strong>Dev</strong> na tym pliku, albo użyj fallbacku przez REST API (14.5).</div>

        <h3 class="dc-sub">14.3 Konfiguracja połączenia (Dev Mode + MCP) krok po kroku</h3>
        <ol class="dc-steps">
            <li><strong>Włącz Dev Mode</strong> w pliku Figmy (przełącznik w prawym górnym rogu). To odblokowuje precyzyjną inspekcję, z której korzysta integracja.</li>
            <li><strong>Autoryzuj serwer MCP Figmy</strong> kontem, które ma dostęp do pliku. To konto autoryzuje odczyt — bez aktywnej sesji MCP Claude nie zobaczy designu. <em>Dlaczego to istotne:</em> autoryzacja wygasa; jeśli „nagle przestało działać”, najpierw odśwież połączenie MCP.</li>
            <li><strong>Sprawdź dostęp do konkretnego pliku.</strong> Konto MCP musi widzieć dokładnie ten plik (nie tylko „jakiś” w organizacji). Współdzielenie pliku → zaproszenie konta MCP jako Dev.</li>
            <li><strong>Test:</strong> poproś Claude o odczyt jednej ramki. Jeśli zwróci kolory/wymiary — działa. Jeśli „brak dostępu/limit” — wróć do 14.2.</li>
        </ol>

        <h3 class="dc-sub">14.4 Jak podawać Claude design (link / node-id)</h3>
        <p>Najpewniejsza metoda to <strong>link do zaznaczenia</strong>: w Figmie zaznacz ramkę → <em>Copy link to selection</em>. Taki URL zawiera <code>node-id</code> — Claude wyciągnie z niego klucz pliku i identyfikator węzła i odczyta dokładnie ten element.</p>
        <pre class="dc-pre"><span class="c"># przykładowy link z node-id</span>
https://www.figma.com/design/<i>FILEKEY</i>/Projekt?node-id=<i>1234-5678</i>
<span class="c"># Claude czyta: file = FILEKEY, node = 1234:5678</span></pre>
        <p><em>Na co uważać:</em> link do całej strony zwróci olbrzymie drzewo (wolno, mniej precyzyjnie). Podawaj <strong>konkretną ramkę/komponent</strong>, którą zmieniasz — wynik jest szybszy i wierniejszy.</p>

        <h3 class="dc-sub">14.5 Fallback: Figma REST API + token (gdy MCP zwróci limit)</h3>
        <p>Gdy konto ma tylko seat View i MCP „nie wyrabia”, działa <strong>Figma REST API</strong> z osobistym tokenem. Claude pobierze węzły i rendery bez MCP.</p>
        <ol class="dc-steps">
            <li>W Figmie: <em>Settings → Security → Personal access tokens</em> → wygeneruj token z uprawnieniem <strong>File content: read</strong> (oraz <em>Dev resources</em>, jeśli dostępne).</li>
            <li>Zapisz token w <code>unified/.env</code> jako <code>FIGMA_TOKEN</code> — to <strong>sekret</strong>, trafia tylko do <code>.env</code> (jest w <code>.gitignore</code>), nigdy do repo.</li>
            <li>Claude użyje endpointów <code>GET /v1/files/:key/nodes?ids=...</code> (struktura + style) oraz <code>GET /v1/images/:key?ids=...</code> (rendery PNG/SVG ramek).</li>
        </ol>
        <div class="dc-note"><em>Dlaczego warto mieć fallback:</em> nie blokuje Cię miesięczny limit MCP, a rendery PNG z API świetnie służą do nakładki „Figma ↔ live” przy weryfikacji (14.8).</div>

        <h3 class="dc-sub">14.6 Problem #1: piksele nie są 1:1 — przyczyny i co robić</h3>
        <p>„Zrobiłem 1:1, a i tak się różni o 1–3 px / czcionka wygląda inaczej”. To <strong>normalne</strong> — przeglądarka renderuje inaczej niż silnik Figmy. Najczęstsze przyczyny i lekarstwa:</p>
        <table class="dc-table">
            <thead><tr><th>Objaw</th><th>Przyczyna</th><th>Co zrobić</th></tr></thead>
            <tbody>
                <tr><td data-label="Objaw">Tekst „grubszy/cieńszy” niż w Figmie</td><td data-label="Przyczyna">font nie załadowany albo zła <code>font-weight</code> / brak <em>antialiasingu</em></td><td data-label="Co zrobić">załaduj dokładnie tę rodzinę i wagę (np. Inter 400/600/700), dodaj <code>-webkit-font-smoothing:antialiased</code></td></tr>
                <tr><td data-label="Objaw">Tekst „skacze” o kilka px w pionie</td><td data-label="Przyczyna"><code>line-height</code> liczone inaczej (Figma vs CSS) i <em>leading-trim</em></td><td data-label="Co zrobić">ustaw <code>line-height</code> w px 1:1 z Figmy, nie mnożnik „normal”</td></tr>
                <tr><td data-label="Objaw">Litery zbyt ściśnięte/rozstrzelone</td><td data-label="Przyczyna">brak <code>letter-spacing</code> z projektu</td><td data-label="Co zrobić">przepisz <code>letter-spacing</code> (Figma podaje w %/px — przelicz na em/px)</td></tr>
                <tr><td data-label="Objaw">Odstępy o 1 px za duże/za małe</td><td data-label="Przyczyna">sub-pixel rounding + <code>box-sizing</code></td><td data-label="Co zrobić"><code>box-sizing:border-box</code>, sprawdź czy padding nie dubluje się z gap autolayoutu</td></tr>
                <tr><td data-label="Objaw">Element przesunięty o stałą wartość</td><td data-label="Przyczyna">autolayout <em>gap</em> zmapowany jako margin (lub odwrotnie)</td><td data-label="Co zrobić">gap → <code>gap</code> we flex/grid; padding ramki → <code>padding</code> kontenera</td></tr>
                <tr><td data-label="Objaw">Cień/zaokrąglenie nie pasuje</td><td data-label="Przyczyna">inny model cienia / promień</td><td data-label="Co zrobić">przepisz <code>box-shadow</code> i <code>border-radius</code> z dokładnych wartości węzła</td></tr>
                <tr><td data-label="Objaw">Wszystko „trochę większe/mniejsze”</td><td data-label="Przyczyna">skala ramki / 2x export vs 1x</td><td data-label="Co zrobić">upewnij się, że pracujesz w skali 1x; nie skaluj całości transformem</td></tr>
                <tr><td data-label="Objaw">Obraz nieostry / rozjechany</td><td data-label="Przyczyna">zły <code>object-fit</code> lub wymiary</td><td data-label="Co zrobić"><code>object-fit:contain/cover</code> wg intencji, wymiary 1:1</td></tr>
            </tbody>
        </table>
        <div class="dc-note dc-note--warn"><strong>Złota zasada pixel-perfect:</strong> najpierw <strong>typografia i odstępy</strong> (90% rozjazdów), dopiero potem kolory i cienie. I pamiętaj — różnica 1 px wynikająca z renderingu fontu bywa <em>nie do usunięcia</em> bez psucia innych miejsc; cel to „nieodróżnialne gołym okiem”, nie „identyczne co do piksela na zrzucie”.</div>

        <h3 class="dc-sub">14.7 Problem #2: projekt niespójny lub niedokończony</h3>
        <p>Częsta sytuacja: ta sama rzecz w dwóch miejscach Figmy ma inny odstęp/kolor, brakuje stanów (hover, error, pusty), albo mobile różni się od desktopu bez reguły. Co robić:</p>
        <ul>
            <li><strong>Ustal źródło prawdy.</strong> Jeśli dwa warianty się kłócą — dopytaj, który jest aktualny, zamiast „uśredniać”.</li>
            <li><strong>Trzymaj się tokenów, nie przypadkowych wartości.</strong> Jeśli design ma zdefiniowane style/zmienne kolorów i typografii — używaj ich; pojedynczy „odjechany” odcień to zwykle błąd w projekcie, nie intencja.</li>
            <li><strong>Brakujące stany dopisz świadomie.</strong> Hover/disabled/focus/empty/error rzadko są w Figmie komplet — zaproponuj spójne z resztą, ale to zaznacz.</li>
            <li><strong>Responsywność:</strong> gdy brak makiety mobilnej, wyprowadź ją z desktopu wg czytelnych reguł (stack, pełna szerokość, większe pola dotykowe) i opisz decyzję.</li>
            <li><strong>Nie „naprawiaj” designu po cichu.</strong> Jeśli coś jest ewidentnie zepsute, napraw — ale powiedz, co i dlaczego, żeby właściciel mógł zweryfikować.</li>
        </ul>

        <h3 class="dc-sub">14.8 Workflow „pixel-perfect” krok po kroku</h3>
        <ol class="dc-steps">
            <li><strong>Odczytaj węzeł</strong> (MCP lub REST) — pobierz wartości i render PNG ramki.</li>
            <li><strong>Zbuduj komponent</strong> z dokładnych liczb (kolory, px, font, line-height, spacing, radius, shadow).</li>
            <li><strong>Render live</strong> w przeglądarce w tym samym viewport co Figma (np. szerokość ramki).</li>
            <li><strong>Nakładka / diff:</strong> porównaj zrzut live z renderem z Figmy (overlay 50% albo różnica). Schemat:
                <pre class="dc-pre"><span class="c"># koncepcja nakładki Figma ↔ live</span>
+-----------------------------+      +-----------------------------+
|  RENDER Z FIGMY (PNG)       |  vs  |  ZRZUT LIVE (przeglądarka)  |
|  [   przycisk   ]           |      |  [   przycisk   ]           |
+-----------------------------+      +-----------------------------+
        \_____ overlay 50% / diff pikseli _____/</pre>
            </li>
            <li><strong>Iteruj</strong> typografia → odstępy → kolory/cienie, aż różnice znikną „dla oka”.</li>
            <li><strong>Sprawdź na telefonie</strong> (realny viewport mobilny), nie tylko na desktopie.</li>
        </ol>
        @php
            $figs = ['figma-devmode','figma-overlay','figma-nodeid'];
        @endphp
        @foreach($figs as $f)
            @php $fp=null; foreach(['png','jpg','webp'] as $e){ if(is_file(public_path("img/docs/$f.$e"))){ $fp="img/docs/$f.$e"; break; } } @endphp
            @if($fp)<figure class="dc-fig"><img src="{{ asset($fp) }}" alt="{{ $f }}" loading="lazy"><figcaption>{{ $f }}</figcaption></figure>@endif
        @endforeach
        <div class="dc-note">Zrzuty poglądowe (Dev Mode, nakładka, node-id) możesz wrzucić do <code>public/img/docs/</code> jako <code>figma-devmode.png</code>, <code>figma-overlay.png</code>, <code>figma-nodeid.png</code> — pojawią się tu automatycznie.</div>

        <h3 class="dc-sub">14.9 Częste pułapki (checklist przed „gotowe”)</h3>
        <ul>
            <li>☐ Font: właściwa rodzina <strong>i waga</strong> faktycznie załadowane (sprawdź <code>document.fonts</code>).</li>
            <li>☐ <code>line-height</code> i <code>letter-spacing</code> przepisane z Figmy (nie domyślne).</li>
            <li>☐ <code>box-sizing:border-box</code>; padding vs gap autolayoutu nie zdublowane.</li>
            <li>☐ Kolory z dokładnych HEX/RGBA (uwaga na przezroczystość i nakładanie warstw).</li>
            <li>☐ Promienie, cienie, obramowania 1:1.</li>
            <li>☐ Stany hover/focus/disabled/empty/error przemyślane.</li>
            <li>☐ Obrazy: <code>object-fit</code>, wymiary, wersje 1x/2x.</li>
            <li>☐ Mobile sprawdzone na realnym viewport.</li>
            <li>☐ Cache assetów zbity (sufiks <code>?v=hash</code>), żeby widzieć nową wersję.</li>
        </ul>

        <h3 class="dc-sub">14.10 Dobre prompty „pod Figmę”</h3>
        <div class="dc-block"><span class="dc-block__label">Szablon</span>
            <pre class="dc-pre">Na <b>/adres</b> odwzoruj <b>[element]</b> 1:1 wg Figmy: <b>[link z node-id]</b>.
Przepisz dokładnie: kolory, font + wagę, line-height, letter-spacing, odstępy,
radius, cień. Zrób render, nakładkę Figma↔live i iteruj aż „dla oka” bez różnic.
Na końcu sprawdź na telefonie.</pre>
        </div>
        <ul>
            <li><em>„Zbuduj sekcję hero z tej ramki [link], pixel-perfect; pokaż nakładkę i listę różnic, które zostały.”</em></li>
            <li><em>„Tu font wygląda inaczej niż w Figmie — sprawdź czy Inter 600 się ładuje i popraw line-height na wartość z projektu.”</em></li>
            <li><em>„Design ma dwa różne odstępy dla tej karty — który jest aktualny? Ujednolić wg [node-id].”</em></li>
        </ul>
        <div class="dc-note">Więcej o pisaniu poleceń ogólnie: <a href="#prompty">sekcja 5 (Jak pisać prompty do Claude)</a>. Konfiguracja skrócona: <a href="#figma">sekcja 4 (Figma — szybki start)</a>.</div>
    </section>

    {{-- 15. MODUŁY SYSTEMU --}}
    <section class="dc-module" id="moduly">
        <div class="dc-module__head"><span class="dc-module__num">15.</span><h2>Moduły systemu — co robią i jak z nich korzystać</h2></div>
        <p class="dc-lead">SupportME to nie jedna strona, lecz zestaw współpracujących modułów: część <strong>publiczna</strong> (to, co widzi darczyńca i kandydat do pracy) oraz <strong>panel administracyjny</strong> pod <code>/panel</code> (to, czym zarządza zespół). Ta sekcja opisuje <strong>każdy moduł osobno</strong>: co robi, jak z niego korzystać krok po kroku i na co uważać. Przy każdym module jest miejsce na zrzut ekranu — wystarczy wrzucić plik do <code>public/img/docs/</code>, a pojawi się tu automatycznie.</p>

        <div class="dc-note"><strong>Jak czytać tę sekcję.</strong> Numeracja <code>15.1 … 15.x</code> to kolejne moduły. Najpierw idą moduły <em>publiczne</em> (frontend), potem <em>panel administracyjny</em>. Trasy (adresy URL) podane są dokładnie tak, jak działają na serwerze — możesz je wkleić w przeglądarkę po zalogowaniu do panelu.</p></div>

        {{-- Mapa modułów (przegląd) --}}
        <h3 class="dc-sub">15.0 Mapa modułów — szybki przegląd</h3>
        <p>Zanim wejdziesz w szczegóły, oto cała mapa systemu w jednym miejscu. Część publiczna prowadzi darczyńcę od taga NFC do płatności; panel obsługuje sprzedaż (CRM parafii), treści (kategorie, sklep) i rekrutację.</p>
        <div class="dc-modgrid">
            <div class="dc-modcard"><span class="dc-modcard__tag dc-modcard__tag--pub">Publiczne</span><h4>Sklep donacyjny NFC</h4><p>Strona startowa <code>/</code> — gadżety NFC, zakup dowolnej kwoty w modalu.</p></div>
            <div class="dc-modcard"><span class="dc-modcard__tag dc-modcard__tag--pub">Publiczne</span><h4>Cyfrowa Taca / wejście NFC</h4><p><code>/t/{tag}</code> — zbliżenie telefonu do taga; kategorie wsparcia.</p></div>
            <div class="dc-modcard"><span class="dc-modcard__tag dc-modcard__tag--pub">Publiczne</span><h4>Strona główna</h4><p><code>/main</code> — landing „Technologia, która pomaga czynić dobro".</p></div>
            <div class="dc-modcard"><span class="dc-modcard__tag dc-modcard__tag--pub">Publiczne</span><h4>Rekrutacja</h4><p><code>/praca</code> — oferty pracy i formularz aplikacyjny z CV.</p></div>
            <div class="dc-modcard"><span class="dc-modcard__tag dc-modcard__tag--pub">Publiczne</span><h4>Kontakt</h4><p><code>/kontakt</code> — formularz wiadomości trafiający do panelu.</p></div>
            <div class="dc-modcard"><span class="dc-modcard__tag dc-modcard__tag--adm">Panel</span><h4>Dashboard</h4><p><code>/panel</code> — podsumowanie wpłat i wykres ostatnich 30 dni.</p></div>
            <div class="dc-modcard"><span class="dc-modcard__tag dc-modcard__tag--adm">Panel</span><h4>Parafie + CRM</h4><p>Lista parafii, statusy lejka, notatki, przypisanie handlowca.</p></div>
            <div class="dc-modcard"><span class="dc-modcard__tag dc-modcard__tag--adm">Panel</span><h4>Kategorie</h4><p>Drzewo „Kogo wspieramy?" — sekcje na stronie głównej.</p></div>
            <div class="dc-modcard"><span class="dc-modcard__tag dc-modcard__tag--adm">Panel</span><h4>Handlowcy</h4><p>Zespół sprzedaży, przypisane województwa.</p></div>
            <div class="dc-modcard"><span class="dc-modcard__tag dc-modcard__tag--adm">Panel</span><h4>Parafie do obdzwonienia + mapa</h4><p>Baza leadów z OSM, statusy telefonów, interaktywna mapa pokrycia.</p></div>
            <div class="dc-modcard"><span class="dc-modcard__tag dc-modcard__tag--adm">Panel</span><h4>Sklep (produkty NFC)</h4><p>Gadżety sklepu donacyjnego: kwota minimalna, tag NFC, produkt domyślny.</p></div>
            <div class="dc-modcard"><span class="dc-modcard__tag dc-modcard__tag--adm">Panel</span><h4>Praca (stanowiska)</h4><p>Oferty pracy publikowane w sekcji <code>/praca</code>.</p></div>
            <div class="dc-modcard"><span class="dc-modcard__tag dc-modcard__tag--adm">Panel</span><h4>Aplikacje rekrutacyjne</h4><p>Skrzynka zgłoszeń z CV, statusy rekrutacji.</p></div>
            <div class="dc-modcard"><span class="dc-modcard__tag dc-modcard__tag--adm">Panel</span><h4>Wiadomości</h4><p>Skrzynka z formularza kontaktowego, znacznik przeczytania.</p></div>
            <div class="dc-modcard"><span class="dc-modcard__tag dc-modcard__tag--adm">Panel</span><h4>Eventy / analityka</h4><p>Zliczanie otwarć tagów i wpłat — źródło danych dla statystyk.</p></div>
        </div>

        {{-- ========================= FRONTEND ========================= --}}
        <h3 class="dc-sub" style="margin-top:2.4rem">Część publiczna (frontend)</h3>

        {{-- 15.1 SKLEP DONACYJNY NFC --}}
        <h3 class="dc-sub">15.1 Sklep donacyjny NFC — strona startowa <code>/</code></h3>
        <p>To <strong>strona główna serwisu</strong> (adres <code>/</code>). Prezentuje siatkę gadżetów charytatywnych powiązanych z tagami NFC (np. „Serduszko"). Darczyńca wybiera produkt, w okienku (modalu) podaje kwotę nie niższą niż minimum tego produktu i przechodzi do płatności — wszystko jedną transakcją w bramce. Produkt oznaczony jako <strong>domyślny</strong> otwiera się automatycznie po wejściu na stronę. Dane produktów pochodzą z modułu <em>Sklep</em> w panelu (15.11).</p>
        <ol class="dc-steps">
            <li><strong>Wejdź na stronę</strong> <code>/</code> — zobaczysz siatkę produktów. Produkt domyślny może od razu otworzyć modal zakupu.</li>
            <li><strong>Wybierz produkt</strong> i kliknij, aby otworzyć modal z polem kwoty.</li>
            <li><strong>Podaj kwotę</strong> w złotówkach — system pilnuje, by była liczbą całkowitą, nie niższą niż minimum produktu i nie wyższą niż 5000 zł.</li>
            <li><strong>Kliknij „Kup"</strong> (<code>POST /sklep/kup/{slug}</code>) — tworzona jest płatność w bramce i następuje przekierowanie do zapłaty.</li>
            <li><strong>Po płatności</strong> darczyńca wraca na ekran potwierdzenia („Możesz zabrać towar", <code>/zwrot/{order}</code>).</li>
        </ol>
        <div class="dc-note">Gdy bramka płatności (PayU) jest jeszcze w trybie zatwierdzania, działa <strong>bypass</strong> (<code>config('payment.bypass')</code>): zakup od razu kieruje na stronę z podziękowaniem, z pominięciem realnej płatności. To celowe na czas wdrożenia.</div>
        @php $f="modul-sklep-nfc"; $fp=null; foreach(['png','jpg','webp'] as $e){ if(is_file(public_path("img/docs/$f.$e"))){ $fp="img/docs/$f.$e"; break; } } @endphp
        @if($fp)<figure class="dc-fig"><img src="{{ asset($fp) }}" loading="lazy" alt="Sklep donacyjny NFC"><figcaption>Sklep donacyjny NFC — siatka produktów i modal zakupu.</figcaption></figure>@endif
        <div class="dc-note">Zrzut: wrzuć do <code>public/img/docs/modul-sklep-nfc.png</code> — pojawi się automatycznie.</div>

        {{-- 15.2 CYFROWA TACA / TAG NFC --}}
        <h3 class="dc-sub">15.2 Cyfrowa Taca / wejście NFC — <code>/t/{tag}</code> i kategorie</h3>
        <p>To serce idei „cyfrowej tacy". Darczyńca <strong>zbliża telefon do taga NFC</strong> (naklejki/karty), telefon otwiera adres <code>/t/{tag_uid}</code>, a system rozpoznaje, do czego ten tag jest przypisany, rejestruje zdarzenie <em>otwarcia taga</em> i przekierowuje we właściwe miejsce: jeśli tag wskazuje parafię — na jej stronę produktu; jeśli wskazuje gadżet sklepu — na sklep z automatycznie otwartym modalem tego produktu. Uzupełnieniem są <strong>kategorie wsparcia</strong> (<code>/kategoria/{slug}</code>) prezentowane na stronie głównej w sekcji „Kogo wspieramy?".</p>
        <ol class="dc-steps">
            <li><strong>Zbliż telefon do taga</strong> — otworzy się <code>/t/{tag_uid}</code> (lub wpisz adres ręcznie do testu).</li>
            <li><strong>System rejestruje event</strong> <code>tag_open</code> (lokalnie i asynchronicznie do bramki) — to później widać w analityce (15.15).</li>
            <li><strong>Następuje przekierowanie</strong> 302 na stronę produktu parafii albo na sklep z modalem gadżetu.</li>
            <li><strong>Kategorie:</strong> na stronie głównej darczyńca może wejść w kategorię (<code>/kategoria/{slug}</code>); dla kategorii typu „parafie" wyświetla się lista realnych parafii.</li>
        </ol>
        <div class="dc-note dc-note--warn">UID taga musi być przypisany do <strong>aktywnej</strong> parafii lub aktywnego produktu sklepu. Jeśli tag prowadzi „donikąd", sprawdź w panelu, czy rekord jest aktywny i czy UID się zgadza (pola <em>tag UID</em> / <em>tag NFC</em>).</div>
        @php $f="modul-tag-nfc"; $fp=null; foreach(['png','jpg','webp'] as $e){ if(is_file(public_path("img/docs/$f.$e"))){ $fp="img/docs/$f.$e"; break; } } @endphp
        @if($fp)<figure class="dc-fig"><img src="{{ asset($fp) }}" loading="lazy" alt="Cyfrowa Taca / wejście NFC"><figcaption>Wejście z taga NFC i strona kategorii wsparcia.</figcaption></figure>@endif
        <div class="dc-note">Zrzut: wrzuć do <code>public/img/docs/modul-tag-nfc.png</code> — pojawi się automatycznie.</div>

        {{-- 15.3 STRONA GŁÓWNA /main --}}
        <h3 class="dc-sub">15.3 Strona główna (landing) — <code>/main</code></h3>
        <p>Marketingowy landing „Technologia, która pomaga czynić dobro" — opowiada o idei SupportME, prezentuje sekcję „Kogo wspieramy?" (zasilaną z modułu Kategorie) i kieruje dalej. To dobra strona „o nas/jak to działa", oddzielona od sklepu, który zajmuje adres główny <code>/</code>.</p>
        <ol class="dc-steps">
            <li><strong>Wejdź na</strong> <code>/main</code> — landing renderuje się z aktualnymi kategoriami z bazy.</li>
            <li><strong>Sekcja „Kogo wspieramy?"</strong> pokazuje kategorie najwyższego poziomu (kolejność i treść ustawiasz w module Kategorie, 15.10).</li>
            <li><strong>Treść/wygląd</strong> zmieniasz w szablonie (Blade) — to typowa robota „pod Figmę" (patrz sekcje 4 i 14).</li>
        </ol>
        @php $f="modul-main"; $fp=null; foreach(['png','jpg','webp'] as $e){ if(is_file(public_path("img/docs/$f.$e"))){ $fp="img/docs/$f.$e"; break; } } @endphp
        @if($fp)<figure class="dc-fig"><img src="{{ asset($fp) }}" loading="lazy" alt="Strona główna /main"><figcaption>Landing /main — „Technologia, która pomaga czynić dobro".</figcaption></figure>@endif
        <div class="dc-note">Zrzut: wrzuć do <code>public/img/docs/modul-main.png</code> — pojawi się automatycznie.</div>

        {{-- 15.4 REKRUTACJA /praca --}}
        <h3 class="dc-sub">15.4 Rekrutacja — <code>/praca</code></h3>
        <p>Publiczna część rekrutacyjna: lista aktywnych stanowisk (<code>/praca</code>), pojedyncza oferta na osobnej podstronie (<code>/praca/oferta/{position}</code>) oraz formularz aplikacyjny — zarówno <strong>spontaniczny</strong> (<code>/praca/aplikuj</code>), jak i <strong>na konkretną ofertę</strong> (<code>/praca/{position}/aplikuj</code>). Kandydat załącza CV; plik trafia na <strong>prywatny</strong> dysk (niepubliczny), a zgłoszenie ląduje w panelu (15.13). Treść ofert pochodzi z modułu Praca w panelu (15.12).</p>
        <ol class="dc-steps">
            <li><strong>Kandydat wchodzi na</strong> <code>/praca</code> i przegląda aktywne stanowiska.</li>
            <li><strong>Otwiera ofertę</strong> (<code>/praca/oferta/{position}</code>) i klika „Aplikuj".</li>
            <li><strong>Wypełnia formularz:</strong> imię i nazwisko, e-mail, telefon (opcjonalnie), wiadomość, plik CV (PDF/DOC/DOCX, maks. 5 MB) i zgodę RODO.</li>
            <li><strong>Wysyła zgłoszenie</strong> — walidacja serwerowa pilnuje formatu CV i zgody; zgłoszenie pojawia się w panelu jako nieprzeczytane.</li>
        </ol>
        <div class="dc-note dc-note--warn">CV przyjmowane jest <strong>tylko</strong> jako PDF, DOC lub DOCX (sprawdzane po rozszerzeniu i typie MIME), maks. 5 MB, a zgoda RODO jest <strong>wymagana</strong>. To zabezpieczenie serwerowe — nie da się go obejść przez sam HTML.</div>
        @php $f="modul-praca"; $fp=null; foreach(['png','jpg','webp'] as $e){ if(is_file(public_path("img/docs/$f.$e"))){ $fp="img/docs/$f.$e"; break; } } @endphp
        @if($fp)<figure class="dc-fig"><img src="{{ asset($fp) }}" loading="lazy" alt="Rekrutacja /praca"><figcaption>Lista ofert pracy i formularz aplikacyjny z CV.</figcaption></figure>@endif
        <div class="dc-note">Zrzut: wrzuć do <code>public/img/docs/modul-praca.png</code> — pojawi się automatycznie.</div>

        {{-- 15.5 KONTAKT --}}
        <h3 class="dc-sub">15.5 Kontakt — <code>/kontakt</code></h3>
        <p>Prosty formularz kontaktowy. Wiadomości zapisują się w bazie i trafiają do skrzynki w panelu (15.14). Temat bywa wstępnie wypełniony, gdy ktoś trafi tu z linku „Aplikuj" (parametr <code>?stanowisko=</code>).</p>
        <ol class="dc-steps">
            <li><strong>Wejdź na</strong> <code>/kontakt</code> i wypełnij pola: imię i nazwisko, e-mail, telefon (opcjonalnie), temat (opcjonalnie), wiadomość.</li>
            <li><strong>Wyślij</strong> — po zapisaniu zobaczysz komunikat „Dziękujemy, odezwiemy się.".</li>
            <li><strong>Wiadomość</strong> czeka w panelu w module Wiadomości jako <em>nieprzeczytana</em>.</li>
        </ol>
        @php $f="modul-kontakt"; $fp=null; foreach(['png','jpg','webp'] as $e){ if(is_file(public_path("img/docs/$f.$e"))){ $fp="img/docs/$f.$e"; break; } } @endphp
        @if($fp)<figure class="dc-fig"><img src="{{ asset($fp) }}" loading="lazy" alt="Formularz kontaktowy"><figcaption>Formularz kontaktowy /kontakt.</figcaption></figure>@endif
        <div class="dc-note">Zrzut: wrzuć do <code>public/img/docs/modul-kontakt.png</code> — pojawi się automatycznie.</div>

        {{-- ========================= PANEL ========================= --}}
        <h3 class="dc-sub" style="margin-top:2.4rem">Panel administracyjny — <code>/panel</code></h3>
        <div class="dc-note"><strong>Logowanie do panelu.</strong> Wejdź na <code>/panel/login</code>, podaj dane administratora i zaloguj się. Wszystkie poniższe moduły (15.6–15.15) wymagają zalogowania — bez sesji panel przekieruje na ekran logowania.</div>

        {{-- 15.6 DASHBOARD --}}
        <h3 class="dc-sub">15.6 Dashboard — <code>/panel</code></h3>
        <p>Pulpit startowy panelu. Po zalogowaniu pokazuje <strong>podsumowanie wpłat</strong> (łącznie i za ostatnie 30 dni) oraz <strong>wykres dziennych zakupów</strong> z ostatnich 30 dni. To szybki „rzut oka" na kondycję zbiórki przed wejściem w szczegóły.</p>
        <ol class="dc-steps">
            <li><strong>Zaloguj się</strong> i trafisz prosto na dashboard (<code>/panel</code>).</li>
            <li><strong>Odczytaj kafelki</strong> podsumowania: suma wpłat ogółem i z 30 dni.</li>
            <li><strong>Spójrz na wykres</strong> dziennych zakupów, by ocenić trend.</li>
            <li><strong>Stąd przechodź</strong> do szczegółowych modułów przez menu panelu.</li>
        </ol>
        @php $f="modul-dashboard"; $fp=null; foreach(['png','jpg','webp'] as $e){ if(is_file(public_path("img/docs/$f.$e"))){ $fp="img/docs/$f.$e"; break; } } @endphp
        @if($fp)<figure class="dc-fig"><img src="{{ asset($fp) }}" loading="lazy" alt="Dashboard panelu"><figcaption>Dashboard — podsumowanie wpłat i wykres 30 dni.</figcaption></figure>@endif
        <div class="dc-note">Zrzut: wrzuć do <code>public/img/docs/modul-dashboard.png</code> — pojawi się automatycznie.</div>

        {{-- 15.7 PARAFIE + CRM --}}
        <h3 class="dc-sub">15.7 Parafie + CRM — <code>/panel/products</code></h3>
        <p>Centralny moduł sprzedażowy. „Produkty" to w praktyce <strong>parafie</strong> (cyfrowa taca) z pełnym CRM: filtrowanie po statusie lejka i wyszukiwanie po nazwie/mieście/województwie, edycja danych parafii (telefon, www, województwo, instrukcja odbioru, opis, zdjęcia, UID taga), przypisanie <strong>handlowca</strong>, szybka zmiana <strong>statusu</strong> oraz <strong>notatki CRM</strong>. Status steruje publikacją: parafia <em>aktywna</em> jest widoczna publicznie, pozostałe statusy traktowane są jak lead (ukryta).</p>
        <ol class="dc-steps">
            <li><strong>Otwórz</strong> <code>/panel/products</code> — zobaczysz listę parafii z zakładkami statusów i wyszukiwarką.</li>
            <li><strong>Filtruj/szukaj</strong> po statusie albo wpisz nazwę/miasto/województwo.</li>
            <li><strong>Dodaj parafię</strong> („Dodaj") lub edytuj istniejącą — uzupełnij dane, w tym <strong>UID taga NFC</strong> (wymagany i unikalny) oraz cenę (podawana w zł, w bazie trzymana w groszach).</li>
            <li><strong>Zmień status</strong> w lejku (np. kontakt → test → wdrożenie → aktywna). Ustawienie statusu „aktywna" <strong>publikuje</strong> parafię.</li>
            <li><strong>Dodawaj notatki CRM</strong> (zapisywane na bieżąco) i <strong>przypisuj handlowca</strong> odpowiedzialnego za parafię.</li>
            <li><strong>Statystyki parafii</strong> (<code>/panel/products/{id}/stats</code>) pokazują wpłaty i otwarcia taga dla konkretnej parafii.</li>
        </ol>
        <div class="dc-note dc-note--warn"><strong>UID taga jest unikalny.</strong> Jeśli przy zapisie pojawi się błąd o zajętym UID, oznacza to, że tag jest już przypisany do innej parafii. Pamiętaj też: dopiero status <em>aktywna</em> czyni parafię widoczną dla darczyńców.</div>
        @php $f="modul-parafie-crm"; $fp=null; foreach(['png','jpg','webp'] as $e){ if(is_file(public_path("img/docs/$f.$e"))){ $fp="img/docs/$f.$e"; break; } } @endphp
        @if($fp)<figure class="dc-fig"><img src="{{ asset($fp) }}" loading="lazy" alt="Parafie + CRM"><figcaption>Lista parafii z CRM — statusy, notatki, przypisanie handlowca.</figcaption></figure>@endif
        <div class="dc-note">Zrzut: wrzuć do <code>public/img/docs/modul-parafie-crm.png</code> — pojawi się automatycznie.</div>

        {{-- 15.8 (statystyki produktu jako podmoduł CRM — opisany powyżej; przechodzimy dalej) --}}

        {{-- 15.9 KATEGORIE --}}
        <h3 class="dc-sub">15.8 Kategorie — <code>/panel/categories</code></h3>
        <p>Edytowalne <strong>drzewo kategorii</strong> zasilające sekcję „Kogo wspieramy?" na stronach publicznych. Kategorie mają hierarchię (rodzic → dzieci), własną kolejność, slug, opis (intro), ikonkę oraz <strong>źródło pozycji</strong> (np. „parafie" — wtedy kategoria listuje realne parafie z bazy). Możesz je dodawać, edytować, zmieniać kolejność i usuwać.</p>
        <ol class="dc-steps">
            <li><strong>Otwórz</strong> <code>/panel/categories</code> — kategorie pokazane są jako drzewo z wcięciami.</li>
            <li><strong>Dodaj kategorię</strong> — podaj nazwę (slug wygeneruje się sam, jeśli go nie wpiszesz), wybierz rodzica (lub brak = poziom główny), opis, ikonkę i źródło.</li>
            <li><strong>Zmień kolejność</strong> przyciskami w górę/dół — zmienia pozycję wśród rodzeństwa.</li>
            <li><strong>Edytuj/usuń</strong> w razie potrzeby. Po usunięciu rodzica jego dzieci nie znikają — stają się kategoriami poziomu głównego.</li>
        </ol>
        <div class="dc-note">Slug i unikalność są pilnowane automatycznie; przy edycji nie wybierzesz jako rodzica samej kategorii ani jej potomka (ochrona przed zapętleniem drzewa).</div>
        @php $f="modul-kategorie"; $fp=null; foreach(['png','jpg','webp'] as $e){ if(is_file(public_path("img/docs/$f.$e"))){ $fp="img/docs/$f.$e"; break; } } @endphp
        @if($fp)<figure class="dc-fig"><img src="{{ asset($fp) }}" loading="lazy" alt="Kategorie"><figcaption>Drzewo kategorii „Kogo wspieramy?".</figcaption></figure>@endif
        <div class="dc-note">Zrzut: wrzuć do <code>public/img/docs/modul-kategorie.png</code> — pojawi się automatycznie.</div>

        {{-- 15.10 HANDLOWCY --}}
        <h3 class="dc-sub">15.9 Handlowcy — <code>/panel/salespeople</code></h3>
        <p>Rejestr zespołu sprzedaży. Każdy handlowiec ma dane kontaktowe i przypisane <strong>województwa</strong>, w których działa. Handlowców przypisuje się do parafii (15.7) i do leadów na mapie/liście do obdzwonienia (15.10), dzięki czemu widać, kto za co odpowiada. Lista pokazuje też, ile parafii ma przypisany dany handlowiec.</p>
        <ol class="dc-steps">
            <li><strong>Otwórz</strong> <code>/panel/salespeople</code> — lista handlowców z liczbą przypisanych parafii.</li>
            <li><strong>Dodaj handlowca</strong> — imię i nazwisko (wymagane), e-mail, telefon i listę województw, które obsługuje.</li>
            <li><strong>Edytuj</strong> dane lub zakres województw w dowolnym momencie.</li>
            <li><strong>Usuwanie</strong> jest bezpieczne: parafie nie znikają, tylko tracą przypisanie do tego handlowca.</li>
        </ol>
        @php $f="modul-handlowcy"; $fp=null; foreach(['png','jpg','webp'] as $e){ if(is_file(public_path("img/docs/$f.$e"))){ $fp="img/docs/$f.$e"; break; } } @endphp
        @if($fp)<figure class="dc-fig"><img src="{{ asset($fp) }}" loading="lazy" alt="Handlowcy"><figcaption>Zespół handlowców i przypisane województwa.</figcaption></figure>@endif
        <div class="dc-note">Zrzut: wrzuć do <code>public/img/docs/modul-handlowcy.png</code> — pojawi się automatycznie.</div>

        {{-- 15.11 PARAFIE DO OBDZWONIENIA + MAPA --}}
        <h3 class="dc-sub">15.10 Parafie do obdzwonienia + mapa pokrycia — <code>/panel/potential-parishes</code>, <code>/panel/coverage</code></h3>
        <p>Baza <strong>leadów</strong> (potencjalnych parafii, m.in. z OpenStreetMap) do telefonicznego pozyskania. Moduł ma dwa widoki: <strong>listę</strong> z filtrami (województwo, miasto, nazwa, status, handlowiec oraz filtr „ma numer telefonu" — domyślnie pokazywane są tylko parafie z numerem) i <strong>interaktywną mapę pokrycia</strong> (Leaflet z klastrowaniem markerów). Przy każdym leadzie zapisujesz status rozmowy, notatkę, telefon i przypisujesz handlowca; pierwsze przejście do statusu „zadzwoniono" stempluje datę pierwszego kontaktu.</p>
        <ol class="dc-steps">
            <li><strong>Otwórz listę</strong> <code>/panel/potential-parishes</code> — domyślnie widać leady z numerem telefonu, ze stronicowaniem.</li>
            <li><strong>Zawężaj</strong> po województwie, mieście, nazwie, statusie lub handlowcu; przełącz filtr „ma numer", jeśli chcesz zobaczyć też leady bez telefonu.</li>
            <li><strong>Obdzwaniaj i zapisuj</strong> status (auto-zapis), notatkę, telefon i handlowca prosto przy rekordzie.</li>
            <li><strong>Otwórz mapę</strong> <code>/panel/coverage</code> — markery ładują się asynchronicznie; nad mapą widać liczniki per status oraz pokrycie per województwo. Markery można filtrować tak samo jak listę.</li>
        </ol>
        <div class="dc-note">Mapa nie wgrywa tysięcy punktów na sztywno do HTML — pobiera je lekkim zapytaniem JSON po starcie. Kolor markera odpowiada statusowi leada, więc od razu widać, gdzie jest robota do zrobienia.</div>
        @php $f="modul-obdzwanianie-mapa"; $fp=null; foreach(['png','jpg','webp'] as $e){ if(is_file(public_path("img/docs/$f.$e"))){ $fp="img/docs/$f.$e"; break; } } @endphp
        @if($fp)<figure class="dc-fig"><img src="{{ asset($fp) }}" loading="lazy" alt="Parafie do obdzwonienia i mapa pokrycia"><figcaption>Lista leadów + interaktywna mapa pokrycia.</figcaption></figure>@endif
        <div class="dc-note">Zrzut: wrzuć do <code>public/img/docs/modul-obdzwanianie-mapa.png</code> — pojawi się automatycznie.</div>

        {{-- 15.12 SKLEP (PRODUKTY NFC) --}}
        <h3 class="dc-sub">15.11 Sklep — produkty NFC — <code>/panel/shop-items</code></h3>
        <p>Zarządzanie gadżetami <strong>sklepu donacyjnego</strong> (to one wyświetlają się na stronie <code>/</code>, moduł 15.1). Każdy produkt ma nazwę, <strong>minimalną kwotę</strong> (w zł, w bazie w groszach), opcjonalny <strong>tag NFC</strong>, grafikę, kolejność oraz flagę <strong>domyślny</strong> (tylko jeden produkt może być domyślny — to on otwiera się automatycznie w sklepie).</p>
        <ol class="dc-steps">
            <li><strong>Otwórz</strong> <code>/panel/shop-items</code> — lista produktów sklepu wg kolejności.</li>
            <li><strong>Dodaj produkt</strong> — nazwa, minimalna kwota (1–5000 zł), opcjonalny tag NFC, grafika, kolejność.</li>
            <li><strong>Ustaw „domyślny"</strong> dla jednego produktu — zaznaczenie automatycznie odznacza poprzedni domyślny.</li>
            <li><strong>Włączaj/wyłączaj</strong> produkt (toggle) — nieaktywne nie pokazują się w sklepie. Edytuj lub usuwaj wedle potrzeby.</li>
        </ol>
        <div class="dc-note dc-note--warn">Tag NFC produktu (jeśli ustawiony) musi być <strong>unikalny</strong> i nie może kolidować z tagiem innego produktu. To ten sam mechanizm, który obsługuje wejście <code>/t/{tag}</code> (15.2).</div>
        @php $f="modul-sklep-panel"; $fp=null; foreach(['png','jpg','webp'] as $e){ if(is_file(public_path("img/docs/$f.$e"))){ $fp="img/docs/$f.$e"; break; } } @endphp
        @if($fp)<figure class="dc-fig"><img src="{{ asset($fp) }}" loading="lazy" alt="Sklep — produkty NFC"><figcaption>Zarządzanie produktami sklepu donacyjnego.</figcaption></figure>@endif
        <div class="dc-note">Zrzut: wrzuć do <code>public/img/docs/modul-sklep-panel.png</code> — pojawi się automatycznie.</div>

        {{-- 15.13 PRACA (STANOWISKA) --}}
        <h3 class="dc-sub">15.12 Praca — stanowiska — <code>/panel/positions</code></h3>
        <p>Treść ofert pracy publikowanych w sekcji <code>/praca</code> (15.4). Stanowisko ma tytuł, lokalizację, rodzaj zatrudnienia, opis (edytor), kolejność oraz przełącznik aktywności. Lista pokazuje też liczbę zgłoszeń na każdą ofertę.</p>
        <ol class="dc-steps">
            <li><strong>Otwórz</strong> <code>/panel/positions</code> — lista stanowisk z liczbą aplikacji.</li>
            <li><strong>Dodaj stanowisko</strong> — tytuł (wymagany), lokalizacja, rodzaj zatrudnienia, opis, kolejność.</li>
            <li><strong>Włącz/wyłącz</strong> ofertę (toggle) — tylko aktywne pokazują się publicznie w <code>/praca</code>.</li>
            <li><strong>Edytuj/usuń</strong> w razie potrzeby.</li>
        </ol>
        @php $f="modul-praca-panel"; $fp=null; foreach(['png','jpg','webp'] as $e){ if(is_file(public_path("img/docs/$f.$e"))){ $fp="img/docs/$f.$e"; break; } } @endphp
        @if($fp)<figure class="dc-fig"><img src="{{ asset($fp) }}" loading="lazy" alt="Praca — stanowiska"><figcaption>Zarządzanie ofertami pracy.</figcaption></figure>@endif
        <div class="dc-note">Zrzut: wrzuć do <code>public/img/docs/modul-praca-panel.png</code> — pojawi się automatycznie.</div>

        {{-- 15.14 APLIKACJE REKRUTACYJNE --}}
        <h3 class="dc-sub">15.13 Aplikacje rekrutacyjne — <code>/panel/applications</code></h3>
        <p>Skrzynka zgłoszeń przesłanych przez kandydatów (15.4). Najnowsze na górze, z licznikiem nieprzeczytanych i filtrami (po ofercie i po statusie rekrutacji). Otwarcie zgłoszenia oznacza je jako przeczytane; CV pobierasz bezpiecznie (plik leży na prywatnym dysku, niedostępny publicznie). Status rekrutacji zmieniasz (np. do sprawdzenia / zaakceptowany / odrzucony).</p>
        <ol class="dc-steps">
            <li><strong>Otwórz</strong> <code>/panel/applications</code> — lista zgłoszeń, najnowsze u góry.</li>
            <li><strong>Filtruj</strong> po ofercie lub statusie, by skupić się na właściwych kandydatach.</li>
            <li><strong>Otwórz zgłoszenie</strong> — zobaczysz dane kandydata i wiadomość; otwarcie zdejmuje znacznik „nieprzeczytane".</li>
            <li><strong>Pobierz CV</strong> przyciskiem pobierania (dostępne tylko dla zalogowanego administratora).</li>
            <li><strong>Ustaw status</strong> rekrutacji; w razie potrzeby usuń zgłoszenie (kasuje też plik CV).</li>
        </ol>
        <div class="dc-note dc-note--warn">CV są <strong>danymi osobowymi</strong> — pobieranie jest ograniczone do zalogowanego administratora, a pliki nie są publicznie dostępne. Usunięcie zgłoszenia trwale kasuje również plik CV.</div>
        @php $f="modul-aplikacje"; $fp=null; foreach(['png','jpg','webp'] as $e){ if(is_file(public_path("img/docs/$f.$e"))){ $fp="img/docs/$f.$e"; break; } } @endphp
        @if($fp)<figure class="dc-fig"><img src="{{ asset($fp) }}" loading="lazy" alt="Aplikacje rekrutacyjne"><figcaption>Skrzynka zgłoszeń rekrutacyjnych z CV.</figcaption></figure>@endif
        <div class="dc-note">Zrzut: wrzuć do <code>public/img/docs/modul-aplikacje.png</code> — pojawi się automatycznie.</div>

        {{-- 15.15 WIADOMOŚCI --}}
        <h3 class="dc-sub">15.14 Wiadomości — <code>/panel/messages</code></h3>
        <p>Skrzynka wiadomości z formularza kontaktowego (15.5). Najnowsze na górze, z licznikiem nieprzeczytanych. Otwarcie wiadomości oznacza ją jako przeczytaną; zbędne można usuwać.</p>
        <ol class="dc-steps">
            <li><strong>Otwórz</strong> <code>/panel/messages</code> — lista wiadomości z licznikiem nieprzeczytanych.</li>
            <li><strong>Kliknij wiadomość</strong>, by zobaczyć pełną treść; otwarcie zdejmuje status „nieprzeczytana".</li>
            <li><strong>Usuń</strong> wiadomość, gdy jest już obsłużona.</li>
        </ol>
        @php $f="modul-wiadomosci"; $fp=null; foreach(['png','jpg','webp'] as $e){ if(is_file(public_path("img/docs/$f.$e"))){ $fp="img/docs/$f.$e"; break; } } @endphp
        @if($fp)<figure class="dc-fig"><img src="{{ asset($fp) }}" loading="lazy" alt="Wiadomości"><figcaption>Skrzynka wiadomości z formularza kontaktowego.</figcaption></figure>@endif
        <div class="dc-note">Zrzut: wrzuć do <code>public/img/docs/modul-wiadomosci.png</code> — pojawi się automatycznie.</div>

        {{-- 15.16 EVENTY / ANALITYKA --}}
        <h3 class="dc-sub">15.15 Eventy i analityka</h3>
        <p>To moduł „pod maską", który zasila statystyki. System zapisuje zdarzenia — przede wszystkim <strong>otwarcia tagów NFC</strong> (<code>tag_open</code>, generowane przy wejściu <code>/t/{tag}</code>, 15.2) oraz <strong>wpłaty</strong> potwierdzane przez bramkę. Na tej podstawie liczone są podsumowania na Dashboardzie (15.6) i statystyki pojedynczej parafii (w CRM, 15.7). Część zdarzeń wysyłana jest również asynchronicznie do bramki, by nie spowalniać przekierowania darczyńcy.</p>
        <ol class="dc-steps">
            <li><strong>Otwarcia tagów</strong> rejestrują się automatycznie — nic nie musisz robić; pojawią się w statystykach.</li>
            <li><strong>Wpłaty</strong> liczone są po potwierdzeniu płatności (webhook bramki <code>/webhooks/gateway</code>).</li>
            <li><strong>Podgląd zbiorczy:</strong> Dashboard (<code>/panel</code>) — sumy i wykres 30 dni.</li>
            <li><strong>Podgląd dla parafii:</strong> statystyki produktu (<code>/panel/products/{id}/stats</code>).</li>
        </ol>
        <div class="dc-note">Jeśli statystyki „nie rosną", sprawdź najpierw, czy tagi są przypisane do <strong>aktywnych</strong> rekordów (15.2/15.7) oraz czy działają usługi w tle (kolejki/scheduler — sekcja 11), bo to one przetwarzają część zdarzeń.</div>
        @php $f="modul-analityka"; $fp=null; foreach(['png','jpg','webp'] as $e){ if(is_file(public_path("img/docs/$f.$e"))){ $fp="img/docs/$f.$e"; break; } } @endphp
        @if($fp)<figure class="dc-fig"><img src="{{ asset($fp) }}" loading="lazy" alt="Eventy i analityka"><figcaption>Statystyki wpłat i otwarć tagów.</figcaption></figure>@endif
        <div class="dc-note">Zrzut: wrzuć do <code>public/img/docs/modul-analityka.png</code> — pojawi się automatycznie.</div>

        <div class="dc-note" style="margin-top:2rem"><strong>Powiązania między modułami w skrócie.</strong> Sklep w panelu (15.11) → wyświetla się na <code>/</code> (15.1). Kategorie (15.8) → sekcja „Kogo wspieramy?" na <code>/main</code> (15.3). Praca w panelu (15.12) → oferty na <code>/praca</code> (15.4) → zgłoszenia w Aplikacjach (15.13). Kontakt (15.5) → Wiadomości (15.14). Tagi NFC (15.2) i wpłaty → Eventy/analityka (15.15) → Dashboard (15.6) i CRM parafii (15.7).</div>
    </section>

</div>

<a href="#gora" class="dc-top" aria-label="Do góry">↑</a>
@endsection
