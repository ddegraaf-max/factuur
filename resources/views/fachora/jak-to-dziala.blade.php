@extends('layouts.marketing')

{{-- Jak działa Fachora dla klienta: sześć kroków od zgłoszenia do gwarancji, co widać przy firmie, spory, koszty. Liczby z config/fachora.php. --}}

@php
  $warranty = config('fachora.default_warranty_months');
  $maxOffers = config('fachora.max_offers');
  $offerHours = config('fachora.offer_hours');
  $acceptDays = config('fachora.auto_accept_days');
  $disputeHours = config('fachora.dispute_hours');
  $confirmDays = config('fachora.confirm_days');
  $cancelHours = config('fachora.cancel_free_hours');
@endphp

@section('title', 'Jak działa Fachora — od zgłoszenia do gwarancji w sześciu krokach')
@section('description', 'Opisujesz zlecenie, potwierdzasz e-mailem, dostajesz oferty od maksymalnie ' . $maxOffers . ' zweryfikowanych firm w ' . $offerHours . ' godzin, wybierasz, rezerwujesz płatność przez Fachora Protect, odbierasz pracę, dostajesz fakturę i gwarancję. Dla klientów bezpłatnie.')

@push('styles')
@include('fachora.partials.styles')
@endpush

@section('content')

<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Dla klientów</div>
    <h1>Jak działa Fachora</h1>
    <p class="lead">Od pierwszego opisu do gwarancji na wykonaną pracę. Sześć kroków, w każdym wiesz, co się dzieje i kto co widzi.</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="fh-steps list">
      <div class="fh-step">
        <div class="nr">1</div>
        <div>
          <h3>Opisujesz zlecenie</h3>
          <p>Bez konta. Wybierasz kategorię, podajesz miasto lub kod pocztowy i opisujesz własnymi słowami, co się dzieje. Zdjęcia pomagają firmom wycenić pracę bez wizyty.</p>
          <ul>
            <li><b>Kiedy:</b> teraz (awaria), w tym tygodniu, w ciągu miesiąca albo konkretna data.</li>
            <li><b>Język:</b> polski, angielski, niemiecki, niderlandzki lub ukraiński. Zlecenie trafi tylko do firm, które go znają.</li>
            <li><b>Kontakt:</b> adres e-mail. Dokładny adres zlecenia podajesz dopiero po wyborze firmy.</li>
          </ul>
        </div>
      </div>
      <div class="fh-step">
        <div class="nr">2</div>
        <div>
          <h3>Potwierdzasz e-mailem</h3>
          <p>Dostajesz wiadomość z linkiem. Po kliknięciu zlecenie jest aktywne, a link prowadzi do Twojej strony „Moje zlecenie”: tam widzisz oferty, wybierasz, płacisz i odbierasz pracę. Niepotwierdzone zlecenie znika po {{ $confirmDays }} dniach.</p>
          <span class="tag">Zachowaj ten link, to Twój klucz do zlecenia</span>
        </div>
      </div>
      <div class="fh-step">
        <div class="nr">3</div>
        <div>
          <h3>Oferty od zweryfikowanych firm</h3>
          <p>Fachora przekazuje zlecenie maksymalnie {{ $maxOffers }} firmom Fachora Verified z Twojej okolicy, które mówią w Twoim języku. Firmy mają {{ $offerHours }} godzin na odpowiedź. Każda oferta zawiera:</p>
          <ul>
            <li>cenę: stałą albo stawkę godzinową z kosztem dojazdu,</li>
            <li>proponowany termin,</li>
            <li>uwagi firmy, na przykład co trzeba sprawdzić na miejscu.</li>
          </ul>
          <p style="margin-top:10px;">Obok oferty widzisz profil firmy: ocenę z wykonanych zleceń, języki, gwarancję i co zostało sprawdzone.</p>
        </div>
      </div>
      <div class="fh-step">
        <div class="nr">4</div>
        <div>
          <h3>Wybierasz firmę i podajesz adres</h3>
          <p>Jedno kliknięcie. Cena, zakres, termin, gwarancja i obowiązek faktury trafiają do potwierdzenia zlecenia. Dopiero teraz wybrana firma widzi Twój adres i dane kontaktowe, a pozostałe firmy dostają informację, że zlecenie jest zamknięte.</p>
        </div>
      </div>
      <div class="fh-step">
        <div class="nr">5</div>
        <div>
          <h3>Rezerwujesz płatność albo płacisz po</h3>
          <p>Z <a href="{{ route('fachora.protect') }}">Fachora Protect</a> rezerwujesz ustaloną kwotę przez BLIK, Przelewy24 lub kartę. Płatność obsługuje Mollie, licencjonowana instytucja płatnicza; Fachora nie przechowuje Twoich pieniędzy. Firma dostaje je dopiero po Twojej akceptacji.</p>
          <p style="margin-top:10px;">Możesz też zapłacić firmie bezpośrednio po wykonaniu pracy. Wtedy nie działa blokada pieniędzy ani rozstrzyganie sporu z udziałem płatności, ale weryfikacja firmy, faktura i gwarancja firmy pozostają.</p>
          <span class="tag">Do {{ $cancelHours }} godzin przed terminem odwołasz zlecenie bezpłatnie</span>
        </div>
      </div>
      <div class="fh-step">
        <div class="nr">6</div>
        <div>
          <h3>Praca, odbiór, faktura, gwarancja</h3>
          <p>Firma potwierdza termin i zgłasza start. Jeśli na miejscu okaże się, że trzeba zrobić więcej, firma dodaje osobną pozycję, a Ty zatwierdzasz ją na swojej stronie. Bez Twojej zgody dodatkowe prace nie istnieją.</p>
          <ul>
            <li>Po zgłoszeniu „gotowe” masz <b>{{ $acceptDays }} dni</b> na akceptację albo zgłoszenie sporu. Brak reakcji oznacza akceptację.</li>
            <li>Po akceptacji firma wystawia <b>fakturę</b> i otrzymuje wypłatę.</li>
            <li>Gwarancja trwa standardowo <b>{{ $warranty }} miesięcy</b>. Reklamacja w tym czasie wraca do tego samego zlecenia.</li>
            <li>Wystawiasz <b>opinię</b>. Opinie pochodzą tylko z zleceń zrealizowanych przez Fachorę i firma nie może ich usunąć.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Profil firmy</div>
      <h2>Co widzisz przy każdej firmie</h2>
      <p>Wszystko, co pozwala porównać oferty. Bez numeru telefonu: kontakt i płatność idą przez Fachorę, dzięki temu wszystko jest zapisane.</p>
    </div>
    <div class="fh-checks">
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'shield', 'size' => 20])</span><div><b>Znak Fachora Verified</b><p>Firma przeszła weryfikację: rejestr, NIP, tożsamość, konto bankowe, telefon, e-mail i Standard Fachora.</p></div></div>
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'star', 'size' => 20])</span><div><b>Ocena i liczba zleceń</b><p>Tylko z zleceń wykonanych przez Fachorę. Opinie z odpowiedzią firmy, nigdy przez nią usuwane.</p></div></div>
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'lang', 'size' => 20])</span><div><b>Języki i poziom</b><p>Polski, angielski, niemiecki, niderlandzki, ukraiński: ojczysty, dobry albo podstawowy.</p></div></div>
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'card', 'size' => 20])</span><div><b>Stawki orientacyjne</b><p>Dojazd, pierwsza godzina, kolejna godzina. Ostateczna cena jest zawsze w ofercie do Twojego zlecenia.</p></div></div>
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'invoice', 'size' => 20])</span><div><b>Faktura, gwarancja, Protect</b><p>Każda firma Verified wystawia fakturę i daje gwarancję. Widzisz też, czy ma ubezpieczenie OC.</p></div></div>
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'pin', 'size' => 20])</span><div><b>Zasięg i czas reakcji</b><p>Miasto i promień działania oraz to, jak szybko firma zwykle odpowiada na zlecenia.</p></div></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="fh-split">
      <div>
        <div class="eyebrow">Gdy coś pójdzie nie tak</div>
        <h2>Spór rozstrzyga Fachora, nie siła przebicia</h2>
        <p>Zgłaszasz spór na stronie zlecenia: opis, zdjęcia, czego oczekujesz. Firma ma {{ $disputeHours }} godzin na odpowiedź. Potem Fachora patrzy na potwierdzenie zlecenia, zdjęcia i wiadomości i decyduje: pełna wypłata, częściowa wypłata, naprawa w nowym terminie albo zwrot.</p>
        <p>Jeśli firma nie odpowie w terminie, decyzja jest po Twojej stronie. Przy Fachora Protect pieniądze są przez cały czas zablokowane, więc nikt nie musi ich od nikogo odzyskiwać.</p>
        <p>Dwie uzasadnione reklamacje w 90 dni zawieszają firmę; no-show po rezerwacji albo próba ominięcia Fachory to usunięcie z platformy. Szczegóły w <a href="{{ route('fachora.standard') }}" style="color:var(--brand);font-weight:600;">Standardzie Fachora</a>.</p>
      </div>
      <div>
        <div class="fh-note">
          <b>Ile to kosztuje?</b><br>
          Dla klientów Fachora jest bezpłatna. Nie płacisz za zgłoszenie, za oferty, za Fachora Protect ani za rozstrzygnięcie sporu. Płacisz wyłącznie cenę ustaloną z firmą, taką, jaka jest w potwierdzeniu zlecenia. Opłaty Fachory ponosi firma.
        </div>
        <div class="fh-note warm" style="margin-top:14px;">
          <b>Twoje zlecenie trafia tylko do firm, które sam wybierzesz.</b><br>
          Wybierasz do {{ config('fachora.max_offers') }} firm z okolicy: zweryfikowane (Fachora Verified) są na górze listy, firmy z rejestru są wyraźnie oznaczone. Jeśli w Twojej okolicy nikogo nie ma, powiemy Ci to od razu, zamiast obiecywać oferty, których nie będzie.
        </div>
      </div>
    </div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Gotowy? Opisz zlecenie.</h2>
    <p>Kilka minut, bez konta. Oferty w ciągu {{ $offerHours }} godzin.</p>
    <div class="fh-cta-row">
      <a href="{{ route('fachora.intake') }}" class="btn btn-primary btn-lg">Znajdź fachowca</a>
      <a href="{{ route('fachora.protect') }}" class="btn btn-white btn-lg">Fachora Protect</a>
    </div>
  </div>
</section>

@endsection
