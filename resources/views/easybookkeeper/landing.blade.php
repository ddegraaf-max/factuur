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

@push('styles')
<style>
  /*
   * De twee gratis hulpmiddelen in de hero.
   *
   * ── Waarom die knoppen bewegen ──────────────────────────────────────────
   *
   * Iemand die hier voor het eerst komt wil niets aanmaken. Deze twee dingen
   * kan hij meteen doen, zonder account, en ze leveren precies het document op
   * waarvoor hij aan het zoeken was. Dat is de reden dat ze opvallen: niet om
   * te versieren, maar omdat ze het enige op deze pagina zijn dat nú iets voor
   * hem doet.
   *
   * Het verloop gaat van diepgroen via het koperen accent terug naar groen.
   * Koper is de tweede merkkleur en staat nergens anders op een knop (zie
   * public/brand/easybookkeeper/theme.css): hier beweegt het dóór het groen
   * heen in plaats van eroverheen te liggen, en dat is precies waar een
   * accentkleur voor is.
   *
   * Wie bewegende beelden heeft uitgezet krijgt een stilstaande knop; dat staat
   * onderaan in de media query, en het is geen nette toevoeging maar een eis:
   * bewegende vlakken geven mensen met vestibulaire klachten echt klachten.
   */
  .free-tools { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; max-width: 940px; margin: 34px auto 0; text-align: left; }
  .free-tool { display: flex; flex-direction: column; gap: 16px; padding: 20px 22px 22px; background: var(--surface); border: 1px solid var(--brand-border); border-radius: 16px; box-shadow: var(--shadow-md); color: inherit; text-decoration: none; transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease; }
  .free-tool:hover { transform: translateY(-3px); box-shadow: var(--shadow-brand); border-color: var(--brand); }
  .ft-top { display: flex; gap: 14px; align-items: flex-start; }
  .ft-icon { flex: none; width: 44px; height: 44px; border-radius: 12px; background: var(--brand-tint); color: var(--brand); display: inline-flex; align-items: center; justify-content: center; }
  .ft-icon svg { width: 22px; height: 22px; }
  .ft-body { display: flex; flex-direction: column; gap: 3px; }
  .ft-kicker { font-size: 11.5px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--brand); }
  .ft-title { font-family: var(--font-display); font-weight: 700; font-size: 20px; line-height: 1.25; letter-spacing: -0.02em; color: var(--text); }
  .ft-text { font-size: 14px; line-height: 1.55; color: var(--text-2); margin-top: 3px; }

  .btn-live { position: relative; isolation: isolate; align-self: flex-start; margin-top: auto; color: #fff; border: 0; text-shadow: 0 1px 1px rgba(0, 0, 0, 0.22); background-image: linear-gradient(115deg, var(--brand-darker) 0%, var(--brand) 26%, var(--accent) 50%, var(--brand) 74%, var(--brand-darker) 100%); background-size: 260% 100%; background-position: 0% 50%; animation: live-shift 4.5s ease-in-out infinite; box-shadow: 0 4px 18px rgba(20, 107, 79, 0.30); }
  /* De tweede knop loopt uit het donker naar het groen, en een halve slag uit
     de maat van de eerste: twee knoppen die precies gelijk bewegen zien eruit
     als één knop die per ongeluk twee keer staat. */
  .btn-live.dark { background-image: linear-gradient(115deg, #23211E 0%, #3A3733 26%, var(--brand) 50%, #3A3733 74%, #23211E 100%); animation-delay: -2.2s; box-shadow: 0 4px 18px rgba(35, 33, 30, 0.28); }
  .free-tool:hover .btn-live { animation-duration: 1.6s; box-shadow: 0 8px 26px rgba(20, 107, 79, 0.42); }
  .btn-live::after { content: ''; position: absolute; inset: -5px; border-radius: inherit; border: 2px solid var(--brand); opacity: 0; pointer-events: none; animation: live-ring 2.6s ease-out 1.2s 3; }
  .btn-live.dark::after { animation-delay: 2.4s; }
  .live-badge { display: inline-block; padding: 2px 8px; border-radius: 999px; background: #fff; color: var(--brand-darker); font-size: 10.5px; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; text-shadow: none; }

  @keyframes live-shift { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
  @keyframes live-ring { 0% { opacity: 0.9; transform: scale(0.94); } 100% { opacity: 0; transform: scale(1.14); } }

  @media (max-width: 760px) {
    .free-tools { grid-template-columns: 1fr; margin-top: 28px; }
  }
  @media (prefers-reduced-motion: reduce) {
    .btn-live { animation: none; background-position: 50% 50%; }
    .btn-live::after { display: none; }
    .free-tool, .free-tool:hover { transition: none; transform: none; }
  }

  /* De regel onder de tarieven: wat er niet in zit hoort er net zo duidelijk te
     staan als wat er wel in zit. */
  .prijs-noot { max-width: 760px; margin: 26px auto 0; font-size: 13px; line-height: 1.7; color: var(--text-3); text-align: center; }
</style>
@endpush

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

    {{-- Twee dingen die u nu al kunt doen, zonder account. --}}
    <div class="free-tools">
      <a class="free-tool" href="{{ route('gratis-factuur') }}">
        <span class="ft-top">
          <span class="ft-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="13" y2="17"/></svg></span>
          <span class="ft-body">
            <span class="ft-kicker">Gratis · zonder account</span>
            <span class="ft-title">Gratis factuur maken</span>
            <span class="ft-text">Vul uw gegevens in en download uw factuur als PDF, met alle vermeldingen erop die de Belastingdienst verplicht stelt.</span>
          </span>
        </span>
        <span class="btn btn-live">Maak uw factuur <span class="live-badge">Gratis</span></span>
      </a>
      <a class="free-tool" href="{{ route('aanmaning') }}">
        <span class="ft-top">
          <span class="ft-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><polyline points="22 6 12 13 2 6"/></svg></span>
          <span class="ft-body">
            <span class="ft-kicker">Gratis · zonder account</span>
            <span class="ft-title">Gratis online aanmaning</span>
            <span class="ft-text">Betaalt uw klant niet? Verstuur een aanmaning waarop de wettelijke rente elke dag oploopt en waarop uw klant met één klik reageert.</span>
          </span>
        </span>
        <span class="btn btn-live dark">Verstuur een aanmaning <span class="live-badge">Nieuw</span></span>
      </a>
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
        <div class="feature-title">Bankmutaties die zichzelf afletteren</div>
        <div class="feature-desc">
          U leest uw bankafschrift in — of koppelt uw bank rechtstreeks — en elke
          mutatie wordt voorgesteld bij de factuur waar hij bij hoort. U
          bevestigt; het pakket boekt de ontvangst in het grootboek. Wat het niet
          zeker weet blijft openstaan in plaats van dat het iets verzint.
        </div>
      </div>

      <div class="feature-card">
        <div class="feature-icon">
          {{-- Een euro, geen dollar: de boog met de twee dwarsstrepen. Hier
               stond het dollarteken uit de icoonset, en dat leest een
               Nederlandse bezoeker meteen als het verkeerde land. --}}
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M16.5 6.5a6.5 6.5 0 1 0 0 11"/><path d="M4.5 10.5H13"/><path d="M4.5 13.5H13"/></svg>
        </div>
        <div class="feature-title">Btw-aangifte per kwartaal</div>
        <div class="feature-desc">
          De bedragen staan klaar op het moment dat de aangifte open gaat, met
          de facturen en inkopen die eronder liggen. U controleert en dient in.
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
      <p>Uw klanten, producten en openstaande facturen importeert u uit uw huidige
      pakket. De stand van uw boekhouding — banksaldo, wat klanten nog moeten
      betalen, wat u nog aan leveranciers moet, de btw-stand — voert u één keer in
      als beginbalans. Vanaf dag één klopt het dus met wat uw accountant al heeft
      gezien, zonder dat u een jaar hoeft over te typen.</p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <h2>Wat het kost</h2>
      <p>De boekhouding zelf zit in Basis — helemaal, zonder modules die u er
      later bij moet kopen omdat uw accountant erom vraagt. Wilt u dat iemand
      meekijkt, dan kiest u Zeker.</p>
    </div>

    <div class="pricing-wrap">
      {{--
        Links de reden, rechts de prijs. Wie naar een prijs kijkt is bezig met
        vergelijken, en dan is de vraag niet "wat kost het" maar "wat kost het
        bij de ander extra".
      --}}
      <div class="pricing-lead">
        <h2>Een boekhouding is geen abonnementenpuzzel.</h2>
        <p>Bij de meeste pakketten betaalt u apart voor het grootboek, voor de
        auditfile, of voor het aantal boekingen per maand. Hier hoort dat bij de
        boekhouding, want zonder die onderdelen ís het geen boekhouding.</p>
        <ul class="pricing-lead-points">
          <li>Volledig grootboek, proefbalans en balans in élk abonnement</li>
          <li>Auditfile (XAF 3.2) voor uw accountant, altijd inbegrepen</li>
          <li>Onbeperkt facturen, klanten en boekingen</li>
          <li>14 dagen gratis proberen, zonder creditcard</li>
          <li>Maandelijks opzegbaar, uw gegevens blijven van u</li>
        </ul>
      </div>

      <div class="pricing-cards">
        <div class="pricing-card basic">
          <div class="pricing-title">Basis</div>
          <div class="pricing-desc">De hele boekhouding, u doet hem zelf</div>
          <div class="pricing-price-row">
            <div class="pricing-price"><span class="euro">€</span>10</div>
            <div class="pricing-period">/ maand</div>
          </div>
          <div class="pricing-vat">Excl. 21% btw · € 12,10 incl. btw</div>
          <ul class="pricing-features">
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Facturen, offertes en creditnota's</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Volledig grootboek: journaal, proefbalans, balans en grootboekkaart</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Het officiële rekeningschema (RGS 3.3)</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Btw-aangifte per kwartaal, rechtstreeks uit het grootboek</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Auditfile (XAF 3.2) per boekjaar</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Bankafschriften inlezen (CAMT.053 en MT940) met afletteren</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Beginbalans invoeren als u overstapt</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Uw accountant erbij, gratis en alleen-lezen</li>
          </ul>
          <a href="{{ route('register') }}" class="btn btn-secondary btn-lg" style="width:100%;justify-content:center;">Start 14 dagen gratis</a>
          <div class="pricing-fineprint">Geen creditcard nodig · Opzeggen wanneer u wil</div>
        </div>

        {{--
          Het tweede tarief is geen software maar werk van een mens: nakijken,
          overzetten, doornemen. Daarom staat er precies bij wat het is en hoe
          vaak — een dienst die vaag is omschreven levert alleen teleurstelling
          op, aan beide kanten.
        --}}
        <div class="pricing-card">
          <div class="pricing-badge">Met een mens erbij</div>
          <div class="pricing-title">Zeker</div>
          <div class="pricing-desc">Alles uit Basis, en wij kijken met u mee</div>
          <div class="pricing-price-row">
            <div class="pricing-price"><span class="euro">€</span>29</div>
            <div class="pricing-period">/ maand</div>
          </div>
          <div class="pricing-vat">Excl. 21% btw · € 35,09 incl. btw</div>
          <ul class="pricing-features">
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><b>Alles uit Basis</b></li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><b>Overstapservice:</b> wij zetten uw beginbalans over uit uw oude pakket</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><b>Btw-aangifte nagekeken</b> vóór u hem indient — elk kwartaal</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><b>Jaarafsluiting samen doorgenomen:</b> balans en resultaat, voordat u het jaar vaststelt</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Boekingen die u niet weet indelen, delen wij in</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Antwoord op uw vraag binnen één werkdag</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Per maand opzegbaar, ook als u alleen het eerste jaar hulp wil</li>
          </ul>
          {{--
            Zeker gaat via contact en niet via de afrekenknop. Dat is geen
            verkooptruc: het pakket kent maar twee abonnementen (basis en slim,
            zie BillingController), dus "kies het in uw account" zou een knop
            beloven die er niet is. Bovendien spreken we bij werk van een mens
            liever eerst af wat u nodig hebt.
          --}}
          <a href="{{ route('contact') }}" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;">Zeker aanvragen</a>
          <div class="pricing-fineprint">U begint met Basis · Zeker spreken we met u af</div>
        </div>
      </div>
    </div>

    {{--
      Wat er níet in zit, staat er net zo duidelijk. Een directe bankkoppeling
      loopt via Ponto en kost daar geld; dat kunnen we niet weglaten en dan
      achteraf in rekening brengen. Het inlezen van afschriften is gratis en doet
      voor de boekhouding hetzelfde werk.
    --}}
    <p class="prijs-noot">
      Wilt u dat uw bank rechtstreeks gekoppeld is, dan komt daar <b>€ 5 excl. btw
      per rekening per maand</b> bij; die koppeling loopt via Ponto en kost ons
      dat ook. Het inlezen van uw bankafschrift (CAMT.053 of MT940) zit in elk
      abonnement en levert dezelfde boekingen op.
      <br>
      <b>Zeker</b> is werk van een mens, geen extra software. Wij zijn geen
      accountantskantoor: wij kijken uw administratie na en leggen uit wat er
      staat. Voor het samenstellen van een jaarrekening of het indienen van uw
      aangifte inkomstenbelasting blijft uw accountant aan zet — en die leest uw
      auditfile zonder vragen in.
    </p>
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
