@extends('layouts.marketing')

{{-- Strona główna Fachory (APP_BRAND=fachora): klient opisuje zlecenie, Fachora przekazuje je kilku firmom Fachora Verified,
     klient porównuje ceny, płaci przez Fachora Protect i dostaje fakturę oraz gwarancję. Liczby z config/fachora.php. --}}

@php
  $fee = config('fachora.fee_percent');
  $intro = config('fachora.intro_fee_percent');
  $introDays = config('fachora.intro_days');
  $warranty = config('fachora.default_warranty_months');
  $maxOffers = config('fachora.max_offers');
  $offerHours = config('fachora.offer_hours');
  $acceptDays = config('fachora.auto_accept_days');
  $disputeHours = config('fachora.dispute_hours');
@endphp

@section('title', 'Fachora — sprawdzone firmy, bezpieczne usługi. Hydraulik, elektryk, ślusarz i inni fachowcy z jasną ceną i gwarancją')
@section('description', 'Opisz, co trzeba zrobić, a Fachora przekaże zlecenie kilku zweryfikowanym firmom w Twojej okolicy. Porównujesz ceny, wybierasz, cena jest ustalona z góry, płatność zabezpiecza Fachora Protect, zawsze dostajesz fakturę i gwarancję. Dla klientów bezpłatnie.')

@push('styles')
@include('fachora.partials.styles')
@endpush

@section('content')

<!-- HERO -->
<section class="hero">
  <div class="container hero-inner">
    <div class="eyebrow">Sprawdzone firmy. Bezpieczne usługi.</div>
    <h1>Zaufani fachowcy <span class="accent">w całej Polsce.</span></h1>
    <p class="hero-sub">
      Jasna cena. Pewny termin. Gwarancja. Opisz, co trzeba zrobić, a my przekażemy zlecenie kilku firmom Fachora Verified w Twojej okolicy. Porównasz oferty i wybierzesz, bez dzwonienia po znajomych.
    </p>

    <form class="fh-search" method="get" action="{{ route('fachora.intake') }}" role="search" aria-label="Znajdź fachowca">
      <label>
        <span class="lbl">Czego potrzebujesz?</span>
        <select name="kategoria">
          <option value="">Wybierz usługę</option>
          @foreach(config('fachora.categories') as $key => $cat)
          <option value="{{ $key }}">{{ $cat['pl'] }}</option>
          @endforeach
        </select>
      </label>
      <label>
        <span class="lbl">Gdzie?</span>
        <input type="text" name="miasto" placeholder="Miasto lub kod pocztowy" autocomplete="address-level2">
      </label>
      <button type="submit" class="btn btn-primary btn-lg">Znajdź fachowca</button>
    </form>

    <ul class="fh-ticks">
      <li>Bezpłatnie dla klientów</li>
      <li>Cena ustalona z góry</li>
      <li>Faktura zawsze</li>
      <li>Gwarancja {{ $warranty }} miesięcy</li>
      <li>Obsługa także po angielsku</li>
    </ul>
  </div>
</section>

<!-- KATEGORIE -->
<section class="section section-alt" id="uslugi">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Usługi</div>
      <h2>Czego potrzebujesz?</h2>
      <p>Dziesięć kategorii, w których działają firmy Fachora Verified. Wybierz swoją, a formularz zlecenia będzie już wypełniony.</p>
    </div>
    <div class="fh-cats">
      @foreach(config('fachora.categories') as $key => $cat)
      <a href="{{ route('fachora.intake', ['kategoria' => $key]) }}" class="fh-cat">
        <span class="ic">@include('fachora.partials.icon', ['name' => $cat['icon'], 'size' => 26])</span>
        <b>{{ $cat['pl'] }}</b>
        <span>{{ $cat['examples']['pl'] }}</span>
      </a>
      @endforeach
    </div>
  </div>
</section>

