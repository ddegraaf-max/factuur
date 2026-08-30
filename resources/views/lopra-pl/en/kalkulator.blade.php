@extends('layouts.marketing')

{{-- Interest and compensation calculator (lead magnet for Lopra Polska + sprzedamfakture.pl), English version: calculates in the browser the days overdue, statutory interest for late payment in commercial transactions (market('interest_rate')) and the 40/70/100 EUR compensation (market('eur_pln')); the button generates a printable payment demand (wezwanie do zapłaty), which stays in Polish because it is addressed to a Polish debtor. --}}

@section('title', 'Late-payment interest and compensation calculator — ' . brand('name'))
@section('description', 'Free calculator: count the days overdue, the statutory interest for late payment in commercial transactions and the 40/70/100 EUR recovery-cost compensation on an unpaid B2B invoice in Poland. Generate a ready-to-print payment demand (wezwanie do zapłaty).')

@php
  $rate = (float) market('interest_rate', 0.14);
  $eurPln = (float) market('eur_pln', 4.30);
  $ratePct = rtrim(rtrim(number_format($rate * 100, 2, '.', ''), '0'), '.');
@endphp

@push('styles')
<style>
  .calc-grid { display: grid; grid-template-columns: 1.1fr 1fr; gap: 24px; align-items: start; max-width: 1040px; margin: 0 auto; }
  @media (max-width: 860px) { .calc-grid { grid-template-columns: 1fr; } }
  .calc-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 28px; box-shadow: var(--shadow-sm); }
  .calc-card h2 { font-size: 19px; margin-bottom: 16px; }
  .calc-hint { font-size: 12.5px; color: var(--text-3); margin-top: 4px; line-height: 1.5; }
  .calc-result { background: var(--brand-tint); border: 1px solid var(--brand-border); border-radius: 12px; padding: 20px 22px; }
  .calc-result .cap { display: flex; justify-content: space-between; align-items: center; font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-3); margin-bottom: 10px; }
  .calc-result .pill { font-size: 10.5px; font-weight: 700; color: #B45309; background: #FEF3C7; border-radius: 100px; padding: 3px 9px; }
  .calc-result table { width: 100%; border-collapse: collapse; font-size: 14.5px; }
  .calc-result td { padding: 9px 0; border-top: 1px solid var(--brand-border); vertical-align: top; }
  .calc-result td.r { text-align: right; font-family: var(--font-mono); font-size: 13.5px; white-space: nowrap; }
  .calc-result td small { display: block; font-size: 12px; color: var(--text-3); }
  .calc-result tr.tot td { border-top: 2px solid var(--brand); font-weight: 700; font-size: 16px; }
  .calc-result tr.tot td.r { color: var(--brand); font-size: 16px; }
  .calc-note { font-size: 12.5px; color: var(--text-3); margin-top: 12px; line-height: 1.55; }
  .calc-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 18px; }
  .calc-actions .btn { white-space: normal; }
  .calc-warn { display: none; background: var(--surface-2); border: 1px solid var(--border); border-radius: 10px; padding: 10px 14px; font-size: 13.5px; color: var(--text-2); margin-top: 12px; }
  .calc-warn.show { display: block; }

  /* Payment demand (wezwanie do zapłaty) — on-screen preview and print */
  #wezwanie { padding: 40px 0 80px; }
  .wz-toolbar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; max-width: 820px; margin: 0 auto 18px; }
  .wz-toolbar .btns { display: flex; flex-wrap: wrap; gap: 10px; }
  .wz-toolbar .info { font-size: 13px; color: var(--text-3); }
  .wz-doc { background: #fff; color: #111; border: 1px solid var(--border); border-radius: 14px; box-shadow: var(--shadow-md); max-width: 820px; margin: 0 auto; padding: 48px 56px; font-family: var(--font-body); font-size: 14.5px; line-height: 1.6; }
  .wz-doc h2 { font-family: var(--font-display); font-size: 24px; letter-spacing: 0.02em; text-transform: uppercase; margin: 0 0 6px; color: #111; }
  .wz-doc .sub { font-size: 13px; color: #555; margin-bottom: 26px; }
  .wz-head { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; margin-bottom: 26px; }
  @media (max-width: 600px) { .wz-head { grid-template-columns: 1fr; } .wz-doc { padding: 28px 22px; } }
  .wz-party .lbl { font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #777; margin-bottom: 6px; }
  .wz-doc input, .wz-doc textarea { width: 100%; border: 1px dashed #bbb; border-radius: 6px; background: #FFFDF5; font: inherit; color: inherit; padding: 6px 8px; margin-bottom: 6px; }
  .wz-doc input:focus, .wz-doc textarea:focus { outline: none; border-color: var(--brand); background: #fff; }
  .wz-doc input.inline { display: inline-block; width: auto; min-width: 120px; margin: 0 2px; padding: 2px 6px; }
  .wz-doc textarea { resize: vertical; min-height: 54px; }
  .wz-doc p { margin: 0 0 12px; }
  .wz-doc table.wz-sum { width: 100%; border-collapse: collapse; margin: 14px 0 18px; font-size: 14px; }
  .wz-doc table.wz-sum td { padding: 8px 0; border-bottom: 1px solid #ddd; }
  .wz-doc table.wz-sum td.r { text-align: right; font-family: var(--font-mono); font-size: 13px; white-space: nowrap; }
  .wz-doc table.wz-sum tr.tot td { border-bottom: none; border-top: 2px solid #111; font-weight: 700; font-size: 15px; }
  .wz-doc .legal { font-size: 12px; color: #555; margin-top: 18px; }
  .wz-doc .sign { margin-top: 44px; display: flex; justify-content: flex-end; }
  .wz-doc .sign div { width: 260px; border-top: 1px solid #111; padding-top: 6px; font-size: 12px; color: #555; text-align: center; }

  @media print {
    @page { margin: 18mm 16mm; }
    body > *:not(#wezwanie) { display: none !important; }
    #wezwanie { display: block !important; padding: 0; }
    .wz-toolbar { display: none !important; }
    .wz-doc { box-shadow: none; border: none; border-radius: 0; max-width: none; padding: 0; font-size: 12.5pt; }
    .wz-doc input, .wz-doc textarea { border: none; background: transparent; padding: 0; margin: 0 0 2px; }
    .wz-doc input.inline { min-width: 0; }
    .wz-doc input::placeholder, .wz-doc textarea::placeholder { color: transparent; }
  }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <span class="eyebrow">Free tool</span>
    <h1>Interest and compensation calculator</h1>
    <p class="lead">A client hasn't paid an invoice on time? Work out what you are actually owed: statutory interest for late payment in commercial transactions (odsetki ustawowe) plus 40, 70 or 100 EUR recovery-cost compensation. The payment demand letter is one click away.</p>
  </div>
</section>

<section class="section" style="padding-top:36px;">
  <div class="container">
    <div class="calc-grid">
      <div class="calc-card">
        <h2>Invoice details</h2>
        <div class="m-field">
          <label for="kwota">Invoice amount, gross (zł)</label>
          <input type="text" id="kwota" inputmode="decimal" value="2 420,00" autocomplete="off">
          <div class="calc-hint">The amount still outstanding. If part has been paid, enter only the unpaid balance.</div>
        </div>
        <div class="m-row-2">
          <div class="m-field">
            <label for="termin">Payment due date</label>
            <input type="date" id="termin">
            <div class="calc-hint">The date on the invoice or in the contract.</div>
          </div>
          <div class="m-field">
            <label for="data">Payment date / today</label>
            <input type="date" id="data">
            <div class="calc-hint">Interest is calculated up to and including this day.</div>
          </div>
        </div>
        <div class="m-row-2">
          <div class="m-field">
            <label for="typ">Type of transaction</label>
            <select id="typ">
              <option value="b2b" selected>Commercial transaction between businesses (B2B)</option>
            </select>
            <div class="calc-hint">The calculator covers transactions between businesses. Transactions with consumers carry a different interest rate and no compensation.</div>
          </div>
          <div class="m-field">
            <label for="stopa">Interest rate (% per year)</label>
            <input type="text" id="stopa" inputmode="decimal" value="{{ $ratePct }}" autocomplete="off">
            <div class="calc-hint">NBP reference rate + 10 percentage points, announced every six months by the Minister of Finance. Change it if a different rate applies.</div>
          </div>
        </div>
      </div>

      <div class="calc-card">
        <h2>Your claim</h2>
        <div class="calc-result">
          <div class="cap"><span>As at <span id="outData">—</span></span><span class="pill"><span id="outDni">0</span> days overdue</span></div>
          <table>
            <tr><td>Principal amount</td><td class="r" id="outKwota">0,00 zł</td></tr>
            <tr><td>Statutory interest for late payment<small><span id="outStopa">{{ $ratePct }}</span>% × <span id="outDni2">0</span> days / 365</small></td><td class="r" id="outOdsetki">0,00 zł</td></tr>
            <tr><td>Recovery-cost compensation<small>Art. 10 of the Act · <span id="outEur">40</span> EUR × {{ number_format($eurPln, 2, ',', ' ') }} zł</small></td><td class="r" id="outRek">0,00 zł</td></tr>
            <tr class="tot"><td>Total payable</td><td class="r" id="outRazem">0,00 zł</td></tr>
          </table>
        </div>
        <div class="calc-warn" id="warnBrak">The payment deadline has not passed yet — interest and compensation only accrue from the day after the due date.</div>
        <div class="calc-note">The calculator is an aid, not legal advice. Compensation is converted at the NBP average euro rate from the last working day of the month preceding the month in which the amount fell due; here we assume a rate of {{ number_format($eurPln, 2, ',', ' ') }} zł. Interest is rounded to the nearest grosz.</div>
        <div class="calc-actions">
          <button type="button" class="btn btn-primary btn-lg" id="btnWezwanie">Generate a payment demand (wezwanie do zapłaty)</button>
        </div>
        <div class="calc-actions" style="margin-top:10px;">
          <a href="{{ route('register') }}" class="btn btn-secondary">Create an account with {{ brand('name') }} — demand letters in one click</a>
          <a href="https://sprzedamfakture.pl" target="_blank" rel="noopener" class="btn btn-secondary">Hand the case to sprzedamfakture.pl</a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="prose">
      <h2>Legal basis</h2>
      <p>All three items derive from the <strong>Act of 8 March 2013 on counteracting excessive delays in commercial transactions</strong> (ustawa o przeciwdziałaniu nadmiernym opóźnieniom w transakcjach handlowych). It applies to commercial transactions, i.e. contracts for the supply of goods or services concluded between businesses (and with public bodies) — not to contracts with consumers.</p>
      <ul>
        <li><strong>Art. 4a</strong> — in commercial transactions the "ordinary" statutory interest for late payment under the Civil Code does not apply. The higher <em>statutory interest for late payment in commercial transactions</em> applies instead: the NBP reference rate plus 10 percentage points (8 p.p. where the debtor is a healthcare provider). The rate is announced by the Minister of Finance for each half-year.</li>
        <li><strong>Art. 7</strong> — interest is due to the creditor <em>without any demand</em>, for the period from the day the payment fell due (the day after the due date) until the day of payment, provided the creditor has performed its side of the contract and has not been paid on time. The parties cannot contract out of this right. As a rule, the payment term in a contract between businesses may not exceed 60 days.</li>
        <li><strong>Art. 10</strong> — from the day the right to interest arises, the creditor is entitled, without any demand, to flat-rate <em>compensation for recovery costs</em>: <strong>40 EUR</strong> for claims up to 5,000 zł, <strong>70 EUR</strong> from 5,000 zł to 50,000 zł and <strong>100 EUR</strong> above 50,000 zł — for each transaction (as a rule, for each invoice). The amount is converted into złoty at the average euro rate announced by the NBP on the last working day of the month preceding the month in which the claim fell due. If your actual recovery costs (e.g. a debt collection agency) are higher, you can also claim the difference.</li>
      </ul>

      <h2>How the calculator works</h2>
      <p><strong>Days overdue</strong> is the number of days from the due date to the payment date you enter. <strong>Interest</strong> = amount × annual rate × days ÷ 365. <strong>Compensation</strong> depends on the size of the principal amount (40 / 70 / 100 EUR) and is converted at the assumed euro rate. <strong>Total</strong> is the sum of the principal, interest and compensation — the figure you can put in the payment demand.</p>
      <p>Interest and compensation are claims you do not need to announce in the contract or on the invoice. In practice many businesses never claim them — yet they are exactly what makes paying late stop being worthwhile for the debtor.</p>

      <h2>What if the demand letter doesn't work?</h2>
      <p>A payment demand with a 7-day deadline is the standard pre-litigation step. If the debtor still does not pay, <a href="https://sprzedamfakture.pl" target="_blank" rel="noopener">sprzedamfakture.pl</a> can take over the case: amicable debt collection, an entry in a debtor register (KRD/BIG) and, if necessary, court proceedings and enforcement — with the costs charged to the debtor. Need the money right away? You can submit the invoice for purchase and receive an assignment offer within one working day.</p>
      <p>In {{ brand('name') }} the whole process is built into invoicing: reminders go out automatically, the demand letter with interest and compensation is created in one click, and handing the case to sprzedamfakture.pl requires no retyping. <a href="{{ route('register') }}">Try it free for 14 days</a>.</p>
    </div>
  </div>
</section>

<!-- PAYMENT DEMAND (WEZWANIE DO ZAPŁATY) — shown after clicking, printable; the letter itself stays in Polish -->
<section id="wezwanie" hidden>
  <div class="container">
    <div class="wz-toolbar">
      <div class="info">The demand letter is generated in Polish, as it is addressed to a Polish debtor. Fill in the fields on the dashed background — the amounts are carried over from the calculator. When printed, the fields become plain text.</div>
      <div class="btns">
        <button type="button" class="btn btn-primary" id="btnDrukuj">Print / save as PDF</button>
        <button type="button" class="btn btn-ghost" id="btnZamknij">Close</button>
      </div>
    </div>

    <div class="wz-doc">
      <div class="wz-head">
        <div class="wz-party">
          <div class="lbl">Wierzyciel</div>
          <input type="text" id="wzWierzNazwa" placeholder="Your company name (creditor)">
          <textarea id="wzWierzAdres" placeholder="Street and number, postcode, town"></textarea>
          <input type="text" id="wzWierzNip" placeholder="NIP (tax ID)">
        </div>
        <div class="wz-party">
          <div class="lbl">Dłużnik</div>
          <input type="text" id="wzDlNazwa" placeholder="Debtor's company name">
          <textarea id="wzDlAdres" placeholder="Street and number, postcode, town"></textarea>
          <input type="text" id="wzDlNip" placeholder="NIP (tax ID)">
        </div>
      </div>

      <p style="text-align:right;"><input type="text" class="inline" id="wzMiejsce" placeholder="Town (place of issue)" style="min-width:140px;">, dnia <span id="wzDataDzis">—</span></p>

      <h2>Wezwanie do zapłaty</h2>
      <div class="sub">Przedsądowe wezwanie do zapłaty na podstawie art. 7 i art. 10 ustawy z dnia 8 marca 2013 r. o przeciwdziałaniu nadmiernym opóźnieniom w transakcjach handlowych</div>

      <p>Działając w imieniu wierzyciela, wzywam do zapłaty należności wynikającej z faktury nr <input type="text" class="inline" id="wzFaktura" placeholder="e.g. FV/2026/0004"> wystawionej dnia <input type="text" class="inline" id="wzFakturaData" placeholder="dd.mm.yyyy" style="min-width:110px;">, której termin płatności upłynął dnia <span id="wzTermin">—</span>. Do dnia dzisiejszego należność nie została uregulowana.</p>

      <p>Na dzień <span id="wzDataStan">—</span> zaległość wynosi:</p>
      <table class="wz-sum">
        <tr><td>Należność główna</td><td class="r" id="wzKwota">0,00 zł</td></tr>
        <tr><td>Odsetki ustawowe za opóźnienie w transakcjach handlowych (<span id="wzStopa">{{ $ratePct }}</span>% w stosunku rocznym, <span id="wzDni">0</span> dni)</td><td class="r" id="wzOdsetki">0,00 zł</td></tr>
        <tr><td>Rekompensata za koszty odzyskiwania należności (art. 10 ust. 1 ustawy; równowartość <span id="wzEur">40</span> EUR)</td><td class="r" id="wzRek">0,00 zł</td></tr>
        <tr class="tot"><td>Razem do zapłaty</td><td class="r" id="wzRazem">0,00 zł</td></tr>
      </table>

      <p>Wzywam do zapłaty powyższej kwoty w terminie <strong>7 dni</strong> od dnia otrzymania niniejszego wezwania, na rachunek bankowy nr <input type="text" class="inline" id="wzIban" placeholder="bank account number" style="min-width:260px;">, z podaniem numeru faktury w tytule przelewu. Odsetki naliczane są nadal, do dnia zapłaty.</p>

      <p>W przypadku braku zapłaty w wyznaczonym terminie sprawa zostanie bez dalszych wezwań przekazana do windykacji oraz skierowana na drogę postępowania sądowego, co narazi Państwa na dodatkowe koszty — w tym koszty procesu, zastępstwa procesowego i egzekucji — a także może skutkować wpisem do rejestru dłużników.</p>

      <p>Jeżeli należność została uregulowana przed otrzymaniem niniejszego pisma, proszę uznać je za nieaktualne i przesłać potwierdzenie przelewu.</p>

      <div class="sign"><div>podpis wierzyciela / osoby upoważnionej</div></div>

      <div class="legal">Odsetki ustawowe za opóźnienie w transakcjach handlowych oraz rekompensata za koszty odzyskiwania należności przysługują wierzycielowi bez wezwania (art. 7 ust. 1 i art. 10 ust. 1 ustawy z dnia 8 marca 2013 r. o przeciwdziałaniu nadmiernym opóźnieniom w transakcjach handlowych). Wyliczenie odsetek: kwota × stopa roczna × liczba dni opóźnienia ÷ 365.</div>
    </div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Stop working this out by hand for every invoice</h2>
    <p>In {{ brand('name') }} reminders go out on their own, the demand letter with interest and compensation is created in one click, and sprzedamfakture.pl is one step away — with no retyping.</p>
    <div class="hero-ctas">
      <a href="{{ route('register') }}" class="btn btn-white btn-lg">Try it free for 14 days →</a>
      <a href="https://sprzedamfakture.pl" target="_blank" rel="noopener" class="btn btn-lg" style="background:rgba(255,255,255,0.15);color:white;border-color:rgba(255,255,255,0.3);">Hand the case to sprzedamfakture.pl</a>
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
      "name": "What is the statutory interest rate for late payment in commercial transactions in Poland?",
      "acceptedAnswer": { "@@type": "Answer", "text": "The NBP reference rate plus 10 percentage points per year (8 p.p. where the debtor is a healthcare provider). The rate is announced by the Minister of Finance for each half-year. Interest runs from the day after the due date until the day of payment: amount × rate × number of days ÷ 365." }
    },
    {
      "@@type": "Question",
      "name": "How much is the compensation for recovery costs?",
      "acceptedAnswer": { "@@type": "Answer", "text": "The equivalent of 40 EUR for claims up to 5,000 zł, 70 EUR from 5,000 zł to 50,000 zł and 100 EUR above 50,000 zł — for each commercial transaction, without any demand, converted at the NBP average euro rate from the last working day of the month preceding the month in which the claim fell due (Art. 10 of the Act of 8 March 2013)." }
    },
    {
      "@@type": "Question",
      "name": "Do interest and compensation apply to transactions with consumers?",
      "acceptedAnswer": { "@@type": "Answer", "text": "No. The Act on counteracting excessive delays in commercial transactions applies to contracts between businesses (and with public bodies). Towards consumers, the statutory interest for late payment under the Civil Code applies and no compensation is due." }
    }
  ]
}
</script>

<script>
(function () {
  var RATE_DEFAULT = {{ json_encode($rate) }};
  var EUR_PLN = {{ json_encode($eurPln) }};
  var NBSP = '\u00A0';

  function $(id) { return document.getElementById(id); }

  function parseNum(value) {
    value = String(value || '').replace(/\s/g, '').replace(/zł|%/gi, '');
    if (value.indexOf(',') !== -1) value = value.replace(/\./g, '').replace(',', '.');
    var n = parseFloat(value);
    return isNaN(n) ? 0 : n;
  }

  function fmt(n) {
    var neg = n < 0; n = Math.abs(Math.round(n * 100) / 100);
    var parts = n.toFixed(2).split('.');
    var int = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, NBSP);
    return (neg ? '-' : '') + int + ',' + parts[1] + NBSP + 'zł';
  }

  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function toIso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
  function fromIso(s) { if (!s) return null; var p = s.split('-'); if (p.length !== 3) return null; var d = new Date(Date.UTC(+p[0], +p[1] - 1, +p[2])); return isNaN(d.getTime()) ? null : d; }
  function fmtDate(s) { var d = fromIso(s); return d ? pad(d.getUTCDate()) + '.' + pad(d.getUTCMonth() + 1) + '.' + d.getUTCFullYear() : '—'; }

  function rekEur(kwota) { if (kwota <= 5000) return 40; if (kwota <= 50000) return 70; return 100; }

  function calc() {
    var kwota = Math.max(0, parseNum($('kwota').value));
    var stopaPct = parseNum($('stopa').value);
    var rate = stopaPct > 0 ? stopaPct / 100 : RATE_DEFAULT;
    var termin = fromIso($('termin').value);
    var data = fromIso($('data').value);
    var dni = (termin && data) ? Math.floor((data - termin) / 86400000) : 0;
    if (dni < 0) dni = 0;
    var late = dni > 0;
    var odsetki = late ? Math.round(kwota * rate * dni / 365 * 100) / 100 : 0;
    var eur = rekEur(kwota);
    var rek = late && kwota > 0 ? Math.round(eur * EUR_PLN * 100) / 100 : 0;
    var razem = kwota + odsetki + rek;
    return { kwota: kwota, rate: rate, stopaPct: stopaPct > 0 ? stopaPct : RATE_DEFAULT * 100, dni: dni, late: late, odsetki: odsetki, eur: eur, rek: rek, razem: razem };
  }

  function fmtPct(p) { return String(Math.round(p * 100) / 100).replace('.', ','); }

  function render() {
    var r = calc();
    $('outData').textContent = fmtDate($('data').value);
    $('outDni').textContent = r.dni;
    $('outDni2').textContent = r.dni;
    $('outStopa').textContent = fmtPct(r.stopaPct);
    $('outKwota').textContent = fmt(r.kwota);
    $('outOdsetki').textContent = fmt(r.odsetki);
    $('outEur').textContent = r.eur;
    $('outRek').textContent = fmt(r.rek);
    $('outRazem').textContent = fmt(r.razem);
    $('warnBrak').classList.toggle('show', !r.late && !!$('termin').value);
    return r;
  }

  function fillWezwanie() {
    var r = render();
    $('wzDataDzis').textContent = fmtDate(toIso(new Date()));
    $('wzDataStan').textContent = fmtDate($('data').value);
    $('wzTermin').textContent = fmtDate($('termin').value);
    $('wzKwota').textContent = fmt(r.kwota);
    $('wzStopa').textContent = fmtPct(r.stopaPct);
    $('wzDni').textContent = r.dni;
    $('wzOdsetki').textContent = fmt(r.odsetki);
    $('wzEur').textContent = r.eur;
    $('wzRek').textContent = fmt(r.rek);
    $('wzRazem').textContent = fmt(r.razem);
  }

  // Starting values: today, and a due date 44 days ago (the example from the home page).
  var today = new Date();
  var due = new Date(today.getTime() - 44 * 86400000);
  $('data').value = toIso(today);
  $('termin').value = toIso(due);

  ['kwota', 'termin', 'data', 'stopa', 'typ'].forEach(function (id) {
    $(id).addEventListener('input', render);
    $(id).addEventListener('change', render);
  });
  $('kwota').addEventListener('blur', function () { var v = parseNum(this.value); if (v > 0) this.value = fmt(v).replace(NBSP + 'zł', ''); });

  $('btnWezwanie').addEventListener('click', function () {
    fillWezwanie();
    var wz = $('wezwanie');
    wz.hidden = false;
    wz.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
  $('btnDrukuj').addEventListener('click', function () { fillWezwanie(); window.print(); });
  $('btnZamknij').addEventListener('click', function () { $('wezwanie').hidden = true; $('btnWezwanie').scrollIntoView({ behavior: 'smooth', block: 'center' }); });

  render();
})();
</script>
@endsection
