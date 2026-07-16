import { useEffect, useRef, useState } from 'react'
import { Head, usePage } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

const fmt = (n) => String(n ?? 0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ')

// Ładuje skrypt z CDN raz (idempotentnie) — Leaflet dotyka window, więc tylko klient.
function loadScript(src, integrity) {
    return new Promise((resolve, reject) => {
        const existing = document.querySelector(`script[src="${src}"]`)
        if (existing) {
            if (existing.dataset.loaded) resolve()
            else existing.addEventListener('load', () => resolve())
            return
        }
        const s = document.createElement('script')
        s.src = src
        if (integrity) { s.integrity = integrity; s.crossOrigin = '' }
        s.onload = () => { s.dataset.loaded = '1'; resolve() }
        s.onerror = reject
        document.head.appendChild(s)
    })
}

function esc(s) {
    return (s == null ? '' : String(s)).replace(/[&<>"']/g, (c) =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]))
}

/**
 * Panel „Mapa pokrycia" — interaktywny Leaflet + markercluster (20k parafii).
 * Biblioteki ładowane dynamicznie z CDN w useEffect (SSR-safe). Markery filtrowane
 * przez re-fetch endpointu coverage.data; popup edycji z auto-zapisem (status CRM).
 */
export default function Map({ byVoivodeship, total, statusCounts, statuses, voivodeships, salespeople, urls }) {
    const csrf = usePage().props.csrf_token
    const [filters, setFilters] = useState({ name: '', voivodeship: '', status: '', salesperson_id: '' })
    const [count, setCount] = useState('')
    const filtersRef = useRef(filters)
    filtersRef.current = filters
    const loadRef = useRef(null)
    const toastRef = useRef(null)
    const toastTimer = useRef(null)

    const showToast = (msg, isError) => {
        const el = toastRef.current
        if (!el) return
        el.textContent = msg
        el.classList.toggle('is-error', !!isError)
        el.classList.add('show')
        clearTimeout(toastTimer.current)
        toastTimer.current = setTimeout(() => el.classList.remove('show'), 2000)
    }

    useEffect(() => {
        let alive = true
        let map = null

        Promise.resolve()
            .then(() => loadScript('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo='))
            .then(() => loadScript('https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js'))
            .then(() => {
                if (!alive) return
                const L = window.L
                const token = csrf
                map = L.map('coverage-map').setView([52.0, 19.3], 6)
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map)
                const cluster = L.markerClusterGroup({ chunkedLoading: true, maxClusterRadius: 50 })
                map.addLayer(cluster)

                const statusMap = Object.fromEntries(statuses.map((s) => [s.value, s.label]))
                const statusUrlTpl = urls.status

                const makeIcon = (color) => L.divIcon({
                    className: 'parish-dot',
                    html: `<span style="display:block;width:14px;height:14px;border-radius:50%;background:${color};border:2px solid #fff;box-shadow:0 0 2px rgba(0,0,0,.5)"></span>`,
                    iconSize: [14, 14], iconAnchor: [7, 7],
                })

                const popupHtml = (p) => {
                    let statusOpts = ''
                    for (const k in statusMap) statusOpts += `<option value="${k}"${p.status === k ? ' selected' : ''}>${esc(statusMap[k])}</option>`
                    let spOpts = '<option value="">— brak —</option>'
                    salespeople.forEach((s) => { spOpts += `<option value="${s.id}"${p.salesperson_id === s.id ? ' selected' : ''}>${esc(s.name)}</option>` })
                    return `<div class="parish-popup" data-id="${p.id}"><h4>${esc(p.name)}</h4>` +
                        `<div class="pp-meta">${esc(p.city || '—')} · ${esc(p.voivodeship || '—')}</div>` +
                        `<label>Status</label><select class="js-f-status">${statusOpts}</select>` +
                        `<label>Telefon</label><input type="text" class="js-f-phone" value="${esc(p.phone || '')}" placeholder="np. 12 345 67 89">` +
                        `<label>Handlowiec</label><select class="js-f-sp">${spOpts}</select>` +
                        `<label>Notatka</label><input type="text" class="js-f-note" value="${esc(p.note || '')}" placeholder="Notatka z rozmowy…"></div>`
                }

                const saveFromPopup = (box) => {
                    const id = box.dataset.id
                    const phoneVal = box.querySelector('.js-f-phone').value.trim()
                    const spVal = box.querySelector('.js-f-sp').value
                    const noteVal = box.querySelector('.js-f-note').value
                    const payload = { status: box.querySelector('.js-f-status').value, salesperson_id: spVal || null, note: noteVal, phone: phoneVal }
                    fetch(statusUrlTpl.replace('__ID__', id), {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload),
                    })
                        .then((r) => (r.ok ? r.json() : r.json().then((j) => Promise.reject(j))))
                        .then((data) => {
                            const marker = box._marker
                            if (marker) {
                                const p = marker._parish
                                if (p) {
                                    p.status = payload.status
                                    p.phone = phoneVal !== '' ? phoneVal : null
                                    p.note = noteVal !== '' ? noteVal : null
                                    p.salesperson_id = spVal !== '' ? parseInt(spVal, 10) : null
                                }
                                if (data.status_colors) marker.setIcon(makeIcon(data.status_colors[0]))
                            }
                            showToast(data.message || 'Zapisano', false)
                        })
                        .catch(() => showToast('Nie udało się zapisać', true))
                }

                const load = () => {
                    setCount('Ładowanie…')
                    const f = filtersRef.current
                    const params = new URLSearchParams()
                    ;['name', 'voivodeship', 'status', 'salesperson_id'].forEach((k) => { if (f[k]) params.set(k, f[k]) })
                    fetch(urls.data + (params.toString() ? `?${params}` : ''), { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } })
                        .then((r) => r.json())
                        .then((data) => {
                            cluster.clearLayers()
                            const markers = data.points.map((p) => {
                                const m = L.marker([p.lat, p.lon], { icon: makeIcon(p.color) })
                                m._parish = p
                                m.bindPopup(() => popupHtml(p), { minWidth: 240 })
                                m.on('popupopen', (e) => {
                                    const box = e.popup.getElement().querySelector('.parish-popup')
                                    if (!box) return
                                    box._marker = m
                                    box.querySelectorAll('select').forEach((el) => el.addEventListener('change', () => saveFromPopup(box)))
                                    box.querySelectorAll('input[type="text"]').forEach((el) => {
                                        el.addEventListener('blur', () => saveFromPopup(box))
                                        el.addEventListener('keydown', (ev) => { if (ev.key === 'Enter') { ev.preventDefault(); el.blur() } })
                                    })
                                })
                                return m
                            })
                            cluster.addLayers(markers)
                            setCount(`Punktów: ${fmt(data.count)}`)
                        })
                        .catch(() => { setCount(''); showToast('Nie udało się wczytać mapy', true) })
                }
                loadRef.current = load
                load()
            })
            .catch(() => showToast('Nie udało się załadować mapy', true))

        return () => { alive = false; if (map) map.remove() }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [])

    const apply = () => loadRef.current && loadRef.current()
    const clear = () => {
        setFilters({ name: '', voivodeship: '', status: '', salesperson_id: '' })
        // filtersRef aktualizuje się przy renderze; wywołaj load po opróżnieniu.
        setTimeout(() => loadRef.current && loadRef.current(), 0)
    }

    return (
        <>
            <Head>
                <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossOrigin="" />
                <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
                <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
            </Head>

            <div className="panel-title">
                <h1>Mapa pokrycia</h1>
                <a href={urls.listBase} className="btn btn-secondary btn-sm">Parafie do obdzwonienia ↗</a>
            </div>

            <div className="card card-static mb-3"><div className="card-body">
                <div className="text-muted mb-1" style={{ fontSize: '.8rem', fontWeight: 600 }}>Statusy — kliknij, aby przejść do listy do obdzwonienia</div>
                <div className="d-flex gap-1" style={{ flexWrap: 'wrap' }}>
                    <a href={urls.listBase} className="btn btn-sm btn-primary">Wszystkie ({fmt(total)})</a>
                    {statuses.map((s) => (
                        <a key={s.value} href={`${urls.listBase}?status=${s.value}`} className="btn btn-sm btn-secondary">
                            {s.label} ({fmt(statusCounts[s.value] ?? 0)})
                        </a>
                    ))}
                </div>
            </div></div>

            <div className="card card-static mb-3"><div className="card-body">
                <div className="text-muted mb-1" style={{ fontSize: '.8rem', fontWeight: 600 }}>Województwa — kliknij, aby przefiltrować listę</div>
                <div className="d-flex gap-1" style={{ flexWrap: 'wrap', alignItems: 'center' }}>
                    {byVoivodeship.map((v) => (
                        <a key={v.voivodeship || 'brak'} href={`${urls.listBase}?voivodeship=${encodeURIComponent(v.voivodeship || '')}`}
                            className="badge badge-muted" style={{ textDecoration: 'none' }}>
                            {v.voivodeship || 'bez województwa'}: <strong>{fmt(v.count)}</strong>
                        </a>
                    ))}
                </div>
            </div></div>

            <div className="parish-filters-bar">
                <form className="d-flex gap-1" style={{ flexWrap: 'wrap', alignItems: 'flex-end' }} onSubmit={(e) => { e.preventDefault(); apply() }}>
                    <div>
                        <label className="text-muted parish-flabel">Nazwa parafii</label>
                        <input type="text" value={filters.name} onChange={(e) => setFilters({ ...filters, name: e.target.value })} placeholder="Szukaj nazwy…" style={{ minWidth: 200 }} />
                    </div>
                    <div>
                        <label className="text-muted parish-flabel">Województwo</label>
                        <select value={filters.voivodeship} onChange={(e) => setFilters({ ...filters, voivodeship: e.target.value })} style={{ minWidth: 180 }}>
                            <option value="">— wszystkie —</option>
                            {voivodeships.map((w) => <option key={w} value={w}>{w}</option>)}
                        </select>
                    </div>
                    <div>
                        <label className="text-muted parish-flabel">Status</label>
                        <select value={filters.status} onChange={(e) => setFilters({ ...filters, status: e.target.value })} style={{ minWidth: 160 }}>
                            <option value="">— wszystkie —</option>
                            {statuses.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                        </select>
                    </div>
                    <div>
                        <label className="text-muted parish-flabel">Handlowiec</label>
                        <select value={filters.salesperson_id} onChange={(e) => setFilters({ ...filters, salesperson_id: e.target.value })} style={{ minWidth: 180 }}>
                            <option value="">— wszyscy —</option>
                            {salespeople.map((sp) => <option key={sp.id} value={sp.id}>{sp.name}</option>)}
                        </select>
                    </div>
                    <div>
                        <button type="submit" className="btn btn-primary btn-sm">Filtruj</button>
                        <button type="button" onClick={clear} className="btn btn-secondary btn-sm">Wyczyść</button>
                        <span className="text-muted" style={{ marginLeft: 8, fontSize: '.85rem' }}>{count}</span>
                    </div>
                </form>
            </div>

            <div id="coverage-map" style={{ width: '100%', height: '74vh', border: 0, borderRadius: 12, overflow: 'hidden' }} />

            <div ref={toastRef} className="parish-toast" role="status" aria-live="polite" />

            <style>{`
                .parish-filters-bar { position: sticky; top: 0; z-index: 1100; background: var(--card-bg, #fff);
                    border: 1px solid var(--border, #e5e7eb); border-radius: 12px; padding: 12px 16px; margin-bottom: 14px; box-shadow: 0 2px 10px rgba(0,0,0,.04); }
                .parish-flabel { display:block; font-size:.78rem; margin-bottom:2px; }
                .parish-popup { min-width: 240px; }
                .parish-popup h4 { margin: 0 0 2px; font-size: .95rem; }
                .parish-popup .pp-meta { color:#667085; font-size:.78rem; margin-bottom:8px; }
                .parish-popup label { display:block; font-size:.72rem; color:#667085; margin:6px 0 2px; }
                .parish-popup select, .parish-popup input[type="text"] { width:100%; font-size:.85rem; }
                .parish-toast { position: fixed; right: 24px; bottom: 24px; z-index: 99999; background: var(--success, #00a36c); color: #fff;
                    padding: 12px 20px; border-radius: 10px; font-weight: 700; font-size: .9rem; box-shadow: 0 8px 24px rgba(0,0,0,.18);
                    opacity: 0; transform: translateY(12px); pointer-events: none; transition: opacity .22s ease, transform .22s ease; }
                .parish-toast.is-error { background: var(--error, #d90000); }
                .parish-toast.show { opacity: 1; transform: translateY(0); }
            `}</style>
        </>
    )
}

Map.layout = (page) => <PanelLayout title="Mapa pokrycia">{page}</PanelLayout>
