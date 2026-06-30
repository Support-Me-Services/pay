@extends('layouts.landing')

@section('title','Fundacje — SupportME')
@section('meta-description','Support Me pomaga fundacjom i organizacjom społecznym zwiększać skuteczność pozyskiwania darowizn dzięki tagom NFC i nowoczesnym płatnościom bezgotówkowym.')

@push('head')
<style>
    /* ── Strona FUNDACJE — odwzorowanie 1:1 wg Figmy (node 126:957) ──
       Paleta i fonty z layoutu landing: granat #24324A, niebieski #1473C0,
       Libre Baskerville (display) + Inter (UI). CSS page-scoped. */
    .fnd{
        --fnd-navy:var(--lp-ink,#24324A);
        --fnd-blue-a:var(--lp-blue-a,#4E7FA7);
        --fnd-blue-b:var(--lp-blue-b,#1473C0);
        color:var(--fnd-navy);
        font-family:var(--lp-ui,'Inter',system-ui,sans-serif);
    }

    /* Pasek tytułowy — gradient z 22% krycia, wyśrodkowany napis */
    .fnd-titlebar{
        display:flex; align-items:center; justify-content:center;
        min-height:200px; padding:48px 24px; margin:0 0 8px;
        background:linear-gradient(134.5deg, rgba(78,127,167,.22) 13%, rgba(20,115,192,.22) 99%);
    }
    .fnd-titlebar h1{
        margin:0; font-family:var(--lp-display,'Libre Baskerville',Georgia,serif);
        font-weight:700; font-size:40px; line-height:1.1; text-align:center;
        color:var(--fnd-navy);
    }

    /* Lead — wyśrodkowany blok pod paskiem tytułowym */
    .fnd-lead{
        max-width:854px; margin:56px auto 12px; padding:0 22px;
        display:flex; flex-direction:column; gap:15px; text-align:center;
    }
    .fnd-lead h2{
        margin:0; font-family:var(--lp-display,'Libre Baskerville',Georgia,serif);
        font-weight:700; font-size:40px; line-height:1.18; color:var(--fnd-navy);
    }
    .fnd-lead p{ margin:0; font-size:20px; line-height:30px; color:var(--fnd-navy); }

    /* Sekcje krokowe — naprzemienny układ (grafika / tekst) */
    .fnd-steps{
        max-width:1142px; margin:0 auto; padding:24px var(--lp-gutter,97px) 8px;
    }
    .fnd-row{
        display:flex; align-items:center; justify-content:space-between;
        gap:48px; flex-wrap:wrap; margin:36px 0;
    }
    .fnd-row--reverse{ flex-direction:row-reverse; }

    .fnd-figure{
        flex:0 0 375px; width:375px; height:375px; border-radius:50%;
        display:flex; align-items:center; justify-content:center;
        background:linear-gradient(135deg,#DCE7F1 0%,#CFE0F0 100%);
    }
    .fnd-figure span{
        font-family:var(--lp-display,'Libre Baskerville',Georgia,serif);
        font-size:40px; color:#000; opacity:.5; text-align:center;
    }

    .fnd-body{ flex:1 1 560px; max-width:667px; display:flex; flex-direction:column; gap:15px; }
    .fnd-body h3{
        margin:0; font-family:var(--lp-display,'Libre Baskerville',Georgia,serif);
        font-weight:700; font-size:25px; line-height:1.32; color:var(--fnd-navy);
    }
    .fnd-body p{ margin:0; font-size:20px; line-height:33px; color:var(--fnd-navy); }
    .fnd-body p + p{ margin-top:8px; }
    .fnd-body strong{ font-weight:700; color:var(--fnd-navy); }
    .fnd-body ul{ margin:6px 0 0; padding-left:30px; list-style:disc; }
    .fnd-body li{ font-size:20px; line-height:33px; color:var(--fnd-navy); }

    /* CTA — przyciski Wróć / Wesprzyj */
    .fnd-cta{
        display:flex; flex-wrap:wrap; gap:14px; justify-content:center;
        max-width:1142px; margin:24px auto 8px; padding:30px var(--lp-gutter,97px) 16px;
    }
    .fnd-btn{
        display:inline-flex; align-items:center; justify-content:center;
        font-family:var(--lp-ui,'Inter',sans-serif); font-weight:700; font-size:1rem;
        text-decoration:none; border-radius:999px; padding:14px 32px;
        transition:transform .12s ease, box-shadow .12s ease, border-color .12s ease, color .12s ease;
    }
    .fnd-btn:hover{ transform:translateY(-1px); }
    .fnd-btn--primary{
        color:#fff; background:linear-gradient(117deg,var(--fnd-blue-a),var(--fnd-blue-b));
        box-shadow:0 10px 26px rgba(20,115,192,.30);
    }
    .fnd-btn--primary:hover{ box-shadow:0 14px 30px rgba(20,115,192,.38); }
    .fnd-btn--ghost{ color:var(--fnd-navy); background:#fff; border:1.5px solid #D7E0EA; }
    .fnd-btn--ghost:hover{ border-color:var(--fnd-blue-b); color:var(--fnd-blue-b); }

    @media(max-width:1180px){
        .fnd-steps,.fnd-cta{ padding-left:32px; padding-right:32px; }
    }
    @media(max-width:900px){
        .fnd-row,.fnd-row--reverse{ flex-direction:column; text-align:center; gap:24px; }
        .fnd-body{ align-items:center; }
        .fnd-body ul{ text-align:left; }
    }
    @media(max-width:640px){
        .fnd-titlebar{ min-height:140px; padding:36px 18px; }
        .fnd-titlebar h1{ font-size:30px; }
        .fnd-lead{ margin-top:36px; }
        .fnd-lead h2{ font-size:28px; }
        .fnd-lead p{ font-size:17px; line-height:27px; }
        .fnd-figure{ flex-basis:auto; width:240px; height:240px; }
        .fnd-figure span{ font-size:28px; }
        .fnd-body h3{ font-size:21px; }
        .fnd-body p,.fnd-body li{ font-size:17px; line-height:28px; }
        .fnd-btn{ width:100%; }
    }
</style>
@endpush

@section('content')
<section class="lp-section fnd">

    {{-- Pasek tytułowy --}}
    <div class="fnd-titlebar">
        <h1>Fundacje</h1>
    </div>

    {{-- Lead --}}
    <div class="fnd-lead">
        <h2>Support Me rozwiązuje problem braku gotówki podczas wspierania fundacji</h2>
        <p>
            W Support Me pomagamy fundacjom i organizacjom społecznym zwiększać skuteczność
            pozyskiwania darowizn dzięki nowoczesnym rozwiązaniom bezgotówkowym. Oferujemy
            specjalne tagi NFC, które mogą zostać umieszczone na puszkach kwestarskich,
            stoiskach informacyjnych, materiałach promocyjnych, identyfikatorach wolontariuszy
            oraz w miejscach prowadzenia zbiórek.
        </p>
    </div>

    <div class="fnd-steps">

        {{-- 1. Wdrożenie w fundacji — grafika z lewej --}}
        <div class="fnd-row">
            <div class="fnd-figure" aria-hidden="true"><span>grafika</span></div>
            <div class="fnd-body">
                <h3>Wdrożenie w fundacji</h3>
                <p>
                    Nasza firma dostarcza fundacji gotowe do użycia tagi NFC przypisane do
                    konkretnej kampanii lub celu zbiórki. Tagi można łatwo umieścić na puszkach,
                    roll-upach, plakatach, ulotkach lub identyfikatorach wolontariuszy.
                </p>
            </div>
        </div>

        {{-- 2. Przekazywanie darowizny przez darczyńcę — grafika z prawej --}}
        <div class="fnd-row fnd-row--reverse">
            <div class="fnd-figure" aria-hidden="true"><span>grafika</span></div>
            <div class="fnd-body">
                <h3>Przekazywanie darowizny przez darczyńcę</h3>
                <p>
                    Podczas wydarzenia charytatywnego, zbiórki publicznej lub kampanii informacyjnej
                    darczyńca może po prostu zbliżyć telefon do oznaczonego miejsca zawierającego tag NFC.
                </p>
                <p><strong>Telefon automatycznie otworzy dedykowaną stronę Support Me, na której użytkownik:</strong></p>
                <ul>
                    <li>wybiera lub wpisuje kwotę darowizny,</li>
                    <li>zapoznaje się z celem zbiórki,</li>
                    <li>potwierdza wsparcie przyciskiem <strong>„Wesprzyj”</strong>,</li>
                    <li>zostaje przekierowany do bezpiecznej płatności mobilnej.</li>
                </ul>
            </div>
        </div>

        {{-- 3. Bezpieczna płatność — grafika z lewej --}}
        <div class="fnd-row">
            <div class="fnd-figure" aria-hidden="true"><span>grafika</span></div>
            <div class="fnd-body">
                <h3>Bezpieczna płatność</h3>
                <p>
                    Po wybraniu kwoty darczyńca zostaje przekierowany do swojego banku lub operatora
                    płatności mobilnych. Płatność zatwierdzana jest za pomocą standardowych metod autoryzacji.
                </p>
                <p>
                    Po pomyślnym zakończeniu transakcji użytkownik automatycznie wraca na stronę Support Me.
                </p>
            </div>
        </div>

        {{-- 4. Strona podziękowania i dodatkowe treści — grafika z prawej --}}
        <div class="fnd-row fnd-row--reverse">
            <div class="fnd-figure" aria-hidden="true"><span>grafika</span></div>
            <div class="fnd-body">
                <h3>Strona podziękowania i dodatkowe treści</h3>
                <p>
                    Po dokonaniu darowizny wyświetlana jest dedykowana strona podziękowania.
                    Może ona zawierać:
                </p>
                <ul>
                    <li>podziękowanie od fundacji,</li>
                    <li>informację o przeznaczeniu przekazanych środków,</li>
                    <li>historie beneficjentów i efekty prowadzonych działań,</li>
                </ul>
            </div>
        </div>

        {{-- 5. Dodatkowe przychody ze sponsorów — grafika z lewej --}}
        <div class="fnd-row">
            <div class="fnd-figure" aria-hidden="true"><span>grafika</span></div>
            <div class="fnd-body">
                <h3>Dodatkowe przychody ze sponsorów</h3>
                <p>
                    Na stronie podziękowania mogą być prezentowane logotypy, materiały informacyjne
                    sponsorów. Całość przychodów generowanych dzięki wyświetleniom i współpracy
                    sponsorskiej może trafiać bezpośrednio do fundacji, tworząc dodatkowe źródło
                    finansowania jej działań statutowych.
                </p>
            </div>
        </div>

        {{-- 6. Historia darowizn i korzyści podatkowe — grafika z prawej --}}
        <div class="fnd-row fnd-row--reverse">
            <div class="fnd-figure" aria-hidden="true"><span>grafika</span></div>
            <div class="fnd-body">
                <h3>Historia darowizn i korzyści podatkowe</h3>
                <p>
                    Na koniec roku użytkownik może pobrać zestawienie darowizn, które może zostać
                    wykorzystane podczas rozliczenia podatkowego zgodnie z obowiązującymi przepisami
                    dotyczącymi odliczania darowizn na cele społeczne i charytatywne.
                </p>
                <ul>
                    <li>dostęp do historii wpłat,</li>
                    <li>możliwość pobrania rocznego zestawienia darowizn,</li>
                    <li>bieżący kontakt z fundacją i informacja o efektach wsparcia.</li>
                </ul>
            </div>
        </div>

    </div>

    {{-- CTA --}}
    <div class="fnd-cta">
        <a class="fnd-btn fnd-btn--ghost" href="{{ route('main') }}">Wróć</a>
        <a class="fnd-btn fnd-btn--primary" href="{{ route('home', ['produkt' => 'serduszko']) }}">Wesprzyj</a>
    </div>

</section>
@endsection
