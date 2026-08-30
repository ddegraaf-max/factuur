@extends('layouts.marketing')

{{-- „Przenieś się z {pakiet}” — Lopra Polska. Dane pakietu ($pkg: name, intro, export[], notes) i lista pakietów ($packages) pochodzą z config/przenies.php; $slug to bieżący pakiet. --}}

@section('title', 'Przenieś się z ' . $pkg['name'] . ' do ' . brand('name') . ' — w kwadrans')
@section('description', 'Przenieś klientów, produkty i nieopłacone faktury z ' . $pkg['name'] . ' do ' . brand('name') . ': eksport CSV lub XLSX, import z automatycznym rozpoznaniem kolumn (NIP, nazwa, termin płatności, brutto), bez duplikatów. Faktury gotowe do KSeF, marka, strona www i windykacja ze sprzedamfakture.pl.')

@section('content')
<style>
  .pz-steps{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;margin-top:28px;}
  .pz-step{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:22px;box-shadow:var(--shadow-sm);}
  .pz-step .nr{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:100px;background:var(--brand);color:#fff;font-weight:700;font-size:14px;margin-bottom:12px;}
  .pz-step h3{font-size:17px;margin:0 0 8px;}
  .pz-step p{color:var(--text-2);line-height:1.65;margin:0 0 8px;font-size:14.5px;}
  .pz-step ol{color:var(--text-2);line-height:1.6;margin:0;padding-left:18px;font-size:14px;}
  .pz-step li{margin-bottom:8px;}
  .pz-step a{color:var(--brand);font-weight:500;}
  .pz-table-wrap{overflow-x:auto;margin-top:22px;}
  .pz-table{width:100%;border-collapse:collapse;font-size:14.5px;background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;min-width:560px;}
  .pz-table th,.pz-table td{padding:13px 16px;border-bottom:1px solid var(--border);text-align:left;vertical-align:top;color:var(--text-2);line-height:1.55;}
  .pz-table th{background:var(--surface-2);color:var(--text);font-weight:600;}
  .pz-table td:first-child{color:var(--text);font-weight:500;width:24%;}
  .pz-table tr:last-child td{border-bottom:none;}
  .pz-yes{color:var(--success);font-weight:600;}
  .pz-note{font-size:13px;color:var(--text-3);margin-top:12px;line-height:1.6;}
  .pz-cols{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px;}
  .pz-faq{max-width:760px;}
  .pz-faq h3{font-size:16px;margin:22px 0 6px;}
  .pz-faq p{color:var(--text-2);line-height:1.7;margin:0;}
  .pz-others{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:24px;}
  .pz-others a{display:flex;align-items:center;gap:12px;background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:16px 18px;font-weight:600;color:var(--text);transition:border-color .15s,transform .15s;}
  .pz-others a:hover{border-color:var(--brand);transform:translateY(-2px);}
  .pz-others small{display:block;font-weight:400;color:var(--text-3);font-size:12px;}
  @media (max-width:820px){.pz-steps{grid-template-columns:minmax(0,1fr);}.pz-others{grid-template-columns:1fr;}}
</style>

<section class="page-hero">
  <div class="container page-hero-inner">
    <span class="eyebrow">Przenieś się z {{ $pkg['name'] }}</span>
    <h1>Z {{ $pkg['name'] }} do {{ brand('name') }} w kwadrans</h1>
    <p class="lead">{{ $pkg['intro'] }} Klientów, produkty i nieopłacone faktury przenosisz sam w kreatorze przenosin — albo wysyłasz nam eksport i robimy to za Ciebie.</p>
    <div class="hero-ctas" style="margin-top:28px;">
      <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Wypróbuj 14 dni za darmo →</a>
      <a href="{{ route('demo') }}" class="btn btn-secondary btn-lg">Zobacz demo</a>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <h2>Trzy kroki, zero przepisywania</h2>
      <p>Stary program możesz zostawić włączony, dopóki wszystko nie stanie na miejscu. Nic nie tracisz.</p>
    </div>
    <div class="pz-steps">
      <div class="pz-step">
        <div class="nr">1</div>
        <h3>Eksport w {{ $pkg['name'] }}</h3>
        <p>Zapisz trzy pliki CSV lub XLSX:</p>
        <ol>
          @foreach($pkg['export'] as $step)
            <li>{{ $step }}</li>
          @endforeach
        </ol>
      </div>
      <div class="pz-step">
        <div class="nr">2</div>
        <h3>Import w {{ brand('name') }}</h3>
        <p>Po zalogowaniu wejdź w <strong>Ustawienia → Przenosiny</strong> i wgraj pliki. {{ brand('name') }} rozpozna kolumny automatycznie, pokaże podgląd i pominie duplikaty. Jedno kliknięcie i dane są na miejscu.</p>
        <p>Kreator przenosin jest dostępny w aplikacji od pierwszego dnia okresu próbnego — <a href="{{ route('register') }}">załóż bezpłatne konto</a>, a link do niego znajdziesz w ustawieniach.</p>
      </div>
      <div class="pz-step">
        <div class="nr">3</div>
        <h3>Sprawdzenie i start</h3>
        <p>Przejrzyj listę klientów i otwarte faktury, ustaw numerację tak, aby kontynuowała ostatni numer z {{ $pkg['name'] }}, i wystaw pierwszą fakturę — z Twoim logo, gotową do KSeF. Przypomnienia o płatności dla zaimportowanych faktur ruszają automatycznie.</p>
      </div>
    </div>
    <p class="pz-note">{{ $pkg['notes'] }}</p>
    <p class="pz-note">Wolisz nie robić tego sam? Wyślij eksport na <a href="mailto:{{ brand('email') }}" style="color:var(--brand);">{{ brand('email') }}</a> — przeniesiemy dane bezpłatnie, zwykle tego samego dnia roboczego.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <h2>Co przenosisz</h2>
      <p>Trzy zestawy danych wystarczą, aby od jutra pracować w {{ brand('name') }}. Resztę — historię opłaconych faktur — zostawiasz w {{ $pkg['name'] }}.</p>
    </div>
    <div class="pz-table-wrap">
      <table class="pz-table">
        <thead><tr><th>Dane</th><th>Z pliku {{ $pkg['name'] }}</th><th>W {{ brand('name') }}</th></tr></thead>
        <tbody>
          <tr>
            <td>Klienci (kontrahenci)</td>
            <td>Nazwa, NIP, adres, kod pocztowy, miasto, e-mail, telefon</td>
            <td class="pz-yes">Kompletna lista klientów; NIP sprawdzony w białej liście, duplikaty pominięte</td>
          </tr>
          <tr>
            <td>Produkty i usługi</td>
            <td>Nazwa, cena netto, stawka VAT, jednostka</td>
            <td class="pz-yes">Lista produktów do wstawiania na faktury i oferty jednym kliknięciem</td>
          </tr>
          <tr>
            <td>Nieopłacone faktury</td>
            <td>Numer faktury, klient, data wystawienia, termin płatności, netto, VAT, brutto, zapłacono</td>
            <td class="pz-yes">Faktury ze statusem „wysłana”, z prawidłowym terminem — przypomnienia, wezwania i windykacja działają od razu</td>
          </tr>
        </tbody>
      </table>
    </div>

    <h3 style="font-size:17px;margin-top:32px;">Kolumny, które {{ brand('name') }} rozpoznaje sama</h3>
    <p class="pz-note" style="margin-top:6px;">Nazwy kolumn mogą być po polsku lub po angielsku, w dowolnej kolejności. Kolumn, których nie potrzebujemy, po prostu nie importujemy.</p>
    <div class="pz-cols">
      @foreach(['NIP', 'Nazwa', 'Adres', 'Kod pocztowy', 'Miasto', 'E-mail', 'Telefon', 'Numer faktury', 'Data wystawienia', 'Termin płatności', 'Brutto', 'Netto', 'VAT', 'Zapłacono'] as $col)
        <span class="value-pill">{{ $col }}</span>
      @endforeach
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container pz-faq">
    <div class="section-header" style="text-align:left;">
      <h2>Pytania przy przenosinach</h2>
    </div>
    <h3>Co z historią opłaconych faktur?</h3>
    <p>Zostaje w {{ $pkg['name'] }} — nie musisz jej przenosić, żeby pracować w {{ brand('name') }}. Przed zamknięciem starego konta pobierz z niego pełny eksport (CSV/XLSX oraz pliki PDF), tak aby mieć dokumenty na wypadek kontroli.</p>
    <h3>Jak długo muszę przechowywać stare faktury?</h3>
    <p>Dokumenty księgowe przechowuje się co do zasady <strong>5 lat, licząc od końca roku kalendarzowego, w którym upłynął termin płatności podatku</strong>. Ten obowiązek spoczywa na Tobie niezależnie od programu — dlatego zachowaj eksport z {{ $pkg['name'] }} w bezpiecznym miejscu. Od dnia przenosin {{ brand('name') }} przechowuje Twoje faktury i udostępnia pełny eksport w każdej chwili.</p>
    <h3>Czy numeracja faktur będzie kontynuowana?</h3>
    <p>Tak. W ustawieniach numeracji podajesz format (np. FV/2026/0001) i numer startowy, tak aby nowe faktury były kolejnymi po ostatniej wystawionej w {{ $pkg['name'] }}. Ciągłość numeracji w roku zostaje zachowana.</p>
    <h3>Czy zaimportowane faktury trafią do KSeF?</h3>
    <p>Faktury wystawione w {{ $pkg['name'] }} zostały już przekazane do KSeF z tamtego programu (jeśli byłeś do tego zobowiązany) — w {{ brand('name') }} służą do pilnowania płatności, a nie do ponownej wysyłki. Każda nowa faktura wystawiona w {{ brand('name') }} ma gotowy plik FA-XML do KSeF.</p>
    <h3>Czy moja księgowa może mieć wgląd?</h3>
    <p>Tak, bezpłatnie. Zapraszasz księgową z rolą „tylko odczyt”: widzi faktury, koszty i rozliczenie VAT per stawka, pobiera eksport CSV — niczego nie zmieni.</p>
    <h3>Czy coś mnie wiąże?</h3>
    <p>Nie. Abonament jest miesięczny, rezygnujesz w każdej chwili, a całą firmę eksportujesz zawsze w całości (CSV, PDF, XML) — także gdybyś kiedyś chciał przenieść się dalej.</p>
    <div style="margin-top:28px;"><a href="{{ route('register') }}" class="btn btn-primary btn-lg">Wypróbuj 14 dni za darmo →</a></div>
  </div>
</section>

<section class="section" style="padding-top:56px;padding-bottom:72px;">
  <div class="container">
    <div class="section-header" style="margin-bottom:8px;">
      <h2 style="font-size:24px;">Korzystasz z innego programu?</h2>
      <p>Instrukcje przenosin przygotowaliśmy także dla innych popularnych programów.</p>
    </div>
    <div class="pz-others">
      @foreach($packages as $key => $other)
        @continue($key === $slug)
        <a href="{{ route('pl.przenies', $key) }}">
          <span>Przenieś się z {{ $other['name'] }}<small>eksport CSV / XLSX → import w {{ brand('name') }}</small></span>
        </a>
      @endforeach
    </div>
  </div>
</section>
@endsection
