@extends('layouts.landing')

@section('title', 'Samouczek — modyfikacja stron z Claude · SupportME')
@section('meta-description', 'Jak modyfikować podstrony SupportME z pomocą Claude: dostęp do serwera (SSH/Google Cloud), konfiguracja Figmy (Dev Mode + MCP) i pisanie promptów.')

@push('head')
<link rel="stylesheet" href="{{ asset('css/docs.css') }}?v={{ substr(md5_file(public_path('css/docs.css')), 0, 10) }}">
@endpush

@section('content')
@php
    $toc = [
        ['1','fakty','Co jest gdzie — szybkie fakty'],
        ['2','dostep','Dostęp do serwera (SSH / Google Cloud)'],
        ['3','deploy','Jak wdrażane są zmiany (deploy)'],
        ['4','figma','Konfiguracja Figmy (Dev Mode + MCP)'],
        ['5','prompty','Jak pisać prompty do Claude'],
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
                <tr><td>Adres IP serwera</td><td><span class="dc-inline">34.118.46.252</span></td></tr>
                <tr><td>Użytkownik SSH</td><td><span class="dc-inline">root</span></td></tr>
                <tr><td>Projekt Google Cloud</td><td><span class="dc-inline">please-support-me-499509</span></td></tr>
                <tr><td>Nazwa instancji (VM)</td><td><span class="dc-inline">instance-20260615-112018</span></td></tr>
                <tr><td>Strefa (zone)</td><td><span class="dc-inline">europe-central2-a</span></td></tr>
                <tr><td>Katalog aplikacji na serwerze</td><td><span class="dc-inline">/var/www/support-me</span></td></tr>
                <tr><td>System / PHP</td><td>Debian 12 · PHP 8.2 · MySQL/MariaDB</td></tr>
                <tr><td>Domeny</td><td>please-support-me.com (Taca/sklep) · pay.please-support-me.com (bramka) · shop2.please-support-me.com</td></tr>
                <tr><td>Repozytorium (lokalnie)</td><td><span class="dc-inline">/var/www/pay/unified</span> → GitHub</td></tr>
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
        <div class="dc-module__head"><span class="dc-module__num">3.</span><h2>Jak wdrażane są zmiany (deploy)</h2></div>
        <p class="dc-lead">Claude edytuje pliki w lokalnym repo (<code>/var/www/pay/unified</code>), a potem wysyła je na serwer przez <strong>rsync po SSH</strong> i czyści cache. To dlatego potrzebny jest dostęp z sekcji 2. Schemat, który Claude wykonuje automatycznie:</p>
        <pre class="dc-pre"><span class="c"># 1) wyślij zmienione pliki na serwer (przykład: jedna podstrona + jej CSS)</span>
<span class="p">$</span> rsync -az resources/views/.../inwestorzy.blade.php public/css/inwestorzy.css \
        root@<i>34.118.46.252</i>:<i>/var/www/support-me/</i>

<span class="c"># 2) wyczyść cache widoków/tras na serwerze</span>
<span class="p">$</span> ssh root@<i>34.118.46.252</i> 'cd /var/www/support-me && php artisan view:clear && php artisan route:clear'

<span class="c"># 3) (jeśli zmiana w bazie) migracja dla właściwego tenanta</span>
<span class="p">$</span> ssh root@<i>34.118.46.252</i> 'cd /var/www/support-me && TENANT=please-support-me.com php artisan migrate --force'</pre>
        <div class="dc-note">Nie musisz tego robić ręcznie — wystarczy, że napiszesz „wdróż na prod”, a Claude wykona wysyłkę, migracje i wyczyści cache, a na końcu sprawdzi, czy strona zwraca <strong>200</strong>.</div>
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

</div>

<a href="#gora" class="dc-top" aria-label="Do góry">↑</a>
@endsection
