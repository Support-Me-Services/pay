@extends('layouts.landing')

@section('title','Szkoły — SupportME')
@section('meta-description','SupportME rozwiązuje problem braku gotówki podczas szkolnych pikników i wydarzeń. Dzięki technologii NFC uczniowie i szkoły skutecznie zbierają środki bezgotówkowo.')

@push('head')
<style>
    .szk{
        --szk-navy:#24324A; --szk-blue:#1473C0;
        --szk-display:'Libre Baskerville',Georgia,serif;
        --szk-ui:'Inter',system-ui,-apple-system,sans-serif;
        color:var(--szk-navy);
        font-family:var(--szk-ui);
    }

    /* Pasek tytułu (band) */
    .szk-band{
        display:flex; align-items:center; justify-content:center;
        min-height:200px; padding:64px 22px;
        background:linear-gradient(134.5deg, rgba(78,127,167,.22) 14%, rgba(20,115,192,.22) 99%);
    }
    .szk-band h1{
        margin:0; text-align:center;
        font-family:var(--szk-display); font-weight:700;
        color:var(--szk-navy); font-size:2.5rem; line-height:1.15;
    }

    /* Lead — wyśrodkowany blok pod paskiem */
    .szk-lead{
        max-width:880px; margin:0 auto; padding:56px 22px 8px;
        text-align:center;
    }
    .szk-lead h2{
        margin:0 0 18px;
        font-family:var(--szk-display); font-weight:700;
        color:var(--szk-navy); font-size:2.2rem; line-height:1.2;
    }
    .szk-lead p{
        margin:0 auto; max-width:760px;
        font-size:1.18rem; line-height:1.55; color:var(--szk-navy);
    }

    /* Sekcje krok po kroku */
    .szk-sections{ max-width:1180px; margin:0 auto; padding:32px 22px 56px; }
    .szk-row{
        display:flex; align-items:center; justify-content:space-between;
        gap:56px; margin:48px 0;
    }
    .szk-row--reverse{ flex-direction:row-reverse; }

    .szk-figure{ flex:0 0 375px; max-width:375px; }
    .szk-figure img{
        display:block; width:100%; height:auto; border-radius:50%;
    }

    .szk-text{ flex:1 1 0; max-width:667px; }
    .szk-text h3{
        margin:0 0 14px;
        font-family:var(--szk-display); font-weight:700;
        color:var(--szk-navy); font-size:1.56rem; line-height:1.32;
    }
    .szk-text p{
        margin:0 0 12px; font-size:1.18rem; line-height:1.65; color:var(--szk-navy);
    }
    .szk-text p:last-child{ margin-bottom:0; }
    .szk-text ul{
        margin:6px 0 0; padding-left:28px;
        font-size:1.18rem; line-height:1.65; color:var(--szk-navy);
    }
    .szk-text li{ margin:2px 0; }
    .szk-text strong{ font-weight:700; color:var(--szk-navy); }

    /* CTA */
    .szk-cta{
        display:flex; flex-wrap:wrap; gap:16px; justify-content:center;
        max-width:1180px; margin:0 auto; padding:8px 22px 72px;
    }
    .szk-btn{
        display:inline-flex; align-items:center; justify-content:center;
        font-weight:700; font-size:1.02rem; text-decoration:none;
        border-radius:999px; padding:15px 34px;
        transition:transform .12s ease, box-shadow .12s ease, border-color .12s ease, color .12s ease;
    }
    .szk-btn:hover{ transform:translateY(-1px); }
    .szk-btn--primary{
        background:var(--szk-blue); color:#fff;
        box-shadow:0 10px 26px rgba(20,115,192,.30);
    }
    .szk-btn--primary:hover{ box-shadow:0 14px 30px rgba(20,115,192,.40); }
    .szk-btn--ghost{
        background:#fff; color:var(--szk-navy); border:1.5px solid #D7E0EA;
    }
    .szk-btn--ghost:hover{ border-color:var(--szk-blue); color:var(--szk-blue); }

    @media(max-width:960px){
        .szk-row,
        .szk-row--reverse{ flex-direction:column; gap:28px; text-align:center; margin:40px 0; }
        .szk-figure{ flex-basis:auto; max-width:300px; margin:0 auto; }
        .szk-text{ max-width:680px; }
        .szk-text ul{ text-align:left; display:inline-block; }
    }
    @media(max-width:640px){
        .szk-band{ min-height:150px; padding:44px 18px; }
        .szk-band h1{ font-size:1.9rem; }
        .szk-lead{ padding:40px 18px 4px; }
        .szk-lead h2{ font-size:1.65rem; }
        .szk-lead p{ font-size:1.06rem; }
        .szk-text h3{ font-size:1.3rem; }
        .szk-text p,
        .szk-text ul{ font-size:1.06rem; }
        .szk-figure{ max-width:240px; }
        .szk-btn{ width:100%; }
    }
</style>
@endpush

