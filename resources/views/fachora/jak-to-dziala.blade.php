@extends('layouts.marketing')
{{-- Jak działa Fachora dla klienta: sześć kroków od zgłoszenia do gwarancji. Klient sam wybiera firmy (Verified
     dostają zapytanie od nas, z firmą z rejestru klient kontaktuje się sam), co widać przy firmie, spory, koszty.
     Liczby z config/fachora.php. --}}
@php
    $protect = fachora_protect();
    $warranty = config('fachora.default_warranty_months');
    $maxOffers = config('fachora.max_offers');
    $offerHours = config('fachora.offer_hours');
    $acceptDays = config('fachora.auto_accept_days');
    $disputeHours = config('fachora.dispute_hours');
    $confirmDays = config('fachora.confirm_days');
    $cancelHours = config('fachora.cancel_free_hours');
    $candidates = config('fachora.choice_candidates');
    $keepDays = (int) config('fachora.register_keep_days');
@endphp
@section('title', 'Jak działa Fachora — sam wybierasz firmy, od zgłoszenia do gwarancji w sześciu krokach')
@section('description', 'Opisujesz zlecenie, potwierdzasz e-mailem, sam wybierasz do ' . $maxOffers . ' firm z okolicy (Fachora Verified i firmy z rejestru), porównujesz ceny, wybierasz jedną, płacisz po pracy' . ($protect ? ' przez Fachora Protect albo bezpośrednio firmie' : ' bezpośrednio firmie') . '. Dla klientów bezpłatnie.')

@push('styles')
@include('fachora.partials.styles')
@endpush

@section('content')

