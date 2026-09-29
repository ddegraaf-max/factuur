@extends('layouts.marketing')

@section('title', 'Gratis aanmaning maken als PDF — ' . brand('name'))
@section('description', 'Maak gratis een laatste aanmaning als PDF, met de wettelijke rente, de incassokosten en de juiste termijn. Zonder account; er wordt niets opgeslagen.')

@php
  $faq = [
      ['Is deze aanmaning echt gratis?', 'Ja. Je vult de gegevens in en downloadt de brief als PDF. Je hebt geen account nodig en er staat geen watermerk op. Onderaan staat een kleine verwijzing naar ' . brand('name') . '.'],
      ['Wat is het verschil tussen zakelijk en particulier?', 'Voor een zakelijke klant geldt de wettelijke handelsrente en kies je zelf de termijn. Een particulier krijgt wettelijk minstens veertien dagen, te rekenen vanaf de dag na ontvangst, en de gewone wettelijke rente. In de brief moet dan het bedrag van de incassokosten staan. Deze tool zet dat er goed in. Zo\'n brief heet ook wel een veertiendagenbrief.'],
      ['Hoe worden de incassokosten berekend?', 'Volgens de wettelijke staffel: 15% over de eerste € 2.500, 10% over de volgende € 2.500, 5% over de volgende € 5.000 en daarna minder, met een minimum van € 40. Kun je geen btw verrekenen, bijvoorbeeld onder de kleineondernemersregeling, dan komt er 21% btw over de kosten bij.'],
      ['Vanaf wanneer loopt de rente?', 'Vanaf de dag na de vervaldatum van de factuur, tot en met vandaag. De brief noemt ook wat er per dag bij komt.'],
      ['Waarom twee dagen extra bij een particulier?', 'De termijn van veertien dagen begint pas de dag nadat je klant de brief heeft ontvangen. Je verstuurt deze brief zelf, per post of per mail. De tool rekent daarom twee dagen voor de bezorging.'],
      ['Wat gebeurt er met mijn gegevens?', 'Niets. De gegevens worden alleen gebruikt om de PDF te maken en daarna vergeten. Je eigen bedrijfsgegevens bewaart je browser voor de volgende keer. Alleen als je er zelf voor kiest, gaan de gegevens mee naar een proefaccount.'],
      ['Kan ik de aanmaning ook online versturen?', 'Ja, vanuit een account. Je klant krijgt dan een eigen pagina met het bedrag van vandaag, reageert met één klik, en jij ziet wanneer hij de aanmaning heeft geopend. Betaalt hij niet, dan draag je het dossier over aan de deurwaarder. Daarvoor bevestig je eerst je e-mailadres, zodat niemand uit jouw naam aanmaningen kan versturen.'],
  ];
@endphp

