@extends('layouts.marketing')

@section('title', 'Online aanmaning met lopende rente — ' . brand('name'))
@section('description', 'Stuur een laatste aanmaning met rente die per dag oploopt. Je klant reageert met één klik. Betaalt hij niet, dan gaat het dossier naar de deurwaarder.')

@php
  $partner = \App\Support\Market::incasso('partner_name');
  $faq = [
      ['Wat is een online aanmaning?', 'De laatste aanmaning vóór de deurwaarder, met een eigen pagina. Je klant krijgt een mail met de aanmaning en de factuur als PDF en een link. Op die pagina staat wat hij vandaag moet betalen: de hoofdsom en de wettelijke rente tot en met vandaag. Hij kan daar ook reageren.'],
      ['Welke rente en kosten rekent ' . brand('name') . '?', 'De wettelijke rente van dat moment: de handelsrente voor een zakelijke klant (artikel 6:119a BW) en de gewone wettelijke rente voor een particulier (artikel 6:119 BW). De incassokosten volgen de wettelijke staffel, met een minimum van € 40. Doe je mee aan de kleineondernemersregeling, dan komt er btw over de incassokosten bij.'],
      ['Wanneer zijn de incassokosten verschuldigd?', 'Na de termijn die in de aanmaning staat. Tot en met de laatste dag betaalt je klant alleen de hoofdsom en de rente. Bij een particulier is de termijn wettelijk minstens veertien dagen, te rekenen vanaf de dag na ontvangst. ' . brand('name') . ' houdt dat minimum aan en noemt het bedrag van de kosten in de aanmaning, zoals de wet vraagt.'],
      ['Wat kan mijn klant antwoorden?', 'Drie dingen: ik heb betaald, ik betaal uiterlijk op een dag die hij kiest, of ik ben het er niet mee eens, met de reden erbij. Je krijgt elk antwoord per mail. Een toegezegde betaaldatum is een erkenning van de schuld en stuit de verjaring.'],
      ['Wat gebeurt er als de termijn voorbij is?', 'Je krijgt een bericht. Op de factuur staat dan de knop Overdragen aan de deurwaarder. Met één klik gaat het dossier naar ' . $partner . ': de factuur, de aanmaning, de berekening van rente en kosten en het logboek met wat je klant heeft gedaan.'],
      ['Gaat het dossier vanzelf naar de deurwaarder?', 'Alleen als je daarvoor kiest. Bij het versturen vink je aan dat het dossier na de termijn automatisch overgaat. Dat gebeurt drie werkdagen na de laatste dag, zodat je een betaling van die dag nog kunt boeken, en je krijgt vooraf bericht. Heeft je klant een betaaldatum toegezegd of bezwaar gemaakt, dan gaat het dossier niet vanzelf over: dan beslis jij.'],
      ['Kan ik het eerst proberen zonder account?', 'Ja. Met de gratis tool maak je de aanmaning als PDF, met dezelfde berekening. Je verstuurt de brief dan zelf. Wil je hem daarna online versturen, dan neem je hem mee naar een proefaccount.'],
      ['Kan ik de aanmaning ook per post sturen?', 'Ja. De aanmaning is een PDF met een QR-code. Wie de code scant, komt op dezelfde pagina met het bedrag van vandaag.'],
  ];
@endphp

