@extends('layouts.panel')

@section('title', 'Parafie do obdzwonienia')

@section('content')
    <div class="panel-title">
        <h1>Parafie do obdzwonienia</h1>
        <a href="{{ route('panel.coverage.map') }}" class="btn btn-secondary btn-sm">Mapa pokrycia ↗</a>
    </div>

    {{-- Liczniki: łączny + per status (klikalne jako filtr) --}}
    <div class="d-flex gap-1 mb-3" style="flex-wrap:wrap">
        <a href="{{ route('panel.potential-parishes.index', array_filter(['voivodeship' => $voivodeship, 'city' => $city, 'name' => $name, 'salesperson_id' => $salespersonId])) }}"
           class="btn btn-sm {{ $status ? 'btn-secondary' : 'btn-primary' }}">Wszystkie ({{ number_format($total, 0, ',', ' ') }})</a>
        @foreach(\App\Modules\Storefront\Models\PotentialParish::STATUSES as $key => $label)
            <a href="{{ route('panel.potential-parishes.index', array_filter(['status' => $key, 'voivodeship' => $voivodeship, 'city' => $city, 'name' => $name, 'salesperson_id' => $salespersonId])) }}"
               class="btn btn-sm {{ $status === $key ? 'btn-primary' : 'btn-secondary' }}">
                {{ $label }} ({{ number_format($statusCounts[$key] ?? 0, 0, ',', ' ') }})
            </a>
        @endforeach
    </div>

    {{-- Pasek filtrów: województwo, miasto, nazwa, status, handlowiec --}}
    <div class="card card-static mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('panel.potential-parishes.index') }}" class="d-flex gap-1" style="flex-wrap:wrap;align-items:flex-end">
                <div>
                    <label class="text-muted" style="display:block;font-size:.78rem;margin-bottom:2px">Województwo</label>
                    <select name="voivodeship" style="min-width:180px">
                        <option value="">— wszystkie —</option>
                        @foreach(\App\Modules\Storefront\Models\Salesperson::VOIVODESHIPS as $woj)
                            <option value="{{ $woj }}" @selected($voivodeship === $woj)>{{ $woj }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-muted" style="display:block;font-size:.78rem;margin-bottom:2px">Miasto</label>
                    <input type="text" name="city" value="{{ $city }}" placeholder="Szukaj miasta…" style="min-width:160px">
                </div>
                <div>
                    <label class="text-muted" style="display:block;font-size:.78rem;margin-bottom:2px">Nazwa</label>
                    <input type="text" name="name" value="{{ $name }}" placeholder="Szukaj nazwy…" style="min-width:200px">
                </div>
                <div>
                    <label class="text-muted" style="display:block;font-size:.78rem;margin-bottom:2px">Status</label>
                    <select name="status" style="min-width:160px">
                        <option value="">— wszystkie —</option>
                        @foreach(\App\Modules\Storefront\Models\PotentialParish::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-muted" style="display:block;font-size:.78rem;margin-bottom:2px">Handlowiec</label>
                    <select name="salesperson_id" style="min-width:180px">
                        <option value="">— wszyscy —</option>
                        @foreach($salespeople as $sp)
                            <option value="{{ $sp->id }}" @selected($salespersonId === $sp->id)>{{ $sp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary btn-sm">Filtruj</button>
                    <a href="{{ route('panel.potential-parishes.index') }}" class="btn btn-secondary btn-sm">Wyczyść</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-static">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Nazwa</th><th>Miasto</th><th>Woj.</th><th>Status</th><th>Telefon</th><th>Handlowiec</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($parishes as $parish)
                        @php [$bg, $fg] = $parish->statusColors(); @endphp
                        <tr data-parish-row="{{ $parish->id }}">
                            <td class="fw-bold">{{ $parish->name }}</td>
                            <td>{{ $parish->city ?: '—' }}</td>
                            <td>{{ $parish->voivodeship ?: '—' }}</td>
                            <td>
                                <span class="badge js-status-badge" data-badge-for="{{ $parish->id }}"
                                      style="background:{{ $bg }};color:{{ $fg }};font-weight:600">{{ $parish->statusLabel() }}</span>
                                <br><span class="text-muted js-called-at" data-called-for="{{ $parish->id }}"
                                          style="font-size:.72rem">{{ $parish->called_at?->format('d.m.Y') }}</span>
                            </td>
                            <td class="text-muted" data-phone-cell="{{ $parish->id }}" style="font-size:.82rem">
                                {{ $parish->phone ?: '—' }}
                            </td>
                            <td class="text-muted js-sp-cell" data-sp-for="{{ $parish->id }}">{{ $parish->salesperson?->name ?: '—' }}</td>
                        </tr>
                        {{-- Formularz auto-zapisu (status + handlowiec + telefon + notatka). Bez przycisku „Zapisz”. --}}
                        <tr data-parish-edit="{{ $parish->id }}">
                            <td colspan="6" style="background:#fafbfc;padding-top:6px;padding-bottom:14px">
                                <div class="js-parish-form d-flex gap-1"
                                     data-url="{{ route('panel.potential-parishes.status', $parish) }}"
                                     data-id="{{ $parish->id }}"
                                     style="flex-wrap:wrap;align-items:flex-end">
                                    <div>
                                        <label class="text-muted" style="display:block;font-size:.74rem;margin-bottom:2px">Status</label>
                                        <select name="status" class="js-f-status" style="min-width:160px">
                                            @foreach(\App\Modules\Storefront\Models\PotentialParish::STATUSES as $key => $label)
                                                <option value="{{ $key }}" @selected($parish->status === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-muted" style="display:block;font-size:.74rem;margin-bottom:2px">Telefon</label>
                                        <input type="text" name="phone" class="js-f-phone" value="{{ $parish->phone }}"
                                               placeholder="np. 12 345 67 89" style="min-width:150px">
                                    </div>
                                    <div>
                                        <label class="text-muted" style="display:block;font-size:.74rem;margin-bottom:2px">Handlowiec</label>
                                        <select name="salesperson_id" class="js-f-sp" style="min-width:180px">
                                            <option value="">— brak —</option>
                                            @foreach($salespeople as $sp)
                                                <option value="{{ $sp->id }}" @selected($parish->salesperson_id === $sp->id)>{{ $sp->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div style="flex:1;min-width:240px">
                                        <label class="text-muted" style="display:block;font-size:.74rem;margin-bottom:2px">Notatka</label>
                                        <input type="text" name="note" class="js-f-note" value="{{ $parish->note }}"
                                               placeholder="Notatka z rozmowy…" style="width:100%">
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">Brak parafii spełniających kryteria.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginacja: własny markup spójny z panelem (Tailwind z links() nie pasuje do theme.css).
                 Querystring filtrów zachowany przez ->withQueryString() w kontrolerze. --}}
            @if($parishes->hasPages())
                <div class="parish-pagination">
                    @if($parishes->onFirstPage())
                        <span class="btn btn-secondary btn-sm is-disabled">← Poprzednia</span>
                    @else
                        <a href="{{ $parishes->previousPageUrl() }}" class="btn btn-secondary btn-sm" rel="prev">← Poprzednia</a>
                    @endif

                    <span class="parish-pagination-info text-muted">
                        Strona {{ $parishes->currentPage() }} z {{ $parishes->lastPage() }}
                        ({{ number_format($parishes->total(), 0, ',', ' ') }} parafii)
                    </span>

                    @if($parishes->hasMorePages())
                        <a href="{{ $parishes->nextPageUrl() }}" class="btn btn-secondary btn-sm" rel="next">Następna →</a>
                    @else
                        <span class="btn btn-secondary btn-sm is-disabled">Następna →</span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Toast „Zapisano” (auto-zapis) --}}
    <div id="parish-toast" class="parish-toast" role="status" aria-live="polite"></div>

    <style>
        .parish-pagination { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-top:14px; }
        .parish-pagination .is-disabled { opacity:.45; pointer-events:none; cursor:default; }
        .parish-pagination-info { font-size:.85rem; }
        .parish-toast {
            position: fixed; right: 24px; bottom: 24px; z-index: 99999;
            background: var(--success, #00a36c); color: #fff;
            padding: 12px 20px; border-radius: 10px; font-weight: 700; font-size: .9rem;
            box-shadow: 0 8px 24px rgba(0,0,0,.18);
            opacity: 0; transform: translateY(12px); pointer-events: none;
            transition: opacity .22s ease, transform .22s ease;
        }
        .parish-toast.is-error { background: var(--error, #d90000); }
        .parish-toast.show { opacity: 1; transform: translateY(0); }
    </style>

    @push('scripts')
    <script>
        (function () {
            const csrf = @json(csrf_token());
            const toast = document.getElementById('parish-toast');
            let toastTimer = null;

            function showToast(msg, isError) {
                if (!toast) return;
                toast.textContent = msg;
                toast.classList.toggle('is-error', !!isError);
                toast.classList.add('show');
                clearTimeout(toastTimer);
                toastTimer = setTimeout(() => toast.classList.remove('show'), 2000);
            }

            function save(form) {
                const id = form.dataset.id;
                const url = form.dataset.url;
                const payload = {
                    status:         form.querySelector('.js-f-status').value,
                    salesperson_id: form.querySelector('.js-f-sp').value || null,
                    note:           form.querySelector('.js-f-note').value,
                    phone:          form.querySelector('.js-f-phone').value,
                };

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload),
                })
                    .then(r => r.ok ? r.json() : r.json().then(j => Promise.reject(j)))
                    .then(data => {
                        // Aktualizacja widoku w wierszu bez przeładowania.
                        const badge = document.querySelector('[data-badge-for="' + id + '"]');
                        if (badge && data.status_colors) {
                            badge.textContent = data.status_label;
                            badge.style.background = data.status_colors[0];
                            badge.style.color = data.status_colors[1];
                        }
                        const called = document.querySelector('[data-called-for="' + id + '"]');
                        if (called) called.textContent = data.called_at || '';
                        const phoneCell = document.querySelector('[data-phone-cell="' + id + '"]');
                        if (phoneCell) phoneCell.textContent = payload.phone.trim() || '—';
                        const spCell = document.querySelector('[data-sp-for="' + id + '"]');
                        if (spCell) spCell.textContent = data.salesperson || '—';

                        showToast(data.message || 'Zapisano', false);
                    })
                    .catch(() => showToast('Nie udało się zapisać', true));
            }

            document.querySelectorAll('.js-parish-form').forEach(function (form) {
                // Selecty → zapis na change; pola tekstowe → zapis na blur.
                form.querySelectorAll('select').forEach(el =>
                    el.addEventListener('change', () => save(form)));
                form.querySelectorAll('input[type="text"]').forEach(el => {
                    el.addEventListener('blur', () => save(form));
                    el.addEventListener('keydown', e => {
                        if (e.key === 'Enter') { e.preventDefault(); el.blur(); }
                    });
                });
            });
        })();
    </script>
    @endpush
@endsection
