@extends('layouts.marketing')

@section('title', 'EasyInvoice — Facturatie zonder gedoe vanaf €10/maand')
@section('description', 'EasyInvoice — eenvoudige facturatie voor Nederlandse ondernemers. Facturen, BTW, klanten en incasso vanaf €10 per maand.')

@section('content')

@push('styles')
<style>
  /* ===== Geanimeerd, realistisch hero-dashboard ===== */
  #heroDash .d-body { display: grid; grid-template-columns: 208px 1fr; height: 588px; background: var(--bg); text-align: left; }

  /* Sidebar */
  #heroDash .d-side { background: var(--surface); border-right: 1px solid var(--border); padding: 14px 12px; display: flex; flex-direction: column; gap: 1px; }
  #heroDash .d-brand { display: flex; align-items: center; gap: 9px; padding: 4px 8px 14px; font-family: var(--font-display); font-weight: 700; font-size: 15px; letter-spacing: -0.02em; color: var(--text); }
  #heroDash .d-brand img { width: 24px; height: 24px; border-radius: 6px; display: block; }
  #heroDash .d-navlabel { font-size: 9px; text-transform: uppercase; letter-spacing: 0.09em; color: var(--text-4); font-weight: 700; padding: 12px 10px 5px; }
  #heroDash .d-navitem { display: flex; align-items: center; gap: 9px; padding: 7px 10px; border-radius: 7px; font-size: 12.5px; color: var(--text-2); font-weight: 500; }
  #heroDash .d-navitem svg { width: 15px; height: 15px; opacity: 0.72; }
  #heroDash .d-navitem.active { background: var(--brand-tint); color: var(--brand); font-weight: 600; }
  #heroDash .d-navitem.active svg { opacity: 1; }

  /* Main */
  #heroDash .d-main { padding: 18px 20px; overflow: hidden; }
  #heroDash .d-topbar { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
  #heroDash .d-greet { font-family: var(--font-display); font-weight: 700; font-size: 19px; letter-spacing: -0.02em; color: var(--text); }
  #heroDash .d-sub { font-size: 12px; color: var(--text-3); margin-top: 3px; }
  #heroDash .d-newbtn { display: inline-flex; align-items: center; gap: 6px; background: var(--brand); color: #fff; font-size: 12px; font-weight: 600; padding: 8px 13px; border-radius: 8px; box-shadow: 0 4px 12px rgba(232,35,31,0.28); white-space: nowrap; }
  #heroDash .d-newbtn svg { width: 13px; height: 13px; }

  /* KPI's */
  #heroDash .d-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 11px; margin-bottom: 14px; }
  #heroDash .d-kpi { position: relative; background: var(--surface); border: 1px solid var(--border); border-radius: 11px; padding: 13px 14px; }
  #heroDash .d-kpi.alert { border-color: var(--brand-border); background: linear-gradient(180deg, var(--brand-tint) 0%, var(--surface) 65%); }
  #heroDash .d-kpi-label { display: flex; align-items: center; gap: 5px; font-size: 10.5px; color: var(--text-3); font-weight: 500; margin-bottom: 8px; }
  #heroDash .d-kpi-label svg { width: 12px; height: 12px; }
  #heroDash .d-kpi-value { font-family: var(--font-display); font-weight: 700; font-size: 21px; letter-spacing: -0.02em; color: var(--text); font-variant-numeric: tabular-nums; line-height: 1; }
  #heroDash .d-kpi-value.red { color: var(--brand); }
  #heroDash .d-kpi-meta { font-size: 10.5px; color: var(--text-3); margin-top: 6px; }
  #heroDash .d-kpi-meta.red { color: var(--brand); font-weight: 600; }
  #heroDash .d-kpi-meta .up { color: var(--success); font-weight: 700; }
  #heroDash .d-dot-alert { position: absolute; top: 12px; right: 12px; width: 7px; height: 7px; border-radius: 50%; background: var(--brand); }

  /* Kaarten */
  #heroDash .d-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 14px 16px; margin-bottom: 12px; }
  #heroDash .d-card-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
  #heroDash .d-card-title { font-family: var(--font-display); font-weight: 700; font-size: 13.5px; letter-spacing: -0.01em; color: var(--text); }
  #heroDash .d-card-sub { font-size: 10.5px; color: var(--text-3); margin-top: 2px; }
  #heroDash .d-card-link { font-size: 11px; color: var(--brand); font-weight: 600; }
  #heroDash .d-legend { display: flex; align-items: center; gap: 6px; font-size: 10.5px; color: var(--text-3); }
  #heroDash .d-legend-dot { width: 8px; height: 8px; border-radius: 2px; background: var(--brand); }

  /* Grafiek */
  #heroDash .d-bars { display: flex; align-items: flex-end; gap: 7px; height: 118px; }
  #heroDash .d-bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; height: 100%; justify-content: flex-end; }
  #heroDash .d-bar-col span { font-size: 9px; color: var(--text-4); font-weight: 500; }
  #heroDash .d-bar { width: 100%; max-width: 26px; border-radius: 4px 4px 0 0; background: var(--brand-tint-2); height: var(--h); transition: height 0.9s cubic-bezier(0.22, 1, 0.36, 1); transition-delay: var(--d, 0s); }
  #heroDash .d-bar.tall { background: var(--brand); }
  #heroDash.anim-ready .d-bar { height: 0; }
  #heroDash.is-live .d-bar { height: var(--h); }

  /* Tabel */
  #heroDash .d-table { width: 100%; border-collapse: collapse; }
  #heroDash .d-table td { padding: 8px 6px; font-size: 11.5px; color: var(--text-2); border-top: 1px solid var(--border); }
  #heroDash .d-table tr:first-child td { border-top: none; }
  #heroDash .d-table .mono { font-family: var(--font-mono); font-size: 11px; color: var(--text-3); }
  #heroDash .d-table .right { text-align: right; color: var(--text); font-weight: 600; }
  #heroDash .d-pill { display: inline-block; font-size: 10px; font-weight: 600; padding: 3px 9px; border-radius: 100px; }
  #heroDash .d-pill.green { background: var(--success-bg); color: #047857; }
  #heroDash .d-pill.blue { background: #E0F2FE; color: var(--info); }
  #heroDash .d-pill.red { background: var(--brand-tint-2); color: var(--brand); }

  /* Entree-animatie (alleen actief zodra JS klaarstaat; zonder JS blijft alles zichtbaar) */
  #heroDash .d-anim { transition: opacity 0.6s ease, transform 0.6s cubic-bezier(0.22, 1, 0.36, 1); transition-delay: var(--d, 0s); }
  #heroDash.anim-ready .d-anim { opacity: 0; transform: translateY(14px); }
  #heroDash.is-live .d-anim { opacity: 1; transform: none; }

  /* Pulserende aandacht-stip + subtiele glow op de hoogste balk */
  #heroDash.is-live .d-dot-alert { animation: dPulse 2s ease-in-out infinite; }
  #heroDash.is-live .d-bar.tall { animation: dGlow 2.6s ease-in-out 1.3s infinite; }
  @keyframes dPulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(232,35,31,0.5); } 50% { box-shadow: 0 0 0 6px rgba(232,35,31,0); } }
  @keyframes dGlow { 0%, 100% { filter: none; } 50% { filter: drop-shadow(0 0 6px rgba(232,35,31,0.55)); } }

  @media (max-width: 760px) {
    #heroDash .d-body { grid-template-columns: 1fr; height: auto; }
    #heroDash .d-side { display: none; }
    #heroDash .d-kpis { grid-template-columns: repeat(2, 1fr); }
    #heroDash .d-hide { display: none; }
  }
  @media (prefers-reduced-motion: reduce) {
    #heroDash .d-anim, #heroDash .d-bar { transition: none !important; }
    #heroDash.is-live .d-dot-alert, #heroDash.is-live .d-bar.tall { animation: none !important; }
  }
