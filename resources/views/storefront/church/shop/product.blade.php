@extends('layouts.public')

@section('title', 'Wesprzyj: ' . $product->name . ' — ' . config('shop.name'))
@section('meta-description', 'Złóż cyfrową tacę na rzecz parafii ' . $product->name . '. Szybka wpłata BLIK, bez gotówki.')

@php
    // Sugerowane kwoty (zł). Domyślnie zaznaczona = cena bazowa parafii (zwykle 20 zł).
    $presets = [10, 20, 50, 100, 200, 500];
    $default = (int) round($product->price / 100);
    if (! in_array($default, $presets, true)) {
        $default = 20;
    }
@endphp

@section('content')
    <section class="don-hero">
        <div class="don-hero-art">
            @if($product->main_image)
                <img src="{{ asset('storage/' . $product->main_image) }}" alt="{{ $product->name }}">
            @endif
        </div>
        <div class="don-hero-inner">
            <a href="{{ route('home') }}" class="don-back">← wszystkie parafie</a>
            @if($product->city)
                <div class="don-city">{{ $product->city }}</div>
            @endif
            <h1>{{ $product->name }}</h1>
            @if($product->purpose)
                <span class="don-purpose">✦ {{ $product->purpose }}</span>
            @endif
        </div>
    </section>

    <div class="don-body">
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        @if($product->description_html)
            <div class="don-lead">{!! $product->description_html !!}</div>
        @endif

        <form method="POST" action="{{ route('product.buy', $product->slug) }}" id="giveForm">
            @csrf
            <input type="hidden" name="amount_pln" id="amountField" value="{{ $default }}">

            <div class="give-card">
                <div class="give-label">Wybierz kwotę wsparcia</div>
                <div class="give-sub">Możesz wpłacić dowolną kwotę — sugerujemy {{ $default }} zł.</div>

                <div class="amount-grid" role="group" aria-label="Kwota wsparcia">
                    @foreach($presets as $value)
                        <button type="button" class="amount-opt {{ $value === $default ? 'is-active' : '' }}"
                                data-amount="{{ $value }}" aria-pressed="{{ $value === $default ? 'true' : 'false' }}">
                            {{ $value }}<small>zł</small>
                        </button>
                    @endforeach
                </div>

                <div class="custom-amount">
                    <label for="customAmount">Inna kwota</label>
                    <div class="amount-input">
                        <input type="number" id="customAmount" inputmode="numeric" min="2" max="5000" step="1"
                               placeholder="np. 30" aria-label="Inna kwota w złotych">
                        <span class="suffix">zł</span>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="give-sticky">
        <div class="inner">
            <button type="submit" form="giveForm" class="btn btn-gold btn-block" id="giveBtn">
                Wesprzyj — <span id="ctaAmount">{{ $default }}</span> zł
            </button>
            <div class="secure">🔒 Bezpieczna płatność BLIK · obsługuje PayU</div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const opts   = document.querySelectorAll('.amount-opt');
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
