import { useState } from 'react'
import { usePage } from '@inertiajs/react'
import GatewayPublicLayout from '@/Layouts/GatewayPublicLayout'

/**
 * Symulator płatności klasycznej (BLIK) — MockProvider (dev/demo).
 * Odwzorowuje mockpay/classic.blade.php: dowolny 6-cyfrowy kod = sukces po 2 s;
 * „Symuluj odmowę" = fail po 1,2 s. confirm/fail → JSON redirect (pay.return).
 */
export default function MockClassic() {
    const { transaction, urls, csrf_token } = usePage().props
    const [code, setCode] = useState('')
    const [codeError, setCodeError] = useState(false)
    const [processing, setProcessing] = useState(false)

    const settle = (url, delayMs) => {
        setProcessing(true)
        setTimeout(() => {
            fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf_token, Accept: 'application/json' } })
                .then((r) => r.json())
                .then((d) => { window.location.href = d.redirect })
                .catch(() => { window.location.reload() })
        }, delayMs)
    }

    const pay = () => {
        const clean = code.replace(/\s/g, '')
        if (!/^\d{6}$/.test(clean)) { setCodeError(true); return }
        setCodeError(false)
        settle(urls.confirm, 2000) // dowolny kod = sukces po 2 s
    }

    return (
        <div style={{ minHeight: '100vh', background: 'var(--bg-alt)', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', padding: 20 }}>
            <div className="card card-static" style={{ width: '100%', maxWidth: 420 }}>
                <div className="card-body">
                    <div className="d-flex justify-between align-center mb-2">
                        <span className="site-logo" style={{ color: 'var(--ink)' }}>Support<span className="dot">ME</span></span>
                        <span className="badge badge-muted">płatność testowa</span>
                    </div>

                    <h3 className="mb-0">{transaction.productName}</h3>
                    <div className="price-xl mb-2">{transaction.amountPln} zł</div>

                    {!processing ? (
                        <div id="payForm">
                            <div className="form-group">
                                <label htmlFor="code">Kod BLIK (6 cyfr)</label>
                                <input type="text" id="code" inputMode="numeric" pattern="[0-9]*" maxLength={6}
                                    placeholder="123 456" style={{ fontSize: '1.5rem', textAlign: 'center', letterSpacing: '.35em' }}
                                    value={code} onChange={(e) => setCode(e.target.value)} />
                                <div className="form-hint">Tryb demo — dowolny 6-cyfrowy kod zostanie zaakceptowany.</div>
                                {codeError && <div className="form-error">Wpisz 6 cyfr.</div>}
                            </div>
                            <button type="button" className="btn btn-primary btn-block" onClick={pay}>Zapłać {transaction.amountPln} zł</button>
                            <button type="button" className="btn btn-secondary btn-block mt-1" onClick={() => settle(urls.fail, 1200)}>Symuluj odmowę</button>
                        </div>
                    ) : (
                        <div id="processing" style={{ textAlign: 'center', padding: '24px 0' }}>
                            <div className="spinner"></div>
                            <p className="fw-bold mt-2 mb-0">Przetwarzanie płatności...</p>
                            <p className="text-muted small">Potwierdź w aplikacji banku</p>
                        </div>
                    )}
                </div>
            </div>
            <p className="small text-muted mt-2">Symulator płatności (MockProvider) — środowisko demo</p>
        </div>
    )
}

MockClassic.layout = (page) => <GatewayPublicLayout bare>{page}</GatewayPublicLayout>
