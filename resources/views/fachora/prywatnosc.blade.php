@extends('layouts.marketing')

{{--
  WERSJA ROBOCZA — DO WERYFIKACJI PRZEZ PRAWNIKA.
  Polityka prywatności Fachora (RODO). Administrator: Budomatch Daniël de Graaf Obejmuje Klientów, Firmy
  (dane z rejestrów na podstawie art. 6 ust. 1 lit. f, przejęty profil na podstawie lit. b, weryfikację,
  Stripe Connect jako odrębnego administratora), osoby reprezentujące Firmy i odwiedzających.
  Oznaczenie „wersja robocza” celowo dyskretne, na dole strony.
--}}

@section('title', 'Polityka prywatności Fachora')
@section('description', 'Jak Fachora (Budomatch) przetwarza dane osobowe klientów, firm i odwiedzających: jakie dane, w jakim celu i na jakiej podstawie, komu je powierzamy (Stripe, Resend, Railway, Anthropic, OpenStreetMap), jak długo je przechowujemy, zasady kontaktu z firmami z rejestrów, pliki cookie i prawa z RODO.')

@push('styles')
<style>
  .legal{padding:56px 0 80px;}
  .legal .container{max-width:780px;}
  .legal h1{font-size:clamp(30px,5vw,42px);margin-bottom:10px;color:var(--brand-darker);}
  .legal .meta{color:var(--text-3);font-size:14px;margin-bottom:26px;}
  .legal .entity{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:18px 20px;margin-bottom:30px;box-shadow:var(--shadow-sm);font-size:14.5px;line-height:1.75;color:var(--text-2);}
  .legal .entity strong{color:var(--text);}
  .legal .toc{background:var(--brand-tint);border:1px solid var(--brand-border);border-radius:14px;padding:18px 22px;margin-bottom:34px;font-size:14px;line-height:1.7;columns:2;column-gap:28px;}
  @media (max-width:640px){.legal .toc{columns:1;}}
  .legal .toc a{color:var(--brand);font-weight:500;display:block;break-inside:avoid;}
  .legal h2{font-size:20px;margin:36px 0 10px;scroll-margin-top:90px;}
  .legal h3{font-size:16px;margin:22px 0 6px;}
  .legal p{color:var(--text-2);margin:0 0 14px;line-height:1.75;}
  .legal ul,.legal ol{color:var(--text-2);margin:0 0 16px;padding-left:20px;line-height:1.75;}
  .legal li{margin-bottom:7px;}
  .legal a{color:var(--brand);font-weight:500;}
  .legal a:hover{text-decoration:underline;}
  .legal .tbl-wrap{overflow-x:auto;margin:0 0 20px;}
  .legal .tbl{width:100%;border-collapse:collapse;font-size:13.5px;background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;}
  .legal .tbl th,.legal .tbl td{padding:10px 12px;border-bottom:1px solid var(--border);text-align:left;vertical-align:top;color:var(--text-2);line-height:1.55;}
  .legal .tbl th{background:var(--surface-2);color:var(--text);font-weight:600;white-space:nowrap;}
  .legal .tbl tr:last-child td{border-bottom:none;}
  .legal .box{background:var(--accent-tint);border:1px solid #F8C9A3;border-radius:12px;padding:14px 18px;margin:0 0 16px;font-size:14px;line-height:1.7;color:var(--text-2);}
  .legal .box b{color:var(--text);}
  .legal .disclaimer{margin-top:44px;padding:12px 16px;background:var(--surface-2);border-radius:10px;font-size:12.5px;color:var(--text-3);}
</style>
@endpush

@section('content')
<section class="legal">
  <div class="container">
    <div class="eyebrow">Dokumenty</div>
    <h1>Polityka prywatności</h1>
    <div class="meta">Wersja z dnia 16 października 2026 r.</div>

    <div class="entity">
      Administrator danych osobowych:<br>
      <strong>Budomatch Daniël de Graaf</strong>, prowadzący platformę <strong>Fachora</strong> (fachora.pl)<br>
      Białka 15 · 09-550 Białka · Polska<br>
      NIP 7010869430 · REGON 381430120 · działalność gospodarcza wpisana do CEIDG<br>
      Kontakt w sprawach danych osobowych: <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a>
    </div>

    <p>Fachora łączy klientów z firmami, które wykonują usługi w ich domach i firmach. Żeby to działało, przetwarzamy dane klientów, firm i osób, które je reprezentują. Poniżej wyjaśniamy — zgodnie z rozporządzeniem (UE) 2016/679 (RODO) — jakie to dane, skąd je mamy, po co i na jakiej podstawie je przetwarzamy, komu je powierzamy, jak długo je przechowujemy i jakie masz prawa.</p>

    <nav class="toc" aria-label="Spis treści">
      <a href="#s1">1. Kogo dotyczy ta polityka</a>
      <a href="#s2">2. Jakie dane przetwarzamy i skąd</a>
      <a href="#s3">3. Cele, podstawy prawne, okresy</a>
      <a href="#s4">4. Firmy z rejestrów publicznych</a>
      <a href="#s5">5. Komu przekazujemy dane</a>
      <a href="#s6">6. Przekazywanie poza EOG</a>
      <a href="#s7">7. E-maile, SMS-y i marketing</a>
      <a href="#s8">8. Narzędzia automatyczne i AI</a>
      <a href="#s9">9. Pliki cookie i statystyki</a>
      <a href="#s10">10. Twoje prawa</a>
      <a href="#s11">11. Bezpieczeństwo</a>
      <a href="#s12">12. Zmiany polityki</a>
      <a href="#s13">13. Kontakt</a>
    </nav>

    <h2 id="s1">1. Kogo dotyczy ta polityka</h2>
    <ul>
      <li><strong>Klientów</strong> — osób, które składają zlecenie przez fachora.pl, wybierają firmę, płacą przez Fachora Protect, zgłaszają spory i wystawiają opinie.</li>
      <li><strong>Firm i osób je reprezentujących</strong> — przedsiębiorców z rejestrów publicznych (profil nieaktywny), firm, które przejęły profil, firm w weryfikacji i firm Fachora Verified, oraz właścicieli, członków zarządu i pracowników, którzy w ich imieniu korzystają z panelu.</li>
      <li><strong>Osób odwiedzających</strong> stronę fachora.pl, w tym korzystających z formularza kontaktowego i formularza zgłoszenia firmy.</li>
    </ul>

    <h2 id="s2">2. Jakie dane przetwarzamy i skąd je mamy</h2>
    <h3>Klienci</h3>
    <ul>
      <li><strong>Dane kontaktowe:</strong> adres e-mail (zawsze), imię i nazwisko oraz numer telefonu (jeżeli je podasz lub są potrzebne do umówienia terminu), preferowany język obsługi.</li>
      <li><strong>Opis zlecenia:</strong> kategoria, opis problemu, zdjęcia, pilność, kod pocztowy lub miejscowość. Zdjęcia mogą przypadkowo zawierać dane osobowe (np. wnętrze mieszkania) — prosimy, żeby fotografować tylko to, co dotyczy usterki.</li>
      <li><strong>Adres wykonania usługi</strong> — podawany dopiero po wyborze firmy i przekazywany tylko tej firmie.</li>
      <li><strong>Płatność:</strong> status płatności, kwota, identyfikator transakcji u operatora płatności, metoda płatności (np. „BLIK”). <strong>Nie otrzymujemy i nie przechowujemy numerów kart ani danych logowania do banku</strong> — obsługuje je wyłącznie Stripe.</li>
      <li><strong>Przebieg zlecenia:</strong> oferty, wybór, zatwierdzone prace dodatkowe, zgłoszenia zakończenia, spory z opisem i zdjęciami, opinia, korespondencja przez platformę, daty i adresy IP czynności (potwierdzenie, wybór, akceptacja).</li>
    </ul>
    <p>Źródło: Ty, a w zakresie statusu płatności — operator płatności.</p>

    <h3>Firmy i osoby je reprezentujące</h3>
    <ul>
      <li><strong>Dane rejestrowe</strong> (profil nieaktywny): nazwa, imię i nazwisko przedsiębiorcy (przy jednoosobowej działalności są to dane osobowe), NIP, REGON, KRS, forma prawna, adres siedziby, kody PKD, status działalności, status VAT i numery rachunków z wykazu podatników VAT. Źródło: CEIDG (dane.biznes.gov.pl), KRS (Ministerstwo Sprawiedliwości), wykaz podatników VAT (Ministerstwo Finansów). Szczegóły w pkt 4.</li>
      <li><strong>Dane przejętego profilu:</strong> adres e-mail i numer telefonu osoby przejmującej profil, jej imię i nazwisko, kod z listu, nazwa wyświetlana, opis, zdjęcie lub logo, kategorie, języki i ich poziom, zasięg, stawki orientacyjne, okres gwarancji, deklaracja faktury, zgoda na Fachora Protect. Źródło: firma.</li>
      <li><strong>Dane weryfikacji:</strong> wyniki sprawdzeń w rejestrach i wykazie VAT (z datą i treścią odpowiedzi), potwierdzenie e-maila i telefonu (kody, daty), data i wersja zaakceptowanego regulaminu i Standardu Fachora, polisa OC (jeżeli ją prześlesz: plik, ubezpieczyciel, zakres, data ważności), notatki z weryfikacji i z obsługi zgłoszeń.</li>
      <li><strong>Stripe Connect:</strong> identyfikator konta u operatora płatności, status możliwości przyjmowania płatności i wypłat, lista brakujących wymagań. <strong>Dokumenty tożsamości, dane beneficjentów rzeczywistych i numer rachunku bankowego zbiera i weryfikuje Stripe jako odrębny administrator</strong> (pkt 5); my widzimy tylko wynik.</li>
      <li><strong>Działalność na platformie:</strong> otrzymane zlecenia, oferty, zlecenia w toku, zgłoszenia, spory, opinie i odpowiedzi, średnia ocena, liczba zleceń, czas reakcji, naruszenia Standardu Fachora, faktury za prowizję, wypłaty.</li>
      <li><strong>Konto:</strong> login, zaszyfrowane hasło (hash), daty logowania, adresy IP, dziennik czynności.</li>
    </ul>

    <h3>Odwiedzający</h3>
    <ul>
      <li>Formularz kontaktowy i formularz zgłoszenia firmy: imię i nazwisko, e-mail, temat, treść, dane firmy (jeżeli je podasz).</li>
      <li>Dane techniczne: adres IP, przeglądarka, odwiedzone strony, czas — w logach serwera i w anonimowej statystyce (pkt 9).</li>
    </ul>

    <h2 id="s3">3. Cele, podstawy prawne i okresy przechowywania</h2>
    <div class="tbl-wrap">
      <table class="tbl">
        <tr><th>Cel</th><th>Dane</th><th>Podstawa prawna (RODO)</th><th>Jak długo</th></tr>
        <tr><td>Przyjęcie i potwierdzenie zlecenia, przekazanie go firmom, zebranie ofert</td><td>dane kontaktowe i opis zlecenia klienta</td><td>art. 6 ust. 1 lit. b — umowa o korzystanie z platformy (na żądanie klienta)</td><td>zlecenie niepotwierdzone: usuwane po {{ (int) config('fachora.confirm_days') }} dniach; zlecenie bez wybranej oferty: 6 miesięcy od złożenia</td></tr>
        <tr><td>Zawarcie i wykonanie umowy między klientem a firmą: potwierdzenie zlecenia, termin, prace dodatkowe, akceptacja, faktura</td><td>przebieg zlecenia, adres wykonania usługi</td><td>art. 6 ust. 1 lit. b; wobec firmy — lit. b (umowa o korzystanie z platformy)</td><td>okres gwarancji (standardowo {{ (int) config('fachora.default_warranty_months') }} miesięcy) i okres przedawnienia roszczeń — do 6 lat od zakończenia zlecenia</td></tr>
        <tr><td>Fachora Protect: rezerwacja, wypłata, zwrot, rozstrzyganie sporów</td><td>status płatności, kwoty, spór z opisem i zdjęciami, korespondencja</td><td>art. 6 ust. 1 lit. b; art. 6 ust. 1 lit. c — przepisy podatkowe i rachunkowe</td><td>jak wyżej; dokumenty księgowe 5 lat od końca roku podatkowego</td></tr>
        <tr><td>Opinie i ich publikacja, odpowiedzi firm</td><td>ocena, tekst, numer zlecenia, data</td><td>art. 6 ust. 1 lit. f — prawnie uzasadniony interes: rzetelna informacja o firmach dla klientów</td><td>dopóki profil firmy jest na platformie; na żądanie autora tekst można zanonimizować</td></tr>
        <tr><td>Profil nieaktywny z rejestru publicznego</td><td>dane rejestrowe</td><td>art. 6 ust. 1 lit. f — prawnie uzasadniony interes (pkt 4)</td><td>do żądania usunięcia albo do 12 miesięcy po wykreśleniu z rejestru; po usunięciu tylko NIP i data żądania</td></tr>
        <tr><td>Przejęcie profilu, prowadzenie konta firmy, panel, komunikacja o zleceniach</td><td>dane przejętego profilu, konto, działalność na platformie</td><td>art. 6 ust. 1 lit. b — umowa o korzystanie z platformy</td><td>czas trwania umowy i 6 lat po jej zakończeniu (przedawnienie roszczeń)</td></tr>
        <tr><td>Weryfikacja i oznaczenie Fachora Verified, identyfikowalność przedsiębiorcy (art. 30 DSA), egzekwowanie Standardu Fachora</td><td>dane weryfikacji, wynik Stripe Connect, polisa, naruszenia</td><td>art. 6 ust. 1 lit. c — rozporządzenie (UE) 2022/2065; art. 6 ust. 1 lit. f — bezpieczeństwo klientów i platformy</td><td>czas trwania umowy i 6 miesięcy po jej zakończeniu (DSA); informacje o poważnych naruszeniach i zablokowany NIP — 5 lat</td></tr>
        <tr><td>Prowizja: faktury, rozliczenia, windykacja</td><td>dane firmy, kwoty zleceń, faktury</td><td>art. 6 ust. 1 lit. b i lit. c (przepisy podatkowe)</td><td>5 lat od końca roku podatkowego</td></tr>
        <tr><td>Obsługa zgłoszeń, reklamacji, odwołań i zgłoszeń treści (DSA)</td><td>treść zgłoszenia, dane zgłaszającego, decyzja i uzasadnienie</td><td>art. 6 ust. 1 lit. c (DSA, prawa konsumenta); lit. f — obrona przed roszczeniami</td><td>3 lata od zakończenia sprawy</td></tr>
        <tr><td>Formularz kontaktowy</td><td>dane z formularza</td><td>art. 6 ust. 1 lit. f — odpowiedź na Twoją wiadomość</td><td>12 miesięcy od ostatniej wiadomości</td></tr>
        <tr><td>Bezpieczeństwo, zapobieganie nadużyciom, logi</td><td>adresy IP, daty, dziennik czynności</td><td>art. 6 ust. 1 lit. f — bezpieczeństwo platformy</td><td>do 12 miesięcy</td></tr>
        <tr><td>Statystyka odwiedzin</td><td>dane zanonimizowane (pkt 9)</td><td>art. 6 ust. 1 lit. f — rozwój serwisu</td><td>bezterminowo, bo nie da się ich powiązać z osobą</td></tr>
      </table>
    </div>
    <p>Podanie danych jest dobrowolne, ale bez adresu e-mail nie możemy przyjąć zlecenia, a bez danych weryfikacyjnych firma nie może zostać Fachora Verified. Po upływie okresów dane usuwamy albo anonimizujemy; kopie zapasowe są nadpisywane w ciągu 30 dni.</p>

    <h2 id="s4">4. Firmy z rejestrów publicznych — na jakiej zasadzie</h2>
    <div class="box"><b>Krótko:</b> firmę z rejestru pokazujemy tylko z nazwy, miejscowości i rodzaju działalności. Nie publikujemy jej telefonu, e-maila ani adresu. Adres e-mail z CEIDG wykorzystujemy wyłącznie do przekazania zapytania klienta, który sam wybrał tę firmę; nie wysyłamy reklam, newsletterów ani SMS-ów. Każda taka wiadomość zawiera link do rezygnacji. Firma może w każdej chwili zażądać usunięcia profilu.</div>
    <p>Tworzymy nieaktywne profile firm z branż obsługiwanych przez Fachora na podstawie danych udostępnianych publicznie przez CEIDG, KRS i wykaz podatników VAT, aby klient mógł sprawdzić, czy dana firma jest już Fachora Verified, a firma — przejąć swój profil. Podstawą jest nasz prawnie uzasadniony interes (art. 6 ust. 1 lit. f RODO): zbudowanie rzetelnego katalogu firm i umożliwienie im przystąpienia do platformy. Oceniliśmy, że interes ten nie narusza praw przedsiębiorców, ponieważ: dane są już publicznie dostępne w celu obrotu gospodarczego, publikujemy ich minimalny zakres bez danych kontaktowych, adres e-mail z rejestru wykorzystujemy wyłącznie do przekazania zapytania klienta, który sam wybrał daną firmę (bez treści reklamowych, z informacją o źródle danych i linkiem do rezygnacji), a rezygnacja lub usunięcie profilu następuje na proste żądanie.</p>
    <p>Dane rejestrowe aktualizujemy okresowo z tych samych źródeł. Po żądaniu usunięcia zachowujemy wyłącznie numer NIP z datą żądania, aby nie utworzyć profilu ponownie przy kolejnym imporcie; jest to realizacja Twojego sprzeciwu, nie dalsze przetwarzanie w pierwotnym celu.</p>
    <p>Jeżeli otrzymałeś od nas list: adres korespondencyjny pochodzi z rejestru, a kod w liście służy wyłącznie do potwierdzenia, że osoba przejmująca profil ma dostęp do adresu firmy. Kod traci ważność po wykorzystaniu.</p>

    <h2 id="s5">5. Komu przekazujemy dane</h2>
    <h3>Firmy Fachora Verified (odrębni administratorzy)</h3>
    <p>Firma, która otrzymuje Twoje zlecenie, widzi przed złożeniem oferty: kategorię, opis, zdjęcia, miejscowość lub rejon, pilność i język. <strong>Po wyborze jej oferty</strong> otrzymuje dodatkowo imię i nazwisko, adres wykonania usługi i dane kontaktowe potrzebne do umówienia terminu. Firma jest odrębnym administratorem tych danych i może ich używać tylko do wykonania zlecenia, faktury i gwarancji — zobowiązuje się do tego w regulaminie. Firma, której oferty nie wybrano, traci dostęp do zlecenia.</p>
    <h3>Podmioty przetwarzające i inni odbiorcy</h3>
    <div class="tbl-wrap">
      <table class="tbl">
        <tr><th>Odbiorca</th><th>Rola</th><th>Po co</th><th>Gdzie</th></tr>
        <tr><td>Stripe Payments Europe, Ltd. (Dublin, Irlandia) i spółki grupy Stripe</td><td>operator płatności — <strong>odrębny administrator</strong> w zakresie płatności i weryfikacji (KYC) firm; częściowo podmiot przetwarzający</td><td>Fachora Protect: przyjęcie płatności klienta, przechowanie środków, wypłata firmie, zwroty; Stripe Connect: weryfikacja tożsamości i rachunku firmy. Stripe stosuje własną politykę prywatności (stripe.com/privacy)</td><td>UE; Stripe może przekazywać dane do USA na podstawie standardowych klauzul umownych</td></tr>
        <tr><td>Resend, Inc. (USA)</td><td>podmiot przetwarzający</td><td>wysyłka wiadomości e-mail (potwierdzenia, oferty, przypomnienia, faktury) i rejestr doręczeń</td><td>USA — standardowe klauzule umowne</td></tr>
        <tr><td>Railway Corp. (USA)</td><td>podmiot przetwarzający</td><td>hosting aplikacji i bazy danych</td><td>serwery w regionie UE (Amsterdam); dostęp administracyjny z USA możliwy na podstawie standardowych klauzul umownych</td></tr>
        <tr><td>Anthropic, PBC (USA)</td><td>podmiot przetwarzający</td><td>narzędzia AI: porządkowanie i tłumaczenie opisu zlecenia, orientacyjny przedział cen, odczyt danych z polisy OC, streszczenie sporu dla osoby, która go rozstrzyga (pkt 8). Przesyłane treści nie są wykorzystywane do trenowania modeli</td><td>USA — standardowe klauzule umowne</td></tr>
        <tr><td>OpenStreetMap Foundation (Wielka Brytania) — usługa Nominatim</td><td>podmiot przetwarzający / dostawca usługi</td><td>zamiana kodu pocztowego lub miejscowości na współrzędne, żeby dobrać firmy w okolicy. Przesyłamy tylko kod pocztowy lub nazwę miejscowości, nigdy imię, nazwisko ani pełny adres</td><td>Wielka Brytania — decyzja Komisji o odpowiednim stopniu ochrony</td></tr>
        <tr><td>Smstools (Belgia)</td><td>podmiot przetwarzający</td><td>wysyłka SMS: kody weryfikacyjne dla klientów i firm, powiadomienia o pilnych zleceniach, przypomnienia o terminie</td><td>UE</td></tr>
        <tr><td>Cloudflare, Inc. (USA) — Turnstile</td><td>podmiot przetwarzający</td><td>zabezpieczenie formularzy przed automatami; przetwarza adres IP i dane przeglądarki przy wysyłce formularza</td><td>UE/USA — standardowe klauzule umowne, Data Privacy Framework</td></tr>
        <tr><td>Biuro rachunkowe, doradca podatkowy, prawnik</td><td>podmioty przetwarzające lub odrębni administratorzy</td><td>księgowość prowizji, obsługa sporów prawnych</td><td>UE</td></tr>
        <tr><td>Organy publiczne (np. sądy, Policja, UODO, organy państw członkowskich na podstawie DSA)</td><td>odrębni administratorzy</td><td>wyłącznie na żądanie oparte na przepisach prawa</td><td>—</td></tr>
      </table>
    </div>
    <p>Z podmiotami przetwarzającymi zawieramy umowy powierzenia (art. 28 RODO). Nie sprzedajemy danych i nie udostępniamy ich reklamodawcom.</p>

    <h2 id="s6">6. Przekazywanie danych poza Europejski Obszar Gospodarczy</h2>
    <p>Aplikacja i baza danych działają na serwerach w Unii Europejskiej. Niektórzy dostawcy (Resend, Anthropic, Cloudflare, Railway jako operator infrastruktury, Stripe w zakresie wsparcia) mają siedzibę w USA; przekazanie danych odbywa się na podstawie standardowych klauzul umownych zatwierdzonych przez Komisję Europejską (art. 46 ust. 2 lit. c RODO), a tam, gdzie dostawca jest certyfikowany — na podstawie decyzji o EU-US Data Privacy Framework. Przekazanie do Wielkiej Brytanii (OpenStreetMap) opiera się na decyzji Komisji stwierdzającej odpowiedni stopień ochrony. Kopię stosowanych zabezpieczeń udostępniamy na żądanie.</p>

    <h2 id="s7">7. E-maile, SMS-y i marketing</h2>
    <ul>
      <li><strong>Klienci</strong> otrzymują wyłącznie wiadomości dotyczące ich zlecenia: potwierdzenie, oferty, przypomnienia o wyborze i akceptacji, płatność, spór, prośba o opinię, przypomnienie o gwarancji. Nie wysyłamy klientom newsletterów bez zgody.</li>
      <li><strong>Firmy, które przejęły profil</strong>, otrzymują wiadomości o zleceniach, weryfikacji, wypłatach, fakturach i zmianach regulaminu — są to wiadomości niezbędne do wykonania umowy. Informacje o nowych funkcjach wysyłamy tylko firmom, które wyraziły na to zgodę w panelu.</li>
      <li><strong>Firmy z rejestrów publicznych</strong> mogą otrzymać od nas wyłącznie e-mail z zapytaniem klienta, który sam wybrał daną firmę do swojego zlecenia. Nie wysyłamy im reklam, newsletterów ani SMS-ów. Po rezygnacji nie otrzymują już żadnych wiadomości.</li>
      <li><strong>Każdy e-mail</strong> od Fachora zawiera link do rezygnacji z dalszych wiadomości tego rodzaju. Rezygnacja nie obejmuje wiadomości koniecznych do wykonania zlecenia w toku (np. informacji o sporze).</li>
    </ul>

    <h2 id="s8">8. Narzędzia automatyczne i sztuczna inteligencja</h2>
    <p>Korzystamy z modeli językowych (Anthropic), aby: uporządkować i przetłumaczyć opis zlecenia dla firm, oszacować orientacyjny przedział cen na podstawie zanonimizowanych zleceń z tej samej kategorii i regionu, odczytać ubezpieczyciela i datę ważności z przesłanej polisy oraz przygotować streszczenie sporu i propozycję rozwiązania dla osoby, która go rozstrzyga.</p>
    <p><strong>Nie podejmujemy wobec Ciebie decyzji opartych wyłącznie na zautomatyzowanym przetwarzaniu, które wywoływałyby skutki prawne lub podobnie istotnie na Ciebie wpływały</strong> (art. 22 RODO). Nadanie i cofnięcie oznaczenia Fachora Verified, rozstrzygnięcie sporu, usunięcie opinii i usunięcie firmy z platformy to decyzje podejmowane przez człowieka. Automatyczne są wyłącznie: dobór firm, do których trafia zlecenie (kategoria, odległość, język, ocena, czas reakcji), przeliczanie średniej oceny oraz tymczasowe zawieszenie profilu po wygaśnięciu warunku weryfikacji (np. utrata statusu VAT) — w tym ostatnim przypadku firma zostaje poinformowana i może się odwołać do osoby.</p>

    <h2 id="s9">9. Pliki cookie i statystyki</h2>
    <p>fachora.pl używa wyłącznie <strong>niezbędnych plików cookie</strong>: sesji (utrzymanie logowania firmy i zabezpieczenie formularzy tokenem CSRF), zapamiętania wybranego języka interfejsu (polski/angielski) oraz — w formularzach — cookie Cloudflare Turnstile do odróżnienia ludzi od automatów. Nie używamy cookie reklamowych ani śledzących i nie osadzamy narzędzi analitycznych osób trzecich, dlatego nie wyświetlamy baneru zgody na cookie.</p>
    <p>Odwiedziny liczymy <strong>własnym, anonimowym licznikiem</strong>: zapisujemy odwiedzoną stronę, datę, kraj i rodzaj urządzenia, bez adresu IP i bez identyfikatora, który pozwalałby rozpoznać tę samą osobę przy kolejnej wizycie. Tych danych nie da się powiązać z Tobą.</p>
    <p>Cookie możesz zablokować w przeglądarce; wtedy logowanie do panelu firmy i wysyłanie formularzy nie będą możliwe.</p>

    <h2 id="s10">10. Twoje prawa</h2>
    <p>Masz prawo do: <strong>dostępu</strong> do swoich danych i otrzymania ich kopii; <strong>sprostowania</strong> danych nieprawidłowych; <strong>usunięcia</strong> danych („prawo do bycia zapomnianym”) — z wyjątkiem danych, które musimy zachować z mocy prawa lub do obrony roszczeń; <strong>ograniczenia przetwarzania</strong>; <strong>przenoszenia danych</strong>, które przetwarzamy na podstawie umowy w sposób zautomatyzowany (otrzymasz je w formacie nadającym się do odczytu maszynowego); <strong>sprzeciwu</strong> wobec przetwarzania opartego na prawnie uzasadnionym interesie — w tym wobec profilu z rejestru publicznego (pkt 4) i wobec marketingu, który w razie sprzeciwu zawsze zaprzestajemy; oraz <strong>cofnięcia zgody</strong> w dowolnym momencie, jeżeli przetwarzanie opiera się na zgodzie, bez wpływu na zgodność z prawem wcześniejszego przetwarzania.</p>
    <p>Aby skorzystać z tych praw, napisz na <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a> (firma: najlepiej z adresu przypisanego do konta; klient: z adresu, na który przyszło potwierdzenie zlecenia). Odpowiadamy najpóźniej w ciągu miesiąca; w skomplikowanych sprawach możemy przedłużyć ten termin o kolejne dwa miesiące, o czym Cię poinformujemy. Możemy poprosić o potwierdzenie tożsamości, jeżeli żądanie przychodzi z innego adresu.</p>
    <p>Masz też prawo wnieść <strong>skargę do organu nadzorczego</strong>: w Polsce jest to Prezes Urzędu Ochrony Danych Osobowych (ul. Stawki 2, 00-193 Warszawa, <a href="https://uodo.gov.pl" target="_blank" rel="noopener">uodo.gov.pl</a>).</p>

    <h2 id="s11">11. Bezpieczeństwo</h2>
    <p>Połączenia są szyfrowane (TLS). Hasła przechowujemy wyłącznie w postaci skrótu (hash). Linki do stron zleceń i ofert są długimi, losowymi tokenami, których nie da się odgadnąć; formularze mają limity liczby prób i zabezpieczenie antyspamowe. Dostęp do systemów produkcyjnych jest ograniczony do niezbędnych osób i rejestrowany. Zdjęcia i dokumenty przechowujemy w bazie danych, nie na publicznie dostępnych adresach. Codziennie wykonujemy kopie zapasowe. Dane kart i dokumenty tożsamości nigdy nie trafiają do naszych systemów — obsługuje je operator płatności. O naruszeniu ochrony danych, które może powodować wysokie ryzyko dla Twoich praw, poinformujemy Cię bez zbędnej zwłoki.</p>

    <h2 id="s12">12. Zmiany polityki</h2>
    <p>Aktualizujemy tę politykę, gdy zmieniają się przepisy, nasi dostawcy lub działanie platformy. O istotnych zmianach informujemy firmy e-mailem i w panelu, a klientów — w wiadomościach dotyczących zlecenia. Aktualna wersja z datą jest zawsze dostępna pod adresem fachora.pl/polityka-prywatnosci; poprzednie wersje udostępniamy na żądanie.</p>

    <h2 id="s13">13. Kontakt</h2>
    <p>W sprawach danych osobowych napisz na <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a> (w temacie „RODO”) albo listownie: Budomatch Daniël de Graaf, Białka 15, 09-550 Białka. Zasady korzystania z platformy opisuje <a href="{{ route('pl.regulamin') }}">regulamin</a>.</p>

    <div class="disclaimer">Wersja robocza do weryfikacji przez prawnika. Pytania dotyczące prywatności: <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a>.</div>
  </div>
</section>
@endsection
