{{-- Podsumowanie stawek VAT: op een Poolse faktura VAT verplicht — netto, VAT en
     brutto per stawka. Alleen in de Poolse markt; in Nederland verandert de PDF niet.
     Verwacht $invoice (of $document) met ->lines, ->subtotal en ->vat_breakdown
     (tarief => btw-bedrag, zie App\Services\VatCalculator). Inline-stijlen zodat
     het tabelletje in elk factuursjabloon hetzelfde oogt. --}}
@php
  $vsDoc = $document ?? $invoice;
  $vsRows = [];
  if (\App\Support\Market::isPl() && is_array($vsDoc->vat_breakdown) && $vsDoc->vat_breakdown !== []) {
      foreach ($vsDoc->vat_breakdown as $vsRate => $vsVat) {
          // Netto per tarief = som van de regelbedragen (excl. btw, na korting) met dat tarief;
          // zonder regels (bijv. overgenomen factuur) is het subtotaal het netto van het enige tarief.
          $vsNet = $vsDoc->lines->isEmpty() && count($vsDoc->vat_breakdown) === 1
              ? (float) $vsDoc->subtotal
              : (float) $vsDoc->lines->filter(fn ($l) => abs((float) $l->vat_rate - (float) $vsRate) < 0.005)->sum('line_subtotal');
          $vsRows[] = ['rate' => (float) $vsRate, 'net' => $vsNet, 'vat' => (float) $vsVat, 'gross' => $vsNet + (float) $vsVat];
      }
  }
  $vsTh = 'font-size:7.5pt; text-transform:uppercase; letter-spacing:0.05em; color:#78716C; font-weight:700; padding:2px 6px; border-bottom:1px solid #D6D3D1; text-align:right;';
  $vsTd = 'font-size:8.5pt; padding:3px 6px; border-bottom:1px solid #EFEEEC; text-align:right; white-space:nowrap;';
  $vsSum = 'font-size:8.5pt; padding:4px 6px; text-align:right; font-weight:700; white-space:nowrap;';
@endphp
@if($vsRows !== [])
<div style="clear:both;"></div>
<div style="float:right; width:290px; margin-top:10px;">
  <div style="font-size:7.5pt; text-transform:uppercase; letter-spacing:0.06em; color:#78716C; font-weight:700; margin-bottom:3px;">{{ __('doc.vat_summary') }}</div>
  <table style="width:100%; border-collapse:collapse;">
    <thead>
      <tr>
        <th style="{{ $vsTh }} text-align:left;">{{ __('doc.vat') }}</th>
        <th style="{{ $vsTh }}">{{ __('doc.net') }}</th>
        <th style="{{ $vsTh }}">{{ __('doc.vat') }}</th>
        <th style="{{ $vsTh }}">{{ __('doc.gross') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach($vsRows as $vsRow)
        <tr>
          <td style="{{ $vsTd }} text-align:left;">{{ rtrim(rtrim(number_format($vsRow['rate'], 2, ',', '.'), '0'), ',') }}%</td>
          <td style="{{ $vsTd }}">{{ money($vsRow['net']) }}</td>
          <td style="{{ $vsTd }}">{{ money($vsRow['vat']) }}</td>
          <td style="{{ $vsTd }}">{{ money($vsRow['gross']) }}</td>
        </tr>
      @endforeach
      @if(count($vsRows) > 1)
        <tr>
          <td style="{{ $vsSum }} text-align:left;">{{ __('doc.total') }}</td>
          <td style="{{ $vsSum }}">{{ money(array_sum(array_column($vsRows, 'net'))) }}</td>
          <td style="{{ $vsSum }}">{{ money(array_sum(array_column($vsRows, 'vat'))) }}</td>
          <td style="{{ $vsSum }}">{{ money(array_sum(array_column($vsRows, 'gross'))) }}</td>
        </tr>
      @endif
    </tbody>
  </table>
</div>
@endif