</style>
@endpush

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

    <!-- APP MOCKUP — geanimeerd, realistisch dashboard -->
    <div class="app-mockup-wrap">
      <div class="app-mockup" id="heroDash">
        <div class="mock-chrome">
          <div class="mock-dot red"></div>
          <div class="mock-dot yellow"></div>
          <div class="mock-dot green"></div>
          <div class="mock-url">app.easyinvoice.nl/dashboard</div>
        </div>
        <div class="d-body">
          <!-- SIDEBAR -->
          <aside class="d-side">
            <div class="d-brand">
              <img src="/images/easyinvoice-favicon-180.png" alt="EasyInvoice">
              <span>EasyInvoice</span>
            </div>
            <div class="d-navlabel">Overzicht</div>
            <div class="d-navitem active">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
              Dashboard
            </div>
            <div class="d-navlabel">Verkoop</div>
            <div class="d-navitem">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              Facturen
            </div>
            <div class="d-navitem">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
              Klanten
            </div>
            <div class="d-navitem">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
              Producten
            </div>
            <div class="d-navitem">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-6 0v4"/><rect x="2" y="9" width="20" height="11" rx="2"/></svg>
              Incasso
            </div>
            <div class="d-navlabel">Rapporten</div>
            <div class="d-navitem">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
              Klantomzet
            </div>
            <div class="d-navlabel">Instellingen</div>
            <div class="d-navitem">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
              Bedrijf
            </div>
          </aside>

          <!-- MAIN -->
          <div class="d-main">
            <div class="d-topbar d-anim" style="--d:.05s">
              <div>
                <div class="d-greet">Goedemorgen, Jan</div>
                <div class="d-sub">Je hebt 2 facturen die aandacht nodig hebben.</div>
              </div>
              <div class="d-newbtn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nieuwe factuur
              </div>
            </div>

            <!-- KPI CARDS -->
            <div class="d-kpis">
              <div class="d-kpi d-anim" style="--d:.12s">
                <div class="d-kpi-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Openstaand</div>
                <div class="d-kpi-value" data-count="7842" data-prefix="€ ">€ 7.842</div>
                <div class="d-kpi-meta">12 facturen</div>
              </div>
              <div class="d-kpi alert d-anim" style="--d:.19s">
                <span class="d-dot-alert"></span>
                <div class="d-kpi-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>Achterstallig</div>
                <div class="d-kpi-value red" data-count="1573" data-prefix="€ ">€ 1.573</div>
                <div class="d-kpi-meta red">2 facturen</div>
              </div>
              <div class="d-kpi d-anim" style="--d:.26s">
                <div class="d-kpi-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>Omzet deze maand</div>
                <div class="d-kpi-value" data-count="4230" data-prefix="€ ">€ 4.230</div>
                <div class="d-kpi-meta"><span class="up" data-count="18" data-prefix="↑ " data-suffix="%">↑ 18%</span> vs vorige maand</div>
              </div>
              <div class="d-kpi d-anim" style="--d:.33s">
                <div class="d-kpi-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>BTW Q2</div>
                <div class="d-kpi-value" data-count="892" data-prefix="€ ">€ 892</div>
                <div class="d-kpi-meta">Deadline 31-07</div>
              </div>
            </div>

            <!-- REVENUE CHART -->
            <div class="d-card d-anim" style="--d:.4s">
              <div class="d-card-head">
                <div>
                  <div class="d-card-title">Omzet per maand</div>
                  <div class="d-card-sub">Exclusief BTW · laatste 12 maanden</div>
                </div>
                <div class="d-legend"><span class="d-legend-dot"></span>2025 — 2026</div>
              </div>
              <div class="d-bars">
                <div class="d-bar-col"><div class="d-bar" style="--h:34%;--d:.45s"></div><span>jul</span></div>
                <div class="d-bar-col"><div class="d-bar" style="--h:44%;--d:.50s"></div><span>aug</span></div>
                <div class="d-bar-col"><div class="d-bar" style="--h:39%;--d:.55s"></div><span>sep</span></div>
                <div class="d-bar-col"><div class="d-bar" style="--h:53%;--d:.60s"></div><span>okt</span></div>
                <div class="d-bar-col"><div class="d-bar" style="--h:48%;--d:.65s"></div><span>nov</span></div>
                <div class="d-bar-col"><div class="d-bar" style="--h:61%;--d:.70s"></div><span>dec</span></div>
                <div class="d-bar-col"><div class="d-bar" style="--h:56%;--d:.75s"></div><span>jan</span></div>
                <div class="d-bar-col"><div class="d-bar" style="--h:69%;--d:.80s"></div><span>feb</span></div>
                <div class="d-bar-col"><div class="d-bar" style="--h:64%;--d:.85s"></div><span>mrt</span></div>
                <div class="d-bar-col"><div class="d-bar" style="--h:77%;--d:.90s"></div><span>apr</span></div>
                <div class="d-bar-col"><div class="d-bar" style="--h:72%;--d:.95s"></div><span>mei</span></div>
                <div class="d-bar-col"><div class="d-bar tall" style="--h:94%;--d:1s"></div><span>jun</span></div>
              </div>
            </div>

            <!-- RECENT INVOICES -->
            <div class="d-card d-anim" style="--d:.5s">
              <div class="d-card-head">
                <div class="d-card-title">Recente facturen</div>
                <span class="d-card-link">Alle →</span>
              </div>
              <table class="d-table">
                <tbody>
                  <tr><td class="mono">2026-0042</td><td>Bakkerij de Korenbloem</td><td class="d-hide">28 jun</td><td><span class="d-pill green">Betaald</span></td><td class="mono right">€ 1.210</td></tr>
                  <tr><td class="mono">2026-0041</td><td>Groenveld Advies</td><td class="d-hide">26 jun</td><td><span class="d-pill blue">Verzonden</span></td><td class="mono right">€ 845</td></tr>
                  <tr><td class="mono">2026-0040</td><td>Studio Lumen</td><td class="d-hide">24 jun</td><td><span class="d-pill red">Achterstallig</span></td><td class="mono right">€ 1.573</td></tr>
                  <tr><td class="mono">2026-0039</td><td>Van Dijk Techniek</td><td class="d-hide">21 jun</td><td><span class="d-pill green">Betaald</span></td><td class="mono right">€ 2.310</td></tr>
                </tbody>
              </table>
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

