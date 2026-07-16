import { usePage } from '@inertiajs/react'
import GatewayPublicLayout from '@/Layouts/GatewayPublicLayout'

/**
 * Ekran błędu płatności (/pay/{uuid} gdy operator odrzuci utworzenie zamówienia).
 * Odwzorowuje payment/error.blade.php. Serwer zwraca ten ekran ze statusem 502.
 */
export default function Error() {
    const { transaction } = usePage().props

    return (
        <div className="fullscreen-status failure">
            <svg className="cross-svg" viewBox="0 0 56 56">
                <circle className="circle" cx="28" cy="28" r="26.5" />
                <line className="line" x1="18" y1="18" x2="38" y2="38" />
                <line className="line" x1="38" y1="18" x2="18" y2="38" />
            </svg>
            <h1>Nie udało się rozpocząć płatności</h1>
            <p className="sub">Spróbuj ponownie za chwilę.</p>
            <a href={transaction.returnUrl} className="btn btn-secondary mt-3" style={{ background: '#fff', borderColor: '#fff' }}>Wróć do sklepu</a>
            <div className="order-no">Transakcja: {transaction.id}</div>
        </div>
    )
}

Error.layout = (page) => <GatewayPublicLayout bare title="Błąd płatności">{page}</GatewayPublicLayout>
