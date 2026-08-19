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
        <div class="road-card"><h4>Betaallink op de factuur</h4><p>Klanten laten betalen met iDEAL via een betaallink in de factuurmail en het klantenportaal.</p></div>
      </div>
      <div>
        <div class="road-head road-doing"><span class="road-dot"></span> In ontwikkeling</div>
        <div class="road-card"><h4>Automatische bankkoppeling</h4><p>Transacties dagelijks automatisch binnenhalen via een beveiligde PSD2-koppeling — zonder maandkosten per rekening.</p></div>
        <div class="road-card"><h4>Boekhoudkoppelingen</h4><p>Directe koppeling met pakketten als Twinfield, e-Boekhouden en Exact.</p></div>
      </div>
      <div>
        <div class="road-head road-done"><span class="road-dot"></span> Onlangs geleverd</div>
        <div class="road-card"><h4>Meerdere administraties</h4><p>Meerdere bedrijven (eigen KvK en nummering) onder één inlog — met wisselen in twee klikken.</p></div>
        <div class="road-card"><h4>Facturen &amp; offertes in het Engels</h4><p>Per klant kiezen voor Engelstalige documenten en e-mails — voor internationale opdrachtgevers.</p></div>
        <div class="road-card"><h4>Kilometerregistratie</h4><p>Zakelijke ritten bijhouden en de kilometervergoeding met één klik doorbelasten — of bewaren voor je eigen administratie.</p></div>
        <div class="road-card"><h4>Meerdere handelsnamen</h4><p>Offertes en facturen versturen onder verschillende handelsnamen, elk met eigen logo, kleur en sjabloon — binnen één administratie.</p></div>
        <div class="road-card"><h4>Urenregistratie</h4><p>Uren schrijven per klant of project — handmatig of met de timer — en met één klik omzetten naar een conceptfactuur.</p></div>
        <div class="road-card"><h4>Bonnetjes automatisch herkennen</h4><p>Foto van de bon maken en de leverancier, datum en bedragen automatisch laten invullen (scan &amp; herken met AI).</p></div>
        <div class="road-card"><h4>Verzending via Peppol</h4><p>Automatische bereikbaarheidscheck en e-facturen (NLCIUS) rechtstreeks afleveren via het Peppol-netwerk.</p></div>
        <div class="road-card"><h4>KvK-koppeling</h4><p>Klanten toevoegen door het Handelsregister te doorzoeken — gegevens automatisch ingevuld.</p></div>
        <div class="road-card"><h4>Bank &amp; transacties</h4><p>Bankafschriften importeren (CAMT.053/MT940) en transacties met één klik koppelen aan facturen en inkoop.</p></div>
        <div class="road-card"><h4>Klantenportaal</h4><p>Je klant bekijkt zijn facturen online via een beveiligde link — en jij ziet in het inzagelog wanneer ze zijn geopend.</p></div>
        <div class="road-card"><h4>BTW-aangifte per kwartaal</h4><p>Rubriek 1a, 1b en 1e plus voorbelasting (5b) en het saldo per kwartaal — met PDF-download.</p></div>
        <div class="road-card"><h4>Inkoopfacturen (crediteuren)</h4><p>Inkoop inboeken, handmatig of met een foto van de bon. De BTW telt mee als voorbelasting.</p></div>
        <div class="road-card"><h4>Team &amp; rollen</h4><p>Collega's en je boekhouder gratis uitnodigen, ieder met eigen rechten.</p></div>
        <div class="road-card"><h4>Offertes</h4><p>Offertes maken, versturen en met één klik omzetten naar een factuur.</p></div>
        <div class="road-card"><h4>Terugkerende facturen</h4><p>Automatisch periodiek factureren voor abonnementen en vaste diensten.</p></div>
        <div class="road-card"><h4>UBL / e-facturatie</h4><p>Elke factuur als UBL 2.1 (NLCIUS) — te downloaden én automatisch als bijlage in de factuurmail.</p></div>
        <div class="road-card"><h4>Export naar boekhouder</h4><p>CSV-export van verkoop én inkoop met BTW per tarief, klaar voor je accountant.</p></div>
      </div>
    </div>
  </div>
</section>
@endsection