@push('styles')
<style>
  .steps { max-width: 760px; margin: 0 auto; counter-reset: step; list-style: none; padding: 0; }
  .steps li { position: relative; padding: 0 0 26px 58px; color: var(--text-2); line-height: 1.7; }
  .steps li::before { counter-increment: step; content: counter(step); position: absolute; left: 0; top: 0; width: 38px; height: 38px; border-radius: 50%; background: var(--brand-tint); border: 1px solid var(--brand-border); color: var(--brand); font-family: var(--font-display); font-weight: 700; display: flex; align-items: center; justify-content: center; }
  .steps li:not(:last-child)::after { content: ''; position: absolute; left: 19px; top: 44px; bottom: 6px; width: 1px; background: var(--border-strong); }
  .steps strong { display: block; color: var(--text); font-size: 17px; font-family: var(--font-display); margin-bottom: 2px; }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <span class="eyebrow">Betaald krijgen</span>
    <h1>De laatste aanmaning, online</h1>
    <p class="lead">Een aanmaning met een eigen pagina. Het bedrag loopt elke dag op met de wettelijke rente, je klant reageert met één klik en jij ziet wanneer hij haar heeft geopend. Betaalt hij niet, dan gaat het dossier met één klik naar de deurwaarder.</p>
    <div class="hero-ctas" style="margin-top:28px;">
      <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Probeer 14 dagen gratis →</a>
      <a href="{{ route('aanmaning-maken') }}" class="btn btn-secondary btn-lg">Maak gratis een aanmaning (PDF)</a>
    </div>
    <div class="hero-trust">Geen betaalgegevens nodig · € 12,10 per maand incl. btw · per maand opzegbaar</div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <h2>Wat er anders is dan een gewone aanmaning</h2>
      <p>Een brief of mail zegt wat er op de dag van schrijven openstond. Deze aanmaning zegt wat er vandaag openstaat.</p>
    </div>
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div>
        <div class="feature-title">Het bedrag van vandaag</div>
        <div class="feature-desc">Hoofdsom plus wettelijke rente, elke dag opnieuw berekend naar het percentage van dat moment. Je klant ziet ook wat er per dag bij komt.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div>
        <div class="feature-title">Reageren met één klik</div>
        <div class="feature-desc">Ik heb betaald, ik betaal uiterlijk op, of ik ben het er niet mee eens. Elk antwoord staat op schrift en komt bij jou in de mail.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></div>
        <div class="feature-title">Je ziet wanneer hij is geopend</div>
        <div class="feature-desc">Tijdstip en IP-adres komen in het logboek. "Ik heb niets ontvangen" is daarna lastig vol te houden.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
        <div class="feature-title">De termijn die de wet vraagt</div>
        <div class="feature-desc">Voor een particulier minstens veertien dagen, te rekenen vanaf de dag na ontvangst, met het bedrag van de incassokosten erbij. Voor een zakelijke klant kies je zelf de termijn.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
        <div class="feature-title">Brief als PDF, met QR-code</div>
        <div class="feature-desc">De aanmaning gaat als PDF mee met de mail, samen met de factuur. Stuur je haar ook per post, dan leidt de QR-code naar dezelfde pagina.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m14.5 12.5-8 8a2.119 2.119 0 1 1-3-3l8-8"/><path d="m16 16 6-6"/><path d="m8 8 6-6"/><path d="m9 7 8 8"/><path d="m21 11-8-8"/></svg></div>
        <div class="feature-title">Met één klik naar de deurwaarder</div>
        <div class="feature-desc">Is de termijn voorbij en is er niet betaald, dan draag je het dossier over aan {{ $partner }}. De aanmaning, de berekening en het logboek gaan mee.</div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <h2>Zo werkt het</h2>
      <p>Vanaf een factuur waarvan de betaaltermijn voorbij is.</p>
    </div>
    <ol class="steps">
      <li><strong>Je kiest Laatste aanmaning</strong>Je ziet de berekening: hoofdsom, rente tot vandaag en de incassokosten die na de termijn gelden. Je kiest zakelijk of particulier en de termijn.</li>
      <li><strong>Je klant krijgt de aanmaning</strong>Per mail, uit jouw naam, met de aanmaning en de factuur als PDF en een knop naar de pagina.</li>
      <li><strong>De pagina houdt het bedrag bij</strong>Elke dag komt de rente erbij. Je klant ziet tot wanneer hij zonder incassokosten kan betalen en maakt over met de QR-code van zijn bank of met iDEAL.</li>
      <li><strong>Je klant reageert</strong>Betaald, een betaaldatum of een bezwaar. Je krijgt een mail en ziet het antwoord op de factuur.</li>
      <li><strong>Termijn voorbij en niet betaald</strong>Je krijgt een bericht en draagt het dossier met één klik over aan de deurwaarder. Heb je automatisch overdragen aangevinkt, dan gaat het drie werkdagen later vanzelf.</li>
    </ol>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="prose">
      <h2>Wat er in de aanmaning staat</h2>
      <ul>
        <li><strong>Het openstaande bedrag</strong> van de factuur en de <strong>wettelijke rente</strong> tot en met de dag van verzenden.</li>
        <li><strong>De termijn</strong> waarbinnen zonder incassokosten betaald kan worden, met de laatste dag erbij.</li>
        <li><strong>Het bedrag van de incassokosten</strong> die daarna verschuldigd zijn, volgens de wettelijke staffel.</li>
        <li><strong>Wat er daarna gebeurt:</strong> de vordering gaat naar de gerechtsdeurwaarder.</li>
        <li><strong>Je rekeningnummer</strong> en het factuurnummer als omschrijving.</li>
      </ul>
      <p>De berekening rust op artikel 6:96, 6:119 en 6:119a van het Burgerlijk Wetboek en het Besluit vergoeding voor buitengerechtelijke incassokosten. De aanmaning is van jou: ze gaat uit jouw naam en antwoorden komen bij jou. {{ brand('name') }} is geen incassobureau en geeft geen juridisch advies.</p>

      <h2>Meer lezen</h2>
      <p>Wil je eerst weten wat je mag vragen? Reken het uit met de <a href="{{ route('incassokosten-calculator') }}">calculator voor incassokosten en rente</a>. In de kennisbank staat uitleg over <a href="{{ route('kennisbank.artikel', 'betalingstermijn-aanmanen') }}">betalingstermijnen en aanmanen</a> en over <a href="{{ route('kennisbank.artikel', 'incassokosten-wettelijke-rente-berekenen') }}">incassokosten en wettelijke rente</a>. Hoe je de aanmaning verstuurt, lees je in het <a href="{{ route('help.article', 'online-aanmaning') }}">helpcentrum</a>.</p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <h2>Veelgestelde vragen</h2>
    </div>
    <div class="faq-list">
      @foreach ($faq as [$question, $answer])
        <details class="faq-item">
          <summary>{{ $question }}<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
          <div class="faq-content">{{ $answer }}</div>
        </details>
      @endforeach
    </div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Probeer het bij je oudste openstaande factuur</h2>
    <p>Zet je factuur erin, stuur de aanmaning en zie wat je klant doet. De eerste 14 dagen zijn gratis.</p>
    <div class="hero-ctas">
      <a href="{{ route('register') }}" class="btn btn-white btn-lg">Start gratis →</a>
    </div>
  </div>
</section>

<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(fn ($item) => [
        '@type' => 'Question',
        'name' => $item[0],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item[1]],
    ], $faq),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection
