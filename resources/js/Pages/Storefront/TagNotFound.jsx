import StorefrontPublicLayout from '@/Layouts/StorefrontPublicLayout'

/** Tabliczka NFC nierozpoznana (404, tryb bare). */
export default function TagNotFound({ categoryUrl }) {
    return (
        <div className="status-screen plain">
            <div style={{ fontSize: '3.4rem', marginBottom: 10 }}>❔</div>
            <h1 style={{ color: 'var(--navy)' }}>Ta tabliczka nie jest<br />jeszcze aktywna</h1>
            <p className="muted">Zapytaj obsługę na miejscu albo wybierz z listy.</p>
            <div className="actions">
                <a href={categoryUrl} className="btn btn-gold">Zobacz listę</a>
            </div>
        </div>
    )
}

TagNotFound.layout = (page) => <StorefrontPublicLayout bare title="Nieznana tabliczka">{page}</StorefrontPublicLayout>
