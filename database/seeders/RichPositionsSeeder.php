<?php

namespace Database\Seeders;

use App\Modules\Storefront\Models\JobPosition;
use Illuminate\Database\Seeder;

/**
 * RichPositionsSeeder — bogate, profesjonalne ogłoszenia rekrutacyjne SupportME.
 *
 * Marka: SupportME — technologia NFC wspierająca organizacje, parafie, fundacje
 * i lokalne inicjatywy w czynieniu dobra (cyfrowa taca, płatności zbliżeniowe).
 *
 * Zasady:
 *  - updateOrCreate po `title` — NIE duplikujemy istniejących ofert.
 *  - Istniejąca w bazie oferta „Koordynator wolontariatu" (jeśli jest) zostaje
 *    przemianowana na rolę z projektu rekrutacyjnego „Specjalista ds. wsparcia
 *    darczyńców" — dzięki temu nie zostaje ubogi duplikat obok nowej oferty.
 *  - Każdy description_html ma spójne sekcje: Twoja rola / Zakres obowiązków /
 *    Czego oczekujemy / Co oferujemy / Jak aplikować (h3 + p + ul/li).
 */
class RichPositionsSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Jeśli w bazie wisi stara, uboga oferta „Koordynator wolontariatu" —
        //    podmieniamy jej tytuł na docelową rolę, by updateOrCreate trafił w nią,
        //    a nie utworzył drugiego wiersza. (Nie ruszamy innych ofert.)
        JobPosition::where('title', 'Koordynator wolontariatu')
            ->update(['title' => 'Specjalista ds. wsparcia darczyńców']);

        foreach ($this->positions() as $data) {
            JobPosition::updateOrCreate(
                ['title' => $data['title']],
                [
                    'location'         => $data['location'],
                    'employment_type'  => $data['employment_type'],
                    'description_html' => $data['description_html'],
                    'active'           => 1,
                    'sort'             => $data['sort'],
                ]
            );
        }
    }

    /**
     * Cztery role wg kierunków z projektu rekrutacyjnego SupportME.
     */
    private function positions(): array
    {
        return [
            [
                'title'           => 'Specjalista ds. wsparcia darczyńców',
                'location'        => 'Zdalnie / Pruszków',
                'employment_type' => 'Pełny etat',
                'sort'            => 1,
                'description_html' => <<<'HTML'
<h3>Twoja rola</h3>
<p>Jesteś pierwszą osobą, z którą kontaktują się parafie, fundacje i organizatorzy zbiórek korzystający z SupportME. To Ty sprawiasz, że technologia NFC — cyfrowa taca, płatności zbliżeniowe, terminale i naklejki — staje się dla nich prosta i bezpieczna. Dbasz o to, by każdy darczyńca i każda wspierana organizacja czuli się zaopiekowani, a wsparcie trafiało tam, gdzie ma realnie pomagać.</p>

<h3>Zakres obowiązków</h3>
<ul>
    <li>Onboarding nowych parafii i organizacji: konfiguracja kont, terminali i kodów NFC, pomoc przy pierwszej zbiórce.</li>
    <li>Bieżące wsparcie darczyńców i koordynatorów przez telefon, e-mail i czat — szybkie, ludzkie i konkretne odpowiedzi.</li>
    <li>Reagowanie na zgłoszenia techniczne pierwszej linii i eskalowanie trudniejszych spraw do zespołu produktowego.</li>
    <li>Tworzenie i aktualizowanie materiałów pomocowych: instrukcji, FAQ, krótkich poradników wideo.</li>
    <li>Zbieranie informacji zwrotnej od organizacji i przekazywanie wniosków zespołowi, by produkt był coraz lepszy.</li>
</ul>

<h3>Czego oczekujemy</h3>
<ul>
    <li>Doświadczenie w obsłudze klienta, wsparciu użytkownika lub pracy z darczyńcami / wolontariuszami (mile widziane organizacje pozarządowe lub parafialne).</li>
    <li>Wyjątkowa cierpliwość i empatia — potrafisz spokojnie przeprowadzić mniej technicznego rozmówcę krok po kroku.</li>
    <li>Bardzo dobra komunikacja w mowie i piśmie po polsku.</li>
    <li>Samodzielność i dobra organizacja pracy zdalnej.</li>
    <li>Swoboda w korzystaniu z narzędzi online (panel administracyjny, poczta, czat, proste raporty).</li>
</ul>

<h3>Co oferujemy</h3>
<ul>
    <li>Realny sens pracy — wspierasz inicjatywy, które robią dobro dla swoich wspólnot.</li>
    <li>Pracę w pełni zdalną z elastycznymi godzinami (siedziba w Pruszkowie dostępna dla chętnych).</li>
    <li>Stabilne zatrudnienie i jasną ścieżkę rozwoju w rosnącym zespole.</li>
    <li>Wdrożenie, mentoring i dostęp do wiedzy o produkcie oraz świecie płatności NFC.</li>
    <li>Małą, życzliwą organizację, w której Twój głos ma znaczenie.</li>
</ul>

<h3>Jak aplikować</h3>
<p>Kliknij „Aplikuj na to stanowisko", załącz CV i napisz w kilku zdaniach, dlaczego chcesz pomagać organizacjom czyniącym dobro. Odpowiadamy na każde zgłoszenie.</p>
HTML,
            ],
            [
                'title'           => 'Software Developer (PHP/Laravel)',
                'location'        => 'Zdalnie / Pruszków',
                'employment_type' => 'Pełny etat',
                'sort'            => 2,
                'description_html' => <<<'HTML'
<h3>Twoja rola</h3>
<p>Współtworzysz platformę SupportME — system płatności NFC i cyfrowej tacy, z którego korzystają parafie, fundacje i lokalne inicjatywy. Piszesz kod, który dosłownie pomaga ludziom wspierać dobre sprawy: od panelu organizacji, przez integracje płatności, po doświadczenie darczyńcy w momencie zbliżenia telefonu do terminala. Pracujesz blisko produktu i widzisz efekt swojej pracy w realnym świecie.</p>

<h3>Zakres obowiązków</h3>
<ul>
    <li>Rozwój aplikacji w PHP 8 i Laravel — nowe funkcje, panel organizacji, API i mechanizmy płatności.</li>
    <li>Integracje z bramkami płatniczymi i obsługa transakcji NFC (app2app, terminale, tagi).</li>
    <li>Projektowanie schematu bazy danych (MySQL/MariaDB) i dbanie o wydajność zapytań.</li>
    <li>Pisanie czytelnego, testowalnego kodu oraz code review w niewielkim zespole.</li>
    <li>Udział w decyzjach architektonicznych i ciągłe ulepszanie istniejącego systemu.</li>
</ul>

<h3>Czego oczekujemy</h3>
<ul>
    <li>Solidna znajomość PHP i frameworka Laravel (mile widziane Laravel 10/11/12).</li>
    <li>Dobre rozumienie relacyjnych baz danych (MySQL/MariaDB), HTTP i bezpieczeństwa aplikacji webowych.</li>
    <li>Praktyka z Git oraz nawyk pisania utrzymywalnego kodu.</li>
    <li>Podstawy front-endu (Blade, JavaScript, CSS) wystarczające do samodzielnej pracy nad funkcją „end to end".</li>
    <li>Mile widziane: doświadczenie z płatnościami, kolejkami, testami automatycznymi lub środowiskiem multi-tenant.</li>
</ul>

<h3>Co oferujemy</h3>
<ul>
    <li>Pracę nad produktem o realnym, pozytywnym wpływie społecznym.</li>
    <li>Pełną zdalność i elastyczne godziny (biuro w Pruszkowie dla chętnych).</li>
    <li>Nowoczesny stack, krótkie ścieżki decyzyjne i brak korporacyjnej biurokracji.</li>
    <li>Wpływ na architekturę i kierunek rozwoju platformy.</li>
    <li>Stabilne zatrudnienie i budżet na rozwój (kursy, konferencje, książki).</li>
</ul>

<h3>Jak aplikować</h3>
<p>Kliknij „Aplikuj na to stanowisko" i załącz CV. Jeśli masz portfolio, GitHub lub przykładowy projekt — koniecznie dołącz link. Najciekawszych kandydatów zapraszamy na krótką rozmowę techniczną.</p>
HTML,
            ],
            [
                'title'           => 'Project Manager',
                'location'        => 'Zdalnie / Pruszków',
                'employment_type' => 'Pełny etat',
                'sort'            => 3,
                'description_html' => <<<'HTML'
<h3>Twoja rola</h3>
<p>Spinasz w całość pracę zespołu SupportME — od pomysłu, przez wdrożenie, po uruchomienie u organizacji. Dbasz, by projekty wspierające parafie, fundacje i lokalne inicjatywy były dowożone na czas, w dobrej jakości i bez chaosu. Jesteś łącznikiem między produktem, technologią, wsparciem darczyńców i partnerami — tłumaczysz potrzeby na konkretne zadania i pilnujesz, żeby wszystko grało.</p>

<h3>Zakres obowiązków</h3>
<ul>
    <li>Planowanie i prowadzenie projektów oraz wdrożeń u nowych organizacji od startu do uruchomienia.</li>
    <li>Priorytetyzacja zadań, ustalanie harmonogramów i pilnowanie terminów oraz budżetów.</li>
    <li>Koordynacja pracy programistów, zespołu wsparcia i partnerów zewnętrznych.</li>
    <li>Komunikacja z kluczowymi organizacjami — raportowanie statusu, zarządzanie oczekiwaniami i ryzykiem.</li>
    <li>Usprawnianie procesów wewnętrznych i wprowadzanie prostych, działających rytmów pracy zespołu.</li>
</ul>

<h3>Czego oczekujemy</h3>
<ul>
    <li>Doświadczenie w prowadzeniu projektów (IT, produkt cyfrowy lub wdrożenia) — minimum 2 lata.</li>
    <li>Znajomość metod zwinnych (Scrum/Kanban) i umiejętność dobrania podejścia do sytuacji.</li>
    <li>Świetna organizacja, komunikatywność i umiejętność porządkowania chaosu.</li>
    <li>Umiejętność rozmawiania zarówno z technicznym zespołem, jak i z mniej technicznym partnerem (np. księdzem czy koordynatorem fundacji).</li>
    <li>Mile widziane: doświadczenie z płatnościami, fintechem, organizacjami pozarządowymi lub sektorem non-profit.</li>
</ul>

<h3>Co oferujemy</h3>
<ul>
    <li>Realny wpływ na produkt, który pomaga organizacjom czynić dobro.</li>
    <li>Pełną zdalność i elastyczne godziny (siedziba w Pruszkowie dla chętnych).</li>
    <li>Dużą samodzielność i zaufanie — sam(a) kształtujesz sposób prowadzenia projektów.</li>
    <li>Pracę w małym, ambitnym zespole z krótkimi ścieżkami decyzyjnymi.</li>
    <li>Stabilne zatrudnienie i przestrzeń do rozwoju razem z firmą.</li>
</ul>

<h3>Jak aplikować</h3>
<p>Kliknij „Aplikuj na to stanowisko", załącz CV i opisz krótko projekt, z którego jesteś dumny(a) oraz swoją rolę w nim. Chętnie poznamy Twój sposób pracy.</p>
HTML,
            ],
            [
                'title'           => 'Specjalista ds. partnerstw i rozwoju',
                'location'        => 'Zdalnie / Pruszków',
                'employment_type' => 'Pełny etat',
                'sort'            => 4,
                'description_html' => <<<'HTML'
<h3>Twoja rola</h3>
<p>Otwierasz SupportME na nowe organizacje — docierasz do parafii, fundacji, diecezji i lokalnych inicjatyw, pokazując im, jak technologia NFC może uprościć zbieranie wsparcia na dobre cele. Budujesz długofalowe relacje oparte na zaufaniu i realnej wartości, a nie na nachalnej sprzedaży. To dzięki Tobie coraz więcej dobrych spraw zyskuje nowoczesne narzędzie do działania.</p>

<h3>Zakres obowiązków</h3>
<ul>
    <li>Pozyskiwanie nowych partnerów: parafii, fundacji, stowarzyszeń i organizatorów zbiórek.</li>
    <li>Prowadzenie rozmów, prezentacji i wdrożeń pilotażowych — od pierwszego kontaktu do podpisania współpracy.</li>
    <li>Budowanie i utrzymywanie długofalowych relacji z partnerami oraz dbanie o ich satysfakcję.</li>
    <li>Współtworzenie strategii rozwoju i poszukiwanie nowych kanałów dotarcia (diecezje, sieci organizacji, wydarzenia).</li>
    <li>Bliska współpraca z zespołem wsparcia i produktu — przekazujesz głos partnerów do wewnątrz.</li>
</ul>

<h3>Czego oczekujemy</h3>
<ul>
    <li>Doświadczenie w sprzedaży relacyjnej, rozwoju biznesu, fundraisingu lub partnerstwach.</li>
    <li>Naturalna łatwość budowania relacji i prowadzenia rozmów z różnymi środowiskami — od księży po zarządy fundacji.</li>
    <li>Wysoka samodzielność, proaktywność i orientacja na wynik.</li>
    <li>Bardzo dobra komunikacja po polsku oraz umiejętność prostego tłumaczenia technologii.</li>
    <li>Mile widziane: znajomość środowiska kościelnego lub pozarządowego oraz prawo jazdy kat. B.</li>
</ul>

<h3>Co oferujemy</h3>
<ul>
    <li>Misję, w którą łatwo uwierzyć — pomagasz dobrym sprawom rosnąć.</li>
    <li>Pełną zdalność i elastyczne godziny (biuro w Pruszkowie dla chętnych).</li>
    <li>Atrakcyjny system wynagrodzeń z częścią premiową powiązaną z efektami.</li>
    <li>Dużą samodzielność i realny wpływ na strategię rozwoju firmy.</li>
    <li>Wsparcie zespołu, materiały i narzędzia, które ułatwiają rozmowy z partnerami.</li>
</ul>

<h3>Jak aplikować</h3>
<p>Kliknij „Aplikuj na to stanowisko", załącz CV i napisz, dlaczego chcesz budować partnerstwa właśnie dla inicjatyw czyniących dobro. Odezwiemy się do każdego.</p>
HTML,
            ],
        ];
    }
}
