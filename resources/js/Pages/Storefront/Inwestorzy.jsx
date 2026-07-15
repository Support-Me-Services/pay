import StorefrontLayout from '@/Layouts/StorefrontLayout'

/** Strona „Inwestorzy i akcjonariusze" (statyczna, wg Figmy). */
export default function Inwestorzy({ css }) {
    return (
        <>
            <section className="inv-hero">
                <h1>Inwestorzy i akcjonariusze</h1>
            </section>

            <section className="inv-intro">
                <h2>Wierzymy, że kapitał<br />może służyć dobru!</h2>
                <p>Nasi inwestorzy wspierają nie tylko projekt technologiczny - wspierają inicjatywę, która ułatwia pomaganie, wzmacnia organizacje społeczne oraz wykorzystuje nowoczesne technologie do budowania pozytywnego wpływu.</p>
                <p>Naszym celem jest <strong>tworzenie rozwiązań, które łączą innowacje, odpowiedzialność i realne korzyści dla społeczeństwa.</strong></p>
            </section>

            <section className="inv-share">
                <h2 className="inv-share__h">Akcjonariusze</h2>
                <div className="inv-cards">
                    <div className="inv-card">
                        <div className="inv-card__avatar"></div>
                        <div className="inv-card__name">PAWEŁ KOZŁOWSKI</div>
                        <p className="inv-card__meta">Inwestycja kapitałowa: 100 000 zł<br />Wsparcie usługowe: 900 000 zł</p>
                    </div>
                    <div className="inv-card">
                        <div className="inv-card__avatar"></div>
                        <div className="inv-card__name">MARCIN LULA</div>
                        <p className="inv-card__meta">Inwestycja kapitałowa: 10 000 zł</p>
                    </div>
                </div>
            </section>
        </>
    )
}

Inwestorzy.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>
