@extends('layouts.public')

@section('title', 'Płatność — ' . $transaction->product_name)
@section('bare', true)

@section('content')
    <div style="min-height:100vh;background:var(--bg-alt);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px">
        <div class="card card-static" style="width:100%;max-width:420px">
            <div class="card-body">
                <div class="d-flex justify-between align-center mb-2">
                    <span class="site-logo" style="color:var(--ink)">nfc<span class="dot">pay</span></span>
                    <span class="badge badge-muted">płatność testowa</span>
                </div>

                <h3 class="mb-0">{{ $transaction->product_name }}</h3>
                <div class="price-xl mb-2">{{ $transaction->amountPln() }} zł</div>

                <div id="payForm">
                    <div class="form-group">
                        <label for="code">Kod BLIK (6 cyfr)</label>
                        <input type="text" id="code" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                               placeholder="123 456" style="font-size:1.5rem;text-align:center;letter-spacing:.35em">
                        <div class="form-hint">Tryb demo — dowolny 6-cyfrowy kod zostanie zaakceptowany.</div>
                        <div class="form-error" id="codeError" style="display:none">Wpisz 6 cyfr.</div>
                    </div>
                    <button type="button" id="payBtn" class="btn btn-primary btn-block">Zapłać {{ $transaction->amountPln() }} zł</button>
                    <button type="button" id="failBtn" class="btn btn-secondary btn-block mt-1">Symuluj odmowę</button>
                </div>

                <div id="processing" style="display:none;text-align:center;padding:24px 0">
                    <div class="spinner"></div>
                    <p class="fw-bold mt-2 mb-0">Przetwarzanie płatności...</p>
                    <p class="text-muted small">Potwierdź w aplikacji banku</p>
                </div>
            </div>
        </div>
        <p class="small text-muted mt-2">Symulator płatności (MockProvider) — środowisko demo</p>
    </div>

    <script>
        const csrf = @json(csrf_token());
        const urls = {
            confirm: @json(route('mockpay.confirm', $transaction->id)),
            fail: @json(route('mockpay.fail', $transaction->id)),
        };

        function settle(url, delayMs) {
            document.getElementById('payForm').style.display = 'none';
            document.getElementById('processing').style.display = 'block';
            setTimeout(function () {
                fetch(url, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
                })
                    .then(r => r.json())
                    .then(d => { window.location.href = d.redirect; })
                    .catch(() => { window.location.reload(); });
            }, delayMs);
        }

        document.getElementById('payBtn').addEventListener('click', function () {
            const code = document.getElementById('code').value.replace(/\s/g, '');
            if (!/^\d{6}$/.test(code)) {
                document.getElementById('codeError').style.display = 'block';
                return;
            }
            settle(urls.confirm, 2000); // dowolny kod = sukces po 2 s
        });

        document.getElementById('failBtn').addEventListener('click', function () {
            settle(urls.fail, 1200);
        });
    </script>
@endsection
