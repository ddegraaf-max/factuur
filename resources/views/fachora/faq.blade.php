@extends('layouts.marketing')
{{-- Najczęstsze pytania o Fachorę: dla klientów i dla firm. Jedno źródło dla listy i schematu FAQPage.
     Klient sam wybiera firmy; firmy Verified dostają zapytanie od nas, firmie z rejestru Fachora nie wysyła niczego
     (klient kontaktuje się sam). Liczby z config/fachora.php. --}}
@php
    $protect = fachora_protect();
    $fee = config('fachora.fee_percent');
    $intro = config('fachora.intro_fee_percent');
    $introDays = config('fachora.intro_days');
    $warranty = config('fachora.default_warranty_months');
    $maxOffers = config('fachora.max_offers');
    $offerHours = config('fachora.offer_hours');
    $acceptDays = config('fachora.auto_accept_days');
    $disputeHours = config('fachora.dispute_hours');
    $cancelHours = config('fachora.cancel_free_hours');
    $candidates = config('fachora.choice_candidates');
    $keepDays = (int) config('fachora.register_keep_days');
    $langs = implode(', ', array_map(fn ($l) => mb_strtolower($l['pl']), config('fachora.languages')));

    $faqGroups = [
        'Dla klientów' => [
            ['Czy Fachora jest bezpłatna dla klientów?', 'Tak. Nie płacisz za zgłoszenie zlecenia, za wybór firm, za oferty' . ($protect ? ', za Fachora Protect' : '') . ' ani za rozstrzygnięcie sporu. Płacisz wyłącznie cenę ustaloną z firmą, taką, jaka jest w potwierdzeniu zlecenia. Opłaty Fachory ponosi firma.'],
            ['Kto wybiera firmy: ja czy Fachora?', 'Ty. Po potwierdzeniu e-mailem widzisz listę do ' . $candidates . ' firm z okolicy: najpierw Fachora Verified, potem według oceny i odległości. Firmy z rejestru przedsiębiorców są wyraźnie oznaczone. Zaznaczasz maksymalnie ' . $maxOffers . ' i wysyłasz zapytanie. Firmy nie płacą za miejsce na liście, a Fachora nie wysyła Twojego zlecenia nikomu, kogo sam nie zaznaczysz.'],
            ['Czym różni się firma Verified od firmy z rejestru?', 'Firma Fachora Verified przeszła naszą weryfikację, przejęła profil i zgodziła się na zlecenia: dostaje Twoje zapytanie od nas e-mailem, odpowiada na Twojej stronie zlecenia, obowiązuje ją Standard Fachora (cena z góry, faktura, gwarancja ' . $warranty . ' miesięcy, odpowiedź na reklamację w ' . $disputeHours . ' godzin). Firma z rejestru to wpis z CEIDG, którego jeszcze nie sprawdziliśmy: Fachora nie wysyła jej niczego. Jeśli ją zaznaczysz, pokazujemy Ci jej publiczne dane kontaktowe i gotową wiadomość po polsku, a Ty dzwonisz lub piszesz sam i umawiasz się bezpośrednio. Nasze gwarancje i rozstrzyganie sporów jej nie obejmują.'],
            ['Jak sprawdzacie firmy?', 'Zanim firma dostanie znak Fachora Verified, sprawdzamy: wpis i status w CEIDG lub KRS oraz NIP i status VAT, ' . ($protect ? 'tożsamość osoby reprezentującej i rachunek bankowy (przez Stripe), ' : '') . 'numer telefonu (kod SMS) i adres e-mail (link), a także akceptację Standardu Fachora z datą i wersją. Ubezpieczenie OC nie jest obowiązkowe, ale jeśli firma je ma, widzisz to w profilu. Kontrola trwa także później: status w rejestrze, VAT, polisa i oceny są sprawdzane cyklicznie. Szczegóły w <a href="' . route('fachora.standard') . '">Standardzie Fachora</a>.'],
            ['Czy dostanę numer telefonu firmy?', 'Zależy od firmy. Firma Verified nie podaje numeru przed wyborem: oferty, pytania i ustalenia idą przez Twoją stronę zlecenia, dzięki czemu wszystko jest zapisane i może być podstawą gwarancji albo sporu; po wyborze firma dostaje Twój adres i numer i kontaktuje się z Tobą. Przy firmie z rejestru jest odwrotnie: gdy ją zaznaczysz, widzisz jej publiczne dane kontaktowe z CEIDG (e-mail, telefon), bo to Ty się z nią kontaktujesz.'],
            ['Po co kod SMS?', 'Zanim zapytanie wyjdzie do firm, potwierdzasz numer telefonu kodem z SMS-a. Dzięki temu firmy wiedzą, że zapytanie jest prawdziwe, a Ty nie dostajesz zapytań podszywających się pod Ciebie. Numer widzi tylko firma, którą ostatecznie wybierzesz.'],
            ['Nie mówię po polsku. Czy to problem?', 'Nie. Przy zleceniu wybierasz język: ' . $langs . '. Firmy Verified dostają zapytanie tylko wtedy, gdy deklarują ten język, a w profilu widzisz poziom (ojczysty, dobry, podstawowy). Opis zlecenia tłumaczymy automatycznie na polski, więc firmę z rejestru możesz zapytać gotową wiadomością po polsku. Strona Fachory jest dostępna po polsku i angielsku.'],
            ...($protect ? [['Co to jest Fachora Protect i czy muszę z niego korzystać?', 'Fachora Protect to rezerwacja ustalonej kwoty przed przyjazdem fachowca, dostępna przy zleceniu z firmą Verified. Płacisz przez BLIK, Przelewy24 lub kartę do Stripe, a firma dostaje pieniądze dopiero po Twojej akceptacji. W sporze pieniądze pozostają zablokowane. Nie musisz z niego korzystać: możesz zapłacić firmie bezpośrednio po pracy, ale wtedy nie ma blokady pieniędzy ani sporu z udziałem płatności. Fachora nie przechowuje Twoich pieniędzy; całą płatnością zajmuje się Stripe. Więcej: <a href="' . route('fachora.protect') . '">Fachora Protect</a>.']] : []),
            ['Czy mogę odwołać zlecenie?', 'Tak. Do ' . $cancelHours . ' godzin przed terminem bezpłatnie' . ($protect ? ', z pełnym zwrotem rezerwacji. Później firma może zatrzymać koszt dojazdu z oferty, a reszta wraca do Ciebie. Jeśli to firma odwołuje bez powodu, dostajesz pełny zwrot, a firma wpis w historii, który bierzemy pod uwagę przy ocenie.' : '. Później firma może policzyć koszt dojazdu z oferty. Jeśli to firma odwołuje bez powodu, dostaje wpis w historii, który bierzemy pod uwagę przy ocenie.') . ' Dotyczy zleceń z firmą Verified; z firmą z rejestru umawiasz się bezpośrednio.'],
            ['Co się dzieje, gdy praca jest źle wykonana?', 'Przy zleceniu z firmą Verified otwierasz spór na stronie zlecenia: opis, zdjęcia, czego oczekujesz. Firma ma ' . $disputeHours . ' godzin na odpowiedź. Fachora patrzy na potwierdzenie zlecenia, zdjęcia i wiadomości i ' . ($protect ? 'rozstrzyga: pełna wypłata, częściowa wypłata, naprawa w nowym terminie albo zwrot' : 'wydaje rozstrzygnięcie: naprawa w nowym terminie albo niższa cena; firma, która go nie respektuje, traci znak Verified') . '. Jeśli firma nie odpowie w terminie, decyzja jest po Twojej stronie. Masz na zgłoszenie ' . $acceptDays . ' dni od momentu, gdy firma oznaczy zlecenie jako gotowe; po tym czasie zlecenie uznajemy za przyjęte, a gwarancja działa dalej.'],
            ['Jak działa gwarancja?', 'Każda firma Verified daje gwarancję na wykonaną pracę, standardowo ' . $warranty . ' miesięcy (w ofercie może być dłuższa). Usterkę w tym czasie zgłaszasz na stronie zlecenia; reklamacja wraca do tego samego zlecenia, a firma ma ' . $disputeHours . ' godzin na odpowiedź. Gwarancja nie obejmuje materiałów kupionych przez Ciebie ani prac, których nie było w potwierdzeniu zlecenia. Gwarancję firmy z rejestru ustalasz z nią sam.'],
            ['Czy dostanę fakturę?', 'Od firmy Verified zawsze: faktura to zasada 06 Standardu Fachora. Firma wystawia ją po Twojej akceptacji z kwoty i opisu zlecenia i wysyła na Twój e-mail. Firmy z rejestru Standard nie obowiązuje, o fakturę poproś ją przy ustalaniu pracy.'],
            ['Co, jeśli w mojej okolicy nie ma jeszcze zweryfikowanych firm?', 'Pokażemy Ci firmy z rejestru przedsiębiorców, które działają w Twojej kategorii w okolicy. Jeśli ich jeszcze nie mamy, sprawdzamy rejestr w ciągu kilku minut po Twoim potwierdzeniu i dajemy znać e-mailem, gdy lista jest gotowa. Jeśli naprawdę nikogo nie ma, powiemy Ci to wprost, zamiast obiecywać oferty, które nie przyjdą.'],
            ['Co robicie z moimi danymi?', 'Opis zlecenia, zdjęcia i preferowany język widzą tylko firmy Verified, które sam zaznaczysz (maksymalnie ' . $maxOffers . '). Firmie z rejestru Fachora nie wysyła niczego; to Ty decydujesz, co jej napiszesz. Adres i numer telefonu dostaje wyłącznie wybrana firma. Dane firm z rejestru, których nikt nie wybrał, usuwamy po ' . $keepDays . ' dniach. ' . ($protect ? 'Dane płatnicze przetwarza Stripe; Fachora nie widzi numeru Twojej karty. ' : '') . 'Administratorem danych jest Creditline B.V. (Bussum, Holandia). Szczegóły w <a href="' . route('pl.prywatnosc') . '">polityce prywatności</a>.'],
        ],
        'Dla firm' => [
            ['Ile płaci firma?', (config('fachora.lead_pricing')
                ? '0 zł abonamentu i 0 zł za rejestrację. Podgląd zapytania jest bezpłatny; płacisz tylko za to, na które chcesz odpowiedzieć: ' . config('fachora.lead_percent') . '% szacowanej wartości pracy, od ' . (int) config('fachora.lead_min') . ' do ' . (int) config('fachora.lead_max') . ' zł netto. Pierwsze zapytanie gratis. Gdy klient płaci Ci bezpośrednio, nie ma prowizji' . ($protect ? '; przy Fachora Protect ' . $fee . '% potrącane przy wypłacie' : '') . '. Dostajesz fakturę. '
                : '0 zł abonamentu, 0 zł za rejestrację, 0 zł za zlecenie. Prowizja wynosi ' . $fee . '% od wykonanej i zaakceptowanej pracy, a przez pierwsze ' . $introDays . ' dni od weryfikacji ' . $intro . '%. Jest potrącana przy wypłacie i dostajesz na nią fakturę. Zlecenie, które nie doszło do skutku, nie kosztuje nic. ')
                . 'Więcej: <a href="' . route('fachora.for-companies') . '">dla firm</a>.'],
            ['Skąd klient wie o mojej firmie i jak dostaję zapytania?', 'Klient opisuje zlecenie i sam wybiera do ' . $maxOffers . ' firm z listy: firmy Verified są na górze, potem decyduje ocena i odległość. Jeśli zaznaczy Twoją firmę Verified, dostajesz e-mail z opisem, zdjęciami, terminem i językiem klienta; podajesz cenę i termin przez link, bez logowania. Adres i numer telefonu klienta widzisz dopiero wtedy, gdy klient wybierze Twoją ofertę.'],
            ['Dlaczego mój profil jest „z rejestru” i jak go przejąć?', 'Bazę firm budujemy z rejestrów publicznych (CEIDG, KRS). Firma z rejestru widnieje jako profil z rejestru: nazwa, miejscowość i rodzaj działalności, bez znaku Verified, bez ocen i bez zapytań od nas. Przejmujesz go na trzy sposoby: kodem z listu, który wysyłamy pocztą, wyszukując profil po NIP na stronie <a href="' . route('fachora.claim') . '">przejmij profil</a>, albo <a href="' . route('fachora.apply') . '">zgłaszając firmę</a>, jeśli jej u nas nie ma. Potem potwierdzasz e-mail i telefon, przechodzisz weryfikację i dostajesz znak Verified.'],
            ['Czy Fachora wysyła firmom e-maile lub SMS-y bez pytania?', 'Nie. Firmie z rejestru nie wysyłamy niczego: ani reklam, ani newsletterów, ani zapytań. Jeśli klient wybierze taki profil, kontaktuje się z firmą sam, korzystając z jej publicznych danych z CEIDG. Wiadomości od Fachory (zapytania klientów i informacje o zleceniach) dostają tylko firmy, które przejęły profil albo same się zgłosiły; z każdej można zrezygnować linkiem w stopce. Dane firm z rejestru, których nikt nie wybrał, usuwamy po ' . $keepDays . ' dniach.'],
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
@section('description', 'Odpowiedzi na pytania o Fachorę: czy jest bezpłatna, kto wybiera firmy, czym różni się firma Verified od firmy z rejestru, jak sprawdzamy firmy, języki, ' . ($protect ? 'Fachora Protect, ' : '') . 'odwołanie zlecenia, spory, gwarancja, faktury, opłaty dla firm, przejęcie profilu, dane osobowe i kontakt.')

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
    <p>Bez konta, bezpłatnie. Sam wybierasz firmy; firmy Verified odpowiadają w ciągu {{ $offerHours }} godzin.</p>
    <div class="fh-cta-row">
      <a href="{{ route('fachora.intake') }}" class="btn btn-primary btn-lg">Znajdź fachowca</a>
      <a href="{{ route('fachora.for-companies') }}" class="btn btn-white btn-lg">Prowadzę firmę</a>
    </div>
  </div>
</section>

@endsection
