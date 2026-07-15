import { Link } from '@inertiajs/react'
import GatewayPanelLayout from '@/Layouts/GatewayPanelLayout'

/** Panel bramki — lista tagów sklepu (z metrykami). */
export default function Index({ shop, tags, createUrl }) {
    return (
        <>
            <div className="panel-title">
                <h1>Tagi: {shop.name}</h1>
                <Link href={createUrl} className="btn btn-primary btn-sm">+ Dodaj tag</Link>
            </div>

            <div className="card card-static"><div className="card-body"><div className="table-wrap">
                <table className="table">
                    <thead>
                        <tr><th>UID taga</th><th>Etykieta</th><th>Prowadzi do</th><th>Otwarcia</th><th>Zakupy</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        {tags.length === 0 && <tr><td colSpan={7} className="text-muted">Brak tagów w tym sklepie.</td></tr>}
                        {tags.map((t) => (
                            <tr key={t.id}>
                                <td className="fw-bold"><code>{t.tag_uid}</code></td>
                                <td>{t.label || '—'}</td>
                                <td><a href={t.target_url} target="_blank" rel="noreferrer">{t.target_url}</a></td>
                                <td>{t.opens}</td>
                                <td>{t.paid}</td>
                                <td>
                                    {t.active
                                        ? <span className="badge badge-success">aktywny</span>
                                        : <span className="badge badge-muted">nieaktywny</span>}
                                </td>
                                <td className="actions nowrap">
                                    <Link href={t.edit_url}>Edytuj</Link>{' '}
                                    <Link href={t.stats_url}>Statystyki</Link>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div></div></div>
        </>
    )
}

Index.layout = (page) => <GatewayPanelLayout title="Tagi">{page}</GatewayPanelLayout>
