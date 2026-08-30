@extends('layouts.marketing')

{{--
  WERSJA ROBOCZA — DO WERYFIKACJI PRZEZ PRAWNIKA.
  Polityka prywatności (RODO) dla Lopra Polska. Administrator: Creditline B.V.
  Projekt na podstawie holenderskiej polityki prywatności; przed publikacją jako
  wiążący dokument wymaga sprawdzenia przez polskiego prawnika (RODO, ustawa
  o ochronie danych osobowych, Prawo telekomunikacyjne / PKE w zakresie cookies).
--}}

@section('title', 'Polityka prywatności — ' . brand('name'))
@section('description', 'Jak ' . brand('name') . ' (Creditline B.V.) przetwarza dane osobowe: cele i podstawy prawne, kategorie danych, odbiorcy i podmioty przetwarzające, okres przechowywania, prawa osób, pliki cookie. Zgodnie z RODO, dane w Unii Europejskiej.')

@section('content')
<style>
  .legal{padding:60px 0 80px;}
  .legal .container{max-width:760px;}
  .legal h1{font-size:clamp(30px,5vw,42px);margin-bottom:10px;}
  .legal .meta{color:var(--text-3);font-size:14px;margin-bottom:26px;}
  .legal .draft{display:flex;gap:10px;align-items:flex-start;background:#FEF3C7;border:1px solid #FCD34D;color:#92400E;border-radius:12px;padding:12px 16px;margin-bottom:26px;font-size:13.5px;line-height:1.6;}
  .legal .draft b{display:block;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;margin-bottom:2px;}
  .legal .entity{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:18px 20px;margin-bottom:34px;box-shadow:var(--shadow-sm);font-size:14.5px;line-height:1.75;color:var(--text-2);}
  .legal .entity strong{color:var(--text);}
  .legal h2{font-size:20px;margin:34px 0 10px;}
  .legal h3{font-size:16px;margin:22px 0 6px;}
  .legal p{color:var(--text-2);margin:0 0 14px;line-height:1.75;}
  .legal ul,.legal ol{color:var(--text-2);margin:0 0 16px;padding-left:20px;line-height:1.75;}
  .legal li{margin-bottom:7px;}
  .legal a{color:var(--brand);font-weight:500;}
  .legal a:hover{text-decoration:underline;}
  .legal .tbl{width:100%;border-collapse:collapse;font-size:14px;margin:0 0 20px;background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;}
  .legal .tbl th,.legal .tbl td{padding:11px 14px;border-bottom:1px solid var(--border);text-align:left;vertical-align:top;color:var(--text-2);line-height:1.55;}
  .legal .tbl th{background:var(--surface-2);color:var(--text);font-weight:600;}
  .legal .tbl tr:last-child td{border-bottom:none;}
  .legal .tbl-wrap{overflow-x:auto;margin:0 0 20px;}
  .legal .disclaimer{margin-top:38px;padding:14px 16px;background:var(--surface-2);border-radius:10px;font-size:13px;color:var(--text-3);}
</style>

<section class="legal">
  <div class="container">
    <div class="eyebrow">Dokumenty</div>
    <h1>Polityka prywatności</h1>
    <div class="meta">Wersja z dnia 30 sierpnia 2026 r.</div>

    <div class="draft">
      <span aria-hidden="true">⚠</span>
      <div><b>Wersja robocza — do weryfikacji przez prawnika</b>Ta polityka prywatności jest projektem. Przed publikacją jako wiążący dokument zostanie zweryfikowana przez polskiego prawnika; do tego czasu ma charakter informacyjny.</div>
    </div>

    <div class="entity">
      Administrator danych osobowych:<br>
      <strong>Creditline B.V.</strong> (świadcząca usługę pod marką <strong>{{ \App\Support\Brand::legalName() }}</strong>)<br>
      Torenlaan 5B · 1402 AT Bussum · Holandia<br>
      Rejestr handlowy (KvK) 59683198 · VAT NL853603108B01<br>
      Kontakt w sprawach danych osobowych: <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a>
    </div>

    <p>Creditline B.V. szanuje Twoją prywatność i przetwarza dane osobowe zgodnie z Rozporządzeniem Parlamentu Europejskiego i Rady (UE) 2016/679 (RODO). Poniżej wyjaśniamy, jakie dane zbieramy w związku z korzystaniem z {{ brand('name') }} i strony {{ brand('domain') }}, po co, na jakiej podstawie, komu je powierzamy i jak długo je przechowujemy.</p>

    <h2>1. Kogo dotyczy ta polityka</h2>
    <ul>
      <li><strong>Użytkowników</strong> — osób, które zakładają konto i korzystają z {{ brand('name') }}, oraz osób zaproszonych do konta (współpracownicy, księgowa).</li>
      <li><strong>Osób odwiedzających</strong> stronę {{ brand('domain') }}, w tym korzystających z formularza kontaktowego i kalkulatora odsetek.</li>
      <li><strong>Kontrahentów Użytkowników</strong> — osób, których dane Użytkownik wprowadza do {{ brand('name') }} (np. klientów, do których wystawia faktury). W tym zakresie administratorem jest Użytkownik, a Creditline B.V. podmiotem przetwarzającym — zob. pkt 8.</li>
      <li><strong>Odwiedzających wizytówki i strony www</strong> Użytkowników, publikowane w domenie {{ brand('name') }} — dane z formularza kontaktowego takiej strony trafiają do Użytkownika, który ją prowadzi.</li>
    </ul>

    <h2>2. Cele i podstawy prawne przetwarzania</h2>
    <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>Cel</th><th>Podstawa prawna (RODO)</th></tr></thead>
      <tbody>
        <tr><td>Założenie i prowadzenie konta, świadczenie usługi (faktury, VAT, wizytówka, strona www, przypomnienia, wezwania), obsługa płatności za abonament</td><td>art. 6 ust. 1 lit. b — wykonanie umowy</td></tr>
        <tr><td>Wystawianie faktur za abonament i ich przechowywanie, rozliczenia podatkowe</td><td>art. 6 ust. 1 lit. c — obowiązek prawny (przepisy podatkowe i rachunkowe)</td></tr>
        <tr><td>Odpowiedź na wiadomość z formularza kontaktowego lub e-mail</td><td>art. 6 ust. 1 lit. f — prawnie uzasadniony interes (komunikacja z osobą, która się do nas zwraca)</td></tr>
        <tr><td>Bezpieczeństwo usługi: logi, wykrywanie nadużyć, zapobieganie spamowi (w tym weryfikacja Cloudflare Turnstile w formularzu), kopie zapasowe</td><td>art. 6 ust. 1 lit. f — prawnie uzasadniony interes (ochrona systemów i danych)</td></tr>
        <tr><td>Wiadomości o usłudze: zmiany regulaminu, ważne informacje techniczne, koniec okresu próbnego</td><td>art. 6 ust. 1 lit. b oraz lit. f</td></tr>
        <tr><td>Wskazówki i informacje o nowych funkcjach w okresie próbnym i w trakcie abonamentu</td><td>art. 6 ust. 1 lit. f — prawnie uzasadniony interes (marketing własnych usług wobec obecnych klientów); możesz się wypisać w każdej chwili</td></tr>
        <tr><td>Przekazanie sprawy windykacyjnej lub wierzytelności do Creditline Polska</td><td>art. 6 ust. 1 lit. b — na wyraźne zlecenie Użytkownika</td></tr>
        <tr><td>Funkcje oparte na sztucznej inteligencji (propozycje identyfikacji wizualnej, tekst strony www, rozpoznawanie kosztów ze zdjęć, oferty z tekstu)</td><td>art. 6 ust. 1 lit. b — wykonanie umowy, wyłącznie gdy Użytkownik korzysta z danej funkcji</td></tr>
        <tr><td>Dochodzenie i obrona roszczeń</td><td>art. 6 ust. 1 lit. f — prawnie uzasadniony interes</td></tr>
      </tbody>
    </table>
    </div>

    <h2>3. Jakie dane przetwarzamy</h2>
    <ul>
      <li><strong>Dane konta:</strong> imię i nazwisko, adres e-mail, hasło (w postaci zaszyfrowanej), numer telefonu (opcjonalnie), ustawienia logowania dwuskładnikowego.</li>
      <li><strong>Dane firmy:</strong> nazwa, adres, NIP, REGON, numer rachunku bankowego, logo i identyfikacja wizualna, treść wizytówki i strony www.</li>
      <li><strong>Dane w usłudze:</strong> faktury, oferty, kontrahenci, produkty, koszty i zdjęcia dokumentów, godziny i przejazdy, historia przypomnień i wezwań, wiadomości z formularza strony www Użytkownika.</li>
      <li><strong>Dane rozliczeniowe:</strong> wybrany pakiet, historia płatności i faktury za abonament; dane karty płatniczej są przetwarzane wyłącznie przez operatora płatności — nie przechowujemy pełnych numerów kart.</li>
      <li><strong>Dane techniczne:</strong> adres IP, typ przeglądarki i urządzenia, data i godzina logowania, logi systemowe.</li>
      <li><strong>Dane z formularza kontaktowego:</strong> imię i nazwisko, adres e-mail, temat i treść wiadomości.</li>
    </ul>
    <p>Kalkulator odsetek na stronie {{ brand('domain') }} działa w Twojej przeglądarce — wpisywane w nim kwoty i dane nie są przesyłane na nasze serwery.</p>

    <h2>4. Odbiorcy danych i podmioty przetwarzające</h2>
    <p>Nie sprzedajemy danych osobowych. Powierzamy je wyłącznie podmiotom, które są niezbędne do świadczenia usługi, na podstawie umów powierzenia przetwarzania:</p>
    <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>Podmiot</th><th>Rola</th><th>Lokalizacja danych</th></tr></thead>
      <tbody>
        <tr><td>Railway</td><td>Hosting aplikacji i bazy danych, kopie zapasowe</td><td>Unia Europejska (Amsterdam)</td></tr>
        <tr><td>Resend</td><td>Wysyłka wiadomości e-mail (faktury, przypomnienia, powiadomienia)</td><td>UE / USA — standardowe klauzule umowne</td></tr>
        <tr><td>Stripe</td><td>Obsługa płatności za abonament</td><td>UE / USA — standardowe klauzule umowne; Stripe jest odrębnym administratorem danych płatniczych</td></tr>
        <tr><td>Mollie</td><td>Linki do zapłaty na fakturach Użytkownika (BLIK, Przelewy24)</td><td>Unia Europejska</td></tr>
        <tr><td>Anthropic</td><td>Funkcje oparte na sztucznej inteligencji — przetwarza wyłącznie treści przesłane przez Użytkownika do analizy (np. zdjęcie paragonu, odpowiedzi na pytania o markę); dane nie są wykorzystywane do trenowania modeli</td><td>USA — standardowe klauzule umowne</td></tr>
        <tr><td>Cloudflare</td><td>Ochrona formularzy przed spamem (Turnstile)</td><td>UE / USA — standardowe klauzule umowne</td></tr>
        <tr><td>Creditline Polska</td><td>Windykacja należności i wykup wierzytelności — wyłącznie na zlecenie Użytkownika; po przekazaniu sprawy Creditline Polska jest odrębnym administratorem danych dłużnika</td><td>Polska</td></tr>
      </tbody>
    </table>
    </div>
    <p>Ponadto dane mogą zostać udostępnione organom publicznym, jeżeli wymagają tego przepisy prawa (np. urząd skarbowy, sąd), oraz doradcom Creditline B.V. (księgowość, obsługa prawna) w zakresie niezbędnym i z zachowaniem poufności.</p>

    <h2>5. Przekazywanie danych poza Europejski Obszar Gospodarczy</h2>
    <p>Dane konta i dane w usłudze są przechowywane w Unii Europejskiej. Niektórzy dostawcy (Resend, Stripe, Anthropic, Cloudflare) mogą przetwarzać dane także w Stanach Zjednoczonych. W takich przypadkach stosujemy standardowe klauzule umowne przyjęte przez Komisję Europejską lub opieramy się na decyzji o adekwatności (EU-US Data Privacy Framework), a dostawcy stosują dodatkowe zabezpieczenia, w tym szyfrowanie. Kopię stosowanych zabezpieczeń możesz uzyskać, pisząc na adres podany powyżej.</p>

    <h2>6. Okres przechowywania</h2>
    <ul>
      <li><strong>Dane konta i dane w usłudze</strong> — przez czas trwania umowy, a po jej zakończeniu 90 dni, aby umożliwić pobranie danych; następnie są trwale usuwane.</li>
      <li><strong>Dokumenty księgowe</strong> (faktury za abonament wystawione przez Creditline B.V. oraz dane niezbędne do rozliczeń) — 5 lat od końca roku podatkowego, w którym upłynął termin płatności podatku, zgodnie z przepisami podatkowymi; w zakresie wymaganym przez prawo holenderskie — 7 lat.</li>
      <li><strong>Wiadomości z formularza kontaktowego</strong> — do 12 miesięcy od zakończenia korespondencji.</li>
      <li><strong>Logi techniczne</strong> — do 90 dni, chyba że są potrzebne do wyjaśnienia incydentu bezpieczeństwa.</li>
      <li><strong>Kopie zapasowe</strong> — nadpisywane cyklicznie; dane usunięte z usługi znikają z kopii zapasowych najpóźniej po 30 dniach.</li>
      <li><strong>Dane niezbędne do dochodzenia lub obrony roszczeń</strong> — do upływu terminów przedawnienia.</li>
    </ul>

    <h2>7. Twoje prawa</h2>
    <p>W związku z przetwarzaniem danych przysługują Ci prawa: dostępu do danych i otrzymania ich kopii, sprostowania, usunięcia („prawo do bycia zapomnianym”), ograniczenia przetwarzania, przenoszenia danych (eksport z konta w formacie CSV jest dostępny w każdej chwili), sprzeciwu wobec przetwarzania opartego na prawnie uzasadnionym interesie (w tym wobec marketingu własnych usług) oraz — jeżeli przetwarzanie odbywa się na podstawie zgody — cofnięcia zgody w dowolnym momencie bez wpływu na zgodność z prawem wcześniejszego przetwarzania.</p>
    <p>Aby skorzystać z tych praw, napisz na <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a>. Odpowiadamy najpóźniej w ciągu miesiąca. Masz też prawo wnieść skargę do organu nadzorczego: w Polsce jest to Prezes Urzędu Ochrony Danych Osobowych (ul. Stawki 2, 00-193 Warszawa, <a href="https://uodo.gov.pl" target="_blank" rel="noopener">uodo.gov.pl</a>), a w Holandii — Autoriteit Persoonsgegevens.</p>
    <p>Podanie danych jest dobrowolne, ale niezbędne do założenia konta i korzystania z usługi. Nie podejmujemy wobec Ciebie decyzji opartych wyłącznie na zautomatyzowanym przetwarzaniu, które wywoływałyby skutki prawne.</p>

    <h2>8. Dane Twoich kontrahentów — powierzenie przetwarzania</h2>
    <p>Wprowadzając do {{ brand('name') }} dane swoich klientów, dostawców lub pracowników, jesteś ich administratorem, a Creditline B.V. przetwarza je w Twoim imieniu jako podmiot przetwarzający (art. 28 RODO). Przetwarzamy te dane wyłącznie w celu świadczenia usługi i zgodnie z Twoimi poleceniami wydawanymi przez funkcje aplikacji (np. wysłanie faktury, przypomnienia lub wezwania, przekazanie sprawy do Creditline Polska). Stosujemy środki techniczne i organizacyjne opisane w pkt 10, korzystamy tylko z podmiotów wymienionych w pkt 4 i pomagamy Ci w realizacji praw osób, których dane dotyczą. Na żądanie udostępniamy umowę powierzenia przetwarzania danych.</p>

    <h2>9. Pliki cookie</h2>
    <p>Strona {{ brand('domain') }} i aplikacja używają wyłącznie <strong>niezbędnych, funkcjonalnych plików cookie</strong>: do utrzymania sesji logowania, zabezpieczenia formularzy (token CSRF), zapamiętania ustawień oraz — w formularzu kontaktowym — do weryfikacji antyspamowej Cloudflare Turnstile. Nie używamy cookie reklamowych, śledzących ani narzędzi analitycznych osób trzecich, dlatego nie prosimy o zgodę na cookie. Możesz zablokować cookie w ustawieniach przeglądarki, ale wtedy logowanie do aplikacji nie będzie możliwe.</p>

    <h2>10. Bezpieczeństwo</h2>
    <p>Połączenia są szyfrowane (TLS), hasła przechowujemy wyłącznie w postaci skrótu (hash), a dostęp do systemów produkcyjnych jest ograniczony i rejestrowany. Codziennie wykonujemy kopie zapasowe. Możesz włączyć logowanie dwuskładnikowe (2FA) i przeglądać dziennik aktywności konta. O naruszeniu ochrony danych, które może powodować wysokie ryzyko dla Twoich praw, poinformujemy Cię bez zbędnej zwłoki.</p>

    <h2>11. Zmiany polityki</h2>
    <p>Możemy aktualizować tę politykę, gdy zmieniają się przepisy, nasi dostawcy lub zakres usługi. O istotnych zmianach poinformujemy Cię e-mailem lub komunikatem w aplikacji. Aktualna wersja jest zawsze dostępna pod adresem {{ brand('domain') }}/polityka-prywatnosci.</p>

    <h2>12. Kontakt</h2>
    <p>W sprawach dotyczących danych osobowych napisz na <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a> lub listownie: Creditline B.V., Torenlaan 5B, 1402 AT Bussum, Holandia. Zobacz też nasz <a href="{{ route('pl.regulamin') }}">regulamin</a>.</p>

    <div class="disclaimer">
      Wersja robocza — do weryfikacji przez prawnika. Pytania dotyczące prywatności: <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a>.
    </div>
  </div>
</section>
@endsection
