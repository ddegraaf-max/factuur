@extends('layouts.marketing')

{{-- Najczęstsze pytania o Fachorę: dla klientów i dla firm. Jedno źródło dla listy i schematu FAQPage. Liczby z config/fachora.php. --}}

@php
  $fee = config('fachora.fee_percent');
  $intro = config('fachora.intro_fee_percent');
  $introDays = config('fachora.intro_days');
  $warranty = config('fachora.default_warranty_months');
  $maxOffers = config('fachora.max_offers');
  $offerHours = config('fachora.offer_hours');
  $acceptDays = config('fachora.auto_accept_days');
  $disputeHours = config('fachora.dispute_hours');
  $cancelHours = config('fachora.cancel_free_hours');
  $langs = implode(', ', array_map(fn ($l) => mb_strtolower($l['pl']), config('fachora.languages')));

  $faqGroups = [
    'Dla klientów' => [
      ['Czy Fachora jest bezpłatna dla klientów?', 'Tak. Nie płacisz za zgłoszenie zlecenia, za oferty, za Fachora Protect ani za rozstrzygnięcie sporu. Płacisz wyłącznie cenę ustaloną z firmą, taką, jaka jest w potwierdzeniu zlecenia. Prowizję od wykonanego zlecenia ponosi firma.'],
      ['Jak sprawdzacie firmy?', 'Zanim firma dostanie znak Fachora Verified, sprawdzamy: wpis i status w CEIDG lub KRS oraz NIP i status VAT, tożsamość osoby reprezentującej i rachunek bankowy (przez Stripe), numer telefonu (kod SMS) i adres e-mail (link), a także akceptację Standardu Fachora z datą i wersją. Ubezpieczenie OC nie jest obowiązkowe, ale jeśli firma je ma, widzisz to w profilu. Kontrola trwa także później: status w rejestrze, VAT, polisa i oceny są sprawdzane cyklicznie. Szczegóły: <a href="' . route('fachora.standard') . '">Standard Fachora</a>.'],
      ['Czy dostanę numer telefonu firmy?', 'Nie przed wyborem. Oferty, pytania i ustalenia idą przez Twoją stronę zlecenia, dzięki czemu wszystko jest zapisane i może być podstawą gwarancji albo sporu. Po wyborze firma dostaje Twój adres i dane kontaktowe, żeby uzgodnić szczegóły wizyty.'],
      ['Nie mówię po polsku. Czy to problem?', 'Nie. Przy zleceniu wybierasz język: ' . $langs . '. Zlecenie trafia tylko do firm, które deklarują ten język, a w profilu widzisz poziom (ojczysty, dobry, podstawowy). Strona Fachory jest dostępna po polsku i angielsku.'],
      ['Co to jest Fachora Protect i czy muszę z niego korzystać?', 'Fachora Protect to rezerwacja ustalonej kwoty przed przyjazdem fachowca. Płacisz przez BLIK, Przelewy24 lub kartę do Stripe, a firma dostaje pieniądze dopiero po Twojej akceptacji. W sporze pieniądze pozostają zablokowane. Nie musisz z niego korzystać: możesz zapłacić firmie bezpośrednio po pracy, ale wtedy nie ma blokady pieniędzy ani sporu z udziałem płatności. Fachora nie przechowuje Twoich pieniędzy; całą płatnością zajmuje się Stripe. Więcej: <a href="' . route('fachora.protect') . '">Fachora Protect</a>.'],
      ['Czy mogę odwołać zlecenie?', 'Tak. Do ' . $cancelHours . ' godzin przed terminem bezpłatnie, z pełnym zwrotem rezerwacji. Później firma może zatrzymać koszt dojazdu z oferty, a reszta wraca do Ciebie. Jeśli to firma odwołuje bez powodu, dostajesz pełny zwrot, a firma wpis w historii, który bierzemy pod uwagę przy ocenie.'],
      ['Co się dzieje, gdy praca jest źle wykonana?', 'Na stronie zlecenia otwierasz spór: opis, zdjęcia, czego oczekujesz. Firma ma ' . $disputeHours . ' godzin na odpowiedź. Fachora patrzy na potwierdzenie zlecenia, zdjęcia i wiadomości i rozstrzyga: pełna wypłata, częściowa wypłata, naprawa w nowym terminie albo zwrot. Jeśli firma nie odpowie w terminie, decyzja jest po Twojej stronie. Masz na zgłoszenie ' . $acceptDays . ' dni od momentu, gdy firma oznaczy zlecenie jako gotowe; po tym czasie zlecenie uznajemy za przyjęte, ale gwarancja nadal obowiązuje.'],
      ['Jak działa gwarancja?', 'Każda firma Verified daje gwarancję na wykonaną pracę, standardowo ' . $warranty . ' miesięcy (w ofercie może być dłuższa). Usterkę w tym czasie zgłaszasz na stronie zlecenia; reklamacja wraca do tego samego zlecenia, a firma ma ' . $disputeHours . ' godzin na odpowiedź. Gwarancja nie obejmuje materiałów kupionych przez Ciebie ani prac, których nie było w potwierdzeniu zlecenia.'],
      ['Czy dostanę fakturę?', 'Zawsze. Faktura to zasada 06 Standardu Fachora. Firma wystawia ją po Twojej akceptacji z kwoty i opisu zlecenia, w programie do faktur, który dostaje od nas, i wysyła ją na Twój e-mail.'],
      ['Co, jeśli w mojej okolicy nie ma jeszcze zweryfikowanych firm?', 'Powiemy Ci to wprost, zamiast obiecywać oferty, które nie przyjdą. Zlecenia trafiają wyłącznie do firm Fachora Verified. Firmy z rejestrów publicznych, których jeszcze nie sprawdziliśmy, widnieją jako profil nieaktywny i nie dostają zleceń; nie wysyłamy im też żadnych wiadomości. Rozwijamy bazę region po regionie, więc warto sprawdzić ponownie za jakiś czas.'],
      ['Co robicie z moimi danymi?', 'Opis zlecenia, zdjęcia i preferowany język widzą tylko firmy, którym zlecenie zostało zaoferowane (maksymalnie ' . $maxOffers . '). Adres i dane kontaktowe dostaje wyłącznie wybrana firma. Dane płatnicze przetwarza Stripe; Fachora nie widzi numeru Twojej karty. Administratorem danych jest Creditline B.V. (Bussum, Holandia). Szczegóły w <a href="' . route('pl.prywatnosc') . '">polityce prywatności</a>.'],
    ],
    'Dla firm' => [
      ['Ile płaci firma?', '0 zł abonamentu, 0 zł za rejestrację, 0 zł za zlecenie. Prowizja wynosi ' . $fee . '% od wykonanej i zaakceptowanej pracy, a przez pierwsze ' . $introDays . ' dni od weryfikacji ' . $intro . '%. Jest potrącana przy wypłacie i dostajesz na nią fakturę. Zlecenie, które nie doszło do skutku, nie kosztuje nic. Program do faktur gotowy na KSeF jest bezpłatny, dopóki firma ma status Verified. Więcej: <a href="' . route('fachora.for-companies') . '">dla firm</a>.'],
      ['Dlaczego mój profil jest „nieaktywny” i jak go przejąć?', 'Bazę firm budujemy z rejestrów publicznych (CEIDG, KRS). Firma z rejestru widnieje jako profil nieaktywny: bez telefonu, bez zleceń i bez znaku Verified. Przejmujesz go na trzy sposoby: kodem z listu, który wysyłamy pocztą, wyszukując profil po NIP na stronie <a href="' . route('fachora.claim') . '">przejmij profil</a>, albo <a href="' . route('fachora.apply') . '">zgłaszając firmę</a>, jeśli jej u nas nie ma. Potem potwierdzasz e-mail i telefon, przechodzisz onboarding w Stripe i akceptujesz Standard.'],
      ['Czy Fachora wysyła firmom e-maile lub SMS-y bez pytania?', 'Nie. Nigdy nie wysyłamy wiadomości elektronicznych do firm, które nie przejęły profilu i nie wyraziły zgody. Firmom z rejestrów możemy wysłać wyłącznie list pocztą. Dlatego przejęty i zweryfikowany profil to jedyna droga, którą zlecenia mogą do Ciebie trafić.'],
      ['Co się dzieje, gdy firma łamie Standard?', 'Dwie uzasadnione reklamacje w 90 dni, wygasła polisa podana w profilu albo utrata statusu aktywnego w rejestrze oznaczają zawieszenie na 30 dni, a potem ponowną ocenę. Oszustwo, no-show po rezerwacji klienta albo rozliczanie zlecenia z Fachory poza Fachorą to usunięcie z platformy. Każda decyzja jest uzasadniona na piśmie i można się do niej odnieść.'],
    ],
    'Kontakt' => [
      ['Jak się z wami skontaktować?', 'Napisz na <a href="mailto:' . brand('email') . '">' . brand('email') . '</a> albo skorzystaj ze <a href="' . route('pl.kontakt') . '">strony kontaktowej</a>. Przy sprawie dotyczącej zlecenia najszybciej jest użyć przycisków na stronie zlecenia, bo wtedy od razu widzimy, o co chodzi. Fachora jest usługą Creditline B.V., Torenlaan 5B, 1402 AT Bussum, Holandia.'],
    ],
  ];
  $chev = '<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>';
  $ld = [];
  foreach ($faqGroups as $items) foreach ($items as [$q, $a]) {
    $ld[] = ['@type' => 'Question', 'name' => $q, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($a)]];
  }
