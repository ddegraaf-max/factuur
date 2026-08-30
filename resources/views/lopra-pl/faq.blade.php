@extends('layouts.marketing')

{{-- Najczęstsze pytania — Lopra Polska (APP_BRAND=lopra_pl). Treść spójna ze stroną główną: KSeF, biała lista, VAT, import, marka, windykacja ze sprzedamfakture.pl, cennik 49/79 zł netto. --}}

@section('title', 'Najczęstsze pytania — ' . brand('name'))
@section('description', 'Odpowiedzi na najczęstsze pytania o ' . brand('name') . ': KSeF, NIP i biała lista, stawki VAT, import z Fakturowni, iFirmy, wFirmy i inFaktu, marka i strona www, windykacja ze sprzedamfakture.pl, cennik i bezpieczeństwo danych.')

@php
  // Jedno źródło dla widocznej listy pytań i schematu FAQPage (rich results):
  // pytanie + odpowiedź (odpowiedź może zawierać prostą składnię HTML, np. <b>).
  $faqGroups = [
    'Start & konto' => [
      ['Czym jest ' . brand('name') . ' i dla kogo?', brand('name') . ' to jedno narzędzie dla polskich firm: faktury gotowe do KSeF, oferty, rozliczenie VAT, identyfikacja wizualna, wizytówka cyfrowa i strona www — oraz windykacja należności ze sprzedamfakture.pl, gdy klient nie płaci. Sprawdzi się u jednoosobowej działalności, spółki z o.o. i małej firmy z kilkoma osobami. Nie potrzebujesz wiedzy księgowej: wszystko jest opisane po polsku, bez żargonu.'],
      ['Co potrzebuję, żeby zacząć?', 'Tylko NIP i adres e-mail. Po podaniu NIP-u ' . brand('name') . ' pobierze nazwę firmy, adres i REGON z Wykazu podatników VAT (białej listy), ustawi numerację faktur i domyślną stawkę VAT. Pierwszą fakturę wystawisz w kilka minut, a markę, wizytówkę i stronę www zrobisz w kwadrans.'],
      ['Czy mogę wypróbować ' . brand('name') . ' za darmo?', 'Tak. Pierwsze 14 dni jest bezpłatne, ze wszystkimi funkcjami — także tymi z pakietu Smart (AI). Nie podajesz karty ani danych do płatności. Po okresie próbnym wybierasz abonament albo po prostu przestajesz korzystać; Twoje dane pozostają do pobrania.'],
      ['Czy mogę zaprosić współpracowników?', 'Tak, dodatkowi użytkownicy nie kosztują nic. Każdej osobie nadajesz rolę: administrator (wszystko), pracownik (codzienna praca bez ustawień i raportów) albo księgowa (tylko odczyt). Możesz też prowadzić kilka firm lub marek na jednym koncie.'],
    ],
    'Faktury & KSeF' => [
      ['Czy ' . brand('name') . ' obsługuje KSeF?', 'Tak. Każda faktura wystawiona w ' . brand('name') . ' ma plik XML w strukturze FA zgodnej z Krajowym Systemem e-Faktur. Pobierasz go jednym kliknięciem, wysyłasz do KSeF, a nadany numer KSeF zapisujesz przy fakturze. <b>Bezpośrednią wysyłkę z poziomu aplikacji</b> (autoryzacja tokenem) wdrażamy w kolejnym kroku — po przetestowaniu na środowisku testowym Ministerstwa Finansów.'],
      ['Czy faktury spełniają wymogi polskich przepisów?', 'Tak. Faktury mają ciągłą numerację w wybranym przez Ciebie formacie (np. FV/2026/0001), wszystkie obowiązkowe elementy z ustawy o VAT, w tym NIP obu stron, daty wystawienia i sprzedaży, stawki i kwoty VAT per stawka, oraz adnotacje takie jak „metoda kasowa” czy podstawa zwolnienia z VAT. Faktury korygujące i zaliczkowe też są obsługiwane.'],
      ['Czy ' . brand('name') . ' sprawdza NIP kontrahenta?', 'Tak. Przy dodawaniu klienta ' . brand('name') . ' weryfikuje NIP w Wykazie podatników VAT (białej liście): sprawdza sumę kontrolną, status VAT i pobiera nazwę oraz adres. Dzięki temu unikasz literówek w danych i wiesz, czy kontrahent jest czynnym podatnikiem VAT.'],
      ['Mam faktury w Fakturowni, iFirmie, wFirmie lub inFakcie. Jak się przenieść?', 'Wyeksportuj kontrahentów, produkty i nieopłacone faktury do CSV lub XLSX i wgraj pliki w kreatorze przenosin. ' . brand('name') . ' rozpoznaje polskie nazwy kolumn (NIP, nazwa, adres, termin płatności, netto, brutto…) i pomija duplikaty. Historię opłaconych faktur zostaw w starym programie. Instrukcje krok po kroku znajdziesz na stronach <a href="' . route('pl.przenies', 'fakturownia') . '">Przenieś się z Fakturowni</a>, <a href="' . route('pl.przenies', 'ifirma') . '">z iFirmy</a>, <a href="' . route('pl.przenies', 'wfirma') . '">z wFirmy</a> i <a href="' . route('pl.przenies', 'infakt') . '">z inFaktu</a>.'],
    ],
    'VAT & księgowa' => [
      ['Jakie stawki VAT obsługuje ' . brand('name') . '?', 'Wszystkie stawki obowiązujące w Polsce: 23%, 8%, 5% i 0%, a także pozycje zwolnione z VAT („zw”) i odwrotne obciążenie. Stawkę wybierasz per pozycja faktury, a VAT należny i naliczony są sumowane per stawka — w układzie, w jakim potrzebuje ich JPK_V7.'],
      ['Jestem zwolniony z VAT. Czy ' . brand('name') . ' jest dla mnie?', 'Tak. Zaznacz w ustawieniach zwolnienie podmiotowe (art. 113 ustawy o VAT) lub przedmiotowe — na fakturach pojawi się właściwa adnotacja i podstawa prawna, a kwoty będą wystawiane bez VAT. Gdy przekroczysz limit i staniesz się podatnikiem VAT, przełączasz jedno ustawienie.'],
      ['Czy moja księgowa może mieć dostęp?', 'Tak, bezpłatnie. Zaproś księgową z rolą „tylko odczyt”: widzi wszystkie faktury, koszty i rozliczenie VAT, może pobrać eksport CSV i zestawienia, ale niczego nie zmieni. Co miesiąc lub kwartał rozliczenie VAT per stawka czeka gotowe — z przypomnieniem przed 25. dniem miesiąca.'],
    ],
    'Marka & strona www' => [
      ['Nie mam logo ani kolorów. Czy ' . brand('name') . ' mi w tym pomoże?', 'Tak. W pakiecie Smart odpowiadasz na cztery pytania (czym się zajmujesz, dla kogo, jaki styl, jakie kolory), a AI proponuje trzy identyfikacje wizualne: logo, paletę kolorów, czcionkę, szablon faktury i slogan. Jedno kliknięcie ustawia wszystko na fakturach, wizytówce i stronie www. Masz już logo? Wgraj je — resztę dopasujemy.'],
      ['Czym jest wizytówka cyfrowa i strona www w ' . brand('name') . '?', 'Wizytówka cyfrowa to publiczna strona z Twoimi danymi kontaktowymi, przyciskami „zadzwoń”, „napisz” i „WhatsApp”, kodem QR i zapisem do kontaktów (vCard). Strona www to kompletna strona-wizytówka Twojej firmy w Twojej identyfikacji: usługi, dlaczego Ty, o nas i formularz kontaktowy. Wiadomości z formularza trafiają do ' . brand('name') . ' jako zapytania (leady). Obie są w cenie pakietu Podstawowego; tekst strony napisze za Ciebie AI w pakiecie Smart.'],
    ],
    'Windykacja ze sprzedamfakture.pl' => [
      ['Co się dzieje, gdy klient nie płaci?', 'Najpierw ' . brand('name') . ' wysyła w Twoim imieniu przypomnienia i ponaglenia (e-mail, SMS) z linkiem do zapłaty BLIK-iem lub Przelewy24. Jeśli to nie działa, jednym kliknięciem tworzysz formalne wezwanie do zapłaty (PDF) z odsetkami ustawowymi za opóźnienie i rekompensatą 40/70/100 EUR. Ostatni krok to przekazanie sprawy do sprzedamfakture.pl — z kompletnym dossier z Twojej faktury, bez przepisywania danych.'],
      ['Ile kosztuje windykacja ze sprzedamfakture.pl?', 'Przypomnienia, ponaglenia i wezwanie do zapłaty są w cenie abonamentu. Przekazanie sprawy do sprzedamfakture.pl jest <b>wyceniane przed zleceniem</b> — bez opłat wstępnych; koszty windykacji i odsetki co do zasady obciążają dłużnika na podstawie ustawy o przeciwdziałaniu nadmiernym opóźnieniom w transakcjach handlowych. Zawsze wiesz, na co się decydujesz, zanim zlecisz sprawę.'],
      ['Czy mogę sprzedać niezapłaconą fakturę?', 'Tak. Jeśli potrzebujesz gotówki teraz, zgłoś wierzytelność do wykupu prosto z faktury. sprzedamfakture.pl przygotuje ofertę cesji w <b>jeden dzień roboczy</b>. Decyzja zawsze należy do Ciebie.'],
      ['Jak policzyć odsetki i rekompensatę za opóźnienie?', 'Skorzystaj z bezpłatnego <a href="' . route('pl.kalkulator') . '">kalkulatora odsetek i rekompensaty</a>: podajesz kwotę faktury i termin płatności, a kalkulator liczy dni opóźnienia, odsetki ustawowe za opóźnienie w transakcjach handlowych i rekompensatę z art. 10 ustawy. Możesz od razu wygenerować wezwanie do zapłaty.'],
    ],
    'Cennik & bezpieczeństwo' => [
      ['Ile kosztuje ' . brand('name') . ' po okresie próbnym?', '<b>Podstawowy</b>: 49 zł netto (60,27 zł brutto) miesięcznie — faktury bez limitu, KSeF, VAT, wizytówka, strona www i windykacja krok 1–2. <b>Smart</b>: 79 zł netto (97,17 zł brutto) miesięcznie — wszystko z Podstawowego plus identyfikacja wizualna z AI, tekst strony www z AI, koszty ze zdjęcia, oferta z tekstu i priorytetowa obsługa spraw windykacyjnych. Bez ukrytych opłat i limitów klientów.'],
      ['Czy wiąże mnie umowa na czas określony?', 'Nie. Abonament jest miesięczny i rezygnujesz w każdej chwili, ze skutkiem na koniec bieżącego okresu rozliczeniowego. Co miesiąc dostajesz fakturę VAT za abonament. Płacisz BLIK-iem, kartą lub przelewem.'],
      ['Gdzie są przechowywane moje dane i czy są bezpieczne?', 'Dane są przechowywane na serwerach w Unii Europejskiej (Amsterdam), codziennie archiwizowane, a połączenie jest szyfrowane (TLS). Możesz włączyć logowanie dwuskładnikowe (2FA). Przetwarzamy dane zgodnie z RODO — szczegóły znajdziesz w <a href="' . route('pl.prywatnosc') . '">polityce prywatności</a>.'],
      ['Czy mogę zabrać swoje dane, gdy odejdę?', 'Tak, w każdej chwili. Eksportujesz całą firmę do CSV (klienci, produkty, faktury, koszty), a pliki FA-XML i PDF faktur pobierasz osobno. Możesz też usunąć konto — Twoje dane należą do Ciebie.'],
    ],
  ];

  $faqLd = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => collect($faqGroups)->flatten(1)->map(fn ($item) => [
      '@type' => 'Question',
      'name' => $item[0],
      'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($item[1])],
    ])->values()->all(),
  ];
