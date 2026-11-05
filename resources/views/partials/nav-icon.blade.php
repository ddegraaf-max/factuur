{{-- Klein icoon in een knop van de marketingbalk (config/marketing.php: cta_icon, secondary.icon). Onbekend of leeg: niets. --}}
@php
    $navIcons = [
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    ];
@endphp
@if(! empty($name) && isset($navIcons[$name]))<svg class="bi" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $navIcons[$name] !!}</svg>@endif