<script>
(function () {
  var mock = document.getElementById('heroDash');
  if (!mock) return;

  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var nums = mock.querySelectorAll('[data-count]');

  function fmt(n, d) { return n.toLocaleString('nl-NL', { minimumFractionDigits: d, maximumFractionDigits: d }); }
  function setNum(el, val) {
    var d = parseInt(el.getAttribute('data-decimals') || '0', 10);
    var prefix = el.getAttribute('data-prefix') || '';
    var suffix = el.getAttribute('data-suffix') || '';
    el.textContent = prefix + fmt(d ? val : Math.round(val), d) + suffix;
  }

  // Zonder JS-animatie (reduced motion): laat alles direct en met echte cijfers staan.
  if (reduce) return;

  mock.classList.add('anim-ready');
  for (var i = 0; i < nums.length; i++) setNum(nums[i], 0);

  function countUp(el) {
    var target = parseFloat(el.getAttribute('data-count')) || 0;
    var dur = 1300, s = null;
    function step(ts) {
      if (s === null) s = ts;
      var p = Math.min((ts - s) / dur, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      setNum(el, target * eased);
      if (p < 1) requestAnimationFrame(step); else setNum(el, target);
    }
    requestAnimationFrame(step);
  }

  var fired = false;
  function activate() {
    if (fired) return;
    fired = true;
    mock.classList.add('is-live');
    for (var j = 0; j < nums.length; j++) countUp(nums[j]);
  }

  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      for (var k = 0; k < entries.length; k++) {
        if (entries[k].isIntersecting) { activate(); io.disconnect(); break; }
      }
    }, { threshold: 0.3 });
    io.observe(mock);
  } else {
    activate();
  }
})();
</script>
@endsection
