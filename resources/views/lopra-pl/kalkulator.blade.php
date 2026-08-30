@extends('layouts.marketing')

{{-- Kalkulator odsetek i rekompensaty (lead-magnet Lopra Polska + sprzedamfakture.pl): liczy w przeglądarce dni opóźnienia, odsetki ustawowe za opóźnienie w transakcjach handlowych (market('interest_rate')) i rekompensatę 40/70/100 EUR (market('eur_pln')), a przycisk generuje drukowalne wezwanie do zapłaty. --}}

@section('title', 'Kalkulator odsetek i rekompensaty za opóźnienie w płatności — ' . brand('name'))
@section('description', 'Bezpłatny kalkulator: policz dni opóźnienia, odsetki ustawowe za opóźnienie w transakcjach handlowych i rekompensatę 40/70/100 EUR za niezapłaconą fakturę B2B. Wygeneruj gotowe wezwanie do zapłaty do druku.')

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

  /* Wezwanie do zapłaty — podgląd na ekranie i wydruk */
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
    <span class="eyebrow">Bezpłatne narzędzie</span>
    <h1>Kalkulator odsetek i rekompensaty</h1>
    <p class="lead">Klient nie zapłacił faktury w terminie? Policz, ile naprawdę Ci się należy: odsetki ustawowe za opóźnienie w transakcjach handlowych plus rekompensata 40, 70 lub 100 EUR. Wezwanie do zapłaty wygenerujesz jednym kliknięciem.</p>
  </div>
</section>

<section class="section" style="padding-top:36px;">
  <div class="container">
    <div class="calc-grid">
      <div class="calc-card">
        <h2>Dane faktury</h2>
        <div class="m-field">
          <label for="kwota">Kwota faktury brutto (zł)</label>
          <input type="text" id="kwota" inputmode="decimal" value="2 420,00" autocomplete="off">
          <div class="calc-hint">Kwota, która pozostała do zapłaty. Przy częściowej wpłacie wpisz tylko brakującą część.</div>
        </div>
        <div class="m-row-2">
          <div class="m-field">
            <label for="termin">Termin płatności</label>
            <input type="date" id="termin">
            <div class="calc-hint">Data z faktury lub umowy.</div>
          </div>
          <div class="m-field">
            <label for="data">Data zapłaty / dzień dzisiejszy</label>
            <input type="date" id="data">
            <div class="calc-hint">Odsetki liczymy do tego dnia włącznie.</div>
          </div>
        </div>
        <div class="m-row-2">
          <div class="m-field">
            <label for="typ">Rodzaj transakcji</label>
            <select id="typ">
              <option value="b2b" selected>Transakcja handlowa między firmami (B2B)</option>
            </select>
            <div class="calc-hint">Kalkulator dotyczy transakcji między przedsiębiorcami. W transakcjach z konsumentami obowiązują inne odsetki i nie przysługuje rekompensata.</div>
          </div>
          <div class="m-field">
            <label for="stopa">Stopa odsetek (% rocznie)</label>
            <input type="text" id="stopa" inputmode="decimal" value="{{ $ratePct }}" autocomplete="off">
            <div class="calc-hint">Stopa referencyjna NBP + 10 p.p., ogłaszana co pół roku przez Ministra Finansów. Zmień, jeśli obowiązuje inna wartość.</div>
          </div>
        </div>
      </div>

      <div class="calc-card">
        <h2>Twoje roszczenie</h2>
        <div class="calc-result">
          <div class="cap"><span>Stan na <span id="outData">—</span></span><span class="pill"><span id="outDni">0</span> dni po terminie</span></div>
          <table>
            <tr><td>Należność główna</td><td class="r" id="outKwota">0,00 zł</td></tr>
            <tr><td>Odsetki ustawowe za opóźnienie<small><span id="outStopa">{{ $ratePct }}</span>% × <span id="outDni2">0</span> dni / 365</small></td><td class="r" id="outOdsetki">0,00 zł</td></tr>
            <tr><td>Rekompensata za koszty odzyskiwania<small>art. 10 ustawy · <span id="outEur">40</span> EUR × {{ number_format($eurPln, 2, ',', ' ') }} zł</small></td><td class="r" id="outRek">0,00 zł</td></tr>
            <tr class="tot"><td>Razem do zapłaty</td><td class="r" id="outRazem">0,00 zł</td></tr>
          </table>
        </div>
        <div class="calc-warn" id="warnBrak">Termin płatności jeszcze nie minął — odsetki i rekompensata przysługują dopiero od dnia następującego po terminie.</div>
        <div class="calc-note">Kalkulator ma charakter pomocniczy. Rekompensatę przelicza się według średniego kursu euro NBP z ostatniego dnia roboczego miesiąca poprzedzającego miesiąc wymagalności; tutaj przyjmujemy kurs {{ number_format($eurPln, 2, ',', ' ') }} zł. Kwoty odsetek zaokrąglamy do pełnych groszy.</div>
        <div class="calc-actions">
          <button type="button" class="btn btn-primary btn-lg" id="btnWezwanie">Wygeneruj wezwanie do zapłaty</button>
        </div>
        <div class="calc-actions" style="margin-top:10px;">
          <a href="{{ route('register') }}" class="btn btn-secondary">Załóż konto w {{ brand('name') }} — wezwania jednym kliknięciem</a>
          <a href="https://sprzedamfakture.pl" target="_blank" rel="noopener" class="btn btn-secondary">Przekaż sprawę do sprzedamfakture.pl</a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="prose">
      <h2>Podstawa prawna</h2>
      <p>Wszystkie trzy pozycje wynikają z <strong>ustawy z dnia 8 marca 2013 r. o przeciwdziałaniu nadmiernym opóźnieniom w transakcjach handlowych</strong>. Dotyczy ona transakcji handlowych, czyli umów o dostawę towaru lub świadczenie usługi zawieranych między przedsiębiorcami (a także z podmiotami publicznymi) — nie dotyczy umów z konsumentami.</p>
      <ul>
        <li><strong>Art. 4a</strong> — w transakcjach handlowych nie stosuje się „zwykłych” odsetek ustawowych za opóźnienie z Kodeksu cywilnego. Obowiązują wyższe <em>odsetki ustawowe za opóźnienie w transakcjach handlowych</em>: stopa referencyjna NBP powiększona o 10 punktów procentowych (dla dłużników będących podmiotami leczniczymi — o 8 p.p.). Ich wysokość ogłasza Minister Finansów w obwieszczeniu, na każde półrocze.</li>
        <li><strong>Art. 7</strong> — odsetki przysługują wierzycielowi <em>bez wezwania</em>, za okres od dnia wymagalności świadczenia (dzień po terminie płatności) do dnia zapłaty, jeżeli wierzyciel spełnił swoje świadczenie, a nie otrzymał zapłaty w terminie. Strony nie mogą wyłączyć tego uprawnienia. Termin zapłaty w umowie między przedsiębiorcami co do zasady nie może przekraczać 60 dni.</li>
        <li><strong>Art. 10</strong> — od dnia nabycia prawa do odsetek wierzycielowi przysługuje, bez wezwania, zryczałtowana <em>rekompensata za koszty odzyskiwania należności</em>: <strong>40 EUR</strong> przy należności do 5 000 zł, <strong>70 EUR</strong> od 5 000 zł do 50 000 zł i <strong>100 EUR</strong> powyżej 50 000 zł — od każdej transakcji (co do zasady od każdej faktury). Kwotę przelicza się na złote według średniego kursu euro ogłoszonego przez NBP w ostatnim dniu roboczym miesiąca poprzedzającego miesiąc, w którym należność stała się wymagalna. Jeżeli rzeczywiste koszty odzyskiwania (np. windykacja) są wyższe, możesz dochodzić także nadwyżki.</li>
      </ul>

      <h2>Jak liczy kalkulator</h2>
      <p><strong>Dni opóźnienia</strong> to liczba dni od terminu płatności do wskazanej daty zapłaty. <strong>Odsetki</strong> = kwota × stopa roczna × dni ÷ 365. <strong>Rekompensata</strong> zależy od wysokości należności głównej (40 / 70 / 100 EUR) i jest przeliczana po przyjętym kursie euro. <strong>Razem</strong> to suma należności głównej, odsetek i rekompensaty — kwota, którą możesz wpisać w wezwaniu do zapłaty.</p>
      <p>Odsetki i rekompensata są roszczeniami, których nie musisz zapowiadać w umowie ani na fakturze. W praktyce wielu przedsiębiorców z nich nie korzysta — a to właśnie one sprawiają, że opóźnianie płatności przestaje się dłużnikowi opłacać.</p>

      <h2>Co dalej, gdy wezwanie nie działa?</h2>
      <p>Wezwanie do zapłaty z terminem 7 dni to standardowy krok przedsądowy. Jeśli dłużnik nadal nie płaci, sprawę może przejąć <a href="https://sprzedamfakture.pl" target="_blank" rel="noopener">sprzedamfakture.pl</a>: windykacja polubowna, wpis do rejestru dłużników (KRD/BIG), a w razie potrzeby postępowanie sądowe i egzekucja — z kosztami obciążającymi dłużnika. Potrzebujesz pieniędzy od razu? Możesz zgłosić fakturę do wykupu i otrzymać ofertę cesji w jeden dzień roboczy.</p>
      <p>W {{ brand('name') }} cały ten proces jest wbudowany w fakturowanie: przypomnienia idą automatycznie, wezwanie z odsetkami i rekompensatą tworzysz jednym kliknięciem, a przekazanie do sprzedamfakture.pl nie wymaga przepisywania danych. <a href="{{ route('register') }}">Wypróbuj 14 dni za darmo</a>.</p>
    </div>
  </div>
</section>

<!-- WEZWANIE DO ZAPŁATY — pokazywane po kliknięciu, drukowalne -->
<section id="wezwanie" hidden>
  <div class="container">
    <div class="wz-toolbar">
      <div class="info">Uzupełnij dane w polach na kreskowanym tle — kwoty są przeniesione z kalkulatora. Przy druku pola stają się zwykłym tekstem.</div>
      <div class="btns">
        <button type="button" class="btn btn-primary" id="btnDrukuj">Drukuj / zapisz jako PDF</button>
        <button type="button" class="btn btn-ghost" id="btnZamknij">Zamknij</button>
      </div>
    </div>

    <div class="wz-doc">
      <div class="wz-head">
        <div class="wz-party">
          <div class="lbl">Wierzyciel</div>
          <input type="text" id="wzWierzNazwa" placeholder="Nazwa Twojej firmy">
          <textarea id="wzWierzAdres" placeholder="Ulica i numer, kod pocztowy, miejscowość"></textarea>
          <input type="text" id="wzWierzNip" placeholder="NIP">
        </div>
        <div class="wz-party">
          <div class="lbl">Dłużnik</div>
          <input type="text" id="wzDlNazwa" placeholder="Nazwa firmy dłużnika">
          <textarea id="wzDlAdres" placeholder="Ulica i numer, kod pocztowy, miejscowość"></textarea>
          <input type="text" id="wzDlNip" placeholder="NIP">
        </div>
      </div>

      <p style="text-align:right;"><input type="text" class="inline" id="wzMiejsce" placeholder="Miejscowość" style="min-width:140px;">, dnia <span id="wzDataDzis">—</span></p>

      <h2>Wezwanie do zapłaty</h2>
      <div class="sub">Przedsądowe wezwanie do zapłaty na podstawie art. 7 i art. 10 ustawy z dnia 8 marca 2013 r. o przeciwdziałaniu nadmiernym opóźnieniom w transakcjach handlowych</div>

      <p>Działając w imieniu wierzyciela, wzywam do zapłaty należności wynikającej z faktury nr <input type="text" class="inline" id="wzFaktura" placeholder="np. FV/2026/0004"> wystawionej dnia <input type="text" class="inline" id="wzFakturaData" placeholder="dd.mm.rrrr" style="min-width:110px;">, której termin płatności upłynął dnia <span id="wzTermin">—</span>. Do dnia dzisiejszego należność nie została uregulowana.</p>

      <p>Na dzień <span id="wzDataStan">—</span> zaległość wynosi:</p>
      <table class="wz-sum">
        <tr><td>Należność główna</td><td class="r" id="wzKwota">0,00 zł</td></tr>
        <tr><td>Odsetki ustawowe za opóźnienie w transakcjach handlowych (<span id="wzStopa">{{ $ratePct }}</span>% w stosunku rocznym, <span id="wzDni">0</span> dni)</td><td class="r" id="wzOdsetki">0,00 zł</td></tr>
        <tr><td>Rekompensata za koszty odzyskiwania należności (art. 10 ust. 1 ustawy; równowartość <span id="wzEur">40</span> EUR)</td><td class="r" id="wzRek">0,00 zł</td></tr>
        <tr class="tot"><td>Razem do zapłaty</td><td class="r" id="wzRazem">0,00 zł</td></tr>
      </table>

      <p>Wzywam do zapłaty powyższej kwoty w terminie <strong>7 dni</strong> od dnia otrzymania niniejszego wezwania, na rachunek bankowy nr <input type="text" class="inline" id="wzIban" placeholder="numer rachunku" style="min-width:260px;">, z podaniem numeru faktury w tytule przelewu. Odsetki naliczane są nadal, do dnia zapłaty.</p>

      <p>W przypadku braku zapłaty w wyznaczonym terminie sprawa zostanie bez dalszych wezwań przekazana do windykacji oraz skierowana na drogę postępowania sądowego, co narazi Państwa na dodatkowe koszty — w tym koszty procesu, zastępstwa procesowego i egzekucji — a także może skutkować wpisem do rejestru dłużników.</p>

      <p>Jeżeli należność została uregulowana przed otrzymaniem niniejszego pisma, proszę uznać je za nieaktualne i przesłać potwierdzenie przelewu.</p>

      <div class="sign"><div>podpis wierzyciela / osoby upoważnionej</div></div>

      <div class="legal">Odsetki ustawowe za opóźnienie w transakcjach handlowych oraz rekompensata za koszty odzyskiwania należności przysługują wierzycielowi bez wezwania (art. 7 ust. 1 i art. 10 ust. 1 ustawy z dnia 8 marca 2013 r. o przeciwdziałaniu nadmiernym opóźnieniom w transakcjach handlowych). Wyliczenie odsetek: kwota × stopa roczna × liczba dni opóźnienia ÷ 365.</div>
    </div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Nie licz tego ręcznie przy każdej fakturze</h2>
    <p>W {{ brand('name') }} przypomnienia idą same, wezwanie z odsetkami i rekompensatą powstaje jednym kliknięciem, a sprzedamfakture.pl jest o jeden krok — bez przepisywania danych.</p>
    <div class="hero-ctas">
      <a href="{{ route('register') }}" class="btn btn-white btn-lg">Wypróbuj 14 dni za darmo →</a>
      <a href="https://sprzedamfakture.pl" target="_blank" rel="noopener" class="btn btn-lg" style="background:rgba(255,255,255,0.15);color:white;border-color:rgba(255,255,255,0.3);">Przekaż sprawę do sprzedamfakture.pl</a>
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
      "name": "Ile wynoszą odsetki ustawowe za opóźnienie w transakcjach handlowych?",
      "acceptedAnswer": { "@@type": "Answer", "text": "Stopa referencyjna NBP powiększona o 10 punktów procentowych w stosunku rocznym (dla dłużników będących podmiotami leczniczymi o 8 p.p.). Wysokość ogłasza Minister Finansów na każde półrocze. Odsetki liczy się od dnia następującego po terminie płatności do dnia zapłaty: kwota × stopa × liczba dni ÷ 365." }
    },
    {
      "@@type": "Question",
      "name": "Ile wynosi rekompensata za koszty odzyskiwania należności?",
      "acceptedAnswer": { "@@type": "Answer", "text": "Równowartość 40 EUR przy należności do 5 000 zł, 70 EUR od 5 000 zł do 50 000 zł i 100 EUR powyżej 50 000 zł — od każdej transakcji handlowej, bez wezwania, przeliczona według średniego kursu euro NBP z ostatniego dnia roboczego miesiąca poprzedzającego miesiąc wymagalności (art. 10 ustawy z dnia 8 marca 2013 r.)." }
    },
    {
      "@@type": "Question",
      "name": "Czy odsetki i rekompensata przysługują w transakcjach z konsumentami?",
      "acceptedAnswer": { "@@type": "Answer", "text": "Nie. Ustawa o przeciwdziałaniu nadmiernym opóźnieniom w transakcjach handlowych dotyczy umów między przedsiębiorcami (oraz z podmiotami publicznymi). Wobec konsumentów obowiązują odsetki ustawowe za opóźnienie z Kodeksu cywilnego, a rekompensata nie przysługuje." }
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

  // Wartości startowe: dziś oraz termin 44 dni temu (przykład ze strony głównej).
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
