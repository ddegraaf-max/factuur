@extends('layouts.marketing')

@section('title', 'Factureren met AI — koppel je administratie aan Claude of ChatGPT — ' . brand('name'))
@section('description', brand('name') . ' is het Nederlandse facturatieprogramma met AI: bonnetjes automatisch inboeken, offertes uit een omschrijving en een directe koppeling met Claude en ChatGPT. Vraag je AI-assistent een factuur te maken — hij staat als concept klaar.')

@push('styles')
<style>
  .chat-demo { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 24px; max-width: 620px; margin: 0 auto; box-shadow: var(--shadow-lg); }
  .chat-msg { display: flex; gap: 12px; margin-bottom: 16px; }
  .chat-avatar { width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 700; }
  .chat-avatar.user { background: var(--surface-3); color: var(--text-2); }
  .chat-avatar.ai { background: var(--brand-tint); color: var(--brand); border: 1px solid var(--brand-border); }
  .chat-bubble { background: var(--surface-2); border-radius: 4px 14px 14px 14px; padding: 12px 16px; font-size: 14.5px; color: var(--text-2); line-height: 1.55; }
  .chat-msg.from-user .chat-bubble { background: var(--brand-tint); border-radius: 14px 4px 14px 14px; }
  .chat-card { border: 1px solid var(--border); background: white; border-radius: 10px; padding: 12px 14px; margin-top: 10px; font-size: 13px; }
  .chat-card .cc-title { font-weight: 700; color: var(--text); margin-bottom: 2px; }
  .chat-card .cc-meta { color: var(--text-3); }
  .chat-card .cc-badge { display: inline-block; margin-top: 6px; background: #FEF3C7; color: #B45309; font-size: 11px; font-weight: 700; padding: 2px 10px; border-radius: 100px; }
  .step-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; max-width: 960px; margin: 0 auto; }
  @media (max-width: 800px) { .step-row { grid-template-columns: 1fr; } }
  .step-num { font-family: var(--font-display); font-weight: 800; font-size: 40px; color: var(--brand-tint-2); line-height: 1; margin-bottom: 10px; }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <span class="eyebrow">{{ brand('name') }} Slim</span>
    <h1>Factureren met <span style="color:var(--brand);">AI</span></h1>
    <p class="lead">Bonnetjes die zichzelf inboeken, offertes uit een paar zinnen — en een directe koppeling met Claude en ChatGPT. Vraag je AI-assistent om een factuur en hij staat als concept voor je klaar.</p>
    <div class="hero-ctas" style="margin-top:28px;">
      <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Probeer 14 dagen gratis →</a>
      <a href="{{ route('demo') }}" class="btn btn-secondary btn-lg">Bekijk de demo</a>
    </div>
  </div>
</section>

<section class="section" style="padding-top:30px;">
  <div class="container">
    <div class="chat-demo" aria-hidden="true">
      <div class="chat-msg from-user">
        <div class="chat-avatar user">Jij</div>
        <div class="chat-bubble">Maak een factuur voor De Vries Bouw: 12 uur advieswerk à € 95, en de rittenvergoeding van vorige week erbij.</div>
      </div>
      <div class="chat-msg">
        <div class="chat-avatar ai">AI</div>
        <div class="chat-bubble">
          Ik heb de klant gevonden en de conceptfactuur aangemaakt:
          <div class="chat-card">
            <div class="cc-title">Factuur — De Vries Bouw B.V.</div>
            <div class="cc-meta">12 × Advieswerk à € 95,00 (21% btw) · Reiskosten 86 km</div>
            <div class="cc-meta">Totaal € 1.417,04 incl. btw</div>
            <div class="cc-badge">Concept — jij controleert en verstuurt</div>
          </div>
        </div>
      </div>
    </div>
    <p style="text-align:center;color:var(--text-3);font-size:13.5px;margin-top:18px;">Alles wat AI aanmaakt is een concept: jij houdt altijd de controle en verstuurt zelf.</p>
  </div>
</section>

