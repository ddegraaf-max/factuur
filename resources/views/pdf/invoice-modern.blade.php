<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<title>Factuur {{ $invoice->number ?? 'concept' }}</title>
<style>
  @page { margin: 18mm 16mm 18mm 16mm; }
  body {
    font-family: {{ $company->invoice_font === 'serif' ? 'serif' : "'DejaVu Sans', sans-serif" }};
    font-size: 10pt;
    color: #1C1917;
    line-height: 1.45;
  }
  h1, h2, h3 { margin: 0; font-weight: 600; }
  .brand { color: {{ $company->brand_color }}; }
  .header { width: 100%; margin-bottom: 30px; border-bottom: 4px solid {{ $company->brand_color }}; padding-bottom: 18px; }
  .header td { vertical-align: top; }
  .logo-img { max-height: 56px; max-width: 200px; margin-bottom: 8px; }
  .logo-mark {
    width: 44px; height: 44px;
    background: {{ $company->brand_color }};
    border-radius: 8px;
    display: inline-block;
    text-align: center;
    color: white;
    font-weight: bold;
    font-size: 22px;
    line-height: 44px;
    margin-bottom: 8px;
  }
  .doc-title { font-size: 28pt; font-weight: 800; letter-spacing: -1px; margin: 0 0 4px 0; }
  .doc-number { font-size: 11pt; color: #78716C; font-family: 'Courier', monospace; }
  .parties { width: 100%; margin-bottom: 24px; }
  .parties td { vertical-align: top; padding-right: 16px; width: 50%; }
  .party-label { font-size: 8pt; text-transform: uppercase; letter-spacing: 0.05em; color: #78716C; margin-bottom: 6px; font-weight: 600; }
  .party-name { font-weight: 600; font-size: 11pt; margin-bottom: 2px; }
  .party-line { color: #44403C; font-size: 9.5pt; }
  .meta-table { width: 100%; background: #FAFAF9; border-radius: 6px; padding: 12px 14px; }
  .meta-table td { padding: 3px 0; font-size: 9.5pt; }
  .meta-label { color: #78716C; width: 35%; }
  .meta-value { font-weight: 500; }
  .lines { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
  .lines th { background: #F5F5F4; text-align: left; padding: 9px 10px; font-size: 8.5pt; text-transform: uppercase; letter-spacing: 0.04em; color: #44403C; border-bottom: 2px solid #1C1917; }
  .lines th.right { text-align: right; }
  .lines th.center { text-align: center; }
  .lines td { padding: 9px 10px; border-bottom: 1px solid #E7E5E4; font-size: 9.5pt; vertical-align: top; }
  .lines td.right { text-align: right; font-family: 'Courier', monospace; }
  .lines td.center { text-align: center; }
  .lines .details { font-size: 8.5pt; color: #78716C; margin-top: 2px; }
  .totals { width: 280px; float: right; margin-top: 12px; }
  .totals tr td { padding: 4px 0; font-size: 10pt; }
  .totals .label { color: #44403C; }
  .totals .value { text-align: right; font-family: 'Courier', monospace; font-weight: 500; }
  .totals .grand-row td { border-top: 2px solid {{ $company->brand_color }}; padding-top: 8px; font-weight: 700; font-size: 13pt; }
  .notes { clear: both; margin-top: 50px; padding-top: 16px; border-top: 1px solid #E7E5E4; font-size: 9pt; color: #44403C; }
  .footer { margin-top: 30px; padding-top: 12px; border-top: 1px solid #E7E5E4; font-size: 8.5pt; color: #78716C; text-align: center; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 100px; font-size: 8pt; font-weight: 600; background: #FEF3C7; color: #B45309; }
</style>
</head>
<body>

<table class="header">
  <tr>
    <td>
      @php
        $scale = max(50, min(200, (int) ($company->logo_scale ?? 100))) / 100;
        $hasGd = extension_loaded('gd');
        $hasLogo = $hasGd && ($company->logo_data || $company->logo_path);
      @endphp
      @if($hasLogo && $company->logo_data)
        <img src="{{ $company->logo_data }}" style="max-height: {{ round(56 * $scale) }}px; max-width: {{ round(200 * $scale) }}px; margin-bottom: 8px;" alt="">
      @elseif($hasLogo && $company->logo_path)
        <img src="{{ public_path('storage/' . $company->logo_path) }}" style="max-height: {{ round(56 * $scale) }}px; max-width: {{ round(200 * $scale) }}px; margin-bottom: 8px;" alt="">
      @else
        <div class="logo-mark">{{ strtoupper(substr($company->name ?? 'E', 0, 1)) }}</div>
      @endif
      <h2>{{ $company->name }}</h2>
      <div style="color:#78716C; font-size:9.5pt;">
        @if($company->address_line){{ $company->address_line }}<br>@endif
        @if($company->postal_code || $company->city){{ $company->postal_code }} {{ $company->city }}<br>@endif
        @if($company->email){{ $company->email }}<br>@endif
        @if($company->website){{ $company->website }}@endif
      </div>
    </td>
    <td style="text-align:right;">
      <div class="doc-title brand">FACTUUR</div>
      <div class="doc-number">
        @if($invoice->number){{ $invoice->number }}@else<span class="badge">CONCEPT</span>@endif
      </div>
    </td>
  </tr>
</table>

<table class="parties">
  <tr>
    <td>
      <div class="party-label">Aan</div>
      <div class="party-name">{{ $invoice->customer_name }}</div>
      @if($invoice->customer_address_line)<div class="party-line">{{ $invoice->customer_address_line }}</div>@endif
      @if($invoice->customer_postal_code || $invoice->customer_city)
        <div class="party-line">{{ $invoice->customer_postal_code }} {{ $invoice->customer_city }}</div>
      @endif
      @if($invoice->customer_kvk_number)<div class="party-line">KVK {{ $invoice->customer_kvk_number }}</div>@endif
      @if($invoice->customer_vat_number)<div class="party-line">BTW {{ $invoice->customer_vat_number }}</div>@endif
    </td>
    <td>
      <table class="meta-table">
        <tr><td class="meta-label">Factuurdatum</td><td class="meta-value">{{ $invoice->invoice_date->translatedFormat('j F Y') }}</td></tr>
        <tr><td class="meta-label">Vervaldatum</td><td class="meta-value">{{ $invoice->due_date->translatedFormat('j F Y') }}</td></tr>
        @if($invoice->reference)<tr><td class="meta-label">Referentie</td><td class="meta-value">{{ $invoice->reference }}</td></tr>@endif
        @if($company->kvk_number)<tr><td class="meta-label">KVK</td><td class="meta-value">{{ $company->kvk_number }}</td></tr>@endif
        @if($company->vat_number)<tr><td class="meta-label">BTW</td><td class="meta-value">{{ $company->vat_number }}</td></tr>@endif
      </table>
    </td>
  </tr>
</table>

<table class="lines">
  <thead>
    <tr>
      <th style="width:50%;">Omschrijving</th>
      <th class="right" style="width:10%;">Aantal</th>
      <th class="right" style="width:14%;">Prijs</th>
      <th class="center" style="width:8%;">BTW</th>
      <th class="right" style="width:18%;">Totaal</th>
    </tr>
  </thead>
  <tbody>
    @foreach($invoice->lines as $line)
      <tr>
        <td>
          <div style="font-weight:500;">{{ $line->description }}</div>
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
  <tr class="grand-row"><td>Te betalen</td><td class="value brand">€ {{ number_format($invoice->total, 2, ',', '.') }}</td></tr>
</table>

<div style="clear:both;"></div>

@if($invoice->notes)<div class="notes"><strong>Opmerking:</strong><br>{!! nl2br(e($invoice->notes)) !!}</div>@endif
@if($company->iban)<div class="notes">Gelieve het bedrag binnen <strong>{{ $invoice->payment_terms }} dagen</strong> over te maken naar <strong>{{ $company->iban }}</strong> ten name van <strong>{{ $company->name }}</strong>@if($invoice->number) onder vermelding van factuurnummer <strong>{{ $invoice->number }}</strong>@endif.</div>@endif
@if($invoice->footer)<div class="footer">{!! nl2br(e($invoice->footer)) !!}</div>@elseif($company->invoice_footer)<div class="footer">{!! nl2br(e($company->invoice_footer)) !!}</div>@endif

</body>
</html>
