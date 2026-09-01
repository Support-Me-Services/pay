import { useEffect, useState } from 'react'
import StorefrontLayout from '@/Layouts/StorefrontLayout'

const CSS = `
.ty-overlay{ position:fixed; inset:0; z-index:1200; display:flex; align-items:center; justify-content:center; padding:20px; background:rgba(20,30,48,.55); -webkit-backdrop-filter:blur(3px); backdrop-filter:blur(3px); }
.ty-modal{ position:relative; width:100%; max-width:540px; background:#fff; border-radius:24px; box-shadow:0 24px 70px rgba(20,30,48,.35); padding:38px 34px 24px; font-family:var(--lp-ui,'Inter',system-ui,sans-serif); color:var(--lp-ink,#24324A); animation:tyPop .25s ease; }
@keyframes tyPop{ from{ opacity:0; transform:translateY(12px) scale(.98); } to{ opacity:1; transform:none; } }
.ty-close{ position:absolute; top:14px; right:16px; width:38px; height:38px; border:0; background:#EEF3F8; color:#24324A; border-radius:50%; font-size:1.5rem; line-height:1; cursor:pointer; }
.ty-close:hover{ background:#dfe8f1; }
.ty-heart{ display:block; margin-bottom:6px; }
.ty-title{ font-family:var(--lp-display,'Libre Baskerville',Georgia,serif); font-size:1.65rem; margin:4px 0 16px; color:var(--lp-ink,#24324A); line-height:1.2; }
.ty-body p{ margin:0 0 12px; font-size:1.02rem; line-height:1.55; color:#3a4961; }
.ty-sign{ font-weight:700; color:var(--lp-ink,#24324A); margin-top:4px; }
.ty-patrons{ display:flex; align-items:center; justify-content:flex-end; flex-wrap:wrap; gap:10px; margin-top:20px; padding-top:16px; border-top:1px solid #E6EAF0; }
.ty-patrons__label{ font-size:.74rem; color:#90a0b3; font-weight:700; text-transform:uppercase; letter-spacing:.08em; }
.ty-patron{ display:inline-flex; text-decoration:none; align-items:center; gap:7px; background:#F4F7FA; border:1px solid #E6EAF0; border-radius:999px; padding:6px 13px; font-weight:700; font-size:.86rem; color:#24324A; }
.ty-patron img{ height:22px; width:auto; display:block; }
@media(max-width:520px){ .ty-modal{ padding:30px 22px 20px; } .ty-title{ font-size:1.4rem; } }
`

// Kafelki „Kogo wspieramy?" — statyczne (system Kategorie usunięty), wszystkie
// prowadzą do podstrony „Wspieramy" (beneficiariesUrl).
const CATEGORIES = [
    { slug: 'organizacje-spoleczne', icon: 'category-icons/organizacje-spoleczne.jpg', label: 'Organizacje społeczne' },
    { slug: 'fundacje-i-stowarzyszenia', icon: 'category-icons/fundacje-i-stowarzyszenia.jpg', label: 'Fundacje <br>i stowarzyszenia' },
    { slug: 'wspolnoty-lokalne', icon: 'category-icons/wspolnoty-lokalne.jpg', label: 'Wspólnoty lokalne' },
    { slug: 'miejsca-kultu', icon: 'category-icons/miejsca-kultu.jpg', label: 'Miejsca kultu <br>i organizacje religijne' },
    { slug: 'inicjatywy-charytatywne', icon: 'category-icons/inicjatywy-charytatywne.jpg', label: 'Inicjatywy charytatywne' },
    { slug: 'projekty-spoleczne-i-edukacyjne', icon: 'category-icons/projekty-spoleczne-i-edukacyjne.jpg', label: 'Projekty społeczne <br>i edukacyjne' },
]

const STEPS = [
    { num: 'KROK 1', g: 'g1', rev: false, body: <p><b>Zbliż telefon do znacznika NFC</b><br />W miejscu zbiórki, podczas wydarzenia, w organizacji lub przestrzeni publicznej zbliżasz telefon do oznaczonego punktu NFC.</p> },
    { num: 'KROK 2', g: 'g2', rev: true, body: <><p><b>Wybierz wsparcie</b><br />Automatycznie otwiera się bezpieczna strona płatności.</p><p>Zobaczysz:<br />• nazwę organizacji lub instytucji<br />• cel zbiórki<br />• sugerowaną kwotę wsparcia<br />• możliwość wpisania własnej kwoty</p></> },
    { num: 'KROK 3', g: 'g3', rev: false, body: <p><b>Dokonaj płatności w wygodny dla Ciebie sposób</b></p> },
    { num: 'KROK 4', g: 'g4', rev: true, body: <><p><b>Otrzymujesz podziękowanie</b><br />Po zakończonej wpłacie trafiasz na specjalnie przygotowaną stronę podziękowania.</p><p><br />Może tam na Ciebie czekać:<br />• wiadomość od organizacji<br />• podziękowanie od zespołu lub opiekuna projektu<br />• zdjęcie z realizowanej inicjatywy<br />• informacja o wykorzystaniu środków<br />• inspirujący cytat lub krótka historia związana z projektem</p></> },
]

