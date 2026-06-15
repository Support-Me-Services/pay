@extends('layouts.public')

@section('title', 'Płatność — ' . $transaction->product_name)
@section('bare', true)

@section('content')
    <div style="min-height:100vh;background:var(--bg-alt);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px">
        <div class="card card-static" style="width:100%;max-width:440px">
            <div class="card-body">
                <div class="d-flex justify-between align-center mb-2">
                    <span class="site-logo" style="color:var(--ink)">nfc<span class="dot">pay</span></span>
                    <span class="badge badge-brand">płatność mobilna</span>
                </div>

                <h3 class="mb-0">{{ $transaction->product_name }}</h3>
                <div class="price-xl mb-2">{{ $transaction->amountPln() }} zł</div>

                {{-- Stan: wybór banku --}}
                <div id="bankSelect" @if($continueUrl) style="display:none" @endif>
                    <p class="fw-bold mb-1">Wybierz swój bank</p>
                    <p class="text-muted small mt-0">Otworzy się aplikacja Twojego banku — potwierdzisz odciskiem palca lub Face ID.</p>
                    <div class="form-error mb-1" id="bankError" style="display:none"></div>
                    <div class="bank-grid">
                        @foreach($banks as $bank)
                            <button type="button" class="bank-btn" data-method="{{ $bank['value'] }}" title="{{ $bank['name'] }}">
                                @if($bank['image'])
                                    <img src="{{ $bank['image'] }}" alt="{{ $bank['name'] }}" loading="lazy">
                                @else
                                    <span class="small fw-bold">{{ $bank['name'] }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    <details class="mt-3">
                        <summary class="text-muted small" style="cursor:pointer">Wolisz zapłacić kodem BLIK?</summary>
                        <div class="mt-2">
                            <div class="form-group">
                                <label for="code">Kod BLIK (6 cyfr)</label>
                                <input type="text" id="code" inputmode="numeric" autocomplete="one-time-code"
                                       pattern="[0-9]*" maxlength="6" placeholder="123 456"
                                       style="font-size:1.4rem;text-align:center;letter-spacing:.35em">
                                <div class="form-error" id="codeError" style="display:none"></div>
                            </div>
                            <button type="button" id="payBtn" class="btn btn-primary btn-block">Zapłać {{ $transaction->amountPln() }} zł</button>
                        </div>
                    </details>
                </div>

                {{-- Stan: czekamy na potwierdzenie w aplikacji banku --}}
                <div id="processing" style="display:none;text-align:center;padding:24px 0">
                    <div class="phone-pulse" style="width:72px;height:72px;margin-bottom:14px">
                        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:2.2rem">📱</div>
                    </div>
                    <p class="fw-bold mb-0" style="font-size:1.05rem">Potwierdź płatność w aplikacji banku</p>
                    <p class="text-muted small">Zatwierdź ją biometrią lub PIN-em w swojej aplikacji bankowej.</p>
                    <div class="spinner mt-1" style="width:34px;height:34px;border-width:4px"></div>
                </div>

                {{-- Stan: zamówienie już czeka u operatora (powrót na stronę) --}}
                @if($continueUrl)
                    <div id="continueBox" class="text-center" style="padding:12px 0">
                        <p class="fw-bold mb-1">Twoja płatność czeka na dokończenie</p>
                        <a href="{{ $continueUrl }}" class="btn btn-primary btn-block">Dokończ w aplikacji banku</a>
                        <p class="text-muted small mt-2">Sprawdzamy status na bieżąco — po opłaceniu wrócisz do sklepu automatycznie.</p>
                    </div>
                @endif
            </div>
        </div>
        <p class="small text-muted mt-2">Płatność obsługuje PayU · pay.redai.pl</p>
    </div>

    <script>
        const csrf = @json(csrf_token());
        const urls = {
            bank: @json(route('pay.bank', $transaction->id)),
            blik: @json(route('pay.blik', $transaction->id)),
            status: @json(route('pay.status', $transaction->id)),
        };

        function poll() {
            fetch(urls.status, {headers: {'Accept': 'application/json'}})
                .then(r => r.json())
                .then(d => { d.redirect ? window.location.href = d.redirect : setTimeout(poll, 2000); })
                .catch(() => setTimeout(poll, 3000));
        }

        function showState(id) {
            ['bankSelect', 'processing', 'continueBox'].forEach(s => {
                const el = document.getElementById(s);
                if (el) el.style.display = s === id ? 'block' : 'none';
            });
        }

        function showError(boxId, msg) {
            showState('bankSelect');
            const box = document.getElementById(boxId);
            box.textContent = msg;
            box.style.display = 'block';
        }

        // Wybór banku → zamówienie PBL → redirect otwiera aplikację banku
        document.querySelectorAll('.bank-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                showState('processing');
                fetch(urls.bank, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json'},
                    body: JSON.stringify({method: this.dataset.method}),
                })
                    .then(async r => {
                        const d = await r.json();
                        if (r.ok && d.redirect) {
                            window.location.href = d.redirect;
                        } else {
                            showError('bankError', d.error || 'Nie udało się rozpocząć płatności. Spróbuj ponownie.');
                        }
                    })
                    .catch(() => showError('bankError', 'Błąd połączenia. Spróbuj ponownie.'));
            });
        });

        // Kod BLIK (opcja zapasowa)
        const payBtn = document.getElementById('payBtn');
        if (payBtn) payBtn.addEventListener('click', function () {
            const code = document.getElementById('code').value.replace(/\s/g, '');
            if (!/^\d{6}$/.test(code)) { showError('codeError', 'Wpisz 6 cyfr kodu BLIK.'); return; }
            showState('processing');
            fetch(urls.blik, {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json'},
                body: JSON.stringify({code}),
            })
                .then(async r => {
                    const d = await r.json();
                    if (r.ok) { d.redirect ? window.location.href = d.redirect : poll(); }
                    else showError('codeError', d.error || (d.errors?.code?.[0]) || 'Nie udało się rozpocząć płatności.');
                })
                .catch(() => showError('codeError', 'Błąd połączenia. Spróbuj ponownie.'));
        });

        @if($continueUrl)
        poll(); // zamówienie czeka — pilnuj statusu
        @endif
    </script>
@endsection
