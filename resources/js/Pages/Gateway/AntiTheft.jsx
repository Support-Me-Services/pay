import { useState } from 'react'
import { router } from '@inertiajs/react'
import GatewayPanelLayout from '@/Layouts/GatewayPanelLayout'

/** Panel bramki — AntiTheft (FIKCYJNE demo: status zawsze OK). */
export default function AntiTheft({ shops, shop, tags, lastCheck, antitheftUrl }) {
    const [modal, setModal] = useState(false)

    const onShop = (e) => router.get(antitheftUrl, { shop_id: e.target.value }, { preserveScroll: true })

    return (
        <>
            <div className="panel-title">
                <h1>AntiTheft <button type="button" className="info-btn" onClick={() => setModal(true)} aria-label="Jak działa AntiTheft?">i</button></h1>
                <select value={shop?.id ?? ''} onChange={onShop}>
                    {shops.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                </select>
            </div>

            {shop && (
                <>
                    <div className="card card-static mb-3"><div className="card-body text-center" style={{ padding: '36px 20px' }}>
                        <div style={{ display: 'inline-flex', alignItems: 'center', gap: 12, background: 'rgba(0,163,108,.12)', color: 'var(--success)', padding: '14px 28px', borderRadius: 999, fontSize: '1.3rem', fontWeight: 800 }}>
                            <span style={{ fontSize: '1.6rem' }}>✓</span> Sprawdzone
                        </div>
                        <p className="mt-2 mb-0 fw-bold">Nie wykryto obcych tagów w okolicy punktu sprzedaży</p>
                        <p className="text-muted small mb-0">Ostatnie sprawdzenie: {lastCheck.at} ({lastCheck.human})</p>
                    </div></div>

                    <div className="card card-static"><div className="card-body">
                        <h3>Tagi sklepu {shop.name}</h3>
                        <div className="table-wrap">
                            <table className="table">
                                <thead>
                                    <tr><th>UID taga</th><th>Etykieta</th><th>Status weryfikacji</th></tr>
                                </thead>
                                <tbody>
                                    {tags.length === 0 && <tr><td colSpan={3} className="text-muted">Brak tagów w tym sklepie.</td></tr>}
                                    {tags.map((t) => (
                                        <tr key={t.id}>
                                            <td className="fw-bold"><code>{t.tag_uid}</code></td>
                                            <td>{t.label || '—'}</td>
                                            <td><span className="badge badge-success">✓ Zweryfikowany</span></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div></div>
                </>
            )}

            <div className={`modal-backdrop${modal ? ' open' : ''}`} onClick={(e) => { if (e.target === e.currentTarget) setModal(false) }}>
                <div className="modal">
                    <h3>Jak działa AntiTheft?</h3>
                    <p>System cyklicznie weryfikuje, czy w okolicy punktu sprzedaży nie pojawiły się tagi NFC inne niż
                        zarejestrowane w tym sklepie. Wykrycie obcego taga (np. naklejonego przez oszusta na Twój produkt
                        w celu przekierowania płatności) oznaczane jest statusem ostrzeżenia i natychmiastowym powiadomieniem.</p>
                    <button type="button" className="btn btn-primary" onClick={() => setModal(false)}>Rozumiem</button>
                </div>
            </div>
        </>
    )
}

AntiTheft.layout = (page) => <GatewayPanelLayout title="AntiTheft">{page}</GatewayPanelLayout>
