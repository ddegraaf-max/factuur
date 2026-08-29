{{-- Wezwanie do zapłaty (ostateczne przedsądowe) — Poolse markt. DejaVu Sans voor Poolse tekens in DomPDF. --}}
@php
  $brandColor = $company->brand_color ?: brand('color');
  $fmtDate = fn ($d) => $d ? $d->format('d.m.Y') : '—';
  $ratePct = rtrim(rtrim(number_format($claim['rate'] * 100, 2, ',', ''), '0'), ',');
@endphp
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="utf-8">
<style>
  @page { margin: 22mm 20mm 20mm 20mm; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #1F1D1A; line-height: 1.5; }
  .head { width: 100%; border-collapse: collapse; margin-bottom: 18pt; }
  .head td { vertical-align: top; }
  .party { font-size: 9.5pt; }
  .party .lbl { font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.08em; color: #8A8681; margin-bottom: 3pt; }
  .party .nm { font-weight: bold; font-size: 10.5pt; }
  h1 { font-size: 16pt; margin: 0 0 3pt; color: {{ $brandColor }}; letter-spacing: 0.02em; }
  .sub { font-size: 9pt; color: #8A8681; margin-bottom: 14pt; }
  p { margin: 0 0 9pt; text-align: justify; }
  table.claim { width: 100%; border-collapse: collapse; margin: 10pt 0 14pt; }
  table.claim td { padding: 5pt 6pt; border-bottom: 1px solid #E4E0D9; }
  table.claim td.r { text-align: right; white-space: nowrap; }
  table.claim tr.tot td { border-top: 2px solid {{ $brandColor }}; border-bottom: none; font-weight: bold; font-size: 11pt; }
  .box { border: 1px solid #D6D3CE; border-radius: 6pt; padding: 8pt 10pt; margin: 10pt 0; background: #FAF8F5; }
  .sig { margin-top: 26pt; width: 100%; }
  .sig td { width: 50%; vertical-align: bottom; }
  .line { border-top: 1px solid #8A8681; padding-top: 4pt; font-size: 8.5pt; color: #8A8681; width: 70%; }
  .foot { position: fixed; bottom: -8mm; left: 0; right: 0; font-size: 7.5pt; color: #8A8681; text-align: center; }
</style>
</head>
<body>
<table class="head">
  <tr>
    <td style="width:50%;">
      <div class="party">
        <div class="lbl">Wierzyciel</div>
        <div class="nm">{{ $company->name }}</div>
        @if($company->address_line)<div>{{ $company->address_line }}</div>@endif
        @if($company->postal_code || $company->city)<div>{{ trim($company->postal_code . ' ' . $company->city) }}</div>@endif
        @if($company->vat_number)<div>NIP {{ $company->vat_number }}</div>@endif
        @if($company->email)<div>{{ $company->email }}</div>@endif
        @if($company->phone)<div>{{ $company->phone }}</div>@endif
      </div>
    </td>
    <td style="width:50%; padding-left: 24pt;">
      <div class="party">
        <div class="lbl">Dłużnik</div>
        <div class="nm">{{ $invoice->customer_name }}</div>
        @if($invoice->customer_address_line)<div>{{ $invoice->customer_address_line }}</div>@endif
        @if($invoice->customer_postal_code || $invoice->customer_city)<div>{{ trim(($invoice->customer_postal_code ?? '') . ' ' . ($invoice->customer_city ?? '')) }}</div>@endif
        @if($invoice->customer_vat_number)<div>NIP {{ $invoice->customer_vat_number }}</div>@endif
      </div>
      <div style="margin-top:10pt; font-size:9pt; color:#8A8681;">{{ $company->city ? $company->city . ', ' : '' }}{{ $fmtDate($claim['on']) }}</div>
    </td>
  </tr>
</table>

<h1>WEZWANIE DO ZAPŁATY</h1>
<div class="sub">ostateczne przedsądowe · dotyczy faktury {{ $invoice->number }} z dnia {{ $fmtDate($invoice->invoice_date) }}</div>

<p>Działając w imieniu wierzyciela, na podstawie art. 4a, 7 i 10 ustawy z dnia 8 marca 2013 r. o przeciwdziałaniu nadmiernym opóźnieniom w transakcjach handlowych, wzywamy do niezwłocznej zapłaty należności wynikającej z faktury <strong>{{ $invoice->number }}</strong>, której termin płatności upłynął dnia <strong>{{ $fmtDate($invoice->due_date) }}</strong> ({{ $claim['days'] }} dni temu).</p>

<table class="claim">
  <tr><td>Należność główna (faktura {{ $invoice->number }})</td><td class="r">{{ money($claim['principal']) }}</td></tr>
  <tr><td>Odsetki ustawowe za opóźnienie w transakcjach handlowych ({{ $ratePct }}% × {{ $claim['days'] }} dni)</td><td class="r">{{ money($claim['interest']) }}</td></tr>
  <tr><td>Rekompensata za koszty odzyskiwania należności (art. 10 — {{ $claim['compensation_eur'] }} EUR)</td><td class="r">{{ money($claim['compensation']) }}</td></tr>
  <tr class="tot"><td>Razem do zapłaty na dzień {{ $fmtDate($claim['on']) }}</td><td class="r">{{ money($claim['total']) }}</td></tr>
</table>

<p>Kwotę należy wpłacić w terminie <strong>{{ \App\Services\WindykacjaService::DEADLINE_DAYS }} dni</strong> od daty niniejszego wezwania, tj. do dnia <strong>{{ $fmtDate($claim['deadline']) }}</strong>,
@if($company->iban) na rachunek bankowy <strong>{{ $company->iban }}</strong> ({{ $company->name }}),@endif
w tytule przelewu podając numer faktury <strong>{{ $invoice->number }}</strong>. Odsetki naliczane są w dalszym ciągu, do dnia zapłaty.</p>

<div class="box">
  <strong>Brak zapłaty w wyznaczonym terminie</strong> skutkować będzie przekazaniem sprawy do windykacji przez <strong>{{ $partner['name'] }}</strong>@if($partner['website']) ({{ preg_replace('#^https?://#', '', $partner['website']) }})@endif,
  zgłoszeniem dłużnika do biura informacji gospodarczej (KRD / BIG) oraz — w razie potrzeby — skierowaniem sprawy na drogę postępowania sądowego, którego koszty obciążą dłużnika.
</div>

<p>Jeżeli należność została już uregulowana, prosimy o przesłanie potwierdzenia przelewu i pominięcie niniejszego wezwania.</p>

<table class="sig">
  <tr>
    <td><div class="line">podpis wierzyciela / osoby upoważnionej</div></td>
    <td></td>
  </tr>
</table>

<div class="foot">Wezwanie sporządzono w {{ brand('name') }} · {{ brand('domain') }} · Windykacja: {{ $partner['name'] }}</div>
</body>
</html>
