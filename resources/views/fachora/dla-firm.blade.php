@extends('layouts.marketing')

{{-- Dla firm: co firma dostaje, ile to kosztuje (0/0/0 + prowizja), trzy drogi dołączenia (kod z listu, wyszukanie profilu, zgłoszenie),
     uczciwie o tym, że Fachora nie wysyła firmom niezamówionych wiadomości. Liczby z config/fachora.php. --}}

@php
  $fee = config('fachora.fee_percent');
  $intro = config('fachora.intro_fee_percent');
  $introDays = config('fachora.intro_days');
  $warranty = config('fachora.default_warranty_months');
  $maxOffers = config('fachora.max_offers');
  $offerHours = config('fachora.offer_hours');
  $acceptDays = config('fachora.auto_accept_days');
  $disputeHours = config('fachora.dispute_hours');
  $zl = fn ($n) => number_format($n, 0, ',', ' ') . ' zł';
  $lead = (bool) config('fachora.lead_pricing');
  $leadMin = (int) config('fachora.lead_min');
  $leadMax = (int) config('fachora.lead_max');
  $leadPct = rtrim(rtrim(number_format((float) config('fachora.lead_percent'), 1, ',', ''), '0'), ',');
  $chev = '<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>';
@endphp

@section('title', $lead ? 'Fachora dla firm — klienci sami wybierają Twoją firmę, płacisz tylko za zlecenia, na które odpowiadasz' : 'Fachora dla firm — nowi klienci w Twojej okolicy, płacisz tylko za zrealizowane zlecenia')
@section('description', $lead
  ? 'Klient sam wybiera Twoją firmę i wysyła zapytanie. Podgląd zlecenia za darmo, odblokowanie od ' . $leadMin . ' do ' . $leadMax . ' zł zależnie od wielkości pracy, pierwsze zapytanie gratis. 0 zł abonamentu, bez prowizji od zapłaty bezpośredniej, darmowy program do faktur gotowy na KSeF.'
  : 'Zlecenia z opisem, zdjęciami i terminem na e-mail, Twoja cena, adres po wyborze przez klienta, pewna zapłata przez Fachora Protect, darmowy program do faktur gotowy na KSeF, profil z opiniami. 0 zł abonamentu, 0 zł za zlecenie, prowizja ' . $fee . '% od wykonanej pracy, przez pierwsze ' . $introDays . ' dni ' . $intro . '%.')

@push('styles')
@include('fachora.partials.styles')
@endpush

@section('content')

