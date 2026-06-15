{{-- SP-CHURCH-REGULAMIN: nowy layout marki (storefront/church/shop/regulamin.blade.php) --}}
@extends('layouts.landing')

@section('title', 'Regulamin sklepu — ' . config('shop.name'))
@section('meta-description', 'Regulamin sklepu — zasady sprzedaży, płatności, zwrotów i reklamacji.')

@push('head')
<link rel="stylesheet" href="{{ asset('css/subpages.css') }}?v={{ substr(md5_file(public_path('css/subpages.css')), 0, 10) }}">
@endpush

@section('content')
    {{-- Znacznik weryfikacyjny (widoczny w HTML) — potwierdza render z NOWEGO pliku church --}}
    <!-- SP-CHURCH-REGULAMIN: storefront/church/shop/regulamin.blade.php -->
    {{-- SUB-HERO (spójnie z marką landingu) --}}
    <section class="sp-subhero">
        <div class="sp-subhero__inner">
            <p class="sp-eyebrow">Dokumenty</p>
            <h1>Regulamin sklepu</h1>
            <p class="sp-lede">Zasady sprzedaży, płatności, realizacji zamówień, zwrotów i reklamacji.</p>
        </div>
    </section>

    <div class="sp-wrap">
        <div class="sp-container sp-doc sp-legal">
            <article class="sp-legal__body">
                <section class="sp-legal__section">
                    <h2>§1. Sprzedawca</h2>
                    <p>
                        Sklep prowadzi: <strong>MARCIN LULA</strong><br>
                        ul. dr Izabeli Wolfram 11, 05-800 Pruszków, Polska<br>
                        NIP: 8741624637 · REGON: 341224327<br>
                        e-mail: <a href="mailto:kontakt@please-support-me.com">kontakt@please-support-me.com</a>
                    </p>
                </section>

                <section class="sp-legal__section">
                    <h2>§2. Zasady sprzedaży</h2>
                    <p>Sklep prowadzi sprzedaż bezobsługową w fizycznym punkcie sprzedaży. Klient skanuje tag NFC
                        umieszczony przy produkcie (lub wybiera produkt na stronie), opłaca zakup online i odbiera
                        towar bezpośrednio w punkcie, zgodnie z instrukcją odbioru wyświetloną po opłaceniu.</p>
                    <p>Wszystkie ceny podane są w złotych polskich (PLN) i są cenami brutto.</p>
                </section>

                <section class="sp-legal__section">
                    <h2>§3. Płatności</h2>
                    <p>Płatności obsługuje <strong>PayU S.A.</strong> z siedzibą w Poznaniu (60-166), ul. Grunwaldzka 186 —
                        krajowa instytucja płatnicza nadzorowana przez Komisję Nadzoru Finansowego. Dostępne metody:
                        BLIK, szybki przelew online (pay-by-link). Sklep nie przechowuje danych płatniczych klienta.</p>
                </section>

                <section class="sp-legal__section">
                    <h2>§4. Realizacja zamówienia</h2>
                    <p>Zamówienie jest realizowane <strong>natychmiast po zaksięgowaniu płatności</strong> — klient
                        odbiera towar samodzielnie w punkcie sprzedaży bezpośrednio po opłaceniu, zgodnie z instrukcją
                        wyświetloną na ekranie potwierdzenia. Numer zamówienia widoczny jest na ekranie potwierdzenia.</p>
                </section>

                <section class="sp-legal__section">
                    <h2>§5. Zwroty i odstąpienie od umowy</h2>
                    <p>Konsument ma prawo odstąpić od umowy w terminie 14 dni bez podania przyczyny (nie dotyczy
                        produktów szybko psujących się, np. żywności — art. 38 ustawy o prawach konsumenta).
                        W celu zwrotu skontaktuj się ze sprzedawcą mailowo lub telefonicznie; środki zwracamy tą samą
                        metodą płatności w terminie do 14 dni.</p>
                </section>

                <section class="sp-legal__section">
                    <h2>§6. Reklamacje</h2>
                    <p>Reklamacje można składać mailowo na <a href="mailto:kontakt@please-support-me.com">kontakt@please-support-me.com</a> lub
                        telefonicznie. Rozpatrujemy je w terminie 14 dni od otrzymania. Reklamacja powinna zawierać
                        numer zamówienia, opis wady oraz dane kontaktowe.</p>
                </section>

                <section class="sp-legal__section">
                    <h2>§7. Dane osobowe</h2>
                    <p>Administratorem danych osobowych przekazywanych w związku z płatnością jest PayU S.A.
                        Sklep nie zakłada kont klientów i nie gromadzi danych osobowych kupujących.</p>
                </section>
            </article>
        </div>
    </div>
@endsection
