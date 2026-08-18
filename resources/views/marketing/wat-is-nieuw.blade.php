@extends('layouts.marketing')

@section('title', 'Wat is nieuw — EasyInvoice')
@section('description', 'De laatste updates en verbeteringen van EasyInvoice.')

@push('styles')
<style>
  .timeline { max-width: 760px; margin: 0 auto; position: relative; padding-left: 28px; }
  .timeline::before { content: ''; position: absolute; left: 7px; top: 8px; bottom: 8px; width: 2px; background: var(--border); }
  .tl-item { position: relative; padding-bottom: 36px; }
  .tl-item:last-child { padding-bottom: 0; }
  .tl-dot { position: absolute; left: -28px; top: 4px; width: 16px; height: 16px; border-radius: 50%; background: var(--brand); border: 3px solid var(--bg); }
  .tl-meta { display: flex; align-items: center; gap: 12px; font-size: 13px; color: var(--text-3); flex-wrap: wrap; }
  .tl-item h3 { font-size: 20px; margin: 8px 0 10px; }
  .tl-list { margin: 0; padding-left: 18px; color: var(--text-2); line-height: 1.7; }
  .tl-list li { margin-bottom: 6px; }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Wat is nieuw</div>
    <h1>Elke maand beter</h1>
    <p class="lead">We verbeteren EasyInvoice continu op basis van wat ondernemers nodig hebben. Hier vind je de laatste updates.</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="timeline">
      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 18 augustus 2026 · Easy 1.3.0</div>
        <h3>Klantenportaal, complete BTW-aangifte, inkoopfacturen &amp; teamrollen</h3>
        <ul class="tl-list">
          <li><b>Klantenportaal</b> — je klant bekijkt zijn facturen voortaan ook online, via een beveiligde link in de factuurmail (met eenmalige toegangscode per e-mail). Jij ziet in een inzagelog precies óf en wanneer je factuur is bekeken — inclusief een groen oogje in je facturenlijst.</li>
          <li><b>BTW-aangifte per kwartaal</b> — per kwartaal precies wat je invult bij de Belastingdienst: rubriek 1a, 1b en 1e, mét voorbelasting (5b) en het saldo dat je per kwartaal betaalt. Inclusief deadline-waarschuwing en PDF-download voor je administratie.</li>
          <li><b>Inkoopfacturen (crediteuren)</b> — boek binnengekomen facturen in, handmatig of met een foto van de bon (op je telefoon opent direct de camera). De BTW telt automatisch mee als voorbelasting in je aangifte, en je ziet altijd wat er bij leveranciers openstaat.</li>
          <li><b>Team &amp; rollen</b> — nodig collega's of je boekhouder gratis uit met een eigen rol: beheerder, medewerker of boekhouder (alleen inzien). Rechten worden ook server-side afgedwongen.</li>
          <li><b>Winst per maand op je dashboard</b> — de omzetgrafiek toont nu ook je inkoop en de winst (of het verlies) per maand, met vorig jaar als stippellijn erdoorheen zodat je de groei direct ziet.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 15 augustus 2026 · Easy 1.2.0</div>
        <h3>Offertes — van voorstel naar factuur in één klik</h3>
        <ul class="tl-list">
          <li>Maak een offerte met dezelfde regels, producten en btw-berekening als een factuur.</li>
          <li>Versturen gaat per e-mail met een eigen offerte-PDF in je huisstijl, inclusief geldigheidsdatum.</li>
          <li>Houd bij wat de klant besloot: geaccepteerd, afgewezen of verlopen — offertes verlopen automatisch.</li>
          <li>Akkoord? Eén klik en de offerte wordt een concept-factuur.</li>
          <li>Daarnaast: een <strong>dagelijks overzicht</strong> in je mailbox, een knop om zelf een <strong>herinnering</strong> te sturen, en de mogelijkheid om <strong>prijzen inclusief btw</strong> in te voeren.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 15 augustus 2026 · Easy 1.1.0</div>
        <h3>Terugkerende facturen, e-facturatie (UBL) &amp; export voor je boekhouder</h3>
        <ul class="tl-list">
          <li><b>Terugkerende facturen</b> — zet elke factuur met één klik om in een terugkerend profiel. EasyInvoice factureert daarna automatisch per week, maand, kwartaal, half jaar of jaar: als concept om zelf te controleren, of direct verstuurd.</li>
          <li><b>UBL / e-facturatie</b> — elke verstuurde factuur bevat nu automatisch een UBL 2.1-bijlage (NLCIUS), die boekhoudpakketten direct kunnen inlezen. Ook los te downloaden op de factuurpagina.</li>
          <li><b>Export naar boekhouder</b> — download al je facturen als CSV met grondslag en BTW per tarief én controletotalen. Kies zelf de periode, bijvoorbeeld per kwartaal voor de BTW-aangifte.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 15 mei 2026 · Easy 1.0.0</div>
        <h3>EASY-assistent: je slimme hulp</h3>
        <ul class="tl-list">
          <li>Stel vragen over je administratie en krijg direct antwoord.</li>
          <li>Suggesties terwijl je een factuur opstelt.</li>
          <li>Snelkoppelingen naar veelgebruikte acties.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--success-bg);color:var(--success);border-color:#6EE7B7;">Verbetering</span> 2 april 2026 · Easy 0.9</div>
        <h3>Incassotraject in fases</h3>
        <ul class="tl-list">
          <li>Gefaseerd traject: herinnering → aanmaning → incasso.</li>
          <li>Per fase de status en datum bijhouden.</li>
          <li>Overzichtspagina met alle lopende trajecten.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 10 maart 2026 · Easy 0.8</div>
        <h3>Creditfacturen &amp; deelbetalingen</h3>
        <ul class="tl-list">
          <li>Maak in één klik een creditnota op een bestaande factuur.</li>
          <li>Registreer deelbetalingen met automatische statusupdate.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--success-bg);color:var(--success);border-color:#6EE7B7;">Verbetering</span> 5 februari 2026 · Easy 0.7</div>
        <h3>Nieuwe factuursjablonen</h3>
        <ul class="tl-list">
          <li>Modern sjabloon met meer ruimte voor je huisstijl.</li>
          <li>Eigen accentkleur instelbaar.</li>
        </ul>
      </article>
    </div>

    <div style="text-align:center;margin-top:40px;">
      <a href="{{ route('roadmap') }}" class="btn btn-secondary">Bekijk de roadmap →</a>
    </div>
  </div>
</section>
@endsection
