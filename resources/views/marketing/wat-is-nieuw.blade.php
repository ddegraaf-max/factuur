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
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 15 mei 2026 · v2.4</div>
        <h3>EASY-assistent: je slimme hulp</h3>
        <ul class="tl-list">
          <li>Stel vragen over je administratie en krijg direct antwoord.</li>
          <li>Suggesties terwijl je een factuur opstelt.</li>
          <li>Snelkoppelingen naar veelgebruikte acties.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--success-bg);color:var(--success);border-color:#6EE7B7;">Verbetering</span> 2 april 2026 · v2.3</div>
        <h3>Incassotraject in fases</h3>
        <ul class="tl-list">
          <li>Gefaseerd traject: herinnering → aanmaning → incasso.</li>
          <li>Per fase de status en datum bijhouden.</li>
          <li>Overzichtspagina met alle lopende trajecten.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 10 maart 2026 · v2.2</div>
        <h3>Creditfacturen &amp; deelbetalingen</h3>
        <ul class="tl-list">
          <li>Maak in één klik een creditnota op een bestaande factuur.</li>
          <li>Registreer deelbetalingen met automatische statusupdate.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--success-bg);color:var(--success);border-color:#6EE7B7;">Verbetering</span> 5 februari 2026 · v2.1</div>
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