@endphp

@section('content')
<script type="application/ld+json">{!! json_encode($faqLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Najczęstsze pytania</div>
    <h1>Pytania i odpowiedzi</h1>
    <p class="lead">Wszystko, o co najczęściej pytają firmy przed startem z {{ brand('name') }}. Nie ma Twojego pytania? <a href="{{ route('pl.kontakt') }}" style="color:var(--brand);font-weight:600;">Napisz do nas</a> — odpowiadamy w ciągu jednego dnia roboczego.</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="faq-list">
      @foreach($faqGroups as $groupTitle => $items)
        <h2 style="font-size:14px;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-4);margin:{{ $loop->first ? '8px' : '32px' }} 0 14px;">{{ $groupTitle }}</h2>
        @foreach($items as [$question, $answer])
          <details class="faq-item">
            <summary>{{ $question }} <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
            <div class="faq-content">{!! $answer !!}</div>
          </details>
        @endforeach
      @endforeach
    </div>

    <div style="text-align:center;margin-top:40px;">
      <p style="color:var(--text-2);margin-bottom:16px;">Nie znalazłeś odpowiedzi?</p>
      <a href="{{ route('pl.kontakt') }}" class="btn btn-primary">Skontaktuj się z nami</a>
      <a href="{{ route('register') }}" class="btn btn-secondary" style="margin-left:8px;">Wypróbuj 14 dni za darmo</a>
    </div>
  </div>
</section>
@endsection
