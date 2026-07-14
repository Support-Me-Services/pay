import { Link, router } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

/** Panel „Wiadomości" — szczegóły wiadomości. */
export default function Show({ message, indexUrl }) {
    const destroy = () => {
        if (confirm('Usunąć wiadomość?')) router.delete(message.destroy_url)
    }
    const replyHref = `mailto:${message.email}?subject=${encodeURIComponent('Re: ' + (message.subject || 'Twoja wiadomość'))}`

    return (
        <>
            <div className="panel-title">
                <h1>Wiadomość</h1>
                <Link href={indexUrl} className="btn btn-secondary btn-sm">← Wróć do listy</Link>
            </div>

            <div className="card card-static mb-3" style={{ maxWidth: 760 }}><div className="card-body">
                <table className="table">
                    <tbody>
                        <tr><th style={{ width: 160 }}>Data</th><td>{message.created_at}</td></tr>
                        <tr><th>Imię i nazwisko</th><td>{message.name}</td></tr>
                        <tr><th>E-mail</th><td><a href={`mailto:${message.email}`}>{message.email}</a></td></tr>
                        <tr><th>Telefon</th><td>{message.phone || '—'}</td></tr>
                        <tr><th>Temat</th><td>{message.subject || '—'}</td></tr>
                    </tbody>
                </table>

                <div className="form-group" style={{ marginTop: 18 }}>
                    <label>Wiadomość</label>
                    <div style={{ whiteSpace: 'pre-wrap', background: '#fff', border: '1px solid var(--line, #e2e8f0)', borderRadius: 'var(--radius, 10px)', padding: 14 }}>
                        {message.message}
                    </div>
                </div>

                <div className="d-flex gap-1">
                    <a href={replyHref} className="btn btn-primary">Odpowiedz e-mailem</a>
                    <button type="button" className="btn btn-danger" onClick={destroy}>Usuń</button>
                </div>
            </div></div>
        </>
    )
}

Show.layout = (page) => <PanelLayout title="Wiadomość">{page}</PanelLayout>
