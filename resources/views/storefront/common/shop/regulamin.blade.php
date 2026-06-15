@extends('layouts.public')

@section('title', 'Regulamin sklepu — ' . config('shop.name'))

@section('content')
    <section class="section" style="padding-top:32px">
        <div class="container" style="max-width:760px">
            <h1 style="font-size:1.7rem">Regulamin sklepu</h1>

            <div class="prose">
                <h3>§1. Sprzedawca</h3>
                <p>
                    Sklep prowadzi: <strong>MARCIN LULA</strong><br>
                    ul. dr Izabeli Wolfram 11, 05-800 Pruszków, Polska<br>
                    NIP: 8741624637 · REGON: 341224327<br>
                    e-mail: <a href="mailto:kontakt@please-support-me.com">kontakt@please-support-me.com</a>
                </p>

                <h3>§2. Zasady sprzedaży</h3>
                <p>Sklep prowadzi sprzedaż bezobsługową w fizycznym punkcie sprzedaży. Klient skanuje tag NFC
                    umieszczony przy produkcie (lub wybiera produkt na stronie), opłaca zakup online i odbiera
                    towar bezpośrednio w punkcie, zgodnie z instrukcją odbioru wyświetloną po opłaceniu.</p>
                <p>Wszystkie ceny podane są w złotych polskich (PLN) i są cenami brutto.</p>

                <h3>§3. Płatności</h3>
                <p>Płatności obsługuje <strong>PayU S.A.</strong> z siedzibą w Poznaniu (60-166), ul. Grunwaldzka 186 —
                    krajowa instytucja płatnicza nadzorowana przez Komisję Nadzoru Finansowego. Dostępne metody:
                    BLIK, szybki przelew online (pay-by-link). Sklep nie przechowuje danych płatniczych klienta.</p>

                <h3>§4. Realizacja zamówienia</h3>
                <p>Zamówienie jest realizowane <strong>natychmiast po zaksięgowaniu płatności</strong> — klient
                    odbiera towar samodzielnie w punkcie sprzedaży bezpośrednio po opłaceniu, zgodnie z instrukcją
                    wyświetloną na ekranie potwierdzenia. Numer zamówienia widoczny jest na ekranie potwierdzenia.</p>

                <h3>§5. Zwroty i odstąpienie od umowy</h3>
                <p>Konsument ma prawo odstąpić od umowy w terminie 14 dni bez podania przyczyny (nie dotyczy
                    produktów szybko psujących się, np. żywności — art. 38 ustawy o prawach konsumenta).
                    W celu zwrotu skontaktuj się ze sprzedawcą mailowo lub telefonicznie; środki zwracamy tą samą
                    metodą płatności w terminie do 14 dni.</p>

                <h3>§6. Reklamacje</h3>
                <p>Reklamacje można składać mailowo na <a href="mailto:kontakt@please-support-me.com">kontakt@please-support-me.com</a> lub
                    telefonicznie. Rozpatrujemy je w terminie 14 dni od otrzymania. Reklamacja powinna zawierać
                    numer zamówienia, opis wady oraz dane kontaktowe.</p>

                <h3>§7. Dane osobowe</h3>
                <p>Administratorem danych osobowych przekazywanych w związku z płatnością jest PayU S.A.
                    Sklep nie zakłada kont klientów i nie gromadzi danych osobowych kupujących.</p>
            </div>
        </div>
    </section>
@endsection
