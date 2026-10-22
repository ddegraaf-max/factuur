@extends('layouts.marketing')

{{--
  WERSJA ROBOCZA — DO WERYFIKACJI PRZEZ PRAWNIKA.
  Regulamin platformy Fachora (pośrednictwo między Klientem a Firmą; Fachora Protect przez Stripe;
  prowizja od Firmy; DSA; prawa konsumenta). Liczby (prowizja, terminy) z config/fachora.php.
  Oznaczenie „wersja robocza” celowo dyskretne, na dole strony.
--}}

@php
    $fee = rtrim(rtrim(number_format((float) config('fachora.fee_percent'), 1, ',', ''), '0'), ',');
    $introFee = rtrim(rtrim(number_format((float) config('fachora.intro_fee_percent'), 1, ',', ''), '0'), ',');
    $minRating = number_format((float) config('fachora.min_rating'), 1, ',', '');
    $lead = (bool) config('fachora.lead_pricing');
    $leadPct = rtrim(rtrim(number_format((float) config('fachora.lead_percent'), 1, ',', ''), '0'), ',');
@endphp

@section('title', 'Regulamin platformy Fachora')
@section('description', 'Regulamin platformy Fachora (Budomatch): rola Fachora jako pośrednika, zlecenia i oferty, umowa między Klientem a Firmą, Fachora Protect i płatności przez Stripe, spory, odwołania, gwarancja, prawa konsumenta, prowizja od Firmy, opinie, zasady DSA, odpowiedzialność, prawo właściwe.')

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
  .legal .box{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:14px 18px;margin:0 0 16px;font-size:14px;line-height:1.7;color:var(--text-2);}
  .legal .box b{color:var(--text);}
  .legal .disclaimer{margin-top:44px;padding:12px 16px;background:var(--surface-2);border-radius:10px;font-size:12.5px;color:var(--text-3);}
</style>
@endpush