@push('styles')
<style>
  .gen-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 32px; max-width: 860px; margin: 0 auto; box-shadow: var(--shadow-sm); }
  .gen-section-title { font-family: var(--font-display); font-size: 17px; font-weight: 700; margin: 26px 0 14px; padding-top: 22px; border-top: 1px solid var(--border); }
  .gen-section-title:first-of-type { margin-top: 0; padding-top: 0; border-top: none; }
  .gen-check { display: flex; align-items: flex-start; gap: 10px; font-size: 14.5px; color: var(--text-2); margin: 0 0 14px; line-height: 1.5; cursor: pointer; }
  .gen-check input { width: 18px; height: 18px; margin-top: 2px; flex: none; }
  .gen-hint { font-size: 12.5px; color: var(--text-3); margin-top: 5px; line-height: 1.5; }
  .calc-box { margin: 22px 0; padding: 18px 20px; border-radius: 12px; background: var(--bg-alt, #FAFAF9); border: 1px solid var(--border); }
  .calc-box[hidden] { display: none; }
  .calc-title { font-family: var(--font-display); font-weight: 700; font-size: 16px; margin-bottom: 8px; }
  .calc-row { display: flex; justify-content: space-between; gap: 16px; padding: 6px 0; font-size: 14.5px; color: var(--text-2); font-variant-numeric: tabular-nums; }
  .calc-row.grand { border-top: 2px solid var(--text); margin-top: 6px; padding-top: 10px; font-weight: 700; font-size: 16.5px; color: var(--text); }
  .calc-note { font-size: 13px; color: var(--text-3); margin-top: 8px; line-height: 1.55; }
  .privacy-note { font-size: 12.5px; color: var(--text-3); margin-top: 14px; }
  .keep-card { margin-top: 22px; padding: 22px; border-radius: 14px; background: var(--brand-tint); border: 1px solid var(--brand-border); }
  .keep-card[hidden] { display: none; }
  .keep-title { font-family: var(--font-display); font-weight: 700; font-size: 19px; margin-bottom: 6px; }
  .keep-card p { color: var(--text-2); font-size: 14.5px; margin: 0 0 14px; line-height: 1.6; }
  .keep-note { font-size: 12.5px; color: var(--text-3); margin-top: 10px; }
  @media (max-width: 700px) {
    .gen-card { padding: 22px 18px; }
    /* 16px voorkomt dat iOS inzoomt zodra een veld focus krijgt. */
    .m-field input, .m-field textarea, .m-field select { font-size: 16px; }
  }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <span class="eyebrow">Gratis tool</span>
    <h1>Gratis aanmaning maken <span style="color:var(--brand);">als PDF</span></h1>
    <p class="lead">Vul de factuur in en download een laatste aanmaning met de wettelijke rente, de incassokosten en de termijn die de wet vraagt. Zonder account. Je gegevens worden nergens opgeslagen.</p>
  </div>
</section>

<section class="section" style="padding-top:36px;">
  <div class="container">
    <form class="gen-card" method="POST" action="{{ route('aanmaning-maken.download') }}" id="genForm">
      @csrf

      @if ($errors->any())
        <div class="alert-error">Controleer het formulier: {{ $errors->first() }}</div>
      @endif

      <div class="gen-section-title">Jouw gegevens</div>
      <div class="m-row-2">
        <div class="m-field">
          <label for="van_bedrijf">Bedrijfsnaam *</label>
          <input type="text" id="van_bedrijf" name="van_bedrijf" required maxlength="120" value="{{ old('van_bedrijf') }}" placeholder="Jansen Webdesign">
        </div>
        <div class="m-field">
          <label for="van_email">E-mailadres</label>
          <input type="email" id="van_email" name="van_email" maxlength="120" value="{{ old('van_email') }}" placeholder="jij@bedrijf.nl">
        </div>
      </div>
      <div class="m-field">
        <label for="van_adres">Adres</label>
        <textarea id="van_adres" name="van_adres" rows="2" maxlength="300" placeholder="Straatnaam 1&#10;1234 AB Plaats">{{ old('van_adres') }}</textarea>
      </div>
      <div class="m-row-2">
        <div class="m-field">
          <label for="van_iban">IBAN (waarop je klant betaalt)</label>
          <input type="text" id="van_iban" name="van_iban" maxlength="40" value="{{ old('van_iban') }}" placeholder="NL00 BANK 0123 4567 89">
        </div>
        <div class="m-field">
          <label for="van_kvk">KvK-nummer</label>
          <input type="text" id="van_kvk" name="van_kvk" maxlength="20" value="{{ old('van_kvk') }}" placeholder="12345678">
        </div>
      </div>
      <div class="m-field">
        <label for="van_telefoon">Telefoon</label>
        <input type="text" id="van_telefoon" name="van_telefoon" maxlength="40" value="{{ old('van_telefoon') }}" placeholder="06 12 34 56 78">
      </div>
      <input type="hidden" name="geen_btw_aftrek" value="0">
      <label class="gen-check">
        <input type="checkbox" name="geen_btw_aftrek" id="geen_btw_aftrek" value="1" @checked(old('geen_btw_aftrek'))>
        <span>Ik kan geen btw verrekenen, bijvoorbeeld door de kleineondernemersregeling. Over de incassokosten komt dan 21% btw.</span>
      </label>

      <div class="gen-section-title">De klant</div>
      <div class="m-row-2">
        <div class="m-field">
          <label for="aan_naam">Naam of bedrijfsnaam *</label>
          <input type="text" id="aan_naam" name="aan_naam" required maxlength="120" value="{{ old('aan_naam') }}" placeholder="De Vries Bouw B.V.">
        </div>
        <div class="m-field">
          <label for="klant">Soort klant *</label>
          <select id="klant" name="klant" required>
            <option value="zakelijk" @selected(old('klant', 'zakelijk') === 'zakelijk')>Zakelijk</option>
            <option value="particulier" @selected(old('klant') === 'particulier')>Particulier</option>
          </select>
        </div>
      </div>
      <div class="m-field">
        <label for="aan_adres">Adres</label>
        <textarea id="aan_adres" name="aan_adres" rows="2" maxlength="300" placeholder="Straatnaam 2&#10;5678 CD Plaats">{{ old('aan_adres') }}</textarea>
      </div>
      <div class="m-field">
        <label for="aan_email">E-mailadres van de klant</label>
        <input type="email" id="aan_email" name="aan_email" maxlength="180" value="{{ old('aan_email') }}" placeholder="administratie@klant.nl">
        <div class="gen-hint">Alleen nodig als je de aanmaning later online wilt versturen. Wij mailen je klant niet.</div>
      </div>

      <div class="gen-section-title">De factuur</div>
      <div class="m-row-2">
        <div class="m-field">
          <label for="factuurnummer">Factuurnummer *</label>
          <input type="text" id="factuurnummer" name="factuurnummer" required maxlength="40" value="{{ old('factuurnummer') }}" placeholder="{{ date('Y') }}-001">
        </div>
        <div class="m-field">
          <label for="bedrag">Openstaand bedrag, inclusief btw *</label>
          <input type="text" inputmode="decimal" id="bedrag" name="bedrag" required maxlength="20" value="{{ old('bedrag') }}" placeholder="1.250,00">
        </div>
      </div>
      <div class="m-row-2">
        <div class="m-field">
          <label for="factuurdatum">Factuurdatum *</label>
          <input type="date" id="factuurdatum" name="factuurdatum" required min="{{ config('rente.from') }}" max="{{ now()->toDateString() }}" value="{{ old('factuurdatum') }}">
        </div>
        <div class="m-field">
          <label for="vervaldatum">Vervaldatum *</label>
          <input type="date" id="vervaldatum" name="vervaldatum" required min="{{ config('rente.from') }}" max="{{ now()->subDay()->toDateString() }}" value="{{ old('vervaldatum') }}">
          <div class="gen-hint">De laatste dag waarop je klant had moeten betalen.</div>
        </div>
      </div>
      <div class="m-row-2">
        <div class="m-field">
          <label for="termijn">Termijn in dagen</label>
          <input type="number" id="termijn" name="termijn" min="{{ $terms['min_zakelijk'] }}" max="{{ $terms['max'] }}" value="{{ old('termijn') }}" placeholder="{{ $terms['zakelijk'] }}">
          <div class="gen-hint" id="termijnHint">Leeg laten: {{ $terms['zakelijk'] }} dagen voor een zakelijke klant, {{ $terms['particulier'] }} dagen voor een particulier.</div>
        </div>
        <div class="m-field">
          <label for="btw">Btw-tarief van de factuur</label>
          <select id="btw" name="btw">
            @foreach (\App\Support\Market::vatRates() as $rate)
              <option value="{{ $rate }}" @selected((string) old('btw', \App\Support\Market::defaultVatRate()) === (string) $rate)>{{ $rate }}%</option>
            @endforeach
          </select>
          <div class="gen-hint">Staat niet in de brief. Alleen van belang als je de factuur meeneemt naar een account.</div>
        </div>
      </div>
      <input type="hidden" name="rente" value="0">
      <label class="gen-check">
        <input type="checkbox" name="rente" id="rente" value="1" @checked(old('rente', '1'))>
        <span>Wettelijke rente meerekenen, vanaf de dag na de vervaldatum.</span>
      </label>

      {{-- Berekening vooraf. Zonder JavaScript blijft dit blok weg; de brief klopt dan net zo goed. --}}
      <div class="calc-box" id="calcBox" hidden>
        <div class="calc-title">Wat er in de brief komt</div>
        <div class="calc-row"><span>Openstaand bedrag</span><span id="cPrincipal"></span></div>
        <div class="calc-row" id="cInterestRow"><span id="cInterestLabel">Wettelijke rente</span><span id="cInterest"></span></div>
        <div class="calc-row grand"><span>Te betalen vandaag</span><span id="cTotal"></span></div>
        <div class="calc-row"><span id="cCostsLabel">Incassokosten na de termijn</span><span id="cCosts"></span></div>
        <div class="calc-note" id="cNote"></div>
      </div>

      @if(config('services.turnstile.sitekey'))
        <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.sitekey') }}" style="margin-bottom:14px;"></div>
        @error('cf-turnstile-response')<div class="m-err">{{ $message }}</div>@enderror
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
      @endif

      <button type="submit" class="btn btn-primary btn-lg btn-block">Download aanmaning (PDF) ↓</button>
      <div class="privacy-note">We slaan niets op en sturen niets naar je klant: je krijgt de brief en verstuurt hem zelf. Alleen je eigen bedrijfsgegevens worden, voor de volgende keer, in je eigen browser bewaard.</div>

      {{-- Na het downloaden: de aanmaning online versturen vanuit een account. Alleen op eigen verzoek;
           pas bij een klik op deze knop gaan de gegevens mee. --}}
      <div class="keep-card" id="keepCard" hidden>
        <div class="keep-title">Je aanmaning is gedownload</div>
        <p>Wil je hem online versturen? Je klant krijgt dan een eigen pagina met het bedrag van vandaag en reageert met één klik. Jij ziet wanneer hij de aanmaning heeft geopend. Betaalt hij niet, dan draag je het dossier over aan de deurwaarder.</p>
        <button type="submit" class="btn btn-primary" formaction="{{ route('aanmaning-maken.keep') }}">Neem mee naar een gratis proefaccount →</button>
        <div class="keep-note">14 dagen gratis, geen betaalgegevens nodig. Je bevestigt eerst je e-mailadres; daarna verstuur je de aanmaning. Pas als je op deze knop klikt, gaan je gegevens mee.</div>
      </div>
    </form>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="prose">
      <h2>Wat er in de brief staat</h2>
      <ul>
        <li><strong>Het openstaande bedrag</strong> en de <strong>wettelijke rente</strong> tot en met vandaag, naar het percentage dat in die periode gold.</li>
        <li><strong>De termijn</strong> waarbinnen je klant zonder incassokosten kan betalen, met de laatste dag erbij.</li>
        <li><strong>Het bedrag van de incassokosten</strong> die daarna verschuldigd zijn, volgens de wettelijke staffel.</li>
        <li><strong>Wat er daarna gebeurt:</strong> de vordering gaat naar de gerechtsdeurwaarder.</li>
        <li><strong>Je rekeningnummer</strong> en het factuurnummer als omschrijving.</li>
      </ul>

      <h2>Eerst herinneren, dan aanmanen</h2>
      <p>Een aanmaning is de laatste stap. Meestal stuur je eerst een of twee herinneringen. Helpt dat niet, dan stel je met deze brief een laatste termijn. Pas als die voorbij is, mag je bij een particulier incassokosten rekenen. Meer uitleg staat in de kennisbank, bij <a href="{{ route('kennisbank.artikel', 'betalingstermijn-aanmanen') }}">betalingstermijnen en aanmanen</a> en bij <a href="{{ route('kennisbank.artikel', 'incassokosten-wettelijke-rente-berekenen') }}">incassokosten en wettelijke rente</a>.</p>
      <p>Wil je alleen de bedragen weten? Gebruik dan de <a href="{{ route('incassokosten-calculator') }}">calculator voor incassokosten en rente</a>. Wil je dat de aanmaning zelf het bedrag bijhoudt en je klant online reageert? Lees over de <a href="{{ route('aanmaning') }}">online aanmaning</a>.</p>
      <p>De berekening rust op artikel 6:96, 6:119 en 6:119a van het Burgerlijk Wetboek en het Besluit vergoeding voor buitengerechtelijke incassokosten. {{ brand('name') }} is geen incassobureau en geeft geen juridisch advies.</p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <h2>Veelgestelde vragen</h2>
    </div>
    <div class="faq-list">
      @foreach ($faq as [$question, $answer])
        <details class="faq-item">
          <summary>{{ $question }}<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
          <div class="faq-content">{{ $answer }}</div>
        </details>
      @endforeach
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

<script>
(function () {
  var form = document.getElementById('genForm');
  var terms = @json($terms);
  var byId = function (id) { return document.getElementById(id); };

  // Onthoud alleen je eigen bedrijfsgegevens, lokaal in je eigen browser (gedeeld met de gratis factuur).
  var ownFields = ['van_bedrijf', 'van_email', 'van_adres', 'van_kvk', 'van_iban'];
  try {
    var saved = JSON.parse(localStorage.getItem('ei_gratis_factuur') || '{}');
    ownFields.forEach(function (id) {
      var el = byId(id);
      if (el && !el.value && saved[id]) el.value = saved[id];
    });
  } catch (e) {}

  // De termijn volgt de soort klant.
  function syncTerm() {
    var consumer = byId('klant').value === 'particulier';
    var field = byId('termijn');
    field.min = consumer ? terms.particulier : terms.min_zakelijk;
    field.placeholder = consumer ? terms.particulier : terms.zakelijk;
    if (field.value && Number(field.value) < Number(field.min)) field.value = field.min;
  }

  // Berekening vooraf: alleen bedragen en datums gaan naar de server, geen namen of adressen.
  var timer = null;
  function calculate() {
    var params = new URLSearchParams({
      bedrag: byId('bedrag').value,
      vervaldatum: byId('vervaldatum').value,
      klant: byId('klant').value,
      termijn: byId('termijn').value,
      rente: byId('rente').checked ? '1' : '0',
      geen_btw_aftrek: byId('geen_btw_aftrek').checked ? '1' : '0'
    });
    if (!byId('bedrag').value || !byId('vervaldatum').value) { byId('calcBox').hidden = true; return; }
    fetch(@json(route('aanmaning-maken.calculation')) + '?' + params.toString(), { headers: { Accept: 'application/json' } })
      .then(function (response) { return response.ok ? response.json() : null; })
      .then(function (data) {
        if (!data) { byId('calcBox').hidden = true; return; }
        byId('cPrincipal').textContent = data.principal;
        byId('cInterestRow').style.display = data.with_interest ? '' : 'none';
        byId('cInterestLabel').textContent = data.interest_label;
        byId('cInterest').textContent = data.interest;
        byId('cTotal').textContent = data.total;
        byId('cCostsLabel').textContent = data.costs_label;
        byId('cCosts').textContent = data.costs;
        byId('cNote').textContent = data.note;
        byId('calcBox').hidden = false;
      })
      .catch(function () { byId('calcBox').hidden = true; });
  }
  function later() { clearTimeout(timer); timer = setTimeout(calculate, 350); }

  ['bedrag', 'vervaldatum', 'termijn'].forEach(function (id) { byId(id).addEventListener('input', later); });
  ['rente', 'geen_btw_aftrek'].forEach(function (id) { byId(id).addEventListener('change', calculate); });
  byId('klant').addEventListener('change', function () { syncTerm(); calculate(); });
  syncTerm();
  calculate();

  // Na het downloaden blijft de pagina staan; dan verschijnt het aanbod om de
  // aanmaning online te versturen. De knop daarin verstuurt hetzelfde formulier naar een ander adres.
  form.addEventListener('submit', function (e) {
    try {
      var data = JSON.parse(localStorage.getItem('ei_gratis_factuur') || '{}');
      ownFields.forEach(function (id) { var el = byId(id); if (el) data[id] = el.value; });
      localStorage.setItem('ei_gratis_factuur', JSON.stringify(data));
    } catch (err) {}

    if (e.submitter && e.submitter.hasAttribute('formaction')) return;
    setTimeout(function () {
      var card = byId('keepCard');
      card.hidden = false;
      card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 1500);
  });
})();
</script>
@endsection
