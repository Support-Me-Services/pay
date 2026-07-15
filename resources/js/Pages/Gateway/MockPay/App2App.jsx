import { useEffect, useRef, useState } from 'react'
import { usePage } from '@inertiajs/react'
import GatewayPublicLayout from '@/Layouts/GatewayPublicLayout'

/**
 * Symulator aplikacji banku (app2app) — MockProvider (dev/demo).
 * Odwzorowuje mockpay/app2app.blade.php: sukces automatycznie po 3 s,
 * przycisk „Symuluj odmowę". confirm/fail → JSON redirect (pay.return).
 */
export default function MockApp2App() {
    const { transaction, urls, csrf_token } = usePage().props
    const declinedRef = useRef(false)

    const settle = (url) => {
        fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf_token, Accept: 'application/json' } })
            .then((r) => r.json())
            .then((d) => { window.location.href = d.redirect })
            .catch(() => { window.location.reload() })
    }

    useEffect(() => {
        const timer = setTimeout(() => { if (!declinedRef.current) settle(urls.confirm) }, 3000)
        return () => clearTimeout(timer)
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [])

    const fail = () => {
        declinedRef.current = true
        settle(urls.fail)
    }

    return (
        <div className="fullscreen-status" style={{ background: '#fff', color: 'var(--ink)' }}>
            <div className="phone-pulse">
                <div style={{ position: 'absolute', inset: 0, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '3rem' }}>🏦</div>
            </div>
            <h1 style={{ color: 'var(--ink)', fontSize: '1.6rem' }}>Otwieram aplikację banku...</h1>
            <p className="text-muted">Potwierdź płatność <strong style={{ color: 'var(--ink)' }}>{transaction.amountPln} zł</strong> w swojej aplikacji.</p>
            <div className="spinner mt-2"></div>
            <p className="small text-muted mt-3">{transaction.productName}</p>

            <button type="button" className="btn btn-secondary btn-sm mt-4" onClick={fail}>Symuluj odmowę</button>
        </div>
    )
}

MockApp2App.layout = (page) => <GatewayPublicLayout bare title="Otwieram aplikację banku...">{page}</GatewayPublicLayout>
