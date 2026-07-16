import { Link } from '@inertiajs/react'
import GatewayPanelLayout from '@/Layouts/GatewayPanelLayout'

/** Panel bramki — leady z Landing Page (lista + eksport CSV). */
export default function Leads({ leads, exportUrl }) {
    return (
        <>
            <div className="panel-title">
                <h1>Leady z Landing Page</h1>
                <a href={exportUrl} className="btn btn-primary btn-sm">Eksport CSV</a>
            </div>

            <div className="card card-static"><div className="card-body">
                <div className="table-wrap">
                    <table className="table">
                        <thead>
                            <tr><th>Data</th><th>Imię i nazwisko</th><th>E-mail</th><th>Telefon</th><th>Firma</th><th>Wiadomość</th></tr>
                        </thead>
                        <tbody>
                            {leads.data.length === 0 && <tr><td colSpan={6} className="text-muted">Brak zgłoszeń.</td></tr>}
                            {leads.data.map((l) => (
                                <tr key={l.id}>
                                    <td className="nowrap">{l.created_at}</td>
                                    <td className="fw-bold">{l.name}</td>
                                    <td><a href={`mailto:${l.email}`}>{l.email}</a></td>
                                    <td><a href={`tel:${l.phone}`}>{l.phone}</a></td>
                                    <td>{l.company || '—'}</td>
                                    <td style={{ maxWidth: 340 }}>{l.message}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {leads.last_page > 1 && (
                    <div className="mt-2 d-flex gap-1" style={{ alignItems: 'center', flexWrap: 'wrap' }}>
                        {leads.prev_page_url
                            ? <Link href={leads.prev_page_url} preserveScroll className="btn btn-secondary btn-sm">← Poprzednia</Link>
                            : <span className="btn btn-secondary btn-sm" style={{ opacity: 0.45, pointerEvents: 'none' }}>← Poprzednia</span>}
                        <span className="text-muted" style={{ fontSize: '.85rem' }}>Strona {leads.current_page} z {leads.last_page}</span>
                        {leads.next_page_url
                            ? <Link href={leads.next_page_url} preserveScroll className="btn btn-secondary btn-sm">Następna →</Link>
                            : <span className="btn btn-secondary btn-sm" style={{ opacity: 0.45, pointerEvents: 'none' }}>Następna →</span>}
                    </div>
                )}
            </div></div>
        </>
    )
}

Leads.layout = (page) => <GatewayPanelLayout title="Leady">{page}</GatewayPanelLayout>
