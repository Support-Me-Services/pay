@extends('layouts.public')

@section('title', 'SupportME — sprzedawaj bez kasy i bez obsługi')

@section('content')
    <section class="hero">
        <div class="container">
            <div class="hero-tile">
            <span class="hero-badge">Bramka płatności NFC · płatności BLIK przez PayU</span>
            <h1>Sprzedawaj bez kasy i&nbsp;bez obsługi.<br>
                <span class="highlight">Tag NFC + telefon klienta</span> = cała Twoja kasa.</h1>
            <p class="mt-2">Klient przykłada telefon do taga przy produkcie, płaci BLIK-iem w aplikacji swojego banku
                i zabiera towar. Bez kolejki, bez terminala, bez pracownika. Ty widzisz każdą sprzedaż na żywo.</p>
            <div class="hero-actions mt-3">
                <a href="#kontakt" class="btn btn-primary">Umów bezpłatną wycenę</a>
                <a href="https://please-support-me.com" target="_blank" rel="noopener" class="btn btn-outline-light">Zobacz sklep demo →</a>
            </div>
            <p class="hero-note">Wdrożenie od 5 dni roboczych · bez umowy na czas określony</p>
            </div>
        </div>
    </section>

    {{-- Pasek zaufania / liczb --}}
    <section class="lp-stats" aria-label="Najważniejsze liczby">
        <div class="container lp-stats-row">
            <div class="lp-stat">
                <div class="lp-stat-value">~15 s</div>
                <div class="lp-stat-label">od skanu do zapłaty</div>
            </div>
            <div class="lp-stat">
                <div class="lp-stat-value">0 zł</div>
                <div class="lp-stat-label">za terminal i sprzęt kasowy</div>
            </div>
            <div class="lp-stat">
                <div class="lp-stat-value">100%</div>
                <div class="lp-stat-label">transakcji online, bez gotówki</div>
            </div>
            <div class="lp-stat">
                <div class="lp-stat-value">24/7</div>
                <div class="lp-stat-label">punkt sprzedaje także w nocy</div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2>Jak to działa</h2>
            <div class="steps-grid mt-3">
                <div class="step-card">
                    <div class="step-no">1</div>
                    <div class="step-icon">📲</div>
                    <h3>Skanuje</h3>
                    <p class="text-muted mb-0">Klient przykłada telefon do taga NFC przy produkcie. Otwiera się strona
                        produktu z ceną — bez instalowania żadnej aplikacji.</p>
                </div>
                <div class="step-card">
                    <div class="step-no">2</div>
                    <div class="step-icon">🏦</div>
                    <h3>Płaci w aplikacji banku</h3>
                    <p class="text-muted mb-0">Jedno kliknięcie i telefon przechodzi do płatności BLIK w aplikacji
                        banku klienta. Pieniądze od razu lecą na Twoje konto.</p>
                </div>
                <div class="step-card">
                    <div class="step-no">3</div>
                    <div class="step-icon">✅</div>
                    <h3>Zabiera towar</h3>
                    <p class="text-muted mb-0">Po opłaceniu klient widzi ekran „Możesz zabrać towar" z instrukcją
                        odbioru. Transakcja zapisuje się w Twoim panelu.</p>
                </div>
            </div>
            <div class="demo-row mt-3">
                <span class="text-muted">Sprawdź na własnym telefonie:</span>
                <a href="https://please-support-me.com" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">Demo: sklep klasyczny</a>
            </div>
        </div>
    </section>

    <section class="section section-alt">
        <div class="container">
            <h2>Dla kogo</h2>
            <p class="text-muted">Wszędzie tam, gdzie pracownik przy kasie kosztuje więcej, niż zarabia punkt:</p>
            <div class="chips mt-2">
                <span class="chip">🍦 Lody i mrożonki</span>
                <span class="chip">🍯 Miody prosto z pasieki</span>
                <span class="chip">💐 Kwiaty i bukiety</span>
                <span class="chip">🥕 Stoiska z warzywami</span>
                <span class="chip">🔌 Wypożyczalnie (powerbanki, parasole)</span>
                <span class="chip">🤖 Vending bez automatu</span>
            </div>
            <a href="#kontakt" class="btn btn-primary mt-3">Sprawdź, czy pasuje do Twojego punktu</a>
        </div>
    </section>

    {{-- Case studies --}}
    <section class="section" id="wdrozenia">
        <div class="container">
            <h2>Przykładowe wdrożenia</h2>
            <p class="text-muted">Tak SupportME zarabia w punktach pilotażowych. Liczby z trzech scenariuszy
                wdrożeniowych — Twoje policzymy indywidualnie.</p>
            <div class="case-grid mt-3">
                <div class="case-card">
                    <div class="case-icon">🍦</div>
                    <h3>Lodziarnia czynna także nocą</h3>
                    <p class="text-muted">Zamrażarka z lodami przed lokalem, każdy smak z własnym tagiem NFC.
                        Po zamknięciu lokalu sprzedaż nie ustaje — klienci kupują sami.</p>
                    <ul class="case-stats">
                        <li><strong>+38%</strong> sprzedaży dzięki godzinom 21:00–7:00</li>
                        <li><strong>~3 900 zł</strong> dodatkowego utargu miesięcznie</li>
                        <li><strong>0</strong> dodatkowych etatów</li>
                    </ul>
                    <span class="badge badge-muted">przykładowe wdrożenie pilotażowe</span>
                </div>
                <div class="case-card">
                    <div class="case-icon">🍯</div>
                    <h3>Pasieka przy drodze krajowej</h3>
                    <p class="text-muted">Stragan z miodami 9 km od domu pszczelarza. Wcześniej „puszka na zaufanie",
                        teraz każdy słoik płacony BLIK-iem przed zabraniem.</p>
                    <ul class="case-stats">
                        <li><strong>92%</strong> transakcji opłaconych (vs ~60% przy puszce)</li>
                        <li><strong>2 h dziennie</strong> mniej dojazdów po gotówkę</li>
                        <li><strong>SMS</strong> przy każdej sprzedaży — pełna kontrola zdalna</li>
                    </ul>
                    <span class="badge badge-muted">przykładowe wdrożenie pilotażowe</span>
                </div>
                <div class="case-card">
                    <div class="case-icon">⛱️</div>
                    <h3>Wypożyczalnia parasoli w hotelu</h3>
                    <p class="text-muted">Stojak z parasolami przy basenie. Gość skanuje tag, płaci kaucję BLIK-iem,
                        oddaje parasol — kaucja wraca automatycznie.</p>
                    <ul class="case-stats">
                        <li><strong>~1 etat</strong> recepcji odzyskany w sezonie</li>
                        <li><strong>87%</strong> konwersji od skanu do płatności</li>
                        <li><strong>−70%</strong> zaginięć sprzętu dzięki kaucjom</li>
                    </ul>
                    <span class="badge badge-muted">przykładowe wdrożenie pilotażowe</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Bezpieczeństwo --}}
    <section class="section section-dark" id="bezpieczenstwo">
        <div class="container">
            <h2>Bezpieczeństwo</h2>
            <p class="sec-lead">Sprzedaż bezobsługowa działa tylko wtedy, gdy pieniądze i punkt są chronione.
                Dlatego pod spodem jest infrastruktura klasy bankowej:</p>
            <div class="sec-grid mt-3">
                <div class="sec-card">
                    <div class="sec-icon">🏛️</div>
                    <h3>Licencjonowany operator płatności</h3>
                    <p>Transakcje obsługuje PayU — krajowa instytucja płatnicza nadzorowana przez KNF.
                        My nie dotykamy Twoich pieniędzy.</p>
                </div>
                <div class="sec-card">
                    <div class="sec-icon">🔐</div>
                    <h3>Szyfrowanie TLS</h3>
                    <p>Cała komunikacja klient–sklep–bramka–operator jest szyfrowana. Żadna płatność
                        nie przechodzi otwartym kanałem.</p>
                </div>
                <div class="sec-card">
                    <div class="sec-icon">✍️</div>
                    <h3>Webhooki podpisane HMAC-SHA256</h3>
                    <p>Każde potwierdzenie płatności jest kryptograficznie podpisane. Nie da się „podrobić"
                        opłaconego zamówienia.</p>
                </div>
                <div class="sec-card">
                    <div class="sec-icon">🛡️</div>
                    <h3>Moduł AntiTheft</h3>
                    <p>System cyklicznie weryfikuje, czy przy Twoim punkcie nie pojawiły się obce tagi NFC
                        podszywające się pod Twoje produkty.</p>
                </div>
                <div class="sec-card">
                    <div class="sec-icon">💳</div>
                    <h3>Zero danych karty u Ciebie</h3>
                    <p>Klient płaci w aplikacji własnego banku. Po stronie sklepu nie ma żadnych danych
                        kart ani danych logowania.</p>
                </div>
                <div class="sec-card">
                    <div class="sec-icon">🇪🇺</div>
                    <h3>Zgodność z RODO</h3>
                    <p>Minimalny zakres danych, jasny cel przetwarzania, serwery w UE. Dostajesz gotowe
                        zapisy do swojej polityki prywatności.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Cennik --}}
    <section class="section" id="cennik">
        <div class="container">
            <h2>Ile to kosztuje</h2>
            <p class="text-muted">Prosty model: płacisz głównie wtedy, kiedy sprzedajesz. Bez kosztów terminala,
                bez opłat za sprzęt. Ceny przykładowe dla etapu MVP — finalną ofertę liczymy pod Twój punkt.</p>
            <div class="pricing-grid mt-3">
                <div class="price-card">
                    <h3>Start</h3>
                    <div class="price-tag">0 zł <span>/ mies.</span></div>
                    <p class="text-muted">Dla jednego punktu na próbę.</p>
                    <ul class="price-list">
                        <li>prowizja <strong>od 1,9%</strong> od transakcji</li>
                        <li>do 10 tagów NFC w cenie wdrożenia</li>
                        <li>panel sprzedaży na żywo</li>
                        <li>wypłaty na konto D+1</li>
                    </ul>
                    <a href="#kontakt" class="btn btn-secondary btn-block">Zapytaj o Start</a>
                </div>
                <div class="price-card price-featured">
                    <span class="badge badge-brand">najczęściej wybierany</span>
                    <h3>Standard</h3>
                    <div class="price-tag">od 99 zł <span>/ mies.</span></div>
                    <p class="text-muted">Dla punktu, który sprzedaje codziennie.</p>
                    <ul class="price-list">
                        <li>prowizja <strong>od 0,9%</strong> od transakcji</li>
                        <li>nielimitowane tagi i produkty</li>
                        <li>moduł AntiTheft + alerty SMS/e-mail</li>
                        <li>kaucje i wypożyczenia (app2app)</li>
                        <li>priorytetowe wsparcie</li>
                    </ul>
                    <a href="#kontakt" class="btn btn-primary btn-block">Umów wycenę</a>
                </div>
            </div>
            <p class="small text-muted mt-2">Do prowizji SupportME dochodzi standardowa opłata operatora płatności (PayU).
                Podane stawki są przykładowe i zależą od wolumenu transakcji.</p>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="section section-alt" id="faq">
        <div class="container" style="max-width:820px">
            <h2>Częste pytania</h2>
            <div class="faq mt-3">
                <details class="faq-item">
                    <summary>Co jeśli klient nie ma NFC w telefonie?</summary>
                    <p>Obok każdego taga umieszczamy kod QR z tym samym linkiem. Skan aparatem działa na każdym
                        smartfonie z 2015+ roku. W praktyce NFC ma dziś ponad 95% telefonów w Polsce.</p>
                </details>
                <details class="faq-item">
                    <summary>Co z kradzieżami? Przecież nikt nie pilnuje towaru.</summary>
                    <p>Sprzedaż bezobsługowa działa od lat (sklepy autonomiczne, stragany „na zaufanie") — straty są
                        zwykle niższe niż koszt jednego etatu. Dodatkowo: ekran „Możesz zabrać towar" widzi tylko ten,
                        kto zapłacił, moduł AntiTheft pilnuje, czy ktoś nie podmienił tagów, a punkt można objąć
                        zwykłym monitoringiem. Każdą transakcję widzisz na żywo, więc rozbieżność stanu wykryjesz od razu.</p>
                </details>
                <details class="faq-item">
                    <summary>Jak szybko mogę wystartować?</summary>
                    <p>Standardowe wdrożenie to 5–10 dni roboczych: konfiguracja sklepu i produktów, podpięcie konta
                        PayU, zaprogramowanie i wysyłka tagów NFC. Tagi przyklejasz sam — to naklejki.</p>
                </details>
                <details class="faq-item">
                    <summary>Czy potrzebuję terminala płatniczego albo kasy fiskalnej?</summary>
                    <p>Terminal — nie, kasą jest telefon klienta. Kwestię kasy fiskalnej reguluje Twoja forma sprzedaży;
                        płatności bezgotówkowe z ewidencją transakcji często korzystają ze zwolnień — podpowiemy,
                        jak to wygląda w Twoim przypadku, a panel eksportuje pełną ewidencję sprzedaży.</p>
                </details>
                <details class="faq-item">
                    <summary>Jak i kiedy otrzymuję pieniądze?</summary>
                    <p>Płatności trafiają na Twoje konto w PayU i są wypłacane na rachunek bankowy — standardowo
                        następnego dnia roboczego (D+1). W panelu widzisz każdą transakcję w momencie zapłaty.</p>
                </details>
                <details class="faq-item">
                    <summary>Czy klient musi mieć internet w telefonie?</summary>
                    <p>Tak — strona produktu i płatność BLIK wymagają transmisji danych. Tag NFC sam w sobie nie
                        potrzebuje zasilania ani internetu. Przy punktach w słabym zasięgu pomagamy dobrać lokalizację
                        tagów lub doradzamy mikro-hotspot.</p>
                </details>
                <details class="faq-item">
                    <summary>Czy mogę sprzedawać kilka produktów w różnych cenach?</summary>
                    <p>Tak. Każdy produkt (albo każda półka, smak, rozmiar) dostaje własny tag z własną ceną i zdjęciem.
                        Ceny zmieniasz w panelu w kilka sekund — bez wymiany tagów.</p>
                </details>
            </div>
        </div>
    </section>

    {{-- CTA band --}}
    <section class="cta-band">
        <div class="container">
            <h2>Policzymy, ile zarobi Twój punkt bez obsługi</h2>
            <p>Bezpłatna analiza: koszt wdrożenia, prognoza sprzedaży, zwrot z inwestycji. Bez zobowiązań.</p>
            <a href="#kontakt" class="btn btn-light">Zostaw kontakt — odpowiadamy w 1 dzień</a>
        </div>
    </section>

    <section class="section" id="kontakt">
        <div class="container" style="max-width:640px">
            <h2>Zostaw kontakt</h2>
            <p class="text-muted">Opisz swój punkt sprzedaży — odezwiemy się i policzymy, ile zaoszczędzisz.</p>

            @if(session('lead_ok'))
                <div class="alert alert-success">Dziękujemy! Twoje zgłoszenie dotarło — odezwiemy się w ciągu 1 dnia roboczego.</div>
            @endif

            @if($errors->any())
                <div class="alert alert-error">Popraw zaznaczone pola formularza.</div>
            @endif

            <form method="POST" action="{{ route('lead.store') }}">
                @csrf
                {{-- Honeypot antyspamowy — pole niewidoczne dla ludzi --}}
                <div style="position:absolute;left:-9999px" aria-hidden="true">
                    <label for="website">Strona WWW</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="name">Imię i nazwisko *</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="email">E-mail *</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                    @error('email')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="phone">Telefon *</label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required>
                    @error('phone')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="company">Firma (opcjonalnie)</label>
                    <input type="text" id="company" name="company" value="{{ old('company') }}">
                    @error('company')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="message">Wiadomość *</label>
                    <textarea id="message" name="message" rows="4" required>{{ old('message') }}</textarea>
                    @error('message')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-primary btn-block">Wyślij zgłoszenie</button>
            </form>
        </div>
    </section>
@endsection
