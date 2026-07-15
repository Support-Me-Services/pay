import { useCallback, useEffect, useRef, useState } from 'react'
import { usePage } from '@inertiajs/react'
import GatewayPublicLayout from '@/Layouts/GatewayPublicLayout'

const PAGE_CSS = `
.pay-screen{
    --sm-ink:#24324A; --sm-blue-a:#4E7FA7; --sm-blue-b:#1473C0; --sm-line:#E6EAF0;
    min-height:100vh; background:#EEF3F8; display:flex; flex-direction:column;
    align-items:center; justify-content:center; padding:20px;
    font-family:'Inter',system-ui,-apple-system,'Segoe UI',sans-serif;
}
.pay-card{ width:100%; max-width:440px; background:#fff; border:1px solid var(--sm-line);
    border-radius:22px; box-shadow:0 16px 48px rgba(36,50,74,.12); padding:22px 22px 24px; }
.pay-top{ display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
.pay-back{ display:inline-flex; align-items:center; gap:6px; border:1px solid var(--sm-line);
    background:#fff; color:var(--sm-ink); font-weight:600; font-size:.85rem; padding:7px 13px;
    border-radius:999px; cursor:pointer; line-height:1; }
.pay-back:hover{ background:#EEF3F8; }
.pay-badge{ background:rgba(20,115,192,.10); color:var(--sm-blue-b); font-weight:700;
    font-size:.72rem; padding:6px 12px; border-radius:999px; white-space:nowrap; }
.pay-logo{ font-weight:800; font-size:1.3rem; letter-spacing:-.02em; color:var(--sm-ink); }
.pay-logo span{ color:var(--sm-blue-b); }
.pay-product{ margin:14px 0 2px; font-size:1.02rem; font-weight:700; color:var(--sm-ink); }
.pay-amount{ font-size:2.15rem; font-weight:800; color:var(--sm-ink); letter-spacing:-.02em; }
.pay-method{ border:1px solid var(--sm-line); border-radius:16px; padding:16px; margin-top:14px; }
.pay-method h4{ margin:0 0 4px; font-size:1rem; color:var(--sm-ink); }
.pay-method .hint{ margin:0 0 12px; color:#5b6b80; font-size:.85rem; line-height:1.45; }
.pay-or{ text-align:center; color:#90a0b3; font-size:.74rem; font-weight:700;
    text-transform:uppercase; letter-spacing:.12em; margin:16px 0 2px; }
.sm-input{ width:100%; border:2px solid var(--sm-line); border-radius:12px; padding:12px;
    font-size:1.5rem; text-align:center; letter-spacing:.35em; color:var(--sm-ink); box-sizing:border-box; }
.sm-input:focus{ outline:none; border-color:var(--sm-blue-b); }
.sm-btn{ display:flex; width:100%; align-items:center; justify-content:center; gap:8px;
    border:0; cursor:pointer; font-weight:700; font-size:.95rem; padding:13px 18px;
    border-radius:999px; box-sizing:border-box; text-decoration:none; }
.sm-btn-primary{ color:#fff; background:linear-gradient(117deg,var(--sm-blue-a),var(--sm-blue-b)); }
.sm-btn-primary:hover{ filter:brightness(1.06); color:#fff; }
.sm-btn-outline{ background:#fff; border:1.5px solid var(--sm-blue-b); color:var(--sm-blue-b); margin-top:10px; }
.sm-btn-outline:hover{ background:rgba(20,115,192,.06); color:var(--sm-blue-b); }
.sm-bank-grid{ display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:12px; }
.sm-bank-btn{ background:#F4F7FA; border:2px solid transparent; border-radius:12px; padding:10px 8px;
    cursor:pointer; display:flex; align-items:center; justify-content:center; min-height:52px; }
.sm-bank-btn:hover{ border-color:var(--sm-blue-b); background:#fff; }
.sm-bank-btn img{ max-height:26px; max-width:100%; object-fit:contain; }
.sm-err{ color:#c0143c; font-size:.85rem; margin:6px 0; }
.pay-foot{ color:#90a0b3; font-size:.8rem; margin-top:14px; text-align:center; }
.sm-spin{ width:34px; height:34px; border:4px solid #dbe4ee; border-top-color:var(--sm-blue-b);
    border-radius:50%; animation:smspin .8s linear infinite; margin:12px auto 0; }
@keyframes smspin{ to{ transform:rotate(360deg); } }
`

