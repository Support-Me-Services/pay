@extends('layouts.panel')

@section('title', 'AntiTheft')

{{-- FIKCYJNE — moduł demo, brak realnej detekcji --}}
@section('content')
    <div class="panel-title">
        <h1>AntiTheft <button type="button" class="info-btn" onclick="document.getElementById('atModal').classList.add('open')" aria-label="Jak działa AntiTheft?">i</button></h1>

        <form method="GET" action="{{ route('panel.antitheft') }}">
            <select name="shop_id" onchange="this.form.submit()">
                @foreach($shops as $s)
                    <option value="{{ $s->id }}" @selected($shop && $shop->id === $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if($shop)
        <div class="card card-static mb-3">
            <div class="card-body text-center" style="padding:36px 20px">
                <div style="display:inline-flex;align-items:center;gap:12px;background:rgba(0,163,108,.12);color:var(--success);padding:14px 28px;border-radius:999px;font-size:1.3rem;font-weight:800">
                    <span style="font-size:1.6rem">✓</span> Sprawdzone
                </div>
                <p class="mt-2 mb-0 fw-bold">Nie wykryto obcych tagów w okolicy punktu sprzedaży</p>
                <p class="text-muted small mb-0">Ostatnie sprawdzenie: {{ $lastCheck->format('d.m.Y H:i') }} ({{ $lastCheck->diffForHumans() }})</p>
            </div>
        </div>

        <div class="card card-static">
            <div class="card-body">
                <h3>Tagi sklepu {{ $shop->name }}</h3>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                        <tr><th>UID taga</th><th>Etykieta</th><th>Status weryfikacji</th></tr>
                        </thead>
                        <tbody>
                        @forelse($tags as $tag)
                            <tr>
                                <td class="fw-bold"><code>{{ $tag->tag_uid }}</code></td>
                                <td>{{ $tag->label ?: '—' }}</td>
                                <td><span class="badge badge-success">✓ Zweryfikowany</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted">Brak tagów w tym sklepie.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="modal-backdrop" id="atModal" onclick="if(event.target===this)this.classList.remove('open')">
        <div class="modal">
            <h3>Jak działa AntiTheft?</h3>
            <p>System cyklicznie weryfikuje, czy w okolicy punktu sprzedaży nie pojawiły się tagi NFC inne niż
                zarejestrowane w tym sklepie. Wykrycie obcego taga (np. naklejonego przez oszusta na Twój produkt
                w celu przekierowania płatności) oznaczane jest statusem ostrzeżenia i natychmiastowym powiadomieniem.</p>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('atModal').classList.remove('open')">Rozumiem</button>
        </div>
    </div>
@endsection
