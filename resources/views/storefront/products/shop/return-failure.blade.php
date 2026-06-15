@extends('layouts.public')

@section('title', 'Płatność nie powiodła się')
@section('bare', true)

@section('content')
    <div class="fullscreen-status failure">
        <svg class="cross-svg" viewBox="0 0 56 56">
            <circle class="circle" cx="28" cy="28" r="26.5"/>
            <line class="line" x1="18" y1="18" x2="38" y2="38"/>
            <line class="line" x1="38" y1="18" x2="18" y2="38"/>
        </svg>

        <h1>Płatność nie powiodła się</h1>
        <p class="sub">Nie pobraliśmy żadnych środków.</p>

        <a href="{{ route('product.show', $order->product->slug) }}" class="btn mt-3"
           style="background:#fff;color:var(--error);font-weight:800">Spróbuj ponownie</a>

        <div class="order-no">Zamówienie nr {{ $order->id }}</div>
    </div>
@endsection
