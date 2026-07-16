import StorefrontLayout from '@/Layouts/StorefrontLayout'

const CSS = `
.of-hero{ background:linear-gradient(112deg, rgba(78,127,167,.22) 13.6%, rgba(20,115,192,.22) 99.4%); padding:42px 0 46px; }
.of-hero__inner{ max-width:820px; margin:0 auto; padding:0 24px; }
.of-back{ display:inline-block; font-size:14px; color:var(--lp-ink); opacity:.7; margin-bottom:18px; }
.of-back:hover{ opacity:1; }
.of-hero h1{ font-family:var(--lp-display); font-weight:700; font-size:clamp(28px,5vw,40px); line-height:normal; color:var(--lp-ink); margin:0 0 16px; }
.of-tags{ display:flex; flex-wrap:wrap; gap:10px; }
.of-tag{ display:inline-flex; align-items:center; background:#fff; border:1px solid var(--lp-wave); color:var(--lp-ink); font-size:14px; font-weight:600; padding:7px 14px; border-radius:999px; }
.of-tag--remote{ background:#e8f4ed; border-color:#aed5c1; color:#1f5f44; }
.of-body{ max-width:820px; margin:0 auto; padding:48px 24px 24px; }
.of-desc{ font-family:var(--lp-ui); font-size:18px; line-height:1.7; color:var(--lp-ink); overflow-wrap:break-word; word-break:break-word; }
.of-desc h2,.of-desc h3{ font-family:var(--lp-display); font-weight:700; line-height:1.25; color:var(--lp-ink); margin:1.6em 0 .55em; }
.of-desc h3{ font-size:22px; }
.of-desc > :first-child{ margin-top:0; }
.of-desc p{ margin:0 0 1em; }
.of-desc ul{ padding-left:1.25em; margin:0 0 1.1em; } .of-desc li{ margin:.4em 0; }
.of-cta{ margin:40px auto 8px; max-width:820px; padding:0 24px; }
.of-btn{ display:flex; align-items:center; justify-content:center; gap:10px; width:100%; padding:18px 28px; border-radius:16px; font-family:var(--lp-ui); font-size:18px; font-weight:700; color:#fff !important; background:linear-gradient(117deg, var(--lp-blue-a), var(--lp-blue-b)); box-shadow:0 12px 30px rgba(20,115,192,.28); transition:transform .15s ease, box-shadow .15s ease; }
.of-btn:hover{ transform:translateY(-2px); box-shadow:0 18px 38px rgba(20,115,192,.36); }
.of-others{ max-width:1142px; margin:72px auto 0; padding:0 24px 24px; }
.of-others h2{ font-family:var(--lp-display); font-weight:700; font-size:26px; color:var(--lp-ink); text-align:center; margin:0 0 24px; }
.of-grid{ display:grid; grid-template-columns:repeat(3,1fr); gap:18px; }
.of-card{ display:block; background:#fff; border:1px solid #E6EAF0; border-radius:16px; padding:22px; box-shadow:0 8px 26px rgba(36,50,74,.06); transition:transform .15s ease, box-shadow .15s ease; }
.of-card:hover{ transform:translateY(-3px); box-shadow:0 16px 38px rgba(36,50,74,.13); }
.of-card__title{ font-family:var(--lp-display); font-weight:700; font-size:18px; color:var(--lp-ink); margin:0 0 8px; }
.of-card__meta{ font-size:14px; color:#5b6678; }
@media (max-width:820px){ .of-grid{ grid-template-columns:1fr; } }
@media (max-width:560px){ .of-hero{ padding:32px 0 34px; } .of-body{ padding:32px 20px 16px; } .of-desc{ font-size:16px; line-height:1.65; } .of-desc h3{ font-size:20px; } .of-cta{ padding:0 20px; } .of-btn{ padding:16px 20px; font-size:16px; } .of-others{ margin-top:48px; } }
`

const FALLBACK = (
    <>
        <p>Szukamy osoby, która chce współtworzyć technologię wspierającą organizacje, wspólnoty i inicjatywy niosące realną wartość ludziom. Jeżeli szukasz w pracy czegoś więcej niż kolejnego stanowiska — jesteś we właściwym miejscu.</p>
        <p>Pełny opis zakresu obowiązków prześlemy w odpowiedzi na zgłoszenie. Aplikuj poniżej — odezwiemy się.</p>
    </>
)

/** Pojedyncza oferta pracy (/praca/oferta/{position}). */
export default function Oferta({ position, others, careersUrl }) {
    return (
        <>
            <style>{CSS}</style>
            <section className="of-hero">
                <div className="of-hero__inner">
                    <a className="of-back" href={careersUrl}>← Wszystkie oferty</a>
                    <h1>{position.title}</h1>
                    <div className="of-tags">
                        {position.employment_type && <span className="of-tag">{position.employment_type}</span>}
                        {position.location && <span className="of-tag">{position.location}</span>}
                        {position.is_remote && <span className="of-tag of-tag--remote">Praca zdalna</span>}
                    </div>
                </div>
            </section>

            <div className="of-body">
                <div className="of-desc">
                    {position.description_html
                        ? <div dangerouslySetInnerHTML={{ __html: position.description_html }} />
                        : FALLBACK}
                </div>
            </div>

            <div className="of-cta">
                <a className="of-btn" href={position.apply_url}>Aplikuj na to stanowisko →</a>
            </div>

            {others.length > 0 && (
                <section className="of-others">
                    <h2>Inne oferty</h2>
                    <div className="of-grid">
                        {others.map((o) => (
                            <a className="of-card" href={o.show_url} key={o.show_url}>
                                <p className="of-card__title">{o.title}</p>
                                <p className="of-card__meta">{o.meta}</p>
                            </a>
                        ))}
                    </div>
                </section>
            )}
        </>
    )
}

Oferta.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>
