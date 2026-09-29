{{-- Laatste aanmaning (online aanmaning) als brief. DejaVu Sans: kent ook de tekens van Engelse en Poolse facturen. --}}
@php
  $brandColor = $company->brand_color ?: brand('color');
  $text = \App\Support\DemandText::letter($demand, $claim);
  $day = fn ($d) => $d ? $d->translatedFormat('j F Y') : '—';
  // Het adres van de pagina op twee regels: de code is te lang voor één regel.
  $online = filled($demand->token);
  $url = $online ? $demand->url() : '';
  $cut = $online ? strrpos($url, '/') + 1 : 0;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<style>
  @page { margin: 18mm 20mm 18mm 20mm; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; color: #1F1D1A; line-height: 1.45; }
  .head { width: 100%; border-collapse: collapse; margin-bottom: 14pt; }
  .head td { vertical-align: top; }
  .party { font-size: 9.5pt; }
  .party .lbl { font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.08em; color: #8A8681; margin-bottom: 3pt; }
  .party .nm { font-weight: bold; font-size: 10.5pt; }
  h1 { font-size: 16pt; margin: 0 0 3pt; color: {!! $brandColor !!}; letter-spacing: 0.02em; }
  .sub { font-size: 9pt; color: #8A8681; margin-bottom: 10pt; }
  p { margin: 0 0 8pt; }
  table.claim { width: 100%; border-collapse: collapse; margin: 8pt 0 12pt; }
  table.claim td { padding: 5pt 6pt; border-bottom: 1px solid #E4E0D9; }
  table.claim td.r { text-align: right; white-space: nowrap; }
  table.claim tr.tot td { border-top: 2px solid {!! $brandColor !!}; border-bottom: none; font-weight: bold; font-size: 11pt; }
  .box { border: 1px solid #D6D3CE; padding: 8pt 10pt; margin: 10pt 0; background: #FAF8F5; }
  table.online { width: 100%; border-collapse: collapse; margin-top: 10pt; page-break-inside: avoid; }
  table.online td { vertical-align: top; }
  .url { font-family: 'DejaVu Sans Mono', monospace; font-size: 7pt; color: #57534E; }
  .legal { font-size: 7.5pt; color: #8A8681; margin-top: 12pt; }
  .foot { position: fixed; bottom: -8mm; left: 0; right: 0; font-size: 7.5pt; color: #8A8681; text-align: center; }
</style>
</head>
<body>
<table class="head">
  <tr>
    <td style="width:50%;">
      <div class="party">
        <div class="lbl">{{ $text['l_creditor'] }}</div>
        <div class="nm">{{ $company->name }}</div>
        @if($company->address_line)<div>{{ $company->address_line }}</div>@endif
        @if($company->postal_code || $company->city)<div>{{ trim($company->postal_code . ' ' . $company->city) }}</div>@endif
        @if($company->kvk_number)<div>{{ market('registry.short') }} {{ $company->kvk_number }}</div>@endif
        @if($company->email)<div>{{ $company->email }}</div>@endif
        @if($company->phone)<div>{{ $company->phone }}</div>@endif
      </div>
    </td>
    <td style="width:50%; padding-left: 24pt;">
      <div class="party">
        <div class="lbl">{{ $text['l_debtor'] }}</div>
        <div class="nm">{{ $invoice->customer_name }}</div>
        @if($invoice->customer_address_line)<div>{{ $invoice->customer_address_line }}</div>@endif
        @if($invoice->customer_postal_code || $invoice->customer_city)<div>{{ trim(($invoice->customer_postal_code ?? '') . ' ' . ($invoice->customer_city ?? '')) }}</div>@endif
        @if($demand->sent_to)<div>{{ $demand->sent_to }}</div>@endif
      </div>
      <div style="margin-top:10pt; font-size:9pt; color:#8A8681;">{{ $company->city ? $company->city . ', ' : '' }}{{ $day($claim['on']) }}</div>
    </td>
  </tr>
</table>

<h1>{{ mb_strtoupper($text['title']) }}</h1>
<div class="sub">{{ $text['subtitle'] }}</div>

<p>{{ $text['salutation'] }}</p>
<p>{{ $text['intro'] }}</p>

<table class="claim">
  <tr><td>{{ $text['l_principal'] }}</td><td class="r">{{ money($claim['principal']) }}</td></tr>
  @if($claim['with_interest'])
    <tr><td>{{ $text['l_interest'] }}</td><td class="r">{{ money($claim['interest']) }}</td></tr>
  @endif
  <tr class="tot"><td>{{ $text['l_total'] }}</td><td class="r">{{ money($claim['total']) }}</td></tr>
</table>

<p>{{ $text['term'] }}@if($text['interest']) {{ $text['interest'] }}@endif</p>
@if($text['bank'])<p>{{ $text['bank'] }}</p>@endif

<div class="box">{{ $text['consequence'] }}</div>

<table class="online">
  <tr>
    @if($qr)<td style="width:80pt;"><img src="{{ $qr }}" style="width:70pt;height:70pt;" alt=""></td>@endif
    <td>
      <p>{{ $text['respond'] }}</p>
      @if($online)
        <div>{{ $text['l_qr'] }}</div>
        <div class="url">{{ substr($url, 0, $cut) }}<br>{{ substr($url, $cut) }}</div>
      @endif
    </td>
  </tr>
</table>

<p style="margin-top:14pt;">{{ $text['regards'] }}<br><strong>{{ $company->name }}</strong></p>

<div class="legal">{{ $text['legal'] }}</div>

<div class="foot">@if($online){{ __('doc.mail_sent_via', ['name' => $company->name, 'brand' => brand('name')]) }}@else{{ __('Gemaakt met :brand, gratis aanmaningen maken op :domain', ['brand' => brand('name'), 'domain' => brand('domain')]) }}@endif</div>
</body>
</html>
