@extends('layouts.marketing')

@section('title', 'Gratis factuur maken (PDF) — zonder account — EasyInvoice')
@section('description', 'Maak gratis een professionele factuur als PDF. Zonder account, zonder watermerk, met correcte btw. Vul je gegevens in en download direct — gemaakt voor Nederlandse zzp\'ers en mkb.')

@push('styles')
<style>
  .gen-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 32px; max-width: 860px; margin: 0 auto; box-shadow: var(--shadow-sm); }
  .gen-section-title { font-family: var(--font-display); font-size: 17px; font-weight: 700; margin: 26px 0 14px; padding-top: 22px; border-top: 1px solid var(--border); }
  .gen-section-title:first-child { margin-top: 0; padding-top: 0; border-top: none; }
  .lines-table { width: 100%; border-collapse: collapse; }
  .lines-table th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-3); font-weight: 600; padding: 0 8px 6px 0; }
  .lines-table td { padding: 0 8px 10px 0; vertical-align: top; }
  .lines-table input, .lines-table select { width: 100%; padding: 10px 12px; border: 1px solid var(--border-strong); border-radius: 8px; font-family: inherit; font-size: 14px; }
  .lines-table input:focus, .lines-table select:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-tint); }
  .line-total { padding-top: 10px; font-size: 14px; text-align: right; font-variant-numeric: tabular-nums; color: var(--text-2); white-space: nowrap; }
  .line-del { background: none; border: none; color: var(--text-4); font-size: 18px; padding: 8px 4px; line-height: 1; border-radius: 6px; }
  .line-del:hover { color: var(--brand); background: var(--brand-tint); }
  .totals-box { margin-top: 8px; margin-left: auto; max-width: 300px; font-size: 14.5px; }
  .totals-box .row { display: flex; justify-content: space-between; padding: 4px 0; color: var(--text-2); }
  .totals-box .grand { border-top: 2px solid var(--text); margin-top: 6px; padding-top: 10px; font-weight: 700; font-size: 17px; color: var(--text); }
  .privacy-note { font-size: 12.5px; color: var(--text-3); margin-top: 14px; }
  .check-list { list-style: none; padding: 0; margin: 0; }
  .check-list li { padding: 7px 0 7px 30px; position: relative; color: var(--text-2); }
  .check-list li::before {
    content: ''; position: absolute; left: 0; top: 10px; width: 18px; height: 18px; border-radius: 50%;
    background: var(--success-bg) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23059669' stroke-width='3'%3E%3Cpolyline points='20 6 9 17 4 12'/%3E%3C/svg%3E") no-repeat center;
  }
  @media (max-width: 700px) {
    .gen-card { padding: 22px 18px; }
    /* Op een telefoon is de tabel te krap: elke factuurregel wordt een
       blokje — omschrijving over de volle breedte, daaronder aantal,
       prijs en btw naast elkaar, met mini-labels erboven. */
    .lines-table, .lines-table tbody { display: block; }
    .lines-table thead { display: none; }
    .lines-table tr.line-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; padding: 14px 0; border-bottom: 1px solid var(--border); }
    .lines-table tr.line-row:first-child { padding-top: 0; }
    .lines-table td { display: block; padding: 0; }
    .lines-table td.td-desc { grid-column: 1 / -1; }
    .lines-table td[data-label]::before { content: attr(data-label); display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); margin-bottom: 4px; }
    .lines-table td.line-total { grid-column: 1 / 3; text-align: left; padding-top: 0; font-weight: 600; color: var(--text); }
    .lines-table td.td-del { text-align: right; }
    /* 16px voorkomt dat iOS inzoomt zodra een veld focus krijgt. */
    .lines-table input, .lines-table select, .m-field input, .m-field textarea, .m-field select { font-size: 16px; }
  }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <span class="eyebrow">Gratis tool</span>
    <h1>Gratis factuur maken <span style="color:var(--brand);">als PDF</span></h1>
    <p class="lead">Vul hieronder je gegevens in en download direct een professionele factuur — zonder account, zonder watermerk, met correcte btw. Je gegevens worden nergens opgeslagen.</p>
  </div>