<section class="hero">
  <div class="container hero-inner">
    <div class="eyebrow">Dla firm</div>
    <h1>Nowi klienci w Twojej okolicy. <span class="accent">Płacisz tylko za zrealizowane zlecenia.</span></h1>
    <p class="hero-sub">{{ $lead ? 'Klient sam wybiera Twoją firmę i opisuje zlecenie, Ty podajesz cenę i termin. Bez abonamentu: płacisz tylko za zapytanie, na które odpowiadasz, a pierwsze jest gratis.' : 'Klient opisuje zlecenie, Ty podajesz cenę i termin. Jeśli Cię wybierze, kwota jest zarezerwowana, zanim wyjedziesz. Żadnych opłat za kontakt, żadnego abonamentu.' }}</p>
    <div class="hero-ctas">
      <a href="{{ route('fachora.apply') }}" class="btn btn-primary btn-lg">Zgłoś firmę</a>
      <a href="{{ route('fachora.claim') }}" class="btn btn-secondary btn-lg">Mam kod z listu: przejmij profil</a>
    </div>
    <div class="hero-trust">Masz już konto? <a href="{{ route('login') }}" style="color:var(--brand);font-weight:600;">Zaloguj się do panelu firmy</a> · Chcesz najpierw zobaczyć panel? <a href="{{ route('demo') }}" style="color:var(--brand);font-weight:600;">Uruchom demo</a></div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Co dostajesz</div>
      <h2>Zlecenia, nie „leady”</h2>
      <p>Każde zlecenie to prawdziwy klient z konkretnym problemem, potwierdzonym adresem e-mail i terminem. Odpowiadasz tylko na te, które Ci pasują.</p>
    </div>
    <div class="fh-usps cols-3">
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'invoice', 'size' => 22])</span><h3>Zlecenie na e-mail</h3><p>Opis, zdjęcia, termin, język klienta i szacunkowy zakres. Masz {{ $offerHours }} godzin, żeby podać cenę i termin przez link, bez logowania.</p></div>
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'card', 'size' => 22])</span><h3>Twoja cena</h3><p>Cena stała albo stawka godzinowa z dojazdem. Nie licytujesz się w dół: klient widzi obok ceny Twoją ocenę, języki i gwarancję.</p></div>
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'pin', 'size' => 22])</span><h3>Adres po wyborze</h3><p>Dopiero gdy klient Cię wybierze, dostajesz adres i dane kontaktowe. Konkurencja do {{ $maxOffers }} firm, nie dwudziestu.</p></div>
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'shield', 'size' => 22])</span><h3>Pewna zapłata</h3><p>Z Fachora Protect klient rezerwuje kwotę przed Twoim przyjazdem. Po jego akceptacji (albo po {{ $acceptDays }} dniach bez reakcji) Stripe przelewa pieniądze na Twoje konto.</p></div>
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'check', 'size' => 22])</span><h3>Darmowe faktury, gotowe na KSeF</h3><p>Każda firma Verified dostaje program do faktur i ofert z plikiem XML dla KSeF. Faktura do zlecenia jest przygotowana z kwoty i opisu zlecenia.</p></div>
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'lang', 'size' => 22])</span><h3>Profil z opiniami i językami</h3><p>Opinie tylko po zleceniach zrealizowanych przez Fachorę, z Twoją odpowiedzią. Mówisz po angielsku, niemiecku lub ukraińsku? Dostajesz zlecenia od klientów z zagranicy, do których inni nie docierają.</p></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Ile to kosztuje</div>
      @if ($lead)
      <h2>Bez abonamentu. Płacisz tylko za zapytanie, na które chcesz odpowiedzieć</h2>
      <p>Klient sam wybiera do pięciu firm. Zlecenie oglądasz za darmo; jeśli chcesz podać cenę, odblokowujesz je jednym kliknięciem (BLIK, Przelewy24, karta) i dostajesz fakturę.</p>
    </div>
    <div class="fh-prices">
      <div class="fh-price"><div class="v">0 <small>zł</small></div><b>abonament</b><span>bez miesięcznej opłaty i bez pakietów punktów</span></div>
      <div class="fh-price"><div class="v">0 <small>zł</small></div><b>podgląd zlecenia</b><span>opis, zdjęcia, termin i miasto widzisz zawsze za darmo</span></div>
      <div class="fh-price hl"><div class="v">{{ $leadMin }}–{{ $leadMax }}<small> zł</small></div><b>za odblokowanie</b><span>{{ $leadPct }}% szacowanej wartości pracy; pierwsze zapytanie gratis</span></div>
      <div class="fh-price"><div class="v">0 <small>%</small></div><b>prowizji przy płatności bezpośredniej</b><span>przy Fachora Protect: {{ $fee }}% potrącane przy wypłacie</span></div>
    </div>
    <div class="fh-note" style="max-width:760px;margin:24px auto 0;">
      <b>Przykład:</b> wymiana gniazdka (szacunek {{ $zl(200) }}) kosztuje {{ $zl($leadMin) }}. Montaż drzwi (szacunek {{ $zl(1200) }}) to {{ $zl(max($leadMin, min($leadMax, round(1200 * (float) config('fachora.lead_percent') / 100)))) }}. Remont łazienki (szacunek {{ $zl(20000) }}) to maksymalnie {{ $zl($leadMax) }}. Cenę widzisz przed odblokowaniem. Fałszywe zapytanie? Zgłoś je w ciągu 7 dni, a zwrócimy opłatę.
    </div>
      @else
      <h2>Zero, zero, zero i prowizja od wykonanej pracy</h2>
      <p>Zarabiamy tylko wtedy, gdy Ty zarabiasz. Prowizja jest potrącana przy wypłacie i dostajesz na nią fakturę.</p>
    </div>
    <div class="fh-prices">
      <div class="fh-price"><div class="v">0 <small>zł</small></div><b>abonament</b><span>bez miesięcznej opłaty, bez okresu próbnego, bo nie ma czego próbować</span></div>
      <div class="fh-price"><div class="v">0 <small>zł</small></div><b>rejestracja</b><span>przejęcie profilu i weryfikacja nic nie kosztują</span></div>
      <div class="fh-price"><div class="v">0 <small>zł</small></div><b>za zlecenie</b><span>nie płacisz za otrzymanie zlecenia ani za złożenie oferty</span></div>
      <div class="fh-price hl"><div class="v">{{ $fee }}<small>%</small></div><b>od wykonanej i zaakceptowanej pracy</b><span>przez pierwsze {{ $introDays }} dni od weryfikacji: {{ $intro }}%</span></div>
    </div>
    <div class="fh-note" style="max-width:760px;margin:24px auto 0;">
      <b>Przykład:</b> zlecenie za {{ $zl(500) }} brutto. Prowizja {{ $fee }}% to {{ $zl(500 * $fee / 100) }}, na Twoje konto trafia {{ $zl(500 - 500 * $fee / 100) }}. Przez pierwsze {{ $introDays }} dni prowizja wynosi {{ $zl(500 * $intro / 100) }}. Zlecenie, które nie doszło do skutku, nie kosztuje nic.
    </div>
      @endif
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Jak dołączyć</div>
      <h2>Trzy drogi, jeden cel: profil Fachora Verified</h2>
    </div>
    <div class="fh-ways">
      <div class="fh-way"><span class="k">Dostałeś list</span><h3>Wpisz kod z listu</h3><p>Firmom z rejestrów publicznych wysyłamy pocztą list z kodem. Kod łączy Cię z Twoim profilem; potwierdzasz e-mail i telefon, i profil jest Twój.</p><a href="{{ route('fachora.claim') }}" class="btn btn-primary">Mam kod z listu</a></div>
      <div class="fh-way"><span class="k">Jesteś w bazie</span><h3>Znajdź swój profil</h3><p>Twoja firma może już być w naszej bazie jako profil nieaktywny, bez telefonu i bez zleceń. Wyszukaj ją po NIP i przejmij profil.</p><a href="{{ route('fachora.claim') }}" class="btn btn-secondary">Znajdź profil po NIP</a></div>
      <div class="fh-way"><span class="k">Nie ma Cię w bazie</span><h3>Zgłoś firmę</h3><p>Podaj NIP, kategorie, zasięg i języki. Sprawdzimy rejestry, poprosimy o onboarding w Stripe i akceptację Standardu, a potem przyznamy znak Verified.</p><a href="{{ route('fachora.apply') }}" class="btn btn-secondary">Zgłoś firmę</a></div>
    </div>
    <div class="fh-note warm" style="max-width:860px;margin:28px auto 0;">
      <b>Nie wysyłamy firmom niezamówionych e-maili ani SMS-ów.</b> Jeśli Twoja firma jest w naszej bazie z rejestru publicznego, nie usłyszysz od nas niczego drogą elektroniczną, dopóki sama nie przejmiesz profilu. Dlatego przejęty i zweryfikowany profil to jedyna droga, którą zlecenia mogą do Ciebie trafić.
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="fh-split">
      <div>
        <div class="eyebrow">Standard Fachora</div>
        <h2>Dziesięć zasad, które chronią także Ciebie</h2>
        <p>Jasna cena z góry, prace dodatkowe tylko za zgodą, faktura zawsze, odpowiedź na reklamację w {{ $disputeHours }} godzin. Te zasady wykluczają z platformy firmy, które psują rynek, i dają Ci klienta, który wie, za co płaci.</p>
        <p>Standard działa w obie strony: klient, który nie odbiera pracy w {{ $acceptDays }} dni, akceptuje ją automatycznie, a odwołanie zlecenia później niż {{ config('fachora.cancel_free_hours') }} godziny przed terminem oznacza, że zatrzymujesz koszt dojazdu.</p>
        <a href="{{ route('fachora.standard') }}" class="btn btn-secondary">Przeczytaj Standard Fachora</a>
      </div>
      <ul class="fh-ticks left" style="display:grid;gap:10px;font-size:15px;">
        <li>Cena z potwierdzenia zlecenia jest wiążąca dla obu stron</li>
        <li>Spór rozstrzyga Fachora na podstawie zapisów i zdjęć</li>
        <li>Opinie tylko od zleceń zrealizowanych przez Fachorę</li>
        <li>Masz prawo odpowiedzieć na każdą opinię</li>
        <li>Zawieszenie dopiero po dwóch uzasadnionych reklamacjach w 90 dni</li>
        <li>Gwarancja {{ $warranty }} mies. standardowo; dłuższa to Twoja przewaga w profilu</li>
      </ul>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Pytania firm</div>
      <h2>Najczęstsze pytania</h2>
    </div>
    <div class="faq-list">
      <details class="faq-item"><summary>Czy muszę odpowiadać na każde zlecenie?{!! $chev !!}</summary><div class="faq-content">Nie. Odpowiadasz na te, które Ci pasują; przy innych klikasz „nie tym razem”, najlepiej z krótkim powodem. Liczy się czas reakcji, nie liczba ofert. Zlecenie, na które nie odpowiesz w {{ $offerHours }} godzin, po prostu wygasa.</div></details>
      <details class="faq-item"><summary>Kiedy dostaję pieniądze?{!! $chev !!}</summary><div class="faq-content">Przy Fachora Protect: po akceptacji klienta albo po {{ $acceptDays }} dniach od Twojego zgłoszenia „gotowe”, jeśli klient nie zareaguje. Stripe przelewa kwotę pomniejszoną o prowizję na rachunek firmy podany przy onboardingu. Bez Protect klient płaci Ci bezpośrednio{{ $lead ? ', bez żadnej prowizji.' : ', a prowizję rozliczamy osobno.' }}</div></details>
      <details class="faq-item"><summary>Co, gdy na miejscu okaże się, że jest więcej pracy?{!! $chev !!}</summary><div class="faq-content">Dodajesz pozycję „prace dodatkowe” z ceną i opisem. Klient zatwierdza ją na swojej stronie zlecenia, a przy Protect od razu ją dopłaca. Bez zatwierdzenia nie wykonujesz tej pracy, bo nikt Ci za nią nie zapłaci.</div></details>
      <details class="faq-item"><summary>Czy muszę mieć ubezpieczenie OC?{!! $chev !!}</summary><div class="faq-content">Nie jest obowiązkowe. Jeśli je masz, dodajesz polisę do profilu i klient widzi znak „ubezpieczony”, co w praktyce pomaga wygrać zlecenie. Przed wygaśnięciem polisy dostajesz przypomnienie.</div></details>
      <details class="faq-item"><summary>Czy mogę używać programu do faktur poza zleceniami z Fachory?{!! $chev !!}</summary><div class="faq-content">Tak, do wszystkich klientów. Faktury, oferty, kontrahenci z weryfikacją NIP, XML dla KSeF. Program jest bezpłatny, dopóki Twoja firma ma status Verified.</div></details>
      <details class="faq-item"><summary>Jak wygląda faktura?{!! $chev !!}</summary><div class="faq-content">Fachora jest usługą Budomatch — polskiej firmy. Opłaty za odblokowanie i prowizję fakturujemy polską fakturą VAT (23%). Fakturę dostajesz e-mailem.</div></details>
    </div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Pierwszy krok należy do Ciebie.</h2>
    <p>Przejmij profil albo zgłoś firmę. Weryfikacja jest bezpłatna, {{ $lead ? 'a pierwsze zapytanie klienta odblokowujesz gratis.' : 'a prowizję płacisz dopiero od pierwszego wykonanego zlecenia.' }}</p>
    <div class="fh-cta-row">
      <a href="{{ route('fachora.apply') }}" class="btn btn-primary btn-lg">Zgłoś firmę</a>
      <a href="{{ route('fachora.claim') }}" class="btn btn-white btn-lg">Przejmij profil</a>
      <a href="{{ route('login') }}" class="btn btn-white btn-lg">Panel firmy</a>
    </div>
  </div>
</section>

@endsection