@section('content')
<div class="szk">

    <div class="szk-band">
        <h1>Szkoły</h1>
    </div>

    <div class="szk-lead">
        <h2>Support Me rozwiązuje problem braku gotówki podczas szkolnych pikników i wydarzeń</h2>
        <p>
            W Support Me pomagamy szkołom skutecznie zbierać środki podczas festynów, pikników
            rodzinnych, kiermaszów oraz wydarzeń organizowanych przez uczniów. Dzięki technologii NFC
            uczestnicy mogą dokonywać wpłat i zakupów bezgotówkowych za pomocą telefonu, nawet jeśli
            nie mają przy sobie gotówki.
        </p>
    </div>

    <div class="szk-sections">

        {{-- 1. Wdrożenie w szkole --}}
        <div class="szk-row">
            <div class="szk-figure">
                <img src="{{ asset('img/szkoly/grafika.svg') }}" alt="">
            </div>
            <div class="szk-text">
                <h3>Wdrożenie w szkole</h3>
                <p>
                    Nasza firma dostarcza szkole gotowe do użycia tagi NFC przypisane do konkretnych
                    stoisk, klas, kół zainteresowań lub wydarzeń. Tagi mogą zostać umieszczone przy:
                </p>
                <ul>
                    <li>stoiskach z ciastami i wypiekami,</li>
                    <li>stoiskach z lemoniadą i napojami,</li>
                    <li>kiermaszach rękodzieła,</li>
                    <li>prezentacjach projektów uczniowskich,</li>
                    <li>wydarzeniach sportowych i artystycznych,</li>
                    <li>zbiórkach charytatywnych organizowanych przez szkołę.</li>
                </ul>
            </div>
        </div>

        {{-- 2. Dokonanie płatności przez uczestnika wydarzenia (tekst po lewej) --}}
        <div class="szk-row szk-row--reverse">
            <div class="szk-figure">
                <img src="{{ asset('img/szkoly/grafika.svg') }}" alt="">
            </div>
            <div class="szk-text">
                <h3>Dokonanie płatności przez uczestnika wydarzenia</h3>
                <p>
                    Podczas szkolnego pikniku rodzice, goście oraz mieszkańcy mogą wesprzeć inicjatywy
                    uczniów lub dokonać zakupu przygotowanych przez nich produktów. Wystarczy zbliżyć
                    telefon do oznaczonego tagu NFC. Telefon automatycznie otworzy stronę Support Me,
                    na której użytkownik:
                </p>
                <ul>
                    <li>wpisuje lub wybiera kwotę,</li>
                    <li>widzi cel zbiórki lub nazwę stoiska,</li>
                    <li>zatwierdza wpłatę przyciskiem <strong>„Wesprzyj”</strong> lub <strong>„Zapłać”</strong>,</li>
                    <li>zostaje przekierowany do bezpiecznej płatności mobilnej.</li>
                </ul>
            </div>
        </div>

        {{-- 3. Uczniowie decydują o przeznaczeniu środków --}}
        <div class="szk-row">
            <div class="szk-figure">
                <img src="{{ asset('img/szkoly/grafika.svg') }}" alt="">
            </div>
            <div class="szk-text">
                <h3>Uczniowie decydują o przeznaczeniu środków</h3>
                <p>
                    Jednym z najważniejszych elementów programu jest aktywne zaangażowanie uczniów
                    w podejmowanie decyzji. Po zakończeniu zbiórki uczniowie mogą wspólnie zdecydować,
                    na jaki cel zostaną przeznaczone zebrane środki. Dzięki temu uczą się
                    przedsiębiorczości, odpowiedzialności społecznej, współpracy i świadomego
                    zarządzania środkami.
                </p>
            </div>
        </div>

        {{-- 4. Strona podziękowania i prezentacja działań uczniów (tekst po lewej) --}}
        <div class="szk-row szk-row--reverse">
            <div class="szk-figure">
                <img src="{{ asset('img/szkoly/grafika.svg') }}" alt="">
            </div>
            <div class="szk-text">
                <h3>Strona podziękowania i prezentacja działań uczniów</h3>
                <p>
                    Po dokonaniu płatności wyświetlana jest strona podziękowania. Może ona zawierać:
                </p>
                <ul>
                    <li>podziękowanie od szkoły i uczniów,</li>
                    <li>informacje o celu zbiórki,</li>
                    <li>prezentację projektów realizowanych przez uczniów,</li>
                    <li>zdjęcia i materiały z działalności kół zainteresowań,</li>
                    <li>prezentację sponsorów wspierających szkołę.</li>
                </ul>
            </div>
        </div>

        {{-- 5. Dodatkowe przychody ze sponsorów --}}
        <div class="szk-row">
            <div class="szk-figure">
                <img src="{{ asset('img/szkoly/grafika.svg') }}" alt="">
            </div>
            <div class="szk-text">
                <h3>Dodatkowe przychody ze sponsorów</h3>
                <p>
                    Na stronach podziękowania mogą być prezentowani lokalni sponsorzy i partnerzy
                    wspierający szkołę. Całość przychodów generowanych przez program partnerski może
                    zasilać budżet szkolnych inicjatyw, tworząc dodatkowe źródło finansowania działań
                    edukacyjnych i społecznych.
                </p>
            </div>
        </div>

    </div>

    <div class="szk-cta">
        <a class="szk-btn szk-btn--ghost" href="{{ route('main') }}">Wróć</a>
        <a class="szk-btn szk-btn--primary" href="{{ route('home', ['produkt' => 'serduszko']) }}">Wesprzyj</a>
    </div>

</div>
@endsection
