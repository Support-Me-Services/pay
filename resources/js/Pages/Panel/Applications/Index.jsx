import { Link, router } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

/** Panel „Aplikacje" — skrzynka zgłoszeń rekrutacyjnych (filtry po statusie/ofercie). */
export default function Index({ items, unread, tabs, filterPosition, clearFilterUrl }) {
    const destroy = (a) => {
        if (confirm('Usunąć zgłoszenie?')) router.delete(a.destroy_url, { preserveScroll: true })
    }

    return (
        <>
            <div className="panel-title">
                <h1>Aplikacje {unread > 0 && <span className="badge badge-brand">{unread} nowych</span>}</h1>
            </div>

            <div className="d-flex gap-1 mb-3" style={{ flexWrap: 'wrap' }}>
                {tabs.map((t) => (
                    <Link key={t.url} href={t.url} className={`btn btn-sm ${t.active ? 'btn-primary' : 'btn-secondary'}`}>
                        {t.label} ({t.count})
                    </Link>
                ))}
            </div>

            {filterPosition && (
                <div className="alert" style={{ background: '#f7efdc', border: '1px solid var(--line,#e2e8f0)' }}>
                    Filtr: oferta „{filterPosition.title}". <Link href={clearFilterUrl}>Pokaż wszystkie</Link>
                </div>
            )}

            <div className="card card-static"><div className="card-body"><div className="table-wrap">
                <table className="table">
                    <thead>
                        <tr><th>Data</th><th>Kandydat</th><th>Oferta</th><th>CV</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        {items.length === 0 && <tr><td colSpan={6} className="text-muted">Brak zgłoszeń.</td></tr>}
                        {items.map((a) => (
                            <tr key={a.id} style={a.is_read ? undefined : { fontWeight: 700 }}>
                                <td className="nowrap">{a.created_at}</td>
                                <td>{a.name}<br /><span className="text-muted" style={{ fontWeight: 400 }}>{a.email}</span></td>
                                <td>{a.position_title || 'aplikacja spontaniczna'}</td>
                                <td>{a.cv_url ? <a href={a.cv_url}>Pobierz</a> : <span className="text-muted">—</span>}</td>
                                <td>
                                    <span className="badge" style={{ background: a.status_bg, color: a.status_fg, fontWeight: 600 }}>{a.status_label}</span>
                                    {!a.is_read && <span className="badge badge-success" style={{ marginLeft: 4 }}>nowe</span>}
                                </td>
                                <td className="actions nowrap">
                                    <Link href={a.show_url}>Otwórz</Link>{' '}
                                    <a href="#" onClick={(e) => { e.preventDefault(); destroy(a) }}>Usuń</a>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div></div></div>
        </>
    )
}

Index.layout = (page) => <PanelLayout title="Aplikacje">{page}</PanelLayout>
