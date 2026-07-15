import { useEffect } from 'react'
import StorefrontPublicLayout from '@/Layouts/StorefrontPublicLayout'

/** Ekran oczekiwania na potwierdzenie wpłaty — polling statusu (tryb bare). */
export default function ReturnPending({ orderId, statusUrl }) {
    useEffect(() => {
        // Webhook PayU może dotrzeć kilka sekund po powrocie darczyńcy.
        let attempts = 0
        const poll = setInterval(() => {
            attempts++
            fetch(statusUrl, { headers: { Accept: 'application/json' } })
                .then((r) => r.json())
                .then((d) => {
                    if (d.status === 'paid' || d.status === 'failed' || attempts >= 30) {
                        clearInterval(poll)
                        window.location.reload()
                    }
                })
                .catch(() => {})
        }, 2000)
        return () => clearInterval(poll)
    }, [statusUrl])

    return (
        <div className="status-screen plain">
            <div className="spinner"></div>
            <h1 style={{ color: 'var(--navy)' }}>Potwierdzamy Twoją wpłatę…</h1>
            <p className="muted">To potrwa tylko chwilę. Nie zamykaj tej strony.</p>
            <div className="order-no" style={{ color: 'var(--muted)' }}>Potwierdzenie nr {orderId}</div>
        </div>
    )
}

ReturnPending.layout = (page) => <StorefrontPublicLayout bare title="Potwierdzamy wpłatę…">{page}</StorefrontPublicLayout>