@section('content')
<section class="legal">
  <div class="container">
    <div class="eyebrow">Dokumenty</div>
    <h1>Regulamin platformy Fachora</h1>
    <div class="meta">Wersja {{ config('fachora.terms_version') }} · obowiązuje od 16 października 2026 r.</div>

    <div class="entity">
      Operator platformy (Usługodawca):<br>
      <strong>Budomatch Daniël de Graaf</strong>, prowadzący platformę pod marką <strong>Fachora</strong> (fachora.pl)<br>
      Białka 15 · 09-550 Białka · Polska<br>
      NIP 7010869430 · REGON 381430120 · działalność gospodarcza wpisana do CEIDG<br>
      E-mail: <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a> (także punkt kontaktowy, o którym mowa w § 15)
    </div>

    <nav class="toc" aria-label="Spis treści">
      <a href="#p1">§ 1. Definicje</a>
      <a href="#p2">§ 2. Czym jest Fachora</a>
      <a href="#p3">§ 3. Korzystanie z Platformy</a>
      <a href="#p4">§ 4. Konto Firmy i weryfikacja</a>
      <a href="#p5">§ 5. Standard Fachora</a>
      <a href="#p6">§ 6. Zlecenia i oferty</a>
      <a href="#p7">§ 7. Umowa Klienta z Firmą</a>
      <a href="#p8">§ 8. Fachora Protect</a>
      <a href="#p9">§ 9. Spory</a>
      <a href="#p10">§ 10. Odwołanie i niestawiennictwo</a>
      <a href="#p11">§ 11. Gwarancja i reklamacje</a>
      <a href="#p12">§ 12. Prawa konsumenta</a>
      <a href="#p13">§ 13. Opłaty i prowizja</a>
      <a href="#p14">§ 14. Opinie</a>
      <a href="#p15">§ 15. Treści i zgłoszenia (DSA)</a>
      <a href="#p16">§ 16. Odpowiedzialność</a>
      <a href="#p17">§ 17. Dane osobowe</a>
      <a href="#p18">§ 18. Reklamacje do Fachora</a>
      <a href="#p19">§ 19. Zmiany Regulaminu</a>
      <a href="#p20">§ 20. Prawo właściwe i spory sądowe</a>
      <a href="#p21">§ 21. Postanowienia końcowe</a>
    </nav>

    <h2 id="p1">§ 1. Definicje</h2>
    <ul>
      <li><strong>Fachora</strong> lub <strong>Platforma</strong> — serwis internetowy dostępny pod adresem fachora.pl, prowadzony przez Usługodawcę, który umożliwia Klientom składanie Zleceń, a Firmom składanie Ofert i wykonywanie Zleceń.</li>
      <li><strong>Usługodawca</strong> — Budomatch Daniël de Graaf z siedzibą w Białce, Białka 15, 09-550 Białka, NIP 7010869430, REGON 381430120 (działalność gospodarcza wpisana do CEIDG).</li>
      <li><strong>Klient</strong> — osoba, która składa Zlecenie przez Platformę: konsument (osoba fizyczna działająca w celach niezwiązanych z działalnością gospodarczą) albo przedsiębiorca.</li>
      <li><strong>Firma</strong> — przedsiębiorca (jednoosobowa działalność gospodarcza, spółka lub inny podmiot) wpisany do właściwego rejestru, który posiada Konto Firmy na Platformie.</li>
      <li><strong>Firma Fachora Verified</strong> (<strong>Verified</strong>) — Firma, która przeszła weryfikację opisaną w § 4 i otrzymała od Usługodawcy oznaczenie „Fachora Verified”. Tylko Firmy Verified otrzymują Zlecenia.</li>
      <li><strong>Profil</strong> — publiczna strona Firmy na Platformie. Profil może pochodzić z rejestru publicznego (profil nieaktywny, bez danych kontaktowych) albo być prowadzony przez Firmę po jego przejęciu.</li>
      <li><strong>Zlecenie</strong> — opis usługi, której potrzebuje Klient (kategoria, miejsce, opis, zdjęcia, termin, język), złożony przez Platformę i potwierdzony przez Klienta.</li>
      <li><strong>Oferta</strong> — odpowiedź Firmy na Zlecenie: cena (ryczałtowa albo według stawek), proponowany termin, okres gwarancji i uwagi.</li>
      <li><strong>Umowa o Usługę</strong> — umowa między Klientem a Firmą o wykonanie Zlecenia, zawarta z chwilą wyboru Oferty przez Klienta (§ 7).</li>
      <li><strong>Fachora Protect</strong> (<strong>Protect</strong>) — sposób zapłaty za Zlecenie opisany w § 8: Klient rezerwuje kwotę Oferty u operatora płatności, a Firma otrzymuje wypłatę po akceptacji wykonanej pracy.</li>
      <li><strong>Operator płatności</strong> — Stripe Payments Europe, Ltd. (Irlandia) i podmioty z jego grupy, świadczące usługi płatnicze na podstawie własnych regulaminów.</li>
      <li><strong>Standard Fachora</strong> — dziesięć zasad, które Firma Verified zobowiązuje się przestrzegać (§ 5).</li>
      <li><strong>Strona Zlecenia</strong> — strona Klienta dostępna przez indywidualny link z e-maila, na której Klient zarządza swoim Zleceniem (oferty, wybór, płatność, akceptacja, spór, opinia).</li>
      <li><strong>Panel Firmy</strong> — część Platformy dostępna po zalogowaniu dla Firmy.</li>
      <li><strong>Regulamin</strong> — niniejszy dokument.</li>
    </ul>

    <h2 id="p2">§ 2. Czym jest Fachora — role stron</h2>
    <p>1. Fachora jest platformą pośredniczącą. Usługodawca udostępnia narzędzia, dzięki którym Klient opisuje potrzebę, Firmy składają Oferty, Klient wybiera Firmę, a płatność, komunikacja, gwarancja i opinie przebiegają w uporządkowany sposób.</p>
    <p>2. <strong>Usługodawca nie wykonuje usług budowlanych, instalacyjnych, naprawczych ani innych usług będących przedmiotem Zleceń i nie jest stroną Umowy o Usługę.</strong> Umowę o Usługę zawierają wyłącznie Klient i Firma. Firma wykonuje Zlecenie na własny rachunek i własną odpowiedzialność, wystawia Klientowi fakturę lub inny dowód wykonania usługi i odpowiada za jakość pracy.</p>
    <p>3. Usługodawca świadczy na rzecz Klienta i Firmy usługi drogą elektroniczną: udostępnienie Platformy, przekazanie Zlecenia i Ofert, prowadzenie Strony Zlecenia i Panelu Firmy, obsługę Fachora Protect, mediację w sporach, publikację opinii i oznaczenia Fachora Verified.</p>
    <p>4. Oznaczenie „Fachora Verified” jest wynikiem weryfikacji opisanej w § 4 i oceną Usługodawcy na dzień jej przeprowadzenia. Nie stanowi gwarancji ani poręczenia za wykonanie usługi przez Firmę, z zastrzeżeniem Gwarancji Fachora opisanej w § 11.</p>
    <p>5. Usługodawca jest dostawcą platformy internetowej w rozumieniu rozporządzenia (UE) 2022/2065 (akt o usługach cyfrowych, DSA). Postanowienia wynikające z tego rozporządzenia znajdują się w § 4 ust. 3, § 14 i § 15.</p>

    <h2 id="p3">§ 3. Korzystanie z Platformy</h2>
    <p>1. Do korzystania z Platformy potrzebne są: urządzenie z dostępem do Internetu, aktualna przeglądarka z obsługą JavaScript i plików cookie oraz aktywny adres e-mail. Firma potrzebuje dodatkowo numeru telefonu komórkowego i konta w polskim banku.</p>
    <p>2. Korzystanie z Platformy przez Klienta jest <strong>bezpłatne</strong>. Klient płaci wyłącznie Firmie za wykonaną usługę — za pośrednictwem Fachora Protect albo bezpośrednio Firmie, jeżeli Protect nie jest dostępny dla danego Zlecenia.</p>
    <p>3. Klient nie zakłada konta. Dostęp do Zlecenia odbywa się przez indywidualny, poufny link przesłany na adres e-mail podany przy składaniu Zlecenia. Klient chroni ten link jak hasło; każdy, kto go posiada, może zarządzać Zleceniem.</p>
    <p>4. Zabronione jest korzystanie z Platformy w sposób niezgodny z prawem lub Regulaminem, w szczególności: składanie fikcyjnych Zleceń lub Ofert, podawanie nieprawdziwych danych, omijanie Platformy przy Zleceniach, które przez nią nadeszły (§ 13 ust. 6), nękanie drugiej strony, wprowadzanie treści niezgodnych z prawem (§ 15), automatyczne pobieranie danych z Platformy (scraping).</p>
    <p>5. Usługodawca może ograniczyć lub zablokować dostęp do Platformy osobie, która narusza ust. 4, oraz zablokować numer NIP Firmy, której Konto zostało usunięte z powodu poważnego naruszenia (§ 5 ust. 4).</p>

    <h2 id="p4">§ 4. Konto Firmy i weryfikacja</h2>
    <p>1. <strong>Profile z rejestrów.</strong> Usługodawca może utworzyć nieaktywny Profil Firmy na podstawie danych z publicznych rejestrów (CEIDG, KRS, wykaz podatników VAT). Taki Profil zawiera wyłącznie nazwę, miejscowość i rodzaj działalności; nie zawiera danych kontaktowych i nie jest oznaczony jako Verified. Usługodawca nie kieruje do takich Firm wiadomości e-mail ani SMS; może natomiast wysłać list pocztą z kodem umożliwiającym przejęcie Profilu. Firma może w każdej chwili zażądać usunięcia nieaktywnego Profilu (§ 17).</p>
    <p>2. <strong>Przejęcie Profilu.</strong> Firma przejmuje Profil, potwierdzając adres e-mail i numer telefonu, a jeżeli otrzymała list — podając kod z listu. Przejmując Profil, Firma oświadcza, że jest uprawniona do reprezentowania przedsiębiorcy, że podane dane są prawdziwe i aktualne, i akceptuje Regulamin oraz Standard Fachora. Z chwilą przejęcia Profilu zostaje zawarta umowa o korzystanie z Platformy między Firmą a Usługodawcą.</p>
    <p>3. <strong>Weryfikacja.</strong> Aby otrzymać oznaczenie Fachora Verified, Firma przechodzi weryfikację obejmującą:</p>
    <ul>
      <li>sprawdzenie w rejestrze (CEIDG lub KRS): aktywna działalność, zgodność numeru NIP i REGON, kod PKD odpowiadający wybranym kategoriom;</li>
      <li>sprawdzenie statusu VAT i rachunku bankowego w wykazie podatników VAT (biała lista), o ile dotyczy;</li>
      <li>potwierdzenie adresu e-mail i numeru telefonu;</li>
      <li>akceptację Regulaminu i Standardu Fachora z zapisem daty i wersji;</li>
      <li>weryfikację tożsamości osoby reprezentującej Firmę i rachunku bankowego, prowadzoną przez Operatora płatności w ramach procedury Stripe Connect (Usługodawca otrzymuje wynik, nie dokumenty);</li>
      <li>opcjonalnie: przesłanie polisy ubezpieczenia odpowiedzialności cywilnej; informacja o aktualnym ubezpieczeniu jest widoczna na Profilu do dnia wygaśnięcia polisy.</li>
    </ul>
    <p>Zbierane w ten sposób informacje odpowiadają obowiązkowi identyfikowalności przedsiębiorców z art. 30 DSA. Firma aktualizuje dane niezwłocznie, gdy ulegną zmianie.</p>
    <p>4. <strong>Decyzja.</strong> Nadanie, odmowa nadania, zawieszenie i cofnięcie oznaczenia Fachora Verified jest decyzją Usługodawcy, podejmowaną na podstawie wyników weryfikacji, Standardu Fachora i dotychczasowej współpracy. Usługodawca informuje Firmę o decyzji i jej powodach; Firma może odwołać się na adres {{ brand('email') }} w ciągu 14 dni, a Usługodawca rozpatruje odwołanie w ciągu 14 dni. Firma nie ma roszczenia o nadanie oznaczenia.</p>
    <p>5. <strong>Konto.</strong> Firma odpowiada za poufność danych logowania i za działania osób, którym udostępniła Panel Firmy. Firma może usunąć Konto w każdej chwili, z zastrzeżeniem zakończenia Zleceń w toku i rozliczenia prowizji; usunięcie Konta nie usuwa opinii ani danych wymaganych przepisami (§ 17).</p>
    <p>6. Wraz z Kontem Firma otrzymuje bezpłatny dostęp do narzędzia do wystawiania faktur w zakresie potrzebnym do rozliczania Zleceń. Korzystanie z niego w szerszym zakresie może podlegać odrębnemu regulaminowi.</p>

    <h2 id="p5">§ 5. Standard Fachora</h2>
    <p>1. Firma Verified zobowiązuje się przestrzegać Standardu Fachora:</p>
    <ol>
      <li>Prowadzi legalnie zarejestrowaną i aktywną działalność gospodarczą.</li>
      <li>Tożsamość osoby reprezentującej Firmę została zweryfikowana.</li>
      <li>Podaje jasną cenę z góry — w Ofercie, przed rozpoczęciem pracy.</li>
      <li>Nie wykonuje prac dodatkowych bez uprzedniej zgody Klienta wyrażonej na Stronie Zlecenia.</li>
      <li>Potwierdza umówiony termin; odwołuje go wyłącznie z ważnego powodu i możliwie wcześnie.</li>
      <li>Zawsze wystawia fakturę albo inny dowód wykonania usługi.</li>
      <li>Udziela gwarancji na wykonaną pracę tam, gdzie ma to zastosowanie (standardowo {{ (int) config('fachora.default_warranty_months') }} miesięcy).</li>
      <li>Odpowiada na reklamację i spór w ciągu {{ (int) config('fachora.dispute_hours') }} godzin.</li>
      <li>Rozlicza Zlecenia z Platformy zgodnie z zasadami Fachora (w tym Fachora Protect, jeżeli Klient go wybrał).</li>
      <li>Utrzymuje średnią ocenę co najmniej {{ $minRating }} (przy co najmniej 5 opiniach) i odpowiada na nowe Zlecenia w ciągu 24 godzin.</li>
    </ol>
    <p>2. <strong>Sygnały i sankcje.</strong> Naruszenie Standardu jest odnotowywane w Koncie Firmy. Dwa uzasadnione zgłoszenia Klientów w ciągu 90 dni skutkują zawieszeniem Profilu na 30 dni; po tym okresie Usługodawca ponownie ocenia Firmę. Spadek średniej oceny poniżej {{ $minRating }} skutkuje ostrzeżeniem, a przy braku poprawy — zawieszeniem.</p>
    <p>3. W okresie zawieszenia Profil nie jest oznaczony jako Verified, nie jest widoczny na listach i Firma nie otrzymuje nowych Zleceń; Zlecenia w toku są kończone.</p>
    <p>4. <strong>Usunięcie z Platformy</strong> następuje w razie poważnego naruszenia, w szczególności: oszustwa lub próby oszustwa, niestawienia się na Zlecenie po dokonanej rezerwacji Protect bez usprawiedliwienia, przyjmowania zapłaty poza Platformą za Zlecenie, które przez nią nadeszło, podania nieprawdziwych danych w weryfikacji, wygaśnięcia działalności. Usługodawca może wówczas zablokować numer NIP Firmy dla ponownej rejestracji.</p>
    <p>5. Wygaśnięcie warunku weryfikacji (np. wykreślenie z rejestru, utrata statusu VAT, wygaśnięcie uprawnień do wypłat u Operatora płatności) powoduje automatyczne zawieszenie do czasu uzupełnienia.</p>

    <h2 id="p6">§ 6. Zlecenia i oferty</h2>
    <p>1. <strong>Złożenie Zlecenia.</strong> Klient opisuje potrzebę w formularzu (kategoria, kod pocztowy lub miejscowość, opis, opcjonalnie zdjęcia, pilność, język obsługi) i podaje adres e-mail. Zlecenie staje się aktywne dopiero po <strong>potwierdzeniu linkiem z e-maila</strong>. Niepotwierdzone Zlecenie jest usuwane po {{ (int) config('fachora.confirm_days') }} dniach.</p>
    <p>2. Usługodawca może wykorzystać narzędzia automatyczne (w tym sztuczną inteligencję) do uporządkowania opisu, przetłumaczenia go na język polski dla Firm i oszacowania orientacyjnego przedziału cen. Oszacowanie nie jest ofertą ani wyceną i nie wiąże żadnej ze stron.</p>
    <p>3. <strong>Przekazanie Firmom.</strong> Potwierdzone Zlecenie jest przekazywane równocześnie maksymalnie <strong>{{ (int) config('fachora.max_offers') }} Firmom Verified</strong> dobranym według kategorii, odległości, języka, oceny i szybkości reakcji. Firma ma {{ (int) config('fachora.offer_hours') }} godzin na odpowiedź; w pilnych przypadkach termin może być krótszy. Jeżeli w okolicy nie ma Firmy Verified, Usługodawca informuje o tym Klienta i może poszukać Firmy ręcznie; nie gwarantuje, że Zlecenie otrzyma jakąkolwiek Ofertę.</p>
    <p>4. <strong>Oferta.</strong> Firma składa Ofertę przez Platformę, podając: cenę <strong>brutto</strong> (z VAT) — ryczałtową za całe Zlecenie albo stawki (dojazd, pierwsza godzina, kolejne godziny) wraz z szacowaną liczbą godzin — proponowany termin, okres gwarancji i ewentualne warunki. Oferta wiąże Firmę przez czas wskazany w Ofercie, a jeżeli go nie wskazano — przez 7 dni.</p>
    <p>5. <strong>Cena ryczałtowa jest wiążąca.</strong> Firma nie może żądać wyższej kwoty niż w Ofercie, chyba że Klient zatwierdził prace dodatkowe zgodnie z ust. 6. Przy stawkach godzinowych wiążące są stawki; Firma informuje Klienta, gdy rzeczywisty czas pracy ma przekroczyć szacunek, a Klient może wówczas zdecydować o kontynuacji.</p>
    <p>6. <strong>Prace dodatkowe.</strong> Jeżeli w trakcie wykonywania Zlecenia okaże się, że konieczne są prace lub materiały nieobjęte Ofertą, Firma dodaje je jako osobną pozycję z ceną na Stronie Zlecenia. Prace dodatkowe są wykonywane i płatne <strong>wyłącznie po zatwierdzeniu ich przez Klienta</strong> na Stronie Zlecenia. Prace wykonane bez zatwierdzenia nie podlegają zapłacie przez Platformę.</p>
    <p>7. <strong>Wybór.</strong> Klient porównuje Oferty na Stronie Zlecenia i wybiera jedną z nich albo nie wybiera żadnej. Klient nie ma obowiązku wyboru. Platforma nie pokazuje Klientowi numeru telefonu ani adresu e-mail Firmy przed wyborem, a Firmie — danych Klienta ponad to, co jest potrzebne do złożenia Oferty.</p>

    <h2 id="p7">§ 7. Umowa między Klientem a Firmą</h2>
    <p>1. Z chwilą wyboru Oferty przez Klienta zostaje zawarta <strong>Umowa o Usługę między Klientem a Firmą</strong> na warunkach określonych w Zleceniu i Ofercie. Usługodawca potwierdza jej zawarcie obu stronom w <strong>potwierdzeniu zlecenia</strong> (e-mail i dokument do pobrania), które zawiera: opis prac, cenę, termin, okres gwarancji, informację o fakturze i sposobie płatności.</p>
    <p>2. Umowa o Usługę jest umową zawieraną na odległość za pośrednictwem Platformy. Firma jest przedsiębiorcą zobowiązanym wobec Klienta-konsumenta z tytułu tej umowy, w tym do wykonania obowiązków informacyjnych; Usługodawca pomaga je spełnić, udostępniając dane Firmy i potwierdzenie zlecenia.</p>
    <p>3. Po wyborze Oferty Firma otrzymuje dane niezbędne do wykonania Zlecenia (imię i nazwisko lub nazwa Klienta, adres wykonania usługi, dane kontaktowe do umówienia terminu), a Klient — dane Firmy wymagane przepisami (nazwa, adres, NIP) oraz możliwość kontaktu przez Platformę.</p>
    <p>4. Firma potwierdza termin, informuje o dojeździe i zgłasza na Platformie rozpoczęcie i zakończenie pracy, dołączając zdjęcia wykonanych prac tam, gdzie to możliwe.</p>
    <p>5. Usługodawca nie jest stroną Umowy o Usługę, nie udziela gwarancji na prace Firmy i nie odpowiada za ich wykonanie, z zastrzeżeniem Gwarancji Fachora (§ 11) i zasad Fachora Protect (§ 8).</p>

    <h2 id="p8">§ 8. Fachora Protect — płatność</h2>
    <p>1. Fachora Protect jest dostępny, jeżeli wybrana Firma ma aktywne konto u Operatora płatności z włączonymi wypłatami. Informacja o dostępności Protect jest widoczna na Profilu i w Ofercie. Jeżeli Protect nie jest dostępny, Klient płaci Firmie bezpośrednio po wykonaniu usługi, na warunkach Oferty, bez Gwarancji Fachora.</p>
    <p>2. <strong>Rezerwacja.</strong> Po wyborze Oferty z Protect Klient płaci kwotę Oferty (brutto) przez stronę płatności Operatora płatności (BLIK, Przelewy24, karta lub inne udostępnione metody). <strong>Środki są przechowywane przez Operatora płatności na rachunku platformy;</strong> Usługodawca nie prowadzi rachunków płatniczych ani nie przechowuje środków Klientów we własnym imieniu. Do płatności stosuje się regulaminy Operatora płatności (stripe.com/legal).</p>
    <p>3. Zatwierdzone prace dodatkowe (§ 6 ust. 6) Klient opłaca w ten sam sposób, przed ich wykonaniem albo niezwłocznie po zatwierdzeniu.</p>
    <p>4. <strong>Akceptacja.</strong> Po zgłoszeniu zakończenia pracy przez Firmę Klient sprawdza wykonanie i na Stronie Zlecenia: (a) akceptuje pracę albo (b) zgłasza spór (§ 9). <strong>Jeżeli Klient nie zrobi żadnej z tych rzeczy w ciągu {{ (int) config('fachora.auto_accept_days') }} dni od zgłoszenia zakończenia, pracę uznaje się za zaakceptowaną</strong> (akceptacja milcząca). Usługodawca przypomina Klientowi o tym terminie e-mailem.</p>
    <p>5. <strong>Wypłata.</strong> Po akceptacji (wyraźnej lub milczącej) Operator płatności przekazuje Firmie kwotę Zlecenia pomniejszoną o prowizję Usługodawcy (§ 13). Firma wystawia Klientowi fakturę na pełną kwotę Zlecenia. Termin dotarcia środków na rachunek Firmy zależy od Operatora płatności.</p>
    <p>6. <strong>Zwrot.</strong> Środki wracają do Klienta w całości, jeżeli: Zlecenie zostało odwołane zgodnie z § 10 przed rozpoczęciem pracy (z zastrzeżeniem opłaty za dojazd w § 10 ust. 2), Firma nie stawiła się i nie uzgodniono nowego terminu, albo Usługodawca tak rozstrzygnął spór. Zwrot następuje tą samą metodą płatności, w terminie zależnym od Operatora płatności (zwykle do 14 dni).</p>
    <p>7. Korzystanie z Protect jest dla Klienta bezpłatne. Usługodawca może w przyszłości wprowadzić opłatę za Protect dla Klienta; będzie ona widoczna przed płatnością i nie obejmie Zleceń rozpoczętych wcześniej.</p>
    <p>8. Klient i Firma zobowiązują się <strong>nie omijać Protect</strong> przy Zleceniu, dla którego został wybrany: Firma nie przyjmuje od Klienta dodatkowej zapłaty poza Platformą, a Klient nie płaci Firmie bezpośrednio. Zapłata poza Platformą pozbawia Klienta Gwarancji Fachora, a Firmę naraża na usunięcie z Platformy (§ 5 ust. 4).</p>

    <h2 id="p9">§ 9. Spory</h2>
    <p>1. Jeżeli Klient uważa, że praca nie została wykonana lub została wykonana niezgodnie z Umową o Usługę, zgłasza <strong>spór</strong> na Stronie Zlecenia w terminie z § 8 ust. 4, opisując problem i dołączając zdjęcia. Zgłoszenie sporu wstrzymuje wypłatę.</p>
    <p>2. Firma ma <strong>{{ (int) config('fachora.dispute_hours') }} godzin</strong> na odpowiedź na Platformie (wyjaśnienie, propozycja naprawy, propozycja obniżenia ceny). Strony mogą w tym czasie uzgodnić rozwiązanie samodzielnie; uzgodnienie zapisane na Platformie kończy spór.</p>
    <p>3. Jeżeli strony nie dojdą do porozumienia albo Firma nie odpowie w terminie, <strong>Usługodawca rozstrzyga spór</strong> na podstawie Zlecenia, Oferty, potwierdzenia zlecenia, zdjęć, korespondencji na Platformie i wyjaśnień stron, kierując się zasadami rozsądku i dobrej wiary. Usługodawca może poprosić o dodatkowe informacje. Rozstrzygnięcie może polegać na: wypłacie pełnej kwoty Firmie, zwrocie pełnej kwoty Klientowi, podziale kwoty między strony, albo wyznaczeniu terminu naprawy. Brak odpowiedzi Firmy w terminie jest rozstrzygany na korzyść Klienta.</p>
    <p>4. Usługodawca dąży do rozstrzygnięcia w ciągu 14 dni od upływu terminu na odpowiedź Firmy i przekazuje je obu stronom wraz z uzasadnieniem. Rozstrzygnięcie dotyczy wyłącznie środków zarezerwowanych w Protect i wykonywane jest przez Operatora płatności.</p>
    <p>5. <strong>Rozstrzygnięcie sporu przez Usługodawcę nie zastępuje drogi sądowej ani pozasądowych metod rozwiązywania sporów konsumenckich</strong> i nie ogranicza roszczeń stron z Umowy o Usługę. Strona, która nie zgadza się z rozstrzygnięciem, może dochodzić roszczeń od drugiej strony Umowy o Usługę na zasadach ogólnych (§ 20). Rozstrzygnięcie co do wypłaty środków z Protect jest ostateczne w relacji z Usługodawcą.</p>

    <h2 id="p10">§ 10. Odwołanie Zlecenia i niestawiennictwo</h2>
    <p>1. <strong>Przez Klienta.</strong> Klient może odwołać Zlecenie na Stronie Zlecenia <strong>bezpłatnie do {{ (int) config('fachora.cancel_free_hours') }} godzin przed umówionym terminem</strong>. Odwołanie Zlecenia bez Protect jest zawsze możliwe do momentu rozpoczęcia pracy, z zastrzeżeniem ust. 2.</p>
    <p>2. Przy odwołaniu później niż {{ (int) config('fachora.cancel_free_hours') }} godzin przed terminem albo gdy Firma stawi się na miejscu, a Klient odwoła Zlecenie, Firma może zażądać <strong>opłaty za dojazd</strong> w wysokości podanej w Ofercie (jeżeli Oferta jej nie zawiera — opłata nie przysługuje). Przy Protect opłata ta jest potrącana ze zwrotu. Nie dotyczy to odwołania z powodu, za który odpowiada Firma (np. zmiana terminu przez Firmę).</p>
    <p>3. <strong>Przez Firmę.</strong> Firma może odwołać potwierdzone Zlecenie wyłącznie z ważnego powodu (choroba, awaria, siła wyższa), możliwie wcześnie i z podaniem powodu na Platformie; w miarę możliwości proponuje nowy termin. Odwołanie bez powodu lub <strong>niestawienie się</strong> w umówionym terminie bez uprzedzenia jest naruszeniem Standardu Fachora (§ 5); Klient otrzymuje pełny zwrot zarezerwowanych środków i może zlecić pracę innej Firmie.</p>
    <p>4. Jeżeli Firma nie może wykonać Zlecenia, Usługodawca może — za zgodą Klienta — przekazać Zlecenie innym Firmom Verified.</p>

    <h2 id="p11">§ 11. Gwarancja, reklamacje i Gwarancja Fachora</h2>
    <p>1. <strong>Odpowiedzialność Firmy.</strong> Za wykonanie Umowy o Usługę odpowiada Firma zgodnie z przepisami prawa (w szczególności Kodeksu cywilnego o umowie o dzieło i rękojmi oraz przepisów o ochronie konsumentów) i z Ofertą. Postanowienia Regulaminu nie ograniczają ustawowej odpowiedzialności Firmy wobec Klienta.</p>
    <p>2. <strong>Gwarancja Firmy.</strong> Firma udziela gwarancji na wykonaną pracę na okres wskazany w Ofercie (standardowo {{ (int) config('fachora.default_warranty_months') }} miesięcy od akceptacji). Gwarancja obejmuje wady wykonanej pracy; nie obejmuje normalnego zużycia, uszkodzeń mechanicznych, ingerencji osób trzecich ani wad materiałów dostarczonych przez Klienta, chyba że Oferta stanowi inaczej. Gwarancja nie wyłącza uprawnień ustawowych.</p>
    <p>3. <strong>Reklamacja w okresie gwarancji.</strong> Klient zgłasza wadę na Stronie Zlecenia (zgłoszenie otwiera Zlecenie ponownie). Firma odpowiada w ciągu {{ (int) config('fachora.dispute_hours') }} godzin i uzgadnia termin naprawy. Usługodawca pośredniczy w komunikacji i monitoruje realizację.</p>
    <p>4. <strong>Gwarancja Fachora.</strong> Dla Zleceń opłaconych przez Fachora Protect Usługodawca dodatkowo zapewnia:</p>
    <ul>
      <li><strong>mediację</strong> — prowadzenie sporu i reklamacji gwarancyjnej zgodnie z § 9 i ust. 3, oraz</li>
      <li><strong>naprawę zastępczą</strong> — jeżeli Firma nie odpowiada na reklamację, odmawia naprawy uzasadnionej wady albo nie wykonuje jej w uzgodnionym terminie, Usługodawca może, według własnej oceny, zlecić usunięcie wady innej Firmie Verified i pokryć jej koszt <strong>do wysokości kwoty Zlecenia zapłaconej przez Protect</strong>.</li>
    </ul>
    <p>Gwarancja Fachora jest dobrowolnym świadczeniem Usługodawcy, ograniczonym do powyższego zakresu; nie obejmuje szkód następczych, utraconych korzyści ani Zleceń opłaconych poza Protect. Usługodawca może odmówić naprawy zastępczej, jeżeli wada nie jest objęta gwarancją Firmy (ust. 2), Klient uniemożliwił Firmie naprawę albo zgłoszenie nastąpiło po upływie okresu gwarancji. Usługodawca, który pokrył koszt naprawy zastępczej, może dochodzić jego zwrotu od Firmy.</p>

    <h2 id="p12">§ 12. Prawa konsumenta</h2>
    <p>1. Klient będący konsumentem ma prawo <strong>odstąpić od Umowy o Usługę zawartej na odległość w terminie 14 dni</strong> od jej zawarcia bez podania przyczyny (art. 27 ustawy z dnia 30 maja 2014 r. o prawach konsumenta), składając oświadczenie Firmie — także przez Stronę Zlecenia lub na adres {{ brand('email') }}, który przekażemy Firmie. Odwołanie Zlecenia na Stronie Zlecenia jest równoznaczne ze złożeniem oświadczenia o odstąpieniu.</p>
    <p>2. <strong>Wykonanie usługi przed upływem terminu.</strong> Aby usługa mogła być wykonana przed upływem 14 dni (co jest typowe dla napraw), Klient przy wyborze Oferty i rezerwacji wyraźnie żąda rozpoczęcia świadczenia usługi przed upływem terminu do odstąpienia i przyjmuje do wiadomości, że <strong>po pełnym wykonaniu usługi traci prawo odstąpienia</strong> (art. 38 ust. 1 pkt 1 ustawy o prawach konsumenta). Jeżeli Klient odstąpi po rozpoczęciu, a przed pełnym wykonaniem usługi, zapłaci Firmie za świadczenia spełnione do chwili odstąpienia, proporcjonalnie do ceny z Oferty.</p>
    <p>3. Prawo odstąpienia nie przysługuje także w innych przypadkach wskazanych w art. 38 ustawy o prawach konsumenta, w szczególności przy pilnych naprawach, o których wykonanie konsument wyraźnie żądał (art. 38 ust. 1 pkt 8), w zakresie prac, o które żądał.</p>
    <p>4. Konsument może odstąpić również od umowy z Usługodawcą o korzystanie z Platformy w terminie 14 dni; ponieważ korzystanie z Platformy jest dla Klienta bezpłatne, odstąpienie nie wiąże się z żadnymi rozliczeniami.</p>
    <p>5. Przedsiębiorca będący osobą fizyczną, dla którego Umowa o Usługę nie ma charakteru zawodowego, korzysta z ochrony konsumenckiej w zakresie przewidzianym przepisami.</p>
    <p>6. Żadne postanowienie Regulaminu nie wyłącza ani nie ogranicza praw konsumenta wynikających z bezwzględnie obowiązujących przepisów prawa; w razie sprzeczności pierwszeństwo mają przepisy.</p>

    <h2 id="p13">§ 13. Opłaty i prowizja</h2>
    @if ($lead)
    <p>1. Platforma jest dla Klienta bezpłatna. Firma nie płaci abonamentu ani opłaty rejestracyjnej. Podgląd Zlecenia (opis, zdjęcia, termin, miejscowość) jest bezpłatny.</p>
    <p>2. <strong>Opłata za odblokowanie.</strong> Aby złożyć Ofertę, Firma odblokowuje Zlecenie. Opłata wynosi {{ $leadPct }}% szacowanej wartości Zlecenia określonej przez Platformę, nie mniej niż {{ (int) config('fachora.lead_min') }} zł i nie więcej niż {{ (int) config('fachora.lead_max') }} zł brutto (z VAT 23%). Kwota jest widoczna przed odblokowaniem. Pierwsze odblokowanie każdej Firmy jest bezpłatne. Odblokowane Zlecenie pozostaje odblokowane: zmiana lub wycofanie Oferty nie kosztuje nic.</p>
    <p>3. Opłata za odblokowanie należy się niezależnie od tego, czy Klient wybierze Ofertę Firmy. Jeżeli Zlecenie okaże się nieprawdziwe (np. fałszywe dane Klienta albo zlecenie zgłoszone wyłącznie w celu pozyskania ofert przez konkurencję), Firma może to zgłosić w ciągu 7 dni od odblokowania; po weryfikacji Usługodawca zwraca opłatę.</p>
    <p>4. <strong>Prowizja przy Fachora Protect.</strong> Za Zlecenie opłacone przez Fachora Protect i zaakceptowane (wyraźnie lub milcząco) Usługodawca potrąca z wypłaty prowizję w wysokości <strong>{{ $fee }}%</strong> kwoty brutto Zlecenia, łącznie z zatwierdzonymi pracami dodatkowymi. Przy Zleceniach rozliczanych bezpośrednio między Klientem a Firmą prowizja nie jest pobierana.</p>
    <p>5. Prowizja jest kwotą netto, do której Usługodawca dolicza podatek VAT według stawki obowiązującej w Polsce (obecnie 23%). Na opłaty i prowizję Usługodawca wystawia fakturę; Firma wyraża zgodę na otrzymywanie faktur drogą elektroniczną. Jeżeli Zlecenie z Protect zostało rozstrzygnięte zwrotem na rzecz Klienta, prowizja nie przysługuje; przy podziale kwoty prowizja jest liczona od części wypłaconej Firmie.</p>
    <p>6. Usługodawca może zmienić wysokość opłat i prowizji z 30-dniowym wyprzedzeniem, informując Firmę e-mailem i w Panelu Firmy. Nowe stawki dotyczą Zleceń odblokowanych po ich wejściu w życie. Firma, która nie akceptuje zmiany, może usunąć Konto przed tym terminem.</p>
    @else
    <p>1. Platforma jest dla Klienta bezpłatna. Firma nie płaci abonamentu, opłaty rejestracyjnej ani opłat za otrzymane Zlecenia lub złożone Oferty.</p>
    <p>2. <strong>Prowizja.</strong> Za każde Zlecenie wykonane i zaakceptowane (wyraźnie lub milcząco) Firma płaci Usługodawcy prowizję w wysokości <strong>{{ $fee }}%</strong> kwoty brutto Zlecenia, łącznie z zatwierdzonymi pracami dodatkowymi. W okresie <strong>{{ (int) config('fachora.intro_days') }} dni</strong> od nadania oznaczenia Fachora Verified prowizja wynosi <strong>{{ $introFee }}%</strong>. Stawka obowiązująca Firmę jest widoczna w Panelu Firmy i w każdej Ofercie przed jej złożeniem.</p>
    <p>3. Przy Zleceniach z Protect prowizja jest potrącana z wypłaty przez Operatora płatności. Przy Zleceniach bez Protect Usługodawca wystawia Firmie fakturę za prowizję z terminem płatności 14 dni; Firma zgłasza na Platformie wykonanie Zlecenia i ostateczną kwotę.</p>
    <p>4. Prowizja jest kwotą netto, do której Usługodawca dolicza podatek VAT według stawki obowiązującej w Polsce (obecnie 23%). Firma wyraża zgodę na otrzymywanie faktur drogą elektroniczną.</p>
    <p>5. Jeżeli Zlecenie zostało rozstrzygnięte zwrotem na rzecz Klienta, prowizja nie przysługuje; przy podziale kwoty prowizja jest liczona od części wypłaconej Firmie. Przy odwołaniu Zlecenia prowizja od opłaty za dojazd nie jest pobierana.</p>
    <p>6. <strong>Zakaz omijania.</strong> Jeżeli Firma wykona dla Klienta pracę, o którą Klient zwrócił się przez Platformę, rozliczając ją poza Platformą, prowizja należy się Usługodawcy w pełnej wysokości od kwoty Oferty, niezależnie od sankcji z § 5 ust. 4. Nie dotyczy to Zleceń, które Klient odwołał, a następnie — po upływie 6 miesięcy — zlecił Firmie poza Platformą.</p>
    <p>7. Usługodawca może zmienić wysokość prowizji z 30-dniowym wyprzedzeniem, informując Firmę e-mailem i w Panelu Firmy. Nowa stawka dotyczy Ofert złożonych po jej wejściu w życie. Firma, która nie akceptuje zmiany, może usunąć Konto przed tym terminem.</p>
    @endif
    <p>8. Usługi dodatkowe dla Firm (np. pakiet SMS, usługi promowane), jeżeli zostaną wprowadzone, są płatne według cennika widocznego w Panelu Firmy przed ich zamówieniem.</p>

    <h2 id="p14">§ 14. Opinie</h2>
    <p>1. Opinię o Firmie (ocena 1–5 i tekst) może wystawić wyłącznie Klient <strong>po zakończeniu i akceptacji Zlecenia</strong> przez Platformę, w ciągu 30 dni od akceptacji. Każda opinia jest powiązana z konkretnym Zleceniem; Usługodawca w ten sposób zapewnia, że opinie pochodzą od osób, które rzeczywiście skorzystały z usługi.</p>
    <p>2. Firma może <strong>odpowiedzieć</strong> na opinię jeden raz; odpowiedź jest publikowana pod opinią. Firma nie może usunąć ani zmienić opinii.</p>
    <p>3. Opinia powinna dotyczyć wykonanego Zlecenia i być zgodna z prawem i dobrymi obyczajami. Usługodawca może odmówić publikacji lub usunąć opinię, która zawiera treści niezgodne z prawem, dane osobowe osób trzecich, wulgaryzmy, treści niezwiązane ze Zleceniem lub została wystawiona w zamian za korzyść. O usunięciu Usługodawca informuje autora i Firmę z podaniem powodu; obie strony mogą się odwołać zgodnie z § 15 ust. 5.</p>
    <p>4. Średnia ocena i liczba opinii na Profilu są obliczane automatycznie ze wszystkich opublikowanych opinii. Usługodawca nie kupuje, nie sprzedaje i nie moderuje opinii ze względu na ich treść pozytywną lub negatywną.</p>
    <p>5. Opinie pozostają widoczne także po usunięciu Konta Firmy, o ile Profil pozostaje na Platformie.</p>

    <h2 id="p15">§ 15. Treści, zgłaszanie treści niezgodnych z prawem, punkt kontaktowy</h2>
    <p>1. Treści na Platformie wprowadzane przez użytkowników (opisy Zleceń, zdjęcia, Oferty, opisy Firm, opinie, odpowiedzi) są treściami ich autorów. Usługodawca nie monitoruje ich w sposób ogólny, ale reaguje na zgłoszenia.</p>
    <p>2. <strong>Punkt kontaktowy.</strong> Dla organów państw członkowskich, Komisji Europejskiej i Rady Usług Cyfrowych (art. 11 DSA) oraz dla użytkowników (art. 12 DSA) Usługodawca wyznacza jeden punkt kontaktowy: <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a>, a dla korespondencji pisemnej — adres siedziby wskazany na początku Regulaminu. Komunikacja może odbywać się w języku polskim, angielskim i niderlandzkim.</p>
    <p>3. <strong>Zgłoszenie treści niezgodnych z prawem</strong> (art. 16 DSA). Każdy może zgłosić treść, którą uważa za niezgodną z prawem, wysyłając na powyższy adres wiadomość zawierającą: wyjaśnienie, dlaczego treść jest niezgodna z prawem, dokładne wskazanie treści (adres strony), imię i nazwisko oraz adres e-mail zgłaszającego (z wyjątkiem zgłoszeń dotyczących przestępstw przeciwko wolności seksualnej) oraz oświadczenie, że zgłoszenie jest dokonane w dobrej wierze, a informacje są dokładne i kompletne. Usługodawca potwierdza otrzymanie zgłoszenia, rozpatruje je bez zbędnej zwłoki, w sposób staranny i obiektywny, i informuje zgłaszającego o decyzji.</p>
    <p>4. <strong>Uzasadnienie decyzji</strong> (art. 17 DSA). O usunięciu treści, ograniczeniu jej widoczności, zawieszeniu lub usunięciu Konta Usługodawca informuje użytkownika, którego to dotyczy, podając powody, podstawę (prawo lub Regulamin), fakty i okoliczności oraz dostępne środki odwoławcze.</p>
    <p>5. <strong>Odwołanie.</strong> Użytkownik, którego dotyczy decyzja z ust. 4, oraz osoba, której zgłoszenie nie zostało uwzględnione, mogą w ciągu 6 miesięcy odwołać się na adres {{ brand('email') }}. Odwołania są rozpatrywane przez osobę, a nie wyłącznie automatycznie, w sposób terminowy, bezstronny i bezpłatny. Po wyczerpaniu tej drogi można skorzystać z certyfikowanego organu pozasądowego rozstrzygania sporów (art. 21 DSA) albo z drogi sądowej.</p>
    <p>6. Usługodawca może zawiesić na rozsądny okres rozpatrywanie zgłoszeń od osób, które często składają zgłoszenia oczywiście bezzasadne, po uprzednim ostrzeżeniu.</p>
    <p>7. Umieszczając treści na Platformie, użytkownik udziela Usługodawcy nieodpłatnej licencji na ich przechowywanie, wyświetlanie i przekazywanie w zakresie potrzebnym do działania Platformy (w tym przekazanie opisu i zdjęć Zlecenia Firmom oraz tłumaczenie) i oświadcza, że ma do tego prawo.</p>

    <h2 id="p16">§ 16. Odpowiedzialność</h2>
    <p>1. Usługodawca odpowiada za należyte świadczenie własnych usług opisanych w § 2 ust. 3. Usługodawca nie odpowiada za wykonanie Umowy o Usługę przez Firmę ani za zachowanie Klienta wobec Firmy, z zastrzeżeniem Gwarancji Fachora (§ 11 ust. 4).</p>
    <p>2. Oznaczenie Fachora Verified, oceny, oszacowania cen i inne informacje na Platformie są przygotowywane z należytą starannością na podstawie dostępnych źródeł (rejestry publiczne, Operator płatności, oświadczenia Firm, opinie Klientów). Usługodawca nie gwarantuje ich aktualności w każdym momencie; Firma odpowiada za aktualność własnych danych.</p>
    <p>3. Usługodawca dokłada starań, aby Platforma działała bez przerw, ale może je zawiesić na czas konserwacji lub z przyczyn od niego niezależnych (awaria dostawców hostingu, poczty, Operatora płatności). O planowanych przerwach informuje z wyprzedzeniem.</p>
    <p>4. Wobec Firm (przedsiębiorców) odpowiedzialność Usługodawcy z tytułu umowy o korzystanie z Platformy jest ograniczona do szkody rzeczywistej i do wysokości prowizji zapłaconej przez Firmę w ciągu 12 miesięcy poprzedzających zdarzenie; Usługodawca nie odpowiada za utracone korzyści. Ograniczenie nie dotyczy szkody wyrządzonej umyślnie.</p>
    <p>5. Wobec konsumentów Usługodawca odpowiada na zasadach określonych przepisami prawa; ograniczenia z ust. 4 nie mają do nich zastosowania.</p>

    <h2 id="p17">§ 17. Dane osobowe</h2>
    <p>1. Administratorem danych osobowych Klientów, osób reprezentujących Firmy i osób odwiedzających Platformę jest Usługodawca. Zasady przetwarzania, podstawy prawne, odbiorcy danych, okresy przechowywania i prawa osób opisuje <a href="{{ route('pl.prywatnosc') }}">Polityka prywatności</a>, która jest częścią informacji przekazywanych przy zawieraniu umowy.</p>
    <p>2. Firma, która otrzymuje dane Klienta w celu wykonania Zlecenia, jest ich odrębnym administratorem i wykorzystuje je wyłącznie do wykonania Umowy o Usługę, wystawienia faktury i realizacji gwarancji. Firma nie może wykorzystywać danych Klienta do marketingu bez jego zgody.</p>
    <p>3. Firma, której nieaktywny Profil powstał na podstawie rejestru publicznego, może zażądać jego usunięcia na adres {{ brand('email') }}; Usługodawca usuwa Profil w ciągu 14 dni i zachowuje wyłącznie numer NIP z datą żądania, aby nie utworzyć Profilu ponownie.</p>

    <h2 id="p18">§ 18. Reklamacje dotyczące usług Usługodawcy</h2>
    <p>1. Reklamacje dotyczące działania Platformy, Fachora Protect, weryfikacji, opinii lub innych usług Usługodawcy można składać na adres <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a> albo pisemnie na adres siedziby. Reklamacja powinna zawierać dane składającego, numer Zlecenia (jeżeli dotyczy) i opis problemu.</p>
    <p>2. Usługodawca odpowiada na reklamację w terminie 14 dni od jej otrzymania, drogą elektroniczną. Reklamacje dotyczące samej usługi wykonanej przez Firmę rozpatruje Firma; Usługodawca przekazuje je Firmie i pośredniczy zgodnie z § 9 i § 11.</p>

    <h2 id="p19">§ 19. Zmiany Regulaminu</h2>
    <p>1. Usługodawca może zmienić Regulamin z ważnych przyczyn: zmiany przepisów lub ich wykładni, zmiany zakresu lub sposobu działania Platformy, wymagań Operatora płatności, względów bezpieczeństwa, konieczności doprecyzowania postanowień.</p>
    <p>2. O zmianie Firmy są informowane e-mailem i w Panelu Firmy co najmniej 14 dni przed jej wejściem w życie (30 dni przy zmianie prowizji). Firma, która nie akceptuje zmian, może usunąć Konto przed ich wejściem w życie; dalsze korzystanie z Platformy po tym terminie oznacza akceptację.</p>
    <p>3. Do Zlecenia stosuje się Regulamin w wersji obowiązującej w chwili potwierdzenia Zlecenia przez Klienta; zmiany korzystne dla Klienta mogą być stosowane od razu. Każda wersja Regulaminu jest oznaczona numerem i datą; poprzednie wersje są dostępne na żądanie.</p>
    <p>4. Zmiany redakcyjne i rozszerzenie funkcji, które nie ograniczają praw użytkowników, mogą wejść w życie z chwilą publikacji.</p>

    <h2 id="p20">§ 20. Prawo właściwe i rozstrzyganie sporów</h2>
    <p>1. Do umów zawieranych na podstawie Regulaminu — między Usługodawcą a Klientem, między Usługodawcą a Firmą oraz do Umowy o Usługę — stosuje się <strong>prawo polskie</strong>. Wybór prawa nie pozbawia konsumenta ochrony wynikającej z przepisów, których nie można wyłączyć umową, obowiązujących w państwie jego zwykłego pobytu.</p>
    <p>2. Spory z konsumentami rozstrzyga sąd powszechny właściwy według przepisów Kodeksu postępowania cywilnego. Spory między Usługodawcą a Firmą rozstrzyga sąd powszechny właściwy dla miasta stołecznego Warszawy.</p>
    <p>3. <strong>Pozasądowe rozwiązywanie sporów konsumenckich.</strong> Konsument może skorzystać z pomocy powiatowego (miejskiego) rzecznika konsumentów, z organizacji konsumenckich (np. Federacja Konsumentów), z postępowania w sprawie pozasądowego rozwiązywania sporów konsumenckich prowadzonego przez Wojewódzkiego Inspektora Inspekcji Handlowej (wnioski do WIIH właściwego ze względu na miejsce wykonywania działalności przedsiębiorcy) oraz ze stałych sądów polubownych przy WIIH. Informacje: <a href="https://uokik.gov.pl" target="_blank" rel="noopener">uokik.gov.pl</a>. Dotyczy to zarówno sporów z Usługodawcą, jak i — co do Umowy o Usługę — sporów z Firmą. Unijna platforma ODR zakończyła działanie w lipcu 2025 r.</p>
    <p>4. Usługodawca deklaruje gotowość do udziału w pozasądowym rozwiązywaniu sporów konsumenckich dotyczących własnych usług. Spory między Klientem a Firmą Usługodawca pomaga rozwiązać w trybie § 9, który nie jest postępowaniem ADR w rozumieniu ustawy o pozasądowym rozwiązywaniu sporów konsumenckich.</p>

    <h2 id="p21">§ 21. Postanowienia końcowe</h2>
    <p>1. Regulamin jest dostępny bezpłatnie pod adresem fachora.pl/regulamin w formie umożliwiającej jego pobranie, utrwalenie i wydrukowanie. Wersja polska jest wiążąca; tłumaczenie angielskie służy wygodzie.</p>
    <p>2. Jeżeli którekolwiek postanowienie Regulaminu okaże się nieważne lub bezskuteczne, pozostałe postanowienia pozostają w mocy.</p>
    <p>3. Integralną częścią Regulaminu są: <a href="{{ route('fachora.standard') }}">Standard Fachora</a> (streszczony w § 5), opis <a href="{{ route('fachora.protect') }}">Fachora Protect</a> i <a href="{{ route('pl.prywatnosc') }}">Polityka prywatności</a>. W razie rozbieżności pierwszeństwo ma Regulamin.</p>
    <p>4. Kontakt z Usługodawcą: <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a>, <a href="{{ route('pl.kontakt') }}">formularz kontaktowy</a> lub listownie: Budomatch Daniël de Graaf, Białka 15, 09-550 Białka.</p>
    <p>5. Regulamin w wersji {{ config('fachora.terms_version') }} wchodzi w życie 16 października 2026 r.</p>

    <div class="disclaimer">Wersja robocza do weryfikacji przez prawnika. Uwagi: <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a>.</div>
  </div>
</section>
@endsection
