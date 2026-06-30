@extends('layouts.public')

@section('title', 'Płatność — ' . $transaction->product_name)
@section('bare', true)

@push('head')
<style>
    .pay-screen{
        --sm-ink:#24324A; --sm-blue-a:#4E7FA7; --sm-blue-b:#1473C0; --sm-line:#E6EAF0;
        min-height:100vh; background:#EEF3F8; display:flex; flex-direction:column;
        align-items:center; justify-content:center; padding:20px;
        font-family:'Inter',system-ui,-apple-system,'Segoe UI',sans-serif;
    }
    .pay-card{ width:100%; max-width:440px; background:#fff; border:1px solid var(--sm-line);
        border-radius:22px; box-shadow:0 16px 48px rgba(36,50,74,.12); padding:22px 22px 24px; }
    .pay-top{ display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
    .pay-back{ display:inline-flex; align-items:center; gap:6px; border:1px solid var(--sm-line);
        background:#fff; color:var(--sm-ink); font-weight:600; font-size:.85rem; padding:7px 13px;
        border-radius:999px; cursor:pointer; line-height:1; }
    .pay-back:hover{ background:#EEF3F8; }
    .pay-badge{ background:rgba(20,115,192,.10); color:var(--sm-blue-b); font-weight:700;
        font-size:.72rem; padding:6px 12px; border-radius:999px; white-space:nowrap; }
    .pay-logo{ font-weight:800; font-size:1.3rem; letter-spacing:-.02em; color:var(--sm-ink); }
    .pay-logo span{ color:var(--sm-blue-b); }
    .pay-product{ margin:14px 0 2px; font-size:1.02rem; font-weight:700; color:var(--sm-ink); }
    .pay-amount{ font-size:2.15rem; font-weight:800; color:var(--sm-ink); letter-spacing:-.02em; }
    .pay-method{ border:1px solid var(--sm-line); border-radius:16px; padding:16px; margin-top:14px; }
    .pay-method h4{ margin:0 0 4px; font-size:1rem; color:var(--sm-ink); }
    .pay-method .hint{ margin:0 0 12px; color:#5b6b80; font-size:.85rem; line-height:1.45; }
    .pay-or{ text-align:center; color:#90a0b3; font-size:.74rem; font-weight:700;
        text-transform:uppercase; letter-spacing:.12em; margin:16px 0 2px; }
    .sm-input{ width:100%; border:2px solid var(--sm-line); border-radius:12px; padding:12px;
        font-size:1.5rem; text-align:center; letter-spacing:.35em; color:var(--sm-ink); box-sizing:border-box; }
    .sm-input:focus{ outline:none; border-color:var(--sm-blue-b); }
    .sm-btn{ display:flex; width:100%; align-items:center; justify-content:center; gap:8px;
        border:0; cursor:pointer; font-weight:700; font-size:.95rem; padding:13px 18px;
        border-radius:999px; box-sizing:border-box; text-decoration:none; }
    .sm-btn-primary{ color:#fff; background:linear-gradient(117deg,var(--sm-blue-a),var(--sm-blue-b)); }
    .sm-btn-primary:hover{ filter:brightness(1.06); color:#fff; }
    .sm-btn-outline{ background:#fff; border:1.5px solid var(--sm-blue-b); color:var(--sm-blue-b); margin-top:10px; }
    .sm-btn-outline:hover{ background:rgba(20,115,192,.06); color:var(--sm-blue-b); }
    .sm-bank-grid{ display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:12px; }
    .sm-bank-btn{ background:#F4F7FA; border:2px solid transparent; border-radius:12px; padding:10px 8px;
        cursor:pointer; display:flex; align-items:center; justify-content:center; min-height:52px; }
    .sm-bank-btn:hover{ border-color:var(--sm-blue-b); background:#fff; }
    .sm-bank-btn img{ max-height:26px; max-width:100%; object-fit:contain; }
    .sm-err{ color:#c0143c; font-size:.85rem; margin:6px 0; display:none; }
    .pay-foot{ color:#90a0b3; font-size:.8rem; margin-top:14px; text-align:center; }
    .sm-spin{ width:34px; height:34px; border:4px solid #dbe4ee; border-top-color:var(--sm-blue-b);
        border-radius:50%; animation:smspin .8s linear infinite; margin:12px auto 0; }
    @keyframes smspin{ to{ transform:rotate(360deg); } }
</style>
@endpush

@section('content')
<div class="pay-screen">
    <div class="pay-card">
        <div class="pay-top">
            <button type="button" class="pay-back" onclick="history.length>1?history.back():location.href='/'">
                <span aria-hidden="true">&larr;</span> Wstecz
            </button>
            <span class="pay-badge">Bezpieczna płatność</span>
        </div>

        <div class="pay-logo">Support<span>ME</span></div>
        <div class="pay-product">{{ $transaction->product_name }}</div>
        <div class="pay-amount">{{ $transaction->amountPln() }} zł</div>

        <div id="payMethods" @if($continueUrl) style="display:none" @endif>
            {{-- BLIK --}}
            <section class="pay-method">
                <h4>Zapłać kodem BLIK</h4>
                <p class="hint">Wpisz 6-cyfrowy kod z aplikacji bankowej i potwierdź płatność w telefonie.</p>
                <input type="text" id="code" inputmode="numeric" autocomplete="one-time-code"
                       pattern="[0-9]*" maxlength="6" placeholder="123 456" class="sm-input">
                <div class="sm-err" id="codeError"></div>
                <button type="button" id="payBtn" class="sm-btn sm-btn-primary" style="margin-top:12px">
                    Zapłać {{ $transaction->amountPln() }} zł
                </button>
            </section>

            <div class="pay-or">albo</div>

            {{-- Przelew przez płatności online --}}
            <section class="pay-method">
                <h4>Przelew przez płatności online</h4>
                <p class="hint">Wybierz swój bank — otworzy się jego aplikacja, a płatność potwierdzisz odciskiem palca lub Face&nbsp;ID. Możesz też zapłacić kartą.</p>
                <div class="sm-err" id="bankError"></div>
                @if(count($banks))
                    <div class="sm-bank-grid">
                        @foreach($banks as $bank)
                            <button type="button" class="sm-bank-btn" data-method="{{ $bank['value'] }}" title="{{ $bank['name'] }}">
                                @if($bank['image'])
                                    <img src="{{ $bank['image'] }}" alt="{{ $bank['name'] }}" loading="lazy">
                                @else
                                    <span style="font-size:.78rem;font-weight:700">{{ $bank['name'] }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
                <form method="POST" action="{{ route('pay.online', $transaction->id) }}">
                    @csrf
                    <button type="submit" class="sm-btn sm-btn-outline">Inny bank lub karta &rarr; przejdź do PayU</button>
                </form>
            </section>
        </div>

        {{-- Stan: czekamy na potwierdzenie --}}
        <div id="processing" style="display:none;text-align:center;padding:24px 0">
            <p style="font-weight:700;margin:0;color:#24324A;font-size:1.05rem">Potwierdź płatność w aplikacji banku</p>
            <p class="hint" style="margin-top:4px">Zatwierdź ją biometrią lub PIN-em w swojej aplikacji bankowej.</p>
            <div class="sm-spin"></div>
        </div>

        {{-- Stan: zamówienie czeka u operatora --}}
        @if($continueUrl)
            <div id="continueBox" style="text-align:center;padding:12px 0">
                <p style="font-weight:700;margin:0 0 8px;color:#24324A">Twoja płatność czeka na dokończenie</p>
                <a href="{{ $continueUrl }}" class="sm-btn sm-btn-primary">Dokończ płatność</a>
                <p class="hint" style="margin-top:10px">Sprawdzamy status na bieżąco — po opłaceniu wrócisz do sklepu automatycznie.</p>
            </div>
        @endif
    </div>
    <p class="pay-foot">Płatność obsługuje PayU &middot; please-support-me.com</p>
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
        ['payMethods', 'processing', 'continueBox'].forEach(s => {
            const el = document.getElementById(s);
            if (el) el.style.display = s === id ? 'block' : 'none';
        });
    }

    function showError(boxId, msg) {
        showState('payMethods');
        const box = document.getElementById(boxId);
        if (box) { box.textContent = msg; box.style.display = 'block'; }
    }

    // Wybór banku -> zamówienie PBL -> redirect otwiera aplikację banku
    document.querySelectorAll('.sm-bank-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            showState('processing');
            fetch(urls.bank, {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json'},
                body: JSON.stringify({method: this.dataset.method}),
            })
                .then(async r => {
                    const d = await r.json();
                    if (r.ok && d.redirect) { window.location.href = d.redirect; }
                    else { showError('bankError', d.error || 'Nie udało się rozpocząć płatności. Spróbuj ponownie.'); }
                })
                .catch(() => showError('bankError', 'Błąd połączenia. Spróbuj ponownie.'));
        });
    });

    // Kod BLIK
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
    poll();
    @endif
</script>
@endsection
