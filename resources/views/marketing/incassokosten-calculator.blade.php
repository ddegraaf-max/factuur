@extends('layouts.marketing')

@section('title', 'Incassokosten en wettelijke rente berekenen (' . date('Y') . ') — ' . brand('name'))
@section('description', 'Bereken de wettelijke incassokosten en rente over een te laat betaalde factuur. Met de staffel, de actuele percentages en een tekst voor je aanmaning.')

@php
  $eur = fn ($n) => '€ ' . number_format((float) $n, 2, ',', '.');
  $pct = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',') . '%';
  $day = fn ($d) => \Illuminate\Support\Carbon::parse($d)->translatedFormat('j F Y');
  $business = $input['klant'] === 'zakelijk';
  $rateName = $business ? 'wettelijke handelsrente' : 'wettelijke rente';
  $faq = [
      ['Mag ik incassokosten rekenen zonder eerst een aanmaning te sturen?', 'Bij een zakelijke klant wel: zodra de betaaltermijn is verstreken, is de klant minimaal 40 euro aan incassokosten verschuldigd, ook zonder aanmaning. Bij een consument niet: die moet eerst een brief krijgen met een termijn van veertien dagen, gerekend vanaf de dag na ontvangst, en met het bedrag aan incassokosten dat daarna volgt.'],
      ['Vanaf welke dag loopt de wettelijke rente?', 'Vanaf de dag na de vervaldatum van de factuur. Staat er geen betaaltermijn op een zakelijke factuur, dan geldt een termijn van dertig dagen na ontvangst van de factuur.'],
      ['Komt er btw over de incassokosten?', 'Alleen als je zelf geen btw kunt verrekenen, bijvoorbeeld omdat je de kleineondernemersregeling gebruikt of vrijgestelde diensten levert. Kun je btw aftrekken, dan reken je de incassokosten zonder btw door.'],
      ['Wat is het verschil tussen wettelijke rente en wettelijke handelsrente?', 'De wettelijke handelsrente geldt tussen bedrijven en bij overheidsopdrachten en ligt hoger: nu ' . $pct(\App\Support\LegalInterest::rateOn(now(), true)) . '. De gewone wettelijke rente geldt als de klant een consument is: nu ' . $pct(\App\Support\LegalInterest::rateOn(now(), false)) . '. Beide percentages worden per 1 januari en 1 juli opnieuw vastgesteld.'],
      ['Mag ik hogere incassokosten rekenen dan de staffel?', 'Bij consumenten niet: de staffel is daar het maximum, ook als je voorwaarden iets anders zeggen. Met zakelijke klanten mag je hogere kosten afspreken, bijvoorbeeld in je algemene voorwaarden. De rechter kan een onredelijk hoog bedrag wel matigen.'],
  ];
@endphp

