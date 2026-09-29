@extends('layouts.marketing')

{{--
  Homepage van EasyBookkeeper (APP_BRAND=easybookkeeper).

  Waar EasyInvoice over de factuur gaat die de deur uit gaat, gaat dit over de
  administratie die eronder ligt. Het verschil dat we verkopen is het grootboek:
  de meeste pakketten voor zzp'ers leiden een jaarrekening af uit documenten,
  wij boeken het echt — met een proefbalans die sluit en een auditfile die de
  accountant zonder morren inleest.

  De opmaak komt uit layouts.marketing; hier staan alleen de klassen die daar
  zijn gedefinieerd. Eigen klassen verzinnen levert schermen op die er net
  anders uitzien dan de rest.
--}}

@section('title', 'EasyBookkeeper — online boekhouden met een echt grootboek')
@section('description', 'Boekhouden voor zzp en mkb: facturen, bankkoppeling, btw-aangifte en een volledig grootboek met proefbalans, balans en auditfile. Uw accountant leest het zo in. 14 dagen gratis proberen.')

@section('content')

<section class="hero">
  <div class="container hero-inner">
    <div class="eyebrow">Voor zzp en mkb</div>
    <h1>Boekhouden dat <span class="accent">klopt.</span></h1>
    <p class="hero-sub">
      Facturen, bank en btw — en eronder een echt grootboek. Geen overzicht dat
      achteraf uit documenten wordt afgeleid, maar een administratie die sluit,
      met een auditfile die uw accountant zonder vragen inleest.
    </p>
    <div class="hero-ctas">
      <a href="{{ route('register') }}" class="btn btn-primary btn-lg">
        Start 14 dagen gratis
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
      <a href="{{ route('demo') }}" class="btn btn-secondary btn-lg">Bekijk de demo</a>
    </div>
    <div class="hero-trust">
      Geen creditcard nodig · 14 dagen gratis · Daarna vanaf <b>€ 12,10/maand incl. btw</b>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <h2>Wat een echt grootboek u oplevert</h2>
      <p>De meeste pakketten laten zien wat er is gefactureerd en betaald. Dat is
      een overzicht, geen boekhouding. Het verschil merkt u bij de accountant, bij
      de Belastingdienst, en op het moment dat er iets niet klopt.</p>
    </div>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6v12"/><path d="M21 6v12"/><path d="M3 12h18"/></svg>
        </div>
        <div class="feature-title">Debet is credit — altijd</div>
        <div class="feature-desc">
          Elke boeking moet in balans zijn, en dat wordt door de database zelf
          afgedwongen. Niet door een controle die iemand kan overslaan: een post
          die niet sluit komt er domweg niet in.
        </div>
      </div>

      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="feature-title">Auditfile die wordt ingelezen</div>
        <div class="feature-desc">
          Een XAF 3.2-bestand dat aan het officiële schema van de Belastingdienst
          voldoet — getoetst, niet aangenomen. Uw accountant hoeft niets uit te
          zoeken en rekent dus ook geen uren voor het uitzoeken.
        </div>
      </div>

      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        </div>
        <div class="feature-title">Bank die zichzelf boekt</div>
        <div class="feature-desc">
          Mutaties komen binnen via de bankkoppeling en worden voorgesteld op de
          juiste rekening. U bevestigt; het pakket boekt. Wat het niet zeker weet,
          blijft liggen in plaats van dat het iets verzint.
        </div>
      </div>

      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="feature-title">Btw-aangifte uit de cijfers</div>
        <div class="feature-desc">
          Niet nageteld uit facturen, maar afgeleid uit wat er werkelijk geboekt
          is. Inclusief de correcties die u in het kwartaal nog hebt gemaakt.
        </div>
      </div>

      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
        </div>
        <div class="feature-title">Proefbalans, balans, grootboekkaart</div>
        <div class="feature-desc">
          De stukken waar een boekhouder om vraagt, op elk moment van het jaar.
          En een grootboekkaart die precies uitkomt op wat de proefbalans zegt —
          dat is minder vanzelfsprekend dan het klinkt.
        </div>
      </div>

      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <div class="feature-title">Een vastgesteld jaar zit dicht</div>
        <div class="feature-desc">
          Is het jaar eenmaal vastgesteld, dan kan er niets meer in veranderen.
          Een correctie hoort in het lopende jaar — anders betekent een
          jaarrekening niets.
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <h2>Overstappen zonder een jaar over te typen</h2>
      <p>U begint met een beginbalans uit uw huidige pakket. Openstaande facturen,
      banksaldi en de stand van het grootboek komen mee; vanaf dag één klopt het
      dus met wat uw accountant al heeft gezien.</p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <h2>Wat het kost</h2>
      <p>Eén prijs, alles erin. Geen modules die u er later bij moet kopen omdat
      uw boekhouder erom vraagt.</p>
    </div>
    <div class="pricing-wrap">
      <div class="pricing-cards">
        <div class="pricing-card">
          <div class="pricing-title">EasyBookkeeper</div>
          <div class="pricing-desc">Voor zzp en klein mkb</div>
          <div class="pricing-price-row">
            <span class="pricing-price">€ 12,10</span>
            <span class="pricing-period">per maand</span>
          </div>
          <div class="pricing-vat">inclusief btw</div>
          <ul class="pricing-features">
            <li>Facturen en offertes</li>
            <li>Bankkoppeling met boekingsvoorstellen</li>
            <li>Volledig grootboek en proefbalans</li>
            <li>Btw-aangifte per kwartaal</li>
            <li>Auditfile (XAF 3.2) voor uw accountant</li>
            <li>Overstapwizard met beginbalans</li>
          </ul>
          <a href="{{ route('register') }}" class="btn btn-primary btn-block">Start 14 dagen gratis</a>
          <div class="pricing-fineprint">Geen creditcard nodig. Maandelijks opzegbaar.</div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Probeer het veertien dagen</h2>
    <p>Zet uw beginbalans erin en kijk of de cijfers kloppen. Doen ze dat niet,
    dan hoort u dat van ons voordat u het zelf ontdekt.</p>
    <a href="{{ route('register') }}" class="btn btn-white btn-lg">Account aanmaken</a>
  </div>
</section>

@endsection
