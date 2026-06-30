@extends('layouts.landing')

@section('title', 'Wesprzyj — ' . config('shop.name'))
@section('meta-description', 'Wesprzyj SupportMe — wybierz produkt i wpłać dowolną kwotę.')

@push('head')
<link rel="stylesheet" href="{{ asset('css/sklep.css') }}?v={{ substr(md5_file(public_path('css/sklep.css')), 0, 10) }}">
@endpush

@section('content')
@php
    $ordered = $items->values();
    $startSlug = request('produkt');
    $start = $ordered->firstWhere('slug', $startSlug) ?? $default ?? $ordered->first();
    $startIdx = $ordered->search(fn ($i) => $i->slug === optional($start)->slug);
    $startIdx = $startIdx === false ? 0 : $startIdx;
    $serverErr = session('error') ?? ($errors->first('amount_pln') ?: null);
@endphp

@php
    // Fundacje wspierane (karuzela). Edytuj listę / wrzuć logo do public/img/fundacje/<slug>.(svg|png|webp).
    $foundations = [
        ['slug' => 'legalsight',     'name' => 'LegalSight Polska'],
        ['slug' => 'twoja-fundacja', 'name' => 'Twoja Fundacja'],
    ];
    foreach ($foundations as $k => $f) {
        $logo = null;
        foreach (['svg','png','webp','jpg'] as $e) {
            if (is_file(public_path("img/fundacje/{$f['slug']}.$e"))) { $logo = "img/fundacje/{$f['slug']}.$e"; break; }
        }
        $foundations[$k]['logo'] = $logo;
    }
@endphp

<div class="paywin" id="paywin" data-main="{{ route('main') }}" data-start="{{ $startIdx }}">
    <button class="paywin__close" type="button" data-close aria-label="Zamknij">&times;</button>

    <div class="paywin__stage" id="payStage">
        <div class="paywin__card" id="payCard">
            <div class="paywin__visual{{ optional($start)->isSvg() ? ' is-svg' : '' }}" id="payVisual">
                <img id="payImg" src="{{ asset(optional($start)->image) }}" alt="{{ optional($start)->name }}">
            </div>
            <div class="paywin__name" id="payName">{{ optional($start)->name }}</div>
        </div>
    </div>

    <label class="paywin__amount" for="payAmount">
        <input id="payAmount" class="paywin__input" type="text" inputmode="numeric" autocomplete="off"
               autofocus value="{{ optional($start)->minAmountPln() }}" aria-label="Kwota wsparcia w złotych">
        <span class="paywin__zl">zł</span>
    </label>

    <p class="paywin__err" id="payErr" role="alert" @if(! $serverErr) hidden @endif>{{ $serverErr }}</p>

    <div class="paywin__support">
        <p class="paywin__support-label">Dochód przeznaczamy na wsparcie:</p>
        <div class="fnd" id="fnd">
            <button class="fnd__nav fnd__nav--prev" type="button" aria-label="Poprzednia fundacja">&lsaquo;</button>
            <div class="fnd__viewport">
                <div class="fnd__track" id="fndTrack">
                    @foreach($foundations as $i => $f)
                        <div class="fnd__item{{ $i === 0 ? ' is-active' : '' }}" data-slug="{{ $f['slug'] }}" title="{{ $f['name'] }}">
                            <div class="fnd__logo">
                                @if($f['logo'])<img src="{{ asset($f['logo']) }}" alt="{{ $f['name'] }}">@else<span class="fnd__name">{{ $f['name'] }}</span>@endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <button class="fnd__nav fnd__nav--next" type="button" aria-label="Nastepna fundacja">&rsaquo;</button>
        </div>
    </div>

    <div class="paywin__dots" id="payDots" aria-hidden="true"></div>

    <form method="POST" id="payForm" action="{{ route('shop.buy', optional($start)->slug) }}" class="paywin__form">
        @csrf
        <input type="hidden" name="amount_pln" id="payHidden" value="{{ optional($start)->minAmountPln() }}">
        <input type="hidden" name="fundacja" id="fndInput" value="{{ $foundations[0]['slug'] }}">
        <button type="submit" class="paywin__btn">Wesprzyj<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s-6.7-4.35-9.33-8.04C.9 10.27 2.05 6.5 5.4 6.5c1.95 0 3.32 1.13 4.45 2.64C11.28 7.63 12.65 6.5 14.6 6.5c3.35 0 4.5 3.77 2.73 6.46C18.7 16.65 12 21 12 21z" fill="#FF5C9A"/><path d="M16.9 6.7a4.4 4.4 0 0 1 0 5.9" stroke="#FF5C9A" stroke-width="1.5" stroke-linecap="round"/><path d="M18.9 5a6.9 6.9 0 0 1 0 9.3" stroke="#FFA8CC" stroke-width="1.5" stroke-linecap="round"/></svg></button>
    </form>

    <p class="paywin__policy">Klikając „Wesprzyj" akceptujesz <a href="{{ asset('polityka-prywatnosci.pdf') }}" target="_blank" rel="noopener noreferrer" download>Politykę prywatności i regulamin (PDF)</a></p>

    <div class="paywin__hint">‹ przesuń, aby zmienić produkt ›</div>
