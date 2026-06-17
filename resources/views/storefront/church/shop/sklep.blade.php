@extends('layouts.landing')

@section('title', 'Sklep — ' . config('shop.name'))
@section('meta-description', 'Sklep SupportMe — wybierz produkt oznaczony tagiem NFC i wesprzyj wybraną kwotą.')

@push('head')
<link rel="stylesheet" href="{{ asset('css/sklep.css') }}?v={{ substr(md5_file(public_path('css/sklep.css')), 0, 10) }}">
@endpush

@section('content')
    <section class="shop-hero">
        <h1>Sklep</h1>
    </section>

    <div class="shop-wrap">
        @if(session('error'))
            <div class="shop-flash shop-flash--err">{{ session('error') }}</div>
        @endif
        @error('amount_pln')
            <div class="shop-flash shop-flash--err">{{ $message }}</div>
        @enderror

        <p class="shop-intro">Wybierz produkt oznaczony tagiem NFC i wesprzyj wybraną kwotą. Każdy produkt ma minimalną kwotę — możesz ją dowolnie zwiększyć. Domyślnie wspierasz nas „Serduszkiem” już od 1 zł.</p>

        <div class="shop-grid">
            @foreach($items as $it)
                <button type="button" class="shop-card" data-shop-open="{{ $it->slug }}" aria-label="Kup: {{ $it->name }}">
                    <span class="shop-card__art{{ $it->isSvg() ? ' shop-card__art--svg' : '' }}">
                        <img src="{{ asset($it->image) }}" alt="{{ $it->name }}">
                    </span>
                    <span class="shop-card__body">
                        <span class="shop-card__name">{{ $it->name }}</span>
                        <span class="shop-card__price">od {{ $it->minAmountPln() }} zł</span>
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Modal KUP — wg Figmy (65:1616) + notatki: edytowalna kwota, serduszko/produkt, KUP --}}
    <div class="wesprzyj" id="wesprzyj" hidden>
        <div class="wesprzyj__scrim" data-wesprzyj-close></div>
        <div class="wesprzyj__dialog" role="dialog" aria-modal="true" aria-label="Kup produkt">
            <button type="button" class="wesprzyj__close" data-wesprzyj-close aria-label="Zamknij">&times;</button>

            <label class="wesprzyj__amount" for="wAmount">
                <input type="number" id="wAmount" class="wesprzyj__input" inputmode="numeric" min="1" step="1" value="1" aria-label="Kwota w złotych">
                <span class="wesprzyj__zl">zł</span>
            </label>
            <p class="wesprzyj__err" id="wErr" role="alert" hidden></p>

            <span class="wesprzyj__visual" id="wVisual"></span>
            <span class="wesprzyj__name" id="wName"></span>

            <form method="POST" id="wForm" class="wesprzyj__form">
                @csrf
                <input type="hidden" name="amount_pln" id="wHidden" value="1">
                <button type="submit" class="wesprzyj__btn">KUP</button>
            </form>
        </div>
    </div>

    @php
        $shopItemsJs = $items->mapWithKeys(fn ($it) => [$it->slug => [
            'name' => $it->name,
            'min' => $it->minAmountPln(),
            'image' => asset($it->image),
            'isSvg' => $it->isSvg(),
            'action' => route('shop.buy', $it->slug),
        ]]);
    @endphp
    <script id="shop-items-data" type="application/json">{!! $shopItemsJs->toJson() !!}</script>
    <script>
    (function () {
        var modal = document.getElementById('wesprzyj');
        if (!modal) return;
        var data = JSON.parse(document.getElementById('shop-items-data').textContent || '{}');
        var input = document.getElementById('wAmount');
        var hidden = document.getElementById('wHidden');
        var err = document.getElementById('wErr');
        var visual = document.getElementById('wVisual');
        var nameEl = document.getElementById('wName');
        var form = document.getElementById('wForm');
        var current = null;

        function open(slug) {
            var it = data[slug];
            if (!it) return;
            current = it;
            input.min = it.min;
            input.value = it.min;
            hidden.value = it.min;
            err.hidden = true;
            var img = document.createElement('img');
            img.src = it.image; img.alt = it.name;
            visual.innerHTML = '';
            visual.appendChild(img);
            visual.className = 'wesprzyj__visual' + (it.isSvg ? ' is-svg' : '');
            nameEl.textContent = it.name;
            form.action = it.action;
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
            try { input.focus(); input.select(); } catch (e) {}
        }
        function close() { modal.hidden = true; document.body.style.overflow = ''; }

        function syncHidden() {
            var v = parseInt(input.value, 10);
            hidden.value = isNaN(v) ? '' : v;
            if (!isNaN(v) && current && v >= current.min) err.hidden = true;
        }
        function valid() {
            var v = parseInt(input.value, 10);
            if (!current || isNaN(v) || v < current.min) {
                err.textContent = 'Minimalna kwota dla „' + (current ? current.name : '') + '” to ' + (current ? current.min : 1) + ' zł.';
                err.hidden = false;
                input.focus();
                return false;
            }
            hidden.value = v;
            return true;
        }

        input.addEventListener('input', syncHidden);
        form.addEventListener('submit', function (e) { if (!valid()) e.preventDefault(); });
        document.querySelectorAll('[data-shop-open]').forEach(function (b) {
            b.addEventListener('click', function () { open(b.getAttribute('data-shop-open')); });
        });
        modal.querySelectorAll('[data-wesprzyj-close]').forEach(function (b) { b.addEventListener('click', close); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) close(); });

        // Auto-modal: ?produkt={slug} zawsze; inaczej domyślny („Serduszko") raz na sesję.
        var params = new URLSearchParams(location.search);
        var wanted = params.get('produkt');
        if (wanted && data[wanted]) {
            open(wanted);
        } else {
            var def = @json($default?->slug);
            if (def && data[def] && !sessionStorage.getItem('shopWelcome')) {
                sessionStorage.setItem('shopWelcome', '1');
                open(def);
            }
        }
    })();
    </script>
@endsection
