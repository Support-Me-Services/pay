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

    {{-- Pasek filtrów PRZYKLEJONY u góry (sticky): nazwa, miasto, województwo, status, handlowiec.
         Filtry działają przez GET (server-side); paginacja zachowuje querystring. --}}
    <div class="parish-filters-bar">
        <form method="GET" action="{{ route('panel.potential-parishes.index') }}" class="d-flex gap-1" style="flex-wrap:wrap;align-items:flex-end">
            <div>
                <label class="text-muted parish-flabel">Nazwa parafii</label>
                <input type="text" name="name" value="{{ $name }}" placeholder="Szukaj nazwy…" style="min-width:200px">
            </div>
            <div>
                <label class="text-muted parish-flabel">Miasto</label>
                <input type="text" name="city" value="{{ $city }}" placeholder="Szukaj miasta…" style="min-width:160px">
            </div>
            <div>
                <label class="text-muted parish-flabel">Województwo</label>
                <select name="voivodeship" style="min-width:180px">
                    <option value="">— wszystkie —</option>
                    @foreach(\App\Modules\Storefront\Models\Salesperson::VOIVODESHIPS as $woj)
                        <option value="{{ $woj }}" @selected($voivodeship === $woj)>{{ $woj }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-muted parish-flabel">Status</label>
                <select name="status" style="min-width:160px">
                    <option value="">— wszystkie —</option>
                    @foreach(\App\Modules\Storefront\Models\PotentialParish::STATUSES as $key => $label)
                        <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-muted parish-flabel">Handlowiec</label>
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

    <div class="card card-static">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table parish-table">
                    <thead>
                    <tr>
                        <th>Nazwa</th><th>Miasto</th><th>Woj.</th>
                        <th>Status</th><th>Telefon</th><th>Handlowiec</th><th>Notatka</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($parishes as $parish)
                        @php [$bg, $fg] = $parish->statusColors(); @endphp
                        {{-- Edycja INLINE bezpośrednio w komórkach. Auto-zapis (AJAX) na change/blur,
                             bez przycisku „Zapisz”. Cały wiersz jest jednym formularzem logicznym. --}}
                        <tr class="js-parish-row" data-id="{{ $parish->id }}"
                            data-url="{{ route('panel.potential-parishes.status', $parish) }}">
                            <td class="fw-bold">{{ $parish->name }}</td>
                            <td>{{ $parish->city ?: '—' }}</td>
                            <td>{{ $parish->voivodeship ?: '—' }}</td>
                            <td>
                                <select name="status" class="js-f-status"
                                        style="min-width:150px;background:{{ $bg }};color:{{ $fg }};font-weight:600">
                                    @foreach(\App\Modules\Storefront\Models\PotentialParish::STATUSES as $key => $label)
                                        <option value="{{ $key }}" @selected($parish->status === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div class="text-muted js-called-at" style="font-size:.72rem;margin-top:2px">{{ $parish->called_at?->format('d.m.Y') }}</div>
                            </td>
                            <td>
                                <input type="text" name="phone" class="js-f-phone" value="{{ $parish->phone }}"
                                       placeholder="np. 12 345 67 89" style="min-width:130px">
                            </td>
                            <td>
                                <select name="salesperson_id" class="js-f-sp" style="min-width:160px">
                                    <option value="">— brak —</option>
                                    @foreach($salespeople as $sp)
                                        <option value="{{ $sp->id }}" @selected($parish->salesperson_id === $sp->id)>{{ $sp->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" name="note" class="js-f-note" value="{{ $parish->note }}"
                                       placeholder="Notatka z rozmowy…" style="min-width:200px;width:100%">
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted">Brak parafii spełniających kryteria.</td></tr>
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
        /* Pasek filtrów przyklejony nad tabelą. */
        .parish-filters-bar {
            position: sticky; top: 0; z-index: 50;
            background: var(--card-bg, #fff);
            border: 1px solid var(--border, #e5e7eb);
            border-radius: 12px;
            padding: 12px 16px; margin-bottom: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,.04);
        }
        .parish-flabel { display:block; font-size:.78rem; margin-bottom:2px; }
        .parish-table td { vertical-align: top; }
        .parish-table select, .parish-table input[type="text"] { font-size:.85rem; }
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
            const statusColors = @json(collect(\App\Modules\Storefront\Models\PotentialParish::STATUSES)->keys()->mapWithKeys(function ($k) {
                $p = new \App\Modules\Storefront\Models\PotentialParish(['status' => $k]);
                return [$k => $p->statusColors()];
            }));
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

            function save(row) {
                const url = row.dataset.url;
                const statusEl = row.querySelector('.js-f-status');
                const payload = {
                    status:         statusEl.value,
                    salesperson_id: row.querySelector('.js-f-sp').value || null,
                    note:           row.querySelector('.js-f-note').value,
                    phone:          row.querySelector('.js-f-phone').value,
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
                        // Odśwież kolor selecta statusu + datę kontaktu, bez przeładowania.
                        const colors = (data.status_colors) || statusColors[payload.status];
                        if (colors) {
                            statusEl.style.background = colors[0];
                            statusEl.style.color = colors[1];
                        }
                        const called = row.querySelector('.js-called-at');
                        if (called) called.textContent = data.called_at || '';
                        showToast(data.message || 'Zapisano', false);
                    })
                    .catch(() => showToast('Nie udało się zapisać', true));
            }

            document.querySelectorAll('.js-parish-row').forEach(function (row) {
                // Selecty (status, handlowiec) → zapis na change.
                row.querySelectorAll('select').forEach(el =>
                    el.addEventListener('change', () => save(row)));
                // Pola tekstowe (telefon, notatka) → zapis na blur; Enter = blur.
                row.querySelectorAll('input[type="text"]').forEach(el => {
                    el.addEventListener('blur', () => save(row));
                    el.addEventListener('keydown', e => {
                        if (e.key === 'Enter') { e.preventDefault(); el.blur(); }
                    });
                });
            });
        })();
    </script>
    @endpush
@endsection