</section>

<section class="section" style="padding-top:36px;">
  <div class="container">
    <form class="gen-card" method="POST" action="{{ route('gratis-factuur.download') }}" id="genForm">
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
          <input type="text" id="van_email" name="van_email" maxlength="120" value="{{ old('van_email') }}" placeholder="jij@bedrijf.nl">
        </div>
      </div>
      <div class="m-field">
        <label for="van_adres">Adres</label>
        <textarea id="van_adres" name="van_adres" rows="2" maxlength="300" placeholder="Straatnaam 1&#10;1234 AB Plaats">{{ old('van_adres') }}</textarea>
      </div>
      <div class="m-row-2">
        <div class="m-field">
          <label for="van_kvk">KvK-nummer</label>
          <input type="text" id="van_kvk" name="van_kvk" maxlength="20" value="{{ old('van_kvk') }}" placeholder="12345678">
        </div>
        <div class="m-field">
          <label for="van_btw">Btw-id</label>
          <input type="text" id="van_btw" name="van_btw" maxlength="25" value="{{ old('van_btw') }}" placeholder="NL123456789B01">
        </div>
      </div>
      <div class="m-field">
        <label for="van_iban">IBAN (voor de betaalinstructie op de factuur)</label>
        <input type="text" id="van_iban" name="van_iban" maxlength="40" value="{{ old('van_iban') }}" placeholder="NL00 BANK 0123 4567 89">
      </div>

      <div class="gen-section-title">De klant</div>
      <div class="m-field">
        <label for="aan_bedrijf">Naam of bedrijfsnaam *</label>
        <input type="text" id="aan_bedrijf" name="aan_bedrijf" required maxlength="120" value="{{ old('aan_bedrijf') }}" placeholder="De Vries Bouw B.V.">
      </div>
      <div class="m-field">
        <label for="aan_adres">Adres</label>
        <textarea id="aan_adres" name="aan_adres" rows="2" maxlength="300" placeholder="Straatnaam 2&#10;5678 CD Plaats">{{ old('aan_adres') }}</textarea>
      </div>

      <div class="gen-section-title">Factuurgegevens</div>
      <div class="m-row-2">
        <div class="m-field">
          <label for="factuurnummer">Factuurnummer *</label>
          <input type="text" id="factuurnummer" name="factuurnummer" required maxlength="40" value="{{ old('factuurnummer') }}" placeholder="{{ date('Y') }}-001">
        </div>
        <div class="m-field">
          <label for="btw_type">Btw-behandeling</label>
          <select id="btw_type" name="btw_type">
            <option value="normaal" @selected(old('btw_type', 'normaal') === 'normaal')>Normaal (btw per regel)</option>
            <option value="verlegd" @selected(old('btw_type') === 'verlegd')>Btw verlegd</option>
            <option value="vrijgesteld" @selected(old('btw_type') === 'vrijgesteld')>Vrijgesteld / KOR (geen btw)</option>
          </select>
        </div>
      </div>
      <div class="m-row-2">
        <div class="m-field">
          <label for="factuurdatum">Factuurdatum *</label>
          <input type="date" id="factuurdatum" name="factuurdatum" required value="{{ old('factuurdatum', date('Y-m-d')) }}">
        </div>
        <div class="m-field">
          <label for="vervaldatum">Vervaldatum</label>
          <input type="date" id="vervaldatum" name="vervaldatum" value="{{ old('vervaldatum', date('Y-m-d', strtotime('+14 days'))) }}">
        </div>
      </div>

      <div class="gen-section-title">Factuurregels</div>
      <table class="lines-table" id="linesTable">
        <thead>
          <tr>
            <th>Omschrijving</th>
            <th class="col-qty" style="width:84px;">Aantal</th>
            <th style="width:120px;">Prijs (excl.)</th>
            <th class="col-vat" style="width:88px;">Btw</th>
            <th style="width:90px;text-align:right;">Bedrag</th>
            <th style="width:30px;"></th>
          </tr>
        </thead>
        <tbody id="linesBody">
          @foreach (old('regels', [['omschrijving' => '', 'aantal' => '1', 'prijs' => '', 'btw' => '21']]) as $i => $line)
            <tr class="line-row">
              <td class="td-desc" data-label="Omschrijving"><input type="text" name="regels[{{ $i }}][omschrijving]" maxlength="200" required value="{{ $line['omschrijving'] ?? '' }}" placeholder="Bijv. Website ontwerp"></td>
              <td data-label="Aantal"><input type="text" name="regels[{{ $i }}][aantal]" required inputmode="decimal" value="{{ $line['aantal'] ?? '1' }}" class="js-qty"></td>
              <td data-label="Prijs (excl.)"><input type="text" name="regels[{{ $i }}][prijs]" required inputmode="decimal" value="{{ $line['prijs'] ?? '' }}" placeholder="0,00" class="js-price"></td>
              <td data-label="Btw">
                <select name="regels[{{ $i }}][btw]" class="js-vat">
                  <option value="21" @selected(($line['btw'] ?? '21') == '21')>21%</option>
                  <option value="9" @selected(($line['btw'] ?? '') == '9')>9%</option>
                  <option value="0" @selected(($line['btw'] ?? '') == '0')>0%</option>
                </select>
              </td>
              <td class="line-total js-line-total" data-label="Bedrag">€ 0,00</td>
              <td class="td-del"><button type="button" class="line-del js-del" title="Regel verwijderen">×</button></td>
            </tr>
          @endforeach
        </tbody>
      </table>
      <button type="button" class="btn btn-secondary" id="addLine" style="margin-top:4px;">+ Regel toevoegen</button>

      <div class="totals-box">
        <div class="row"><span>Subtotaal (excl. btw)</span><span id="tSub">€ 0,00</span></div>
        <div class="row" id="tVatRow"><span>Btw</span><span id="tVat">€ 0,00</span></div>
        <div class="row grand"><span>Totaal</span><span id="tTotal">€ 0,00</span></div>
      </div>

      <div class="m-field" style="margin-top:18px;">
        <label for="opmerking">Opmerking op de factuur (optioneel)</label>
        <textarea id="opmerking" name="opmerking" rows="2" maxlength="500" placeholder="Bijv. bedankt voor de fijne samenwerking!">{{ old('opmerking') }}</textarea>
      </div>

      @if(config('services.turnstile.sitekey'))
        <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.sitekey') }}" style="margin-bottom:14px;"></div>
        @error('cf-turnstile-response')<div class="m-err">{{ $message }}</div>@enderror
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
      @endif

      <button type="submit" class="btn btn-primary btn-lg btn-block">Download factuur (PDF) ↓</button>
      <div class="privacy-note">We slaan niets op: je gegevens worden alleen gebruikt om de PDF te maken. Alleen je eigen bedrijfsgegevens worden — voor de volgende keer — in je eigen browser bewaard.</div>
    </form>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <h2>Wat moet er verplicht op een factuur staan?</h2>
      <p>De Belastingdienst stelt duidelijke eisen aan een factuur. Deze generator zet ze er automatisch goed op — dit is de checklist:</p>
    </div>
    <div class="card-grid cols-2" style="max-width:860px;margin:0 auto;">
      <div class="info-card">
        <ul class="check-list">
          <li>Je bedrijfsnaam en adres</li>
          <li>Je KvK-nummer</li>
          <li>Je btw-identificatienummer (bij btw-belaste leveringen)</li>
          <li>Naam en adres van je klant</li>
          <li>Een uniek, opeenvolgend factuurnummer</li>
        </ul>
      </div>
      <div class="info-card">
        <ul class="check-list">
          <li>De factuurdatum</li>
          <li>Omschrijving en hoeveelheid van wat je leverde</li>
          <li>Bedrag exclusief btw, per btw-tarief</li>
          <li>Het btw-tarief en het btw-bedrag</li>
          <li>Bij verlegging of vrijstelling: de juiste vermelding</li>
        </ul>
      </div>
    </div>
    <p style="text-align:center;color:var(--text-3);font-size:13.5px;max-width:640px;margin:24px auto 0;">
      Meer weten? Lees onze uitleg over <a href="{{ route('kennisbank.artikel', 'factuureisen') }}" style="color:var(--brand);font-weight:500;">de factuureisen van de Belastingdienst</a> en <a href="{{ route('kennisbank.artikel', 'factuurnummering') }}" style="color:var(--brand);font-weight:500;">de regels voor factuurnummers</a>.
    </p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <h2>Veelgestelde vragen</h2>
    </div>
    <div class="faq-list">
      <details class="faq-item">
        <summary>Is deze factuur-generator echt gratis? <svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Ja, helemaal. Geen account, geen watermerk, geen verborgen kosten. EasyInvoice is het betaalde facturatieprogramma erachter — handig zodra je vaker factureert — maar deze tool blijft gewoon gratis.</div>
      </details>
      <details class="faq-item">
        <summary>Worden mijn gegevens opgeslagen? <svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Nee. De gegevens die je invult worden alleen gebruikt om de PDF te genereren en daarna direct vergeten. Alleen je eigen bedrijfsgegevens worden in je eigen browser bewaard, zodat je ze de volgende keer niet opnieuw hoeft in te typen.</div>
      </details>
      <details class="faq-item">
        <summary>Voldoet de factuur aan de eisen van de Belastingdienst? <svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Ja — als je alle velden invult bevat de PDF alle verplichte onderdelen: jullie beider gegevens, KvK- en btw-nummer, factuurnummer en -datum, omschrijving, bedragen per btw-tarief en het btw-bedrag. Ook btw verlegd en vrijstelling (KOR) worden correct vermeld.</div>
      </details>
      <details class="faq-item">
        <summary>Kan ik ook offertes maken of facturen automatisch laten herinneren? <svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Daarvoor is er EasyInvoice zelf: offertes met digitale ondertekening, automatische betalingsherinneringen, btw-overzicht voor je aangifte, urenregistratie en meer. Je probeert het <a href="{{ route('register') }}">14 dagen gratis</a>, of kijk eerst rond in de <a href="{{ route('demo') }}">demo</a>.</div>
      </details>
    </div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Vaker facturen sturen?</h2>
    <p>Met EasyInvoice verstuur je facturen in een paar klikken, herinnert het systeem je klanten automatisch en staat je btw-overzicht altijd klaar. Vanaf € 12,10 per maand, incl. btw.</p>
    <div class="hero-ctas">
      <a href="{{ route('register') }}" class="btn btn-white btn-lg">Probeer 14 dagen gratis →</a>
      <a href="{{ route('demo') }}" class="btn btn-lg" style="background:rgba(255,255,255,0.15);color:white;border-color:rgba(255,255,255,0.3);">Bekijk de demo</a>
    </div>
  </div>
