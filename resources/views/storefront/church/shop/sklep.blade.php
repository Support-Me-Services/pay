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

    <div class="paywin__dots" id="payDots" aria-hidden="true"></div>

    <form method="POST" id="payForm" action="{{ route('shop.buy', optional($start)->slug) }}" class="paywin__form">
        @csrf
        <input type="hidden" name="amount_pln" id="payHidden" value="{{ optional($start)->minAmountPln() }}">
        <button type="submit" class="paywin__btn">KUP</button>
    </form>

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
@endsection
