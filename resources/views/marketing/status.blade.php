@extends('layouts.marketing')

@section('title', 'Status — EasyInvoice')
@section('description', 'Actuele status van de EasyInvoice-systemen en uptime.')

@push('styles')
<style>
  .status-wrap { max-width: 760px; margin: 0 auto; }
  .status-banner { display: flex; align-items: center; gap: 16px; background: var(--success-bg); border: 1px solid #6EE7B7; border-radius: 16px; padding: 24px 28px; }
  .status-big-dot { width: 16px; height: 16px; border-radius: 50%; background: var(--success); box-shadow: 0 0 0 5px rgba(5,150,105,0.15); flex-shrink: 0; }
  .status-banner h1 { font-size: 26px; }
  .status-board { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; margin-top: 24px; }
  .status-row { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-top: 1px solid var(--border); }
  .status-row:first-child { border-top: none; }
  .status-tag { display: inline-flex; align-items: center; gap: 7px; font-size: 13px; font-weight: 600; color: var(--success); }
  .status-tag .dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }
  .uptime-bar { display: flex; gap: 2px; align-items: flex-end; }
  .uptime-tick { flex: 1; height: 32px; border-radius: 2px; background: var(--success); opacity: 0.85; }
  .uptime-tick.down { background: var(--warning); }
  .hist-row { display: flex; gap: 16px; align-items: baseline; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 16px 20px; margin-bottom: 10px; }
  .hist-date { font-size: 13px; color: var(--text-3); white-space: nowrap; min-width: 96px; }
</style>
@endpush

@section('content')
<section class="section" style="padding-top:56px;padding-bottom:80px;">
  <div class="container status-wrap">
    <div class="status-banner">
      <span class="status-big-dot"></span>
      <div>
        <h1>Alle systemen operationeel</h1>
        <p style="color:var(--text-3);margin:2px 0 0;font-size:14px;">Laatst bijgewerkt: 7 juni 2026, 09:00 uur</p>
      </div>
    </div>

    <div class="status-board">
      @foreach ([
        'Webapplicatie',
        'Facturen & PDF-generatie',
        'E-mail versturen',
        'Herinneringen & incasso',
        'Inloggen & 2FA',
        'Database',
      ] as $component)
        <div class="status-row">
          <span style="font-weight:500;">{{ $component }}</span>
          <span class="status-tag"><span class="dot"></span> Operationeel</span>
        </div>
      @endforeach
    </div>

    <div style="display:flex;align-items:baseline;justify-content:space-between;margin:36px 0 14px;">
      <h2 style="font-size:19px;">Uptime laatste 90 dagen</h2>
      <span style="font-weight:700;color:var(--success);">99,98%</span>
    </div>
    <div class="uptime-bar">
      @for ($i = 1; $i <= 90; $i++)
        <span class="uptime-tick {{ $i === 30 ? 'down' : '' }}"></span>
      @endfor
    </div>

    <h2 style="font-size:19px;margin:36px 0 14px;">Recente geschiedenis</h2>
    <div class="hist-row"><span class="hist-date">7 juni 2026</span><span>Geen incidenten — alles werkte normaal.</span></div>
    <div class="hist-row"><span class="hist-date">6 juni 2026</span><span>Geen incidenten — alles werkte normaal.</span></div>
    <div class="hist-row"><span class="hist-date">5 juni 2026</span><span>Gepland onderhoud (02:00–02:20). Geen merkbare onderbreking.</span></div>
    <div class="hist-row"><span class="hist-date">4 juni 2026</span><span>Geen incidenten — alles werkte normaal.</span></div>
    <div class="hist-row"><span class="hist-date">3 juni 2026</span><span>Geen incidenten — alles werkte normaal.</span></div>
  </div>
</section>
@endsection