<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Dla klientów</div>
    <h1>Jak działa Fachora</h1>
    <p class="lead">Od pierwszego opisu do gwarancji na wykonaną pracę. Sześć kroków, w każdym wiesz, co się dzieje, kto co widzi i kto się z kim kontaktuje.</p>
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
            <li><b>Język:</b> polski, angielski, niemiecki, niderlandzki lub ukraiński. Firmy Verified dostają zapytanie tylko wtedy, gdy znają ten język; opis tłumaczymy automatycznie na polski, więc firmie z rejestru możesz napisać po polsku.</li>
            <li><b>Kontakt:</b> adres e-mail i numer telefonu, który potwierdzasz kodem SMS, zanim zapytanie wyjdzie. Dokładny adres zlecenia podajesz dopiero po wyborze firmy.</li>
          </ul>
        </div>
      </div>

      <div class="fh-step">
        <div class="nr">2</div>
        <div>
          <h3>Potwierdzasz e-mailem</h3>
          <p>Dostajesz wiadomość z linkiem. Po kliknięciu zlecenie jest aktywne, a link prowadzi do Twojej strony „Moje zlecenie”: tam wybierasz firmy, widzisz ceny, płacisz i odbierasz pracę. Niepotwierdzone zlecenie znika po {{ $confirmDays }} dniach.</p>
          <p style="margin-top:10px;">Jeśli w Twojej okolicy nie ma jeszcze firm na Fachorze, sprawdzamy rejestr przedsiębiorców wokół Ciebie. Trwa to zwykle kilka minut; gdy lista jest gotowa, dostajesz e-mail.</p>
          <span class="tag">Zachowaj ten link, to Twój klucz do zlecenia</span>
        </div>
      </div>

      <div class="fh-step">
        <div class="nr">3</div>
        <div>
          <h3>Sam wybierasz firmy</h3>
          <p>Widzisz do {{ $candidates }} firm z okolicy. Kolejność: najpierw Fachora Verified, potem ocena, potem odległość; firmy nie płacą za miejsce na liście. Przy każdej widzisz profil, języki, odległość i oznaczenie „z rejestru”, jeśli to firma, której jeszcze nie sprawdziliśmy. Zaznaczasz maksymalnie {{ $maxOffers }} i wysyłasz zapytanie. Dwa rodzaje firm, dwie drogi:</p>
          <ul>
            <li><b>Firma Fachora Verified:</b> zapytanie dostaje od nas e-mailem, bo sama przejęła profil i zgodziła się na zlecenia. Odpowiada na Twojej stronie zlecenia.</li>
            <li><b>Firma z rejestru:</b> nie dostaje od nas nic. Pokazujemy Ci jej publiczne dane kontaktowe z CEIDG i gotową wiadomość po polsku z opisem zlecenia; dzwonisz lub piszesz sam. Dane firm z rejestru, których nikt nie wybrał, usuwamy po {{ $keepDays }} dniach.</li>
          </ul>
        </div>
      </div>

      <div class="fh-step">
        <div class="nr">4</div>
        <div>
          <h3>Porównujesz ceny i wybierasz jedną</h3>
          <p>Firmy Verified mają {{ $offerHours }} godzin na odpowiedź. Każda oferta zawiera cenę (stałą albo stawkę godzinową z kosztem dojazdu), proponowany termin i uwagi firmy, na przykład co trzeba sprawdzić na miejscu. Obok oferty widzisz profil: ocenę z wykonanych zleceń, języki, gwarancję i co zostało sprawdzone.</p>
          <p style="margin-top:10px;">Jedno kliknięcie: podajesz adres, a cena, zakres, termin, gwarancja i obowiązek faktury trafiają do potwierdzenia zlecenia. Dopiero teraz wybrana firma widzi Twój adres i numer telefonu, a pozostałe firmy dostają informację, że zlecenie jest zamknięte. Z firmą z rejestru wszystko ustalasz bezpośrednio, poza Fachorą.</p>
        </div>
      </div>

      <div class="fh-step">
        <div class="nr">5</div>
        <div>
          @if($protect)
          <h3>Rezerwujesz płatność albo płacisz po</h3>
          <p>Z <a href="{{ route('fachora.protect') }}">Fachora Protect</a> rezerwujesz ustaloną kwotę przez BLIK, Przelewy24 lub kartę. Płatność obsługuje Stripe, instytucja pieniądza elektronicznego; Fachora nie przechowuje Twoich pieniędzy. Firma dostaje je dopiero po Twojej akceptacji.</p>
          <p style="margin-top:10px;">Możesz też zapłacić firmie bezpośrednio po wykonaniu pracy. Wtedy nie działa blokada pieniędzy ani rozstrzyganie sporu z udziałem płatności, ale weryfikacja firmy, faktura i gwarancja firmy Verified pozostają.</p>
          @else
          <h3>Płacisz po pracy</h3>
          <p>Płacisz firmie bezpośrednio po wykonaniu pracy, dokładnie tyle, ile jest w potwierdzeniu zlecenia: BLIK, przelew albo gotówka, jak się umówicie. Fachora nie pośredniczy w płatności i nigdy nie trzyma Twoich pieniędzy.</p>
          <p style="margin-top:10px;">Firma Verified zawsze wystawia fakturę i daje gwarancję; dodatkowe prace tylko po Twojej zgodzie na stronie zlecenia.</p>
          @endif
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
            <li>Po akceptacji firma Verified wystawia <b>fakturę</b> i otrzymuje wypłatę.</li>
            <li>Gwarancja firmy Verified trwa standardowo <b>{{ $warranty }} miesięcy</b>. Reklamacja w tym czasie wraca do tego samego zlecenia.</li>
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
      <p>Wszystko, co pozwala porównać oferty. Firma Verified: bez numeru telefonu, kontakt i płatność idą przez Twoją stronę zlecenia, dzięki temu wszystko jest zapisane. Firma z rejestru: publiczne dane z CEIDG, widoczne dopiero wtedy, gdy ją wybierzesz.</p>
    </div>
    <div class="fh-checks">
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'shield', 'size' => 20])</span><div><b>Znak Fachora Verified</b><p>Firma przeszła weryfikację: rejestr, NIP,{{ $protect ? ' tożsamość, konto bankowe,' : '' }} telefon, e-mail i Standard Fachora. Bez znaku: profil z rejestru.</p></div></div>
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'star', 'size' => 20])</span><div><b>Ocena i liczba zleceń</b><p>Tylko z zleceń wykonanych przez Fachorę. Opinie z odpowiedzią firmy, nigdy przez nią usuwane.</p></div></div>
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'lang', 'size' => 20])</span><div><b>Języki i poziom</b><p>Polski, angielski, niemiecki, niderlandzki, ukraiński: ojczysty, dobry albo podstawowy.</p></div></div>
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'card', 'size' => 20])</span><div><b>Stawki orientacyjne</b><p>Dojazd, pierwsza godzina, kolejna godzina. Ostateczna cena jest zawsze w ofercie do Twojego zlecenia.</p></div></div>
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'invoice', 'size' => 20])</span><div><b>Faktura, gwarancja{{ $protect ? ', Protect' : '' }}</b><p>Każda firma Verified wystawia fakturę i daje gwarancję. Widzisz też, czy ma ubezpieczenie OC.</p></div></div>
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'pin', 'size' => 20])</span><div><b>Zasięg i odległość</b><p>Miasto, promień działania i odległość od Twojego zlecenia; przy firmach Verified także to, jak szybko zwykle odpowiadają.</p></div></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="fh-split">
      <div>
        <div class="eyebrow">Gdy coś pójdzie nie tak</div>
        <h2>Spór rozstrzyga Fachora, nie siła przebicia</h2>
        <p>Przy zleceniu z firmą Verified zgłaszasz spór na stronie zlecenia: opis, zdjęcia, czego oczekujesz. Firma ma {{ $disputeHours }} godzin na odpowiedź. Potem Fachora patrzy na potwierdzenie zlecenia, zdjęcia i wiadomości i decyduje: {{ $protect ? 'pełna wypłata, częściowa wypłata, naprawa w nowym terminie albo zwrot' : 'naprawa w nowym terminie albo niższa cena; firma, która nie respektuje rozstrzygnięcia, traci znak Verified' }}.</p>
        <p>Jeśli firma nie odpowie w terminie, decyzja jest po Twojej stronie.@if($protect) Przy Fachora Protect pieniądze są przez cały czas zablokowane, więc nikt nie musi ich od nikogo odzyskiwać.@endif</p>
        <p>Dwie uzasadnione reklamacje w 90 dni zawieszają firmę; no-show po {{ $protect ? 'rezerwacji' : 'potwierdzonym terminie' }} albo próba ominięcia Fachory to usunięcie z platformy. Szczegóły w <a href="{{ route('fachora.standard') }}" style="color:var(--brand);font-weight:600;">Standardzie Fachora</a>. Z firmą z rejestru umawiasz się bezpośrednio, więc te zasady jej nie obejmują.</p>
      </div>
      <div>
        <div class="fh-note">
          <b>Ile to kosztuje?</b><br>
          Dla klientów Fachora jest bezpłatna. Nie płacisz za zgłoszenie, za wybór firm, za oferty{{ $protect ? ', za Fachora Protect' : '' }} ani za rozstrzygnięcie sporu. Płacisz wyłącznie cenę ustaloną z firmą, taką, jaka jest w potwierdzeniu zlecenia. Opłaty Fachory ponosi firma.
        </div>
        <div class="fh-note warm" style="margin-top:14px;">
          <b>Twoje zlecenie widzą tylko firmy, które sam zaznaczysz.</b><br>
          Maksymalnie {{ $maxOffers }} z listy do {{ $candidates }} firm z okolicy. Firmy Verified dostają je od nas; firmie z rejestru Fachora nie wysyła niczego, to Ty się z nią kontaktujesz. Jeśli w Twojej okolicy nikogo nie ma, powiemy Ci to wprost, zamiast obiecywać oferty, których nie będzie.
        </div>
      </div>
    </div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Gotowy? Opisz zlecenie.</h2>
    <p>Kilka minut, bez konta. Firmy Verified odpowiadają w ciągu {{ $offerHours }} godzin.</p>
    <div class="fh-cta-row">
      <a href="{{ route('fachora.intake') }}" class="btn btn-primary btn-lg">Znajdź fachowca</a>
      @if($protect)<a href="{{ route('fachora.protect') }}" class="btn btn-white btn-lg">Fachora Protect</a>@endif
    </div>
  </div>
</section>

@endsection
