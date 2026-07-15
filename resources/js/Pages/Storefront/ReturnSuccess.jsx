import StorefrontPublicLayout from '@/Layouts/StorefrontPublicLayout'

/** Ekran po opłaceniu tacy — „Bóg zapłać!" (tryb bare). */
export default function ReturnSuccess({ amount, productName, productCity, orderId, categoryUrl }) {
    return (
        <div className="status-screen success">
            <div className="candle" aria-hidden="true">
                <span className="flame"></span>
                <span className="stick"></span>
            </div>

            <svg className="mark-svg" viewBox="0 0 56 56" aria-hidden="true">
                <circle className="ring" cx="28" cy="28" r="26.5" />
                <path className="tick" d="M16 29 l8 8 l16 -17" />
            </svg>

            <h1>Bóg zapłać!</h1>
            <p className="sub">Twoja taca została złożona.</p>

            <div className="amount-badge">
                <span className="lbl">Wpłacono</span>
                <span className="val">{amount} zł</span>
                <span className="for">na rzecz: {productName}{productCity ? `, ${productCity}` : ''}</span>
            </div>

            <div className="actions">
                <a href={categoryUrl} className="btn btn-gold">Wesprzyj kolejną parafię</a>
            </div>

            <div className="order-no">Potwierdzenie nr {orderId}</div>
        </div>
    )
}

ReturnSuccess.layout = (page) => <StorefrontPublicLayout bare title="Dziękujemy za wsparcie">{page}</StorefrontPublicLayout>
