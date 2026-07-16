import { Link } from '@inertiajs/react'
import GatewayPanelLayout from '@/Layouts/GatewayPanelLayout'

/** Panel bramki — lista sklepów (z metrykami). Klucz API nowego sklepu jednorazowo. */
export default function Index({ shops, createUrl, newApiKey }) {
    return (
        <>
            <div className="panel-title">
                <h1>Sklepy</h1>
                <Link href={createUrl} className="btn btn-primary btn-sm">+ Dodaj sklep</Link>
            </div>

            {newApiKey && (
                <div className="alert alert-warning">
                    <strong>Klucz API nowego sklepu</strong> (zapisz teraz — nie zostanie ponownie wyświetlony):<br />
                    <code style={{ wordBreak: 'break-all', fontSize: '.95rem' }}>{newApiKey}</code>
                </div>
            )}

            <div className="card card-static"><div className="card-body"><div className="table-wrap">
                <table className="table">
                    <thead>
                        <tr><th>Nazwa</th><th>URL</th><th>Tryb płatności</th><th>Tagi</th><th>Przychód</th><th>Konwersja</th><th></th></tr>
                    </thead>
                    <tbody>
                        {shops.length === 0 && <tr><td colSpan={7} className="text-muted">Brak sklepów.</td></tr>}
                        {shops.map((s) => (
                            <tr key={s.id}>
                                <td className="fw-bold">{s.name}</td>
                                <td><a href={s.base_url} target="_blank" rel="noreferrer">{s.base_url}</a></td>
                                <td><span className={`badge ${s.payment_mode === 'app2app' ? 'badge-brand' : 'badge-muted'}`}>{s.payment_mode}</span></td>
                                <td>{s.tags_count}</td>
                                <td className="fw-bold">{s.revenue}</td>
                                <td>{s.conversion}%</td>
                                <td className="actions nowrap">
                                    <Link href={s.tags_url}>Tagi</Link>{' '}
                                    <Link href={s.edit_url}>Edytuj</Link>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div></div></div>
        </>
    )
}

Index.layout = (page) => <GatewayPanelLayout title="Sklepy">{page}</GatewayPanelLayout>