/** Strona główna marketingowa (/main) — kafelki kategorii + „Jak to działa" + modal podziękowania. */
export default function Home({ beneficiariesUrl, showThanks, mecenasLogo, mecenasUrl, mainUrl, itemThanks }) {
    const [thanks, setThanks] = useState(showThanks)

    useEffect(() => {
        if (!thanks) return
        document.body.style.overflow = 'hidden'
        const onKey = (e) => { if (e.key === 'Escape') close() }
        document.addEventListener('keydown', onKey)
        return () => { document.body.style.overflow = ''; document.removeEventListener('keydown', onKey) }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [thanks])

    const close = () => {
        setThanks(false)
        try { history.replaceState({}, '', mainUrl) } catch (e) { /* noop */ }
    }

    return (
        <>
            <style>{CSS}</style>

            <section className="lp-hero">
                <div className="lp-hero__inner">
                    <div className="lp-hero__copy">
                        <h1>Technologia, <br className="br-m" />która <br className="br-d" />pomaga czynić dobro</h1>
                        <p>Łączymy ludzi, wartości<br className="br-m" /> i nowoczesne płatności,<br />aby wspieranie ważnych inicjatyw było prostsze<br className="br-d" /> niż kiedykolwiek wcześniej.</p>
                        <div className="lp-badges">
                            <a href="#"><img src="/img/landing/appstore.png" alt="Pobierz w App Store" /></a>
                            <a href="#"><img src="/img/landing/googleplay.png" alt="Pobierz w Google Play" /></a>
                        </div>
                    </div>
                    <div className="lp-hero__art">
                        <div className="lp-circle lp-circle--hero lp-circle--ghero" aria-hidden="true"></div>
                    </div>
                </div>
            </section>

            <section className="lp-section lp-support" id="kogo-wspieramy">
                <div className="lp-head">
                    <h2>Kogo wspieramy?</h2>
                    <p>Każdego dnia tysiące osób chce pomagać, ale często brakuje prostych i wygodnych narzędzi do działania. Tworzymy rozwiązanie, które umożliwia wsparcie jednym zbliżeniem telefonu.</p>
                </div>
                <div className="lp-cats">
                    {CATEGORIES.map((cat) => (
                        <a className="lp-cat" href={beneficiariesUrl} key={cat.slug}>
                            <span className="lp-cat__disc"><img className="lp-cat__disc-img" src={`/storage/${cat.icon}`} alt="" /></span>
                            <span className="lp-cat__label" dangerouslySetInnerHTML={{ __html: cat.label }} />
                        </a>
                    ))}
                </div>
            </section>

            <section className="lp-section lp-how">
                <div className="lp-head">
                    <h2>Jak to działa?</h2>
                    <p>Jedno zbliżenie telefonu. Kilka sekund. Realna pomoc.</p>
                </div>
                <div className="lp-steps">
                    {STEPS.map((s) => (
                        <div className={`lp-step${s.rev ? ' lp-step--rev' : ''}`} key={s.num}>
                            <div className="lp-step__art"><div className={`lp-circle lp-circle--step lp-circle--${s.g}`} aria-hidden="true"></div></div>
                            <div className="lp-step__body">
                                <p className="lp-step__num">{s.num}</p>
                                {s.body}
                            </div>
                        </div>
                    ))}
                </div>
            </section>

            <section className="lp-closing">
                <p>Tworzymy technologię, która wspiera ludzi w pomaganiu i zamienianiu dobrych intencji w realną pomoc.<br /><br /><b>Dobro przekazane dalej ma największą wartość.</b></p>
            </section>

            {thanks && (
                <div className="ty-overlay" role="dialog" aria-modal="true" aria-labelledby="tyTitle" onClick={(e) => { if (e.target === e.currentTarget) close() }}>
                    <div className="ty-modal">
                        <button type="button" className="ty-close" aria-label="Zamknij" onClick={close}>&times;</button>
                        <svg className="ty-heart" width="44" height="44" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 21s-7.4-4.55-9.9-9.2C.7 8.7 2.2 5 5.6 5c2 0 3.3 1.05 4.4 2.55C11.1 6.05 12.4 5 14.4 5c3.4 0 4.9 3.7 3.5 6.8C19.4 16.45 12 21 12 21z" fill="#1473C0" />
                        </svg>
                        <h2 className="ty-title" id="tyTitle">{itemThanks?.heading || 'Dziękujemy za Twoje wsparcie!'}</h2>
                        {itemThanks?.image && (
                            <img src={itemThanks.image} alt="" style={{ maxWidth: '100%', borderRadius: 12, marginBottom: 12 }} />
                        )}
                        <div className="ty-body">
                            {itemThanks?.body ? (
                                itemThanks.body.map((p, i) => <p key={i}>{p}</p>)
                            ) : (
                                <>
                                    <p>Jesteśmy wdzięczni za Twoje wsparcie parafii <strong>Żbików</strong>.</p>
                                    <p>Dzięki Tobie Mecenasi parafii Żbików dodatkowo przekażą datek na rzecz parafii.</p>
                                </>
                            )}
                            <p>Pozostajemy w kontakcie.<br />Będziemy nadal Cię wspierać w pomaganiu.</p>
                            <p className="ty-sign">Zespół Support Me</p>
                        </div>
                        <div className="ty-patrons">
                            <span className="ty-patrons__label">Mecenasi</span>
                            {itemThanks?.mecenasName ? (
                                <a className="ty-patron" href={itemThanks.mecenasUrl || '#'} title={itemThanks.mecenasName}>
                                    {itemThanks.mecenasLogo ? <img src={itemThanks.mecenasLogo} alt={itemThanks.mecenasName} /> : itemThanks.mecenasName}
                                </a>
                            ) : (
                                <a className="ty-patron" href={mecenasUrl} title="LokalnyRolnik">
                                    {mecenasLogo ? <img src={mecenasLogo} alt="LokalnyRolnik" /> : 'LokalnyRolnik'}
                                </a>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </>
    )
}

Home.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>
