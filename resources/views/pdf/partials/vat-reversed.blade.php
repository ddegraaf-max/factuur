{{-- Btw verlegd: de wettelijk verplichte vermelding, met het btw-nummer van de afnemer. --}}
@if($doc->vat_reversed)
<div class="notes"><strong>{{ __('doc.vat_reversed') }}.</strong> {{ __('doc.vat_reversed_note', ['name' => $doc->customer_name, 'number' => $doc->customer_vat_number ?: '—']) }}</div>
@endif
