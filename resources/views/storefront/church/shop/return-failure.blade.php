@extends('layouts.public')

@section('title', 'Wpłata nie powiodła się')
@section('bare', true)

@section('content')
    <div class="status-screen failure">
        <svg class="mark-svg" viewBox="0 0 56 56" aria-hidden="true">
            <circle class="ring" cx="28" cy="28" r="26.5"/>
            <line class="x" x1="19" y1="19" x2="37" y2="37"/>
            <line class="x" x1="37" y1="19" x2="19" y2="37"/>
        </svg>

        <h1>Wpłata się nie udała</h1>
        <p class="sub">Nie pobraliśmy żadnych środków.</p>

        <div class="actions">
            <a href="{{ route('product.show', $order->product->slug) }}" class="btn btn-gold">Spróbuj ponownie</a>
            <a href="{{ route('home') }}" class="btn btn-ghost" style="border-color:#e0d4b0;color:#f1e8d2">Inna parafia</a>
        </div>

        <div class="order-no">Potwierdzenie nr {{ $order->id }}</div>
    </div>
@endsection
