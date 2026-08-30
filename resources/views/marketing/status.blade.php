@extends('layouts.marketing')

@section('title', __('Status van de :brand-systemen — live en actueel', ['brand' => brand('name')]))
@section('description', __('Live status van de :brand-systemen: app, e-mailbezorging en betalingen. Bij een storing lees je hier direct wat er speelt en waar we aan werken.', ['brand' => brand('name')]))

@push('styles')
<style>
  .status-wrap { max-width: 760px; margin: 0 auto; }
  .status-banner { display: flex; align-items: center; gap: 16px; border-radius: 16px; padding: 24px 28px; }
  .status-banner.ok { background: var(--success-bg); border: 1px solid #6EE7B7; }
  .status-banner.down { background: var(--brand-tint); border: 1px solid var(--brand-border); }
  .status-big-dot { width: 16px; height: 16px; border-radius: 50%; flex-shrink: 0; }
  .status-banner.ok .status-big-dot { background: var(--success); box-shadow: 0 0 0 5px rgba(5,150,105,0.15); }
  .status-banner.down .status-big-dot { background: var(--brand); box-shadow: 0 0 0 5px rgba(232,35,31,0.15); }
  .status-banner h1 { font-size: 26px; }
  .status-board { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; margin-top: 24px; }
  .status-row { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-top: 1px solid var(--border); }
  .status-row:first-child { border-top: none; }
  .status-tag { display: inline-flex; align-items: center; gap: 7px; font-size: 13px; font-weight: 600; }
  .status-tag.ok { color: var(--success); }
  .status-tag.down { color: var(--brand-darker); }
  .status-tag .dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }
  .status-note { margin-top: 22px; font-size: 13px; color: var(--text-3); line-height: 1.6; }
</style>
@endpush

@section('content')
<section class="section" style="padding-top:56px;padding-bottom:80px;">
  <div class="container status-wrap">
    <div class="status-banner {{ $allOk ? 'ok' : 'down' }}">
      <span class="status-big-dot"></span>
      <div>
        <h1>{{ $allOk ? __('Alle systemen operationeel') : __('Storing bij een of meer onderdelen') }}</h1>
        <p style="color:var(--text-3);margin:2px 0 0;font-size:14px;">
          {{ __('Laatst gecontroleerd: :time uur · live', ['time' => $checkedAt->format(market('date_format') . ' H:i')]) }}
        </p>
      </div>
    </div>

    <div class="status-board">
      @foreach ($components as $component)
        <div class="status-row">
          <span style="font-weight:500;">{{ $component['label'] }}</span>
          @if ($component['ok'])
            <span class="status-tag ok"><span class="dot"></span> {{ __('Operationeel') }}</span>
          @else
            <span class="status-tag down"><span class="dot"></span> {{ __('Storing') }}</span>
          @endif
        </div>
      @endforeach
    </div>

    <p class="status-note">
      {!! __('Deze pagina controleert de systemen <strong>live</strong> op het moment dat je hem opent — er wordt niets vooraf ingevuld. Ervaar je toch een probleem dat hier niet wordt getoond? Mail ons op') !!}
      <a href="mailto:{{ brand('email') }}" style="color:var(--brand);font-weight:500;">{{ brand('email') }}</a>.
    </p>

    <div class="status-note" style="margin-top:28px;">
      <h2 style="font-size:16px;color:var(--text);margin-bottom:8px;">{{ __('Wat controleren we hier?') }}</h2>
      <p style="margin:0 0 14px;">
        {{ __('We meten de onderdelen waar je administratie van afhankelijk is: de applicatie zelf, de database waarin je facturen en klanten staan, de e-mailbezorging (factuur- en offertemails, herinneringen) en de betaalkoppelingen. Elke controle gebeurt op het moment dat jij deze pagina opent, dus wat je hier ziet is de werkelijke situatie van nu — geen cache en geen handmatig bijgewerkte melding.') }}
      </p>
      <h2 style="font-size:16px;color:var(--text);margin-bottom:8px;">{{ __('Wat als er een storing is?') }}</h2>
      <p style="margin:0;">
        {{ __('Je gegevens zijn ook tijdens een storing veilig: alles staat versleuteld op servers binnen de EU en wordt dagelijks geback-upt. Geplande factuurverzendingen en herinneringen worden na herstel automatisch alsnog verwerkt — je hoeft zelf niets opnieuw te doen. Duurt een storing langer of heb je er direct last van, mail dan gerust naar') }}
        <a href="mailto:{{ brand('email') }}" style="color:var(--brand);font-weight:500;">{{ brand('email') }}</a>
        {{ __('— ook tijdens een storing lezen we mee. Zie ook het') }}
        <a href="{{ route('helpcentrum') }}" style="color:var(--brand);font-weight:500;">{{ __('helpcentrum') }}</a>
        {{ __('voor antwoorden op veelvoorkomende vragen.') }}
      </p>
    </div>
  </div>
</section>
@endsection
