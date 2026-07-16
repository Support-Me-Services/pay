import { useEffect, useMemo, useState } from 'react'
import StorefrontLayout from '@/Layouts/StorefrontLayout'

const CSS = `
.lp-search{ max-width:1142px; margin:34px auto 40px; padding:0 var(--lp-gutter); }
.lp-search__row{ display:flex; flex-wrap:wrap; gap:12px; align-items:stretch; }
.lp-search__btn{ flex:0 0 auto; display:inline-flex; align-items:center; gap:8px; border:0; cursor:pointer; border-radius:14px; padding:0 26px; font-family:var(--lp-ui); font-size:16px; font-weight:600; color:#fff; background:linear-gradient(117deg, var(--lp-blue-a), var(--lp-blue-b)); box-shadow:0 8px 20px rgba(20,115,192,.22); transition:transform .15s ease, box-shadow .15s ease; }
.lp-search__btn:hover{ transform:translateY(-1px); box-shadow:0 12px 26px rgba(20,115,192,.3); }
.lp-catpage .lp-grid{ margin-top:0; }
.lp-search__field{ position:relative; display:flex; align-items:center; min-width:200px; background:#fff; border:1px solid var(--lp-wave); border-radius:14px; padding:0 14px; transition:border-color .15s ease, box-shadow .15s ease; }
.lp-search__field--grow{ flex:1 1 280px; }
.lp-search__field:focus-within{ border-color:var(--lp-blue-b); box-shadow:0 0 0 3px rgba(20,115,192,.14); }
.lp-search__field input, .lp-search__field select{ flex:1; width:100%; border:0; outline:none; background:transparent; font-family:var(--lp-ui); font-size:16px; color:var(--lp-ink); padding:13px 0; line-height:1.3; }
.lp-search__field select{ cursor:pointer; padding-right:6px; }
.lp-search__field input::placeholder{ color:#8b95a5; }
.lp-search__empty{ max-width:1142px; margin:0 auto 40px; padding:18px var(--lp-gutter); text-align:center; font-family:var(--lp-ui); font-size:18px; color:var(--lp-ink); }
@media (max-width:860px){ .lp-search, .lp-search__empty{ padding-left:22px; padding-right:22px; } .lp-search__field--grow{ flex-basis:100%; } .lp-search__field{ flex:1 1 100%; } .lp-search__btn{ flex:1 1 100%; width:100%; justify-content:center; padding-top:14px; padding-bottom:14px; } }
`

const ucfirst = (s) => s.charAt(0).toUpperCase() + s.slice(1)
const DIA = { ą: 'a', ć: 'c', ę: 'e', ł: 'l', ń: 'n', ó: 'o', ś: 's', ź: 'z', ż: 'z' }
const norm = (s) => (s || '').toString().toLowerCase().replace(/[ąćęłńóśźż]/g, (c) => DIA[c] || c)

/** Strona kategorii wsparcia — siatka parafii z klienckim filtrem. */
export default function Category({ category, products, cities, voivodeships, mainUrl, cultUrl }) {
    const [q, setQ] = useState('')
    const [city, setCity] = useState('')
    const [voiv, setVoiv] = useState('')
    // Kryteria zastosowane (dopiero po „Szukaj"/Enter) — filtr nie działa na żywo przy pisaniu.
    const [applied, setApplied] = useState({ q: '', city: '', voiv: '' })

    // Deeplink: wczytaj kryteria z URL przy wejściu.
    useEffect(() => {
        const sp = new URLSearchParams(window.location.search)
        const iq = sp.get('q') || '', ic = sp.get('miasto') || '', iv = sp.get('woj') || ''
        setQ(iq); setCity(ic); setVoiv(iv)
        setApplied({ q: iq, city: ic, voiv: iv })
    }, [])

    const apply = () => {
        setApplied({ q, city, voiv })
        const p = new URLSearchParams()
        if (q.trim()) p.set('q', q.trim())
        if (city.trim()) p.set('miasto', city.trim())
        if (voiv) p.set('woj', voiv)
        const qs = p.toString()
        history.replaceState(null, '', window.location.pathname + (qs ? `?${qs}` : ''))
    }

    const visible = useMemo(() => {
        const nq = norm(applied.q.trim()), nc = norm(applied.city.trim()), nv = norm(applied.voiv)
        return products.filter((p) =>
            (!nq || norm(p.name).indexOf(nq) !== -1) &&
            (!nc || norm(p.city).indexOf(nc) !== -1) &&
            (!nv || norm(p.voiv) === nv))
    }, [products, applied])

    const onEnter = (e) => { if (e.key === 'Enter') { e.preventDefault(); apply() } }

    return (
        <section className="lp-section lp-catpage lp-container">
            <style>{CSS}</style>
            <a className="lp-back" href={`${mainUrl}#kogo-wspieramy`}>← Wszystkie kategorie</a>
            <div className="lp-head">
                <h2>{category.label_text}</h2>
                <p>{category.intro}</p>
            </div>

            {products.length > 0 ? (
                <>
                    <div className="lp-search">
                        <div className="lp-search__row">
                            <label className="lp-search__field lp-search__field--grow">
                                <input type="text" value={q} onChange={(e) => setQ(e.target.value)} onKeyDown={onEnter}
                                    placeholder="Szukaj po nazwie…" autoComplete="off" aria-label="Szukaj po nazwie" />
                            </label>
                            <label className="lp-search__field">
                                <input type="text" value={city} onChange={(e) => setCity(e.target.value)} onKeyDown={onEnter}
                                    list="lp-city-list" placeholder="Miasto…" autoComplete="off" aria-label="Miasto" />
                            </label>
                            <datalist id="lp-city-list">
                                {cities.map((c) => <option value={c} key={c}></option>)}
                            </datalist>
                            <label className="lp-search__field">
                                <select value={voiv} onChange={(e) => setVoiv(e.target.value)} aria-label="Województwo">
                                    <option value="">Wszystkie województwa</option>
                                    {voivodeships.map((w) => <option value={w} key={w}>{ucfirst(w)}</option>)}
                                </select>
                            </label>
                            <button type="button" className="lp-search__btn" onClick={apply}>Szukaj</button>
                        </div>
                    </div>

                    {visible.length === 0 && <p className="lp-search__empty">Brak wyników dla podanych kryteriów.</p>}

                    {visible.length > 0 && (
                        <div className="lp-grid">
                            {visible.map((p) => (
                                <a className="lp-pcard" href={p.url} key={p.slug}>
                                    <div className="lp-pcard__art">
                                        {p.image && <img src={p.image} alt={p.name} loading="lazy" />}
                                        {p.city && <span className="lp-pcard__city">{p.city}</span>}
                                    </div>
                                    <div className="lp-pcard__body">
                                        <span className="lp-pcard__name">{p.name}</span>
                                        {p.purpose && <span className="lp-pcard__purpose">{p.purpose}</span>}
                                        <span className="lp-pcard__cta">
                                            <span>już od <b>{p.price} zł</b></span>
                                            <b>Wesprzyj →</b>
                                        </span>
                                    </div>
                                </a>
                            ))}
                        </div>
                    )}
                </>
            ) : (
                <p className="lp-empty">
                    W tej kategorii nie ma jeszcze organizacji.<br />
                    Pracujemy nad tym — w międzyczasie zobacz{' '}
                    <a href={cultUrl}>miejsca kultu i organizacje religijne</a>, które już wspieramy.
                </p>
            )}
        </section>
    )
}

Category.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>