</div>

<script id="pay-data" type="application/json">{!! $ordered->map(fn ($i) => [
    'slug'   => $i->slug,
    'name'   => $i->name,
    'min'    => $i->minAmountPln(),
    'image'  => asset($i->image),
    'isSvg'  => $i->isSvg(),
    'action' => route('shop.buy', $i->slug),
])->toJson() !!}</script>

<script>
(function () {
    var win = document.getElementById('paywin');
    if (!win) return;
    var data = JSON.parse(document.getElementById('pay-data').textContent || '[]');
    if (!data.length) return;

    var stage  = document.getElementById('payStage');
    var card   = document.getElementById('payCard');
    var img    = document.getElementById('payImg');
    var visual = document.getElementById('payVisual');
    var nameEl = document.getElementById('payName');
    var input  = document.getElementById('payAmount');
    var hidden = document.getElementById('payHidden');
    var err    = document.getElementById('payErr');
    var form   = document.getElementById('payForm');
    var dotsW  = document.getElementById('payDots');
    var MAIN   = win.getAttribute('data-main');
    var idx    = parseInt(win.getAttribute('data-start'), 10) || 0;

    document.body.style.overflow = 'hidden';

    // kropki produktów
    data.forEach(function () { var d = document.createElement('span'); d.className = 'paywin__dot'; dotsW.appendChild(d); });
    var dots = dotsW.querySelectorAll('.paywin__dot');
    function syncDots() { dots.forEach(function (d, i) { d.classList.toggle('is-on', i === idx); }); }

    function render(resetAmount) {
        var it = data[idx];
        img.src = it.image; img.alt = it.name;
        visual.className = 'paywin__visual' + (it.isSvg ? ' is-svg' : '');
        nameEl.textContent = it.name;
        form.action = it.action;
        if (resetAmount) { input.value = it.min; hidden.value = it.min; }
        err.hidden = true;
        syncDots();
    }

    function focusInput() {
        try {
            input.focus({ preventScroll: true });
            var v = input.value; input.value = ''; input.value = v;
            if (input.setSelectionRange) input.setSelectionRange(v.length, v.length);
        } catch (e) {}
    }

    function go(delta) {
        idx = (idx + delta + data.length) % data.length;
        render(true);
        card.classList.remove('is-in'); void card.offsetWidth; card.classList.add('is-in');
        focusInput();
    }

    // tylko cyfry, max 4 znaki (≤ 5000)
    input.addEventListener('input', function () {
        var v = input.value.replace(/\D+/g, '').replace(/^0+(?=\d)/, '');
        if (v.length > 4) v = v.slice(0, 4);
        input.value = v; hidden.value = v;
        if (v !== '' && parseInt(v, 10) >= data[idx].min) err.hidden = true;
    });

    form.addEventListener('submit', function (e) {
        var v = parseInt(input.value, 10), min = data[idx].min;
        if (isNaN(v) || v < min) {
            e.preventDefault();
            err.textContent = 'Minimalna kwota dla „' + data[idx].name + '” to ' + min + ' zł.';
            err.hidden = false; focusInput(); return;
        }
        if (v > 5000) {
            e.preventDefault();
            err.textContent = 'Maksymalna kwota to 5000 zł.';
            err.hidden = false; focusInput(); return;
        }
        hidden.value = v;
    });

    // zamknięcie → strona główna
    function close() { window.location.href = MAIN; }
    win.querySelectorAll('[data-close]').forEach(function (b) { b.addEventListener('click', close); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') close();
        else if (e.key === 'ArrowLeft') go(-1);
        else if (e.key === 'ArrowRight') go(1);
    });

    // SWIPE — próg = połowa szerokości ekranu (mocny gest, bez omsknięcia)
    var startX = null, startY = null, dragging = false, swiping = false;
    function threshold() { return Math.min(window.innerWidth, 560) * 0.5; }
    function down(x, y) { startX = x; startY = y; dragging = true; swiping = false; stage.style.transition = 'none'; }
    function move(x, y) {
        if (!dragging) return false;
        var dx = x - startX, dy = y - startY;
        if (!swiping && Math.abs(dx) > 14 && Math.abs(dx) > Math.abs(dy) * 1.3) swiping = true;
        if (swiping) {
            stage.style.transform = 'translateX(' + dx + 'px)';
            stage.style.opacity = String(Math.max(0.25, 1 - Math.abs(dx) / (threshold() * 2)));
        }
        return swiping;
    }
    function up(x) {
        if (!dragging) return; dragging = false;
        stage.style.transition = 'transform .25s ease, opacity .25s ease';
        if (swiping && Math.abs(x - startX) >= threshold()) go(x - startX < 0 ? 1 : -1);
        stage.style.transform = ''; stage.style.opacity = ''; swiping = false;
    }
    win.addEventListener('touchstart', function (e) { var t = e.touches[0]; down(t.clientX, t.clientY); }, { passive: true });
    win.addEventListener('touchmove', function (e) { var t = e.touches[0]; if (move(t.clientX, t.clientY)) e.preventDefault(); }, { passive: false });
    win.addEventListener('touchend', function (e) { var t = e.changedTouches[0]; up(t.clientX); });
    win.addEventListener('pointerdown', function (e) { if (e.pointerType === 'mouse') down(e.clientX, e.clientY); });
    window.addEventListener('pointermove', function (e) { if (e.pointerType === 'mouse') move(e.clientX, e.clientY); });
    window.addEventListener('pointerup',  function (e) { if (e.pointerType === 'mouse') up(e.clientX); });

    // start
    render(false);
    syncDots();
    focusInput();
    setTimeout(focusInput, 200);
    window.addEventListener('load', focusInput);
})();
</script>

