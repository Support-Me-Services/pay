import { useEffect, useState } from 'react'
import { Link, router } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

/** Panel „Parafie (tagi NFC)" — lista z filtrem statusu i wyszukiwarką. */
export default function Index({ items, tabs, q, indexUrl, createUrl }) {
    const [query, setQuery] = useState(q || '')
    useEffect(() => { setQuery(q || '') }, [q])

    // Aktywny status (z zakładek) — zachowujemy go przy wyszukiwaniu.
    const activeStatus = tabs.find((t) => t.active && t.key)?.key || null

    const search = (e) => {
        e.preventDefault()
        router.get(indexUrl, { ...(activeStatus ? { status: activeStatus } : {}), ...(query ? { q: query } : {}) }, { preserveScroll: true })
    }
    const clear = () => router.get(indexUrl, activeStatus ? { status: activeStatus } : {})

    return (
        <>
            <div className="panel-title">
                <h1>Parafie (tagi NFC)</h1>
                <Link href={createUrl} className="btn btn-primary btn-sm">+ Dodaj parafię</Link>
            </div>

            <div className="d-flex gap-1 mb-3" style={{ flexWrap: 'wrap' }}>
                {tabs.map((t) => (
                    <Link key={t.label} href={t.url} preserveScroll
                        className={`btn btn-sm ${t.active ? 'btn-primary' : 'btn-secondary'}`}>
                        {t.label} ({t.count})
                    </Link>
                ))}
            </div>

            <form onSubmit={search} className="d-flex gap-1 mb-3" style={{ flexWrap: 'wrap' }}>
                <input type="text" value={query} onChange={(e) => setQuery(e.target.value)}
                    placeholder="Szukaj: nazwa, miasto, województwo…" style={{ flex: 1, minWidth: 240, maxWidth: 420 }} />
                <button type="submit" className="btn btn-primary btn-sm">Szukaj</button>
                {q !== '' && <button type="button" onClick={clear} className="btn btn-secondary btn-sm">Wyczyść</button>}
            </form>

            <div className="card card-static"><div className="card-body"><div className="table-wrap">
                <table className="table">
                    <thead>
                        <tr>
                            <th>Nazwa</th><th>Miasto / województwo</th><th>Status</th>
                            <th>Tag NFC</th><th>Wpłaty</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {items.length === 0 && <tr><td colSpan={6} className="text-muted">Brak parafii spełniających kryteria.</td></tr>}
                        {items.map((p) => (
                            <tr key={p.id}>
                                <td className="fw-bold">{p.name}</td>
                                <td>
                                    {p.city || '—'}
                                    {p.voivodeship && <><br /><span className="text-muted" style={{ fontWeight: 400 }}>{p.voivodeship}</span></>}
                                </td>
                                <td><span className="badge" style={{ background: p.status_bg, color: p.status_fg, fontWeight: 600 }}>{p.status_label}</span></td>
                                <td><code>{p.tag_uid}</code></td>
                                <td>{p.orders_count}</td>
                                <td className="actions nowrap">
                                    <Link href={p.edit_url}>Edytuj</Link>{' '}
                                    <Link href={p.stats_url}>Statystyki</Link>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div></div></div>
        </>
    )
}

Index.layout = (page) => <PanelLayout title="Parafie">{page}</PanelLayout>