<section class="section section-alt" id="functies">
  <div class="container">
    <div class="section-header">
      <h2>Wat de AI voor je doet</h2>
      <p>Geen speeltje, maar handen-uit-de-mouwen-hulp bij het saaiste deel van ondernemen: de administratie.</p>
    </div>
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>
        <div class="feature-title">Bonnetjes scannen</div>
        <div class="feature-desc">Maak een foto van een bon of upload een factuur-PDF: leverancier, datum, bedragen en btw worden automatisch herkend en ingeboekt als inkoop.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
        <div class="feature-title">Inkoop per e-mail</div>
        <div class="feature-desc">Stuur (of laat leveranciers sturen) facturen naar je eigen inboek-adres. Ze verschijnen gescand en wel in je Postvak IN — inboeken is één klik.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg></div>
        <div class="feature-title">Offerte uit een omschrijving</div>
        <div class="feature-desc">Typ in gewone taal wat je gaat doen ("website bouwen, 5 pagina's, hosting eerste jaar") en de offerteregels met bedragen staan klaar.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a7 7 0 0 1 7 7c0 2.38-1.19 4.47-3 5.74V17a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1v-2.26C6.19 13.47 5 11.38 5 9a7 7 0 0 1 7-7z"/><line x1="9" y1="21" x2="15" y2="21"/></svg></div>
        <div class="feature-title">Koppeling met Claude &amp; ChatGPT</div>
        <div class="feature-desc">Koppel {{ brand('name') }} als tool aan je AI-assistent (via MCP). Vraag om een factuur of offerte, zoek klanten op of check welke facturen nog openstaan — vanuit je chatvenster.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div>
        <div class="feature-title">Slimme inzichten</div>
        <div class="feature-desc">De AI kijkt met je mee: welke klant betaalt structureel te laat, waar blijft omzet liggen, wat valt op in je cijfers. Kort en concreet, geen dashboardbrij.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <div class="feature-title">Jij houdt de controle</div>
        <div class="feature-desc">AI maakt altijd concepten — versturen doe jij. Elke herkende bon toont wat er is uitgelezen, zodat je het in één oogopslag controleert.</div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <h2>De AI-koppeling in drie stappen</h2>
      <p>Werk je al dagelijks met Claude of ChatGPT? Dan is je administratie voortaan één vraag verwijderd.</p>
    </div>
    <div class="step-row">
      <div>
        <div class="step-num">1</div>
        <h3 style="font-size:19px;margin-bottom:8px;">Zet de koppeling aan</h3>
        <p style="color:var(--text-2);font-size:14.5px;">Ga in {{ brand('name') }} naar Instellingen → Koppelingen en kopieer je persoonlijke koppelingsadres. Voeg het in Claude of ChatGPT toe als connector.</p>
      </div>
      <div>
        <div class="step-num">2</div>
        <h3 style="font-size:19px;margin-bottom:8px;">Stel je vraag</h3>
        <p style="color:var(--text-2);font-size:14.5px;">"Welke facturen staan nog open?" — "Maak een offerte voor Bakker Media voor een huisstijl, € 2.400." Je assistent zoekt de klant op en zet alles klaar.</p>
      </div>
      <div>
        <div class="step-num">3</div>
        <h3 style="font-size:19px;margin-bottom:8px;">Controleer en verstuur</h3>
        <p style="color:var(--text-2);font-size:14.5px;">Alles verschijnt als concept in {{ brand('name') }}. Eén blik, eventueel een aanpassing, en versturen — jij blijft eindverantwoordelijk.</p>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <h2>Wat kost het?</h2>
      <p>De AI-functies zitten in het Slim-abonnement: <strong>€ 21,18 per maand</strong> incl. btw (€ 17,50 excl.), maandelijks opzegbaar. Factureren zonder AI kan met Basis voor € 12,10 incl. btw. Beide probeer je <a href="{{ route('register') }}" style="color:var(--brand);font-weight:600;">14 dagen gratis</a> — zonder creditcard.</p>
    </div>
  </div>
</section>

<section class="section" style="padding-top:0;">
  <div class="container">
    <div class="section-header">
      <h2>Veelgestelde vragen</h2>
    </div>
    <div class="faq-list">
      <details class="faq-item">
        <summary>Met welke AI-assistenten werkt de koppeling? <svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Met alle assistenten die het open MCP-protocol ondersteunen — waaronder Claude (claude.ai en Claude Code) en ChatGPT. Je koppelt via een persoonlijk koppelingsadres dat je op elk moment kunt intrekken.</div>
      </details>
      <details class="faq-item">
        <summary>Kan de AI zomaar facturen versturen namens mij? <svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Nee. Alles wat via de AI binnenkomt — facturen, offertes — wordt als concept aangemaakt. Versturen kan alleen jij, vanuit {{ brand('name') }} zelf, na controle.</div>
      </details>
      <details class="faq-item">
        <summary>Wat gebeurt er met mijn gegevens? <svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Voor het scannen van bonnetjes en het herkennen van offerteteksten gebruiken we Claude van Anthropic; er worden alleen de documenten verwerkt die jij aanbiedt en ze worden niet gebruikt om AI-modellen te trainen. Je administratie staat in de EU en valt onder ons <a href="{{ route('privacy') }}">privacybeleid</a>.</div>
      </details>
      <details class="faq-item">
        <summary>Is er een limiet aan het AI-gebruik? <svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Er geldt een ruime fair-use-limiet die voor vrijwel iedere ondernemer meer dan genoeg is. Loop je er structureel tegenaan, dan denken we graag mee.</div>
      </details>
    </div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Je administratie, op de automatische piloot</h2>
    <p>Probeer {{ brand('name') }} Slim 14 dagen gratis — inclusief alle AI-functies en de koppeling met Claude en ChatGPT.</p>
    <div class="hero-ctas">
      <a href="{{ route('register') }}" class="btn btn-white btn-lg">Start gratis →</a>
      <a href="{{ route('demo') }}" class="btn btn-lg" style="background:rgba(255,255,255,0.15);color:white;border-color:rgba(255,255,255,0.3);">Eerst rondkijken in de demo</a>
    </div>
  </div>
</section>

<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "FAQPage",
  "mainEntity": [
    {
      "@@type": "Question",
      "name": "Met welke AI-assistenten werkt {{ brand('name') }}?",
      "acceptedAnswer": { "@@type": "Answer", "text": "Met alle assistenten die het open MCP-protocol ondersteunen, waaronder Claude en ChatGPT. Je koppelt via een persoonlijk koppelingsadres dat je op elk moment kunt intrekken." }
    },
    {
      "@@type": "Question",
      "name": "Kan de AI zomaar facturen versturen namens mij?",
      "acceptedAnswer": { "@@type": "Answer", "text": "Nee. Alles wat via de AI binnenkomt wordt als concept aangemaakt. Versturen kan alleen de gebruiker zelf, na controle." }
    }
  ]
}
</script>
@endsection