@push('styles')
<style>
  .calc-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 32px; max-width: 680px; margin: 0 auto; box-shadow: var(--shadow-sm); }
  @media (max-width: 600px) { .calc-card { padding: 22px 18px; } }
  .calc-result { background: var(--surface-2); border-radius: 12px; padding: 20px 22px; margin-top: 22px; }
  .calc-result .row { display: flex; justify-content: space-between; gap: 16px; padding: 5px 0; font-size: 15px; color: var(--text-2); }
  .calc-result .row span:last-child { white-space: nowrap; font-variant-numeric: tabular-nums; }
  .calc-result .grand { font-weight: 700; font-size: 20px; color: var(--text); border-top: 2px solid var(--text); margin-top: 8px; padding-top: 12px; }
  .calc-hint { font-size: 12.5px; color: var(--text-3); margin-top: 4px; }
  .seg { display: flex; border: 1px solid var(--border-strong); border-radius: 10px; overflow: hidden; margin-bottom: 16px; }
  .seg label { flex: 1; text-align: center; padding: 11px 8px; font-size: 14px; font-weight: 600; color: var(--text-2); cursor: pointer; background: var(--surface); }
  .seg input { position: absolute; opacity: 0; pointer-events: none; }
  .seg input:checked + label { background: var(--brand); color: white; }
  .seg input:focus-visible + label { outline: 2px solid var(--text); outline-offset: -2px; }
  .calc-check { display: flex; align-items: flex-start; gap: 10px; font-size: 14px; color: var(--text-2); margin: 4px 0 18px; }
  .calc-check input { width: 18px; height: 18px; margin-top: 2px; flex: none; accent-color: var(--brand); }
  .calc-table-wrap { overflow-x: auto; margin: 0 0 16px; }
  .calc-table { width: 100%; border-collapse: collapse; font-size: 14px; }
  .calc-table th { text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); padding: 8px 10px 8px 0; border-bottom: 1px solid var(--border-strong); white-space: nowrap; }
  .calc-table td { padding: 8px 10px 8px 0; border-bottom: 1px solid var(--border); color: var(--text-2); white-space: nowrap; font-variant-numeric: tabular-nums; }
  .calc-table .num { text-align: right; padding-right: 0; }
  .calc-copy { background: var(--surface); border: 1px dashed var(--border-strong); border-radius: 10px; padding: 14px 16px; font-size: 14px; color: var(--text-2); line-height: 1.6; margin-top: 16px; }
  .calc-copy b { display: block; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); margin-bottom: 4px; }
  .calc-next { background: var(--brand-tint); border: 1px solid var(--brand-border); border-radius: 12px; padding: 18px 20px; margin-top: 18px; font-size: 14.5px; color: var(--text-2); }
  .calc-next a { color: var(--brand); font-weight: 600; }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <span class="eyebrow">Gratis tool</span>
    <h1>Incassokosten en rente berekenen</h1>
    <p class="lead">Betaalt een klant te laat? Reken uit welke incassokosten en wettelijke rente je mag vragen, volgens de wettelijke staffel en de percentages van {{ date('Y') }}.</p>
  </div>
</section>

