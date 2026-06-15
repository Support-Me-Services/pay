@extends('layouts.public')

@section('title', 'Zapłacono — możesz zabrać towar')
@section('bare', true)

@section('content')
    <div class="fullscreen-status success">
        {{-- Animowana zielona fajka (czysty SVG/CSS) --}}
        <svg class="checkmark-svg" viewBox="0 0 56 56">
            <circle class="circle" cx="28" cy="28" r="26.5"/>
            <path class="check" d="M16 29 l8 8 l16 -17"/>
        </svg>

        <h1>Zapłacono!</h1>
        <p class="sub">Możesz zabrać towar</p>

        @if($product->pickup_instruction)
            <div class="pickup-box">
                {{ $product->pickup_instruction }}
                @if($standNo)
                    <div class="mt-2" style="font-size:.9rem;opacity:.9">Stanowisko:</div>
                    <div style="font-size:3rem;font-weight:800;line-height:1">{{ $standNo }}</div>
                @endif
            </div>
        @endif

        @if($showTether)
            {{-- Animacja odpinającej się linki: dwa elementy rozsuwają się, linka znika --}}
            <svg class="tether-svg" viewBox="0 0 220 90" aria-hidden="true">
                <g class="anchor-left">
                    <rect x="58" y="30" width="34" height="30" rx="6" fill="#fff" opacity=".92"/>
                    <circle cx="92" cy="45" r="5" fill="#fff"/>
                </g>
                <line class="cord" x1="97" y1="45" x2="123" y2="45" stroke="#fff" stroke-width="3"/>
                <g class="anchor-right">
                    <circle cx="128" cy="45" r="5" fill="#fff"/>
                    <rect x="128" y="30" width="34" height="30" rx="6" fill="#fff" opacity=".92"/>
                </g>
            </svg>
        @endif

        <div class="d-flex gap-1 mt-3">
            <a href="{{ route('home') }}" class="btn" style="background:#fff;color:var(--success);font-weight:800">Wróć do sklepu</a>
        </div>

        <div class="order-no">Zamówienie nr {{ $order->id }} · {{ $order->amountPln() }} zł</div>
    </div>
@endsection
