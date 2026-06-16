{{-- SP-CHURCH-REGULAMIN: nowy layout marki (storefront/church/shop/regulamin.blade.php) --}}
{{-- Tresc: REGULAMIN SKLEPU INTERNETOWEGO SUPPORT ME (z docx, 16.06.2026) --}}
@extends('layouts.landing')

@section('title', 'Regulamin sklepu — ' . config('shop.name'))
@section('meta-description', 'Regulamin sklepu internetowego SUPPORT ME — zasady sprzedazy, platnosci, dostawy, odstapienia od umowy, reklamacji i ochrony danych.')

@push('head')
<link rel="stylesheet" href="{{ asset('css/subpages.css') }}?v={{ substr(md5_file(public_path('css/subpages.css')), 0, 10) }}">
@endpush

@section('content')
    <!-- SP-CHURCH-REGULAMIN: storefront/church/shop/regulamin.blade.php -->
    <section class="sp-subhero">
        <div class="sp-subhero__inner">
            <p class="sp-eyebrow">Dokumenty</p>
            <h1>Regulamin sklepu internetowego</h1>
            <p class="sp-lede">Zasady korzystania ze sklepu, sprzedazy, platnosci, dostawy, odstapienia od umowy oraz reklamacji.</p>
        </div>
    </section>

    <div class="sp-wrap">
        <div class="sp-container sp-doc sp-legal">
            <article class="sp-legal__body">
                <section class="sp-legal__section">
                    <h2>§ 1. Postanowienia ogólne</h2>
                    <ol><li>Niniejszy regulamin określa zasady korzystania ze sklepu internetowego www.please-suport-me.com w tym zasady zawierania umów sprzedaży, dostawy towarów, prawa do odstąpienia od umowy oraz trybu postępowania reklamacyjnego.</li><li>Sklep internetowy prowadzony jest przez Marcina Lulę prowadzącego działalność gospodarczą pn. MLI Marcin Lula z siedzibą pod adresem w Pruszkowie przy ul. dr Izabeli Wolfram nr 11, 05-800 Pruszków, NIP: 8741624637 REGON: 341224327(„Sprzedawca”).</li><li>Kontakt ze Sprzedawcą jest możliwy za pośrednictwem poczty e-mail: <a href="mailto:office@please-support-me.com">office@please-support-me.com</a> w godzinach 8:00-20:00</li></ol>
                </section>
                <section class="sp-legal__section">
                    <h2>§ 2. Definicje</h2>
                    <p><strong>Sklep</strong> – sklep internetowy prowadzony przez Sprzedawcę pod adresem www.please-support-me.com</p>
                    <p><strong>Kupujący</strong> – każdy podmiot (osoba fizyczna, osoba prawna, jednostka organizacyjna) dokonujący zakupów w Sklepie.</p>
                    <p><strong>Konsument</strong> – osoba fizyczna dokonująca zakupu niezwiązanego bezpośrednio z jej działalnością gospodarczą lub zawodową.</p>
                    <p><strong>Konto klienta</strong> – Usługa elektroniczna, oznaczony indywidualną nazwą (Loginem) i Hasłem podanym przez Kupującego zbiór zasobów w systemie teleinformatycznym Sprzedawcy, pozwalający na korzystanie z dodatkowych funkcjonalności/usług. Kupujący uzyskuje dostęp do indywidualnego Konta za pomocą Loginu i Hasła. Kupujący loguje się na swoje Konto po dokonaniu rejestracji w Sklepie. Konto umożliwia zapisywanie i przechowywanie informacji o danych adresowych Kupującego do wysyłki Towarów, śledzenie statusu zamówień, dostęp do historii zamówień, oraz innych usług udostępnianych przez Sprzedawcę;</p>
                    <p><strong>Przedsiębiorca na prawach konsumenta</strong> – osoba fizyczna zawierająca umowę bezpośrednio związaną z jej działalnością gospodarczą, gdy z treści tej umowy wynika, że nie posiada ona dla niej charakteru zawodowego.</p>
                    <p><strong>Towar</strong> – produkty fizyczne oferowane w Sklepie, w szczególności tagi NFC, naklejki zbliżeniowe, breloki NFC oraz inne powiązane gadżety elektroniczne.</p>
                </section>
                <section class="sp-legal__section">
                    <h2>§ 3. Usługi świadczone drogą elektroniczną</h2>
                    <ol><li>Sklep świadczy drogą elektroniczną nieodpłatne usługi polegające na: prowadzeniu Konta klienta oraz udostępnianiu interaktywnego formularza zamówienia (Koszyka).</li><li>Założenie Konta jest całkowicie dobrowolne, bezpłatne i nie jest wymagane do złożenia zamówienia (możliwe są zakupy bez rejestracji), jednak ułatwia proces zakupowy oraz pozwala na śledzenie historii zamówień.</li><li>Rejestracja Konta następuje poprzez wypełnienie formularza rejestracyjnego, w którym Kupujący podaje swoje prawdziwe dane, adres e-mail (Login) oraz ustala Hasło. Kupujący zobowiązany jest do zachowania Hasła w tajemnicy oraz zabezpieczenia go przed dostępem osób nieuprawnionych. Sprzedawca nie ponosi odpowiedzialności za szkody wynikłe z ujawnienia hasła przez Kupującego.</li><li>Umowa o świadczenie usługi prowadzenia Konta klienta zostaje zawarta na czas nieoznaczony.</li><li>Kupujący może w każdej chwili, bez podania przyczyny i bez ponoszenia opłat, usunąć Konto klienta, wysyłając stosowne żądanie do Sprzedawcy na adres e-mail: <a href="mailto:office@please-support-me.com">office@please-support-me.com</a></li><li>Sprzedawca może zablokować lub wypowiedzieć umowę o prowadzenie Konta klienta w trybie natychmiastowym w przypadku rażącego i uporczywego naruszania Regulaminu przez Kupującego.</li></ol>
                </section>
                <section class="sp-legal__section">
                    <h2>§ 4. Składanie zamówień</h2>
                    <ol><li>Do złożenia zamówienia w Sklepie niezbędne jest urządzenie z dostępem do Internetu, zaktualizowana przeglądarka internetowa oraz aktywne konto e-mail.</li><li>Zamówienia można składać 24 godziny na dobę, 7 dni w tygodniu.</li><li>Proces składania zamówienia polega na: dodaniu Towaru do koszyka, podaniu danych do wysyłki, wyborze metody dostawy i płatności, a następnie kliknięciu przycisku „Kupuję i płacę” (lub równoznacznego).</li><li>Wymagane przy zamówieniu zgody (np. akceptacja regulaminu, zgoda na newsletter) są rozdzielone; zgoda marketingowa jest zawsze opcjonalna i nie warunkuje możliwości dokonania zakupu.</li><li>Umowa sprzedaży zostaje zawarta w momencie otrzymania przez Kupującego wiadomości e-mail z potwierdzeniem przyjęcia zamówienia do realizacji.</li></ol>
                </section>
                <section class="sp-legal__section">
                    <h2>§ 5. Ceny, płatności i dostawa</h2>
                    <ol><li>Wszystkie ceny podane w Sklepie są cenami brutto (zawierają podatek VAT) i wyrażone są w polskich złotych (PLN).</li><li>Zgodnie z przepisami prawa, w przypadku ogłoszenia obniżki ceny (promocji), Sklep obok aktualnej ceny wyświetla najniższą cenę z 30 dni przed obniżką.</li><li>Sklep oferuje następujące metody płatności: płatność online lub płatności kartą.</li><li>Sklep realizuje dostawy na terytorium Polski za pośrednictwem firm kurierskich.</li><li>Koszty dostawy są wyraźnie podane w procesie składania zamówienia i ponosi je Kupujący, chyba że wartość zamówienia przekracza kwotę uprawniającą do darmowej dostawy określoną w Sklepie.</li><li>Sprzedawca ponosi ryzyko uszkodzenia towaru w transporcie do momentu jego odbioru przez Kupującego.</li></ol>
                </section>
                <section class="sp-legal__section">
                    <h2>§ 6. Wymagania techniczne</h2>
                    <ol><li>Do prawidłowego korzystania ze Sklepu, w tym przeglądania asortymentu i składania zamówień, niezbędne jest spełnienie następujących minimalnych wymagań technicznych:</li></ol>
                    <ol><li>urządzenie końcowe (komputer, laptop, tablet, smartfon) z dostępem do sieci Internet,</li><li>zaktualizowana przeglądarka internetowa (np. Chrome, Firefox, Safari, Edge) z włączoną obsługą plików cookies, wyskakujących okienek oraz języka JavaScript,</li><li>aktywne konto poczty elektronicznej (e-mail). Sprzedawca zastrzega sobie prawo do czasowego zawieszenia lub ograniczenia dostępności Sklepu w związku z koniecznością przeprowadzenia prac konserwacyjnych, modernizacji systemu lub awariami.</li><li>Sprzedawca nie ponosi odpowiedzialności za zakłócenia w funkcjonowaniu Sklepu wywołane siłą wyższą, niedozwolonym działaniem osób trzecich lub niekompatybilnością Sklepu z infrastrukturą techniczną Kupującego.</li></ol>
                </section>
                <section class="sp-legal__section">
                    <h2>§ 7. Prawo odstąpienia od umowy</h2>
                    <ol><li>Konsument oraz Przedsiębiorca na prawach konsumenta mają prawo odstąpić od umowy w terminie 14 dni od dnia otrzymania Towaru, bez podania przyczyny.</li><li>Odstąpienie od umowy może nastąpić poprzez kliknięcie wyraźnie oznaczonego przycisku „Odstąp od umowy” (lub przycisku zwrotu), dostępnego bezpośrednio w interfejsie Sklepu (z poziomu historii zamówień w koncie Kupującego). Po złożeniu oświadczenia w ten sposób, Sprzedawca niezwłocznie przesyła Kupującemu potwierdzenie jego otrzymania na adres email podany przez Kupującego.</li><li>Oświadczenie o odstąpieniu można również złożyć na adres e-mail: <a href="mailto:office@please-support-me.com">office@please-support-me.com</a> lub listownie, korzystając ze wzoru formularza stanowiącego załącznik do niniejszego Regulaminu.</li><li>Bezpośrednie koszty zwrotu Towaru (np. koszt odesłania) ponosi Kupujący.</li><li>Zwracany Towar może zostać sprawdzony przez Kupującego w sposób, w jaki mógłby to zrobić w sklepie stacjonarnym. Jeśli wartość towaru zmalała przez korzystanie z niego ponad miarę, Sprzedawca ma prawo żądać odszkodowania (obniżyć kwotę zwrotu).</li><li>Prawo odstąpienia od umowy nie przysługuje m.in. w odniesieniu do Towarów nieprefabrykowanych, wyprodukowanych według specyfikacji Kupującego (np. tagi NFC z indywidualnym nadrukiem lub zakodowane na specjalne, unikalne zlecenie Kupującego).</li></ol>
                </section>
                <section class="sp-legal__section">
                    <h2>§ 8. Reklamacje i zwroty</h2>
                    <ol><li>Sprzedawca jest zobowiązany do dostarczenia Towaru zgodnego z umową. Odpowiedzialność Sprzedawcy z tytułu niezgodności Towaru z umową (wobec Konsumentów i Przedsiębiorców uprzywilejowanych) trwa 2 lata od daty wydania Towaru.</li><li>Reklamacje należy składać na adres e-mail: <a href="mailto:office@please-support-me.com">office@please-support-me.com</a> lub listownie na adres Sprzedawcy.</li><li>Sprzedawca rozpatruje reklamacje w terminie 14 dni kalendarzowych od dnia ich otrzymania.</li><li>W przypadku niezgodności towaru z umową Kupujący może w pierwszej kolejności żądać naprawy lub wymiany Towaru. Dopiero w określonych ustawowo przypadkach (np. gdy naprawa jest niemożliwa lub Sprzedawca odmówił naprawy) można żądać obniżenia ceny lub odstąpienia od umowy.</li><li>Dla transakcji z przedsiębiorcami (z wyłączeniem Przedsiębiorców na prawach konsumenta): Odpowiedzialność Sprzedawcy z tytułu rękojmi za wady fizyczne i prawne towaru zostaje całkowicie wyłączona (zgodnie z art. 558 § 1 Kodeksu cywilnego). Ewentualne zwroty pełnowartościowego towaru od takich przedsiębiorców zależą wyłącznie od dobrej woli Sprzedawcy.</li></ol>
                </section>
                <section class="sp-legal__section">
                    <h2>§ 9. Ochrona danych osobowych</h2>
                    <ol><li>Administratorem danych osobowych podanych podczas składania zamówienia jest Sprzedawca.</li><li>Dane osobowe są przetwarzane w celu realizacji umowy sprzedaży (art. 6 ust. 1 lit. b RODO), wypełnienia obowiązków prawnych, takich jak rachunkowość (art. 6 ust. 1 lit. c RODO) oraz opcjonalnie w celach marketingowych, jeżeli Kupujący wyraził na to osobną zgodę (art. 6 ust. 1 lit. a RODO).</li><li>Szczegółowe informacje dotyczące przetwarzania danych osobowych, okresu ich przechowywania, stosowania plików cookies oraz praw przysługujących Kupującemu (prawo dostępu, sprostowania, usunięcia, ograniczenia przetwarzania) znajdują się w osobnej Polityce Prywatności Sklepu.</li></ol>
                </section>
                <section class="sp-legal__section">
                    <h2>§ 10. Własność intelektualna i ochrona praw autorskich</h2>
                    <ol><li>Wszelkie prawa do Sklepu Internetowego, w tym autorskie prawa majątkowe, prawa do nazwy Sklepu, jego domeny, a także do wchodzących w ich skład elementów graficznych, logotypów, zdjęć Towarów oraz tekstów i opisów podlegają ochronie prawnej i należą do Sprzedawcy.</li><li>Zabrania się kopiowania, modyfikowania, powielania oraz wykorzystywania w celach komercyjnych jakichkolwiek Treści i elementów Sklepu bez uprzedniej, wyraźnej zgody Sprzedawcy wyrażonej na piśmie. Dozwolone jest korzystanie z nich wyłącznie w zakresie użytku osobistego w celu prawidłowego korzystania ze Sklepu.</li><li>W przypadku, gdy Sklep umożliwia dodawanie opinii o Produktach, Kupujący z chwilą ich umieszczenia udziela Sprzedawcy niewyłącznej, nieodpłatnej licencji na ich wykorzystywanie, utrwalanie i rozpowszechnianie na stronie Sklepu w celach informacyjnych i promocyjnych.</li></ol>
                </section>
                <section class="sp-legal__section">
                    <h2>§ 11. Pozasądowe rozwiązywanie sporów</h2>
                    <ol><li>Konsument ma możliwość skorzystania z pozasądowych sposobów rozpatrywania reklamacji i dochodzenia roszczeń.</li><li>Szczegółowe informacje o procedurach oraz wykaz podmiotów uprawnionych znajdują się na stronie Urzędu Ochrony Konkurencji i Konsumentów (uokik.gov.pl).</li><li>Konsument może również skorzystać z unijnej platformy ODR, służącej do rozwiązywania sporów online, dostępnej pod adresem: https://ec.europa.eu/consumers/odr/.</li></ol>
                </section>
                <section class="sp-legal__section">
                    <h2>§ 12. Postanowienia końcowe</h2>
                    <ol><li>Sprzedawca zastrzega sobie prawo do wprowadzania zmian w Regulaminie z ważnych przyczyn prawnych lub z przyczyn organizacyjnych.</li><li>Wszelkie zmiany Regulaminu wchodzą w życie w terminie podanym przez Sprzedawcę (nie krótszym niż 14 dni od powiadomienia). Do zamówień złożonych przed zmianą Regulaminu stosuje się wersję dokumentu z chwili zawarcia umowy.</li><li>W sprawach nieuregulowanych w niniejszym Regulaminie mają zastosowanie powszechnie obowiązujące przepisy prawa polskiego, w szczególności Kodeksu cywilnego oraz Ustawy o prawach konsumenta.</li></ol>
                    <p>Regulamin wchodzi w życie z dniem 16.06.2026r..</p>
                </section>
                <section class="sp-legal__section">
                    <h2>ZAŁĄCZNIK NR 1 – WZÓR FORMULARZA ODSTĄPIENIA OD UMOWY</h2>
                    <p>MLI Marcin Lula</p>
                    <p>ul. dr Izabeli Wolfram nr 11</p>
                    <p>05-800 Pruszków</p>
                    <p>Niniejszym informuję, że zgodnie z art. 27 ustawy z dnia 30 maja 2014 r. o prawach konsumenta odstępuję od umowy sprzedaży następujących towarów:</p>
                    <p>........................................................................................................................................................................................................................................................................</p>
                    <p>Imię i nazwisko: ..............................................................................................................................</p>
                    <p>Adres: .............................................................................................................................. .............................................................................................................................. Numer zamówienia: ..............................................................................................................................</p>
                    <p>Adres e-mail/nr telefonu:</p>
                    <p>……………………………………………………………………………………………………………………….. Numer rachunku bankowego do zwrotu środków (opcjonalnie): ..............................................................................................................................</p>
                    <p>Podpis:</p>
                    <p>…………………………………………………………</p>
                    <p>Data: .....................................................</p>
                </section>
            </article>
        </div>
    </div>
@endsection
