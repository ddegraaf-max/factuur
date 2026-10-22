@extends('layouts.marketing')

{{-- Standard Fachora: dziesięć zasad dla firm Fachora Verified, sankcje i przebieg weryfikacji. Liczby z config/fachora.php. --}}

@php
  $warranty = config('fachora.default_warranty_months');
  $disputeHours = config('fachora.dispute_hours');
  $minRating = config('fachora.min_rating');
  $version = config('fachora.terms_version');
@endphp

@section('title', 'Standard Fachora — dziesięć zasad, których przestrzega każda firma Fachora Verified')
@section('description', 'Legalna i aktywna firma, zweryfikowana tożsamość, jasne ceny z góry, bez prac dodatkowych bez zgody, potwierdzony termin, faktura zawsze, gwarancja ' . $warranty . ' miesięcy, odpowiedź na reklamację w ' . $disputeHours . ' h, płatność przez Fachora Protect, ocena minimum ' . str_replace('.', ',', (string) $minRating) . '. Sankcje i przebieg weryfikacji.')

@push('styles')
@include('fachora.partials.styles')
@endpush

@section('content')

<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Standard Fachora</div>
    <h1>Dziesięć zasad, których przestrzega każda firma Fachora Verified.</h1>
    <p class="lead">Nie każda firma trafia na Fachorę. Ta, która trafia, podpisuje się pod tymi zasadami z datą i wersją. Łamanie ich ma konsekwencje, od zawieszenia po usunięcie.</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="fh-rules">
      <div class="fh-rule"><span class="no">01</span><div><b>Legalnie zarejestrowana, aktywna firma</b><p>Wpis w CEIDG lub KRS, poprawny NIP, status aktywny. Sprawdzamy to przy weryfikacji i potem cyklicznie.</p></div></div>
      <div class="fh-rule"><span class="no">02</span><div><b>Zweryfikowana tożsamość osoby reprezentującej</b><p>Tożsamość i rachunek bankowy potwierdzone przez Mollie, operatora płatności. Fachora nie przechowuje dokumentów tożsamości.</p></div></div>
      <div class="fh-rule"><span class="no">03</span><div><b>Jasne ceny z góry</b><p>Oferta zawiera cenę stałą albo stawkę godzinową z kosztem dojazdu. Cena z potwierdzenia zlecenia jest wiążąca.</p></div></div>
      <div class="fh-rule"><span class="no">04</span><div><b>Żadnych prac dodatkowych bez zgody klienta</b><p>Dodatkowa praca to osobna pozycja, którą klient zatwierdza na swojej stronie. Bez zatwierdzenia nie ma rachunku.</p></div></div>
      <div class="fh-rule"><span class="no">05</span><div><b>Potwierdzony termin, odwołanie tylko z powodu</b><p>Firma potwierdza termin i go dotrzymuje. Odwołanie wymaga podania powodu; no-show po rezerwacji to usunięcie z platformy.</p></div></div>
      <div class="fh-rule"><span class="no">06</span><div><b>Faktura lub dowód wykonania usługi, zawsze</b><p>Po każdym zleceniu klient dostaje fakturę. Firma ma do tego darmowy program gotowy na KSeF.</p></div></div>
      <div class="fh-rule"><span class="no">07</span><div><b>Gwarancja na pracę tam, gdzie ma zastosowanie</b><p>Standardowo {{ $warranty }} miesięcy. Firma może dać dłuższą; krótszą tylko tam, gdzie z natury usługi gwarancja nie ma sensu, i musi to być w ofercie.</p></div></div>
      <div class="fh-rule"><span class="no">08</span><div><b>Odpowiedź na reklamację w {{ $disputeHours }} godzin</b><p>Spór lub reklamacja trafia do firmy przez Fachorę. Brak odpowiedzi w terminie oznacza rozstrzygnięcie na korzyść klienta.</p></div></div>
      <div class="fh-rule"><span class="no">09</span><div><b>Płatność według zasad Fachory (Protect)</b><p>Zlecenie, które przyszło przez Fachorę, jest rozliczane przez Fachorę. Namawianie klienta na gotówkę „obok” to usunięcie z platformy.</p></div></div>
      <div class="fh-rule"><span class="no">10</span><div><b>Ocena minimum {{ str_replace('.', ',', (string) $minRating) }} i reakcja w 24 godziny</b><p>Średnia z opinii po zleceniach (liczona od pięciu opinii) nie spada poniżej {{ str_replace('.', ',', (string) $minRating) }}, a na nowe zlecenia firma odpowiada w ciągu doby.</p></div></div>
    </div>
    <p style="text-align:center;font-size:13px;color:var(--text-3);margin-top:18px;">Wersja Standardu: {{ $version }}. Firma akceptuje go w panelu, z zapisem daty i wersji.</p>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="fh-split">
      <div>
        <div class="eyebrow">Sankcje</div>
        <h2>Co się dzieje, gdy firma łamie zasady</h2>
        <p><b>Zawieszenie na 30 dni:</b> dwie uzasadnione reklamacje w ciągu 90 dni, wygasłe ubezpieczenie podane w profilu, utrata statusu aktywnego w rejestrze. Firma znika z wyników, a po 30 dniach oceniamy ją ponownie. Spadek oceny poniżej {{ str_replace('.', ',', (string) $minRating) }} to najpierw sygnał i rozmowa; bez poprawy także zawieszenie.</p>
        <p><b>Usunięcie z platformy:</b> oszustwo, no-show po rezerwacji klienta, rozliczanie zlecenia z Fachory poza Fachorą. NIP takiej firmy jest blokowany dla nowych zgłoszeń.</p>
        <p>Każda decyzja jest uzasadniona na piśmie i firma może się do niej odnieść. Rozstrzyga Fachora, nie algorytm.</p>
      </div>
      <div class="fh-note">
        <b>Co to znaczy dla klienta?</b><br>
        Znak Fachora Verified to nie deklaracja firmy, tylko wynik kontroli, która trwa także po przyznaniu znaku: status w rejestrze, status VAT, ubezpieczenie i oceny sprawdzamy cyklicznie. Firma, która przestaje spełniać Standard, przestaje dostawać zlecenia.
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Weryfikacja</div>
      <h2>Jak firma zostaje Fachora Verified</h2>
      <p>Większość kroków jest automatyczna. Ostatni wykonuje człowiek.</p>
    </div>
    <div class="fh-steps cols-3">
      <div class="fh-step"><div class="nr">1</div><h3>Przejęcie profilu</h3><p>Firma przejmuje profil z bazy (kod z listu, wyszukanie profilu albo zgłoszenie) i potwierdza e-mail oraz numer telefonu kodem SMS.</p></div>
      <div class="fh-step"><div class="nr">2</div><h3>Rejestry</h3><p>Automatyczna kontrola w CEIDG lub KRS i w Wykazie podatników VAT: firma aktywna, NIP poprawny, profil działalności pasuje do kategorii.</p></div>
      <div class="fh-step"><div class="nr">3</div><h3>Tożsamość i konto</h3><p>Onboarding w Mollie: tożsamość osoby reprezentującej i rachunek bankowy na firmę. Fachora dostaje tylko wynik, nie dokumenty.</p></div>
      <div class="fh-step"><div class="nr">4</div><h3>Standard i regulamin</h3><p>Firma akceptuje Standard Fachora i regulamin, uzupełnia języki, zasięg, stawki orientacyjne i gwarancję. Polisę OC może dodać do profilu.</p></div>
      <div class="fh-step"><div class="nr">5</div><h3>Ocena przez Fachorę</h3><p>Pracownik Fachory sprawdza całość i przyznaje znak Verified. Dopiero wtedy firma widzi zlecenia i pojawia się w wynikach.</p></div>
      <div class="fh-step"><div class="nr">6</div><h3>Kontrola ciągła</h3><p>Status w rejestrze, status VAT, ważność polisy i średnia ocen są sprawdzane cyklicznie. Firma dostaje przypomnienie, zanim coś wygaśnie.</p></div>
    </div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Prowadzisz firmę, która tak pracuje?</h2>
    <p>{{ config('fachora.lead_pricing') ? '0 zł abonamentu, płacisz tylko za zapytania, na które odpowiadasz; pierwsze gratis.' : '0 zł abonamentu, prowizja tylko od wykonanych zleceń.' }} Nie wysyłamy firmom reklam ani newsletterów, dlatego pierwszy krok należy do Ciebie.</p>
    <div class="fh-cta-row">
      <a href="{{ route('fachora.for-companies') }}" class="btn btn-primary btn-lg">Oferta dla firm</a>
      <a href="{{ route('fachora.claim') }}" class="btn btn-white btn-lg">Przejmij profil firmy</a>
    </div>
  </div>
</section>

@endsection
