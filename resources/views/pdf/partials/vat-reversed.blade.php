{{-- Document zonder btw-bedragen: de wettelijk verplichte vermelding. Bij btw verlegd met het
     btw-nummer van de afnemer, bij de kleineondernemersregeling de vrijstelling. --}}
@if($doc->vatTreatment() === 'exempt')
<div class="notes">{{ __('doc.vat_exempt_note') }}</div>
@elseif($doc->vatTreatment() === 'reversed')
<div class="notes"><strong>{{ __('doc.vat_reversed') }}.</strong> {{ __('doc.vat_reversed_note', ['name' => $doc->customer_name, 'number' => $doc->customer_vat_number ?: '—']) }}</div>
@endif
