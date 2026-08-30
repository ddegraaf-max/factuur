<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<title>Offerte {{ $quote->number ?? 'concept' }}</title>
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

  .intro { margin-bottom: 20px; font-size: 10pt; color: #44403C; }

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

  .notes { clear: both; margin-top: 34px; padding: 12px 14px; background: #FAFAF9; border-left: 3px solid {{ $company->brand_color }}; border-radius: 4px; font-size: 9pt; color: #44403C; }
  .validity { clear: both; margin-top: 20px; padding: 12px 14px; border: 1px solid #EFEEEC; border-radius: 6px; font-size: 9.5pt; color: #44403C; }
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
      <div class="doc-title brand">{{ __('doc.quote') }}</div>
      <div class="doc-number">
        @if($quote->number){{ $quote->number }}@else<span class="badge">{{ __('doc.draft') }}</span>@endif
      </div>
    </td>
  </tr>
</table>

<table class="parties">
  <tr>
    <td>
      <div class="party-label">{{ __('doc.for') }}</div>
      <div class="party-name">{{ $quote->customer_name }}</div>
      @if($quote->customer_address_line)<div class="party-line">{{ $quote->customer_address_line }}</div>@endif
      @if($quote->customer_postal_code || $quote->customer_city)
        <div class="party-line">{{ $quote->customer_postal_code }} {{ $quote->customer_city }}</div>
      @endif
      @if($quote->customer_kvk_number)<div class="party-line">{{ __('doc.coc') }} {{ $quote->customer_kvk_number }}</div>@endif
      @if($quote->customer_vat_number)<div class="party-line">{{ __('doc.vat_no') }} {{ $quote->customer_vat_number }}</div>@endif
    </td>
    <td>
      <table class="meta-table">
        <tr><td class="meta-label">{{ __('doc.quote_date') }}</td><td class="meta-value">{{ $quote->quote_date->translatedFormat('j F Y') }}</td></tr>
        <tr><td class="meta-label">{{ __('doc.valid_until') }}</td><td class="meta-value">{{ $quote->valid_until->translatedFormat('j F Y') }}</td></tr>
        @if($quote->reference)<tr><td class="meta-label">{{ __('doc.reference') }}</td><td class="meta-value">{{ $quote->reference }}</td></tr>@endif
        @if($company->kvk_number)<tr><td class="meta-label">{{ __('doc.coc') }}</td><td class="meta-value">{{ $company->kvk_number }}</td></tr>@endif
        @if($company->vat_number)<tr><td class="meta-label">{{ __('doc.vat_no') }}</td><td class="meta-value">{{ $company->vat_number }}</td></tr>@endif
      </table>
    </td>
  </tr>
</table>

@if($quote->intro)<div class="intro">{!! nl2br(e($quote->intro)) !!}</div>@endif

@php
  $hasDiscount = $quote->lines->contains(fn ($l) => (float) ($l->discount_pct ?? 0) > 0);
@endphp
<table class="lines">
  <thead>
    <tr>
      <th style="width:{{ $hasDiscount ? 44 : 50 }}%;">{{ __('doc.description') }}</th>
      <th class="right" style="width:10%;">{{ __('doc.quantity') }}</th>
      <th class="right" style="width:14%;">{{ __('doc.price') }}</th>
      @if($hasDiscount)<th class="right" style="width:8%;">{{ __('doc.discount') }}</th>@endif
      <th class="center" style="width:8%;">{{ __('doc.vat') }}</th>
      <th class="right" style="width:{{ $hasDiscount ? 16 : 18 }}%;">{{ __('doc.total') }}</th>
    </tr>
  </thead>
  <tbody>
    @foreach($quote->lines as $line)
      <tr>
        <td>
          <div style="font-weight:500;">{{ $line->description }}</div>
          @if($line->details)<div class="details">{{ $line->details }}</div>@endif
        </td>
        <td class="right">{{ rtrim(rtrim(number_format($line->quantity, 3, ',', '.'), '0'), ',') }} {{ $line->unit }}</td>
        <td class="right">{{ money($line->unit_price) }}</td>
        @if($hasDiscount)<td class="right">{{ (float) ($line->discount_pct ?? 0) > 0 ? rtrim(rtrim(number_format($line->discount_pct, 2, ',', '.'), '0'), ',') . '%' : '—' }}</td>@endif
        <td class="center">{{ (int) $line->vat_rate }}%</td>
        <td class="right">{{ money($line->line_subtotal) }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

<table class="totals">
  <tr><td class="label">{{ __('doc.subtotal') }}</td><td class="value">{{ money($quote->subtotal) }}</td></tr>
  @if(is_array($quote->vat_breakdown))
    @foreach($quote->vat_breakdown as $rate => $amount)
      <tr><td class="label">{{ __('doc.vat') }} {{ rtrim(rtrim(number_format((float) $rate, 2, ',', '.'), '0'), ',') }}%</td><td class="value">{{ money((float) $amount) }}</td></tr>
    @endforeach
  @endif
  <tr class="grand-row"><td>{{ __('doc.total') }}</td><td class="value brand">{{ money($quote->total) }}</td></tr>
</table>

<div style="clear:both;"></div>

@if($quote->notes)<div class="notes"><strong>{{ __('doc.note') }}:</strong><br>{!! nl2br(e($quote->notes)) !!}</div>@endif

@if($quote->signed_at)
  {{-- Digitaal ondertekend: het akkoord staat zwart-op-wit op het document. --}}
  <div class="validity" style="border-color: #16A34A;">
    {!! __('doc.signed_by', ['name' => e($quote->signed_name), 'date' => e($quote->signed_at->translatedFormat('j F Y, H:i'))]) !!}
    @if(extension_loaded('gd') && $quote->signature_data)
      <div style="margin-top: 8px;"><img src="{{ $quote->signature_data }}" style="max-height: 60px; max-width: 220px;" alt=""></div>
    @endif
  </div>
@else
  <div class="validity">
    {!! __('doc.quote_valid_note', ['date' => e($quote->valid_until->translatedFormat('j F Y'))]) !!}
    {!! __('doc.quote_accept_note', ['phone' => $company->phone ? __('doc.quote_accept_phone', ['phone' => e($company->phone)]) : '']) !!}
  </div>
@endif

@if($quote->footer)<div class="footer">{!! nl2br(e($quote->footer)) !!}</div>@elseif($company->invoice_footer)<div class="footer">{!! nl2br(e($company->invoice_footer)) !!}</div>@endif

</body>
</html>
