import { Link, router } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

/** Panel „Wiadomości" — lista zgłoszeń kontaktowych. */
export default function Index({ items, unread }) {
    const destroy = (m) => {
        if (confirm('Usunąć wiadomość?')) router.delete(m.destroy_url, { preserveScroll: true })
    }

    return (
        <>
            <div className="panel-title">
                <h1>Wiadomości {unread > 0 && <span className="badge badge-brand">{unread} nowych</span>}</h1>
            </div>

            <div className="card card-static"><div className="card-body"><div className="table-wrap">
                <table className="table">
                    <thead>
                        <tr><th>Data</th><th>Nadawca</th><th>Temat</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        {items.length === 0 && <tr><td colSpan={5} className="text-muted">Brak wiadomości.</td></tr>}
                        {items.map((m) => (
                            <tr key={m.id} style={m.is_read ? undefined : { fontWeight: 700 }}>
                                <td className="nowrap">{m.created_at}</td>
                                <td>{m.name}<br /><span className="text-muted" style={{ fontWeight: 400 }}>{m.email}</span></td>
                                <td>{m.subject || '—'}</td>
                                <td>{m.is_read ? <span className="badge badge-muted">przeczytane</span> : <span className="badge badge-success">nowe</span>}</td>
                                <td className="actions nowrap">
                                    <Link href={m.show_url}>Otwórz</Link>{' '}
                                    <a href="#" onClick={(e) => { e.preventDefault(); destroy(m) }}>Usuń</a>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div></div></div>
        </>
    )
}

Index.layout = (page) => <PanelLayout title="Wiadomości">{page}</PanelLayout>
