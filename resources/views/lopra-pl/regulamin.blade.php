@extends('layouts.marketing')

{{--
  WERSJA ROBOCZA — DO WERYFIKACJI PRZEZ PRAWNIKA.
  Regulamin świadczenia usług drogą elektroniczną dla Lopra Polska (abonament SaaS).
  Tekst jest projektem przygotowanym na podstawie holenderskich warunków usługi i wymaga
  sprawdzenia przez polskiego prawnika (m.in. ustawa o świadczeniu usług drogą elektroniczną,
  Kodeks cywilny — przedsiębiorca na prawach konsumenta, ustawa o prawach konsumenta)
  przed publikacją jako wiążący dokument.
--}}

@section('title', 'Regulamin — ' . brand('name'))
@section('description', 'Regulamin świadczenia usług drogą elektroniczną ' . brand('name') . ' — usługi Creditline B.V.: zakres usług, konto i okres próbny, abonament i płatności, rezygnacja, dane, odpowiedzialność, reklamacje.')

@section('content')
@if(app()->getLocale() === 'en')
<div class="container" style="padding-top:28px;">
  <div style="background:#FEF3C7;border:1px solid #F59E0B;border-radius:12px;padding:14px 18px;font-size:14px;line-height:1.55;">
    <b>In English:</b> this document is available in Polish only; the Polish text is the legally binding version. Need help understanding it? E-mail <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a>.
  </div>
</div>
@endif
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
  .legal .disclaimer{margin-top:38px;padding:14px 16px;background:var(--surface-2);border-radius:10px;font-size:13px;color:var(--text-3);}
</style>

