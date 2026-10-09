{{--
  Van het factuurtotaal naar "Te betalen".

  Staat er een betaling, aanbetaling, verrekening of kwijtschelding op de
  factuur, dan komt eerst het totaal incl. btw, dan per post een regel met
  minteken, en dan het restant. Zonder zo'n post alleen de eindregel.

  Gedeeld door de vier factuursjablonen; de klassen en het opschrift van de
  eindregel geven zij mee:
    pdfLabelClass  klasse van de omschrijvingscel ('label' of leeg)
    pdfValueClass  klasse van de bedragcel op de eindregel ('value' of 'value brand')
    pdfGrandLabel  sleutel voor de eindregel als er niets is afgetrokken
                   (minimal en stationery zeggen daar "Totaal")
--}}
@php
  $pdfSettlements = $invoice->documentSettlements();
  $pdfPayable = max((float) $invoice->total - (float) $pdfSettlements->sum('amount'), 0);
  $pdfLabelClass = $pdfLabelClass ?? '';
  $pdfValueClass = $pdfValueClass ?? 'value';
  $pdfDueLabel = $invoice->is_credit ? 'doc.amount_credited' : 'doc.amount_due';
@endphp
@if($pdfSettlements->isNotEmpty())
  <tr><td class="{{ $pdfLabelClass }}">{{ __('doc.total_incl_vat') }}</td><td class="value">{{ money($pdfSign * $invoice->total) }}</td></tr>
  @foreach($pdfSettlements as $pdfSettlement)
    <tr><td class="{{ $pdfLabelClass }}">{{ $pdfSettlement->documentLabel() }}@if($pdfSettlement->paid_on) ({{ $pdfSettlement->paid_on->format(market('date_format')) }})@endif</td><td class="value">-&nbsp;{{ money($pdfSettlement->amount) }}</td></tr>
  @endforeach
  <tr class="grand-row"><td>{{ __($pdfDueLabel) }}</td><td class="{{ $pdfValueClass }}">{{ money($pdfSign * $pdfPayable) }}</td></tr>
@else
  <tr class="grand-row"><td>{{ __($pdfGrandLabel ?? $pdfDueLabel) }}</td><td class="{{ $pdfValueClass }}">{{ money($pdfSign * $invoice->total) }}</td></tr>
@endif
