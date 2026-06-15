@extends('layouts.public')

@section('title', 'Płatność BLIK — ' . $transaction->product_name)
@section('bare', true)

@section('content')
    <div style="min-height:100vh;background:var(--bg-alt);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px">
        <div class="card card-static" style="width:100%;max-width:420px">
            <div class="card-body">
                <div class="d-flex justify-between align-center mb-2">
                    <span class="site-logo" style="color:var(--ink)">nfc<span class="dot">pay</span></span>
                    <span class="badge badge-brand">BLIK</span>
                </div>

                <h3 class="mb-0">{{ $transaction->product_name }}</h3>
                <div class="price-xl mb-2">{{ $transaction->amountPln() }} zł</div>

                <div id="payForm">
                    <div class="form-group">
                        <label for="code">Kod BLIK (6 cyfr)</label>
                        <input type="text" id="code" inputmode="numeric" autocomplete="one-time-code"
                               pattern="[0-9]*" maxlength="6" placeholder="123 456"
                               style="font-size:1.5rem;text-align:center;letter-spacing:.35em">
                        <div class="form-hint">Kod znajdziesz w aplikacji swojego banku, w sekcji BLIK.</div>
                        <div class="form-error" id="codeError" style="display:none"></div>
                    </div>
                    <button type="button" id="payBtn" class="btn btn-primary btn-block">Zapłać {{ $transaction->amountPln() }} zł</button>
                </div>

                <div id="processing" style="display:none;text-align:center;padding:24px 0">
                    <div class="phone-pulse" style="width:72px;height:72px;margin-bottom:14px">
                        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:2.2rem">📱</div>
                    </div>
                    <p class="fw-bold mb-0" style="font-size:1.05rem">Potwierdź płatność w aplikacji banku</p>
                    <p class="text-muted small">Wysłaliśmy żądanie płatności — zatwierdź je w swojej aplikacji bankowej.</p>
                    <div class="spinner mt-1" style="width:34px;height:34px;border-width:4px"></div>
                </div>
            </div>
        </div>
        <p class="small text-muted mt-2">Płatność obsługuje PayU · please-support-me.com</p>
    </div>

    <script>
        const csrf = @json(csrf_token());
        const urls = {
            blik: @json(route('pay.blik', $transaction->id)),
            status: @json(route('pay.status', $transaction->id)),
            ret: @json(route('pay.return', $transaction->id)),
        };

        const codeInput = document.getElementById('code');
        const errBox = document.getElementById('codeError');

        function showError(msg) {
            document.getElementById('processing').style.display = 'none';
            document.getElementById('payForm').style.display = 'block';
            errBox.textContent = msg;
            errBox.style.display = 'block';
            const btn = document.getElementById('payBtn');
            btn.disabled = false;
        }

        function poll() {
            fetch(urls.status, {headers: {'Accept': 'application/json'}})
                .then(r => r.json())
                .then(d => {
                    if (d.redirect) {
                        window.location.href = d.redirect;
                    } else {
                        setTimeout(poll, 2000);
                    }
                })
                .catch(() => setTimeout(poll, 3000));
        }

        document.getElementById('payBtn').addEventListener('click', function () {
            const code = codeInput.value.replace(/\s/g, '');
            if (!/^\d{6}$/.test(code)) {
                errBox.textContent = 'Wpisz 6 cyfr kodu BLIK.';
                errBox.style.display = 'block';
                return;
            }
            this.disabled = true;
            errBox.style.display = 'none';
            document.getElementById('payForm').style.display = 'none';
            document.getElementById('processing').style.display = 'block';

            fetch(urls.blik, {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json'},
                body: JSON.stringify({code}),
            })
                .then(async r => {
                    const d = await r.json();
                    if (r.ok) {
                        if (d.redirect) { window.location.href = d.redirect; return; }
                        poll(); // czekamy na potwierdzenie w aplikacji banku (webhook)
                    } else {
                        showError(d.error || (d.errors?.code?.[0]) || 'Nie udało się rozpocząć płatności.');
                    }
                })
                .catch(() => showError('Błąd połączenia. Spróbuj ponownie.'));
        });

        codeInput.focus();
    </script>
@endsection
