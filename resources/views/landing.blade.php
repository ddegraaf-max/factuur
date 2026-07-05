@extends('layouts.marketing')

@section('title', 'EasyInvoice — Facturatie zonder gedoe vanaf €10/maand')
@section('description', 'EasyInvoice — eenvoudige facturatie voor Nederlandse ondernemers. Facturen, BTW, klanten en incasso vanaf €10 per maand.')

@section('content')
<!-- HERO -->
<section class="hero">
  <div class="container hero-inner">
    <div class="eyebrow">Voor Nederlandse ondernemers</div>
    <h1>Facturatie <span class="accent">zonder gedoe.</span></h1>
    <p class="hero-sub">
      Stuur facturen, beheer je klanten en houd je BTW automatisch bij. Speciaal voor ZZP'ers en MKB — voor <b style="color:var(--text);font-weight:600;">maar €10 per maand</b>.
    </p>
    <div class="hero-ctas">
      <a href="{{ route('register') }}" class="btn btn-primary btn-lg">
        Start 14 dagen gratis
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
      <a href="{{ route('demo') }}" class="btn btn-secondary btn-lg">
        Bekijk de demo
      </a>
    </div>
    <div class="hero-trust">
      Geen creditcard nodig · 14 dagen gratis · Daarna <b>€10/maand</b>
    </div>

    <!-- APP MOCKUP -->
    <div class="app-mockup-wrap">
      <div class="app-mockup">
        <div class="mock-chrome">
          <div class="mock-dot red"></div>
          <div class="mock-dot yellow"></div>
          <div class="mock-dot green"></div>
          <div class="mock-url">app.easyinvoice.nl/dashboard</div>
        </div>
        <div class="mock-body">
          <div class="mock-sidebar">
            <div class="mock-side-brand"><div class="ico"></div>EasyInvoice</div>
            <div class="mock-side-label">Overzicht</div>
            <div class="mock-side-item active"><div class="mock-side-dot"></div>Dashboard</div>
            <div class="mock-side-label">Verkoop</div>
            <div class="mock-side-item"><div class="mock-side-dot"></div>Facturen</div>
            <div class="mock-side-item"><div class="mock-side-dot"></div>Klanten</div>
            <div class="mock-side-item"><div class="mock-side-dot"></div>Producten</div>
            <div class="mock-side-item"><div class="mock-side-dot"></div>Incasso</div>
            <div class="mock-side-label">Rapporten</div>
            <div class="mock-side-item"><div class="mock-side-dot"></div>Klantomzet</div>
            <div class="mock-side-label">Instellingen</div>
            <div class="mock-side-item"><div class="mock-side-dot"></div>Bedrijf</div>
            <div class="mock-side-item"><div class="mock-side-dot"></div>Huisstijl</div>
          </div>
          <div class="mock-main">
            <div class="mock-h1">Welkom terug, Jan</div>
            <div class="mock-kpi-grid">
              <div class="mock-kpi">
                <div class="mock-kpi-label">Openstaand</div>
                <div class="mock-kpi-value">€ 7.842</div>
                <div class="mock-kpi-meta" style="color:var(--warning);">12 facturen</div>
              </div>
              <div class="mock-kpi">
                <div class="mock-kpi-label">Achterstallig</div>
                <div class="mock-kpi-value" style="color:var(--brand);">€ 1.573</div>
                <div class="mock-kpi-meta" style="color:var(--brand);">2 facturen</div>
              </div>
              <div class="mock-kpi">
                <div class="mock-kpi-label">Deze maand</div>
                <div class="mock-kpi-value">€ 4.230</div>
                <div class="mock-kpi-meta">+18% vs vorige maand</div>
              </div>
              <div class="mock-kpi">
                <div class="mock-kpi-label">BTW Q2</div>
                <div class="mock-kpi-value">€ 892</div>
                <div class="mock-kpi-meta" style="color:var(--info);">Over 14 dagen</div>
              </div>
            </div>
            <div class="mock-card">
              <div class="mock-card-title">Omzet laatste 12 maanden</div>
              <div class="mock-chart">
                <div class="mock-bar" style="height:30%"></div>
                <div class="mock-bar" style="height:40%"></div>
                <div class="mock-bar" style="height:55%"></div>
                <div class="mock-bar" style="height:50%"></div>
                <div class="mock-bar" style="height:65%"></div>
                <div class="mock-bar" style="height:70%"></div>
                <div class="mock-bar" style="height:60%"></div>
                <div class="mock-bar" style="height:80%"></div>
                <div class="mock-bar" style="height:75%"></div>
                <div class="mock-bar" style="height:85%"></div>
                <div class="mock-bar" style="height:78%"></div>
                <div class="mock-bar tall" style="height:95%"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- TRUST STRIP -->
