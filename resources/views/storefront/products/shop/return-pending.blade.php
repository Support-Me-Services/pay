@extends('layouts.public')

@section('title', 'Sprawdzamy płatność...')
@section('bare', true)

@section('content')
    <div class="fullscreen-status" style="background:#fff;color:var(--ink)">
        <div class="spinner mb-2"></div>
        <h1 style="color:var(--ink);font-size:1.5rem">Sprawdzamy Twoją płatność...</h1>
        <p class="text-muted">To potrwa tylko chwilę. Nie zamykaj tej strony.</p>
        <div class="order-no" style="color:var(--muted)">Zamówienie nr {{ $order->id }}</div>
    </div>

    <script>
        // Polling statusu — webhook PayU może dotrzeć kilka sekund po powrocie klienta.
        const statusUrl = @json(route('order.status', $order->id));
        let attempts = 0;

        const poll = setInterval(function () {
            attempts++;
            fetch(statusUrl, {headers: {'Accept': 'application/json'}})
                .then(r => r.json())
                .then(d => {
                    if (d.status === 'paid' || d.status === 'failed') {
                        clearInterval(poll);
                        window.location.reload();
                    } else if (attempts >= 30) { // ~60 s — poddajemy się, pokaż stan bieżący
                        clearInterval(poll);
                        window.location.reload();
                    }
                })
                .catch(() => {});
        }, 2000);
    </script>
@endsection
