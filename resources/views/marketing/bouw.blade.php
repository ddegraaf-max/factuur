@extends('layouts.marketing')

@section('title', 'Offerte- en factuurprogramma voor de bouw — ' . brand('name'))
@section('description', 'Offertes met handtekening, termijnfacturen en prijsaanvragen bij onderaannemers in één programma. Voor aannemers, klusbedrijven en zzp\'ers in de bouw.')

@php
  $faq = [
      ['Kan ik een offerte in termijnen factureren?', 'Ja. Bij een verstuurde of getekende offerte leg je een termijnplan vast, bijvoorbeeld 30% bij opdracht, 40% als de ruwbouw staat en 30% bij oplevering. Elke termijn wordt met één klik een factuur. De laatste termijn is altijd de rest, zodat het totaal tot op de cent gelijk is aan de offerte.'],
      ['Moeten onderaannemers een account hebben om te reageren?', 'Nee. Een onderaannemer krijgt een mail met een knop. Daarmee opent hij de aanvraag met de bijlagen, vult zijn prijs en de week waarin hij kan beginnen in en stuurt zijn eigen offerte als bestand mee. Een account is niet nodig.'],
      ['Wat stuur ik mee met een prijsaanvraag?', 'Een omschrijving van het werk, de plaats, de gewenste startweek en bijlagen zoals een tekening, bestek of foto\'s (PDF, PNG, JPG of WEBP). De prijs die je zelf aan je klant hebt gegeven, gaat nooit mee.'],
      ['Werkt het op de bouwplaats, op mijn telefoon?', 'Ja. ' . brand('name') . ' werkt in de browser van je telefoon en kun je als app op je beginscherm zetten. Een offerte maken, uren schrijven of een bon fotograferen kan ter plekke.'],
      ['Kan mijn boekhouder meekijken?', 'Ja, gratis. Je nodigt je boekhouder uit en die ziet je facturen, inkoop en btw-overzicht, zonder iets te kunnen wijzigen.'],
      ['Wat kost het?', 'Het pakket Basis kost € 12,10 per maand inclusief btw en is per maand opzegbaar. Offertes, termijnfacturen en prijsaanvragen zitten daarin. De eerste 14 dagen zijn gratis, zonder betaalgegevens.'],
  ];
  $packages = array_map(fn ($package) => $package[0], \App\Services\TenderService::DEFAULT_PACKAGES);
@endphp

