@extends('layouts.marketing')

{{-- Fachora Protect: po co, jak płyną pieniądze (Mollie, rezerwacja, wypłata po akceptacji, spór), czego nie obejmuje, terminy, FAQ.
     Uczciwie: płatność obsługuje Mollie, Fachora nie przechowuje pieniędzy klientów. Liczby z config/fachora.php. --}}

@php
  $fee = config('fachora.fee_percent');
  $warranty = config('fachora.default_warranty_months');
  $offerHours = config('fachora.offer_hours');
  $acceptDays = config('fachora.auto_accept_days');
  $disputeHours = config('fachora.dispute_hours');
  $cancelHours = config('fachora.cancel_free_hours');
  $chev = '<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>';
@endphp

@section('title', 'Fachora Protect — płacisz dopiero, gdy praca jest wykonana')
@section('description', 'Fachora Protect: rezerwujesz ustaloną kwotę przez Mollie (BLIK, Przelewy24, karta), firma wykonuje pracę, Ty akceptujesz w ciągu ' . $acceptDays . ' dni, dopiero wtedy firma dostaje wypłatę. W sporze pieniądze pozostają zablokowane. Fachora nie przechowuje Twoich pieniędzy.')

@push('styles')
@include('fachora.partials.styles')
@endpush

@section('content')

<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Fachora Protect</div>
    <h1>Pieniądze czekają, aż powiesz: zrobione.</h1>
    <p class="lead">Ustalona kwota jest zarezerwowana przed przyjazdem fachowca i wypłacona dopiero po Twojej akceptacji. Firma ma pewność zapłaty, Ty masz pewność, że płacisz za wykonaną pracę.</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Po co to</div>
      <h2>Trzy problemy, które Protect rozwiązuje</h2>
    </div>
    <div class="fh-steps cols-3">
      <div class="fh-step"><h3>„Przyjadę jutro”</h3><p>Fachowiec, który nie przyjeżdża. Przy Protect firma wie, że pieniądze są zarezerwowane, a no-show po rezerwacji oznacza usunięcie z platformy.</p></div>
      <div class="fh-step"><h3>Cena, która rośnie na miejscu</h3><p>Kwota w potwierdzeniu zlecenia jest ostateczna. Dodatkowe prace to osobna pozycja, którą zatwierdzasz albo nie. Bez zatwierdzenia nie ma rachunku.</p></div>
      <div class="fh-step"><h3>Gotówka bez faktury</h3><p>Płatność idzie przez Mollie, więc jest ślad, a firma Verified zawsze wystawia fakturę. To podstawa gwarancji i ewentualnej reklamacji.</p></div>
    </div>
  </div>
</section>