<script>
(function () {
    var fnd = document.getElementById('fnd');
    if (!fnd) return;
    var track = document.getElementById('fndTrack');
    var vp = fnd.querySelector('.fnd__viewport');
    var input = document.getElementById('fndInput');
    var items = [].slice.call(track.children);
    var IW = 150, active = 0;

    function layout() {
        var w = vp.clientWidth;
        track.style.transform = 'translateX(' + (w / 2 - (active * IW + IW / 2)) + 'px)';
        items.forEach(function (it, i) { it.classList.toggle('is-active', i === active); });
        if (input && items[active]) input.value = items[active].getAttribute('data-slug') || '';
    }
    function setActive(i) { active = Math.max(0, Math.min(items.length - 1, i)); layout(); }

    items.forEach(function (it, i) { it.addEventListener('click', function () { setActive(i); }); });
    var prev = fnd.querySelector('.fnd__nav--prev'), next = fnd.querySelector('.fnd__nav--next');
    if (prev) prev.addEventListener('click', function () { setActive(active - 1); });
    if (next) next.addEventListener('click', function () { setActive(active + 1); });

    // swipe lokalny — nie propaguj do swipe produktow
    var sx = null;
    vp.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; e.stopPropagation(); }, { passive: true });
    vp.addEventListener('touchmove', function (e) { e.stopPropagation(); }, { passive: true });
    vp.addEventListener('touchend', function (e) { if (sx === null) return; var dx = e.changedTouches[0].clientX - sx; if (Math.abs(dx) > 40) setActive(active + (dx < 0 ? 1 : -1)); sx = null; }, { passive: true });
    vp.addEventListener('pointerdown', function (e) { e.stopPropagation(); });

    window.addEventListener('resize', layout);
    setTimeout(layout, 60);
    layout();
})();
</script>
@endsection
