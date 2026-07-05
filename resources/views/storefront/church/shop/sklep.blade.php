@extends('layouts.landing')

@section('title', 'Sklep — ' . config('shop.name'))
@section('meta-description', 'Sklep SupportMe — gadżety i tagi NFC. Wybierz produkt i kup online: BLIK, szybki przelew, karta.')

@push('head')
<link rel="stylesheet" href="{{ asset('css/sklep.css') }}?v={{ substr(md5_file(public_path('css/sklep.css')), 0, 10) }}">
<style>
    .paywin__price{ font-size:34px; font-weight:800; line-height:1; margin:6px 0 2px; }
    .paywin__price small{ font-size:18px; font-weight:700; margin-left:4px; }
    .paywin__desc{ max-width:34ch; margin:10px auto 4px; font-size:15px; line-height:1.45; color:#4a5568; text-align:center; }
    .paywin__ship{ margin:8px auto 0; font-size:13px; color:#6b7280; }
</style>
@endpush

@section('content')
@php
    $ordered = $items->values();
    $startSlug = request('produkt');
    $start = $ordered->firstWhere('slug', $startSlug) ?? $default ?? $ordered->first();
    $startIdx = $ordered->search(fn ($i) => $i->slug === optional($start)->slug);
    $startIdx = $startIdx === false ? 0 : $startIdx;
    $serverErr = session('error');
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

    <p class="paywin__desc" id="payDesc">{{ optional($start)->description }}</p>

    <div class="paywin__price" id="payPrice">{{ optional($start)->pricePln() }}<small>zł</small></div>

    <p class="paywin__err" id="payErr" role="alert" @if(! $serverErr) hidden @endif>{{ $serverErr }}</p>

    <div class="paywin__dots" id="payDots" aria-hidden="true"></div>

    <form method="POST" id="payForm" action="{{ route('shop.buy', optional($start)->slug) }}" class="paywin__form">
        @csrf
        <button type="submit" class="paywin__btn">Kupuję i płacę<svg width="21" height="21" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="margin-left:8px"><path d="M6 6h15l-1.5 9h-12L5 3H2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="20" r="1.4" fill="currentColor"/><circle cx="18" cy="20" r="1.4" fill="currentColor"/></svg></button>
    </form>

    <p class="paywin__ship">Wysyłka kurierem: 1–3 dni robocze · zwrot do 14 dni</p>

    <p class="paywin__policy">Klikając „Kupuję i płacę" akceptujesz <a href="{{ asset('polityka-prywatnosci.pdf') }}" target="_blank" rel="noopener noreferrer">Politykę prywatności (PDF)</a> i <a href="{{ route('regulamin') }}" target="_blank" rel="noopener noreferrer">Regulamin</a></p>

    <div class="paywin__hint">‹ przesuń, aby zmienić produkt ›</div>
</div>

<script id="pay-data" type="application/json">{!! $ordered->map(fn ($i) => [
    'slug'   => $i->slug,
    'name'   => $i->name,
    'desc'   => $i->description,
    'price'  => $i->pricePln(),
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
    var descEl = document.getElementById('payDesc');
    var priceEl= document.getElementById('payPrice');
    var form   = document.getElementById('payForm');
    var dotsW  = document.getElementById('payDots');
    var MAIN   = win.getAttribute('data-main');
    var idx    = parseInt(win.getAttribute('data-start'), 10) || 0;

    document.body.style.overflow = 'hidden';

    // kropki produktów
    data.forEach(function () { var d = document.createElement('span'); d.className = 'paywin__dot'; dotsW.appendChild(d); });
    var dots = dotsW.querySelectorAll('.paywin__dot');
    function syncDots() { dots.forEach(function (d, i) { d.classList.toggle('is-on', i === idx); }); }

    function render() {
        var it = data[idx];
        img.src = it.image; img.alt = it.name;
        visual.className = 'paywin__visual' + (it.isSvg ? ' is-svg' : '');
        nameEl.textContent = it.name;
        descEl.textContent = it.desc || '';
        priceEl.innerHTML = it.price + '<small>zł</small>';
        form.action = it.action;
        syncDots();
    }

    function go(delta) {
        idx = (idx + delta + data.length) % data.length;
        render();
        card.classList.remove('is-in'); void card.offsetWidth; card.classList.add('is-in');
    }

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
    render();
    syncDots();
})();
</script>
@endsection
