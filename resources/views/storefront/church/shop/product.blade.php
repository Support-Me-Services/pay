@extends('layouts.landing')

@section('title', 'Wesprzyj: ' . $product->name . ' — ' . config('shop.name'))
@section('meta-description', 'Złóż cyfrową tacę na rzecz parafii ' . $product->name . '. Szybka wpłata BLIK, bez gotówki.')

@push('head')
<link rel="stylesheet" href="{{ asset('css/subpages.css') }}?v={{ filemtime(public_path('css/subpages.css')) }}">
@endpush

@php
    // Sugerowane kwoty (zł). Domyślnie zaznaczona = cena bazowa parafii (zwykle 20 zł).
    $presets = [10, 20, 50, 100, 200, 500];
    $default = (int) round($product->price / 100);
    if (! in_array($default, $presets, true)) {
        $default = 20;
    }
@endphp

@section('content')
    {{-- SUB-HERO (pasek wg Figmy) --}}
    <section class="sp-subhero">
        <div class="sp-subhero__inner">
            <a href="{{ route('home') }}" class="sp-back">← wszystkie parafie</a>
            <h1>{{ $product->name }}</h1>
            @if($product->purpose)
                <p class="sp-lede">✦ {{ $product->purpose }}</p>
            @endif
        </div>
    </section>

    <div class="sp-wrap">
        <div class="sp-container sp-give">
            <div class="sp-give__grid">
                {{-- Grafika parafii (zdjęcie w kółku lub czyste koło „grafika") --}}
                <div class="sp-give__art">
                    @if($product->main_image)
                        <div class="sp-circle sp-circle--lg"><img src="{{ asset('storage/' . $product->main_image) }}" alt="{{ $product->name }}"></div>
                    @else
                        <div class="sp-circle sp-circle--lg">grafika</div>
                    @endif
                </div>

                <div class="sp-give__main">
                    @if($product->city)
                        <span class="sp-give__city">{{ $product->city }}</span>
                    @endif
                    <h2 class="sp-give__title">{{ $product->name }}</h2>
                    @if($product->purpose)
                        <span class="sp-give__purpose">✦ {{ $product->purpose }}</span>
                    @endif

                    @if(session('error'))
                        <div class="sp-alert sp-alert--err">{{ session('error') }}</div>
                    @endif

                    @if($product->description_html)
                        <div class="sp-give__lead">{!! $product->description_html !!}</div>
                    @endif

                    <form method="POST" action="{{ route('product.buy', $product->slug) }}" id="giveForm">
                        @csrf
                        <input type="hidden" name="amount_pln" id="amountField" value="{{ $default }}">

                        <div class="sp-give-card">
                            <div class="sp-give-card__label">Wybierz kwotę wsparcia</div>
                            <div class="sp-give-card__sub">Możesz wpłacić dowolną kwotę — sugerujemy {{ $default }} zł.</div>

                            <div class="sp-amounts" role="group" aria-label="Kwota wsparcia">
                                @foreach($presets as $value)
                                    <button type="button" class="sp-amount {{ $value === $default ? 'is-active' : '' }}"
                                            data-amount="{{ $value }}" aria-pressed="{{ $value === $default ? 'true' : 'false' }}">
                                        {{ $value }}<small>zł</small>
                                    </button>
                                @endforeach
                            </div>

                            <div class="sp-custom">
                                <label for="customAmount">Inna kwota</label>
                                <div class="sp-custom__input">
                                    <input type="number" id="customAmount" inputmode="numeric" min="2" max="5000" step="1"
                                           placeholder="np. 30" aria-label="Inna kwota w złotych">
                                    <span class="sp-suffix">zł</span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="sp-give-sticky">
            <div class="sp-give-sticky__inner">
                <button type="submit" form="giveForm" class="sp-btn sp-btn--block" id="giveBtn">
                    Wesprzyj — <span id="ctaAmount">{{ $default }}</span> zł
                </button>
                <div class="sp-secure">🔒 Bezpieczna płatność BLIK · obsługuje PayU</div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const opts   = document.querySelectorAll('.sp-amount');
    const custom = document.getElementById('customAmount');
    const field  = document.getElementById('amountField');
    const cta     = document.getElementById('ctaAmount');
    const btn     = document.getElementById('giveBtn');
    const form    = document.getElementById('giveForm');

    function setAmount(value) {
        field.value = value;
        cta.textContent = value;
    }

    opts.forEach(function (opt) {
        opt.addEventListener('click', function () {
            opts.forEach(o => { o.classList.remove('is-active'); o.setAttribute('aria-pressed', 'false'); });
            opt.classList.add('is-active');
            opt.setAttribute('aria-pressed', 'true');
            custom.value = '';
            setAmount(opt.dataset.amount);
        });
    });

    custom.addEventListener('input', function () {
        const v = parseInt(custom.value, 10);
        opts.forEach(o => { o.classList.remove('is-active'); o.setAttribute('aria-pressed', 'false'); });
        if (!isNaN(v) && v > 0) {
            setAmount(v);
            // jeśli pokrywa się z presetem — podświetl go
            opts.forEach(o => { if (parseInt(o.dataset.amount, 10) === v) { o.classList.add('is-active'); o.setAttribute('aria-pressed', 'true'); } });
        } else {
            cta.textContent = '—';
        }
    });

    form.addEventListener('submit', function (e) {
        const v = parseInt(field.value, 10);
        if (isNaN(v) || v < 2) {
            e.preventDefault();
            custom.focus();
            return;
        }
        btn.disabled = true;
        btn.textContent = 'Przenosimy do płatności…';
    });
})();
</script>
@endpush
