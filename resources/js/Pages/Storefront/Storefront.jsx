import { useEffect, useLayoutEffect, useRef, useState } from 'react'
import { usePage } from '@inertiajs/react'
import StorefrontLayout from '@/Layouts/StorefrontLayout'

/**
 * Paywin „/" — darowizna na wybraną kwotę (model donacyjny).
 * WAŻNE (PayU): formularz to NATYWNY POST do shop.buy — pełne przeładowanie,
 * by redirect kontrolera na payment_url PayU (redirect()->away) zadziałał.
 * React steruje karuzelą produktów, kwotą i karuzelą fundacji.
 */
export default function Storefront({ items, startIdx, foundations, mainUrl, regulaminUrl }) {
    const page = usePage().props
    const csrf = page.csrf_token
    const serverErr = page.errors?.amount_pln || page.flash?.error || null

    const [idx, setIdx] = useState(startIdx || 0)
    const [amount, setAmount] = useState(items[startIdx || 0]?.min ?? '')
    const [fIdx, setFIdx] = useState(0)
    const [err, setErr] = useState(serverErr)

    const inputRef = useRef(null)
    const fndVpRef = useRef(null)
    const [fndShift, setFndShift] = useState(0)
    const IW = 150

    const it = items[idx] || {}
    const prevIt = items[(idx - 1 + items.length) % items.length] || {}
    const nextIt = items[(idx + 1) % items.length] || {}

    const focusInput = () => {
        const el = inputRef.current
        if (!el) return
        try { el.focus({ preventScroll: true }); const v = el.value; el.setSelectionRange?.(v.length, v.length) } catch (e) { /* noop */ }
    }

    useEffect(() => {
        document.body.style.overflow = 'hidden'
        focusInput()
        const onKey = (e) => {
            if (e.key === 'Escape') close()
            else if (e.key === 'ArrowLeft') go(-1)
            else if (e.key === 'ArrowRight') go(1)
        }
        document.addEventListener('keydown', onKey)
        return () => { document.body.style.overflow = ''; document.removeEventListener('keydown', onKey) }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [])

    // Wyśrodkowanie aktywnej fundacji (transform track wg szerokości viewportu).
    useLayoutEffect(() => {
        const w = fndVpRef.current?.clientWidth || 0
        setFndShift(w / 2 - (fIdx * IW + IW / 2))
    }, [fIdx, foundations.length])
    useEffect(() => {
        const onResize = () => { const w = fndVpRef.current?.clientWidth || 0; setFndShift(w / 2 - (fIdx * IW + IW / 2)) }
        window.addEventListener('resize', onResize)
        return () => window.removeEventListener('resize', onResize)
    }, [fIdx])

    const close = () => { window.location.href = mainUrl }

    const onAmount = (e) => {
        let v = e.target.value.replace(/\D+/g, '').replace(/^0+(?=\d)/, '')
        if (v.length > 4) v = v.slice(0, 4)
        setAmount(v)
        if (v !== '' && parseInt(v, 10) >= it.min) setErr(null)
    }

    const onSubmit = (e) => {
        const v = parseInt(amount, 10)
        if (isNaN(v) || v < it.min) {
            e.preventDefault()
            setErr(`Minimalna kwota dla „${it.name}” to ${it.min} zł.`)
            focusInput()
        } else if (v > 5000) {
            e.preventDefault()
            setErr('Maksymalna kwota to 5000 zł.')
            focusInput()
        }
        // w przeciwnym razie — natywny POST leci dalej (redirect do PayU)
    }

    // Zmiana produktu wymaga najpierw przytrzymania go (HOLD_MS) — dopiero
    // po tym czasie (sygnalizowanym krótką wibracją i podświetleniem karty)
    // przesunięcie w bok (dotyk lub mysz — Pointer Events obsługują oba
    // jednym API) przełącza produkt. Próg przesunięcia jest celowo mały
    // (SWIPE_PX) — samo przytrzymanie już chroni przed przypadkową zmianą,
    // więc po odblokowaniu zmiana ma być łatwa i szybka, bez przeciągania
    // przez cały ekran. Prawdziwa karuzela: tor z trzema kartami (poprzednia/
    // bieżąca/następna) przesuwa się na żywo razem z gestem (dragX), a po
    // puszczeniu płynnie dociąga do pełnego przejścia albo wraca do środka.
    const HOLD_MS = 500
    const SWIPE_PX = 40
    const holdTimer = useRef(null)
    const swipe = useRef({ x: 0, active: false })
    const [unlocked, setUnlocked] = useState(false)

    const trackRef = useRef(null)
    const pendingDelta = useRef(0)
    const [dragX, setDragX] = useState(0)
    const [instant, setInstant] = useState(false)

    const clearHold = () => {
        if (holdTimer.current) { clearTimeout(holdTimer.current); holdTimer.current = null }
    }

    useEffect(() => clearHold, [])

    // Animuje tor do pełnego przejścia (delta=±1) albo z powrotem na środek
    // (delta=0) — faktyczna zmiana idx następuje dopiero po zakończeniu tej
    // animacji (patrz onTrackTransitionEnd), żeby przejście było widoczne.
    const animateTo = (delta) => {
        if (items.length < 2) return
        const cardW = trackRef.current ? trackRef.current.clientWidth / 3 : window.innerWidth
        pendingDelta.current = delta
        setInstant(false)
        setDragX(delta === 0 ? 0 : (delta > 0 ? -cardW : cardW))
    }

    const go = (delta) => animateTo(delta)

    const onTrackTransitionEnd = (e) => {
        if (e.target !== e.currentTarget) return
        const delta = pendingDelta.current
        pendingDelta.current = 0
        if (delta !== 0) {
            // Zawartość środkowej karty zmienia się w tym samym renderze co
            // wyłączenie transition — wizualnie niezauważalny, „cichy" powrót
            // toru na pozycję środkową (teraz już z nowym produktem).
            setInstant(true)
            setIdx((prev) => {
                const next = (prev + delta + items.length) % items.length
                setAmount(items[next].min)
                setErr(null)
                return next
            })
            setDragX(0)
            requestAnimationFrame(focusInput)
        }
    }

    const onStageDown = (e) => {
        const x = e.clientX
        const pointerId = e.pointerId
        const target = e.currentTarget
        clearHold()
        holdTimer.current = setTimeout(() => {
            swipe.current = { x, active: true }
            setUnlocked(true)
            setInstant(true)
            if (navigator.vibrate) navigator.vibrate(200)
            // Usztywnia element jako cel WSZYSTKICH kolejnych zdarzeń tego
            // pointera — bez tego, przy przesuwaniu na spory dystans, naturalny
            // gest łatwo "zjeżdża" pionowo poza wąski pas .paywin__stage (jego
            // wysokość to tylko obrazek+nazwa, nie cały ekran), i pointerup
            // gubi się na innym elemencie zamiast dotrzeć tutaj.
            try { target.setPointerCapture(pointerId) } catch (err) { /* noop */ }
        }, HOLD_MS)
    }

    const onStageMove = (e) => {
        if (!swipe.current.active) return
        setDragX(e.clientX - swipe.current.x)
    }

    const onStageUp = (e) => {
        clearHold()
        if (swipe.current.active) {
            const dx = e.clientX - swipe.current.x
            try { e.currentTarget.releasePointerCapture(e.pointerId) } catch (err) { /* noop */ }
            animateTo(Math.abs(dx) >= SWIPE_PX ? (dx < 0 ? 1 : -1) : 0)
        }
        swipe.current.active = false
        setUnlocked(false)
    }

    // Kursor/palec zjechał poza obszar produktu przed odblokowaniem — anuluj
    // odliczanie (po odblokowaniu pointer capture sprawia, że to zdarzenie
    // już się nie odpala mimo ruchu poza granice elementu).
    const onStageLeave = () => { if (!swipe.current.active) clearHold() }

    return (
        <>
            <div className="paywin">
                <button className="paywin__close" type="button" aria-label="Zamknij" onClick={close}>&times;</button>

                <div className="paywin__stage" onPointerDown={onStageDown} onPointerMove={onStageMove} onPointerUp={onStageUp} onPointerLeave={onStageLeave}>
                    {/* Tor z trzema kartami (poprzednia/bieżąca/następna) — podczas
                        przeciągania przesuwa się na żywo razem z gestem (dragX),
                        dając prawdziwy efekt karuzeli zamiast podmiany obrazka. */}
                    <div ref={trackRef} className="paywin__track"
                        style={{ transform: `translateX(calc(-33.3333% + ${dragX}px))`, transition: instant ? 'none' : undefined }}
                        onTransitionEnd={onTrackTransitionEnd}>
                        {[prevIt, it, nextIt].map((cardIt, i) => (
                            <div key={i} className={`paywin__card is-in${i === 1 && unlocked ? ' is-unlocked' : ''}`}>
                                <div className={`paywin__visual${cardIt.is_svg ? ' is-svg' : ''}`}>
                                    <img src={cardIt.image} alt={cardIt.name} />
                                </div>
                                <div className="paywin__name">{cardIt.name}</div>
                            </div>
                        ))}
                    </div>
                </div>

                {items.length > 1 && (
                    <div className="paywin__dots" aria-hidden="true">
                        {items.map((_, i) => <span key={i} className={`paywin__dot${i === idx ? ' is-on' : ''}`} />)}
                    </div>
                )}

                <label className="paywin__amount" htmlFor="payAmount">
                    <input id="payAmount" ref={inputRef} className="paywin__input" type="text" inputMode="numeric"
                        autoComplete="off" autoFocus value={amount} onChange={onAmount} aria-label="Kwota wsparcia w złotych" />
                    <span className="paywin__zl">zł</span>
                </label>

                <p className="paywin__err" role="alert" hidden={!err}>{err}</p>

                <div className="paywin__support">
                    <p className="paywin__support-label">Pieniądze trafią na konto fundacji Support Me</p>
                    {/* Wybór fundacji — tymczasowo wyłączony (kod zostaje, tylko wykomentowany)
                    <div className="fnd" onTouchStart={(e) => e.stopPropagation()} onTouchEnd={(e) => e.stopPropagation()}>
                        <button className="fnd__nav fnd__nav--prev" type="button" aria-label="Poprzednia fundacja" onClick={() => setFIdx((i) => Math.max(0, i - 1))}>&lsaquo;</button>
                        <div className="fnd__viewport" ref={fndVpRef}>
                            <div className="fnd__track" style={{ transform: `translateX(${fndShift}px)` }}>
                                {foundations.map((f, i) => (
                                    <div key={f.slug} className={`fnd__item${i === fIdx ? ' is-active' : ''}`} title={f.name} onClick={() => setFIdx(i)}>
                                        <div className="fnd__logo">
                                            {f.logo ? <img src={f.logo} alt={f.name} /> : <span className="fnd__name">{f.name}</span>}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                        <button className="fnd__nav fnd__nav--next" type="button" aria-label="Następna fundacja" onClick={() => setFIdx((i) => Math.min(foundations.length - 1, i + 1))}>&rsaquo;</button>
                    </div>
                    */}
                </div>

                <form method="POST" action={it.action} className="paywin__form" onSubmit={onSubmit}>
                    <input type="hidden" name="_token" value={csrf} />
                    <input type="hidden" name="amount_pln" value={amount} />
                    <input type="hidden" name="fundacja" value={foundations[fIdx]?.slug || ''} />
                    <button type="submit" className="paywin__btn">Wesprzyj
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s-6.7-4.35-9.33-8.04C.9 10.27 2.05 6.5 5.4 6.5c1.95 0 3.32 1.13 4.45 2.64C11.28 7.63 12.65 6.5 14.6 6.5c3.35 0 4.5 3.77 2.73 6.46C18.7 16.65 12 21 12 21z" fill="#FF5C9A" /><path d="M16.9 6.7a4.4 4.4 0 0 1 0 5.9" stroke="#FF5C9A" strokeWidth="1.5" strokeLinecap="round" /><path d="M18.9 5a6.9 6.9 0 0 1 0 9.3" stroke="#FFA8CC" strokeWidth="1.5" strokeLinecap="round" /></svg>
                    </button>
                </form>

                <p className="paywin__policy">Klikając „Wesprzyj" akceptujesz <a href="/polityka-prywatnosci.pdf" target="_blank" rel="noopener noreferrer">Politykę prywatności (PDF)</a> i <a href={regulaminUrl} target="_blank" rel="noopener noreferrer">Regulamin</a></p>

                {items.length > 1 && (
                    <div className="paywin__hint">przytrzymaj produkt, aby zmienić</div>
                )}
            </div>
        </>
    )
}

Storefront.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>
