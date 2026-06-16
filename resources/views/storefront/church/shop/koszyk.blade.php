@extends('layouts.landing')

@section('title', 'Koszyk — ' . config('shop.name'))
@section('meta-description', 'Twój koszyk — gadżety charytatywne SupportMe.')

@push('head')
<link rel="stylesheet" href="{{ asset('css/sklep.css') }}?v={{ substr(md5_file(public_path('css/sklep.css')), 0, 10) }}">
@endpush

@section('content')
    <section class="shop-hero">
        <h1>Koszyk</h1>
    </section>

    <div class="shop-wrap shop-wrap--narrow">
        @if(session('error'))
            <div class="shop-flash shop-flash--err">{{ session('error') }}</div>
        @endif

        @if($count === 0)
            <div class="cart-empty">
                <p>Twój koszyk jest pusty.</p>
                <a href="{{ route('home') }}" class="shop-card__btn cart-empty__btn">Wróć do sklepu</a>
            </div>
        @else
            <div class="cart">
                @foreach($lines as $l)
                    <div class="cart-row">
                        <div class="cart-row__art"><img src="{{ asset($l['product']['image']) }}" alt="{{ $l['product']['name'] }}"></div>
                        <div class="cart-row__name">
                            <span>{{ $l['product']['name'] }}</span>
                            <small>{{ number_format($l['product']['price'] / 100, 0, ',', ' ') }} zł / szt.</small>
                        </div>
                        <form method="POST" action="{{ route('cart.update', $l['product']['slug']) }}" class="cart-row__qty">
                            @csrf
                            <input type="number" name="qty" value="{{ $l['qty'] }}" min="0" max="99" aria-label="Ilość" onchange="this.form.submit()">
                        </form>
                        <div class="cart-row__sub">{{ number_format($l['subtotal'] / 100, 0, ',', ' ') }} zł</div>
                        <form method="POST" action="{{ route('cart.update', $l['product']['slug']) }}" class="cart-row__rm">
                            @csrf
                            <input type="hidden" name="qty" value="0">
                            <button type="submit" aria-label="Usuń">×</button>
                        </form>
                    </div>
                @endforeach

                <div class="cart-total">
                    <span>Razem</span>
                    <strong>{{ number_format($total / 100, 0, ',', ' ') }} zł</strong>
                </div>

                <form method="POST" action="{{ route('cart.checkout') }}" class="cart-checkout">
                    @csrf
                    <a href="{{ route('home') }}" class="cart-continue">← Kontynuuj zakupy</a>
                    <button type="submit" class="shop-card__btn cart-pay">Zamów i zapłać</button>
                </form>
                <p class="cart-note">🔒 Bezpieczna płatność BLIK / karta · obsługuje PayU</p>
            </div>
        @endif
    </div>
@endsection
