<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<title>Factuur {{ $invoice->number ?? 'concept' }}</title>
<style>
  @page { margin: 24mm 22mm 24mm 22mm; }
  body {
    font-family: {{ $company->invoice_font === 'serif' ? 'Georgia, serif' : "'DejaVu Sans', sans-serif" }};
    font-size: 10pt;
    color: #1C1917;
    line-height: 1.6;
    font-weight: 300;
  }
  h1, h2, h3 { margin: 0; font-weight: 400; }
  .brand { color: {{ $company->brand_color }}; }

  /* Generous header */
  .doc-header { margin-bottom: 56px; }
  .logo-img { max-height: 44px; max-width: 180px; margin-bottom: 18px; }
  .doc-title {
    font-size: 30pt;
    font-weight: 300;
    letter-spacing: -1.5px;
    margin: 0;
    color: {{ $company->brand_color }};
  }
  .doc-meta {
    font-size: 10pt;
    color: #999;
    margin-top: 6px;
    letter-spacing: 0.02em;
  }

  /* Two-column from/to with lots of space */
  .parties { width: 100%; margin-bottom: 56px; }
  .parties td { vertical-align: top; padding-right: 24px; width: 50%; }
  .party-label {
    font-size: 8pt;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: #999;
    margin-bottom: 8px;
  }
  .party-name { font-weight: 500; font-size: 11pt; }
  .party-line { color: #555; font-size: 10pt; }

  /* Lines — very minimal */
  .lines {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 30px;
  }
  .lines th {
    text-align: left;
    padding: 12px 0 8px;
    font-size: 8.5pt;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: {{ $company->brand_color }};
    border-bottom: 1px solid {{ $company->brand_color }};
  }
  .lines th.right { text-align: right; }
  .lines td {
    padding: 14px 0;
    border-bottom: 1px solid #eee;
    font-size: 10pt;
    vertical-align: top;
  }
  .lines td.right { text-align: right; font-variant-numeric: tabular-nums; }
  .lines .details { font-size: 9pt; color: #999; margin-top: 3px; }

  /* Totals — no card, just numbers */
  .totals { width: 280px; float: right; margin-top: 18px; }
  .totals tr td { padding: 5px 0; font-size: 10pt; color: #666; }
  .totals .value { text-align: right; font-variant-numeric: tabular-nums; }
  .totals .grand-row td {
    border-top: 2px solid {{ $company->brand_color }};
    padding-top: 12px;
    font-weight: 600;
    font-size: 13pt;
    color: #1C1917;
  }

  .notes { clear: both; margin-top: 60px; font-size: 9.5pt; color: #555; line-height: 1.7; }
  .footer { margin-top: 30px; font-size: 9pt; color: #999; }
  .badge { display: inline-block; padding: 2px 8px; font-size: 8pt; color: #B45309; }
</style>
</head>
<body>

<div class="doc-header">
  @php
    $scale = max(50, min(200, (int) ($company->logo_scale ?? 100))) / 100;
    $hasGd = extension_loaded('gd');
  @endphp
  @if($hasGd && $company->logo_data)
    <img src="{{ $company->logo_data }}" style="max-height: {{ round(44 * $scale) }}px; max-width: {{ round(180 * $scale) }}px; margin-bottom: 18px;" alt="">
  @elseif($hasGd && $company->logo_path)
    <img src="{{ public_path('storage/' . $company->logo_path) }}" style="max-height: {{ round(44 * $scale) }}px; max-width: {{ round(180 * $scale) }}px; margin-bottom: 18px;" alt="">
  @endif
  <div class="doc-title">Factuur</div>
  <div class="doc-meta">
    @if($invoice->number){{ $invoice->number }}@else<span class="badge">Concept</span>@endif
    &middot; {{ $invoice->invoice_date->translatedFormat('j F Y') }}
  </div>
</div>

<table class="parties">
  <tr>
    <td>
      <div class="party-label">Van</div>
      <div class="party-name">{{ $company->name }}</div>
      @if($company->address_line)<div class="party-line">{{ $company->address_line }}</div>@endif
      @if($company->postal_code || $company->city)<div class="party-line">{{ $company->postal_code }} {{ $company->city }}</div>@endif
      @if($company->email)<div class="party-line" style="margin-top:8px;color:#999;">{{ $company->email }}</div>@endif
      @if($company->kvk_number)<div class="party-line" style="color:#999;">KVK {{ $company->kvk_number }}</div>@endif
      @if($company->vat_number)<div class="party-line" style="color:#999;">BTW {{ $company->vat_number }}</div>@endif
    </td>
    <td>
      <div class="party-label">Aan</div>
      <div class="party-name">{{ $invoice->customer_name }}</div>
      @if($invoice->customer_address_line)<div class="party-line">{{ $invoice->customer_address_line }}</div>@endif
      @if($invoice->customer_postal_code || $invoice->customer_city)
        <div class="party-line">{{ $invoice->customer_postal_code }} {{ $invoice->customer_city }}</div>
      @endif
      @if($invoice->customer_vat_number)<div class="party-line" style="margin-top:8px;color:#999;">BTW {{ $invoice->customer_vat_number }}</div>@endif
      <div class="party-line" style="margin-top:14px;color:#999;font-size:9pt;">
        Vervaldatum: {{ $invoice->due_date->translatedFormat('j F Y') }}
      </div>
    </td>
  </tr>
</table>

<table class="lines">
  <thead>
    <tr>
      <th style="width:58%;">Omschrijving</th>
      <th class="right" style="width:14%;">Aantal</th>
      <th class="right" style="width:14%;">Prijs</th>
      <th class="right" style="width:14%;">Totaal</th>
    </tr>
  </thead>
  <tbody>
    @foreach($invoice->lines as $line)
      <tr>
        <td>
          <div>{{ $line->description }}</div>
          @if($line->details)<div class="details">{{ $line->details }}</div>@endif
        </td>
        <td class="right">{{ rtrim(rtrim(number_format($line->quantity, 3, ',', '.'), '0'), ',') }}</td>
        <td class="right">€ {{ number_format($line->unit_price, 2, ',', '.') }}</td>
        <td class="right">€ {{ number_format($line->line_subtotal, 2, ',', '.') }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

<table class="totals">
  <tr><td>Subtotaal</td><td class="value">€ {{ number_format($invoice->subtotal, 2, ',', '.') }}</td></tr>
  @if(is_array($invoice->vat_breakdown))
    @foreach($invoice->vat_breakdown as $rate => $amount)
      <tr><td>BTW {{ rtrim(rtrim(number_format((float) $rate, 2, ',', '.'), '0'), ',') }}%</td><td class="value">€ {{ number_format((float) $amount, 2, ',', '.') }}</td></tr>
    @endforeach
  @endif
  <tr class="grand-row"><td>Totaal</td><td class="value">€ {{ number_format($invoice->total, 2, ',', '.') }}</td></tr>
</table>

<div style="clear:both;"></div>

@if($invoice->notes)<div class="notes">{!! nl2br(e($invoice->notes)) !!}</div>@endif
@if($company->iban)
<div class="notes">
  Gelieve binnen {{ $invoice->payment_terms }} dagen over te maken naar {{ $company->iban }}
  @if($invoice->number) onder vermelding van {{ $invoice->number }}@endif.
</div>
@endif
@if($invoice->footer)<div class="footer">{!! nl2br(e($invoice->footer)) !!}</div>@elseif($company->invoice_footer)<div class="footer">{!! nl2br(e($company->invoice_footer)) !!}</div>@endif

</body>
</html>
