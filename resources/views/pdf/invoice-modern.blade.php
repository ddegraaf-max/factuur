<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<title>Factuur {{ $invoice->number ?? 'concept' }}</title>
<style>
  @page { margin: 16mm 15mm 16mm 15mm; }
  body {
    font-family: {{ $company->invoice_font === 'serif' ? 'serif' : "'DejaVu Sans', sans-serif" }};
    font-size: 10pt;
    color: #1C1917;
    line-height: 1.5;
  }
  h1, h2, h3 { margin: 0; font-weight: 600; }
  .brand { color: {{ $company->brand_color }}; }

  .header { width: 100%; margin-bottom: 26px; border-bottom: 3px solid {{ $company->brand_color }}; padding-bottom: 16px; }
  .header td { vertical-align: top; }
  .logo-img { max-height: 56px; max-width: 200px; margin-bottom: 8px; }
  .logo-mark {
    width: 46px; height: 46px;
    background: {{ $company->brand_color }};
    border-radius: 10px;
    display: inline-block;
    text-align: center;
    color: white;
    font-weight: bold;
    font-size: 22px;
    line-height: 46px;
    margin-bottom: 10px;
  }
  .company-name { font-weight: 700; font-size: 12pt; }
  .company-line { color: #78716C; font-size: 9pt; }
  .doc-title { font-size: 30pt; font-weight: 800; letter-spacing: -1px; margin: 0 0 4px 0; color: {{ $company->brand_color }}; }
  .doc-number { font-size: 10pt; color: #78716C; font-family: 'Courier', monospace; }

  .parties { width: 100%; margin-bottom: 20px; }
  .parties td { vertical-align: top; padding-right: 16px; width: 50%; }
  .party-label { font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.08em; color: #A8A29E; margin-bottom: 5px; font-weight: 700; }
  .party-name { font-weight: 700; font-size: 11pt; margin-bottom: 2px; }
  .party-line { color: #57534E; font-size: 9.5pt; }

  .meta-table { width: 100%; margin-bottom: 22px; border-collapse: separate; border-spacing: 0; }
  .meta-table td { padding: 8px 14px; font-size: 9pt; background: #FAFAF9; border-top: 1px solid #EFEEEC; border-bottom: 1px solid #EFEEEC; }
  .meta-table tr td:first-child { border-left: 1px solid #EFEEEC; border-top-left-radius: 6px; border-bottom-left-radius: 6px; }
  .meta-table tr td:last-child { border-right: 1px solid #EFEEEC; border-top-right-radius: 6px; border-bottom-right-radius: 6px; }
  .meta-label { color: #A8A29E; text-transform: uppercase; font-size: 7.5pt; letter-spacing: 0.05em; }
  .meta-value { font-weight: 600; font-size: 9.5pt; }

  .lines { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
  .lines th { text-align: left; padding: 0 10px 8px; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.06em; color: #A8A29E; border-bottom: 2px solid {{ $company->brand_color }}; }
  .lines th.right { text-align: right; }
  .lines th.center { text-align: center; }
  .lines td { padding: 10px 10px; border-bottom: 1px solid #EFEEEC; font-size: 9.5pt; vertical-align: top; }
  .lines td.right { text-align: right; font-family: 'Courier', monospace; }
  .lines td.center { text-align: center; }
  .lines .details { font-size: 8.5pt; color: #A8A29E; margin-top: 2px; }

  .totals { width: 290px; float: right; margin-top: 14px; }
  .totals tr td { padding: 5px 12px; font-size: 10pt; }
  .totals .label { color: #57534E; }
  .totals .value { text-align: right; font-family: 'Courier', monospace; font-weight: 500; }
  .totals .grand-row td { background: {{ $company->brand_color }}; color: #fff; font-weight: 700; font-size: 12.5pt; border-radius: 6px; padding: 10px 12px; }
  .totals .grand-row .value { color: #fff; }

  .notes { clear: both; margin-top: 46px; padding: 12px 14px; background: #FAFAF9; border-left: 3px solid {{ $company->brand_color }}; border-radius: 4px; font-size: 9pt; color: #44403C; }
  .footer { margin-top: 22px; padding-top: 12px; border-top: 1px solid #E7E5E4; font-size: 8.5pt; color: #A8A29E; text-align: center; }
  .badge { display: inline-block; padding: 2px 10px; border-radius: 100px; font-size: 8pt; font-weight: 600; background: #FEF3C7; color: #B45309; }
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
      <div class="doc-title brand">{{ __('doc.invoice') }}</div>
      <div class="doc-number">
        @if($invoice->number){{ $invoice->number }}@else<span class="badge">{{ __('doc.draft') }}</span>@endif
      </div>
    </td>
  </tr>
</table>

<table class="parties">
  <tr>
    <td>
      <div class="party-label">{{ __('doc.to') }}</div>
      <div class="party-name">{{ $invoice->customer_name }}</div>
      @if($invoice->customer_address_line)<div class="party-line">{{ $invoice->customer_address_line }}</div>@endif
      @if($invoice->customer_postal_code || $invoice->customer_city)
        <div class="party-line">{{ $invoice->customer_postal_code }} {{ $invoice->customer_city }}</div>
      @endif
      @if($invoice->customer_kvk_number)<div class="party-line">{{ __('doc.coc') }} {{ $invoice->customer_kvk_number }}</div>@endif
      @if($invoice->customer_vat_number)<div class="party-line">{{ __('doc.vat_no') }} {{ $invoice->customer_vat_number }}</div>@endif
    </td>
    <td>
      <table class="meta-table">
        <tr><td class="meta-label">{{ __('doc.invoice_date') }}</td><td class="meta-value">{{ $invoice->invoice_date->translatedFormat('j F Y') }}</td></tr>
        <tr><td class="meta-label">{{ __('doc.due_date') }}</td><td class="meta-value">{{ $invoice->due_date->translatedFormat('j F Y') }}</td></tr>
        @if($invoice->reference)<tr><td class="meta-label">{{ __('doc.reference') }}</td><td class="meta-value">{{ $invoice->reference }}</td></tr>@endif
        @if($company->kvk_number)<tr><td class="meta-label">{{ __('doc.coc') }}</td><td class="meta-value">{{ $company->kvk_number }}</td></tr>@endif
        @if($company->vat_number)<tr><td class="meta-label">{{ __('doc.vat_no') }}</td><td class="meta-value">{{ $company->vat_number }}</td></tr>@endif
      </table>
    </td>
  </tr>
</table>

<table class="lines">
  <thead>
    <tr>
      <th style="width:50%;">{{ __('doc.description') }}</th>
      <th class="right" style="width:10%;">{{ __('doc.quantity') }}</th>
      <th class="right" style="width:14%;">{{ __('doc.price') }}</th>
      <th class="center" style="width:8%;">{{ __('doc.vat') }}</th>
      <th class="right" style="width:18%;">{{ __('doc.total') }}</th>
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
  <tr><td class="label">{{ __('doc.subtotal') }}</td><td class="value">€ {{ number_format($invoice->subtotal, 2, ',', '.') }}</td></tr>
  @if(is_array($invoice->vat_breakdown))
    @foreach($invoice->vat_breakdown as $rate => $amount)
      <tr><td class="label">{{ __('doc.vat') }} {{ rtrim(rtrim(number_format((float) $rate, 2, ',', '.'), '0'), ',') }}%</td><td class="value">€ {{ number_format((float) $amount, 2, ',', '.') }}</td></tr>
    @endforeach
  @endif
  @php
    // Verrekeningen ("reeds doorgestort"): verlagen het te betalen bedrag,
    // maar niet het factuurtotaal of de BTW.
    $pdfAdvances = $invoice->payments()->where('kind', 'advance')->orderBy('paid_on')->get();
    $pdfPayable = max((float) $invoice->total - (float) $pdfAdvances->sum('amount'), 0);
  @endphp
  @if($pdfAdvances->isNotEmpty())
    <tr><td class="label">{{ __('doc.total_incl_vat') }}</td><td class="value">€ {{ number_format($invoice->total, 2, ',', '.') }}</td></tr>
    @foreach($pdfAdvances as $adv)
      <tr><td class="label">{{ $adv->reference ?: __('doc.already_settled') }} ({{ $adv->paid_on->format('d-m-Y') }})</td><td class="value">− € {{ number_format($adv->amount, 2, ',', '.') }}</td></tr>
    @endforeach
    <tr class="grand-row"><td>{{ __('doc.amount_due') }}</td><td class="value brand">€ {{ number_format($pdfPayable, 2, ',', '.') }}</td></tr>
  @else
    <tr class="grand-row"><td>{{ __('doc.amount_due') }}</td><td class="value brand">€ {{ number_format($invoice->total, 2, ',', '.') }}</td></tr>
  @endif
</table>

<div style="clear:both;"></div>

@if($invoice->notes)<div class="notes"><strong>{{ __('doc.note') }}:</strong><br>{!! nl2br(e($invoice->notes)) !!}</div>@endif
@if($company->iban)<div class="notes">{!! __('doc.pay_instruction', ['days' => (int) $invoice->payment_terms, 'iban' => e($company->iban), 'name' => e($company->name)]) !!}@if($invoice->number){!! __('doc.pay_reference', ['number' => e($invoice->number)]) !!}@endif.</div>@endif
@if($invoice->footer)<div class="footer">{!! nl2br(e($invoice->footer)) !!}</div>@elseif($company->invoice_footer)<div class="footer">{!! nl2br(e($company->invoice_footer)) !!}</div>@endif

</body>
</html>
