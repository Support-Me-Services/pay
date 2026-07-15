import { useRef, useState } from 'react'
import { usePage } from '@inertiajs/react'
import StorefrontLayout from '@/Layouts/StorefrontLayout'

/**
 * Strona parafii — cyfrowa taca. Presety kwot + „Inna kwota".
 * ⚠️ PayU: NATYWNY POST do product.buy (pełne przeładowanie → redirect PayU).
 */
export default function Product({ product, presets, default: def, buyUrl, categoryUrl }) {
    const page = usePage().props
    const csrf = page.csrf_token
    const serverErr = page.flash?.error || page.errors?.amount_pln || null

    // amount = wybrana kwota (liczba) lub '' gdy tryb „Inna kwota" bez wartości.
    const [amount, setAmount] = useState(def)
    const [custom, setCustom] = useState(false)   // czy kafelek „Inna kwota" jest w trybie input
    const [customVal, setCustomVal] = useState('')
    const [submitting, setSubmitting] = useState(false)
    const customRef = useRef(null)

    const pickPreset = (v) => { setCustom(false); setCustomVal(''); setAmount(v) }
    const openCustom = () => { setCustom(true); setAmount(''); requestAnimationFrame(() => customRef.current?.focus()) }
    const onCustom = (e) => {
        const raw = e.target.value
        setCustomVal(raw)
        const v = parseInt(raw, 10)
        setAmount(!isNaN(v) && v > 0 ? v : '')
    }

    const onSubmit = (e) => {
        const v = parseInt(amount, 10)
        if (isNaN(v) || v < 2) {
            e.preventDefault()
            if (!custom) openCustom(); else customRef.current?.focus()
            return
        }
        setSubmitting(true)
    }

    const ctaAmount = amount === '' ? '—' : amount

    return (
        <>
            <section className="sp-subhero">
                <div className="sp-subhero__inner">
                    <a href={categoryUrl} className="sp-back">← wszystkie parafie</a>
                    <h1>{product.name}</h1>
                    {product.purpose && <p className="sp-lede">✦ {product.purpose}</p>}
                </div>
            </section>

            <div className="sp-wrap">
                <div className="sp-container sp-give">
                    <div className="sp-give__grid">
                        <div className="sp-give__art">
                            <div className="sp-circle sp-circle--lg">
                                {product.image ? <img src={product.image} alt={product.name} /> : 'grafika'}
                            </div>
                        </div>

                        <div className="sp-give__main">
                            {product.city && <span className="sp-give__city">{product.city}</span>}
                            <h2 className="sp-give__title">{product.name}</h2>
                            {product.purpose && <span className="sp-give__purpose">✦ {product.purpose}</span>}

                            {serverErr && <div className="sp-alert sp-alert--err">{serverErr}</div>}

                            {product.description_html && (
                                <div className="sp-give__lead" dangerouslySetInnerHTML={{ __html: product.description_html }} />
                            )}

                            <form method="POST" action={buyUrl} id="giveForm" onSubmit={onSubmit}>
                                <input type="hidden" name="_token" value={csrf} />
                                <input type="hidden" name="amount_pln" value={amount} />

                                <div className="sp-give-card">
                                    <div className="sp-give-card__label">Wybierz kwotę wsparcia</div>
                                    <div className="sp-give-card__sub">Możesz wpłacić dowolną kwotę — sugerujemy {def} zł.</div>

                                    <div className="sp-amounts" role="group" aria-label="Kwota wsparcia">
                                        {presets.map((v) => {
                                            const active = !custom && amount === v
                                            return (
                                                <button type="button" key={v} className={`sp-amount ${active ? 'is-active' : ''}`}
                                                    aria-pressed={active} onClick={() => pickPreset(v)}>
                                                    {v}<small>zł</small>
                                                </button>
                                            )
                                        })}

                                        <div className={`sp-amount sp-amount--custom ${custom ? 'is-active is-editing' : ''}`} data-custom="1">
                                            {!custom ? (
                                                <button type="button" className="sp-amount__label" aria-pressed={false} onClick={openCustom}>Inna kwota</button>
                                            ) : (
                                                <span className="sp-amount__input">
                                                    <input ref={customRef} type="number" inputMode="numeric" min={2} max={5000} step={1}
                                                        placeholder="np. 30" aria-label="Inna kwota w złotych" value={customVal} onChange={onCustom} />
                                                    <span className="sp-amount__suffix">zł</span>
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    <div className="sp-give-cta">
                                        <button type="submit" form="giveForm" className="sp-btn sp-btn--block sp-btn--cta" disabled={submitting}>
                                            {submitting ? 'Przenosimy do płatności…' : <>Wesprzyj — <span>{ctaAmount}</span> zł</>}
                                        </button>
                                        <div className="sp-secure">🔒 Bezpieczna płatność BLIK · obsługuje PayU</div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </>
    )
}

Product.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>
