import StorefrontPublicLayout from '@/Layouts/StorefrontPublicLayout'

/** Tabliczka NFC nieprzypisana do parafii (404, tryb bare). */
export default function TagNotFound({ categoryUrl }) {
    return (
        <div className="status-screen plain">
            <div style={{ fontSize: '3.4rem', marginBottom: 10 }}>⛪</div>
            <h1 style={{ color: 'var(--navy)' }}>Ta tabliczka nie jest<br />przypisana do parafii</h1>
            <p className="muted">Zapytaj obsługę kościoła albo wybierz parafię z listy.</p>
            <div className="actions">
                <a href={categoryUrl} className="btn btn-gold">Zobacz parafie</a>
            </div>
        </div>
    )
}

TagNotFound.layout = (page) => <StorefrontPublicLayout bare title="Nieznana tabliczka">{page}</StorefrontPublicLayout>
