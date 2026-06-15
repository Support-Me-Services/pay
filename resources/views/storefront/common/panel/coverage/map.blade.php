@extends('layouts.panel')

@section('title', 'Mapa pokrycia')

@push('head')
    {{-- Leaflet + markercluster (CDN unpkg) — 20k punktów klastrowanych. --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
@endpush

@section('content')
    <div class="panel-title">
        <h1>Mapa pokrycia</h1>
        <a href="{{ route('panel.potential-parishes.index') }}" class="btn btn-secondary btn-sm">Parafie do obdzwonienia ↗</a>
    </div>

    {{-- Pasek statusów: te same statusy co w „Parafie do obdzwonienia”, z licznikami.
         Każdy link prowadzi do listy przefiltrowanej po danym statusie (obdzwanianie). --}}
    <div class="card card-static mb-3">
        <div class="card-body">
            <div class="text-muted mb-1" style="font-size:.8rem;font-weight:600">Statusy — kliknij, aby przejść do listy do obdzwonienia</div>
            <div class="d-flex gap-1" style="flex-wrap:wrap">
                <a href="{{ route('panel.potential-parishes.index') }}" class="btn btn-sm btn-primary">
                    Wszystkie ({{ number_format($total, 0, ',', ' ') }})
                </a>
                @foreach(\App\Modules\Storefront\Models\PotentialParish::STATUSES as $key => $label)
                    <a href="{{ route('panel.potential-parishes.index', ['status' => $key]) }}" class="btn btn-sm btn-secondary">
                        {{ $label }} ({{ number_format($statusCounts[$key] ?? 0, 0, ',', ' ') }})
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Licznik parafii per województwo (z bazy potential_parishes) — skrót do listy po woj. --}}
    <div class="card card-static mb-3">
        <div class="card-body">
            <div class="text-muted mb-1" style="font-size:.8rem;font-weight:600">Województwa — kliknij, aby przefiltrować listę</div>
            <div class="d-flex gap-1" style="flex-wrap:wrap;align-items:center">
                @foreach($byVoivodeship as $woj => $count)
                    <a href="{{ route('panel.potential-parishes.index', ['voivodeship' => $woj]) }}"
                       class="badge badge-muted" style="text-decoration:none">
                        {{ $woj ?: 'bez województwa' }}: <strong>{{ number_format($count, 0, ',', ' ') }}</strong>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Filtry mapy (TE SAME co na liście): nazwa, województwo, status, handlowiec.
         Filtrują wyświetlane markery przez re-fetch endpointu coverage.data. --}}
    <div class="parish-filters-bar">
        <form id="map-filters" class="d-flex gap-1" style="flex-wrap:wrap;align-items:flex-end" onsubmit="return false">
            <div>
                <label class="text-muted parish-flabel">Nazwa parafii</label>
                <input type="text" name="name" placeholder="Szukaj nazwy…" style="min-width:200px">
            </div>
            <div>
                <label class="text-muted parish-flabel">Województwo</label>
                <select name="voivodeship" style="min-width:180px">
                    <option value="">— wszystkie —</option>
                    @foreach(\App\Modules\Storefront\Models\Salesperson::VOIVODESHIPS as $woj)
                        <option value="{{ $woj }}">{{ $woj }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-muted parish-flabel">Status</label>
                <select name="status" style="min-width:160px">
                    <option value="">— wszystkie —</option>
                    @foreach(\App\Modules\Storefront\Models\PotentialParish::STATUSES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-muted parish-flabel">Handlowiec</label>
                <select name="salesperson_id" style="min-width:180px">
                    <option value="">— wszyscy —</option>
                    @foreach($salespeople as $sp)
                        <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="button" id="map-filter-apply" class="btn btn-primary btn-sm">Filtruj</button>
                <button type="button" id="map-filter-clear" class="btn btn-secondary btn-sm">Wyczyść</button>
                <span id="map-count" class="text-muted" style="margin-left:8px;font-size:.85rem"></span>
            </div>
        </form>
    </div>

    {{-- Kontener mapy interaktywnej --}}
    <div id="coverage-map" style="width:100%;height:74vh;border:0;border-radius:12px;overflow:hidden"></div>

    {{-- Toast „Zapisano” (auto-zapis z popupu) --}}
    <div id="parish-toast" class="parish-toast" role="status" aria-live="polite"></div>

    <style>
        .parish-filters-bar {
            position: sticky; top: 0; z-index: 1100;
            background: var(--card-bg, #fff);
            border: 1px solid var(--border, #e5e7eb);
            border-radius: 12px;
            padding: 12px 16px; margin-bottom: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,.04);
        }
        .parish-flabel { display:block; font-size:.78rem; margin-bottom:2px; }
        /* Popup z formularzem edycji parafii (te same pola co lista). */
        .parish-popup { min-width: 240px; }
        .parish-popup h4 { margin: 0 0 2px; font-size: .95rem; }
        .parish-popup .pp-meta { color:#667085; font-size:.78rem; margin-bottom:8px; }
        .parish-popup label { display:block; font-size:.72rem; color:#667085; margin:6px 0 2px; }
        .parish-popup select, .parish-popup input[type="text"] { width:100%; font-size:.85rem; }
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
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script>
        (function () {
            const csrf = @json(csrf_token());
            const dataUrl = @json(route('panel.coverage.data'));
            const statusBase = @json(route('panel.potential-parishes.index'));
            const statuses = @json(\App\Modules\Storefront\Models\PotentialParish::STATUSES);
            const salespeople = @json($salespeople->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values());
            // Bazowy URL do zapisu — :id podstawiamy po stronie klienta.
            const statusUrlTpl = @json(route('panel.potential-parishes.status', ['potentialParish' => '__ID__']));

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

            function esc(s) {
                return (s == null ? '' : String(s)).replace(/[&<>"']/g, c =>
                    ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
            }

            const map = L.map('coverage-map').setView([52.0, 19.3], 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            const cluster = L.markerClusterGroup({ chunkedLoading: true, maxClusterRadius: 50 });
            map.addLayer(cluster);

            const countEl = document.getElementById('map-count');

            // Buduje HTML popupu z formularzem edycji (te same pola co lista CRM).
            function popupHtml(p) {
                let statusOpts = '';
                for (const k in statuses) {
                    statusOpts += '<option value="' + k + '"' + (p.status === k ? ' selected' : '') + '>' + esc(statuses[k]) + '</option>';
                }
                let spOpts = '<option value="">— brak —</option>';
                salespeople.forEach(s => {
                    spOpts += '<option value="' + s.id + '"' + (p.salesperson_id === s.id ? ' selected' : '') + '>' + esc(s.name) + '</option>';
                });
                return '<div class="parish-popup" data-id="' + p.id + '">' +
                    '<h4>' + esc(p.name) + '</h4>' +
                    '<div class="pp-meta">' + esc(p.city || '—') + ' · ' + esc(p.voivodeship || '—') + '</div>' +
                    '<label>Status</label><select class="js-f-status">' + statusOpts + '</select>' +
                    '<label>Telefon</label><input type="text" class="js-f-phone" value="' + esc(p.phone || '') + '" placeholder="np. 12 345 67 89">' +
                    '<label>Handlowiec</label><select class="js-f-sp">' + spOpts + '</select>' +
                    '<label>Notatka</label><input type="text" class="js-f-note" value="' + esc(p.note || '') + '" placeholder="Notatka z rozmowy…">' +
                    '</div>';
            }

            function saveFromPopup(box) {
                const id = box.dataset.id;
                const url = statusUrlTpl.replace('__ID__', id);
                const payload = {
                    status:         box.querySelector('.js-f-status').value,
                    salesperson_id: box.querySelector('.js-f-sp').value || null,
                    note:           box.querySelector('.js-f-note').value,
                    phone:          box.querySelector('.js-f-phone').value,
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
                        // Odśwież kolor markera wg nowego statusu.
                        const marker = box._marker;
                        if (marker && data.status_colors) {
                            marker.setIcon(makeIcon(data.status_colors[0]));
                            marker._parish.status = payload.status;
                        }
                        showToast(data.message || 'Zapisano', false);
                    })
                    .catch(() => showToast('Nie udało się zapisać', true));
            }

            // Marker jako kolorowa kropka (CSS divIcon) — kolor = kolor tła statusu.
            function makeIcon(color) {
                return L.divIcon({
                    className: 'parish-dot',
                    html: '<span style="display:block;width:14px;height:14px;border-radius:50%;' +
                          'background:' + color + ';border:2px solid #fff;box-shadow:0 0 2px rgba(0,0,0,.5)"></span>',
                    iconSize: [14, 14],
                    iconAnchor: [7, 7],
                });
            }

            function buildParams() {
                const f = document.getElementById('map-filters');
                const params = new URLSearchParams();
                ['name', 'voivodeship', 'status', 'salesperson_id'].forEach(k => {
                    const v = f.querySelector('[name="' + k + '"]').value;
                    if (v) params.set(k, v);
                });
                return params;
            }

            function load() {
                countEl.textContent = 'Ładowanie…';
                const params = buildParams();
                fetch(dataUrl + (params.toString() ? '?' + params.toString() : ''), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                })
                    .then(r => r.json())
                    .then(data => {
                        cluster.clearLayers();
                        const markers = [];
                        data.points.forEach(p => {
                            const m = L.marker([p.lat, p.lon], { icon: makeIcon(p.color) });
                            m._parish = p;
                            m.bindPopup(() => popupHtml(p), { minWidth: 240 });
                            // Po otwarciu popupu podłącz auto-zapis na change/blur.
                            m.on('popupopen', function (e) {
                                const box = e.popup.getElement().querySelector('.parish-popup');
                                if (!box) return;
                                box._marker = m;
                                box.querySelectorAll('select').forEach(el =>
                                    el.addEventListener('change', () => saveFromPopup(box)));
                                box.querySelectorAll('input[type="text"]').forEach(el => {
                                    el.addEventListener('blur', () => saveFromPopup(box));
                                    el.addEventListener('keydown', ev => {
                                        if (ev.key === 'Enter') { ev.preventDefault(); el.blur(); }
                                    });
                                });
                            });
                            markers.push(m);
                        });
                        cluster.addLayers(markers);
                        countEl.textContent = 'Punktów: ' + Number(data.count).toLocaleString('pl-PL');
                    })
                    .catch(() => { countEl.textContent = ''; showToast('Nie udało się wczytać mapy', true); });
            }

            document.getElementById('map-filter-apply').addEventListener('click', load);
            document.getElementById('map-filter-clear').addEventListener('click', function () {
                const f = document.getElementById('map-filters');
                f.querySelectorAll('input, select').forEach(el => { el.value = ''; });
                load();
            });

            load();
        })();
    </script>
@endpush
