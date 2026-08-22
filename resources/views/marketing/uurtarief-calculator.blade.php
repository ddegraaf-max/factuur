@extends('layouts.marketing')

@section('title', 'Uurtarief berekenen als zzp\'er — gratis calculator — EasyInvoice')
@section('description', 'Bereken welk uurtarief je als zzp\'er moet vragen op basis van je gewenste inkomen, declarabele uren, vakantie en kosten. Gratis calculator met uitleg.')

@push('styles')
<style>
  .calc-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 32px; max-width: 640px; margin: 0 auto; box-shadow: var(--shadow-sm); }
  .calc-result { background: var(--brand-tint); border: 1px solid var(--brand-border); border-radius: 12px; padding: 22px; margin-top: 20px; text-align: center; }
  .calc-result .big { font-family: var(--font-display); font-weight: 700; font-size: 40px; letter-spacing: -0.02em; color: var(--brand); }
  .calc-result .sub { font-size: 13.5px; color: var(--text-3); margin-top: 4px; }
  .calc-hint { font-size: 12.5px; color: var(--text-3); margin-top: 4px; }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <span class="eyebrow">Gratis tool</span>
    <h1>Uurtarief-calculator voor zzp'ers</h1>
    <p class="lead">Welk uurtarief moet je vragen om uit te komen op het inkomen dat je wilt? Reken het hieronder uit — inclusief vakantie, kosten en niet-declarabele uren.</p>
  </div>
</section>

<section class="section" style="padding-top:36px;">
  <div class="container">
    <div class="calc-card">
      <div class="m-row-2">
        <div class="m-field">
          <label for="inkomen">Gewenst bruto jaarinkomen</label>
          <input type="text" id="inkomen" inputmode="numeric" value="50.000">
          <div class="calc-hint">Vergelijkbaar met een brutosalaris in loondienst, vóór inkomstenbelasting.</div>
        </div>
        <div class="m-field">
          <label for="kosten">Zakelijke kosten per jaar</label>
          <input type="text" id="kosten" inputmode="numeric" value="6.000">
          <div class="calc-hint">Software, verzekeringen (o.a. AOV), pensioen, apparatuur, vervoer.</div>
        </div>
      </div>
      <div class="m-row-2">
        <div class="m-field">
          <label for="uren">Declarabele uren per week</label>
          <input type="text" id="uren" inputmode="numeric" value="28">
          <div class="calc-hint">Uren die je écht kunt factureren — administratie, acquisitie en reistijd betalen niet.</div>
        </div>
        <div class="m-field">
          <label for="vakantie">Weken vrij per jaar</label>
          <input type="text" id="vakantie" inputmode="numeric" value="6">
          <div class="calc-hint">Vakantie, feestdagen en een buffer voor ziekte.</div>
        </div>
      </div>
      <div class="calc-result">
        <div>Je minimale uurtarief</div>
        <div class="big" id="outTarief">€ 0</div>
        <div class="sub">exclusief btw · <span id="outUren">0</span> declarabele uren per jaar</div>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="prose">
      <h2>Zo werkt de berekening</h2>
      <p>Je gewenste jaarinkomen plus je zakelijke kosten is wat je in een jaar moet omzetten. Dat bedrag deel je door je declarabele uren: het aantal weken dat je werkt maal de uren per week die je daadwerkelijk kunt factureren. De uitkomst is je minimale uurtarief — exclusief btw, want de btw op je factuur draag je af aan de Belastingdienst.</p>
      <p>De grootste valkuil: rekenen met 40 declarabele uren per week. In de praktijk gaat al snel een derde van je tijd op aan acquisitie, administratie, offertes en reistijd. Reken je met 40 uur terwijl je er 26 factureert, dan zit je tarief er zomaar 50% naast.</p>
      <h2>Vergeet deze kosten niet</h2>
      <ul>
        <li><strong>Arbeidsongeschiktheidsverzekering (AOV)</strong> — vaak de grootste kostenpost voor zzp'ers.</li>
        <li><strong>Pensioen</strong> — in loondienst betaalt de werkgever mee; als zzp'er sta je er alleen voor.</li>
        <li><strong>Lege periodes</strong> — tussen twee opdrachten zit soms een gat; een buffer hoort in je tarief.</li>
        <li><strong>Belastingen</strong> — over je winst betaal je inkomstenbelasting en Zvw-bijdrage; ondernemersaftrekken (zoals de zelfstandigenaftrek) verzachten dat, maar worden de laatste jaren stap voor stap afgebouwd.</li>
      </ul>
      <p>Wil je achteraf zien wat een klant je per uur écht oplevert? EasyInvoice heeft <a href="{{ url('/') }}#functies">urenregistratie</a> ingebouwd en rekent per klant je effectieve uurtarief uit: omzet gedeeld door bestede uren. Zo zie je precies welke klanten renderen — en waar je tarief omhoog moet.</p>
    </div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Uren schrijven en direct factureren</h2>
    <p>Registreer je uren in EasyInvoice en zet ze met één klik op een factuur. Inclusief btw-overzicht, herinneringen en inzicht in wat elke klant oplevert.</p>
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
      "name": "Hoe bereken ik mijn uurtarief als zzp'er?",
      "acceptedAnswer": { "@@type": "Answer", "text": "Tel je gewenste bruto jaarinkomen en je zakelijke kosten bij elkaar op en deel dat door je declarabele uren per jaar (werkweken × factureerbare uren per week). Reken realistisch: gemiddeld is maar 60 à 70 procent van je tijd declarabel." }
    },
    {
      "@@type": "Question",
      "name": "Is mijn uurtarief inclusief of exclusief btw?",
      "acceptedAnswer": { "@@type": "Answer", "text": "Zakelijke uurtarieven zijn vrijwel altijd exclusief btw. De btw die je factureert draag je af aan de Belastingdienst en is dus geen inkomen." }
    }
  ]
}
</script>

<script>
(function () {
  function parseNum(value) {
    value = String(value || '').trim();
    if (value.indexOf(',') !== -1) value = value.replace(/\./g, '').replace(',', '.');
    else value = value.replace(/\./g, '');
    var n = parseFloat(value);
    return isNaN(n) ? 0 : n;
  }

  function recalc() {
    var inkomen = parseNum(document.getElementById('inkomen').value);
    var kosten = parseNum(document.getElementById('kosten').value);
    var urenPerWeek = parseNum(document.getElementById('uren').value);
    var vakantie = parseNum(document.getElementById('vakantie').value);
    var weken = Math.max(0, 52 - vakantie);
    var jaarUren = Math.round(weken * urenPerWeek);
    var tarief = jaarUren > 0 ? (inkomen + kosten) / jaarUren : 0;
    document.getElementById('outTarief').textContent = '€ ' + (Math.ceil(tarief) || 0);
    document.getElementById('outUren').textContent = jaarUren;
  }

  ['inkomen', 'kosten', 'uren', 'vakantie'].forEach(function (id) {
    document.getElementById(id).addEventListener('input', recalc);
  });
  recalc();
})();
</script>
@endsection
