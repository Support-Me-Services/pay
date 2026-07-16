import StorefrontPublicLayout from '@/Layouts/StorefrontPublicLayout'

/** Ekran nieudanej wpłaty (tryb bare). */
export default function ReturnFailure({ orderId, retryUrl, categoryUrl }) {
    return (
        <div className="status-screen failure">
            <svg className="mark-svg" viewBox="0 0 56 56" aria-hidden="true">
                <circle className="ring" cx="28" cy="28" r="26.5" />
                <line className="x" x1="19" y1="19" x2="37" y2="37" />
                <line className="x" x1="37" y1="19" x2="19" y2="37" />
            </svg>

            <h1>Wpłata się nie udała</h1>
            <p className="sub">Nie pobraliśmy żadnych środków.</p>

            <div className="actions">
                <a href={retryUrl} className="btn btn-gold">Spróbuj ponownie</a>
                <a href={categoryUrl} className="btn btn-ghost" style={{ borderColor: '#e0d4b0', color: '#f1e8d2' }}>Inna parafia</a>
            </div>

            <div className="order-no">Potwierdzenie nr {orderId}</div>
        </div>
    )
}

ReturnFailure.layout = (page) => <StorefrontPublicLayout bare title="Wpłata nie powiodła się">{page}</StorefrontPublicLayout>
