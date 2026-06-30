@extends('layouts.landing')

@section('title','Parafie — SupportME')
@section('meta-description','Support Me pomaga parafiom zwiększyć dostępność darowizn bezgotówkowych dzięki tagom NFC na tacach. Prosty, bezpieczny sposób na wsparcie kościoła telefonem.')

@push('head')
<style>
    /* ====== PARAFIE — page-scoped, spójne z systemem landingu (.lp-*) ======
       Paleta: granat #24324A, niebieski #1473C0; fonty: Libre Baskerville + Inter.
       Kółka grafik wypełnione #D8E3EC (--lp-wave) — 1:1 z ramką Figmy (PARAFIE). */

    .pf-hero{
        position:relative; z-index:2;
        background:linear-gradient(112deg, rgba(78,127,167,.22) 13.6%, rgba(20,115,192,.22) 99.4%);
        display:flex; align-items:center; justify-content:center;
        text-align:center; padding:75px var(--lp-gutter);
        min-height:200px;
    }
    .pf-hero h1{
        font-family:var(--lp-display); font-weight:700; font-size:40px; line-height:normal;
        margin:0; color:var(--lp-ink);
    }

    .pf-intro{
        max-width:854px; margin:0 auto; text-align:center;
        padding:68px var(--lp-gutter) 0;
    }
    .pf-intro h2{
        font-family:var(--lp-display); font-weight:700; font-size:40px; line-height:normal;
        margin:0 0 15px; color:var(--lp-ink);
    }
    .pf-intro p{ margin:0; font-size:20px; line-height:30px; color:var(--lp-ink); }

    .pf-steps{
        max-width:1142px; margin:64px auto 0; padding:0 var(--lp-gutter);
        display:flex; flex-direction:column; gap:64px;
    }
    .pf-step{
        display:flex; align-items:center; justify-content:space-between; gap:40px;
    }
    .pf-step--rev{ flex-direction:row-reverse; }
    .pf-step__art{ flex:0 0 auto; }
    .pf-circle{
        width:375px; height:375px; border-radius:50%;
        background:var(--lp-wave, #D8E3EC) no-repeat center / 30%;
        display:block;
    }
    .pf-step--1 .pf-circle{ background-image:url('{{ asset('img/parafie/p1.svg') }}'); }
    .pf-step--2 .pf-circle{ background-image:url('{{ asset('img/parafie/p2.svg') }}'); }
    .pf-step--3 .pf-circle{ background-image:url('{{ asset('img/parafie/p3.svg') }}'); }
    .pf-step--4 .pf-circle{ background-image:url('{{ asset('img/parafie/p4.svg') }}'); }

    .pf-step__body{ width:667px; max-width:667px; flex:0 0 auto; }
    .pf-step__body h3{
        font-family:var(--lp-display); font-weight:700; font-size:25px; line-height:33px;
        color:var(--lp-ink); margin:0 0 15px;
    }
    .pf-step__body p{ margin:0; font-size:20px; line-height:33px; color:var(--lp-ink); }
    .pf-step__body b{ font-weight:700; }
    .pf-step__body .pf-lead-list{ margin:18px 0 0; font-weight:700; }
    .pf-step__body ul{ margin:6px 0 0; padding-left:30px; list-style:disc; }
    .pf-step__body ul li{ font-size:20px; line-height:33px; color:var(--lp-ink); }

    /* zamknięcie + CTA */
    .pf-cta{
        max-width:854px; margin:72px auto 0; padding:0 var(--lp-gutter) 8px;
        text-align:center;
    }
    .pf-cta p{ margin:0 0 24px; font-size:20px; line-height:33px; color:var(--lp-ink); }
    .pf-cta__btn{
        display:inline-block; border:0; cursor:pointer;
        font-family:var(--lp-ui); font-size:16px; font-weight:700; color:#fff;
        background:linear-gradient(117deg,var(--lp-blue-a),var(--lp-blue-b));
        padding:14px 38px; border-radius:999px; line-height:1; white-space:nowrap;
        text-decoration:none; transition:transform .15s ease, box-shadow .15s ease;
    }
    .pf-cta__btn:hover{ transform:translateY(-2px); box-shadow:0 12px 28px rgba(20,115,192,.28); }

    .pf-back{
        display:block; text-align:center; margin:28px auto 64px;
        font-size:14px; color:var(--lp-ink); opacity:.7;
    }
    .pf-back:hover{ opacity:1; }

    /* ---------- RESPONSIVE (zgodnie z konwencją landingu) ---------- */
    @media (max-width:1200px){
        :root{ --lp-gutter:48px; }
    }
    @media (max-width:980px){
        .pf-step, .pf-step--rev{ flex-direction:column; text-align:left; }
        .pf-step__body{ width:100%; max-width:560px; }
        .pf-step__art{ align-self:center; }
        .pf-circle{ width:280px; height:280px; }
    }
    @media (max-width:560px){
        :root{ --lp-gutter:22px; }
        .pf-hero h1{ font-size:34px; }
        .pf-intro h2{ font-size:32px; }
        .pf-steps{ gap:56px; margin-top:48px; }
        .pf-step__body{ text-align:center; max-width:354px; margin:0 auto; }
        .pf-step__body ul{ display:inline-block; text-align:left; }
        .pf-circle{ width:min(280px,72vw); height:min(280px,72vw); }
    }
</style>
@endpush

@section('content')
<section class="pf-hero">
    <h1>Parafie</h1>
</section>

<div class="pf-intro">
    <h2>Support Me rozwiązuje problem braku gotówki podczas składania ofiary</h2>
    <p>W Support Me pomagamy parafiom w prosty i bezpieczny sposób zwiększyć dostępność darowizn bezgotówkowych. Oferujemy specjalne tagi NFC, które mogą zostać umieszczone na tacach używanych podczas zbiórek w kościele.</p>
</div>

<div class="pf-steps">

    {{-- KROK 1 — grafika po lewej --}}
    <section class="pf-step pf-step--rev pf-step--1">
        <div class="pf-step__art"><span class="pf-circle" aria-hidden="true"></span></div>
        <div class="pf-step__body">
            <h3>Wdrożenie w parafii</h3>
            <p>Nasza firma dostarcza parafii gotowe do użycia tagi NFC. Proboszcz otrzymuje tagi, które można łatwo przykleić do tac wykorzystywanych podczas zbierania ofiar od wiernych.</p>
        </div>
    </section>

    {{-- KROK 2 — grafika po prawej --}}
    <section class="pf-step pf-step--2">
        <div class="pf-step__body">
            <h3>Przekazywanie darowizny przez wiernego</h3>
            <p>Podczas Mszy Świętej ministrant przekazuje tacę wśród wiernych. Osoba chcąca wesprzeć parafię może po prostu zbliżyć swój telefon do oznaczonego miejsca na tacy. Telefon automatycznie otworzy dedykowaną stronę Support Me, na której wierny:</p>
            <ul>
                <li>wybiera lub wpisuje kwotę darowizny,</li>
                <li>potwierdza ją przyciskiem <b>„Wesprzyj”</b>,</li>
                <li>zostaje przekierowany do bezpiecznej płatności mobilnej.</li>
            </ul>
        </div>
        <div class="pf-step__art"><span class="pf-circle" aria-hidden="true"></span></div>
    </section>

    {{-- KROK 3 — grafika po lewej --}}
    <section class="pf-step pf-step--rev pf-step--3">
        <div class="pf-step__art"><span class="pf-circle" aria-hidden="true"></span></div>
        <div class="pf-step__body">
            <h3>Strona podziękowania i dodatkowe treści</h3>
            <p>Po dokonaniu darowizny wyświetlana jest specjalna strona podziękowania. Może ona zawierać:</p>
            <ul>
                <li>podziękowanie od parafii,</li>
                <li>fragment Słowa Bożego wraz z krótkim komentarzem lub refleksją,</li>
                <li>aktualne informacje i ogłoszenia parafialne,</li>
                <li>prezentację sponsorów wspierających działalność parafii.</li>
            </ul>
        </div>
    </section>

    {{-- KROK 4 — grafika po prawej --}}
    <section class="pf-step pf-step--4">
        <div class="pf-step__body">
            <h3>Dodatkowe przychody ze sponsorów</h3>
            <p>Na stronie podziękowania mogą być prezentowane logotypy oraz materiały partnerów i sponsorów parafii. Całość przychodów generowanych dzięki emisji tych materiałów trafia bezpośrednio do parafii, tworząc dodatkowe źródło finansowania jej działalności.</p>
        </div>
        <div class="pf-step__art"><span class="pf-circle" aria-hidden="true"></span></div>
    </section>

</div>

<div class="pf-cta">
    <p>Chcesz wprowadzić darowizny bezgotówkowe w swojej parafii?</p>
    <a class="pf-cta__btn" href="{{ route('home', ['produkt' => 'serduszko']) }}">Wesprzyj</a>
</div>

<a class="pf-back" href="{{ route('main') }}">&larr; Wróć na stronę główną</a>
@endsection