<section class="legal">
  <div class="container">
    <div class="eyebrow">Dokumenty</div>
    <h1>Regulamin świadczenia usług drogą elektroniczną</h1>
    <div class="meta">Wersja z dnia 30 sierpnia 2026 r.</div>

    <div class="draft">
      <span aria-hidden="true">⚠</span>
      <div><b>Wersja robocza — do weryfikacji przez prawnika</b>Ten regulamin jest projektem. Przed zawarciem umowy na jego podstawie zostanie zweryfikowany przez polskiego prawnika; do tego czasu ma charakter informacyjny.</div>
    </div>

    <div class="entity">
      Usługodawca:<br>
      <strong>Creditline B.V.</strong> (świadcząca usługę pod marką <strong>{{ \App\Support\Brand::legalName() }}</strong>)<br>
      Torenlaan 5B · 1402 AT Bussum · Holandia<br>
      Rejestr handlowy (KvK) 59683198 · VAT NL853603108B01<br>
      E-mail: <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a><br>
      Partner windykacyjny w Polsce: <strong>Creditline Polska</strong> (<a href="https://creditline.pl" target="_blank" rel="noopener">creditline.pl</a>)
    </div>

    <h2>§ 1. Definicje</h2>
    <ul>
      <li><strong>Usługodawca</strong> — Creditline B.V. z siedzibą w Bussum (Holandia), Torenlaan 5B, 1402 AT Bussum, wpisana do holenderskiego rejestru handlowego (KvK) pod numerem 59683198, NIP UE NL853603108B01.</li>
      <li><strong>Usługa</strong> lub <strong>{{ brand('name') }}</strong> — oprogramowanie udostępniane przez Internet (SaaS) pod adresem {{ brand('domain') }}, służące w szczególności do wystawiania faktur i ofert, prowadzenia listy kontrahentów i produktów, rozliczania VAT, tworzenia identyfikacji wizualnej, wizytówki cyfrowej i strony www oraz obsługi przypomnień i wezwań do zapłaty.</li>
      <li><strong>Użytkownik</strong> — przedsiębiorca (osoba fizyczna prowadząca działalność gospodarczą, osoba prawna lub jednostka organizacyjna), który zawarł z Usługodawcą umowę o świadczenie Usługi. Usługa jest przeznaczona dla przedsiębiorców; osoba fizyczna korzysta z niej w związku z prowadzoną działalnością gospodarczą.</li>
      <li><strong>Konto</strong> — indywidualny dostęp Użytkownika do Usługi, chroniony loginem i hasłem, obejmujący jedną lub więcej administracji (firm).</li>
      <li><strong>Okres próbny</strong> — bezpłatny okres 14 dni od założenia Konta, w którym Użytkownik korzysta z Usługi w pełnym zakresie.</li>
      <li><strong>Abonament</strong> — odpłatne korzystanie z Usługi w pakiecie Podstawowy lub Smart, rozliczane w miesięcznych okresach rozliczeniowych.</li>
      <li><strong>Dane Użytkownika</strong> — wszystkie dane wprowadzone do Usługi przez Użytkownika lub w jego imieniu, w tym dane kontrahentów, faktury, koszty, pliki i treści strony www.</li>
      <li><strong>Regulamin</strong> — niniejszy regulamin świadczenia usług drogą elektroniczną.</li>
    </ul>

    <h2>§ 2. Postanowienia ogólne</h2>
    <p>1. Regulamin określa zasady świadczenia Usługi przez Usługodawcę, zawierania i rozwiązywania umowy, prawa i obowiązki stron oraz tryb reklamacyjny.</p>
    <p>2. Warunkiem korzystania z Usługi jest akceptacja Regulaminu przy zakładaniu Konta. Umowa o świadczenie Usługi zostaje zawarta z chwilą założenia Konta.</p>
    <p>3. Do korzystania z Usługi potrzebne są: urządzenie z dostępem do Internetu, aktualna przeglądarka internetowa z obsługą JavaScript i plików cookie oraz aktywny adres e-mail.</p>
    <p>4. Usługodawca świadczy Usługę w języku polskim. Komunikacja z Użytkownikiem odbywa się drogą elektroniczną, na adres e-mail przypisany do Konta.</p>

    <h2>§ 3. Zakres usług</h2>
    <p>1. W ramach Usługi Usługodawca udostępnia w szczególności:</p>
    <ul>
      <li>wystawianie faktur VAT, faktur korygujących, zaliczkowych i ofert, z ciągłą numeracją i szablonami w identyfikacji wizualnej Użytkownika;</li>
      <li>generowanie pliku XML w strukturze FA zgodnej z Krajowym Systemem e-Faktur (KSeF) dla każdej faktury; bezpośrednia wysyłka do KSeF z poziomu Usługi zostanie udostępniona w kolejnym etapie i będzie odrębnie ogłoszona;</li>
      <li>weryfikację NIP kontrahenta w Wykazie podatników VAT oraz listę kontrahentów i produktów;</li>
      <li>zestawienie VAT należnego i naliczonego per stawka, w układzie pomocnym przy sporządzaniu JPK_V7, oraz eksport danych (CSV);</li>
      <li>przypomnienia o płatności, ponaglenia i generowanie wezwania do zapłaty z odsetkami ustawowymi za opóźnienie w transakcjach handlowych i rekompensatą;</li>
      <li>przekazanie sprawy do partnera windykacyjnego Creditline Polska oraz zgłoszenie wierzytelności do wykupu — na wyraźne zlecenie Użytkownika;</li>
      <li>identyfikację wizualną, wizytówkę cyfrową i stronę www Użytkownika, publikowane pod adresem w domenie Usługi;</li>
      <li>w pakiecie Smart — funkcje oparte na sztucznej inteligencji (m.in. propozycje identyfikacji wizualnej, tekst strony www, rozpoznawanie kosztów ze zdjęć, oferty z tekstu).</li>
    </ul>
    <p>2. Usługa jest narzędziem wspierającym Użytkownika. Usługodawca nie świadczy usług księgowych, doradztwa podatkowego ani prawnego. Za poprawność, kompletność i terminowość faktur, rozliczeń podatkowych, wysyłki do KSeF oraz treści wezwań do zapłaty odpowiada Użytkownik.</p>
    <p>3. Windykacja należności i wykup wierzytelności są usługami Creditline Polska świadczonymi na podstawie odrębnej umowy lub zlecenia między Użytkownikiem a Creditline Polska. Wynagrodzenie za te usługi jest ustalane każdorazowo przed przyjęciem zlecenia; Usługa nie pobiera z tego tytułu opłat wstępnych.</p>
    <p>4. Treści generowane przez funkcje sztucznej inteligencji stanowią propozycje. Użytkownik sprawdza je przed użyciem i ponosi odpowiedzialność za ich wykorzystanie (w tym za prawa osób trzecich do nazw, sloganów lub znaków).</p>

    <h2>§ 4. Konto i okres próbny</h2>
    <p>1. Konto zakłada się, podając adres e-mail, hasło oraz dane firmy (w tym NIP). Użytkownik oświadcza, że podane dane są prawdziwe i że jest uprawniony do działania w imieniu firmy, dla której zakłada Konto.</p>
    <p>2. Nowe Konto otrzymuje bezpłatny Okres próbny 14 dni z pełnym zakresem funkcji, także z pakietu Smart. W Okresie próbnym nie są wymagane dane do płatności.</p>
    <p>3. Po upływie Okresu próbnego dalsze korzystanie z Usługi wymaga wykupienia Abonamentu. Jeżeli Użytkownik nie wykupi Abonamentu, dostęp do Konta zostaje ograniczony do trybu podglądu i eksportu danych; Dane Użytkownika są przechowywane zgodnie z § 8.</p>
    <p>4. Użytkownik jest odpowiedzialny za poufność danych logowania i za wszystkie działania wykonane w ramach Konta. Zaleca się włączenie logowania dwuskładnikowego (2FA). O nieuprawnionym użyciu Konta należy niezwłocznie poinformować Usługodawcę.</p>
    <p>5. Użytkownik może zapraszać do Konta dodatkowych użytkowników (współpracowników, księgową) i nadawać im role. Za działania zaproszonych osób odpowiada Użytkownik.</p>

    <h2>§ 5. Abonament i płatności</h2>
    <p>1. Usługa jest dostępna w dwóch pakietach: <strong>Podstawowy</strong> za 49 zł netto miesięcznie oraz <strong>Smart</strong> za 79 zł netto miesięcznie. Do cen netto doliczany jest podatek VAT według stawki obowiązującej w dniu wystawienia faktury (obecnie 23%, tj. odpowiednio 60,27 zł i 97,17 zł brutto). Aktualny cennik jest publikowany na stronie {{ brand('domain') }}.</p>
    <p>2. Abonament jest rozliczany w miesięcznych okresach rozliczeniowych, płatnych z góry. Płatność odbywa się za pośrednictwem operatora płatności (Stripe) — kartą, BLIK-iem lub przelewem, zgodnie z metodami dostępnymi w Usłudze. Usługodawca nie przechowuje pełnych danych kart płatniczych.</p>
    <p>3. Za każdy okres rozliczeniowy Usługodawca wystawia fakturę VAT i udostępnia ją w Usłudze oraz przesyła na adres e-mail Użytkownika. Użytkownik wyraża zgodę na otrzymywanie faktur w formie elektronicznej.</p>
    <p>4. Abonament odnawia się automatycznie na kolejny okres rozliczeniowy, dopóki Użytkownik nie zrezygnuje zgodnie z § 6. W przypadku nieudanej płatności Usługodawca ponawia próbę obciążenia; jeżeli płatność nie zostanie uregulowana w ciągu 14 dni od początku okresu rozliczeniowego, dostęp do Usługi może zostać ograniczony do czasu uregulowania należności.</p>
    <p>5. Użytkownik może w każdej chwili zmienić pakiet. Przejście na wyższy pakiet obowiązuje od razu i jest rozliczane proporcjonalnie; przejście na niższy pakiet obowiązuje od kolejnego okresu rozliczeniowego.</p>
    <p>6. Usługodawca może zmienić ceny Abonamentu, informując Użytkownika z co najmniej 30-dniowym wyprzedzeniem drogą elektroniczną. Nowa cena obowiązuje od pierwszego okresu rozliczeniowego rozpoczynającego się po upływie tego terminu. Użytkownik, który nie akceptuje zmiany, może zrezygnować z Usługi przed jej wejściem w życie.</p>

    <h2>§ 6. Rezygnacja i rozwiązanie umowy</h2>
    <p>1. Umowa jest zawarta na czas nieokreślony. Użytkownik może zrezygnować z Abonamentu w każdej chwili w ustawieniach Konta lub wiadomością e-mail, ze skutkiem na koniec bieżącego okresu rozliczeniowego. Do końca opłaconego okresu Użytkownik zachowuje pełny dostęp do Usługi. Opłata za rozpoczęty okres rozliczeniowy nie podlega zwrotowi, chyba że bezwzględnie obowiązujące przepisy stanowią inaczej.</p>
    <p>2. Użytkownik może w każdej chwili usunąć Konto. Przed usunięciem powinien pobrać Dane Użytkownika (eksport CSV, pliki PDF i XML faktur), ponieważ po usunięciu Konta dane zostaną trwale usunięte zgodnie z § 8.</p>
    <p>3. Usługodawca może rozwiązać umowę ze skutkiem natychmiastowym, jeżeli Użytkownik istotnie narusza Regulamin, w szczególności wykorzystuje Usługę niezgodnie z prawem lub na szkodę osób trzecich, albo zalega z płatnością mimo wezwania. Usługodawca może również rozwiązać umowę z zachowaniem 30-dniowego okresu wypowiedzenia, jeżeli zaprzestaje świadczenia Usługi; w takim przypadku umożliwia Użytkownikowi pobranie danych.</p>

    <h2>§ 7. Obowiązki Użytkownika</h2>
    <p>1. Użytkownik zobowiązuje się korzystać z Usługi zgodnie z prawem, Regulaminem i dobrymi obyczajami, a w szczególności:</p>
    <ul>
      <li>wprowadzać prawdziwe i aktualne dane oraz odpowiadać za treść wystawianych dokumentów;</li>
      <li>nie wykorzystywać Usługi do wystawiania fikcyjnych faktur, wysyłania niezamówionych wiadomości (spamu), naruszania praw osób trzecich lub rozpowszechniania treści bezprawnych — w tym na wizytówce i stronie www;</li>
      <li>nie podejmować działań zakłócających działanie Usługi, nie obchodzić zabezpieczeń ani nie uzyskiwać dostępu do danych innych użytkowników;</li>
      <li>posiadać podstawę prawną do przetwarzania danych osobowych swoich kontrahentów, które wprowadza do Usługi (zob. § 8 ust. 4);</li>
      <li>korzystać z funkcji windykacyjnych i wezwań do zapłaty wyłącznie w odniesieniu do rzeczywistych, wymagalnych należności.</li>
    </ul>
    <p>2. Wizytówka cyfrowa i strona www są publikowane pod adresem w domenie Usługi. Użytkownik odpowiada za ich treść i może je w każdej chwili wyłączyć. Usługodawca może wyłączyć stronę zawierającą treści naruszające prawo lub Regulamin, informując o tym Użytkownika.</p>
    <p>3. Korzystanie z Usługi podlega zasadzie dozwolonego użytku: funkcje oparte na sztucznej inteligencji i wysyłka wiadomości mogą być objęte rozsądnymi limitami, chroniącymi stabilność Usługi. Limity nie ograniczają liczby faktur ani kontrahentów.</p>

    <h2>§ 8. Dane Użytkownika i kopie zapasowe</h2>
    <p>1. Dane Użytkownika pozostają własnością Użytkownika. Usługodawca korzysta z nich wyłącznie w celu świadczenia Usługi i nie udostępnia ich osobom trzecim poza przypadkami opisanymi w <a href="{{ route('pl.prywatnosc') }}">polityce prywatności</a> (podmioty przetwarzające, obowiązki prawne, zlecenie Użytkownika).</p>
    <p>2. Dane są przechowywane na serwerach w Unii Europejskiej (Amsterdam). Usługodawca wykonuje codziennie kopie zapasowe i stosuje szyfrowanie połączeń (TLS). Kopie zapasowe służą odtworzeniu Usługi po awarii i nie zastępują własnej archiwizacji dokumentów przez Użytkownika.</p>
    <p>3. Użytkownik może w każdej chwili wyeksportować Dane Użytkownika (CSV, PDF, XML). Po rozwiązaniu umowy Usługodawca przechowuje Dane Użytkownika przez 90 dni, umożliwiając ich pobranie, a następnie trwale je usuwa — z wyjątkiem danych, które Usługodawca ma obowiązek przechowywać na podstawie przepisów prawa (np. własne faktury za Abonament).</p>
    <p>4. W zakresie danych osobowych kontrahentów i pracowników Użytkownika, Użytkownik jest administratorem, a Usługodawca podmiotem przetwarzającym. Zasady powierzenia przetwarzania określa polityka prywatności oraz — na żądanie Użytkownika — umowa powierzenia przetwarzania danych.</p>
    <p>5. Obowiązek przechowywania dokumentów księgowych (co do zasady 5 lat od końca roku kalendarzowego, w którym upłynął termin płatności podatku) spoczywa na Użytkowniku. Usługa ułatwia jego wykonanie, ale Użytkownik powinien zapewnić własną archiwizację wyeksportowanych dokumentów.</p>

    <h2>§ 9. Dostępność Usługi</h2>
    <p>1. Usługodawca dokłada starań, aby Usługa była dostępna przez całą dobę, i dąży do dostępności na poziomie 99,5% w skali miesiąca, z wyłączeniem planowanych prac serwisowych.</p>
    <p>2. Planowane prace serwisowe, które mogą powodować przerwy w dostępie, są w miarę możliwości wykonywane poza godzinami pracy i ogłaszane z wyprzedzeniem na stronie statusu. Usługodawca może bez uprzedzenia wprowadzać zmiany niezbędne ze względów bezpieczeństwa.</p>
    <p>3. Usługodawca rozwija Usługę i może dodawać, zmieniać lub wycofywać funkcje. O wycofaniu istotnej funkcji Użytkownik zostanie poinformowany z co najmniej 30-dniowym wyprzedzeniem.</p>

    <h2>§ 10. Odpowiedzialność</h2>
    <p>1. Usługodawca odpowiada za szkody wyrządzone Użytkownikowi z winy umyślnej lub rażącego niedbalstwa. W pozostałym zakresie odpowiedzialność Usługodawcy jest ograniczona do szkody rzeczywistej i do wysokości wynagrodzenia zapłaconego przez Użytkownika za Usługę w okresie 12 miesięcy poprzedzających zdarzenie wywołujące szkodę.</p>
    <p>2. Usługodawca nie odpowiada za utracone korzyści, za skutki nieprawidłowych danych wprowadzonych przez Użytkownika, za decyzje podjęte na podstawie zestawień i obliczeń generowanych przez Usługę (w tym wyliczeń odsetek i rekompensaty, które mają charakter pomocniczy), za przerwy wynikające z przyczyn leżących po stronie dostawców Internetu, operatorów płatności, KSeF lub innych systemów zewnętrznych ani za działania partnera windykacyjnego świadczone na podstawie odrębnej umowy.</p>
    <p>3. Ograniczenia odpowiedzialności nie dotyczą sytuacji, w których bezwzględnie obowiązujące przepisy prawa wyłączają możliwość ich zastosowania — w szczególności wobec Użytkowników objętych ochroną konsumencką (§ 12 ust. 2).</p>

    <h2>§ 11. Reklamacje</h2>
    <p>1. Reklamacje dotyczące Usługi można składać na adres e-mail <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a> lub pisemnie na adres Usługodawcy. Reklamacja powinna zawierać dane Użytkownika, opis problemu i, w miarę możliwości, datę jego wystąpienia.</p>
    <p>2. Usługodawca rozpatruje reklamację w terminie 14 dni od jej otrzymania i przekazuje odpowiedź drogą elektroniczną. Jeżeli rozpatrzenie wymaga dodatkowych informacji, Usługodawca zwraca się o nie do Użytkownika; termin biegnie wówczas od ich otrzymania.</p>
    <p>3. Zgłoszenia błędów i propozycje zmian, które nie stanowią reklamacji, są mile widziane pod tym samym adresem.</p>

    <h2>§ 12. Prawo właściwe i rozstrzyganie sporów</h2>
    <p>1. Do umowy o świadczenie Usługi stosuje się prawo holenderskie (Niderlandów), jako prawo siedziby Usługodawcy, z zastrzeżeniem ust. 2.</p>
    <p>2. Wobec Użytkownika będącego osobą fizyczną, która zawiera umowę bezpośrednio związaną z jej działalnością gospodarczą, gdy z treści umowy wynika, że nie ma ona dla niej charakteru zawodowego (przedsiębiorca na prawach konsumenta), stosuje się bezwzględnie obowiązujące przepisy prawa polskiego chroniące takie osoby, w szczególności przepisy Kodeksu cywilnego o niedozwolonych postanowieniach umownych i rękojmi oraz ustawy o prawach konsumenta. Postanowienia Regulaminu nie wyłączają ani nie ograniczają uprawnień przysługujących takim Użytkownikom z mocy prawa.</p>
    <p>3. Strony będą dążyć do polubownego rozwiązania sporów. Spory, których nie uda się rozwiązać polubownie, rozstrzyga sąd właściwy według przepisów prawa; w stosunku do Użytkowników, o których mowa w ust. 2, właściwość sądu określają przepisy polskiego Kodeksu postępowania cywilnego.</p>

    <h2>§ 13. Zmiany Regulaminu</h2>
    <p>1. Usługodawca może zmienić Regulamin z ważnych przyczyn, w szczególności w związku ze zmianą przepisów prawa, zmianą zakresu lub sposobu świadczenia Usługi, względami bezpieczeństwa lub koniecznością doprecyzowania postanowień.</p>
    <p>2. O zmianie Regulaminu Użytkownik zostanie poinformowany drogą elektroniczną co najmniej 14 dni przed jej wejściem w życie, a w przypadku zmian cen — 30 dni. Jeżeli Użytkownik nie akceptuje zmian, może zrezygnować z Usługi przed ich wejściem w życie; dalsze korzystanie z Usługi po tym terminie oznacza akceptację zmienionego Regulaminu.</p>
    <p>3. Zmiany, które nie ograniczają praw Użytkownika (np. redakcyjne, dodanie nowych funkcji), mogą wejść w życie z chwilą publikacji.</p>

    <h2>§ 14. Postanowienia końcowe</h2>
    <p>1. Regulamin jest dostępny nieodpłatnie pod adresem {{ brand('domain') }}/regulamin w formie umożliwiającej jego pobranie, utrwalenie i wydrukowanie.</p>
    <p>2. Jeżeli którekolwiek postanowienie Regulaminu okaże się nieważne lub bezskuteczne, pozostałe postanowienia pozostają w mocy, a postanowienie nieważne zastępuje się postanowieniem najbliższym jego celowi, zgodnym z prawem.</p>
    <p>3. Zasady przetwarzania danych osobowych określa <a href="{{ route('pl.prywatnosc') }}">polityka prywatności</a>, stanowiąca uzupełnienie Regulaminu.</p>
    <p>4. Regulamin wchodzi w życie z dniem publikacji wskazanym powyżej.</p>

    <div class="disclaimer">
      Wersja robocza — do weryfikacji przez prawnika. Pytania dotyczące Regulaminu: <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a>.
    </div>
  </div>
</section>
@endsection
