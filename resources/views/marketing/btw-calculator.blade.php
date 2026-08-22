@extends('layouts.marketing')

@section('title', 'Btw-calculator — btw berekenen (21%, 9% of 0%) — EasyInvoice')
@section('description', 'Bereken direct de btw over een bedrag: van exclusief naar inclusief én andersom, met 21% of 9%. Gratis btw-calculator voor Nederlandse ondernemers, met uitleg.')

@push('styles')
<style>
  .calc-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 32px; max-width: 560px; margin: 0 auto; box-shadow: var(--shadow-sm); }
  .calc-result { background: var(--surface-2); border-radius: 12px; padding: 20px 22px; margin-top: 20px; }
  .calc-result .row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 15px; color: var(--text-2); }
  .calc-result .grand { font-weight: 700; font-size: 20px; color: var(--text); border-top: 2px solid var(--text); margin-top: 8px; padding-top: 12px; }
  .seg { display: flex; border: 1px solid var(--border-strong); border-radius: 10px; overflow: hidden; margin-bottom: 16px; }
  .seg label { flex: 1; text-align: center; padding: 11px 8px; font-size: 14px; font-weight: 600; color: var(--text-2); cursor: pointer; background: var(--surface); }
  .seg input { display: none; }
  .seg input:checked + label { background: var(--brand); color: white; }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <span class="eyebrow">Gratis tool</span>
    <h1>Btw-calculator</h1>
    <p class="lead">Reken een bedrag om van exclusief naar inclusief btw — of haal juist de btw uit een totaalbedrag. Met het hoge (21%) en lage (9%) tarief.</p>
  </div>
</section>

<section class="section" style="padding-top:36px;">
  <div class="container">
    <div class="calc-card">
      <div class="seg">
        <input type="radio" name="richting" id="richtingEx" value="ex" checked>
        <label for="richtingEx">Excl. → incl. btw</label>
        <input type="radio" name="richting" id="richtingIn" value="in">
        <label for="richtingIn">Incl. → excl. btw</label>
      </div>
      <div class="m-row-2">
        <div class="m-field">
          <label for="bedrag" id="bedragLabel">Bedrag exclusief btw</label>
          <input type="text" id="bedrag" inputmode="decimal" placeholder="100,00" autofocus>
        </div>
        <div class="m-field">
          <label for="tarief">Btw-tarief</label>
          <select id="tarief">
            <option value="21">21% (algemeen tarief)</option>
            <option value="9">9% (verlaagd tarief)</option>
            <option value="0">0%</option>
          </select>
        </div>
      </div>
      <div class="calc-result">
        <div class="row"><span id="lblEx">Exclusief btw</span><span id="outEx">€ 0,00</span></div>
        <div class="row"><span id="lblVat">Btw (21%)</span><span id="outVat">€ 0,00</span></div>
        <div class="row grand"><span id="lblIn">Inclusief btw</span><span id="outIn">€ 0,00</span></div>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="prose">
      <h2>Zo bereken je btw — zonder de klassieke fout</h2>
      <p><strong>Van exclusief naar inclusief</strong> is simpel: vermenigvuldig met 1,21 (bij 21%) of 1,09 (bij 9%). Dus € 100 exclusief wordt € 121 inclusief.</p>
      <p><strong>Van inclusief naar exclusief</strong> gaat vaak mis. Je mag níet 21% van het totaalbedrag aftrekken — je moet délen door 1,21. Van € 121 inclusief is het exclusieve bedrag € 121 ÷ 1,21 = € 100, en de btw € 21. Wie "21% eraf" rekent, komt op € 95,59 uit en zit ernaast.</p>
      <h2>Welk tarief gebruik je?</h2>
      <p>Het <strong>algemene tarief van 21%</strong> geldt voor de meeste diensten en producten — ook voor vrijwel al het werk van zzp'ers zoals ontwerp, advies, bouw en IT. Het <strong>verlaagde tarief van 9%</strong> geldt onder meer voor voedingsmiddelen, boeken, kappers en fietsenmakers. Het <strong>0%-tarief</strong> is vooral voor export en internationale diensten binnen de EU (btw verlegd). Twijfel je? Check de site van de Belastingdienst of vraag het je boekhouder.</p>
      <p>Factureer je regelmatig? In <a href="{{ url('/') }}">EasyInvoice</a> kies je het tarief per factuurregel en staat je <a href="{{ route('kennisbank.artikel', 'btw-tarieven') }}">btw-overzicht voor de aangifte</a> automatisch klaar. Of maak eerst eens <a href="{{ route('gratis-factuur') }}">gratis een factuur</a>.</p>
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
      "name": "Hoe haal ik de btw uit een bedrag inclusief btw?",
      "acceptedAnswer": { "@@type": "Answer", "text": "Deel het bedrag inclusief btw door 1,21 (bij 21%) of 1,09 (bij 9%). Van € 121 inclusief is het exclusieve bedrag € 100 en de btw € 21. Trek dus niet simpelweg 21% van het totaal af — dat geeft een verkeerd bedrag." }
    },
    {
      "@@type": "Question",
      "name": "Wanneer gebruik ik 21% en wanneer 9% btw?",
      "acceptedAnswer": { "@@type": "Answer", "text": "21% is het algemene tarief en geldt voor de meeste producten en diensten. 9% geldt onder meer voor voedingsmiddelen, boeken en kappers. 0% is vooral voor export en intracommunautaire leveringen." }
    }
  ]
}
</script>

<script>
(function () {
  var bedrag = document.getElementById('bedrag');
  var tarief = document.getElementById('tarief');

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
    var rate = parseFloat(tarief.value) / 100;
    var incl = document.getElementById('richtingIn').checked;
    var input = parseNum(bedrag.value);
    var ex, vat;
    if (incl) { ex = input / (1 + rate); vat = input - ex; }
    else { ex = input; vat = input * rate; }
    document.getElementById('bedragLabel').textContent = incl ? 'Bedrag inclusief btw' : 'Bedrag exclusief btw';
    document.getElementById('lblVat').textContent = 'Btw (' + tarief.value + '%)';
    document.getElementById('outEx').textContent = euro(ex);
    document.getElementById('outVat').textContent = euro(vat);
    document.getElementById('outIn').textContent = euro(ex + vat);
  }

  document.querySelectorAll('#bedrag, #tarief, input[name="richting"]').forEach(function (el) {
    el.addEventListener('input', recalc);
    el.addEventListener('change', recalc);
  });
  recalc();
})();
</script>
@endsection
