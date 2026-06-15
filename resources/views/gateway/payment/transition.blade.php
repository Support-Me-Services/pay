@extends('layouts.public')

@section('title', 'Przenosimy Cię do płatności...')
@section('bare', true)

@section('content')
    <div class="fullscreen-status" style="background:#fff;color:var(--ink)">
        <div class="phone-pulse">
            <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:3rem">📱</div>
        </div>
        <h1 style="color:var(--ink);font-size:1.6rem">Przenosimy Cię do płatności...</h1>
        <p class="text-muted">Za chwilę otworzy się aplikacja Twojego banku.</p>
        <div class="spinner mt-2"></div>
        <p class="small text-muted mt-3">{{ $transaction->product_name }} — <strong>{{ $transaction->amountPln() }} zł</strong></p>
    </div>

    <script>
        // ~1 s ekranu przejściowego, potem redirect do operatora (BLIK/aplikacja banku)
        setTimeout(function () {
            window.location.href = @json($redirectUrl);
        }, 1100);
    </script>
@endsection