@endphp

@section('title', 'Najczęstsze pytania — Fachora')
@section('description', 'Odpowiedzi na pytania o Fachorę: czy jest bezpłatna, jak sprawdzamy firmy, języki, Fachora Protect, odwołanie zlecenia, spory, gwarancja, faktury, prowizja dla firm, przejęcie profilu, dane osobowe i kontakt.')

@push('styles')
@include('fachora.partials.styles')
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $ld], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')

<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Pomoc</div>
    <h1>Najczęstsze pytania</h1>
    <p class="lead">Dla klientów i dla firm. Jeśli nie znajdziesz odpowiedzi, napisz do nas: odpowiadamy po polsku i angielsku.</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    @foreach($faqGroups as $group => $items)
    <div class="fh-faq-group">{{ $group }}</div>
    <div class="faq-list">
      @foreach($items as [$q, $a])
      <details class="faq-item">
        <summary>{{ $q }}{!! $chev !!}</summary>
        <div class="faq-content">{!! $a !!}</div>
      </details>
      @endforeach
    </div>
    @endforeach
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Masz zlecenie? Opisz je.</h2>
    <p>Bez konta, bezpłatnie. Oferty od zweryfikowanych firm w ciągu {{ $offerHours }} godzin.</p>
    <div class="fh-cta-row">
      <a href="{{ route('fachora.intake') }}" class="btn btn-primary btn-lg">Znajdź fachowca</a>
      <a href="{{ route('fachora.for-companies') }}" class="btn btn-white btn-lg">Prowadzę firmę</a>
    </div>
  </div>
</section>

@endsection
