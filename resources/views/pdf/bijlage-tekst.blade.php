<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="utf-8">
<style>
  @page { margin: 16mm 15mm 18mm 15mm; }
  /* DejaVu Sans: ingebouwd in DomPDF en met volledige unicode (€, accenten). */
  body {
    font-family: {{ $company->invoice_font === 'serif' ? 'serif' : "'DejaVu Sans', sans-serif" }};
    font-size: 9.5pt; color: #1c1917; line-height: 1.6; margin: 0;
  }
  .header { width: 100%; margin-bottom: 20px; border-bottom: 3px solid {{ $company->brand_color }}; padding-bottom: 14px; }
  .header td { vertical-align: bottom; }
  .logo-mark {
    width: 40px; height: 40px; background: {{ $company->brand_color }}; border-radius: 9px;
    display: inline-block; text-align: center; color: white; font-weight: bold;
    font-size: 19px; line-height: 40px;
  }
  .doc-title { font-size: 19pt; font-weight: 800; letter-spacing: -0.5px; margin: 0; color: {{ $company->brand_color }}; }
  .doc-meta { font-size: 8pt; color: #78716c; margin-top: 3px; }

  h1 { font-size: 14pt; margin: 14pt 0 5pt; color: {{ $company->brand_color }}; }
  h2 { font-size: 11.5pt; margin: 14pt 0 5pt; }
  h3 { font-size: 10pt; margin: 12pt 0 4pt; }
  p { margin: 6pt 0; }
  ul, ol { margin: 6pt 0; padding-left: 16pt; }
  li { margin: 2pt 0; }
  strong { font-weight: bold; }
  blockquote { margin: 8pt 0; padding: 6pt 10pt; border-left: 3px solid {{ $company->brand_color }}; background: #fafaf9; color: #57534e; }
  table { border-collapse: collapse; width: 100%; margin: 8pt 0; }
  th, td { border: 1px solid #e7e5e4; padding: 4pt 7pt; font-size: 8.5pt; text-align: left; }
  th { background: #fafaf9; text-transform: uppercase; font-size: 7.5pt; letter-spacing: 0.05em; color: #78716c; border-bottom: 2px solid {{ $company->brand_color }}; }
  code { font-family: 'Courier', monospace; font-size: 8.5pt; background: #f5f5f4; padding: 1pt 3pt; }
  hr { border: none; border-top: 1px solid #e7e5e4; margin: 12pt 0; }
  .footer { margin-top: 26pt; padding-top: 10pt; border-top: 1px solid #e7e5e4; font-size: 8pt; color: #a8a29e; text-align: center; }
</style>
</head>
<body>
@php
  $hasGd = extension_loaded('gd');
  $scale = max(50, min(200, (int) ($company->logo_scale ?? 100))) / 100;
  $hasLogo = $hasGd && ($company->logo_data || $company->logo_path);
@endphp
<table class="header">
  <tr>
    <td>
      @if($hasLogo && $company->logo_data)
        <img src="{{ $company->logo_data }}" style="max-height: {{ round(46 * $scale) }}px; max-width: {{ round(170 * $scale) }}px;" alt="">
      @elseif($hasLogo && $company->logo_path)
        <img src="{{ public_path('storage/' . $company->logo_path) }}" style="max-height: {{ round(46 * $scale) }}px; max-width: {{ round(170 * $scale) }}px;" alt="">
      @else
        <div class="logo-mark">{{ strtoupper(substr($company->name ?? 'E', 0, 1)) }}</div>
      @endif
    </td>
    <td style="text-align: right;">
      <div class="doc-title">{{ $title }}</div>
      <div class="doc-meta">{{ $company->name }} · bijlage bij {{ $documentLabel }}</div>
    </td>
  </tr>
</table>

{!! $html !!}

<div class="footer">{{ $company->name }}@if($company->email) · {{ $company->email }}@endif @if($company->phone) · {{ $company->phone }}@endif</div>
</body>
</html>