/**
 * Hostowana strona płatności app2app (/pay/{uuid}) — BLIK Level 0 + pay-by-link (PBL).
 * Odwzorowuje payment/app2app.blade.php 1:1. Akcje BLIK/bank idą fetch-em (JSON) do
 * /pay/{uuid}/{blik,bank} z X-CSRF-TOKEN; status polłowany z /pay/{uuid}/status.
 * „Inny bank lub karta" to NATYWNY POST do /pay/{uuid}/online (pełne przeładowanie →
 * redirect()->away() na PayU) — nie router Inertia.
 */
export default function App2App() {
    const { transaction, banks, continueUrl, urls, csrf_token } = usePage().props
    // Widok: metody płatności | oczekiwanie na potwierdzenie | dokończenie (gdy zamówienie już istnieje)
    const [view, setView] = useState(continueUrl ? 'continue' : 'methods')
    const [code, setCode] = useState('')
    const [codeError, setCodeError] = useState('')
    const [bankError, setBankError] = useState('')
    const pollingRef = useRef(false)

    // Polling statusu — po opłaceniu wraca do sklepu (redirect z serwera).
    const poll = useCallback(() => {
        fetch(urls.status, { headers: { Accept: 'application/json' } })
            .then((r) => r.json())
            .then((d) => { d.redirect ? (window.location.href = d.redirect) : setTimeout(poll, 2000) })
            .catch(() => setTimeout(poll, 3000))
    }, [urls.status])

    // Gdy zamówienie już istnieje u operatora (powrót bez dokończenia) — polluj od razu.
    useEffect(() => {
        if (continueUrl && !pollingRef.current) {
            pollingRef.current = true
            poll()
        }
    }, [continueUrl, poll])

    const showError = (which, msg) => {
        setView('methods')
        if (which === 'code') setCodeError(msg)
        else setBankError(msg)
    }

    const payBank = (method) => {
        setBankError('')
        setView('processing')
        fetch(urls.bank, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf_token, Accept: 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ method }),
        })
            .then(async (r) => {
                const d = await r.json()
                if (r.ok && d.redirect) window.location.href = d.redirect
                else showError('bank', d.error || 'Nie udało się rozpocząć płatności. Spróbuj ponownie.')
            })
            .catch(() => showError('bank', 'Błąd połączenia. Spróbuj ponownie.'))
    }

    const payBlik = () => {
        const clean = code.replace(/\s/g, '')
        if (!/^\d{6}$/.test(clean)) { showError('code', 'Wpisz 6 cyfr kodu BLIK.'); return }
        setCodeError('')
        setView('processing')
        fetch(urls.blik, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf_token, Accept: 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ code: clean }),
        })
            .then(async (r) => {
                const d = await r.json()
                if (r.ok) { d.redirect ? (window.location.href = d.redirect) : poll() }
                else showError('code', d.error || d.errors?.code?.[0] || 'Nie udało się rozpocząć płatności.')
            })
            .catch(() => showError('code', 'Błąd połączenia. Spróbuj ponownie.'))
    }

    const goBack = () => {
        if (window.history.length > 1) window.history.back()
        else window.location.href = '/'
    }

    return (
        <div className="pay-screen">
            <style>{PAGE_CSS}</style>
            <div className="pay-card">
                <div className="pay-top">
                    <button type="button" className="pay-back" onClick={goBack}>
                        <span aria-hidden="true">&larr;</span> Wstecz
                    </button>
                    <span className="pay-badge">Bezpieczna płatność</span>
                </div>

                <div className="pay-logo">Support<span>ME</span></div>
                <div className="pay-product">{transaction.productName}</div>
                <div className="pay-amount">{transaction.amountPln} zł</div>

                {view === 'methods' && (
                    <div id="payMethods">
                        {/* BLIK */}
                        <section className="pay-method">
                            <h4>Zapłać kodem BLIK</h4>
                            <p className="hint">Wpisz 6-cyfrowy kod z aplikacji bankowej i potwierdź płatność w telefonie.</p>
                            <input type="text" inputMode="numeric" autoComplete="one-time-code"
                                pattern="[0-9]*" maxLength={6} placeholder="123 456" className="sm-input"
                                value={code} onChange={(e) => setCode(e.target.value)} />
                            {codeError && <div className="sm-err">{codeError}</div>}
                            <button type="button" className="sm-btn sm-btn-primary" style={{ marginTop: 12 }} onClick={payBlik}>
                                Zapłać {transaction.amountPln} zł
                            </button>
                        </section>

                        <div className="pay-or">albo</div>

                        {/* Przelew przez płatności online */}
                        <section className="pay-method">
                            <h4>Przelew przez płatności online</h4>
                            <p className="hint">Wybierz swój bank — otworzy się jego aplikacja, a płatność potwierdzisz odciskiem palca lub Face&nbsp;ID. Możesz też zapłacić kartą.</p>
                            {bankError && <div className="sm-err">{bankError}</div>}
                            {banks.length > 0 && (
                                <div className="sm-bank-grid">
                                    {banks.map((bank) => (
                                        <button type="button" key={bank.value} className="sm-bank-btn"
                                            title={bank.name} onClick={() => payBank(bank.value)}>
                                            {bank.image
                                                ? <img src={bank.image} alt={bank.name} loading="lazy" />
                                                : <span style={{ fontSize: '.78rem', fontWeight: 700 }}>{bank.name}</span>}
                                        </button>
                                    ))}
                                </div>
                            )}
                            {/* Natywny POST → redirect()->away() na PayU (pełne przeładowanie), nie Inertia */}
                            <form method="POST" action={urls.online}>
                                <input type="hidden" name="_token" value={csrf_token} />
                                <button type="submit" className="sm-btn sm-btn-outline">Inny bank lub karta &rarr; przejdź do PayU</button>
                            </form>
                        </section>
                    </div>
                )}

                {/* Stan: czekamy na potwierdzenie */}
                {view === 'processing' && (
                    <div id="processing" style={{ textAlign: 'center', padding: '24px 0' }}>
                        <p style={{ fontWeight: 700, margin: 0, color: '#24324A', fontSize: '1.05rem' }}>Potwierdź płatność w aplikacji banku</p>
                        <p className="hint" style={{ marginTop: 4 }}>Zatwierdź ją biometrią lub PIN-em w swojej aplikacji bankowej.</p>
                        <div className="sm-spin"></div>
                    </div>
                )}

                {/* Stan: zamówienie czeka u operatora */}
                {view === 'continue' && continueUrl && (
                    <div id="continueBox" style={{ textAlign: 'center', padding: '12px 0' }}>
                        <p style={{ fontWeight: 700, margin: '0 0 8px', color: '#24324A' }}>Twoja płatność czeka na dokończenie</p>
                        <a href={continueUrl} className="sm-btn sm-btn-primary">Dokończ płatność</a>
                        <p className="hint" style={{ marginTop: 10 }}>Sprawdzamy status na bieżąco — po opłaceniu wrócisz do sklepu automatycznie.</p>
                    </div>
                )}
            </div>
            <p className="pay-foot">Płatność obsługuje PayU &middot; please-support-me.com</p>
        </div>
    )
}

App2App.layout = (page) => <GatewayPublicLayout bare>{page}</GatewayPublicLayout>
