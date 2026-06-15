@extends('layouts.public')

@section('title', 'Błąd płatności')
@section('bare', true)

@section('content')
    <div class="fullscreen-status failure">
        <svg class="cross-svg" viewBox="0 0 56 56">
            <circle class="circle" cx="28" cy="28" r="26.5"/>
            <line class="line" x1="18" y1="18" x2="38" y2="38"/>
            <line class="line" x1="38" y1="18" x2="18" y2="38"/>
        </svg>
        <h1>Nie udało się rozpocząć płatności</h1>
        <p class="sub">Spróbuj ponownie za chwilę.</p>
        <a href="{{ $transaction->return_url }}" class="btn btn-secondary mt-3" style="background:#fff;border-color:#fff">Wróć do sklepu</a>
        <div class="order-no">Transakcja: {{ $transaction->id }}</div>
    </div>
@endsection