<section class="section" style="padding-top:36px;" id="berekening">
  <div class="container">
    <form class="calc-card" method="get" action="{{ route('incassokosten-calculator') }}#berekening">
      <div class="seg" role="radiogroup" aria-label="Soort klant">
        <input type="radio" name="klant" id="klantZakelijk" value="zakelijk" @checked($business)>
        <label for="klantZakelijk">Zakelijke klant</label>
        <input type="radio" name="klant" id="klantConsument" value="consument" @checked(! $business)>
        <label for="klantConsument">Consument</label>
      </div>

      <div class="m-row-2">
        <div class="m-field">
          <label for="bedrag">Openstaand factuurbedrag</label>
          <input type="text" id="bedrag" name="bedrag" inputmode="decimal" placeholder="1.250,00" value="{{ $input['bedrag'] }}" required>
          <div class="calc-hint">Inclusief btw, zoals het op de factuur staat.</div>
          @isset($calcErrors['bedrag'])<div class="m-err">{{ $calcErrors['bedrag'][0] }}</div>@endisset
        </div>
        <div class="m-field">
          <label for="vervaldatum">Vervaldatum van de factuur</label>
          <input type="date" id="vervaldatum" name="vervaldatum" value="{{ $input['vervaldatum'] }}" min="{{ config('rente.from') }}" required>
          <div class="calc-hint">De rente loopt vanaf de dag erna.</div>
          @isset($calcErrors['vervaldatum'])<div class="m-err">{{ $calcErrors['vervaldatum'][0] }}</div>@endisset
        </div>
      </div>

      <div class="m-field">
        <label for="tot">Rente berekenen tot en met</label>
        <input type="date" id="tot" name="tot" value="{{ $input['tot'] }}" required>
        <div class="calc-hint">Meestal vandaag, of de dag waarop je betaling verwacht.</div>
        @isset($calcErrors['tot'])<div class="m-err">{{ $calcErrors['tot'][0] }}</div>@endisset
      </div>

      <label class="calc-check">
        <input type="checkbox" name="btw" value="1" @checked($input['btw'])>
        <span>Ik kan zelf geen btw verrekenen (tel 21% btw bij de incassokosten)</span>
      </label>

      <button type="submit" class="btn btn-primary btn-lg btn-block">Bereken</button>

      @if ($result)
        <div class="calc-result" aria-live="polite">
          <div class="row"><span>Hoofdsom</span><span>{{ $eur($result['principal']) }}</span></div>
          <div class="row"><span>Incassokosten volgens de staffel</span><span>{{ $eur($result['costs']) }}</span></div>
          @if ($result['costs_vat'] > 0)
            <div class="row"><span>Btw over de incassokosten (21%)</span><span>{{ $eur($result['costs_vat']) }}</span></div>
          @endif
          <div class="row"><span>{{ ucfirst($rateName) }} over {{ $result['days'] }} {{ $result['days'] === 1 ? 'dag' : 'dagen' }}</span><span>{{ $eur($result['interest']) }}</span></div>
          <div class="row grand"><span>Totaal te vorderen</span><span>{{ $eur($result['total']) }}</span></div>
        </div>

        @if ($result['periods'])
          <h3 style="font-size:16px;margin:24px 0 10px;">Zo is de rente opgebouwd</h3>
          <div class="calc-table-wrap">
            <table class="calc-table">
              <tr><th>Periode</th><th class="num">Dagen</th><th class="num">Percentage</th><th class="num">Over</th><th class="num">Rente</th></tr>
              @foreach ($result['periods'] as $period)
                <tr>
                  <td>{{ $period['from']->format('d-m-Y') }} t/m {{ $period['to']->format('d-m-Y') }}</td>
                  <td class="num">{{ $period['days'] }}</td>
                  <td class="num">{{ $pct($period['rate']) }}</td>
                  <td class="num">{{ $eur($period['base']) }}</td>
                  <td class="num">{{ $eur($period['interest']) }}</td>
                </tr>
              @endforeach
            </table>
          </div>
          @if (count(array_unique(array_map(fn ($p) => $p['base'], $result['periods']))) > 1)
            <div class="calc-hint">Na elk vol jaar komt de rente van dat jaar bij het bedrag waarover verder wordt gerekend.</div>
          @endif
        @endif

        <div class="calc-copy">
          <b>Tekst voor je aanmaning</b>
          Het openstaande bedrag van factuur [nummer] is {{ $eur($result['principal']) }}. Daar komt bij: {{ $eur($result['costs'] + $result['costs_vat']) }} aan buitengerechtelijke incassokosten{{ $result['costs_vat'] > 0 ? ' (inclusief btw)' : '' }} en {{ $eur($result['interest']) }} aan {{ $rateName }}, berekend tot en met {{ $day($input['tot']) }}. In totaal is dat {{ $eur($result['total']) }}.
        </div>

        @unless ($business)
          <div class="calc-hint" style="margin-top:12px;">Let op bij een consument: de incassokosten mag je pas rekenen nadat je een brief hebt gestuurd met een termijn van veertien dagen (vanaf de dag na ontvangst) waarin dit bedrag aan incassokosten staat.</div>
        @endunless

        <div class="calc-next">
          <strong>Dit niet elke keer zelf doen?</strong> In {{ brand('name') }} gaan betalingsherinneringen en aanmaningen vanzelf de deur uit, en draag je een factuur die onbetaald blijft met één klik over aan de deurwaarder. <a href="{{ route('register') }}">Probeer 14 dagen gratis</a> of <a href="{{ route('demo') }}">bekijk de demo</a>.
        </div>
      @endif
    </form>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="prose">
      <h2>Hoe hoog zijn de wettelijke incassokosten?</h2>
      <p>De incassokosten zijn een percentage van het openstaande bedrag. De percentages liggen vast in de wet en lopen af naarmate het bedrag hoger is. Het minimum is {{ $eur(config('rente.minimum')) }}, het maximum {{ $eur(config('rente.maximum')) }}.</p>
      <div class="calc-table-wrap">
        <table class="calc-table">
          <tr><th>Deel van het bedrag</th><th class="num">Percentage</th></tr>
          <tr><td>Over de eerste € 2.500</td><td class="num">15%</td></tr>
          <tr><td>Over de volgende € 2.500</td><td class="num">10%</td></tr>
          <tr><td>Over de volgende € 5.000</td><td class="num">5%</td></tr>
          <tr><td>Over de volgende € 190.000</td><td class="num">1%</td></tr>
          <tr><td>Over alles daarboven</td><td class="num">0,5%</td></tr>
        </table>
      </div>
      <p><strong>Voorbeeld.</strong> Een factuur van € 6.000 geeft 15% over € 2.500 (€ 375), 10% over € 2.500 (€ 250) en 5% over de laatste € 1.000 (€ 50). Samen {{ $eur(\App\Support\LegalInterest::collectionCosts(6000)) }}. Een factuur van € 150 geeft rekenkundig € 22,50, maar je mag het minimum van {{ $eur(config('rente.minimum')) }} rekenen.</p>

      <h2>Hoe hoog is de wettelijke rente?</h2>
      <p>Er zijn twee percentages. Tussen bedrijven geldt de wettelijke handelsrente, bij een consument de gewone wettelijke rente. Ze worden per 1 januari en 1 juli opnieuw vastgesteld. De calculator rekent per dag met het percentage dat op die dag gold.</p>
      <div class="card-grid cols-2" style="margin-bottom:16px;">
        @foreach (['zakelijk' => 'Zakelijke klant (handelsrente)', 'consument' => 'Consument (wettelijke rente)'] as $kind => $label)
          <div class="info-card">
            <h3>{{ $label }}</h3>
            <table class="calc-table">
              <tr><th>Vanaf</th><th class="num">Percentage</th></tr>
              @foreach (array_slice($rates[$kind], 0, 6, true) as $from => $rate)
                <tr><td>{{ $day($from) }}</td><td class="num">{{ $pct($rate) }}</td></tr>
              @endforeach
            </table>
          </div>
        @endforeach
      </div>
      <p>Percentages voor het laatst gecontroleerd op {{ $day(config('rente.checked')) }}; de bron is <a href="https://www.rijksoverheid.nl/vraag-en-antwoord/schulden/hoogte-wettelijke-rente" rel="noopener" target="_blank">rijksoverheid.nl</a>.</p>

      <h2>Zakelijke klant of consument: wat is het verschil?</h2>
      <p><strong>Zakelijke klant.</strong> Zodra de betaaltermijn voorbij is, mag je incassokosten en handelsrente rekenen. Een aanmaning is wettelijk niet nodig, al is een herinnering eerst wel zo netjes. Het minimum van {{ $eur(config('rente.minimum')) }} geldt ook bij een klein bedrag.</p>
      <p><strong>Consument.</strong> Je stuurt eerst een brief waarin je veertien dagen geeft om te betalen, gerekend vanaf de dag nadat de brief is ontvangen, en waarin het bedrag aan incassokosten staat dat volgt als er niet wordt betaald. Pas na die termijn mag je de kosten rekenen. De staffel is bij consumenten het maximum.</p>

      <h2>Van berekening naar betaling</h2>
      <ol>
        <li><strong>Stuur een herinnering</strong> kort na de vervaldatum. De meeste facturen worden daarna alsnog betaald.</li>
        <li><strong>Stuur een aanmaning</strong> met de hoofdsom, de incassokosten, de rente tot een genoemde dag en het totaal. Geef een korte termijn en zeg wat er daarna gebeurt.</li>
        <li><strong>Draag de vordering over</strong> aan een incassobureau of deurwaarder als er dan nog niet is betaald.</li>
      </ol>
      <p>Meer uitleg staat in het artikel <a href="{{ route('kennisbank.artikel', 'incassokosten-wettelijke-rente-berekenen') }}">Incassokosten en wettelijke rente berekenen</a> en in <a href="{{ route('kennisbank.artikel', 'betalingstermijn-aanmanen') }}">Betalingstermijn en aanmanen</a>. Nog geen factuur gestuurd? <a href="{{ route('gratis-factuur') }}">Maak er gratis een</a>, zonder account.</p>

      <h2>Veelgestelde vragen</h2>
      @foreach ($faq as [$question, $answer])
        <h3>{{ $question }}</h3>
        <p>{{ $answer }}</p>
      @endforeach

      <p style="font-size:13.5px;color:var(--text-3);">Deze calculator geeft een berekening volgens de wettelijke regels, geen juridisch advies. Heb je met je klant andere afspraken gemaakt over rente of kosten, dan gaan die afspraken voor zover de wet dat toelaat.</p>
    </div>
  </div>
</section>

<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(fn ($item) => [
        '@type' => 'Question',
        'name' => $item[0],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item[1]],
    ], $faq),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection
