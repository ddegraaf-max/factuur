<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<title>Factuur {{ $invoice->number ?? 'concept' }}</title>
<style>
  @page { margin: 20mm 18mm 20mm 18mm; }
  body {
    font-family: {{ $company->invoice_font === 'sans' ? "'DejaVu Sans', sans-serif" : 'Georgia, serif' }};
    font-size: 10pt;
    color: #1C1917;
    line-height: 1.5;
  }
  h1, h2, h3 { margin: 0; font-weight: 700; }
  .brand { color: {{ $company->brand_color }}; }

  /* Centered header with double rule */
  .doc-header {
    text-align: center;
    padding-bottom: 18px;
    border-bottom: 3px double {{ $company->brand_color }};
    margin-bottom: 24px;
  }
  .logo-img { max-height: 56px; max-width: 200px; display: block; margin: 0 auto 10px; }
  .doc-title {
    font-size: 24pt;
    font-weight: 700;
    letter-spacing: 6px;
    margin: 6px 0 4px;
    color: {{ $company->brand_color }};
  }
  .doc-sub {
    font-size: 10pt;
    color: #555;
    font-style: italic;
  }

  /* Meta block */
  .meta-row { width: 100%; margin-bottom: 18px; font-size: 10pt; }
  .meta-row td { vertical-align: top; padding: 4px 0; width: 50%; }
  .meta-label { font-weight: 700; display: inline-block; min-width: 90px; }

  /* Party block — bordered */
  .party-block {
    border: 1px solid #888;
    padding: 12px 14px;
    margin-bottom: 18px;
  }
  .party-label { font-weight: 700; text-transform: uppercase; font-size: 9pt; letter-spacing: 0.06em; margin-bottom: 4px; color: {{ $company->brand_color }}; }
  .party-name { font-weight: 600; font-size: 11pt; }
  .party-line { font-size: 10pt; color: #333; }

  /* Lines table — gridded */
  .lines {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 16px;
    border: 1px solid #6b6b6b;
  }
  .lines th {
    background: {{ $company->brand_color }};
    color: #fff;
    text-align: left;
    padding: 8px 10px;
    font-size: 9.5pt;
    font-weight: 700;
    border: 1px solid {{ $company->brand_color }};
  }
  .lines th.right { text-align: right; }
  .lines th.center { text-align: center; }
  .lines td {
    padding: 8px 10px;
    border: 1px solid #c0c0c0;
    font-size: 10pt;
    vertical-align: top;
  }
  .lines td.right { text-align: right; }
  .lines td.center { text-align: center; }
  .lines .details { font-size: 8.5pt; color: #6b6b6b; font-style: italic; margin-top: 2px; }

  /* Totals — bordered */
  .totals {
    width: 320px;
    float: right;
    margin-top: 12px;
    border-collapse: collapse;
  }
  .totals tr td {
    padding: 5px 8px;
    font-size: 10pt;
    border-bottom: 1px solid #c0c0c0;
  }
  .totals .label { color: #333; }
  .totals .value { text-align: right; font-weight: 500; }
  .totals .grand-row td {
    border-top: 2px solid {{ $company->brand_color }};
    border-bottom: 3px double {{ $company->brand_color }};
    padding: 8px;
    font-weight: 700;
    font-size: 12pt;
  }

  .notes { clear: both; margin-top: 36px; padding: 12px 14px; border: 1px solid #ccc; background: #fafafa; font-size: 9.5pt; font-style: italic; }
  .footer { margin-top: 24px; padding-top: 12px; border-top: 1px solid #ccc; font-size: 9pt; color: #555; text-align: center; font-style: italic; }
  .badge { display: inline-block; padding: 2px 8px; border: 1px solid #B45309; font-size: 8pt; font-weight: 600; color: #B45309; }
</style>
</head>
<body>

<div class="doc-header">
  @php
    $scale = max(50, min(200, (int) ($company->logo_scale ?? 100))) / 100;
    $hasGd = extension_loaded('gd');
  @endphp
  @if($hasGd && $company->logo_data)
    <img src="{{ $company->logo_data }}" style="max-height: {{ round(56 * $scale) }}px; max-width: {{ round(200 * $scale) }}px; display: block; margin: 0 auto 10px;" alt="">
  @elseif($hasGd && $company->logo_path)
    <img src="{{ public_path('storage/' . $company->logo_path) }}" style="max-height: {{ round(56 * $scale) }}px; max-width: {{ round(200 * $scale) }}px; display: block; margin: 0 auto 10px;" alt="">
  @endif
  <div class="doc-title">FACTUUR</div>
  <div class="doc-sub">
    {{ $company->name }}
    @if($invoice->number) &middot; {{ $invoice->number }} @else &middot; <span class="badge">CONCEPT</span> @endif
  </div>
</div>

<table class="meta-row">
  <tr>
    <td>
      <span class="meta-label">Factuurdatum:</span> {{ $invoice->invoice_date->translatedFormat('j F Y') }}<br>
      <span class="meta-label">Vervaldatum:</span> {{ $invoice->due_date->translatedFormat('j F Y') }}<br>
      @if($invoice->reference)<span class="meta-label">Referentie:</span> {{ $invoice->reference }}@endif
    </td>
    <td>
      @if($company->kvk_number)<span class="meta-label">KVK:</span> {{ $company->kvk_number }}<br>@endif
      @if($company->vat_number)<span class="meta-label">BTW:</span> {{ $company->vat_number }}<br>@endif
      @if($company->iban)<span class="meta-label">IBAN:</span> {{ $company->iban }}@endif
    </td>
  </tr>
</table>

<div class="party-block">
  <div class="party-label">Geadresseerde</div>
  <div class="party-name">{{ $invoice->customer_name }}</div>
  @if($invoice->customer_address_line)<div class="party-line">{{ $invoice->customer_address_line }}</div>@endif
  @if($invoice->customer_postal_code || $invoice->customer_city)
    <div class="party-line">{{ $invoice->customer_postal_code }} {{ $invoice->customer_city }}</div>
  @endif
  @if($invoice->customer_kvk_number)<div class="party-line">KVK {{ $invoice->customer_kvk_number }}</div>@endif
  @if($invoice->customer_vat_number)<div class="party-line">BTW {{ $invoice->customer_vat_number }}</div>@endif
</div>

<table class="lines">
  <thead>
    <tr>
      <th style="width:46%;">Omschrijving</th>
      <th class="right" style="width:10%;">Aantal</th>
      <th class="right" style="width:14%;">Prijs</th>
      <th class="center" style="width:10%;">BTW</th>
      <th class="right" style="width:20%;">Totaal</th>
    </tr>
  </thead>
  <tbody>
    @foreach($invoice->lines as $line)
      <tr>
        <td>
          <div>{{ $line->description }}</div>
          @if($line->details)<div class="details">{{ $line->details }}</div>@endif
        </td>
        <td class="right">{{ rtrim(rtrim(number_format($line->quantity, 3, ',', '.'), '0'), ',') }} {{ $line->unit }}</td>
        <td class="right">€ {{ number_format($line->unit_price, 2, ',', '.') }}</td>
        <td class="center">{{ (int) $line->vat_rate }}%</td>
        <td class="right">€ {{ number_format($line->line_subtotal, 2, ',', '.') }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

<table class="totals">
  <tr><td class="label">Subtotaal</td><td class="value">€ {{ number_format($invoice->subtotal, 2, ',', '.') }}</td></tr>
  @if(is_array($invoice->vat_breakdown))
    @foreach($invoice->vat_breakdown as $rate => $amount)
      <tr><td class="label">BTW {{ rtrim(rtrim(number_format((float) $rate, 2, ',', '.'), '0'), ',') }}%</td><td class="value">€ {{ number_format((float) $amount, 2, ',', '.') }}</td></tr>
    @endforeach
  @endif
  <tr class="grand-row"><td>Te betalen</td><td class="value">€ {{ number_format($invoice->total, 2, ',', '.') }}</td></tr>
</table>

<div style="clear:both;"></div>

@if($invoice->notes)<div class="notes"><strong>Opmerking:</strong> {!! nl2br(e($invoice->notes)) !!}</div>@endif
@if($company->iban)
<div class="notes">
  Gelieve het bedrag binnen {{ $invoice->payment_terms }} dagen over te maken naar
  {{ $company->iban }} ten name van {{ $company->name }}@if($invoice->number), onder vermelding van factuurnummer {{ $invoice->number }}@endif.
</div>
@endif
@if($invoice->footer)<div class="footer">{!! nl2br(e($invoice->footer)) !!}</div>@elseif($company->invoice_footer)<div class="footer">{!! nl2br(e($company->invoice_footer)) !!}</div>@endif

</body>
</html>
