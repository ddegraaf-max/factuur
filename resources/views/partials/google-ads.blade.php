{{-- Google Ads-tag (1.101.1): alleen voor merken met een tag in config/brand.php (Fachora). De basistag staat op
     elke pagina (nodig om de klik-id van een advertentie vast te houden); het conversie-event op de pagina direct
     na het indienen van een aanvraag (1.107.1, zelfde browser als de klik) en na de bevestiging via de mail, via
     session('ads_conversion') uit de controller. Geen cookies van onszelf, geen persoonsgegevens in het event. --}}
@php($adsTag = brand('google_ads_tag'))
@if($adsTag)
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $adsTag }}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '{{ $adsTag }}');
  @if(session('ads_conversion') && brand('google_ads_lead_label'))
  gtag('event', 'conversion', {'send_to': '{{ $adsTag }}/{{ brand('google_ads_lead_label') }}', 'value': 1.0, 'currency': 'PLN'});
  @endif
</script>
@endif
