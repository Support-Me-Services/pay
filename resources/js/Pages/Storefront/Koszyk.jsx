import { useState } from 'react'
import { router, usePage } from '@inertiajs/react'
import StorefrontLayout from '@/Layouts/StorefrontLayout'

const CSS = `
.cart{ max-width:820px; margin:0 auto; padding:32px 20px 64px; }
.cart h1{ font-size:30px; margin:0 0 20px; }
.cart__flash{ padding:12px 16px; border-radius:10px; font-size:15px; margin-bottom:18px; }
.cart__flash.is-ok{ background:#e7f7ee; color:#1a7f45; border:1px solid #b7e4c7; }
.cart__flash.is-err{ background:#fdeaea; color:#b02525; border:1px solid #f5c2c2; }
.cart__empty{ color:#4a5568; font-size:17px; margin:8px 0 20px; }
.citem{ display:grid; grid-template-columns:64px 1fr auto auto auto; align-items:center; gap:14px; padding:14px 0; border-bottom:1px solid #eef1f4; }
.citem__img{ width:64px; height:64px; object-fit:contain; background:#f6f9fb; border-radius:10px; }
.citem__name{ font-weight:700; }
.citem__unit{ font-size:13px; color:#6b7280; }
.citem__qty{ display:flex; gap:6px; align-items:center; }
.citem__qty input{ width:60px; padding:7px 8px; border:1px solid #d5dbe2; border-radius:8px; text-align:center; }
.citem__set{ padding:7px 10px; border:1px solid #d5dbe2; background:#fff; border-radius:8px; cursor:pointer; font-size:13px; }
.citem__line{ font-weight:800; min-width:90px; text-align:right; }
.citem__del{ width:30px; height:30px; border:0; border-radius:50%; background:#f1f3f6; color:#8a94a2; font-size:18px; line-height:1; cursor:pointer; }
.citem__del:hover{ background:#fdeaea; color:#b02525; }
.cart__foot{ margin-top:24px; }
.cart__back{ color:#2563eb; text-decoration:none; font-weight:600; }
.cart__actions{ display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; }
.cart__buy{ padding:13px 26px; border:0; border-radius:12px; background:#2563eb; color:#fff; font-weight:800; font-size:16px; cursor:pointer; }
.cart__buy:hover{ background:#1d4ed8; }
.cart__ship{ color:#6b7280; font-size:13.5px; margin:16px 0 4px; }
.cart__policy{ color:#6b7280; font-size:12.5px; margin:0; }
@media (max-width:560px){ .citem{ grid-template-columns:52px 1fr auto; row-gap:8px; } .citem__line{ grid-column:2/4; text-align:left; } }
.ship{ margin:24px 0 6px; }
.ship__title{ font-weight:700; font-size:17px; margin-bottom:10px; }
.ship__opt{ display:flex; align-items:center; gap:10px; padding:11px 13px; border:1px solid #e6eaef; border-radius:10px; margin-bottom:8px; cursor:pointer; }
.ship__opt:has(input:checked){ border-color:#2563eb; background:#f5f8ff; }
.ship__label{ flex:1; }
.ship__price{ font-weight:700; color:#334155; }
.ship__point{ margin:2px 0 10px; }
.ship__point input{ width:100%; padding:10px 12px; border:1px solid #d5dbe2; border-radius:8px; }
.ship__apply{ padding:9px 15px; border:1px solid #2563eb; background:#fff; color:#2563eb; border-radius:8px; font-weight:700; cursor:pointer; }
.ship__opt.is-disabled{ opacity:.6; cursor:not-allowed; background:#f8fafc; }
.ship__soon{ display:inline-block; margin-left:8px; padding:1px 8px; border-radius:999px; background:#eef2f7; color:#64748b; font-size:11px; font-weight:700; vertical-align:middle; }
.ship__note{ font-size:12.5px; color:#6b7280; margin:6px 0 10px; }
.cart__sums{ margin:18px 0; }
.cart__row{ display:flex; justify-content:space-between; padding:5px 0; color:#334155; }
.cart__row--total{ border-top:1px solid #e6eaef; margin-top:6px; padding-top:12px; font-size:20px; }
.cart__row--total strong{ font-size:24px; }
`

/** Koszyk sklepu per-konto. Aktualizacja/usuwanie/dostawa przez Inertia;
 *  checkout NATYWNYM POST (redirect PayU). */