<section class="trust-strip">
  <div class="container trust-grid">
    <div>
      <div class="trust-stat-num"><span class="accent">2.500+</span></div>
      <div class="trust-stat-label">Actieve gebruikers</div>
    </div>
    <div>
      <div class="trust-stat-num"><span class="accent">€48M</span></div>
      <div class="trust-stat-label">Aan facturen verstuurd</div>
    </div>
    <div>
      <div class="trust-stat-num"><span class="accent">4,9</span>/5</div>
      <div class="trust-stat-label">Sterren van klanten</div>
    </div>
    <div>
      <div class="trust-stat-num"><span class="accent">99,9%</span></div>
      <div class="trust-stat-label">Uptime SLA</div>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section class="section section-alt" id="functies">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Functies</div>
      <h2>Alles wat je nodig hebt — niet meer.</h2>
      <p>Geen overbodige features waar je nooit aan toekomt. Wel alles om vandaag nog professioneel je administratie te doen.</p>
    </div>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/></svg>
        </div>
        <div class="feature-title">Facturen versturen</div>
        <div class="feature-desc">In drie klikken een factuur opgesteld, automatisch genummerd en met je eigen huisstijl naar de klant verstuurd.</div>
      </div>

      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div class="feature-title">BTW automatisch</div>
        <div class="feature-desc">21%, 9% en 0% per regel. Per kwartaal een aangifte-overzicht klaar — gewoon overnemen op MijnBelastingdienst.</div>
      </div>

      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="feature-title">Klantenbeheer</div>
        <div class="feature-desc">Volledige klantadministratie inclusief KVK-, BTW- en IBAN-gegevens. Onderscheid tussen zakelijk en particulier.</div>
      </div>

      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
        </div>
        <div class="feature-title">Creditnota's</div>
        <div class="feature-desc">Volledig of gedeeltelijk crediteren met eigen nummerreeks — voldoet aan alle Nederlandse boekhoudregels.</div>
      </div>

      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="m14.5 12.5-8 8a2.119 2.119 0 1 1-3-3l8-8"/><path d="m16 16 6-6"/><path d="m8 8 6-6"/><path d="m9 7 8 8"/><path d="m21 11-8-8"/></svg>
        </div>
        <div class="feature-title">Incasso bij Armaere</div>
        <div class="feature-desc">Eén klik om een achterstallige factuur over te dragen aan onze vaste deurwaarder. Incassokosten voor de schuldenaar.</div>
      </div>

      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div class="feature-title">AI-assistent EASY</div>
        <div class="feature-desc">Vraag EASY in normale taal wat er openstaat, hoe je BTW ervoor staat en welke klanten extra aandacht nodig hebben.</div>
      </div>
    </div>
  </div>
</section>

