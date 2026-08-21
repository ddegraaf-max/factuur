@extends('layouts.marketing')

@section('title', 'Status van de EasyInvoice-systemen — live en actueel')
@section('description', 'Live status van de EasyInvoice-systemen: app, e-mailbezorging en betalingen. Bij een storing lees je hier direct wat er speelt en waar we aan werken.')

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
        <h1>{{ $allOk ? 'Alle systemen operationeel' : 'Storing bij een of meer onderdelen' }}</h1>
        <p style="color:var(--text-3);margin:2px 0 0;font-size:14px;">
          Laatst gecontroleerd: {{ $checkedAt->format('d-m-Y H:i') }} uur · live
        </p>
      </div>
    </div>

    <div class="status-board">
      @foreach ($components as $component)
        <div class="status-row">
          <span style="font-weight:500;">{{ $component['label'] }}</span>
          @if ($component['ok'])
            <span class="status-tag ok"><span class="dot"></span> Operationeel</span>
          @else
            <span class="status-tag down"><span class="dot"></span> Storing</span>
          @endif
        </div>
      @endforeach
    </div>

    <p class="status-note">
      Deze pagina controleert de systemen <strong>live</strong> op het moment dat je hem opent —
      er wordt niets vooraf ingevuld. Ervaar je toch een probleem dat hier niet wordt getoond?
      Mail ons op <a href="mailto:hallo@easyinvoice.nl" style="color:var(--brand);font-weight:500;">hallo@easyinvoice.nl</a>.
    </p>
  </div>
</section>
@endsection
