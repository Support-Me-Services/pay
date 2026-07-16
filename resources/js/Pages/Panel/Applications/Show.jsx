import { Link, router } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

/** Panel „Aplikacje" — szczegóły zgłoszenia + zmiana statusu + CV. */
export default function Show({ application: a, statusOptions, indexUrl }) {
    const setStatus = (status) => router.post(a.status_url, { status }, { preserveScroll: true })
    const destroy = () => { if (confirm('Usunąć zgłoszenie?')) router.delete(a.destroy_url) }
    const replyHref = `mailto:${a.email}?subject=${encodeURIComponent('Re: aplikacja' + (a.position_title ? ' — ' + a.position_title : ''))}`

    return (
        <>
            <div className="panel-title">
                <h1>Zgłoszenie rekrutacyjne</h1>
                <Link href={indexUrl} className="btn btn-secondary btn-sm">← Wróć do listy</Link>
            </div>

            <div className="card card-static mb-3" style={{ maxWidth: 760 }}><div className="card-body">
                <table className="table">
                    <tbody>
                        <tr><th style={{ width: 160 }}>Data</th><td>{a.created_at}</td></tr>
                        <tr><th>Imię i nazwisko</th><td>{a.name}</td></tr>
                        <tr><th>E-mail</th><td><a href={`mailto:${a.email}`}>{a.email}</a></td></tr>
                        <tr><th>Telefon</th><td>{a.phone || '—'}</td></tr>
                        <tr><th>Oferta</th><td>{a.position_title || 'aplikacja spontaniczna'}</td></tr>
                        <tr><th>CV</th><td>{a.cv_url ? <a href={a.cv_url}>{a.cv_name || 'Pobierz CV'}</a> : <span className="text-muted">brak załączonego CV</span>}</td></tr>
                        <tr>
                            <th>Przyszłe rekrutacje</th>
                            <td>
                                {a.future_consent ? (
                                    a.future_consent_active
                                        ? <>Zgoda aktywna <span className="text-muted">(udzielona {a.future_consent_at}, ważna do {a.future_consent_until})</span></>
                                        : <span className="text-muted">Zgoda wygasła (udzielona {a.future_consent_at}, ważna do {a.future_consent_until})</span>
                                ) : <span className="text-muted">brak zgody</span>}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div className="form-group" style={{ marginTop: 18 }}>
                    <label>List motywacyjny</label>
                    <div style={{ whiteSpace: 'pre-wrap', background: '#fff', border: '1px solid var(--line, #e2e8f0)', borderRadius: 'var(--radius, 10px)', padding: 14 }}>
                        {a.message || '—'}
                    </div>
                </div>

                <div style={{ margin: '18px 0', padding: 16, background: '#faf8f3', border: '1px solid var(--line,#e2e8f0)', borderRadius: 'var(--radius,10px)' }}>
                    <label style={{ display: 'block', fontWeight: 600, marginBottom: 8 }}>
                        Status zgłoszenia:
                        <span className="badge" style={{ background: a.status_bg, color: a.status_fg, fontWeight: 600, marginLeft: 4 }}>{a.status_label}</span>
                    </label>
                    <div className="d-flex gap-1" style={{ flexWrap: 'wrap' }}>
                        {statusOptions.map((o) => (
                            <button
                                key={o.value}
                                type="button"
                                className={`btn btn-sm ${a.status === o.value ? 'btn-primary' : 'btn-secondary'}`}
                                disabled={a.status === o.value}
                                onClick={() => setStatus(o.value)}
                            >
                                {o.label}
                            </button>
                        ))}
                    </div>
                </div>

                <div className="d-flex gap-1">
                    <a href={replyHref} className="btn btn-primary">Odpowiedz e-mailem</a>
                    {a.cv_url && <a href={a.cv_url} className="btn btn-secondary">Pobierz CV</a>}
                    <button type="button" className="btn btn-danger" onClick={destroy}>Usuń</button>
                </div>
            </div></div>
        </>
    )
}

Show.layout = (page) => <PanelLayout title="Zgłoszenie">{page}</PanelLayout>
