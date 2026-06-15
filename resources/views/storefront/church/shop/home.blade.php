@extends('layouts.landing')

@section('content')
    {{-- HERO --}}
    <section class="lp-hero">
        <div class="lp-hero__inner">
            <div class="lp-hero__copy">
                <h1>Technologia,<br class="br-m">która <br class="br-d">pomaga czynić dobro</h1>
                <p>Łączymy ludzi, wartości<br class="br-m"> i nowoczesne płatności,<br>aby wspieranie ważnych inicjatyw było prostsze<br class="br-d"> niż kiedykolwiek wcześniej.</p>
                <div class="lp-badges">
                    <a href="#"><img src="{{ asset('img/landing/appstore.png') }}" alt="Pobierz w App Store"></a>
                    <a href="#"><img src="{{ asset('img/landing/googleplay.png') }}" alt="Pobierz w Google Play"></a>
                </div>
            </div>
            <div class="lp-hero__art">
                <div class="lp-circle lp-circle--hero lp-circle--ghero" aria-hidden="true"></div>
            </div>
        </div>
    </section>

    {{-- KOGO WSPIERAMY --}}
    <section class="lp-section lp-support" id="kogo-wspieramy">
        <div class="lp-head">
            <h2>Kogo wspieramy?</h2>
            <p>Każdego dnia tysiące osób chce pomagać, ale często brakuje prostych i wygodnych narzędzi do działania. Tworzymy rozwiązanie, które umożliwia wsparcie jednym zbliżeniem telefonu.</p>
        </div>
        <div class="lp-cats">
            @foreach($categories as $cat)
                <a class="lp-cat" href="{{ route('category', $cat['slug']) }}">
                    <span class="lp-cat__disc">@if(!empty($cat['icon']))<img class="lp-cat__disc-img" src="{{ asset('storage/' . $cat['icon']) }}" alt="">@endif</span>
                    <span class="lp-cat__label">{!! $cat['label_html'] !!}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- JAK TO DZIALA --}}
    <section class="lp-section lp-how">
        <div class="lp-head">
            <h2>Jak to działa?</h2>
            <p>Jedno zbliżenie telefonu. Kilka sekund. Realna pomoc.</p>
        </div>

        <div class="lp-steps">
            {{-- KROK 1 — grafika z lewej --}}
            <div class="lp-step">
                <div class="lp-step__art"><div class="lp-circle lp-circle--step lp-circle--g1" aria-hidden="true"></div></div>
                <div class="lp-step__body">
                    <p class="lp-step__num">KROK 1</p>
                    <p><b>Zbliż telefon do znacznika NFC</b><br>W miejscu zbiórki, podczas wydarzenia, w organizacji lub przestrzeni publicznej zbliżasz telefon do oznaczonego punktu NFC.</p>
                </div>
            </div>

            {{-- KROK 2 — grafika z prawej --}}
            <div class="lp-step lp-step--rev">
                <div class="lp-step__art"><div class="lp-circle lp-circle--step lp-circle--g2" aria-hidden="true"></div></div>
                <div class="lp-step__body">
                    <p class="lp-step__num">KROK 2</p>
                    <p><b>Wybierz wsparcie</b><br>Automatycznie otwiera się bezpieczna strona płatności.</p>
                    <p>Zobaczysz:<br>• nazwę organizacji lub instytucji<br>• cel zbiórki<br>• sugerowaną kwotę wsparcia<br>• możliwość wpisania własnej kwoty</p>
                </div>
            </div>

            {{-- KROK 3 — grafika z lewej --}}
            <div class="lp-step">
                <div class="lp-step__art"><div class="lp-circle lp-circle--step lp-circle--g3" aria-hidden="true"></div></div>
                <div class="lp-step__body">
                    <p class="lp-step__num">KROK 3</p>
                    <p><b>Dokonaj płatności</b></p>
                </div>
            </div>

            {{-- KROK 4 — grafika z prawej --}}
            <div class="lp-step lp-step--rev">
                <div class="lp-step__art"><div class="lp-circle lp-circle--step lp-circle--g4" aria-hidden="true"></div></div>
                <div class="lp-step__body">
                    <p class="lp-step__num">KROK 4</p>
                    <p><b>Otrzymujesz podziękowanie</b><br>Po zakończonej wpłacie trafiasz na specjalnie przygotowaną stronę podziękowania.</p>
                    <p>Może tam na Ciebie czekać:<br>• wiadomość od organizacji<br>• podziękowanie od zespołu lub opiekuna projektu<br>• zdjęcie z realizowanej inicjatywy<br>• informacja o wykorzystaniu środków<br>• inspirujący cytat lub krótka historia związana z projektem</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Zamknięcie (w Figmie obecne w wersji mobilnej) --}}
    <section class="lp-closing">
        <p>Tworzymy technologię, która wspiera ludzi w pomaganiu i zamienianiu dobrych intencji w realną pomoc.<br><br><b>Dobro przekazane dalej ma największą wartość.</b></p>
    </section>
@endsection
