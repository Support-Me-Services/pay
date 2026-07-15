import { useEffect } from 'react'
import { usePage } from '@inertiajs/react'
import GatewayPublicLayout from '@/Layouts/GatewayPublicLayout'

/**
 * Ekran przejściowy app2app (/pay/{uuid}) — ~1 s spinnera, potem redirect do
 * operatora (BLIK / aplikacja banku). Odwzorowuje payment/transition.blade.php.
 */
export default function Transition() {
    const { transaction, redirectUrl } = usePage().props

    useEffect(() => {
        const t = setTimeout(() => { window.location.href = redirectUrl }, 1100)
        return () => clearTimeout(t)
    }, [redirectUrl])

    return (
        <div className="fullscreen-status" style={{ background: '#fff', color: 'var(--ink)' }}>
            <div className="phone-pulse">
                <div style={{ position: 'absolute', inset: 0, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '3rem' }}>📱</div>
            </div>
            <h1 style={{ color: 'var(--ink)', fontSize: '1.6rem' }}>Przenosimy Cię do płatności...</h1>
            <p className="text-muted">Za chwilę otworzy się aplikacja Twojego banku.</p>
            <div className="spinner mt-2"></div>
            <p className="small text-muted mt-3">{transaction.productName} — <strong>{transaction.amountPln} zł</strong></p>
        </div>
    )
}

Transition.layout = (page) => <GatewayPublicLayout bare title="Przenosimy Cię do płatności...">{page}</GatewayPublicLayout>
