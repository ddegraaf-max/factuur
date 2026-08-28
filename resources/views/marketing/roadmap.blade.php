@extends('layouts.marketing')

@section('title', 'Roadmap van EasyInvoice — waar we nu aan werken')
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
  .road-card h3 { font-size: 16px; margin-bottom: 4px; }
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
        <h2 class="road-head road-idea"><span class="road-dot"></span> In onderzoek</h2>
        <div class="road-card"><h3>BTW-aangifte direct indienen</h3><p>De kwartaalaangifte omzetbelasting rechtstreeks vanuit EasyInvoice indienen bij de Belastingdienst.</p></div>
        <div class="road-card"><h3>Meerdere valuta</h3><p>Factureren in dollars, ponden of andere valuta — met de juiste BTW-behandeling en de wisselkoers vastgelegd op de factuur.</p></div>
      </div>
      <div>
        <h2 class="road-head road-doing"><span class="road-dot"></span> In ontwikkeling</h2>
        <div class="road-card"><h3>Boekhoudkoppelingen</h3><p>Directe koppeling met pakketten als Twinfield, e-Boekhouden en Exact.</p></div>
      </div>
      <div>
        <h2 class="road-head road-done"><span class="road-dot"></span> Onlangs geleverd</h2>
        <div class="road-card"><h3>Termijnfacturen</h3><p>Grote projecten in delen factureren (bijv. 30% vooraf, 70% bij oplevering) — de laatste termijn is automatisch het restant.</p></div>
        <div class="road-card"><h3>Postvak IN verwerkt zichzelf</h3><p>Aangeleverde bonnen worden automatisch herkend: er ligt een kant-en-klaar boekingsvoorstel klaar dat je met één klik bevestigt.</p></div>
        <div class="road-card"><h3>Korting per factuurregel</h3><p>Een kortingspercentage per regel op facturen en offertes — netjes zichtbaar op de PDF, de BTW rekent automatisch mee.</p></div>
        <div class="road-card"><h3>Ouderdomsanalyse debiteuren</h3><p>Wie staat er hoe lang open (30/60/90+ dagen) — per klant, met de langst vervallen facturen bovenaan.</p></div>
        <div class="road-card"><h3>Winstgevendheid per klant</h3><p>Omzet, bestede uren en het effectieve uurtarief per klant in het rapport Klantomzet.</p></div>
        <div class="road-card"><h3>QR-code betalen</h3><p>Een scan-en-betaal QR-code op de factuur-PDF: je klant scant met de telefoon en betaalt direct via iDEAL.</p></div>
        <div class="road-card"><h3>Cashflow-prognose</h3><p>Wat komt er de komende maanden binnen en wat gaat eruit — op basis van openstaande facturen, terugkerende facturen en vaste lasten.</p></div>
        <div class="road-card"><h3>Aanpasbare e-mailteksten</h3><p>Eigen onderwerp en tekst voor de factuur- en offertemail, met variabelen zoals {klant} en {factuurnummer}.</p></div>
        <div class="road-card"><h3>Inkoopfacturen per e-mail</h3><p>Bonnen en facturen doorsturen naar je eigen inboek-adres — ze staan klaar in het Postvak IN, inclusief scan &amp; herken.</p></div>
        <div class="road-card"><h3>Vaste lasten</h3><p>Terugkerende kosten (huur, software, verzekeringen) automatisch periodiek inboeken als inkoopfactuur.</p></div>
        <div class="road-card"><h3>Jaaroverzicht</h3><p>Omzet, kosten en resultaat per kwartaal, met kilometeraftrek en PDF voor je boekhouder — de basis voor je aangifte.</p></div>
        <div class="road-card"><h3>Strippenkaarten &amp; tegoeden</h3><p>Vooraf betaalde urenbundels verkopen; geschreven uren tellen automatisch af van het tegoed.</p></div>
        <div class="road-card"><h3>Offertes digitaal ondertekenen</h3><p>Klanten geven akkoord met een digitale handtekening in het portaal — met bewijsdossier en de handtekening op de PDF.</p></div>
        <div class="road-card"><h3>Betaallink op de factuur</h3><p>Klanten betalen met iDEAL vanuit de factuurmail en het klantenportaal — via je eigen Mollie-account, direct geboekt op de factuur.</p></div>
        <div class="road-card"><h3>Meerdere administraties</h3><p>Meerdere bedrijven (eigen KvK en nummering) onder één inlog — met wisselen in twee klikken.</p></div>
        <div class="road-card"><h3>Facturen &amp; offertes in het Engels</h3><p>Per klant kiezen voor Engelstalige documenten en e-mails — voor internationale opdrachtgevers.</p></div>
        <div class="road-card"><h3>Kilometerregistratie</h3><p>Zakelijke ritten bijhouden en de kilometervergoeding met één klik doorbelasten — of bewaren voor je eigen administratie.</p></div>
        <div class="road-card"><h3>Meerdere handelsnamen</h3><p>Offertes en facturen versturen onder verschillende handelsnamen, elk met eigen logo, kleur en sjabloon — binnen één administratie.</p></div>
        <div class="road-card"><h3>Urenregistratie</h3><p>Uren schrijven per klant of project — handmatig of met de timer — en met één klik omzetten naar een conceptfactuur.</p></div>
        <div class="road-card"><h3>Bonnetjes automatisch herkennen</h3><p>Foto van de bon maken en de leverancier, datum en bedragen automatisch laten invullen (scan &amp; herken met AI).</p></div>
        <div class="road-card"><h3>Verzending via Peppol</h3><p>Automatische bereikbaarheidscheck en e-facturen (NLCIUS) rechtstreeks afleveren via het Peppol-netwerk.</p></div>
        <div class="road-card"><h3>KvK-koppeling</h3><p>Klanten toevoegen door het Handelsregister te doorzoeken — gegevens automatisch ingevuld.</p></div>
        <div class="road-card"><h3>Bank &amp; transacties</h3><p>Bankafschriften importeren (CAMT.053/MT940) en transacties met één klik koppelen aan facturen en inkoop.</p></div>
        <div class="road-card"><h3>Klantenportaal</h3><p>Je klant bekijkt zijn facturen online via een beveiligde link — en jij ziet in het inzagelog wanneer ze zijn geopend.</p></div>
        <div class="road-card"><h3>BTW-aangifte per kwartaal</h3><p>Rubriek 1a, 1b en 1e plus voorbelasting (5b) en het saldo per kwartaal — met PDF-download.</p></div>
        <div class="road-card"><h3>Inkoopfacturen (crediteuren)</h3><p>Inkoop inboeken, handmatig of met een foto van de bon. De BTW telt mee als voorbelasting.</p></div>
        <div class="road-card"><h3>Team &amp; rollen</h3><p>Collega's en je boekhouder gratis uitnodigen, ieder met eigen rechten.</p></div>
        <div class="road-card"><h3>Offertes</h3><p>Offertes maken, versturen en met één klik omzetten naar een factuur.</p></div>
        <div class="road-card"><h3>Terugkerende facturen</h3><p>Automatisch periodiek factureren voor abonnementen en vaste diensten.</p></div>
        <div class="road-card"><h3>UBL / e-facturatie</h3><p>Elke factuur als UBL 2.1 (NLCIUS) — te downloaden én automatisch als bijlage in de factuurmail.</p></div>
        <div class="road-card"><h3>Export naar boekhouder</h3><p>CSV-export van verkoop én inkoop met BTW per tarief, klaar voor je accountant.</p></div>
      </div>
    </div>
  </div>
</section>
@endsection
