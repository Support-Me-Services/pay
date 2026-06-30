@extends('layouts.landing')

@push('head')
<style>
    .ty-overlay{ position:fixed; inset:0; z-index:1200; display:flex; align-items:center; justify-content:center;
        padding:20px; background:rgba(20,30,48,.55); -webkit-backdrop-filter:blur(3px); backdrop-filter:blur(3px); }
    .ty-modal{ position:relative; width:100%; max-width:540px; background:#fff; border-radius:24px;
        box-shadow:0 24px 70px rgba(20,30,48,.35); padding:38px 34px 24px;
        font-family:var(--lp-ui,'Inter',system-ui,sans-serif); color:var(--lp-ink,#24324A); animation:tyPop .25s ease; }
    @keyframes tyPop{ from{ opacity:0; transform:translateY(12px) scale(.98); } to{ opacity:1; transform:none; } }
    .ty-close{ position:absolute; top:14px; right:16px; width:38px; height:38px; border:0; background:#EEF3F8;
        color:#24324A; border-radius:50%; font-size:1.5rem; line-height:1; cursor:pointer; }
    .ty-close:hover{ background:#dfe8f1; }
    .ty-heart{ display:block; margin-bottom:6px; }
    .ty-title{ font-family:var(--lp-display,'Libre Baskerville',Georgia,serif); font-size:1.65rem;
        margin:4px 0 16px; color:var(--lp-ink,#24324A); line-height:1.2; }
    .ty-body p{ margin:0 0 12px; font-size:1.02rem; line-height:1.55; color:#3a4961; }
    .ty-sign{ font-weight:700; color:var(--lp-ink,#24324A); margin-top:4px; }
    .ty-patrons{ display:flex; align-items:center; justify-content:flex-end; flex-wrap:wrap; gap:10px;
        margin-top:20px; padding-top:16px; border-top:1px solid #E6EAF0; }
    .ty-patrons__label{ font-size:.74rem; color:#90a0b3; font-weight:700; text-transform:uppercase; letter-spacing:.08em; }
    .ty-patron{ display:inline-flex; text-decoration:none; align-items:center; gap:7px; background:#F4F7FA; border:1px solid #E6EAF0;
        border-radius:999px; padding:6px 13px; font-weight:700; font-size:.86rem; color:#24324A; }
    .ty-patron img{ height:22px; width:auto; display:block; }
    @media(max-width:520px){ .ty-modal{ padding:30px 22px 20px; } .ty-title{ font-size:1.4rem; } }
</style>
@endpush

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
                    <p><br>Może tam na Ciebie czekać:<br>• wiadomość od organizacji<br>• podziękowanie od zespołu lub opiekuna projektu<br>• zdjęcie z realizowanej inicjatywy<br>• informacja o wykorzystaniu środków<br>• inspirujący cytat lub krótka historia związana z projektem</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Zamknięcie (w Figmie obecne w wersji mobilnej) --}}
    <section class="lp-closing">
        <p>Tworzymy technologię, która wspiera ludzi w pomaganiu i zamienianiu dobrych intencji w realną pomoc.<br><br><b>Dobro przekazane dalej ma największą wartość.</b></p>
    </section>

@if(request('dzieki'))
<div class="ty-overlay" id="tyOverlay" role="dialog" aria-modal="true" aria-labelledby="tyTitle">
    <div class="ty-modal">
        <button type="button" class="ty-close" data-ty-close aria-label="Zamknij">&times;</button>
        <svg class="ty-heart" width="44" height="44" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 21s-7.4-4.55-9.9-9.2C.7 8.7 2.2 5 5.6 5c2 0 3.3 1.05 4.4 2.55C11.1 6.05 12.4 5 14.4 5c3.4 0 4.9 3.7 3.5 6.8C19.4 16.45 12 21 12 21z" fill="#1473C0"/>
        </svg>
        <h2 class="ty-title" id="tyTitle">Dziękujemy za Twoje wsparcie!</h2>
        <div class="ty-body">
            <p>Jesteśmy wdzięczni, że wspierasz fundację <strong>LegalSight Polska</strong> — która wspiera ubogich seniorów w pomocy prawnej.</p>
            <p>Dzięki Tobie Mecenasi LegalSight dodatkowo przekażą datek na rzecz fundacji.</p>
            <p>Pozostajemy w kontakcie.<br>Będziemy nadal Cię wspierać w pomaganiu.</p>
            <p class="ty-sign">Zespół Support Me</p>
        </div>
        <div class="ty-patrons">
            <span class="ty-patrons__label">Mecenasi</span>
            @php
                $lr = null;
                foreach (['svg','png','webp','jpg'] as $ext) {
                    if (file_exists(public_path("img/mecenasi/lokalnyrolnik.$ext"))) { $lr = "img/mecenasi/lokalnyrolnik.$ext"; break; }
                }
            @endphp
            <a class="ty-patron" href="{{ route('mecenasi.lokalnyrolnik') }}" title="LokalnyRolnik">
                @if($lr)<img src="{{ asset($lr) }}" alt="LokalnyRolnik">@else LokalnyRolnik @endif
            </a>
        </div>
    </div>
</div>
<script>
(function(){
    var ov = document.getElementById('tyOverlay');
    if (!ov) return;
    document.body.style.overflow = 'hidden';
    function closeTy(){
        document.body.style.overflow = '';
        if (ov.parentNode) ov.parentNode.removeChild(ov);
        try { history.replaceState({}, '', @json(route('main'))); } catch(e){}
    }
    ov.querySelectorAll('[data-ty-close]').forEach(function(b){ b.addEventListener('click', closeTy); });
    ov.addEventListener('click', function(e){ if (e.target === ov) closeTy(); });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeTy(); });
})();
</script>
@endif
@endsection
