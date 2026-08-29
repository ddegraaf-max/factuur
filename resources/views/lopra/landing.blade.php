@extends('layouts.marketing')

{{-- Homepage van Lopra (APP_BRAND=lopra): het startpakket — administratie, huisstijl, visitekaartje en website in één. --}}

@section('title', 'Lopra — administratie, huisstijl en website voor starters, vanaf € 12,10 per maand')
@section('description', 'Begin professioneel vanaf dag één: factureren, offertes, btw-overzicht, huisstijl met AI, digitaal visitekaartje en je eigen website — alles wat een startende ondernemer nodig heeft, in één abonnement. 14 dagen gratis.')

@push('styles')
<style>
  /* Startersreis onder de hero: vier stappen die in elkaar overlopen. */
  .lp-flow { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; max-width: 1040px; margin: 56px auto 0; text-align: left; }
  .lp-step { position: relative; background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 22px 22px 20px; box-shadow: var(--shadow-sm); }
  .lp-step::after { content: ''; position: absolute; right: -9px; top: 50%; width: 14px; height: 14px; border-top: 2px solid var(--border-strong); border-right: 2px solid var(--border-strong); transform: translateY(-50%) rotate(45deg); background: var(--bg); }
  .lp-step:last-child::after { display: none; }
  .lp-step-nr { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 50%; background: var(--brand); color: #fff; font-family: var(--font-display); font-weight: 600; font-size: 14px; margin-bottom: 14px; }
  .lp-step:last-child .lp-step-nr { background: var(--accent); }
  .lp-step h3 { font-size: 16px; margin-bottom: 6px; }
  .lp-step p { font-size: 13.5px; color: var(--text-3); line-height: 1.55; margin: 0; }
  .lp-step-time { display: inline-block; margin-top: 12px; font-size: 11.5px; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: var(--brand); background: var(--brand-tint); border: 1px solid var(--brand-border); border-radius: 100px; padding: 3px 10px; }
  @media (max-width: 980px) { .lp-flow { grid-template-columns: 1fr 1fr; } .lp-step:nth-child(2)::after { display: none; } }
  @media (max-width: 560px) { .lp-flow { grid-template-columns: 1fr; } .lp-step::after { display: none; } }

  /* Drie troeven */
  .lp-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
  .lp-card { background: var(--surface); border: 1px solid var(--border); border-radius: 18px; padding: 30px 28px; }
  .lp-card-icon { width: 46px; height: 46px; border-radius: 12px; background: var(--brand-tint); color: var(--brand); display: flex; align-items: center; justify-content: center; margin-bottom: 18px; }
  .lp-card-icon svg { width: 22px; height: 22px; }
  .lp-card h3 { font-size: 19px; margin-bottom: 10px; }
  .lp-card p { color: var(--text-2); line-height: 1.6; margin: 0; font-size: 15px; }
  .lp-card p + p { margin-top: 10px; }
  @media (max-width: 900px) { .lp-cards { grid-template-columns: 1fr; } }

  /* "Los kopen" vergelijking */
  .lp-compare { display: grid; grid-template-columns: 1.1fr 1fr; gap: 40px; align-items: center; }
  .lp-compare-list { list-style: none; padding: 0; margin: 18px 0 0; display: grid; gap: 10px; }
  .lp-compare-list li { display: flex; align-items: flex-start; gap: 12px; padding: 12px 16px; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; font-size: 14.5px; color: var(--text-2); }
  .lp-compare-list li s { color: var(--text-4); }
  .lp-compare-list li b { color: var(--text); }
  .lp-compare-sum { margin-top: 18px; padding: 18px 20px; border-radius: 14px; background: var(--brand-darker); color: #fff; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
  .lp-compare-sum .amount { font-family: var(--font-display); font-size: 30px; font-weight: 600; letter-spacing: -0.01em; }
  .lp-compare-sum .amount small { font-family: var(--font-body); font-size: 13px; font-weight: 500; opacity: 0.75; margin-left: 4px; }
  .lp-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: var(--accent); background: var(--accent-tint, #FBF1E7); border-radius: 100px; padding: 5px 12px; }
  @media (max-width: 900px) { .lp-compare { grid-template-columns: 1fr; } }

  /* Voorbeeld-visitekaartje in de huisstijl */
  .lp-card-demo { background: var(--brand-darker); border-radius: 22px; padding: 32px; color: #fff; box-shadow: var(--shadow-lg); position: relative; overflow: hidden; }
  .lp-card-demo::before { content: ''; position: absolute; width: 320px; height: 320px; border-radius: 50%; right: -120px; top: -140px; background: radial-gradient(circle, rgba(224,165,92,0.28) 0%, rgba(224,165,92,0) 65%); }
  .lp-card-demo .who { display: flex; align-items: center; gap: 14px; margin-bottom: 22px; position: relative; }
  .lp-card-demo .avatar { width: 52px; height: 52px; border-radius: 14px; background: var(--accent); color: #fff; font-family: var(--font-display); font-weight: 600; font-size: 22px; display: flex; align-items: center; justify-content: center; }
  .lp-card-demo .name { font-family: var(--font-display); font-size: 20px; font-weight: 600; }
  .lp-card-demo .role { font-size: 13px; opacity: 0.7; }
  .lp-card-demo .lines { display: grid; gap: 8px; position: relative; }
  .lp-card-demo .line { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 10px; background: rgba(255,255,255,0.08); font-size: 13.5px; }
  .lp-card-demo .line svg { width: 16px; height: 16px; opacity: 0.85; flex-shrink: 0; }
  .lp-card-demo .line.site { background: var(--accent); color: #fff; font-weight: 600; }
  .lp-card-demo .qr { position: absolute; right: 28px; bottom: 28px; width: 66px; height: 66px; border-radius: 10px; background: #fff; display: grid; grid-template-columns: repeat(5, 1fr); gap: 3px; padding: 8px; }
  .lp-card-demo .qr i { background: var(--brand-darker); border-radius: 2px; }
  .lp-card-demo .qr i.o { background: transparent; }
</style>
@endpush

@section('content')

<!-- HERO -->
<section class="hero">
  <div class="container hero-inner">
    <div class="eyebrow">Voor wie net begint</div>
    <h1>Je hele administratie <span class="accent">op één plek.</span></h1>
    <p class="hero-sub">
      Factureren, offertes, btw, huisstijl, visitekaartje en je eigen website. Alles wat je als startende ondernemer nodig hebt — in één abonnement, zonder boekhoudkennis.
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

    <div class="lp-flow" aria-label="Zo begin je met Lopra">
      <div class="lp-step">
        <div class="lp-step-nr">1</div>
        <h3>Account aanmaken</h3>
        <p>Naam, e-mail, KvK-nummer. Meer heb je niet nodig om te beginnen.</p>
        <span class="lp-step-time">1 minuut</span>
      </div>
      <div class="lp-step">
        <div class="lp-step-nr">2</div>
        <h3>Huisstijl kiezen</h3>
        <p>Vertel wat je doet en voor wie. Je krijgt drie voorstellen met kleuren, lettertype, slogan en logo.</p>
        <span class="lp-step-time">5 minuten</span>
      </div>
      <div class="lp-step">
        <div class="lp-step-nr">3</div>
        <h3>Visitekaartje &amp; website online</h3>
        <p>Vier vragen beantwoorden, tekst laten schrijven, publiceren. Met contactformulier en QR-code.</p>
        <span class="lp-step-time">10 minuten</span>
      </div>
      <div class="lp-step">
        <div class="lp-step-nr">4</div>
        <h3>Eerste factuur versturen</h3>
        <p>In je eigen huisstijl, met iDEAL-link. De btw houdt Lopra vanaf nu voor je bij.</p>
        <span class="lp-step-time">Vandaag nog</span>
      </div>
    </div>
  </div>
</section>

<!-- WAAROM LOPRA -->
<section class="section" id="waarom">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Waarom Lopra</div>
      <h2>Begin professioneel vanaf dag één.</h2>
      <p>De meeste starters kopen drie losse tools: een factuurprogramma, een logo-ontwerper en een websitebouwer. Lopra is alle drie — en ze werken samen.</p>
    </div>

    <div class="lp-cards">
      <div class="lp-card">
        <div class="lp-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.586 7.586"/><circle cx="11" cy="11" r="2"/></svg></div>
        <h3>Een huisstijl in een kwartier</h3>
        <p>Nog geen logo, geen kleuren, geen idee waar te beginnen? Vertel wat je doet en voor wie. De AI van Lopra stelt drie huisstijlen voor: kleuren, lettertype, factuursjabloon, slogan en een logo.</p>
        <p>Eén klik, en je facturen, je visitekaartje en je website dragen dezelfde stijl. Heb je al een huisstijl? Upload je logo en Lopra neemt de kleuren over.</p>
      </div>
      <div class="lp-card">
        <div class="lp-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></div>
        <h3>Zichtbaar zonder webbouwer</h3>
        <p>Een digitaal visitekaartje met QR-code — bellen, mailen, WhatsApp, opslaan in contacten — en een complete website met diensten, over jou en een contactformulier. Beide in je huisstijl, beide met één klik online.</p>
        <p>Berichten via je website komen als leads binnen in je administratie, dus je maakt er direct een offerte van.</p>
      </div>
      <div class="lp-card">
        <div class="lp-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="13" y2="17"/></svg></div>
        <h3>Factureren zoals het hoort</h3>
        <p>Offertes die je klant digitaal ondertekent, facturen met iDEAL-link en QR-code, herinneringen die vanzelf gaan. Je btw-overzicht staat elk kwartaal klaar in de indeling van de Belastingdienst — overnemen en klaar.</p>
        <p>Nodig je boekhouder gratis uit om mee te kijken, of exporteer alles wanneer je wilt.</p>
      </div>
    </div>
  </div>
</section>

<!-- LOS KOPEN VS LOPRA -->
<section class="section section-alt">
  <div class="container">
    <div class="lp-compare">
      <div>
        <div class="eyebrow" style="margin-bottom:16px;">Reken het na</div>
        <h2>Wat je anders los zou kopen.</h2>
        <p style="color:var(--text-2);margin-top:12px;">Een startende ondernemer betaalt al snel voor een factuurprogramma, een logo, een websitebouwer én een visitekaartjes-app — vier abonnementen, vier inlogs, vier keer je gegevens invullen.</p>
        <ul class="lp-compare-list">
          <li><span>🧾</span><span><b>Factuurprogramma</b> — <s>€ 10 tot € 25 per maand</s></span></li>
          <li><span>🎨</span><span><b>Logo en huisstijl</b> — <s>€ 150 tot € 800 eenmalig</s></span></li>
          <li><span>🌐</span><span><b>Websitebouwer</b> — <s>€ 12 tot € 30 per maand</s></span></li>
          <li><span>📇</span><span><b>Digitaal visitekaartje</b> — <s>€ 5 tot € 10 per maand</s></span></li>
        </ul>
        <div class="lp-compare-sum">
          <div><div style="font-size:13px;opacity:0.75;">Bij Lopra zit het allemaal in één abonnement</div><div class="amount">€ 10<small>per maand excl. btw</small></div></div>
          <span class="lp-pill">Basis · alles inbegrepen</span>
        </div>
      </div>

      <div class="lp-card-demo" aria-hidden="true">
        <div class="who">
          <div class="avatar">S</div>
          <div>
            <div class="name">Sanne de Wit</div>
            <div class="role">Interieurstylist · De Wit Interieur</div>
          </div>
        </div>
        <div class="lines">
          <div class="line"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.37 1.9.72 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.35 1.85.59 2.81.72A2 2 0 0 1 22 16.92z"/></svg>06 - 12 34 56 78</div>
          <div class="line"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>sanne@dewitinterieur.nl</div>
          <div class="line site"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>Bekijk mijn website →</div>
        </div>
        <div class="qr">
          <i></i><i></i><i></i><i class="o"></i><i></i>
          <i></i><i class="o"></i><i></i><i></i><i class="o"></i>
          <i></i><i></i><i class="o"></i><i></i><i></i>
          <i class="o"></i><i></i><i></i><i class="o"></i><i></i>
          <i></i><i class="o"></i><i></i><i></i><i></i>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FUNCTIES -->
<section class="section" id="functies">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Functies</div>
      <h2>Alles wat je nodig hebt om te beginnen. En om te groeien.</h2>
      <p>Geen boekhoudpakket met honderd knoppen. Wel alles om vandaag professioneel te starten — en wat je later nodig hebt, zit er al in.</p>
    </div>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/></svg></div>
        <div class="feature-title">Facturen &amp; offertes</div>
        <div class="feature-desc">In drie klikken opgesteld, automatisch genummerd, in je eigen huisstijl verstuurd. Je klant ondertekent de offerte digitaal en betaalt met iDEAL of QR-code.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
        <div class="feature-title">Btw-overzicht, aangifte-klaar</div>
        <div class="feature-desc">Per kwartaal alle rubrieken in de indeling van Mijn Belastingdienst Zakelijk, met een herinnering vóór de deadline. Ook als je onder de KOR valt.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/></svg></div>
        <div class="feature-title">Huisstijl met AI</div>
        <div class="feature-desc">Drie voorstellen met kleuren, lettertype, sjabloon, slogan en logo — uit vier vragen. Of upload je eigen huisstijlgids en alles staat goed.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/><line x1="6" y1="15" x2="10" y2="15"/></svg></div>
        <div class="feature-title">Digitaal visitekaartje</div>
        <div class="feature-desc">Eén link en een QR-code met al je contactgegevens: bellen, mailen, WhatsApp, opslaan in contacten. Voor op je offerte, je e-mailhandtekening en je telefoon.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></div>
        <div class="feature-title">Je eigen website</div>
        <div class="feature-desc">Een complete one-pager met diensten, over jou, waarom jij en een contactformulier. De tekst laat je schrijven door AI en pas je zelf aan. Online of offline met één schakelaar.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div class="feature-title">Uren &amp; kilometers</div>
        <div class="feature-desc">Schrijf uren met de timer, verkoop urenbundels die vanzelf aftellen en belast ritten door — alles met één klik op een factuur.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></div>
        <div class="feature-title">Bonnetjes scannen</div>
        <div class="feature-desc">Fotografeer of forward een bon: leverancier, datum en bedragen worden herkend en de btw telt mee als voorbelasting. Jij bevestigt met één klik.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
        <div class="feature-title">Boekhouder kijkt gratis mee</div>
        <div class="feature-desc">Nodig je boekhouder uit met een "alleen inzien"-rol, of download het auditfile (XAF) dat elk boekhoudpakket direct inleest.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div>
        <div class="feature-title">Groeit met je mee</div>
        <div class="feature-desc">Herinneringen en incasso, e-facturatie via Peppol, bankkoppeling, meerdere handelsnamen en administraties, een klantenportaal — zonder over te stappen.</div>
      </div>
    </div>
  </div>
</section>

<!-- PRIJZEN -->
<section class="section section-alt" id="prijzen">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Eerlijke prijs</div>
      <h2>Eén abonnement. Alles inbegrepen.</h2>
      <p>Geen verborgen kosten, geen limieten op facturen of klanten. Opzeggen kan elke maand.</p>
    </div>

    <div class="pricing-wrap">
      <div class="pricing-lead">
        <h2>Alles wat een starter nodig heeft, zit in Basis.</h2>
        <p>Factureren, offertes, btw, visitekaartje én je website — voor de prijs die anderen alleen voor een factuurprogramma vragen. Wil je dat de AI je huisstijl ontwerpt, je websitetekst schrijft en je bonnetjes inboekt? Kies Slim.</p>
        <ul class="pricing-lead-points">
          <li>Digitaal visitekaartje en website inbegrepen</li>
          <li>Onbeperkt facturen, offertes, klanten en producten</li>
          <li>Meerdere handelsnamen én administraties</li>
          <li>14 dagen gratis proberen — inclusief alle AI-functies</li>
          <li>Persoonlijke ondersteuning · maandelijks opzegbaar</li>
        </ul>
      </div>

      <div class="pricing-cards">
        <div class="pricing-card basic">
          <div class="pricing-title">Basis</div>
          <div class="pricing-desc">Je administratie, je visitekaartje en je website</div>
          <div class="pricing-price-row">
            <div class="pricing-price"><span class="euro">€</span>10</div>
            <div class="pricing-period">/ maand</div>
          </div>
          <div class="pricing-vat">Excl. 21% btw · € 12,10 incl. btw</div>
          <ul class="pricing-features">
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Onbeperkt facturen, offertes en creditnota's</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Digitaal visitekaartje met QR-code</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Je eigen website met contactformulier</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Huisstijl: eigen logo, kleuren en sjablonen</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Digitaal ondertekenen, iDEAL-link en QR op je factuur</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Btw-, jaar- en cashflow-overzicht</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Uren, urenbundels en kilometers</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Herinneringen, aanmaningen en incasso</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Gratis extra gebruikers en je boekhouder</li>
          </ul>
          <a href="{{ route('register') }}" class="btn btn-secondary btn-lg" style="width:100%;justify-content:center;">Start 14 dagen gratis</a>
          <div class="pricing-fineprint">Geen creditcard nodig · Opzeggen wanneer je wil</div>
        </div>

        <div class="pricing-card">
          <div class="pricing-badge">Meest gekozen door starters</div>
          <div class="pricing-title">Slim</div>
          <div class="pricing-desc">Alles uit Basis, plus de AI die je huisstijl ontwerpt en je administratie invult</div>
          <div class="pricing-price-row">
            <div class="pricing-price"><span class="euro">€</span>17,50</div>
            <div class="pricing-period">/ maand</div>
          </div>
          <div class="pricing-vat">Excl. 21% btw · € 21,18 incl. btw</div>
          <ul class="pricing-features">
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><b>Alles uit Basis</b></li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Huisstijl ontwerpen met AI: drie voorstellen met logo, kleuren en slogan</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Websitetekst geschreven door AI uit vier vragen</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Scan &amp; herken: bonnen en inkoopfacturen automatisch ingevuld</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Offerte uit tekst: plak je concept, het formulier vult zich in</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Claude-koppeling: maak offertes en facturen vanuit je gesprek</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Eerst proberen? De proefperiode bevat alle AI-functies</li>
          </ul>
          <a href="{{ route('register') }}" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;">Start 14 dagen gratis</a>
          <div class="pricing-fineprint">Geen creditcard nodig · Opzeggen wanneer je wil</div>
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
      <h2>Vragen die je als starter vast hebt.</h2>
      <p>Staat je vraag er niet bij? <a href="mailto:{{ brand('email') }}" style="color:var(--brand);font-weight:500;">Mail ons direct.</a></p>
    </div>

    <div class="faq-list">
      <details class="faq-item">
        <summary>
          Ik sta net ingeschreven bij de KvK. Wat heb ik minimaal nodig?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Je KvK-nummer, je btw-id (of de KOR-vrijstelling) en een zakelijke rekening. Meer niet. Lopra zet je bedrijfsgegevens automatisch op je facturen, nummert ze doorlopend en houdt de btw bij. Een huisstijl en een website maak je er in een kwartier bij.
        </div>
      </details>

      <details class="faq-item">
        <summary>
          Heb ik boekhoudkennis nodig?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Nee. Lopra is gemaakt om zonder voorkennis te gebruiken: btw wordt automatisch berekend, je kwartaaloverzicht staat klaar in de indeling van de Belastingdienst, en je boekhouder kan gratis meekijken. Val je onder de Kleine Ondernemersregeling (KOR), dan regelt Lopra de juiste vermelding op je factuur.
        </div>
      </details>

      <details class="faq-item">
        <summary>
          Ik heb al een logo. Kan ik dat gebruiken?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Ja. Upload je logo en Lopra neemt de kleuren over voor je facturen, je visitekaartje en je website. Heb je een huisstijlgids? Upload die en alles staat in één keer goed. De AI-wizard is er voor wie nog níets heeft.
        </div>
      </details>

      <details class="faq-item">
        <summary>
          Hoe werkt de website? Heb ik een eigen domeinnaam nodig?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Je website staat direct online op een eigen adres bij Lopra ({{ brand('domain') }}/s/jouw-bedrijf) — geen hosting, geen webbouwer. Een eigen domeinnaam is niet nodig; heb je er een, dan laat je die simpelweg doorverwijzen. De tekst schrijf je zelf of laat je door AI schrijven uit vier vragen, en je past hem altijd aan. Berichten via het contactformulier komen als leads in je administratie.
        </div>
      </details>

      <details class="faq-item">
        <summary>
          Wat kost het na de proefperiode?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Je kiest uit <b>Basis</b> (€ 12,10 per maand incl. btw) met je administratie, visitekaartje en website, of <b>Slim</b> (€ 21,18 per maand incl. btw) met daarbovenop de AI-functies: huisstijl ontwerpen, websitetekst schrijven, bonnetjes herkennen en de Claude-koppeling. Geen contract, maandelijks opzegbaar, en je facturen blijven altijd downloadbaar.
        </div>
      </details>

      <details class="faq-item">
        <summary>
          Wat als mijn bedrijf groeit — of als ik wil overstappen?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Lopra groeit mee: meerdere handelsnamen en administraties, een klantenportaal, incasso, e-facturatie via Peppol en een bankkoppeling zitten er al in. En je data blijft van jou: exporteer je hele administratie als CSV of als auditfile (XAF) dat elk boekhoudpakket inleest.
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
    <h2>Begin vandaag. Professioneel vanaf de eerste factuur.</h2>
    <p>Account, huisstijl, visitekaartje, website en je eerste factuur — allemaal vóór het avondeten.</p>
    <a href="{{ route('register') }}" class="btn btn-white btn-lg">
      Start 14 dagen gratis
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
    </a>
    <div style="margin-top:16px;font-size:13px;opacity:0.8;">Geen creditcard nodig · Daarna vanaf € 12,10/maand incl. btw</div>
  </div>
</section>
@endsection
