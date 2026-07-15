import { useEffect, useRef, useState } from 'react'
import { Link, router, usePage } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

// number_format(x, 0, ',', ' ') — spacja jako separator tysięcy.
const fmt = (n) => String(n ?? 0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ')

/**
 * Panel „Parafie do obdzwonienia" (CRM). Filtry przez GET (server-side),
 * edycja INLINE w komórkach z auto-zapisem (fetch do endpointu status,
 * bez przeładowania). Paginacja zachowuje filtry (withQueryString w kontrolerze).
 */
export default function Index({
    parishes, statusTabs, statuses, statusColors, voivodeships,
    salespeople, filters, indexUrl, coverageMapUrl,
}) {
    const csrf = usePage().props.csrf_token

    // Lokalny stan pól per wiersz (id -> {status, phone, salesperson_id, note, ...}).
    const buildRows = () => Object.fromEntries(parishes.data.map((p) => [p.id, { ...p }]))
    const [rows, setRows] = useState(buildRows)
    // Po zmianie strony/filtrów Inertia podmienia propsy — resetujemy stan wierszy.
    useEffect(() => { setRows(buildRows()) }, [parishes]) // eslint-disable-line react-hooks/exhaustive-deps

    // Stan filtrów (kontrolowany) — wysyłany GET-em przez router.
    const [f, setF] = useState(filters)
    useEffect(() => { setF(filters) }, [filters])

    // Toast „Zapisano".
    const [toast, setToast] = useState(null)
    const toastTimer = useRef(null)
    const showToast = (msg, isError) => {
        setToast({ msg, isError })
        clearTimeout(toastTimer.current)
        toastTimer.current = setTimeout(() => setToast(null), 2000)
    }

    const setField = (id, key, value) =>
        setRows((r) => ({ ...r, [id]: { ...r[id], [key]: value } }))

    const save = (id) => {
        const row = rows[id]
        fetch(row.status_url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                status: row.status,
                salesperson_id: row.salesperson_id || null,
                note: row.note ?? '',
                phone: row.phone ?? '',
            }),
        })
            .then((r) => (r.ok ? r.json() : r.json().then((j) => Promise.reject(j))))
            .then((data) => {
                const colors = data.status_colors || statusColors[row.status]
                setField(id, 'status_bg', colors[0])
                setField(id, 'status_fg', colors[1])
                if ('called_at' in data) setField(id, 'called_at', data.called_at || '')
                showToast(data.message || 'Zapisano', false)
            })
            .catch(() => showToast('Nie udało się zapisać', true))
    }

    const applyFilters = (e) => {
        e?.preventDefault()
        router.get(indexUrl, { ...f }, { preserveScroll: true, preserveState: true })
    }
    const clearFilters = () => router.get(indexUrl)

    return (
        <>
            <div className="panel-title">
                <h1>Parafie do obdzwonienia</h1>
                <a href={coverageMapUrl} className="btn btn-secondary btn-sm">Mapa pokrycia ↗</a>
            </div>

            {/* Liczniki per status (klikalne jako filtr) */}
            <div className="d-flex gap-1 mb-3" style={{ flexWrap: 'wrap' }}>
                {statusTabs.map((t) => (
                    <Link key={t.label} href={t.url} preserveScroll
                        className={`btn btn-sm ${t.active ? 'btn-primary' : 'btn-secondary'}`}>
                        {t.label} ({fmt(t.count)})
                    </Link>
                ))}
            </div>

            {/* Pasek filtrów przyklejony u góry */}
            <div className="parish-filters-bar">
                <form onSubmit={applyFilters} className="d-flex gap-1" style={{ flexWrap: 'wrap', alignItems: 'flex-end' }}>
                    <div>
                        <label className="text-muted parish-flabel">Nazwa parafii</label>
                        <input type="text" value={f.name || ''} onChange={(e) => setF({ ...f, name: e.target.value })}
                            placeholder="Szukaj nazwy…" style={{ minWidth: 200 }} />
                    </div>
                    <div>
                        <label className="text-muted parish-flabel">Miasto</label>
                        <input type="text" value={f.city || ''} onChange={(e) => setF({ ...f, city: e.target.value })}
                            placeholder="Szukaj miasta…" style={{ minWidth: 160 }} />
                    </div>
                    <div>
                        <label className="text-muted parish-flabel">Województwo</label>
                        <select value={f.voivodeship || ''} onChange={(e) => setF({ ...f, voivodeship: e.target.value })} style={{ minWidth: 180 }}>
                            <option value="">— wszystkie —</option>
                            {voivodeships.map((w) => <option key={w} value={w}>{w}</option>)}
                        </select>
                    </div>
                    <div>
                        <label className="text-muted parish-flabel">Status</label>
                        <select value={f.status || ''} onChange={(e) => setF({ ...f, status: e.target.value })} style={{ minWidth: 160 }}>
                            <option value="">— wszystkie —</option>
                            {statuses.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                        </select>
                    </div>
                    <div>
                        <label className="text-muted parish-flabel">Handlowiec</label>
                        <select value={f.salesperson_id || ''} onChange={(e) => setF({ ...f, salesperson_id: e.target.value })} style={{ minWidth: 180 }}>
                            <option value="">— wszyscy —</option>
                            {salespeople.map((sp) => <option key={sp.id} value={sp.id}>{sp.name}</option>)}
                        </select>
                    </div>
                    <div>
                        <label className="text-muted parish-flabel">Numer telefonu</label>
                        <select value={f.has_phone || 'with'} onChange={(e) => setF({ ...f, has_phone: e.target.value })} style={{ minWidth: 150 }}>
                            <option value="with">Z telefonem</option>
                            <option value="without">Bez telefonu</option>
                            <option value="all">Wszystkie</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" className="btn btn-primary btn-sm">Filtruj</button>
                        <button type="button" onClick={clearFilters} className="btn btn-secondary btn-sm">Wyczyść</button>
                    </div>
                </form>
            </div>

            <div className="card card-static"><div className="card-body">
                <div className="table-wrap">
                    <table className="table parish-table">
                        <thead>
                            <tr>
                                <th>Nazwa</th><th>Miasto</th><th>Woj.</th>
                                <th>Status</th><th>Telefon</th><th>Handlowiec</th><th>Notatka</th>
                            </tr>
                        </thead>
                        <tbody>
                            {parishes.data.length === 0 && (
                                <tr><td colSpan={7} className="text-muted">Brak parafii spełniających kryteria.</td></tr>
                            )}
                            {parishes.data.map((p) => {
                                const row = rows[p.id] || p
                                return (
                                    <tr key={p.id}>
                                        <td className="fw-bold">
                                            <a href={p.gm_url} target="_blank" rel="noopener" title="Znajdź w Google Maps"
                                                style={{ color: 'inherit', textDecoration: 'none' }}>
                                                {p.name} <span style={{ opacity: 0.55, fontWeight: 400 }} aria-hidden="true">↗</span>
                                            </a>
                                        </td>
                                        <td>{p.city ? <a href={p.gm_url} target="_blank" rel="noopener" style={{ color: 'inherit' }}>{p.city}</a> : '—'}</td>
                                        <td>{p.voivodeship || '—'}</td>
                                        <td>
                                            <select value={row.status} onChange={(e) => { setField(p.id, 'status', e.target.value); }}
                                                onBlur={() => save(p.id)}
                                                style={{ minWidth: 150, background: row.status_bg, color: row.status_fg, fontWeight: 600 }}>
                                                {statuses.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                                            </select>
                                            <div className="text-muted" style={{ fontSize: '.72rem', marginTop: 2 }}>{row.called_at || ''}</div>
                                        </td>
                                        <td>
                                            <input type="text" value={row.phone || ''} placeholder="np. 12 345 67 89" style={{ minWidth: 130 }}
                                                onChange={(e) => setField(p.id, 'phone', e.target.value)}
                                                onBlur={() => save(p.id)}
                                                onKeyDown={(e) => { if (e.key === 'Enter') { e.preventDefault(); e.target.blur() } }} />
                                        </td>
                                        <td>
                                            <select value={row.salesperson_id || ''} style={{ minWidth: 160 }}
                                                onChange={(e) => setField(p.id, 'salesperson_id', e.target.value)}
                                                onBlur={() => save(p.id)}>
                                                <option value="">— brak —</option>
                                                {salespeople.map((sp) => <option key={sp.id} value={sp.id}>{sp.name}</option>)}
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" value={row.note || ''} placeholder="Notatka z rozmowy…" style={{ minWidth: 200, width: '100%' }}
                                                onChange={(e) => setField(p.id, 'note', e.target.value)}
                                                onBlur={() => save(p.id)}
                                                onKeyDown={(e) => { if (e.key === 'Enter') { e.preventDefault(); e.target.blur() } }} />
                                        </td>
                                    </tr>
                                )
                            })}
                        </tbody>
                    </table>
                </div>

                {parishes.last_page > 1 && (
                    <div className="parish-pagination">
                        {parishes.prev_page_url
                            ? <Link href={parishes.prev_page_url} preserveScroll className="btn btn-secondary btn-sm" rel="prev">← Poprzednia</Link>
                            : <span className="btn btn-secondary btn-sm is-disabled">← Poprzednia</span>}
                        <span className="parish-pagination-info text-muted">
                            Strona {parishes.current_page} z {parishes.last_page} ({fmt(parishes.total)} parafii)
                        </span>
                        {parishes.next_page_url
                            ? <Link href={parishes.next_page_url} preserveScroll className="btn btn-secondary btn-sm" rel="next">Następna →</Link>
                            : <span className="btn btn-secondary btn-sm is-disabled">Następna →</span>}
                    </div>
                )}
            </div></div>

            <div className={`parish-toast${toast ? ' show' : ''}${toast?.isError ? ' is-error' : ''}`} role="status" aria-live="polite">
                {toast?.msg}
            </div>

            <style>{`
                .parish-filters-bar { position: sticky; top: 0; z-index: 50; background: var(--card-bg, #fff);
                    border: 1px solid var(--border, #e5e7eb); border-radius: 12px; padding: 12px 16px;
                    margin-bottom: 14px; box-shadow: 0 2px 10px rgba(0,0,0,.04); }
                .parish-flabel { display:block; font-size:.78rem; margin-bottom:2px; }
                .parish-table td { vertical-align: top; }
                .parish-table select, .parish-table input[type="text"] { font-size:.85rem; }
                .parish-pagination { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-top:14px; }
                .parish-pagination .is-disabled { opacity:.45; pointer-events:none; cursor:default; }
                .parish-pagination-info { font-size:.85rem; }
                .parish-toast { position: fixed; right: 24px; bottom: 24px; z-index: 99999;
                    background: var(--success, #00a36c); color: #fff; padding: 12px 20px; border-radius: 10px;
                    font-weight: 700; font-size: .9rem; box-shadow: 0 8px 24px rgba(0,0,0,.18);
                    opacity: 0; transform: translateY(12px); pointer-events: none;
                    transition: opacity .22s ease, transform .22s ease; }
                .parish-toast.is-error { background: var(--error, #d90000); }
                .parish-toast.show { opacity: 1; transform: translateY(0); }
            `}</style>
        </>
    )
}

Index.layout = (page) => <PanelLayout title="Parafie do obdzwonienia">{page}</PanelLayout>
