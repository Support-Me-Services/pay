import { useEffect } from 'react'
import { useForm, usePage } from '@inertiajs/react'
import GatewayPublicLayout from '@/Layouts/GatewayPublicLayout'

/**
 * Landing bramki (/) — marketing „sprzedawaj bez kasy" + formularz kontaktowy (lead).
 * Odwzorowuje gateway/landing.blade.php 1:1. Formularz przez Inertia useForm (POST /lead,
 * redirect → flash lead_ok jako prop leadOk). Honeypot `website` (boty). Walidacja: form.errors.
 */
export default function Landing() {
    const { leadOk } = usePage().props
    const form = useForm({ name: '', email: '', phone: '', company: '', message: '', website: '' })

    // Po udanym zgłoszeniu (redirect) przewiń do sekcji kontaktu — jak withFragment('kontakt').
    useEffect(() => {
        if (leadOk) document.getElementById('kontakt')?.scrollIntoView()
    }, [leadOk])

    const submit = (e) => {
        e.preventDefault()
        form.post('/lead')
    }

    const err = form.errors

    return (
        <>
            <section className="hero">
                <div className="container">
                    <div className="hero-tile">
                        <span className="hero-badge">Bramka płatności NFC · płatności BLIK przez PayU</span>
                        <h1>Sprzedawaj bez kasy i&nbsp;bez obsługi.<br />
                            <span className="highlight">Tag NFC + telefon klienta</span> = cała Twoja kasa.</h1>
                        <p className="mt-2">Klient przykłada telefon do taga przy produkcie, płaci BLIK-iem w aplikacji swojego banku
                            i zabiera towar. Bez kolejki, bez terminala, bez pracownika. Ty widzisz każdą sprzedaż na żywo.</p>
                        <div className="hero-actions mt-3">
                            <a href="#kontakt" className="btn btn-primary">Umów bezpłatną wycenę</a>
                            <a href="https://please-support-me.com" target="_blank" rel="noopener" className="btn btn-outline-light">Zobacz sklep demo →</a>
                        </div>
                        <p className="hero-note">Wdrożenie od 5 dni roboczych · bez umowy na czas określony</p>
                    </div>
                </div>
            </section>

            {/* Pasek zaufania / liczb */}
            <section className="lp-stats" aria-label="Najważniejsze liczby">
                <div className="container lp-stats-row">
                    <div className="lp-stat">
                        <div className="lp-stat-value">~15 s</div>
                        <div className="lp-stat-label">od skanu do zapłaty</div>
                    </div>
                    <div className="lp-stat">
                        <div className="lp-stat-value">0 zł</div>
                        <div className="lp-stat-label">za terminal i sprzęt kasowy</div>
                    </div>
                    <div className="lp-stat">
                        <div className="lp-stat-value">100%</div>
                        <div className="lp-stat-label">transakcji online, bez gotówki</div>
                    </div>
                    <div className="lp-stat">
                        <div className="lp-stat-value">24/7</div>
                        <div className="lp-stat-label">punkt sprzedaje także w nocy</div>
                    </div>
                </div>
            </section>

            <section className="section">
                <div className="container">
                    <h2>Jak to działa</h2>
                    <div className="steps-grid mt-3">
                        <div className="step-card">
                            <div className="step-no">1</div>
                            <div className="step-icon">📲</div>
                            <h3>Skanuje</h3>
                            <p className="text-muted mb-0">Klient przykłada telefon do taga NFC przy produkcie. Otwiera się strona
                                produktu z ceną — bez instalowania żadnej aplikacji.</p>
                        </div>
                        <div className="step-card">
                            <div className="step-no">2</div>
                            <div className="step-icon">🏦</div>
                            <h3>Płaci w aplikacji banku</h3>
                            <p className="text-muted mb-0">Jedno kliknięcie i telefon przechodzi do płatności BLIK w aplikacji
                                banku klienta. Pieniądze od razu lecą na Twoje konto.</p>
                        </div>
                        <div className="step-card">
                            <div className="step-no">3</div>
                            <div className="step-icon">✅</div>
                            <h3>Zabiera towar</h3>
                            <p className="text-muted mb-0">Po opłaceniu klient widzi ekran „Możesz zabrać towar" z instrukcją
                                odbioru. Transakcja zapisuje się w Twoim panelu.</p>
                        </div>
                    </div>
                    <div className="demo-row mt-3">
                        <span className="text-muted">Sprawdź na własnym telefonie:</span>
                        <a href="https://please-support-me.com" target="_blank" rel="noopener" className="btn btn-secondary btn-sm">Demo: sklep klasyczny</a>
                    </div>
                </div>
            </section>

            <section className="section section-alt">
                <div className="container">
                    <h2>Dla kogo</h2>
                    <p className="text-muted">Wszędzie tam, gdzie pracownik przy kasie kosztuje więcej, niż zarabia punkt:</p>
                    <div className="chips mt-2">
                        <span className="chip">🍦 Lody i mrożonki</span>
                        <span className="chip">🍯 Miody prosto z pasieki</span>
                        <span className="chip">💐 Kwiaty i bukiety</span>
                        <span className="chip">🥕 Stoiska z warzywami</span>
                        <span className="chip">🔌 Wypożyczalnie (powerbanki, parasole)</span>
                        <span className="chip">🤖 Vending bez automatu</span>
                    </div>
                    <a href="#kontakt" className="btn btn-primary mt-3">Sprawdź, czy pasuje do Twojego punktu</a>
                </div>
            </section>

            {/* Case studies */}
            <section className="section" id="wdrozenia">
                <div className="container">
                    <h2>Przykładowe wdrożenia</h2>
                    <p className="text-muted">Tak SupportME zarabia w punktach pilotażowych. Liczby z trzech scenariuszy
                        wdrożeniowych — Twoje policzymy indywidualnie.</p>
                    <div className="case-grid mt-3">
                        <div className="case-card">
                            <div className="case-icon">🍦</div>
                            <h3>Lodziarnia czynna także nocą</h3>
                            <p className="text-muted">Zamrażarka z lodami przed lokalem, każdy smak z własnym tagiem NFC.
                                Po zamknięciu lokalu sprzedaż nie ustaje — klienci kupują sami.</p>
                            <ul className="case-stats">
                                <li><strong>+38%</strong> sprzedaży dzięki godzinom 21:00–7:00</li>
                                <li><strong>~3 900 zł</strong> dodatkowego utargu miesięcznie</li>
                                <li><strong>0</strong> dodatkowych etatów</li>
                            </ul>
                            <span className="badge badge-muted">przykładowe wdrożenie pilotażowe</span>
                        </div>
                        <div className="case-card">
                            <div className="case-icon">🍯</div>
                            <h3>Pasieka przy drodze krajowej</h3>
                            <p className="text-muted">Stragan z miodami 9 km od domu pszczelarza. Wcześniej „puszka na zaufanie",
                                teraz każdy słoik płacony BLIK-iem przed zabraniem.</p>
                            <ul className="case-stats">
                                <li><strong>92%</strong> transakcji opłaconych (vs ~60% przy puszce)</li>
                                <li><strong>2 h dziennie</strong> mniej dojazdów po gotówkę</li>
                                <li><strong>SMS</strong> przy każdej sprzedaży — pełna kontrola zdalna</li>
                            </ul>
                            <span className="badge badge-muted">przykładowe wdrożenie pilotażowe</span>
                        </div>
                        <div className="case-card">
                            <div className="case-icon">⛱️</div>
                            <h3>Wypożyczalnia parasoli w hotelu</h3>
                            <p className="text-muted">Stojak z parasolami przy basenie. Gość skanuje tag, płaci kaucję BLIK-iem,
                                oddaje parasol — kaucja wraca automatycznie.</p>
                            <ul className="case-stats">
                                <li><strong>~1 etat</strong> recepcji odzyskany w sezonie</li>
                                <li><strong>87%</strong> konwersji od skanu do płatności</li>
                                <li><strong>−70%</strong> zaginięć sprzętu dzięki kaucjom</li>
                            </ul>
                            <span className="badge badge-muted">przykładowe wdrożenie pilotażowe</span>
                        </div>
                    </div>
                </div>
            </section>

            {/* Bezpieczeństwo */}
            <section className="section section-dark" id="bezpieczenstwo">
                <div className="container">
                    <h2>Bezpieczeństwo</h2>
                    <p className="sec-lead">Sprzedaż bezobsługowa działa tylko wtedy, gdy pieniądze i punkt są chronione.
                        Dlatego pod spodem jest infrastruktura klasy bankowej:</p>
                    <div className="sec-grid mt-3">
                        <div className="sec-card">
                            <div className="sec-icon">🏛️</div>
                            <h3>Licencjonowany operator płatności</h3>
                            <p>Transakcje obsługuje PayU — krajowa instytucja płatnicza nadzorowana przez KNF.
                                My nie dotykamy Twoich pieniędzy.</p>
                        </div>
                        <div className="sec-card">
                            <div className="sec-icon">🔐</div>
                            <h3>Szyfrowanie TLS</h3>
                            <p>Cała komunikacja klient–sklep–bramka–operator jest szyfrowana. Żadna płatność
                                nie przechodzi otwartym kanałem.</p>
                        </div>
                        <div className="sec-card">
                            <div className="sec-icon">✍️</div>
                            <h3>Webhooki podpisane HMAC-SHA256</h3>
                            <p>Każde potwierdzenie płatności jest kryptograficznie podpisane. Nie da się „podrobić"
                                opłaconego zamówienia.</p>
                        </div>
                        <div className="sec-card">
                            <div className="sec-icon">🛡️</div>
                            <h3>Moduł AntiTheft</h3>
                            <p>System cyklicznie weryfikuje, czy przy Twoim punkcie nie pojawiły się obce tagi NFC
                                podszywające się pod Twoje produkty.</p>
                        </div>
                        <div className="sec-card">
                            <div className="sec-icon">💳</div>
                            <h3>Zero danych karty u Ciebie</h3>
                            <p>Klient płaci w aplikacji własnego banku. Po stronie sklepu nie ma żadnych danych
                                kart ani danych logowania.</p>
                        </div>
                        <div className="sec-card">
                            <div className="sec-icon">🇪🇺</div>
                            <h3>Zgodność z RODO</h3>
                            <p>Minimalny zakres danych, jasny cel przetwarzania, serwery w UE. Dostajesz gotowe
                                zapisy do swojej polityki prywatności.</p>
                        </div>
                    </div>
                </div>
            </section>

            {/* Cennik */}
            <section className="section" id="cennik">
                <div className="container">
                    <h2>Ile to kosztuje</h2>
                    <p className="text-muted">Prosty model: płacisz głównie wtedy, kiedy sprzedajesz. Bez kosztów terminala,
                        bez opłat za sprzęt. Ceny przykładowe dla etapu MVP — finalną ofertę liczymy pod Twój punkt.</p>
                    <div className="pricing-grid mt-3">
                        <div className="price-card">
                            <h3>Start</h3>
                            <div className="price-tag">0 zł <span>/ mies.</span></div>
                            <p className="text-muted">Dla jednego punktu na próbę.</p>
                            <ul className="price-list">
                                <li>prowizja <strong>od 1,9%</strong> od transakcji</li>
                                <li>do 10 tagów NFC w cenie wdrożenia</li>
                                <li>panel sprzedaży na żywo</li>
                                <li>wypłaty na konto D+1</li>
                            </ul>
                            <a href="#kontakt" className="btn btn-secondary btn-block">Zapytaj o Start</a>
                        </div>
                        <div className="price-card price-featured">
                            <span className="badge badge-brand">najczęściej wybierany</span>
                            <h3>Standard</h3>
                            <div className="price-tag">od 99 zł <span>/ mies.</span></div>
                            <p className="text-muted">Dla punktu, który sprzedaje codziennie.</p>
                            <ul className="price-list">
                                <li>prowizja <strong>od 0,9%</strong> od transakcji</li>
                                <li>nielimitowane tagi i produkty</li>
                                <li>moduł AntiTheft + alerty SMS/e-mail</li>
                                <li>kaucje i wypożyczenia (app2app)</li>
                                <li>priorytetowe wsparcie</li>
                            </ul>
                            <a href="#kontakt" className="btn btn-primary btn-block">Umów wycenę</a>
                        </div>
                    </div>
                    <p className="small text-muted mt-2">Do prowizji SupportME dochodzi standardowa opłata operatora płatności (PayU).
                        Podane stawki są przykładowe i zależą od wolumenu transakcji.</p>
                </div>
            </section>

            {/* FAQ */}
            <section className="section section-alt" id="faq">
                <div className="container" style={{ maxWidth: 820 }}>
                    <h2>Częste pytania</h2>
                    <div className="faq mt-3">
                        <details className="faq-item">
                            <summary>Co jeśli klient nie ma NFC w telefonie?</summary>
                            <p>Obok każdego taga umieszczamy kod QR z tym samym linkiem. Skan aparatem działa na każdym
                                smartfonie z 2015+ roku. W praktyce NFC ma dziś ponad 95% telefonów w Polsce.</p>
                        </details>
                        <details className="faq-item">
                            <summary>Co z kradzieżami? Przecież nikt nie pilnuje towaru.</summary>
                            <p>Sprzedaż bezobsługowa działa od lat (sklepy autonomiczne, stragany „na zaufanie") — straty są
                                zwykle niższe niż koszt jednego etatu. Dodatkowo: ekran „Możesz zabrać towar" widzi tylko ten,
                                kto zapłacił, moduł AntiTheft pilnuje, czy ktoś nie podmienił tagów, a punkt można objąć
                                zwykłym monitoringiem. Każdą transakcję widzisz na żywo, więc rozbieżność stanu wykryjesz od razu.</p>
                        </details>
                        <details className="faq-item">
                            <summary>Jak szybko mogę wystartować?</summary>
                            <p>Standardowe wdrożenie to 5–10 dni roboczych: konfiguracja sklepu i produktów, podpięcie konta
                                PayU, zaprogramowanie i wysyłka tagów NFC. Tagi przyklejasz sam — to naklejki.</p>
                        </details>
                        <details className="faq-item">
                            <summary>Czy potrzebuję terminala płatniczego albo kasy fiskalnej?</summary>
                            <p>Terminal — nie, kasą jest telefon klienta. Kwestię kasy fiskalnej reguluje Twoja forma sprzedaży;
                                płatności bezgotówkowe z ewidencją transakcji często korzystają ze zwolnień — podpowiemy,
                                jak to wygląda w Twoim przypadku, a panel eksportuje pełną ewidencję sprzedaży.</p>
                        </details>
                        <details className="faq-item">
                            <summary>Jak i kiedy otrzymuję pieniądze?</summary>
                            <p>Płatności trafiają na Twoje konto w PayU i są wypłacane na rachunek bankowy — standardowo
                                następnego dnia roboczego (D+1). W panelu widzisz każdą transakcję w momencie zapłaty.</p>
                        </details>
                        <details className="faq-item">
                            <summary>Czy klient musi mieć internet w telefonie?</summary>
                            <p>Tak — strona produktu i płatność BLIK wymagają transmisji danych. Tag NFC sam w sobie nie
                                potrzebuje zasilania ani internetu. Przy punktach w słabym zasięgu pomagamy dobrać lokalizację
                                tagów lub doradzamy mikro-hotspot.</p>
                        </details>
                        <details className="faq-item">
                            <summary>Czy mogę sprzedawać kilka produktów w różnych cenach?</summary>
                            <p>Tak. Każdy produkt (albo każda półka, smak, rozmiar) dostaje własny tag z własną ceną i zdjęciem.
                                Ceny zmieniasz w panelu w kilka sekund — bez wymiany tagów.</p>
                        </details>
                    </div>
                </div>
            </section>

            {/* CTA band */}
            <section className="cta-band">
                <div className="container">
                    <h2>Policzymy, ile zarobi Twój punkt bez obsługi</h2>
                    <p>Bezpłatna analiza: koszt wdrożenia, prognoza sprzedaży, zwrot z inwestycji. Bez zobowiązań.</p>
                    <a href="#kontakt" className="btn btn-light">Zostaw kontakt — odpowiadamy w 1 dzień</a>
                </div>
            </section>

            <section className="section" id="kontakt">
                <div className="container" style={{ maxWidth: 640 }}>
                    <h2>Zostaw kontakt</h2>
                    <p className="text-muted">Opisz swój punkt sprzedaży — odezwiemy się i policzymy, ile zaoszczędzisz.</p>

                    {leadOk && (
                        <div className="alert alert-success">Dziękujemy! Twoje zgłoszenie dotarło — odezwiemy się w ciągu 1 dnia roboczego.</div>
                    )}

                    {Object.keys(err).length > 0 && (
                        <div className="alert alert-error">Popraw zaznaczone pola formularza.</div>
                    )}

                    <form method="POST" onSubmit={submit}>
                        {/* Honeypot antyspamowy — pole niewidoczne dla ludzi */}
                        <div style={{ position: 'absolute', left: -9999 }} aria-hidden="true">
                            <label htmlFor="website">Strona WWW</label>
                            <input type="text" id="website" name="website" tabIndex={-1} autoComplete="off"
                                value={form.data.website} onChange={(e) => form.setData('website', e.target.value)} />
                        </div>

                        <div className="form-group">
                            <label htmlFor="name">Imię i nazwisko *</label>
                            <input type="text" id="name" name="name" value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)} required />
                            {err.name && <div className="form-error">{err.name}</div>}
                        </div>
                        <div className="form-group">
                            <label htmlFor="email">E-mail *</label>
                            <input type="email" id="email" name="email" value={form.data.email}
                                onChange={(e) => form.setData('email', e.target.value)} required />
                            {err.email && <div className="form-error">{err.email}</div>}
                        </div>
                        <div className="form-group">
                            <label htmlFor="phone">Telefon *</label>
                            <input type="tel" id="phone" name="phone" value={form.data.phone}
                                onChange={(e) => form.setData('phone', e.target.value)} required />
                            {err.phone && <div className="form-error">{err.phone}</div>}
                        </div>
                        <div className="form-group">
                            <label htmlFor="company">Firma (opcjonalnie)</label>
                            <input type="text" id="company" name="company" value={form.data.company}
                                onChange={(e) => form.setData('company', e.target.value)} />
                            {err.company && <div className="form-error">{err.company}</div>}
                        </div>
                        <div className="form-group">
                            <label htmlFor="message">Wiadomość *</label>
                            <textarea id="message" name="message" rows="4" value={form.data.message}
                                onChange={(e) => form.setData('message', e.target.value)} required></textarea>
                            {err.message && <div className="form-error">{err.message}</div>}
                        </div>
                        <button type="submit" className="btn btn-primary btn-block" disabled={form.processing}>Wyślij zgłoszenie</button>
                    </form>
                </div>
            </section>
        </>
    )
}

Landing.layout = (page) => <GatewayPublicLayout>{page}</GatewayPublicLayout>
