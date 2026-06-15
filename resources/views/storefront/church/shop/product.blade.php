@extends('layouts.landing')

@section('title', 'Wesprzyj: ' . $product->name . ' — ' . config('shop.name'))
@section('meta-description', 'Złóż cyfrową tacę na rzecz parafii ' . $product->name . '. Szybka wpłata BLIK, bez gotówki.')

@push('head')
<link rel="stylesheet" href="{{ asset('css/subpages.css') }}?v={{ filemtime(public_path('css/subpages.css')) }}">
@endpush

@php
    // Sugerowane kwoty (zł). Domyślnie zaznaczona = cena bazowa parafii (zwykle 20 zł).
    $presets = [10, 20, 50, 100, 200];
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

                                {{-- „Inna kwota" — ostatni kafelek; po kliknięciu zamienia się w pole input --}}
                                <div class="sp-amount sp-amount--custom" id="customTile" data-custom="1">
                                    <button type="button" class="sp-amount__label" id="customTrigger" aria-pressed="false">
                                        Inna kwota
                                    </button>
                                    <span class="sp-amount__input" hidden>
                                        <input type="number" id="customAmount" inputmode="numeric" min="2" max="5000" step="1"
                                               placeholder="np. 30" aria-label="Inna kwota w złotych">
                                        <span class="sp-amount__suffix">zł</span>
                                    </span>
                                </div>
                            </div>

                            {{-- CTA „Wesprzyj" — pod presetami i polem „Inna kwota", pełna szerokość --}}
                            <div class="sp-give-cta">
                                <button type="submit" form="giveForm" class="sp-btn sp-btn--block sp-btn--cta" id="giveBtn">
                                    Wesprzyj — <span id="ctaAmount">{{ $default }}</span> zł
                                </button>
                                <div class="sp-secure">🔒 Bezpieczna płatność BLIK · obsługuje PayU</div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const presetOpts = document.querySelectorAll('.sp-amount[data-amount]');
    const customTile = document.getElementById('customTile');
    const trigger    = document.getElementById('customTrigger');
    const label      = trigger;                                  // część „etykietowa" kafelka
    const inputWrap  = customTile.querySelector('.sp-amount__input');
    const custom     = document.getElementById('customAmount');
    const field      = document.getElementById('amountField');
    const cta        = document.getElementById('ctaAmount');
    const btn        = document.getElementById('giveBtn');
    const form       = document.getElementById('giveForm');

    function setAmount(value) {
        field.value = value;
        cta.textContent = value;
    }

    function clearPresets() {
        presetOpts.forEach(o => { o.classList.remove('is-active'); o.setAttribute('aria-pressed', 'false'); });
    }

    // tryb „etykieta" — kafelek pokazuje napis „Inna kwota"
    function showLabel() {
        customTile.classList.remove('is-active', 'is-editing');
        trigger.setAttribute('aria-pressed', 'false');
        label.hidden = false;
        inputWrap.hidden = true;
        custom.value = '';
    }

    // tryb „input" — kafelek staje się polem do wpisania kwoty
    function showInput() {
        clearPresets();
        customTile.classList.add('is-active', 'is-editing');
        trigger.setAttribute('aria-pressed', 'true');
        label.hidden = true;
        inputWrap.hidden = false;
        custom.focus();
        cta.textContent = '—';
        field.value = '';
    }

    // Klik presetu: zaznacz preset i wróć z kafelka „Inna kwota" do trybu etykiety
    presetOpts.forEach(function (opt) {
        opt.addEventListener('click', function () {
            showLabel();
            opt.classList.add('is-active');
            opt.setAttribute('aria-pressed', 'true');
            setAmount(opt.dataset.amount);
        });
    });

    // Klik „Inna kwota": zamień kafelek na input
    trigger.addEventListener('click', showInput);

    // Wpisywanie własnej kwoty w kafelku-inpucie
    custom.addEventListener('input', function () {
        const v = parseInt(custom.value, 10);
        if (!isNaN(v) && v > 0) {
            // jeśli pokrywa się z presetem — podświetl preset i wpisz wartość
            clearPresets();
            customTile.classList.add('is-active');
            presetOpts.forEach(o => {
                if (parseInt(o.dataset.amount, 10) === v) { o.classList.add('is-active'); o.setAttribute('aria-pressed', 'true'); }
            });
            setAmount(v);
        } else {
            field.value = '';
            cta.textContent = '—';
        }
    });

    form.addEventListener('submit', function (e) {
        const v = parseInt(field.value, 10);
        if (isNaN(v) || v < 2) {
            e.preventDefault();
            if (inputWrap.hidden) { showInput(); } else { custom.focus(); }
            return;
        }
        btn.disabled = true;
        btn.textContent = 'Przenosimy do płatności…';
    });
})();
</script>
@endpush