@push('styles')
<style>
  .steps { max-width: 760px; margin: 0 auto; counter-reset: step; list-style: none; padding: 0; }
  .steps li { position: relative; padding: 0 0 26px 58px; color: var(--text-2); line-height: 1.7; }
  .steps li::before { counter-increment: step; content: counter(step); position: absolute; left: 0; top: 0; width: 38px; height: 38px; border-radius: 50%; background: var(--brand-tint); border: 1px solid var(--brand-border); color: var(--brand); font-family: var(--font-display); font-weight: 700; display: flex; align-items: center; justify-content: center; }
  .steps li:not(:last-child)::after { content: ''; position: absolute; left: 19px; top: 44px; bottom: 6px; width: 1px; background: var(--border-strong); }
  .steps strong { display: block; color: var(--text); font-size: 17px; font-family: var(--font-display); margin-bottom: 2px; }
  .pill-row { display: flex; flex-wrap: wrap; gap: 8px; margin: 14px 0 18px; }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <span class="eyebrow">Voor aannemers en bouwbedrijven</span>
    <h1>Offertes en facturen voor de bouw</h1>
    <p class="lead">Van offerte met handtekening tot eindfactuur. Met termijnfacturen en prijsaanvragen bij onderaannemers, zonder een zwaar bouwpakket.</p>
    <div class="hero-ctas" style="margin-top:28px;">
      <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Probeer 14 dagen gratis →</a>
      <a href="{{ route('demo') }}" class="btn btn-secondary btn-lg">Bekijk de demo</a>
    </div>
    <div class="hero-trust">Geen betaalgegevens nodig · € 12,10 per maand incl. btw · per maand opzegbaar</div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <h2>Wat je als bouwbedrijf nodig hebt</h2>
      <p>Geen planbord en geen calculatiepakket. Wel alles tussen de eerste offerte en de laatste betaling.</p>
    </div>
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg></div>
        <div class="feature-title">Offerte met handtekening</div>
        <div class="feature-desc">Je klant leest de offerte online en tekent op zijn telefoon. Naam, tijdstip en handtekening staan in het dossier, zodat er later geen discussie is over wat er is afgesproken.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
        <div class="feature-title">Prijsaanvragen bij onderaannemers</div>
        <div class="feature-desc">Eén aanvraag met tekening naar meerdere bedrijven tegelijk. De prijzen en startweken komen naast elkaar te staan en je gunt met één klik.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
        <div class="feature-title">Termijnfacturen</div>
        <div class="feature-desc">Factureer een offerte in delen: bij opdracht, halverwege en bij oplevering. Elke termijn is één klik en het totaal klopt tot op de cent met de offerte.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div class="feature-title">Uren en kilometers</div>
        <div class="feature-desc">Schrijf uren per klant en zet ze in één keer op de factuur. Ritten naar de bouwplaats houd je bij voor je kilometeradministratie.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></div>
        <div class="feature-title">Bonnen van de bouwmarkt</div>
        <div class="feature-desc">Maak een foto van de bon en het bedrag, de btw en de leverancier staan in je inkoop. Het inlezen van bonnen zit in het pakket Slim.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
        <div class="feature-title">Betaald krijgen</div>
        <div class="feature-desc">Op elke factuur staat een betaallink met iDEAL en een QR-code. Herinneringen en aanmaningen gaan vanzelf, en loopt er nog een oplevering of klacht, dan zet je ze voor die factuur op pauze.</div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <h2>Zo loopt een klus</h2>
      <p>Een uitbouw, een dakrenovatie of een badkamer: de stappen zijn steeds dezelfde.</p>
    </div>
    <ol class="steps">
      <li><strong>Offerte maken en versturen</strong>Je zet de posten in de offerte en stuurt hem per mail. De klant tekent online.</li>
      <li><strong>Prijzen opvragen bij onderaannemers</strong>Per onderdeel, zoals metselwerk of dakbedekking, stuur je een aanvraag met tekening naar de bedrijven in je eigen lijst.</li>
      <li><strong>Vergelijken en gunnen</strong>De reacties staan naast elkaar met prijs en startweek. Het bedrijf dat je kiest krijgt de opdracht per mail, de andere een nette afwijzing.</li>
      <li><strong>In termijnen factureren</strong>Bij opdracht de eerste termijn, tijdens het werk de volgende. Je ziet per offerte wat er al is gefactureerd en wat nog komt.</li>
      <li><strong>Opleveren en de laatste termijn</strong>De laatste factuur is de rest van de offerte. Betaalt de klant te laat, dan volgt de herinnering vanzelf.</li>
    </ol>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="prose">
      <h2>Prijsaanvragen bij onderaannemers</h2>
      <p>Wie een klus aanneemt, belt en mailt daarna dagen om prijzen rond te krijgen. In {{ brand('name') }} doe je dat in één keer. Je kiest het onderdeel, vinkt de bedrijven aan, voegt de tekening toe en verstuurt. Elk bedrijf krijgt zijn eigen mail met een knop om te reageren.</p>
      <p>Er staan {{ count($packages) }} onderdelen klaar, die je zelf aanpast of aanvult:</p>
      <div class="pill-row">
        @foreach ($packages as $name)
          <span class="value-pill">{{ $name }}</span>
        @endforeach
      </div>
      <ul>
        <li><strong>Je eigen lijst met bedrijven</strong> per onderdeel, met contactpersoon en e-mailadres.</li>
        <li><strong>Reageren zonder account.</strong> Het bedrijf vult prijs en startweek in en stuurt zijn offerte mee.</li>
        <li><strong>Afzeggen en herinneren.</strong> Heeft een bedrijf geen tijd, dan leg je dat vast en krijgt het geen herinnering meer. Wie nog niet heeft gereageerd, herinner je met één klik.</li>
        <li><strong>Je verkoopprijs blijft van jou.</strong> In de aanvraag staan alleen de omschrijving, de plaats en de bijlagen die jij kiest.</li>
      </ul>

      <h2>Meer lezen</h2>
      <p>In de kennisbank staat uitleg over <a href="{{ route('kennisbank.artikel', 'deelfactuur-termijnfactuur') }}">deelfacturen en termijnfacturen</a>, de <a href="{{ route('kennisbank.artikel', 'voorschotfactuur-aanbetaling') }}">aanbetaling</a> en de <a href="{{ route('kennisbank.artikel', 'eindfactuur') }}">eindfactuur</a>. Betaalt een klant niet? Reken met de <a href="{{ route('incassokosten-calculator') }}">calculator voor incassokosten en rente</a> uit wat je mag vragen.</p>
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
    <h2>Probeer het bij je volgende klus</h2>
    <p>Maak een offerte, laat hem tekenen en vraag de eerste prijzen op. De eerste 14 dagen zijn gratis.</p>
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