<!-- JAK TO DZIAŁA -->
<section class="section" id="jak-to-dziala">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Jak to działa</div>
      <h2>Od problemu do fachowca w czterech krokach</h2>
      <p>Bez konta, bez obdzwaniania, bez zgadywania, ile to będzie kosztować.</p>
    </div>
    <div class="fh-steps">
      <div class="fh-step">
        <div class="nr">1</div>
        <h3>Opisz zlecenie</h3>
        <p>Co, gdzie i kiedy. Dodaj zdjęcia, jeśli pomogą. Potwierdzasz e-mailem i dostajesz tajny link do swojego zlecenia.</p>
      </div>
      <div class="fh-step">
        <div class="nr">2</div>
        <h3>Porównaj oferty</h3>
        <p>Zlecenie trafia do maksymalnie {{ $maxOffers }} firm Fachora Verified. W ciągu {{ $offerHours }} godzin widzisz ceny, terminy i oceny.</p>
      </div>
      <div class="fh-step">
        <div class="nr">3</div>
        <h3>Wybierz i ustal cenę</h3>
        <p>Wybierasz firmę. Cena, zakres, termin i gwarancja trafiają do potwierdzenia zlecenia. Dopiero teraz firma widzi Twój adres.</p>
      </div>
      <div class="fh-step">
        <div class="nr">4</div>
        <h3>Odbierz pracę</h3>
        <p>Firma wykonuje pracę, Ty ją akceptujesz. Dostajesz fakturę i gwarancję, a firma otrzymuje wypłatę. Na koniec wystawiasz opinię.</p>
      </div>
    </div>
    <div class="fh-more"><a href="{{ route('fachora.how') }}">Zobacz wszystkie kroki ze szczegółami →</a></div>
  </div>
</section>

<!-- FACHORA PROTECT -->
<section class="fh-band" id="protect">
  <div class="container">
    <div class="eyebrow">Fachora Protect</div>
    <h2>Pieniądze czekają, aż powiesz: zrobione.</h2>
    <p class="lead">Przy zleceniu z Fachora Protect rezerwujesz ustaloną kwotę z góry. Firma wie, że zapłata jest pewna, a Ty, że zapłacisz dopiero za wykonaną pracę.</p>
    <div class="fh-band-grid">
      <div class="fh-flow">
        <div class="fh-flow-item">
          <div class="nr">1</div>
          <div><b>Rezerwujesz kwotę</b><p>BLIK, Przelewy24 lub karta. Płatność obsługuje Stripe, licencjonowana instytucja płatnicza.</p></div>
        </div>
        <div class="fh-flow-item">
          <div class="nr">2</div>
          <div><b>Firma wykonuje pracę</b><p>Dodatkowe prace tylko jako osobna pozycja, którą zatwierdzasz na swojej stronie zlecenia. Bez niespodzianek na koniec.</p></div>
        </div>
        <div class="fh-flow-item">
          <div class="nr">3</div>
          <div><b>Akceptujesz</b><p>Masz {{ $acceptDays }} dni od zgłoszenia „gotowe”. Jeśli nic nie zgłosisz, zlecenie uznajemy za przyjęte.</p></div>
        </div>
        <div class="fh-flow-item">
          <div class="nr">4</div>
          <div><b>Firma dostaje wypłatę</b><p>Dopiero po Twojej akceptacji. Prowizję Fachory płaci firma, nie Ty.</p></div>
        </div>
      </div>
      <div>
        <div class="fh-band-card">
          <div class="cap"><span>Potwierdzenie zlecenia</span><span class="pill">Płatność zarezerwowana</span></div>
          <table>
            <tr><td>Naprawa kotła gazowego</td><td class="r">480,00 zł</td></tr>
            <tr><td>Dodatkowe prace</td><td class="r">tylko za Twoją zgodą</td></tr>
            <tr><td>Termin</td><td class="r">piątek 9:00–11:00</td></tr>
            <tr><td>Gwarancja</td><td class="r">{{ $warranty }} miesięcy</td></tr>
            <tr><td>Faktura</td><td class="r">zawsze</td></tr>
            <tr class="tot"><td>Płacisz po akceptacji</td><td class="r">480,00 zł</td></tr>
          </table>
          <div class="ex">Przykład. Kwoty i terminy ustalasz z wybraną firmą.</div>
        </div>
        <div class="note">
          <b>Spór?</b> Pieniądze zostają zablokowane, dopóki sprawa nie zostanie rozstrzygnięta. Firma ma {{ $disputeHours }} godzin na odpowiedź; jeśli jej nie ma, decyzja jest po Twojej stronie. Fachora nie przechowuje Twoich pieniędzy: całą płatnością zajmuje się Stripe.
        </div>
        <div style="margin-top:18px;"><a href="{{ route('fachora.protect') }}" class="btn btn-white">Jak działa Fachora Protect</a></div>
      </div>
    </div>
  </div>
</section>

<!-- FACHORA VERIFIED -->
<section class="section" id="verified">
  <div class="container">
    <div class="fh-split">
      <div>
        <div class="eyebrow">Fachora Verified</div>
        <h2>Nie każda firma trafia na Fachorę.</h2>
        <p>Zanim firma dostanie znak Fachora Verified i pierwsze zlecenie, sprawdzamy ją w rejestrach i u partnera płatniczego. Dopiero wtedy ręczymy za to, co widzisz w jej profilu.</p>
        <p>Firmy z rejestrów publicznych, których jeszcze nie sprawdziliśmy, pokazujemy jako <b>profil nieaktywny</b>: bez numeru telefonu i bez zleceń. Nie wysyłamy im żadnych e-maili ani SMS-ów. Mogą same przejąć swój profil i przejść weryfikację.</p>
        <p><a href="{{ route('fachora.standard') }}" class="btn btn-secondary">Standard Fachora: dziesięć zasad</a></p>
      </div>
      <div class="fh-checks" style="grid-template-columns:1fr;">
        <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'invoice', 'size' => 20])</span><div><b>Rejestr i NIP</b><p>Firma istnieje, jest aktywna w CEIDG lub KRS, NIP i status VAT się zgadzają.</p></div></div>
        <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'shield', 'size' => 20])</span><div><b>Tożsamość i konto bankowe</b><p>Osoba reprezentująca firmę i jej rachunek bankowy potwierdzone przez Stripe.</p></div></div>
        <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'check', 'size' => 20])</span><div><b>Telefon, e-mail, regulamin</b><p>Numer potwierdzony kodem SMS, adres linkiem, Standard Fachora zaakceptowany z datą i wersją.</p></div></div>
        <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'star', 'size' => 20])</span><div><b>Ubezpieczenie OC: widoczne</b><p>Jeśli firma ma polisę, zobaczysz to w profilu. Po jej wygaśnięciu znak znika do czasu odnowienia.</p></div></div>
      </div>
    </div>
  </div>
</section>

<!-- PRZYKŁADOWY PROFIL -->
<section class="section section-alt" id="profil">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Tak wygląda profil firmy</div>
      <h2>Co widzisz przy każdej firmie</h2>
      <p>Poniższe karty to <b>przykłady</b>: pokazują układ profilu, nie prawdziwe firmy. Oceny i stawki w prawdziwych profilach pochodzą wyłącznie z wykonanych zleceń.</p>
    </div>
    <div class="fh-companies">
      <div class="fh-company">
        <span class="ex">Przykład</span>
        <span class="fv-badge">Fachora Verified</span>
        <div class="name">Instalacje Przykładowe</div>
        <div class="meta">Hydraulik · Ogrzewanie · Łódź, do 25 km</div>
        <div class="rating"><b>★ 4,8</b> · ocena z potwierdzonych zleceń</div>
        <div class="langs"><span>🇵🇱 polski</span><span>🇬🇧 English</span><span>🇩🇪 Deutsch</span></div>
        <ul class="ticks"><li>Faktura</li><li>Gwarancja {{ $warranty }} mies.</li><li>Fachora Protect</li><li>Ubezpieczony</li></ul>
        <div class="rates">Stawki orientacyjne: dojazd 80 zł · pierwsza godzina 150 zł</div>
      </div>
      <div class="fh-company">
        <span class="ex">Przykład</span>
        <span class="fv-badge">Fachora Verified</span>
        <div class="name">Elektro Wzór</div>
        <div class="meta">Elektryk · Warszawa, do 30 km</div>
        <div class="rating"><b>★ 4,9</b> · ocena z potwierdzonych zleceń</div>
        <div class="langs"><span>🇵🇱 polski</span><span>🇬🇧 English</span><span>🇺🇦 українська</span></div>
        <ul class="ticks"><li>Faktura</li><li>Gwarancja {{ $warranty }} mies.</li><li>Fachora Protect</li><li>Reakcja w 24 h</li></ul>
        <div class="rates">Stawki orientacyjne: dojazd 60 zł · pierwsza godzina 140 zł</div>
      </div>
      <div class="fh-company">
        <span class="ex">Przykład</span>
        <span class="fv-badge">Fachora Verified</span>
        <div class="name">Zamki Demo</div>
        <div class="meta">Ślusarz · Kraków, do 20 km</div>
        <div class="rating"><b>★ 4,7</b> · ocena z potwierdzonych zleceń</div>
        <div class="langs"><span>🇵🇱 polski</span><span>🇬🇧 English</span></div>
        <ul class="ticks"><li>Faktura</li><li>Gwarancja {{ $warranty }} mies.</li><li>Fachora Protect</li><li>Awarie 24/7</li></ul>
        <div class="rates">Stawki orientacyjne: dojazd 90 zł · otwarcie od 200 zł</div>
      </div>
    </div>
    <p style="text-align:center;font-size:13.5px;color:var(--text-3);margin-top:18px;">Nie pokazujemy numerów telefonu. Rozmowa i płatność idą przez Fachorę, dzięki temu działa gwarancja.</p>
  </div>
</section>

<!-- CZTERY OBIETNICE -->
<section class="section" id="dlaczego">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Dlaczego Fachora</div>
      <h2>Cztery rzeczy, które obiecujemy</h2>
    </div>
    <div class="fh-usps">
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'shield', 'size' => 22])</span><h3>Weryfikacja firm</h3><p>Rejestr, NIP, tożsamość, konto bankowe, telefon i e-mail sprawdzone, zanim firma zobaczy pierwsze zlecenie.</p></div>
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'clock', 'size' => 22])</span><h3>Pewny termin</h3><p>Termin jest częścią zlecenia. Firma go potwierdza, a odwołać może tylko z podanego powodu. No-show po rezerwacji to usunięcie z platformy.</p></div>
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'card', 'size' => 22])</span><h3>Bezpieczne płatności</h3><p>Z Fachora Protect płacisz dopiero po akceptacji pracy. W razie sporu pieniądze czekają na rozstrzygnięcie.</p></div>
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'star', 'size' => 22])</span><h3>Gwarancja na usługi</h3><p>Standardowo {{ $warranty }} miesięcy. Reklamacja wraca do zlecenia, a firma musi odpowiedzieć w {{ $disputeHours }} godzin.</p></div>
    </div>
  </div>
</section>