<section class="fh-band">
  <div class="container">
    <div class="eyebrow">Jak płyną pieniądze</div>
    <h2>Cztery kroki, zero niespodzianek</h2>
    <p class="lead">Płatność obsługuje Mollie B.V., licencjonowana instytucja płatnicza nadzorowana przez De Nederlandsche Bank. Fachora nie prowadzi własnego rachunku na pieniądze klientów i nigdy ich nie przechowuje.</p>
    <div class="fh-band-grid">
      <div class="fh-flow">
        <div class="fh-flow-item"><div class="nr">1</div><div><b>Rezerwacja</b><p>Po wyborze firmy płacisz kwotę z potwierdzenia zlecenia przez BLIK, Przelewy24 lub kartę. Pieniądze trafiają do Mollie i są oznaczone jako zarezerwowane na to zlecenie.</p></div></div>
        <div class="fh-flow-item"><div class="nr">2</div><div><b>Praca</b><p>Firma potwierdza termin, zgłasza start i koniec. Dodatkowe prace zatwierdzasz na swojej stronie zlecenia; wtedy dopłacasz tylko tę pozycję.</p></div></div>
        <div class="fh-flow-item"><div class="nr">3</div><div><b>Akceptacja</b><p>Masz {{ $acceptDays }} dni od zgłoszenia „gotowe”. Akceptujesz albo otwierasz spór. Brak reakcji po {{ $acceptDays }} dniach to akceptacja automatyczna.</p></div></div>
        <div class="fh-flow-item"><div class="nr">4</div><div><b>Wypłata</b><p>Mollie przekazuje kwotę firmie, pomniejszoną o prowizję Fachory ({{ $fee }}%), którą płaci firma. Ty płacisz dokładnie tyle, ile ustaliliście.</p></div></div>
      </div>
      <div>
        <div class="fh-band-card">
          <div class="cap"><span>Przykładowe zlecenie 480 zł</span><span class="pill">Protect</span></div>
          <table>
            <tr><td>Rezerwujesz</td><td class="r">480,00 zł</td></tr>
            <tr><td>Firma wykonuje pracę</td><td class="r">pieniądze czekają</td></tr>
            <tr><td>Akceptujesz</td><td class="r">do {{ $acceptDays }} dni</td></tr>
            <tr><td>Prowizja Fachory ({{ $fee }}%)</td><td class="r">płaci firma</td></tr>
            <tr class="tot"><td>Zapłaciłeś</td><td class="r">480,00 zł</td></tr>
          </table>
          <div class="ex">Przykład. Przy sporze kwota pozostaje w Mollie do rozstrzygnięcia.</div>
        </div>
        <div class="note">
          <b>Spór:</b> pieniądze nie ruszają się, dopóki Fachora nie rozstrzygnie. Możliwe wyniki: pełna wypłata, częściowa wypłata i zwrot reszty, naprawa w nowym terminie albo pełny zwrot na Twoje konto. Firma ma {{ $disputeHours }} godzin na odpowiedź; bez niej decyzja jest po Twojej stronie.
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Zakres</div>
      <h2>Co obejmuje Protect, a czego nie</h2>
      <p>Protect dotyczy tego, co jest zapisane w potwierdzeniu zlecenia. Jeśli czegoś tam nie ma, nie możemy tego rozstrzygnąć.</p>
    </div>
    <div class="fh-table-wrap">
      <table class="fh-table">
        <thead><tr><th>Sytuacja</th><th>Z Fachora Protect</th><th>Płatność po, poza Fachorą</th></tr></thead>
        <tbody>
          <tr><td>Firma nie przyjeżdża</td><td class="yes">Pełny zwrot, firma usunięta z platformy</td><td class="no">Brak zapłaty, nowe zlecenie</td></tr>
          <tr><td>Praca wykonana zgodnie z potwierdzeniem</td><td class="yes">Wypłata po Twojej akceptacji</td><td class="no">Płacisz firmie bezpośrednio</td></tr>
          <tr><td>Praca wykonana źle lub nie do końca</td><td class="yes">Spór: pieniądze zablokowane, Fachora rozstrzyga</td><td class="no">Reklamacja u firmy, bez blokady pieniędzy</td></tr>
          <tr><td>Dodatkowe prace zatwierdzone na Twojej stronie</td><td class="yes">Objęte, dopłacasz tę pozycję</td><td class="no">Ustalasz z firmą</td></tr>
          <tr><td>Dodatkowe prace bez Twojej zgody</td><td class="no">Nieobjęte: nie płacisz za nie</td><td class="no">Nieobjęte</td></tr>
          <tr><td>Materiały kupione przez Ciebie</td><td class="no">Nieobjęte: rozliczasz je ze sklepem</td><td class="no">Nieobjęte</td></tr>
          <tr><td>Ustalenia poza Fachorą (telefon, gotówka)</td><td class="no">Nieobjęte: nie ma ich w potwierdzeniu</td><td class="no">Nieobjęte</td></tr>
          <tr><td>Usterka w okresie gwarancji ({{ $warranty }} mies.)</td><td class="yes">Reklamacja wraca do zlecenia, firma odpowiada w {{ $disputeHours }} h</td><td class="yes">Gwarancja firmy nadal obowiązuje</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Terminy</div>
      <h2>Liczby, które warto znać</h2>
    </div>
    <div class="fh-times">
      <div class="fh-time"><div class="v">{{ $offerHours }} h</div><span>na oferty od firm po zgłoszeniu zlecenia</span></div>
      <div class="fh-time"><div class="v">{{ $cancelHours }} h</div><span>przed terminem: bezpłatne odwołanie przez Ciebie</span></div>
      <div class="fh-time"><div class="v">{{ $acceptDays }} dni</div><span>na akceptację po zgłoszeniu „gotowe”; potem akceptacja automatyczna</span></div>
      <div class="fh-time"><div class="v">{{ $disputeHours }} h</div><span>dla firmy na odpowiedź w sporze lub reklamacji</span></div>
      <div class="fh-time"><div class="v">{{ $warranty }} mies.</div><span>standardowa gwarancja na wykonaną pracę</span></div>
    </div>
    <div class="fh-note" style="max-width:860px;margin:28px auto 0;">
      <b>Uczciwie o tym, kto trzyma pieniądze.</b> Fachora jest usługą Creditline B.V. (Bussum, Holandia) i nie jest instytucją płatniczą. Rezerwację, przechowanie i wypłatę obsługuje Mollie B.V. — licencjonowana instytucja płatnicza nadzorowana przez De Nederlandsche Bank. Fachora widzi status płatności i decyduje, kiedy Mollie ma ją wypłacić albo zwrócić, ale nigdy nie ma Twoich pieniędzy na własnym koncie.
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Pytania o Protect</div>
      <h2>Najczęstsze pytania</h2>
    </div>
    <div class="faq-list">
      <details class="faq-item"><summary>Czy Protect kosztuje dodatkowo?{!! $chev !!}</summary><div class="faq-content">Nie. Dla klientów Fachora Protect nie kosztuje nic dodatkowego. Prowizję od wykonanego zlecenia ({{ $fee }}%) płaci firma i jest ona potrącana przy wypłacie.</div></details>
      <details class="faq-item"><summary>Kiedy pieniądze schodzą z mojego konta?{!! $chev !!}</summary><div class="faq-content">W momencie rezerwacji, czyli po wyborze firmy. BLIK i Przelewy24 nie znają „blokady” jak karty, dlatego kwota jest pobierana i trzymana w Mollie na to zlecenie. Firma nie ma do niej dostępu aż do Twojej akceptacji.</div></details>
      <details class="faq-item"><summary>Co się dzieje, gdy odwołam zlecenie?{!! $chev !!}</summary><div class="faq-content">Do {{ $cancelHours }} godzin przed terminem: pełny zwrot. Później firma może zatrzymać koszt dojazdu z oferty, reszta wraca do Ciebie. Jeśli to firma odwołuje bez powodu, dostajesz pełny zwrot, a firma wpis w historii, który bierzemy pod uwagę przy ocenie.</div></details>
      <details class="faq-item"><summary>Nie zdążyłem zaakceptować w {{ $acceptDays }} dni. Co teraz?{!! $chev !!}</summary><div class="faq-content">Zlecenie zostało uznane za przyjęte i firma dostała wypłatę. Gwarancja jednak trwa: w ciągu {{ $warranty }} miesięcy możesz zgłosić reklamację na stronie zlecenia i firma musi odpowiedzieć w {{ $disputeHours }} godzin.</div></details>
      <details class="faq-item"><summary>Ile trwa zwrot?{!! $chev !!}</summary><div class="faq-content">Zwrot realizuje Mollie na ten sam sposób płatności, którym zapłaciłeś. Zwykle pojawia się na koncie w ciągu kilku dni roboczych; przy kartach czasem zależy to od banku wydawcy.</div></details>
      <details class="faq-item"><summary>Czy mogę zapłacić bez Protect?{!! $chev !!}</summary><div class="faq-content">Tak, bezpośrednio firmie po wykonaniu pracy. Wtedy nie ma blokady pieniędzy ani rozstrzygania sporu z udziałem płatności. Weryfikacja firmy, faktura i gwarancja firmy pozostają.</div></details>
    </div>
    <div class="fh-more"><a href="{{ route('pl.faq') }}">Wszystkie pytania i odpowiedzi →</a></div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Zlecenie z Protect zaczyna się tak samo: od opisu.</h2>
    <p>Opisz, co trzeba zrobić. Protect wybierasz po otrzymaniu ofert.</p>
    <div class="fh-cta-row">
      <a href="{{ route('fachora.intake') }}" class="btn btn-primary btn-lg">Znajdź fachowca</a>
      <a href="{{ route('fachora.how') }}" class="btn btn-white btn-lg">Jak to działa</a>
    </div>
  </div>
</section>

@endsection
