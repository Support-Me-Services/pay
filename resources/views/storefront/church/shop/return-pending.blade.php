@extends('layouts.public')

@section('title', 'Potwierdzamy wpłatę…')
@section('bare', true)

@section('content')
    <div class="status-screen plain">
        <div class="spinner"></div>
        <h1 style="color:var(--navy)">Potwierdzamy Twoją wpłatę…</h1>
        <p class="muted">To potrwa tylko chwilę. Nie zamykaj tej strony.</p>
        <div class="order-no" style="color:var(--muted)">Potwierdzenie nr {{ $order->id }}</div>
    </div>

    <script>
        // Polling statusu — webhook PayU może dotrzeć kilka sekund po powrocie darczyńcy.
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
                    } else if (attempts >= 30) { // ~60 s — pokaż stan bieżący
                        clearInterval(poll);
                        window.location.reload();
                    }
                })
                .catch(() => {});
        }, 2000);
    </script>
@endsection