<!-- DLA FIRM -->
<section class="section section-alt" id="dla-firm">
  <div class="container">
    <div class="fh-split">
      <div>
        <div class="eyebrow">Dla firm</div>
        <h2>Nowi klienci w Twojej okolicy. Płacisz tylko za zrealizowane zlecenia.</h2>
        <p>0 zł abonamentu, 0 zł za rejestrację, 0 zł za zlecenie. Prowizja {{ $fee }}% wyłącznie od wykonanej i zaakceptowanej pracy, a przez pierwsze {{ $introDays }} dni tylko {{ $intro }}%. Do tego darmowy program do faktur gotowy na KSeF i profil z opiniami od prawdziwych klientów.</p>
        <div class="fh-cta-row" style="justify-content:flex-start;">
          <a href="{{ route('fachora.for-companies') }}" class="btn btn-primary">Zobacz ofertę dla firm</a>
          <a href="{{ route('fachora.claim') }}" class="btn btn-secondary">Przejmij profil firmy</a>
        </div>
      </div>
      <div class="fh-prices" style="grid-template-columns:1fr 1fr;">
        <div class="fh-price"><div class="v">0 <small>zł</small></div><b>abonament</b><span>bez miesięcznej opłaty</span></div>
        <div class="fh-price"><div class="v">0 <small>zł</small></div><b>za zlecenie</b><span>nie płacisz za kontakt ani za ofertę</span></div>
        <div class="fh-price hl"><div class="v">{{ $fee }}<small>%</small></div><b>od wykonanej pracy</b><span>przez pierwsze {{ $introDays }} dni {{ $intro }}%</span></div>
        <div class="fh-price"><div class="v">0 <small>zł</small></div><b>program do faktur</b><span>faktury gotowe na KSeF, dopóki jesteś Verified</span></div>
      </div>
    </div>
  </div>
</section>

<!-- JĘZYKI -->
<section class="section" id="jezyki" style="padding-top:0;">
  <div class="container">
    <div class="section-header" style="margin-bottom:28px;">
      <div class="eyebrow" style="margin-bottom:16px;">Nie mówisz po polsku?</div>
      <h2>Wybierz język, w którym chcesz rozmawiać z firmą</h2>
      <p>Przy zleceniu zaznaczasz język. Trafi ono tylko do firm, które go znają, a ich poziom widzisz w profilu.</p>
    </div>
    <div class="fh-langs">
      @foreach(config('fachora.languages') as $code => $lang)
      <span class="fh-lang"><span class="flag" aria-hidden="true">{{ $lang['flag'] }}</span>{{ $lang['pl'] }}</span>
      @endforeach
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="section section-alt" id="faq">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Najczęstsze pytania</div>
      <h2>Trzy rzeczy, o które pytają najczęściej</h2>
    </div>
    <div class="faq-list">
      <details class="faq-item">
        <summary>Ile to kosztuje?<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Dla klientów Fachora jest bezpłatna: nie płacisz za zgłoszenie, oferty ani za Fachora Protect. Płacisz tylko cenę ustaloną z firmą. Prowizję od wykonanego zlecenia ponosi firma.</div>
      </details>
      <details class="faq-item">
        <summary>Czy dostanę numer telefonu firmy?<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Nie przed wyborem firmy. Oferty, pytania i ustalenia idą przez Twoją stronę zlecenia, dzięki temu wszystko jest zapisane i obowiązuje gwarancja. Po wyborze firma dostaje Twój adres i możecie uzgodnić szczegóły.</div>
      </details>
      <details class="faq-item">
        <summary>Co, jeśli praca jest źle wykonana?<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Zgłaszasz spór na stronie zlecenia, ze zdjęciami i opisem. Firma ma {{ $disputeHours }} godzin na odpowiedź, a Fachora rozstrzyga: wypłata, częściowa wypłata, naprawa albo zwrot. Przy Fachora Protect pieniądze przez cały ten czas pozostają zablokowane.</div>
      </details>
    </div>
    <div class="fh-more"><a href="{{ route('pl.faq') }}">Wszystkie pytania i odpowiedzi →</a></div>
  </div>
</section>

<!-- CTA -->
<section class="cta-final">
  <div class="container cta-inner">
    <h2>Opisz zlecenie. Resztą zajmiemy się my.</h2>
    <p>Kilka minut na opis, oferty w ciągu {{ $offerHours }} godzin, cena ustalona przed przyjazdem fachowca.</p>
    <div class="fh-cta-row">
      <a href="{{ route('fachora.intake') }}" class="btn btn-primary btn-lg">Znajdź fachowca</a>
      <a href="{{ route('fachora.how') }}" class="btn btn-white btn-lg">Jak to działa</a>
    </div>
  </div>
</section>

@endsection
