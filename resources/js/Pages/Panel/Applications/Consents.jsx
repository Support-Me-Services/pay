import { Link } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

/**
 * Panel „Baza kandydatów" — osoby z AKTYWNĄ zgodą na przyszłe rekrutacje
 * (ważną w okresie {consentMonths} miesięcy). Dane kontaktowe pod ręką:
 * e-mail (mailto), telefon (tel) i CV — tak jak w skrzynce „Aplikacje".
 */
export default function Consents({ items, consentMonths, indexUrl }) {
    return (
        <>
            <div className="panel-title">
                <h1>Baza kandydatów {items.length > 0 && <span className="badge badge-brand">{items.length}</span>}</h1>
                <Link href={indexUrl} className="btn btn-secondary btn-sm">← Aplikacje</Link>
            </div>

            <div className="alert" style={{ background: '#f7efdc', border: '1px solid var(--line,#e2e8f0)' }}>
                Osoby, które wyraziły zgodę na przetwarzanie danych na potrzeby <strong>przyszłych procesów rekrutacyjnych</strong>.
                Zgoda jest ważna {consentMonths} miesięcy od dnia jej udzielenia — poniżej wyłącznie zgody aktywne.
            </div>

            <div className="card card-static"><div className="card-body"><div className="table-wrap">
                <table className="table">
                    <thead>
                        <tr><th>Data zgody</th><th>Ważna do</th><th>Kandydat</th><th>Telefon</th><th>CV</th><th></th></tr>
                    </thead>
                    <tbody>
                        {items.length === 0 && <tr><td colSpan={6} className="text-muted">Brak kandydatów z aktywną zgodą.</td></tr>}
                        {items.map((a) => (
                            <tr key={a.id}>
                                <td className="nowrap">{a.future_consent_at}</td>
                                <td className="nowrap">{a.future_consent_until}</td>
                                <td>
                                    {a.name}<br />
                                    <a href={`mailto:${a.email}`} style={{ fontWeight: 400 }}>{a.email}</a>
                                </td>
                                <td className="nowrap">{a.phone ? <a href={`tel:${a.phone}`}>{a.phone}</a> : <span className="text-muted">—</span>}</td>
                                <td>{a.cv_url ? <a href={a.cv_url}>Pobierz</a> : <span className="text-muted">—</span>}</td>
                                <td className="actions nowrap">
                                    <a href={`mailto:${a.email}`}>Napisz</a>{' '}
                                    <Link href={a.show_url}>Otwórz</Link>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div></div></div>
        </>
    )
}

Consents.layout = (page) => <PanelLayout title="Baza kandydatów">{page}</PanelLayout>