<!-- PRICING -->
<section class="section" id="prijzen">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Eerlijke prijs</div>
      <h2>Eén prijs. Alles inbegrepen.</h2>
      <p>Geen verborgen kosten, geen limieten op facturen of klanten. Opzeggen kan elke maand.</p>
    </div>

    <div class="pricing-wrap">
      <div class="pricing-lead">
        <h2>Waarom zou je meer betalen?</h2>
        <p>Andere boekhoudpakketten vragen €8 tot €25 per maand voor functies die jij waarschijnlijk niet gebruikt. EasyInvoice geeft je alleen wat je echt nodig hebt — voor een fractie van de prijs.</p>
        <ul class="pricing-lead-points">
          <li>Onbeperkt facturen versturen</li>
          <li>Onbeperkt klanten en producten</li>
          <li>Alle functies vanaf dag één</li>
          <li>Persoonlijke ondersteuning</li>
          <li>Maandelijks opzegbaar</li>
        </ul>
      </div>

      <div class="pricing-card">
        <div class="pricing-badge">Meest gekozen</div>
        <div class="pricing-title">EasyInvoice</div>
        <div class="pricing-desc">Alles wat een Nederlandse ondernemer nodig heeft</div>
        <div class="pricing-price-row">
          <div class="pricing-price"><span class="euro">€</span>10</div>
          <div class="pricing-period">/ maand</div>
        </div>
        <div class="pricing-vat">Excl. 21% BTW · €12,10 incl. BTW</div>
        <ul class="pricing-features">
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Onbeperkt facturen en creditnota's</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Onbeperkt klanten en producten</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Eigen huisstijl op factuur en PDF</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>BTW-rapport per kwartaal</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Automatische herinneringen en aanmaningen</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Incasso via Armaere Gerechtsdeurwaarders</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>AI-assistent EASY</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Tweestapsverificatie en beveiliging</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Persoonlijke support per e-mail</li>
        </ul>
        <a href="{{ route('register') }}" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;">Start 14 dagen gratis</a>
        <div class="pricing-fineprint">Geen creditcard nodig · Opzeggen wanneer je wil</div>
      </div>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="section section-alt" id="reviews">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Reviews</div>
      <h2>Wat klanten zeggen.</h2>
      <p>Meer dan 2.500 Nederlandse ondernemers gebruiken EasyInvoice dagelijks.</p>
    </div>

    <div class="testimonials-grid">
      <div class="testimonial">
        <div class="testimonial-quote-mark">"</div>
        <div class="testimonial-text">Ik betaalde €18 per maand bij Moneybird voor functies die ik niet gebruikte. EasyInvoice doet precies wat ik nodig heb, en is zeven keer goedkoper. Heerlijk simpel.</div>
        <div class="testimonial-author">
          <div class="testimonial-avatar">SV</div>
          <div>
            <div class="testimonial-author-name">Sander Vermeer</div>
            <div class="testimonial-author-role">ZZP webdeveloper</div>
          </div>
        </div>
      </div>

      <div class="testimonial">
        <div class="testimonial-quote-mark">"</div>
        <div class="testimonial-text">De incassofunctie heeft ons al meerdere keren gered. Eén klik en Armaere neemt het over — geen e-mailtjes meer naar wanbetalers. Een uitkomst voor een klein bedrijf.</div>
        <div class="testimonial-author">
          <div class="testimonial-avatar">MK</div>
          <div>
            <div class="testimonial-author-name">Marit de Koning</div>
            <div class="testimonial-author-role">Eigenaar Bloemstudio Lina</div>
          </div>
        </div>
      </div>

      <div class="testimonial">
        <div class="testimonial-quote-mark">"</div>
        <div class="testimonial-text">EASY weet altijd hoe het ervoor staat met mijn administratie. "Hoeveel staat er open?" — boem, antwoord. BTW-deadline? EASY waarschuwt. Bijna alsof ik een boekhouder heb.</div>
        <div class="testimonial-author">
          <div class="testimonial-avatar">TP</div>
          <div>
            <div class="testimonial-author-name">Tom Pieters</div>
            <div class="testimonial-author-role">Trainer en coach</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="section" id="faq">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Veelgestelde vragen</div>
      <h2>Vragen die je vast hebt.</h2>
      <p>Staat je vraag er niet bij? <a href="mailto:hallo@easyinvoice.nl" style="color:var(--brand);font-weight:500;">Mail ons direct.</a></p>
    </div>

    <div class="faq-list">
      <details class="faq-item">
        <summary>
          Hoe lang duurt het om EasyInvoice op te zetten?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Vijf minuten. Maak een account, vul je bedrijfsgegevens (KVK, BTW, IBAN) in, en je kunt direct je eerste factuur versturen. Geen ingewikkelde inrichting nodig.
        </div>
      </details>

      <details class="faq-item">
        <summary>
          Kan ik op elk moment opzeggen?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Ja, EasyInvoice is maandelijks opzegbaar. Geen contracten, geen jaarverplichtingen. Opzeggen kan vanuit je account-instellingen. Je facturen blijven gewoon downloadbaar.
        </div>
      </details>

      <details class="faq-item">
        <summary>
          Wat als ik geen BTW-nummer heb (KOR-regeling)?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Geen probleem. EasyInvoice ondersteunt de Kleine Ondernemersregeling (KOR) volledig — je kunt facturen zonder BTW versturen, met automatische vermelding van de KOR-regeling op je factuur.
        </div>
      </details>

      <details class="faq-item">
        <summary>
          Hoe veilig is mijn data?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Je data staat op servers in Amsterdam, dagelijks geback-upt. We zijn AVG-compliant en bieden tweestapsverificatie. Alle verkeer is versleuteld via TLS 1.3. Je houdt de eigendomsrechten op al je data en kunt het op elk moment exporteren.
        </div>
      </details>

      <details class="faq-item">
        <summary>
          Werkt EasyInvoice samen met mijn boekhouder?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Ja. Je kunt je boekhouder toegang geven tot je administratie, en BTW-overzichten, factuurregels en omzetrapporten exporteren naar Excel of CSV. Een directe koppeling met Twinfield, e-Boekhouden en Exact volgt later dit jaar.
        </div>
      </details>

      <details class="faq-item">
        <summary>
          Wat zit er ná de gratis proefperiode in het abonnement?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Alles. Voor €10 per maand (excl. BTW) krijg je het volledige product — onbeperkt facturen, klanten, producten, incasso, AI-assistent, alle functies. We geloven niet in betaalmuren voor basisfuncties.
        </div>
      </details>
    </div>
    <div style="text-align:center;margin-top:32px;">
      <a href="{{ route('faq') }}" class="btn btn-secondary">Alle veelgestelde vragen →</a>
    </div>
  </div>
</section>

<!-- FINAL CTA -->
<section class="cta-final">
  <div class="container cta-inner">
    <h2>Klaar om te beginnen?</h2>
    <p>Begin vandaag nog en stuur binnen vijf minuten je eerste professionele factuur.</p>
    <a href="{{ route('register') }}" class="btn btn-white btn-lg">
      Start 14 dagen gratis
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
    </a>
    <div style="margin-top:16px;font-size:13px;opacity:0.8;">Geen creditcard nodig · Daarna €10/maand</div>
  </div>
</section>
@endsection
