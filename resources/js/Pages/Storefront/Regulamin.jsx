import StorefrontLayout from '@/Layouts/StorefrontLayout'

const M = 'mailto:founder@please-support-me.com'

/** Regulamin Serwisu Internetowego i Przekazywania Darowizn Fundacji SUPPORT ME HAVEN & HEAVEN (treść z PDF, obowiązuje od 29.07.2026). */
export default function Regulamin() {
    return (
        <>
            <section className="sp-subhero">
                <div className="sp-subhero__inner">
                    <p className="sp-eyebrow">Dokumenty</p>
                    <h1>Regulamin serwisu i przekazywania darowizn</h1>
                    <p className="sp-lede">Zasady korzystania z serwisu internetowego oraz przekazywania darowizn online na rzecz realizacji celów statutowych Fundacji.</p>
                </div>
            </section>

            <div className="sp-wrap">
                <div className="sp-container sp-doc sp-legal">
                    <article className="sp-legal__body">
                        <p><strong>Regulamin Serwisu Internetowego i Przekazywania Darowizn</strong> obowiązujący od dnia 29 lipca 2026 r.</p>

                        <section className="sp-legal__section">
                            <h2>§ 1. Postanowienia ogólne</h2>
                            <ol>
                                <li>Niniejszy Regulamin określa zasady korzystania z serwisu internetowego prowadzonego pod adresem <a href="https://www.please-support-me.com" target="_blank" rel="noopener noreferrer">www.please-support-me.com</a> (dalej „Serwis") oraz zasady przekazywania darowizn online na rzecz realizacji celów statutowych FUNDACJI SUPPORT ME HAVEN &amp; HEAVEN.</li>
                                <li>
                                    Właścicielem i operatorem Serwisu jest:
                                    <br />FUNDACJA SUPPORT ME HAVEN &amp; HEAVEN
                                    <br />ul. dr Izabeli Wolfram 11
                                    <br />05-800 Pruszków
                                    <br />KRS: 0001255725
                                    <br />NIP: 5342715041
                                    <br />REGON: 545334957
                                    <br />dalej zwana „Fundacją".
                                </li>
                                <li>Fundacja jest usługodawcą świadczącym usługi drogą elektroniczną w rozumieniu ustawy z dnia 18 lipca 2002 r. o świadczeniu usług drogą elektroniczną.</li>
                                <li>Kontakt z Fundacją możliwy jest pod adresem e-mail: <a href={M}>founder@please-support-me.com</a>.</li>
                                <li>Organami sprawującymi nadzór nad Fundacją są Minister Zdrowia oraz Prezydent Miasta Pruszkowa.</li>
                                <li>
                                    Wszelkie wpłaty realizowane za pośrednictwem Serwisu stanowią darowizny w rozumieniu art. 888 i następnych Kodeksu cywilnego i przeznaczane są na realizację celów statutowych Fundacji, w szczególności:
                                    <ul>
                                        <li>propagowanie i organizowanie wolontariatu;</li>
                                        <li>opiekę i pomoc społeczną;</li>
                                        <li>promocję zatrudnienia osób z niepełnosprawnościami, wykluczonych społecznie lub zagrożonych wykluczeniem społecznym;</li>
                                        <li>rozwój nauki i techniki, w tym cyfryzację oraz nowoczesne rozwiązania technologiczne wspierające działalność dobroczynną;</li>
                                        <li>ochronę zdrowia oraz promocję zdrowego żywienia.</li>
                                    </ul>
                                </li>
                            </ol>
                        </section>

                        <section className="sp-legal__section">
                            <h2>§ 2. Definicje</h2>
                            <p>Na potrzeby niniejszego Regulaminu przyjmuje się następujące definicje:</p>
                            <ol>
                                <li><strong>Serwis</strong> – serwis internetowy dostępny pod adresem www.please-support-me.com.</li>
                                <li><strong>Fundacja</strong> – FUNDACJA SUPPORT ME HAVEN &amp; HEAVEN.</li>
                                <li><strong>Darczyńca</strong> – osoba fizyczna, osoba prawna albo jednostka organizacyjna nieposiadająca osobowości prawnej przekazująca darowiznę na rzecz Fundacji.</li>
                                <li><strong>Darowizna</strong> – dobrowolne, nieodpłatne świadczenie pieniężne przekazane na rzecz Fundacji z przeznaczeniem na realizację jej celów statutowych.</li>
                                <li><strong>Użytkownik</strong> – każda osoba korzystająca z Serwisu.</li>
                            </ol>
                        </section>

                        <section className="sp-legal__section">
                            <h2>§ 3. Usługi świadczone drogą elektroniczną</h2>
                            <ol>
                                <li>
                                    Fundacja świadczy za pośrednictwem Serwisu nieodpłatnie usługi drogą elektroniczną polegające na:
                                    <ul>
                                        <li>udostępnianiu informacji o działalności Fundacji,</li>
                                        <li>udostępnianiu formularza umożliwiającego przekazanie darowizny,</li>
                                        <li>umożliwieniu kontaktu z Fundacją za pośrednictwem Serwisu.</li>
                                    </ul>
                                </li>
                                <li>Umowa o świadczenie usług drogą elektroniczną zostaje zawarta z chwilą rozpoczęcia korzystania z odpowiedniej funkcjonalności Serwisu i ulega rozwiązaniu z chwilą zakończenia korzystania z niej albo opuszczenia Serwisu.</li>
                                <li>
                                    Do korzystania z Serwisu wymagane jest:
                                    <ul>
                                        <li>urządzenie z dostępem do Internetu,</li>
                                        <li>aktualna przeglądarka internetowa,</li>
                                        <li>włączona obsługa JavaScript,</li>
                                        <li>włączona obsługa plików cookies,</li>
                                        <li>aktywny adres poczty elektronicznej (e-mail).</li>
                                    </ul>
                                </li>
                                <li>Użytkownik zobowiązuje się korzystać z Serwisu zgodnie z obowiązującymi przepisami prawa, postanowieniami niniejszego Regulaminu oraz dobrymi obyczajami.</li>
                                <li>Zabrania się dostarczania przez Użytkowników treści o charakterze bezprawnym.</li>
                            </ol>
                        </section>

                        <section className="sp-legal__section">
                            <h2>§ 4. Zasady przekazywania darowizn</h2>
                            <ol>
                                <li>Przekazanie Darowizny ma charakter całkowicie dobrowolny i nie wiąże się z uzyskaniem jakiegokolwiek świadczenia wzajemnego ze strony Fundacji.</li>
                                <li>Darczyńca samodzielnie określa wysokość przekazywanej Darowizny.</li>
                                <li>Darczyńca może wskazać preferowany cel szczegółowy. Jeżeli z przyczyn organizacyjnych lub prawnych realizacja wskazanego celu okaże się niemożliwa, Fundacja może przeznaczyć środki na inny cel statutowy zgodny ze swoim statutem.</li>
                                <li>
                                    W celu przekazania Darowizny Darczyńca:
                                    <ul>
                                        <li>wybiera kwotę Darowizny,</li>
                                        <li>opcjonalnie wskazuje cel szczegółowy,</li>
                                        <li>podaje dane wymagane w formularzu,</li>
                                        <li>akceptuje Regulamin,</li>
                                        <li>zatwierdza przekazanie Darowizny.</li>
                                    </ul>
                                </li>
                                <li>Darowizny przekazywane są w złotych polskich (PLN).</li>
                                <li>Po prawidłowym zakończeniu procesu przekazania Darowizny Fundacja może przesłać Darczyńcy potwierdzenie dokonania wpłaty na wskazany adres e-mail.</li>
                            </ol>
                        </section>

                        <section className="sp-legal__section">
                            <h2>§ 5. Formy płatności i realizacja</h2>
                            <ol>
                                <li>Płatności online realizowane są za pośrednictwem zewnętrznego dostawcy usług płatniczych posiadającego stosowne zezwolenia wymagane przepisami prawa.</li>
                                <li>Po uruchomieniu funkcjonalności płatności online Darczyńca będzie mógł skorzystać z metod płatności udostępnionych przez wybranego dostawcę usług płatniczych.</li>
                                <li>Fundacja nie pobiera od Darczyńców żadnych dodatkowych opłat ani prowizji z tytułu przekazania Darowizny.</li>
                                <li>Dane instrumentów płatniczych, w szczególności dane kart płatniczych, nie są przetwarzane ani przechowywane przez Fundację.</li>
                                <li>Środki przekazane przez Darczyńców przeznaczane są na realizację celów statutowych Fundacji.</li>
                                <li>Fundacja nie ponosi odpowiedzialności za przerwy techniczne lub awarie systemów teleinformatycznych dostawcy usług płatniczych pozostające poza jej kontrolą.</li>
                            </ol>
                        </section>

                        <section className="sp-legal__section">
                            <h2>§ 6. Reklamacje i zwroty</h2>
                            <ol>
                                <li>Umowa darowizny nie stanowi umowy sprzedaży ani umowy o świadczenie usług zawieranej z konsumentem w rozumieniu przepisów ustawy o prawach konsumenta. W związku z tym prawo odstąpienia od umowy zawartej na odległość nie ma zastosowania.</li>
                                <li>W przypadku błędów technicznych przy realizacji płatności lub omyłkowego przekazania środków Darczyńca może złożyć reklamację.</li>
                                <li>Reklamacje dotyczące funkcjonowania Serwisu lub świadczonych usług drogą elektroniczną mogą być składane drogą elektroniczną na adres: <a href={M}>founder@please-support-me.com</a>.</li>
                                <li>
                                    Reklamacja powinna zawierać:
                                    <ul>
                                        <li>dane Darczyńcy,</li>
                                        <li>datę dokonania wpłaty,</li>
                                        <li>kwotę wpłaty,</li>
                                        <li>identyfikator transakcji (jeżeli został nadany),</li>
                                        <li>opis problemu.</li>
                                    </ul>
                                </li>
                                <li>Fundacja rozpatruje reklamację w terminie 14 dni kalendarzowych od dnia jej otrzymania.</li>
                                <li>W przypadku uznania reklamacji zwrot środków nastąpi na rachunek bankowy, z którego dokonano płatności, o ile będzie to uzasadnione okolicznościami sprawy.</li>
                            </ol>
                        </section>

                        <section className="sp-legal__section">
                            <h2>§ 7. Ochrona danych osobowych</h2>
                            <ol>
                                <li>Administratorem danych osobowych jest FUNDACJA SUPPORT ME HAVEN &amp; HEAVEN z siedzibą w Pruszkowie.</li>
                                <li>Dane osobowe przetwarzane są zgodnie z Rozporządzeniem Parlamentu Europejskiego i Rady (UE) 2016/679 (RODO).</li>
                                <li>
                                    Dane przetwarzane są w celu:
                                    <ul>
                                        <li>realizacji przekazanej Darowizny,</li>
                                        <li>wykonania obowiązków wynikających z przepisów prawa,</li>
                                        <li>prowadzenia dokumentacji księgowej i rachunkowej,</li>
                                        <li>dochodzenia lub obrony przed ewentualnymi roszczeniami.</li>
                                    </ul>
                                </li>
                                <li>Podanie danych jest dobrowolne, jednak niezbędne do realizacji Darowizny oraz wystawienia potwierdzenia jej przekazania.</li>
                                <li>
                                    Dane mogą zostać przekazane:
                                    <ul>
                                        <li>dostawcy usług płatniczych,</li>
                                        <li>dostawcom usług informatycznych,</li>
                                        <li>biuru rachunkowemu,</li>
                                        <li>podmiotom uprawnionym na podstawie przepisów prawa.</li>
                                    </ul>
                                </li>
                                <li>Dane będą przechowywane przez okres wymagany przepisami prawa podatkowego i rachunkowego, a następnie przez okres przedawnienia ewentualnych roszczeń.</li>
                                <li>
                                    Osobie, której dane dotyczą, przysługuje prawo:
                                    <ul>
                                        <li>dostępu do danych,</li>
                                        <li>sprostowania danych,</li>
                                        <li>usunięcia danych,</li>
                                        <li>ograniczenia przetwarzania,</li>
                                        <li>przenoszenia danych,</li>
                                        <li>wniesienia sprzeciwu wobec przetwarzania danych prowadzonego na podstawie prawnie uzasadnionego interesu Administratora,</li>
                                        <li>wniesienia skargi do Prezesa Urzędu Ochrony Danych Osobowych.</li>
                                    </ul>
                                </li>
                                <li>Szczegółowe zasady przetwarzania danych osobowych oraz wykorzystywania plików cookies określa <a href="/polityka-prywatnosci.pdf" target="_blank" rel="noopener noreferrer">Polityka Prywatności Serwisu</a>.</li>
                            </ol>
                        </section>

                        <section className="sp-legal__section">
                            <h2>§ 8. Pliki cookies</h2>
                            <ol>
                                <li>Serwis wykorzystuje pliki cookies niezbędne do prawidłowego funkcjonowania strony internetowej oraz realizacji procesu przekazywania Darowizn.</li>
                                <li>Korzystanie z Serwisu może wymagać zapisania plików cookies na urządzeniu Użytkownika.</li>
                                <li>Szczegółowe informacje dotyczące plików cookies znajdują się w <a href="/polityka-prywatnosci.pdf" target="_blank" rel="noopener noreferrer">Polityce Prywatności</a>.</li>
                            </ol>
                        </section>

                        <section className="sp-legal__section">
                            <h2>§ 9. Odpowiedzialność</h2>
                            <ol>
                                <li>Fundacja dokłada należytej staranności w celu zapewnienia prawidłowego działania Serwisu.</li>
                                <li>
                                    Fundacja nie ponosi odpowiedzialności za przerwy w funkcjonowaniu Serwisu spowodowane:
                                    <ul>
                                        <li>działaniem siły wyższej,</li>
                                        <li>awariami sieci telekomunikacyjnych,</li>
                                        <li>awariami dostawcy usług płatniczych,</li>
                                        <li>pracami konserwacyjnymi lub technicznymi.</li>
                                    </ul>
                                </li>
                                <li>Fundacja nie ponosi odpowiedzialności za szkody wynikające z korzystania z Serwisu w sposób sprzeczny z obowiązującym prawem lub niniejszym Regulaminem.</li>
                            </ol>
                        </section>

                        <section className="sp-legal__section">
                            <h2>§ 10. Postanowienia końcowe</h2>
                            <ol>
                                <li>Językiem stosowanym przy zawieraniu umów za pośrednictwem Serwisu jest język polski.</li>
                                <li>
                                    W sprawach nieuregulowanych niniejszym Regulaminem zastosowanie mają przepisy prawa polskiego, w szczególności:
                                    <ul>
                                        <li>Kodeks cywilny,</li>
                                        <li>ustawa o fundacjach,</li>
                                        <li>ustawa o świadczeniu usług drogą elektroniczną,</li>
                                        <li>Rozporządzenie RODO,</li>
                                        <li>ustawa o ochronie danych osobowych.</li>
                                    </ul>
                                </li>
                                <li>
                                    Fundacja może zmienić Regulamin z ważnych przyczyn, w szczególności w przypadku:
                                    <ul>
                                        <li>zmiany przepisów prawa,</li>
                                        <li>zmian organizacyjnych,</li>
                                        <li>zmian funkcjonalności Serwisu,</li>
                                        <li>konieczności dostosowania Regulaminu do zmian technologicznych.</li>
                                    </ul>
                                </li>
                                <li>Zmiany Regulaminu nie naruszają praw i obowiązków wynikających z darowizn przekazanych przed dniem wejścia w życie nowej wersji Regulaminu.</li>
                                <li>Aktualna wersja Regulaminu jest publikowana w Serwisie.</li>
                                <li>Regulamin wchodzi w życie z dniem 29 lipca 2026 r.</li>
                            </ol>
                        </section>
                    </article>
                </div>
            </div>
        </>
    )
}

Regulamin.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>
