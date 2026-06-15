@extends('layouts.public')

@section('title', 'Otwieram aplikację banku...')
@section('bare', true)

@section('content')
    <div class="fullscreen-status" style="background:#fff;color:var(--ink)">
        <div class="phone-pulse">
            <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:3rem">🏦</div>
        </div>
        <h1 style="color:var(--ink);font-size:1.6rem">Otwieram aplikację banku...</h1>
        <p class="text-muted">Potwierdź płatność <strong style="color:var(--ink)">{{ $transaction->amountPln() }} zł</strong> w swojej aplikacji.</p>
        <div class="spinner mt-2"></div>
        <p class="small text-muted mt-3">{{ $transaction->product_name }}</p>

        <button type="button" id="failBtn" class="btn btn-secondary btn-sm mt-4">Symuluj odmowę</button>
    </div>

    <script>
        const csrf = @json(csrf_token());
        const urls = {
            confirm: @json(route('mockpay.confirm', $transaction->id)),
            fail: @json(route('mockpay.fail', $transaction->id)),
        };
        let declined = false;

        function settle(url) {
            fetch(url, {method: 'POST', headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'}})
                .then(r => r.json())
                .then(d => { window.location.href = d.redirect; })
                .catch(() => { window.location.reload(); });
        }

        // Symulacja aplikacji banku: sukces po 3 s
        const timer = setTimeout(function () {
            if (!declined) settle(urls.confirm);
        }, 3000);

        document.getElementById('failBtn').addEventListener('click', function () {
            declined = true;
            clearTimeout(timer);
            settle(urls.fail);
        });
    </script>
@endsection
