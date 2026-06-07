@extends('layouts.marketing')

@section('title', 'Roadmap — EasyInvoice')
@section('description', 'Waar we aan werken. Bekijk wat er op de planning staat voor EasyInvoice.')

@push('styles')
<style>
  .road-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
  @media (max-width: 900px) { .road-grid { grid-template-columns: 1fr; } }
  .road-head { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 15px; margin-bottom: 16px; }
  .road-dot { width: 10px; height: 10px; border-radius: 50%; background: currentColor; }
  .road-idea { color: var(--text-4); }
  .road-doing { color: var(--brand); }
  .road-done { color: var(--success); }
  .road-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 18px; margin-bottom: 12px; }
  .road-card h4 { font-size: 16px; margin-bottom: 4px; }
  .road-card p { color: var(--text-2); font-size: 14px; margin: 0; line-height: 1.55; }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Roadmap</div>
    <h1>Waar we aan werken</h1>
    <p class="lead">EasyInvoice wordt gebouwd samen met onze gebruikers. Dit staat op de planning — heb je een wens? Laat het ons weten.</p>
    <div style="margin-top:24px;"><a href="{{ route('contact') }}" class="btn btn-primary">Deel je idee</a></div>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="road-grid">
      <div>
        <div class="road-head road-idea"><span class="road-dot"></span> In onderzoek</div>
        <div class="road-card"><h4>Koppeling met de bank</h4><p>Betalingen automatisch matchen via een PSD2-koppeling.</p></div>
        <div class="road-card"><h4>Offertes</h4><p>Offertes maken en met één klik omzetten naar een factuur.</p></div>
        <div class="road-card"><h4>Meerdere gebruikers</h4><p>Collega's toegang geven met eigen rechten.</p></div>
      </div>
      <div>
        <div class="road-head road-doing"><span class="road-dot"></span> In ontwikkeling</div>
        <div class="road-card"><h4>UBL / e-facturatie</h4><p>Facturen versturen in UBL-formaat (Peppol).</p></div>
        <div class="road-card"><h4>Terugkerende facturen</h4><p>Automatisch periodiek factureren voor abonnementen.</p></div>
        <div class="road-card"><h4>Export naar boekhouder</h4><p>Eenvoudige export voor je accountant.</p></div>
      </div>
      <div>
        <div class="road-head road-done"><span class="road-dot"></span> Onlangs geleverd</div>
        <div class="road-card"><h4>EASY-assistent</h4><p>Slimme assistent die met je meedenkt.</p></div>
        <div class="road-card"><h4>Incasso in fases</h4><p>Gefaseerd incassotraject.</p></div>
        <div class="road-card"><h4>Creditfacturen</h4><p>Creditnota's en deelbetalingen.</p></div>
      </div>
    </div>
  </div>
</section>
@endsection