</section>

<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "FAQPage",
  "mainEntity": [
    {
      "@@type": "Question",
      "name": "Is deze factuur-generator echt gratis?",
      "acceptedAnswer": { "@@type": "Answer", "text": "Ja, helemaal. Geen account, geen watermerk, geen verborgen kosten. EasyInvoice is het betaalde facturatieprogramma erachter, maar deze tool blijft gratis." }
    },
    {
      "@@type": "Question",
      "name": "Worden mijn gegevens opgeslagen?",
      "acceptedAnswer": { "@@type": "Answer", "text": "Nee. De ingevulde gegevens worden alleen gebruikt om de PDF te genereren en daarna direct vergeten." }
    },
    {
      "@@type": "Question",
      "name": "Voldoet de factuur aan de eisen van de Belastingdienst?",
      "acceptedAnswer": { "@@type": "Answer", "text": "Ja — de PDF bevat alle verplichte onderdelen: gegevens van beide partijen, KvK- en btw-nummer, factuurnummer en -datum, omschrijving, bedragen per btw-tarief en het btw-bedrag. Ook btw verlegd en vrijstelling (KOR) worden correct vermeld." }
    }
  ]
}
</script>

<script>
(function () {
  var body = document.getElementById('linesBody');
  var addBtn = document.getElementById('addLine');
  var btwType = document.getElementById('btw_type');

  function parseNum(value) {
    value = String(value || '').trim();
    if (value.indexOf(',') !== -1) value = value.replace(/\./g, '').replace(',', '.');
    var n = parseFloat(value);
    return isNaN(n) ? 0 : n;
  }
  function euro(n) {
    return '€ ' + n.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  function recalc() {
    var sub = 0, vat = 0;
    var normal = btwType.value === 'normaal';
    body.querySelectorAll('.line-row').forEach(function (row) {
      var amount = parseNum(row.querySelector('.js-qty').value) * parseNum(row.querySelector('.js-price').value);
      amount = Math.round(amount * 100) / 100;
      sub += amount;
      if (normal) vat += Math.round(amount * parseFloat(row.querySelector('.js-vat').value) / 100 * 100) / 100;
      row.querySelector('.js-line-total').textContent = euro(amount);
      row.querySelector('.js-vat').disabled = !normal;
    });
    document.getElementById('tSub').textContent = euro(sub);
    document.getElementById('tVat').textContent = euro(vat);
    document.getElementById('tVatRow').style.display = normal ? '' : 'none';
    document.getElementById('tTotal').textContent = euro(sub + vat);
  }

  function renumber() {
    body.querySelectorAll('.line-row').forEach(function (row, i) {
      row.querySelectorAll('input, select').forEach(function (field) {
        field.name = field.name.replace(/regels\[\d+\]/, 'regels[' + i + ']');
      });
    });
  }

  addBtn.addEventListener('click', function () {
    var first = body.querySelector('.line-row');
    var clone = first.cloneNode(true);
    clone.querySelectorAll('input').forEach(function (input) { input.value = ''; });
    clone.querySelector('.js-qty').value = '1';
    clone.querySelector('.js-vat').selectedIndex = 0;
    body.appendChild(clone);
    renumber();
    recalc();
    clone.querySelector('input').focus();
  });

  body.addEventListener('click', function (e) {
    if (!e.target.classList.contains('js-del')) return;
    if (body.querySelectorAll('.line-row').length > 1) {
      e.target.closest('.line-row').remove();
      renumber();
      recalc();
    }
  });

  body.addEventListener('input', recalc);
  body.addEventListener('change', recalc);
  btwType.addEventListener('change', recalc);

  // Onthoud alleen je eigen bedrijfsgegevens — lokaal, in je eigen browser.
  var ownFields = ['van_bedrijf', 'van_email', 'van_adres', 'van_kvk', 'van_btw', 'van_iban'];
  try {
    var saved = JSON.parse(localStorage.getItem('ei_gratis_factuur') || '{}');
    ownFields.forEach(function (id) {
      var el = document.getElementById(id);
      if (el && !el.value && saved[id]) el.value = saved[id];
    });
  } catch (e) {}

  document.getElementById('genForm').addEventListener('submit', function () {
    try {
      var data = {};
      ownFields.forEach(function (id) {
        var el = document.getElementById(id);
        if (el) data[id] = el.value;
      });
      localStorage.setItem('ei_gratis_factuur', JSON.stringify(data));
    } catch (e) {}
  });

  // Suggestie voor het factuurnummer als het veld leeg is.
  var nr = document.getElementById('factuurnummer');
  if (nr && !nr.value) nr.value = new Date().getFullYear() + '-001';

  recalc();
})();
</script>
@endsection
