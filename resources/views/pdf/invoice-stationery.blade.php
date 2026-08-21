<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<title>Factuur {{ $invoice->number ?? 'concept' }}</title>
@php
  // Eigen briefpapier: het ontwerp van de gebruiker is de volledige ondergrond;
  // wij zetten er alleen de factuurinhoud op, in neutrale typografie (de
  // branding zit immers al in het papier). Marges instelbaar per administratie.
  $mt = max(10, min(150, (int) ($company->stationery_margin_top ?? 45)));
  $mb = max(5, min(100, (int) ($company->stationery_margin_bottom ?? 25)));
@endphp
<style>
  @page { margin: {{ $mt }}mm 18mm {{ $mb }}mm 18mm; }
  body {
    font-family: {{ $company->invoice_font === 'serif' ? 'Georgia, serif' : "'DejaVu Sans', sans-serif" }};
    font-size: 9.5pt;
    color: #1C1917;
    line-height: 1.55;
  }
  h1, h2, h3 { margin: 0; font-weight: 600; }

  /* Het briefpapier: paginavullend, herhaald op elke pagina. De negatieve
     offsets tillen de afbeelding uit de paginamarges (DomPDF-patroon). */
  .stationery-bg {
    position: fixed;
    top: -{{ $mt }}mm; left: -18mm;
    width: 210mm; height: 297mm;
    z-index: -1000;
  }
  .stationery-bg img { width: 210mm; height: 297mm; }

  .doc-meta-row { width: 100%; margin-bottom: 16px; }
  .doc-meta-row td { vertical-align: top; }
  .doc-title { font-size: 15pt; font-weight: 700; letter-spacing: -0.3px; }
  .doc-sub { font-size: 9pt; color: #57534E; margin-top: 2px; }
  .meta-block { text-align: right; font-size: 9pt; color: #44403C; }
  .meta-block .label { color: #78716C; }
  .badge { display: inline-block; padding: 2px 8px; font-size: 8pt; color: #B45309; background: #FEF3C7; border-radius: 100px; font-weight: 600; }

  .party { margin-bottom: 20px; font-size: 9.5pt; }
  .party-label { font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.08em; color: #78716C; margin-bottom: 3px; }
  .party-name { font-weight: 700; font-size: 10.5pt; }
  .party-line { color: #44403C; }

  .lines { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
  .lines th {
    text-align: left; padding: 6px 8px; font-size: 7.5pt; text-transform: uppercase;
    letter-spacing: 0.06em; color: #57534E; border-bottom: 1.5px solid #1C1917;
  }
  .lines th.right { text-align: right; }
  .lines td { padding: 8px; border-bottom: 1px solid #E7E5E4; font-size: 9.5pt; vertical-align: top; }
  .lines td.right { text-align: right; font-variant-numeric: tabular-nums; }
  .lines .details { font-size: 8.5pt; color: #78716C; margin-top: 2px; }

  .totals { width: 280px; float: right; margin-top: 10px; }
  .totals tr td { padding: 4px 8px; font-size: 9.5pt; color: #44403C; }
  .totals .value { text-align: right; font-variant-numeric: tabular-nums; }
  .totals .grand-row td {
    border-top: 2px solid #1C1917; padding-top: 8px;
    font-weight: 700; font-size: 11.5pt; color: #1C1917;
  }

  .notes { clear: both; margin-top: 26px; font-size: 9pt; color: #44403C; line-height: 1.6; }
  .footer { margin-top: 18px; font-size: 8.5pt; color: #78716C; }
</style>
</head>
<body>

<div class="stationery-bg"><img src="{{ $company->stationery_data }}" alt=""></div>

<table class="doc-meta-row">
  <tr>
    <td>
      <div class="doc-title">{{ __('doc.invoice_tc') }}</div>
      <div class="doc-sub">
        @if($invoice->number){{ $invoice->number }}@else<span class="badge">{{ __('doc.draft_tc') }}</span>@endif
      </div>
    </td>
    <td class="meta-block">
      <div><span class="label">{{ __('doc.invoice_date') }}:</span> {{ $invoice->invoice_date->translatedFormat('j F Y') }}</div>
      <div><span class="label">{{ __('doc.due_date') }}:</span> {{ $invoice->due_date->translatedFormat('j F Y') }}</div>
      @if($invoice->reference)<div><span class="label">{{ __('doc.reference') }}:</span> {{ $invoice->reference }}</div>@endif
    </td>
  </tr>
</table>

<div class="party">
  <div class="party-label">{{ __('doc.to') }}</div>
  <div class="party-name">{{ $invoice->customer_name }}</div>
  @if($invoice->customer_address_line)<div class="party-line">{{ $invoice->customer_address_line }}</div>@endif
  @if($invoice->customer_postal_code || $invoice->customer_city)
    <div class="party-line">{{ $invoice->customer_postal_code }} {{ $invoice->customer_city }}</div>
  @endif
  @if($invoice->customer_vat_number)<div class="party-line" style="color:#78716C;">{{ __('doc.vat_no') }} {{ $invoice->customer_vat_number }}</div>@endif
</div>

@php
  $hasDiscount = $invoice->lines->contains(fn ($l) => (float) ($l->discount_pct ?? 0) > 0);
@endphp
<table class="lines">
  <thead>
    <tr>
      <th style="width:{{ $hasDiscount ? 44 : 54 }}%;">{{ __('doc.description') }}</th>
      <th class="right" style="width:12%;">{{ __('doc.quantity') }}</th>
      <th class="right" style="width:15%;">{{ __('doc.price') }}</th>
      @if($hasDiscount)<th class="right" style="width:10%;">{{ __('doc.discount') }}</th>@endif
      <th class="right" style="width:15%;">{{ __('doc.total') }}</th>
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
        <td class="right">€&nbsp;{{ number_format($line->unit_price, 2, ',', '.') }}</td>
        @if($hasDiscount)<td class="right">{{ (float) ($line->discount_pct ?? 0) > 0 ? rtrim(rtrim(number_format($line->discount_pct, 2, ',', '.'), '0'), ',') . '%' : '—' }}</td>@endif
        <td class="right">€&nbsp;{{ number_format($line->line_subtotal, 2, ',', '.') }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

<table class="totals">
  <tr><td>{{ __('doc.subtotal') }}</td><td class="value">€&nbsp;{{ number_format($invoice->subtotal, 2, ',', '.') }}</td></tr>
  @if(is_array($invoice->vat_breakdown))
    @foreach($invoice->vat_breakdown as $rate => $amount)
      <tr><td>{{ __('doc.vat') }} {{ rtrim(rtrim(number_format((float) $rate, 2, ',', '.'), '0'), ',') }}%</td><td class="value">€&nbsp;{{ number_format((float) $amount, 2, ',', '.') }}</td></tr>
    @endforeach
  @endif
  @php
    $pdfAdvances = $invoice->payments()->where('kind', 'advance')->orderBy('paid_on')->get();
    $pdfPayable = max((float) $invoice->total - (float) $pdfAdvances->sum('amount'), 0);
  @endphp
  @if($pdfAdvances->isNotEmpty())
    <tr><td>{{ __('doc.total_incl_vat') }}</td><td class="value">€&nbsp;{{ number_format($invoice->total, 2, ',', '.') }}</td></tr>
    @foreach($pdfAdvances as $adv)
      <tr><td>{{ $adv->reference ?: __('doc.already_settled') }} ({{ $adv->paid_on->format('d-m-Y') }})</td><td class="value">-&nbsp;€&nbsp;{{ number_format($adv->amount, 2, ',', '.') }}</td></tr>
    @endforeach
    <tr class="grand-row"><td>{{ __('doc.amount_due') }}</td><td class="value">€&nbsp;{{ number_format($pdfPayable, 2, ',', '.') }}</td></tr>
  @else
    <tr class="grand-row"><td>{{ __('doc.total') }}</td><td class="value">€&nbsp;{{ number_format($invoice->total, 2, ',', '.') }}</td></tr>
  @endif
</table>

<div style="clear:both;"></div>

@if($invoice->notes)<div class="notes">{!! nl2br(e($invoice->notes)) !!}</div>@endif
@php($payQr = \App\Support\PaymentQr::forInvoice($invoice))
@if($company->iban || $payQr)
<div class="notes">
  <table style="width:100%; border-collapse:collapse;"><tr>
    <td style="vertical-align:middle; padding-right:12px;">
      @if($company->iban){!! __('doc.pay_instruction', ['days' => (int) $invoice->payment_terms, 'iban' => e($company->iban), 'name' => e($company->name)]) !!}@if($invoice->number){!! __('doc.pay_reference', ['number' => e($invoice->number)]) !!}@endif.@endif
      @if($payQr)<div style="margin-top:6px; color:#78716C; font-size:8pt;">{{ __('doc.pay_qr_hint') }}</div>@endif
    </td>
    @if($payQr)
    <td style="width:88px; text-align:center; vertical-align:middle;">
      <img src="{{ $payQr }}" style="width:74px; height:74px;" alt="QR">
      <div style="font-size:7pt; color:#78716C;">{{ __('doc.pay_qr_title') }}</div>
    </td>
    @endif
  </tr></table>
</div>
@endif
@if($invoice->footer)<div class="footer">{!! nl2br(e($invoice->footer)) !!}</div>@elseif($company->invoice_footer)<div class="footer">{!! nl2br(e($company->invoice_footer)) !!}</div>@endif

</body>
</html>