export default function Koszyk({ lines, methods, shipCode, shipPoint, shipHasPoint, shipMultiple, subtotal, shipLabel, shipCost, total, urls }) {
    const page = usePage().props
    const flash = page.flash || {}
    const csrf = page.csrf_token

    const [qty, setQty] = useState(() => Object.fromEntries(lines.map((l) => [l.id, l.qty])))
    const [ship, setShip] = useState(shipCode)
    const [point, setPoint] = useState(shipPoint || '')
    const selectedHasPoint = methods.find((m) => m.code === ship)?.point ?? shipHasPoint

    const setQtyLine = (id, v) => setQty((q) => ({ ...q, [id]: v }))
    const updateLine = (l) => router.post(l.update_url, { qty: qty[l.id] }, { preserveScroll: true })
    const removeLine = (l) => router.post(l.remove_url, {}, { preserveScroll: true })
    const applyShipping = (e) => { e.preventDefault(); router.post(urls.shipping, { ship, ship_point: point }, { preserveScroll: true }) }

    return (
        <main className="cart">
            <style>{CSS}</style>
            <h1>Twój koszyk</h1>

            {flash.success && <div className="cart__flash is-ok">{flash.success}</div>}
            {flash.error && <div className="cart__flash is-err">{flash.error}</div>}

            {lines.length === 0 ? (
                <>
                    <p className="cart__empty">Koszyk jest pusty.</p>
                    <a className="cart__back" href={urls.shop}>← Wróć do sklepu</a>
                </>
            ) : (
                <>
                    <div className="cart__list">
                        {lines.map((l) => (
                            <div className="citem" key={l.id}>
                                <img className="citem__img" src={l.image} alt={l.name} />
                                <div className="citem__main">
                                    <div className="citem__name">{l.name}</div>
                                    <div className="citem__unit">{l.unit_price} zł / szt.</div>
                                </div>
                                <div className="citem__qty">
                                    <input type="number" min={0} max={99} value={qty[l.id]} aria-label="Ilość"
                                        onChange={(e) => setQtyLine(l.id, e.target.value)} />
                                    <button type="button" className="citem__set" onClick={() => updateLine(l)}>Zmień</button>
                                </div>
                                <div className="citem__line">{l.line_total} zł</div>
                                <button type="button" className="citem__del" aria-label="Usuń z koszyka" onClick={() => removeLine(l)}>&times;</button>
                            </div>
                        ))}
                    </div>

                    <div className="cart__foot">
                        <div className="ship">
                            <div className="ship__title">Dostawa</div>
                            <form onSubmit={applyShipping}>
                                {methods.map((m) => (
                                    <label className={`ship__opt${m.enabled ? '' : ' is-disabled'}`} key={m.code}>
                                        <input type="radio" name="ship" value={m.code} checked={ship === m.code}
                                            disabled={!m.enabled} onChange={() => setShip(m.code)} />
                                        <span className="ship__label">{m.label}{!m.enabled && <span className="ship__soon">dostępne wkrótce</span>}</span>
                                        <span className="ship__price">{m.price}</span>
                                    </label>
                                ))}
                                <p className="ship__note">Na razie realizujemy wyłącznie <strong>odbiór osobisty</strong>. Pozostałe metody (paczkomaty, kurierzy) udostępnimy wkrótce.</p>
                                {selectedHasPoint && (
                                    <div className="ship__point">
                                        <input type="text" name="ship_point" value={point} maxLength={64}
                                            placeholder="Nr paczkomatu / punktu odbioru (np. WAW01A)" onChange={(e) => setPoint(e.target.value)} />
                                    </div>
                                )}
                                {shipMultiple && <button type="submit" className="ship__apply">Zastosuj dostawę</button>}
                            </form>
                        </div>

                        <div className="cart__sums">
                            <div className="cart__row"><span>Produkty</span><span>{subtotal} zł</span></div>
                            <div className="cart__row"><span>Dostawa — {shipLabel}</span><span>{shipCost}</span></div>
                            <div className="cart__row cart__row--total"><span>Razem</span><strong>{total} zł</strong></div>
                        </div>

                        <div className="cart__actions">
                            <a className="cart__back" href={urls.shop}>← Kontynuuj zakupy</a>
                            {/* ⚠️ PayU: natywny POST — checkout redirectuje na payment_url PayU. */}
                            <form method="POST" action={urls.checkout}>
                                <input type="hidden" name="_token" value={csrf} />
                                <button type="submit" className="cart__buy">Kupuję i płacę</button>
                            </form>
                        </div>
                        <p className="cart__ship">Czas realizacji: do 3 dni roboczych · zwrot do 14 dni</p>
                        <p className="cart__policy">Klikając „Kupuję i płacę" akceptujesz{' '}
                            <a href="/polityka-prywatnosci.pdf" target="_blank" rel="noopener noreferrer">Politykę prywatności (PDF)</a>
                            {' '}i <a href={urls.regulamin} target="_blank" rel="noopener noreferrer">Regulamin</a>.
                        </p>
                    </div>
                </>
            )}
        </main>
    )
}

Koszyk.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>
