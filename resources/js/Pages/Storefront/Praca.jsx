import StorefrontLayout from '@/Layouts/StorefrontLayout'

/** Lista ofert pracy (/praca). */
export default function Praca({ positions }) {
    return (
        <>
            <section className="sp-subhero">
                <div className="sp-subhero__inner"><h1>Pracuj z nami!</h1></div>
            </section>

            <div className="sp-wrap">
                <div className="sp-container sp-careers">
                    <div className="sp-careers__head">
                        <div className="sp-circle sp-circle--lg sp-careers__photo">
                            <img src="/img/landing/praca.jpg" alt="Zespół SupportME przy pracy" loading="lazy" />
                        </div>
                        <div className="sp-careers__head-body">
                            <h2>Tworzymy coś dobrego — razem</h2>
                            <p>Szukamy osób, które chcą łączyć technologię z realnym wpływem na świat. Budujemy płatności NFC, które pomagają parafiom, fundacjom i lokalnym inicjatywom zbierać wsparcie prościej niż kiedykolwiek. Rozwiń ofertę i wyślij zgłoszenie wraz z CV — odezwiemy się.</p>
                        </div>
                    </div>

                    <div className="sp-jobs">
                        {positions.length === 0 && <p className="sp-job--empty">Obecnie nie prowadzimy rekrutacji. Zapraszamy ponownie wkrótce.</p>}
                        {positions.map((p) => (
                            <article className="sp-job sp-job--rich" id={`oferta-${p.id}`} key={p.id}>
                                <h2>{p.title}</h2>
                                <div className="sp-job__chips">
                                    {p.employment_type && <span className="sp-job__chip">{p.employment_type}</span>}
                                    {p.location && <span className="sp-job__chip">{p.location}</span>}
                                    {p.is_remote && <span className="sp-job__chip sp-job__chip--remote">Praca zdalna</span>}
                                </div>
                                <p className="sp-job__excerpt">{p.excerpt}</p>
                                <div className="sp-job__foot">
                                    <a className="sp-btn sp-btn--cta" href={p.show_url}>Zobacz więcej</a>
                                </div>
                            </article>
                        ))}
                    </div>
                </div>
            </div>
        </>
    )
}

Praca.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>
