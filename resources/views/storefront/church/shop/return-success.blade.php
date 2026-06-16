@extends('layouts.public')

@section('title', 'Dziękujemy za wsparcie')
@section('bare', true)

@section('content')
    <div class="status-screen success">
        <div class="candle" aria-hidden="true">
            <span class="flame"></span>
            <span class="stick"></span>
        </div>

        <svg class="mark-svg" viewBox="0 0 56 56" aria-hidden="true">
            <circle class="ring" cx="28" cy="28" r="26.5"/>
            <path class="tick" d="M16 29 l8 8 l16 -17"/>
        </svg>

        <h1>Bóg zapłać!</h1>
        <p class="sub">Twoja taca została złożona.</p>

        <div class="amount-badge">
            <span class="lbl">Wpłacono</span>
            <span class="val">{{ $order->amountPln() }} zł</span>
            <span class="for">na rzecz: {{ $product->name }}@if($product->city), {{ $product->city }}@endif</span>
        </div>

        <div class="actions">
            <a href="{{ route('category', 'miejsca-kultu') }}" class="btn btn-gold">Wesprzyj kolejną parafię</a>
        </div>

        <div class="order-no">Potwierdzenie nr {{ $order->id }}</div>
    </div>
@endsection
