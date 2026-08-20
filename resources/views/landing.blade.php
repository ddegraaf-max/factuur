@extends('layouts.marketing')

@section('title', 'EasyInvoice — Van offerte tot geld op je rekening, vanaf € 12,10/maand incl. btw')
@section('description', 'EasyInvoice — facturatie voor Nederlandse ondernemers. Offertes digitaal laten ondertekenen, facturen met iDEAL en QR, meerdere handelsnamen, strippenkaarten, urenregistratie en BTW-overzicht — vanaf € 12,10 per maand (incl. 21% btw).')

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
  #heroDash.js-ready .d-screen .d-bar { height: 0; }
  #heroDash.js-ready .d-screen.active .d-bar { height: var(--h); }

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
  #heroDash .d-anim, #heroDash .d-fade { transition: opacity 0.55s ease, transform 0.55s cubic-bezier(0.22, 1, 0.36, 1); transition-delay: var(--d, 0s); }
  #heroDash.js-ready .d-screen .d-anim { opacity: 0; transform: translateY(12px); }
  #heroDash.js-ready .d-screen .d-fade { opacity: 0; }
  #heroDash.js-ready .d-screen.active .d-anim,
  #heroDash.js-ready .d-screen.active .d-fade { opacity: 1; transform: none; }

  /* Pulserende aandacht-stip + subtiele glow op de hoogste balk */
  #heroDash .d-screen.active .d-dot-alert { animation: dPulse 2s ease-in-out infinite; }
  #heroDash .d-screen.active .d-bar.tall { animation: dGlow 2.6s ease-in-out 1.3s infinite; }
  @keyframes dPulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(232,35,31,0.5); } 50% { box-shadow: 0 0 0 6px rgba(232,35,31,0); } }
  @keyframes dGlow { 0%, 100% { filter: none; } 50% { filter: drop-shadow(0 0 6px rgba(232,35,31,0.55)); } }

  /* ===== Tour: schermsysteem + componenten ===== */
  #heroDash .d-progress { height: 3px; background: var(--surface-3); border-radius: 3px; margin: 0 0 14px; overflow: hidden; }
  #heroDash .d-progress i { display: block; height: 100%; width: 0; background: var(--brand); border-radius: 3px; }
  #heroDash .d-screen { display: none; }
  #heroDash .d-screen:first-of-type { display: block; }
  #heroDash.js-ready .d-screen { display: none; }
  #heroDash.js-ready .d-screen.active { display: block; animation: dIn 0.45s ease both; }
  @keyframes dIn { from { opacity: 0.3; } to { opacity: 1; } }

  #heroDash.js-ready .d-navitem { cursor: pointer; transition: background 0.15s, color 0.15s; }
  #heroDash.js-ready .d-navitem:not(.active):hover { background: var(--surface-2); color: var(--text); }

  #heroDash .d-shead { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
  #heroDash .d-h1 { font-family: var(--font-display); font-weight: 700; font-size: 19px; letter-spacing: -0.02em; color: var(--text); }
  #heroDash .d-newbtn.ghost { background: var(--surface); color: var(--text-2); border: 1px solid var(--border-strong); box-shadow: none; }

  #heroDash .d-tabs { display: flex; gap: 6px; margin-bottom: 12px; flex-wrap: wrap; }
  #heroDash .d-tab { font-size: 11px; font-weight: 600; color: var(--text-3); padding: 5px 11px; border-radius: 100px; background: var(--surface); border: 1px solid var(--border); }
  #heroDash .d-tab.active { background: var(--brand-tint); color: var(--brand); border-color: var(--brand-border); }

  #heroDash .d-table thead th { text-align: left; font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-4); font-weight: 700; padding: 0 6px 9px; border-bottom: 1px solid var(--border); }
  #heroDash .d-table thead th.right { text-align: right; }

  #heroDash .d-cust { display: flex; align-items: center; gap: 9px; }
  #heroDash .d-avatar { width: 24px; height: 24px; border-radius: 50%; background: var(--brand-tint-2); color: var(--brand); font-size: 9.5px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; font-family: var(--font-display); flex-shrink: 0; }

  #heroDash .d-pill.gray { background: var(--surface-3); color: var(--text-3); }
  #heroDash .d-pill.amber { background: #FEF3C7; color: #B45309; }

  #heroDash .d-hbars { display: flex; flex-direction: column; gap: 13px; }
  #heroDash .d-hrow { display: grid; grid-template-columns: 140px 1fr 64px; align-items: center; gap: 12px; }
  #heroDash .d-hlabel { font-size: 11.5px; color: var(--text-2); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  #heroDash .d-htrack { height: 15px; background: var(--surface-2); border-radius: 4px; overflow: hidden; }
  #heroDash .d-hfill { height: 100%; width: var(--w); background: var(--brand-tint-2); border-radius: 4px; transition: width 0.85s cubic-bezier(0.22, 1, 0.36, 1); transition-delay: var(--d, 0s); }
  #heroDash .d-hrow:first-child .d-hfill { background: var(--brand); }
  #heroDash .d-hval { font-family: var(--font-mono); font-size: 11px; color: var(--text); font-weight: 600; text-align: right; }
  #heroDash.js-ready .d-screen .d-hfill { width: 0; }
  #heroDash.js-ready .d-screen.active .d-hfill { width: var(--w); }

  #heroDash .d-logo-tile { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border: 1px solid var(--border); border-radius: 11px; background: var(--surface); margin-bottom: 13px; }
  #heroDash .d-logo-tile img { width: 40px; height: 40px; border-radius: 9px; display: block; }
  #heroDash .d-logo-tile .t { font-size: 12.5px; font-weight: 700; color: var(--text); }
  #heroDash .d-logo-tile .s { font-size: 10.5px; color: var(--text-3); margin-top: 2px; }
  #heroDash .d-form { display: grid; grid-template-columns: 1fr 1fr; gap: 11px 14px; }
  #heroDash .d-field label { display: block; font-size: 9px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-4); font-weight: 700; margin-bottom: 4px; }
  #heroDash .d-field .val { font-size: 12px; color: var(--text); background: var(--surface); border: 1px solid var(--border-strong); border-radius: 8px; padding: 8px 11px; }

  @media (max-width: 760px) {
    #heroDash .d-body { grid-template-columns: 1fr; height: auto; }
    #heroDash .d-side { display: none; }
    #heroDash .d-kpis { grid-template-columns: repeat(2, 1fr); }
    #heroDash .d-hide { display: none; }
    #heroDash .d-form { grid-template-columns: 1fr; }
    #heroDash .d-hrow { grid-template-columns: 92px 1fr 58px; gap: 8px; }
    #heroDash .d-hlabel { font-size: 10.5px; }
  }
  @media (prefers-reduced-motion: reduce) {
    #heroDash .d-anim, #heroDash .d-fade, #heroDash .d-bar, #heroDash .d-hfill { transition: none !important; }
    #heroDash .d-screen.active .d-dot-alert, #heroDash .d-screen.active .d-bar.tall { animation: none !important; }
    #heroDash.js-ready .d-screen.active { animation: none !important; }
  }
</style>
@endpush

<!-- HERO -->
<section class="hero">
  <div class="container hero-inner">
    <div class="eyebrow">Voor Nederlandse ondernemers</div>
    <h1>Facturatie <span class="accent">zonder gedoe.</span></h1>
    <p class="hero-sub">
      Van offerte tot geld op je rekening: je klant ondertekent digitaal, betaalt met iDEAL of QR-code, en je BTW wordt automatisch bijgehouden. Alles zit erin — vanaf <b style="color:var(--text);font-weight:600;">€ 12,10 per maand (incl. 21% btw)</b>.
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
      Geen creditcard nodig · 14 dagen gratis · Daarna vanaf <b>€ 12,10/maand incl. btw</b>
    </div>

    <!-- APP MOCKUP — geanimeerd, realistisch dashboard -->
    <div class="app-mockup-wrap">
      <div class="app-mockup" id="heroDash">
        <div class="mock-chrome">
          <div class="mock-dot red"></div>
          <div class="mock-dot yellow"></div>
          <div class="mock-dot green"></div>
          <div class="mock-url" id="dashUrl">app.easyinvoice.nl/dashboard</div>
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
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15l2 2 4-4"/></svg>
              Offertes
            </div>
            <div class="d-navitem">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              Uren
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
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
              Handelsnamen
            </div>
          </aside>

          <!-- MAIN -->
          <div class="d-main">
            <div class="d-progress"><i id="dashProgress"></i></div>

            <!-- SCHERM: Dashboard -->
            <section class="d-screen active" data-screen="dashboard">
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
                <div class="d-kpi-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>BTW Q3</div>
                <div class="d-kpi-value" data-count="892" data-prefix="€ ">€ 892</div>
                <div class="d-kpi-meta">Deadline 31-10</div>
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
            </section>

            <!-- SCHERM: Facturen -->
            <section class="d-screen" data-screen="facturen">
              <div class="d-shead d-anim" style="--d:.04s">
                <div><div class="d-h1">Facturen</div><div class="d-sub">142 facturen · € 7.842 openstaand</div></div>
                <div class="d-newbtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Nieuwe factuur</div>
              </div>
              <div class="d-tabs d-anim" style="--d:.09s">
                <span class="d-tab active">Alle</span><span class="d-tab">Concept</span><span class="d-tab">Verzonden</span><span class="d-tab">Betaald</span><span class="d-tab">Achterstallig</span>
              </div>
              <div class="d-card d-anim" style="--d:.14s">
                <table class="d-table">
                  <thead><tr><th>Nummer</th><th>Klant</th><th class="d-hide">Datum</th><th class="d-hide">Vervalt</th><th>Status</th><th class="right">Bedrag</th></tr></thead>
                  <tbody>
                    <tr class="d-fade" style="--d:.18s"><td class="mono">2026-0042</td><td>Bakkerij de Korenbloem</td><td class="d-hide">28-06</td><td class="d-hide">12-07</td><td><span class="d-pill green">Betaald</span></td><td class="mono right">€ 1.210</td></tr>
                    <tr class="d-fade" style="--d:.24s"><td class="mono">2026-0041</td><td>Groenveld Advies</td><td class="d-hide">26-06</td><td class="d-hide">10-07</td><td><span class="d-pill blue">Verzonden</span></td><td class="mono right">€ 845</td></tr>
                    <tr class="d-fade" style="--d:.30s"><td class="mono">2026-0040</td><td>Studio Lumen</td><td class="d-hide">24-06</td><td class="d-hide">08-07</td><td><span class="d-pill red">Achterstallig</span></td><td class="mono right">€ 1.573</td></tr>
                    <tr class="d-fade" style="--d:.36s"><td class="mono">2026-0039</td><td>Van Dijk Techniek</td><td class="d-hide">21-06</td><td class="d-hide">05-07</td><td><span class="d-pill green">Betaald</span></td><td class="mono right">€ 2.310</td></tr>
                    <tr class="d-fade" style="--d:.42s"><td class="mono">2026-0038</td><td>De Groot Interim</td><td class="d-hide">18-06</td><td class="d-hide">02-07</td><td><span class="d-pill blue">Verzonden</span></td><td class="mono right">€ 690</td></tr>
                    <tr class="d-fade" style="--d:.48s"><td class="mono">2026-0037</td><td>Horizon Media</td><td class="d-hide">15-06</td><td class="d-hide">29-06</td><td><span class="d-pill gray">Concept</span></td><td class="mono right">€ 540</td></tr>
                  </tbody>
                </table>
              </div>
            </section>

            <!-- SCHERM: Offertes -->
            <section class="d-screen" data-screen="offertes">
              <div class="d-shead d-anim" style="--d:.04s">
                <div><div class="d-h1">Offertes</div><div class="d-sub">18 offertes · € 24.150 uitstaand</div></div>
                <div class="d-newbtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Nieuwe offerte</div>
              </div>
              <div class="d-card d-anim" style="--d:.10s;border-color:var(--success-border);background:var(--success-bg);">
                <div class="d-card-sub" style="margin:0;">✍️ <b>Van Dijk Techniek</b> ondertekende offerte OFF-2026-0018 zojuist digitaal in het portaal — rechtsgeldig, met bewijsdossier. Termijn 1 van 2 (30%) staat klaar als conceptfactuur.</div>
              </div>
              <div class="d-card d-anim" style="--d:.16s">
                <table class="d-table">
                  <thead><tr><th>Nummer</th><th>Klant</th><th class="d-hide">Geldig tot</th><th>Status</th><th class="right">Bedrag</th></tr></thead>
                  <tbody>
                    <tr class="d-fade" style="--d:.20s"><td class="mono">OFF-0018</td><td>Van Dijk Techniek</td><td class="d-hide">14-09</td><td><span class="d-pill green">Ondertekend</span></td><td class="mono right">€ 8.470</td></tr>
                    <tr class="d-fade" style="--d:.26s"><td class="mono">OFF-0017</td><td>Bakkerij de Korenbloem</td><td class="d-hide">—</td><td><span class="d-pill amber">Termijn 1/2</span></td><td class="mono right">€ 6.050</td></tr>
                    <tr class="d-fade" style="--d:.32s"><td class="mono">OFF-0016</td><td>Studio Lumen</td><td class="d-hide">02-09</td><td><span class="d-pill blue">Verstuurd</span></td><td class="mono right">€ 3.920</td></tr>
                    <tr class="d-fade" style="--d:.38s"><td class="mono">OFF-0015</td><td>Groenveld Advies</td><td class="d-hide">28-08</td><td><span class="d-pill blue">Verstuurd</span></td><td class="mono right">€ 2.140</td></tr>
                    <tr class="d-fade" style="--d:.44s"><td class="mono">OFF-0014</td><td>Horizon Media</td><td class="d-hide">—</td><td><span class="d-pill gray">Concept</span></td><td class="mono right">€ 1.780</td></tr>
                  </tbody>
                </table>
              </div>
            </section>

            <!-- SCHERM: Uren & strippenkaarten -->
            <section class="d-screen" data-screen="uren">
              <div class="d-shead d-anim" style="--d:.04s">
                <div><div class="d-h1">Uren</div><div class="d-sub">Deze week 14:30 geschreven · timer, strippenkaarten en kilometers</div></div>
                <div class="d-newbtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>Start timer</div>
              </div>
              <div class="d-card d-anim" style="--d:.10s">
                <div class="d-card-head">
                  <div>
                    <div class="d-card-title">Strippenkaart — Bakkerij de Korenbloem</div>
                    <div class="d-card-sub">Bundel van 10 uur · vooraf betaald · uren tellen automatisch af</div>
                  </div>
                  <span class="d-pill green">Nog 3:30 over</span>
                </div>
                <div class="d-htrack"><div class="d-hfill" style="--w:65%;--d:.20s"></div></div>
              </div>
              <div class="d-card d-anim" style="--d:.16s">
                <div class="d-card-head">
                  <div class="d-card-title">Klaar om te factureren</div>
                  <span class="d-card-link">Maak factuur →</span>
                </div>
                <table class="d-table">
                  <tbody>
                    <tr class="d-fade" style="--d:.22s"><td>Van Dijk Techniek</td><td class="d-hide">6 urenregels</td><td class="mono right">6:15 uur</td><td class="mono right">€ 593,75</td></tr>
                    <tr class="d-fade" style="--d:.28s"><td>Studio Lumen</td><td class="d-hide">4 urenregels</td><td class="mono right">4:30 uur</td><td class="mono right">€ 427,50</td></tr>
                    <tr class="d-fade" style="--d:.34s"><td>Groenveld Advies</td><td class="d-hide">3 urenregels + 2 ritten</td><td class="mono right">3:45 uur</td><td class="mono right">€ 389,05</td></tr>
                  </tbody>
                </table>
              </div>
            </section>

            <!-- SCHERM: Incasso -->
            <section class="d-screen" data-screen="incasso">
              <div class="d-shead d-anim" style="--d:.04s">
                <div><div class="d-h1">Incasso</div><div class="d-sub">3 lopende dossiers · via Armaere Gerechtsdeurwaarders</div></div>
              </div>
              <div class="d-card d-anim" style="--d:.12s">
                <table class="d-table">
                  <thead><tr><th>Klant</th><th class="d-hide">Factuur</th><th class="right">Bedrag</th><th class="right d-hide">Te laat</th><th>Stap</th></tr></thead>
                  <tbody>
                    <tr class="d-fade" style="--d:.18s"><td>Studio Lumen</td><td class="mono d-hide">2026-0040</td><td class="mono right">€ 1.573</td><td class="right d-hide">14 dagen</td><td><span class="d-pill amber">Aanmaning</span></td></tr>
                    <tr class="d-fade" style="--d:.26s"><td>Horizon Media</td><td class="mono d-hide">2026-0021</td><td class="mono right">€ 2.940</td><td class="right d-hide">32 dagen</td><td><span class="d-pill red">Deurwaarder</span></td></tr>
                    <tr class="d-fade" style="--d:.34s"><td>De Groot Interim</td><td class="mono d-hide">2026-0033</td><td class="mono right">€ 690</td><td class="right d-hide">6 dagen</td><td><span class="d-pill blue">Herinnering</span></td></tr>
                  </tbody>
                </table>
              </div>
            </section>

            <!-- SCHERM: Klantomzet -->
            <section class="d-screen" data-screen="klantomzet">
              <div class="d-shead d-anim" style="--d:.04s">
                <div><div class="d-h1">Klantomzet</div><div class="d-sub">Top klanten in 2026 · € 48.320 totaal · effectief uurtarief € 92/u</div></div>
              </div>
              <div class="d-card d-anim" style="--d:.12s">
                <div class="d-hbars">
                  <div class="d-hrow d-fade" style="--d:.18s"><span class="d-hlabel">Van Dijk Techniek</span><div class="d-htrack"><div class="d-hfill" style="--w:100%;--d:.20s"></div></div><span class="d-hval">€ 12.480</span></div>
                  <div class="d-hrow d-fade" style="--d:.24s"><span class="d-hlabel">Meijer &amp; Zn</span><div class="d-htrack"><div class="d-hfill" style="--w:74%;--d:.26s"></div></div><span class="d-hval">€ 9.240</span></div>
                  <div class="d-hrow d-fade" style="--d:.30s"><span class="d-hlabel">Bakkerij de Korenbloem</span><div class="d-htrack"><div class="d-hfill" style="--w:61%;--d:.32s"></div></div><span class="d-hval">€ 7.610</span></div>
                  <div class="d-hrow d-fade" style="--d:.36s"><span class="d-hlabel">Studio Lumen</span><div class="d-htrack"><div class="d-hfill" style="--w:50%;--d:.38s"></div></div><span class="d-hval">€ 6.180</span></div>
                  <div class="d-hrow d-fade" style="--d:.42s"><span class="d-hlabel">Groenveld Advies</span><div class="d-htrack"><div class="d-hfill" style="--w:39%;--d:.44s"></div></div><span class="d-hval">€ 4.920</span></div>
                  <div class="d-hrow d-fade" style="--d:.48s"><span class="d-hlabel">Horizon Media</span><div class="d-htrack"><div class="d-hfill" style="--w:28%;--d:.50s"></div></div><span class="d-hval">€ 3.540</span></div>
                </div>
              </div>
            </section>

            <!-- SCHERM: Handelsnamen -->
            <section class="d-screen" data-screen="handelsnamen">
              <div class="d-shead d-anim" style="--d:.04s">
                <div><div class="d-h1">Handelsnamen</div><div class="d-sub">Twee huisstijlen · één administratie, één abonnement</div></div>
                <div class="d-newbtn ghost"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Handelsnaam toevoegen</div>
              </div>
              <div class="d-logo-tile d-anim" style="--d:.10s">
                <div style="width:40px;height:40px;border-radius:9px;background:var(--brand);color:#fff;display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-weight:700;font-size:15px;">JC</div>
                <div><div class="t">Jansen Consultancy</div><div class="s">Standaard huisstijl · sjabloon Modern · rood</div></div>
              </div>
              <div class="d-logo-tile d-anim" style="--d:.16s">
                <div style="width:40px;height:40px;border-radius:9px;background:#1D4ED8;color:#fff;display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-weight:700;font-size:15px;">JF</div>
                <div><div class="t">Jansen Fotografie</div><div class="s">Eigen logo &amp; kleur · sjabloon Minimal · blauw</div></div>
              </div>
              <div class="d-card d-anim" style="--d:.22s">
                <div class="d-card-sub" style="margin:0;">Kies bij elke factuur of offerte onder welke handelsnaam hij de deur uitgaat — je klant ziet dát logo, díe kleur en díe afzendernaam. Juridisch verandert er niets: zelfde KvK, zelfde BTW-aangifte. En een écht tweede bedrijf? Dat voeg je toe als extra administratie onder dezelfde inlog — zonder tweede abonnement.</div>
              </div>
            </section>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- WAAROM EASYINVOICE: de drie troeven -->
<section class="section" id="waarom">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Waarom EasyInvoice</div>
      <h2>Drie dingen die je bij anderen niet vindt.</h2>
      <p>Facturen sturen kan overal. Dit is waar EasyInvoice écht het verschil maakt.</p>
    </div>

    <div class="card-grid">
      <div class="info-card">
        <div class="ic-emoji">🏷️</div>
        <h3>Meerdere handelsnamen én administraties — één inlog, één prijs</h3>
        <p>Bij andere pakketten betekent een tweede handelsnaam of een tweede bedrijf: een tweede abonnement.
        Bij EasyInvoice beheer je meerdere administraties (elk met eigen KvK en nummering) onder één inlog,
        en verstuur je binnen één administratie facturen en offertes onder verschillende handelsnamen —
        elk met eigen logo, kleur en sjabloon. Zonder meerprijs.</p>
      </div>
      <div class="info-card">
        <div class="ic-emoji">🎟️</div>
        <h3>Strippenkaarten: urenbundels die zichzelf aftellen</h3>
        <p>Verkoop een bundel van bijvoorbeeld 10 uur, factureer 'm met één klik, en elke geschreven werksessie
        telt automatisch af van het tegoed — jij en je klant zien altijd wat er nog openstaat.
        Coaches, therapeuten en consultants houden dit nu bij in Excel náást hun factuurpakket;
        hier zit het er gewoon in. Geen enkel ander Nederlands pakket doet dit.</p>
      </div>
      <div class="info-card">
        <div class="ic-emoji">✍️</div>
        <h3>Van ondertekende offerte tot geld op je rekening</h3>
        <p>Je klant ondertekent de offerte digitaal in het portaal — rechtsgeldig, met bewijsdossier.
        Daarna factureer je in één keer of in termijnen (30% vooraf, 70% bij oplevering), betaalt je klant
        met de iDEAL-link of de QR-code op de factuur, en gaan herinneringen en zonodig incasso vanzelf.
        Eén doorlopende keten, zonder losse tekentool of betaaldienst ertussen.</p>
      </div>
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
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </div>
        <div class="feature-title">Klantenportaal met inzagelog</div>
        <div class="feature-desc">Je klant bekijkt en downloadt facturen online via een beveiligde link. Jij ziet precies óf en wanneer je factuur is bekeken.</div>
      </div>

      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div class="feature-title">BTW-aangifte klaar</div>
        <div class="feature-desc">Per kwartaal rubriek 1a, 1b en 1e plus voorbelasting (5b) en het saldo — gewoon overnemen op MijnBelastingdienst.</div>
      </div>

      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
        </div>
        <div class="feature-title">Inkoop die zichzelf inboekt</div>
        <div class="feature-desc">Forward bonnen naar je eigen inboek-adres: de AI herkent leverancier, datum en bedragen en jij bevestigt met één klik. De BTW telt automatisch mee als voorbelasting.</div>
      </div>

      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="feature-title">Team &amp; rollen</div>
        <div class="feature-desc">Nodig collega's of je boekhouder gratis uit, ieder met eigen rechten: beheerder, medewerker of alleen inzien.</div>
      </div>

      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="feature-title">Uren, strippenkaarten &amp; kilometers</div>
        <div class="feature-desc">Schrijf uren met de timer, verkoop vooraf betaalde urenbundels die automatisch aftellen, en belast ritten door — alles met één klik op een factuur.</div>
      </div>

      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
        </div>
        <div class="feature-title">Offertes: ondertekend &amp; in termijnen</div>
        <div class="feature-desc">Je klant tekent digitaal in het portaal — rechtsgeldig, met bewijsdossier. Daarna factureer je in één keer of in termijnen, met iDEAL-link en QR op de factuur.</div>
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
        <p>Bij andere pakketten zijn dit betaalde extra's of duurdere bundels: digitaal ondertekenen, een tweede handelsnaam, een tweede administratie, e-facturatie. Bij EasyInvoice zit dat állemaal in Basis. Wil je dat de AI ook je bonnen, inkoopfacturen en offertes invult? Dan kies je Slim.</p>
        <ul class="pricing-lead-points">
          <li>Meerdere administraties én handelsnamen inbegrepen</li>
          <li>Onbeperkt facturen, offertes, klanten en producten</li>
          <li>Alle facturatiefuncties in élk abonnement</li>
          <li>14 dagen gratis proberen — inclusief alle AI-functies</li>
          <li>Persoonlijke ondersteuning · maandelijks opzegbaar</li>
        </ul>
      </div>

      <div class="pricing-cards">
        <div class="pricing-card basic">
          <div class="pricing-title">Basis</div>
          <div class="pricing-desc">Alles om te factureren en je administratie bij te houden</div>
          <div class="pricing-price-row">
            <div class="pricing-price"><span class="euro">€</span>10</div>
            <div class="pricing-period">/ maand</div>
          </div>
          <div class="pricing-vat">Excl. 21% BTW · € 12,10 incl. BTW</div>
          <ul class="pricing-features">
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Onbeperkt facturen, offertes en creditnota's</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Meerdere administraties én handelsnamen</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Digitaal ondertekenen + termijnfacturen</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>iDEAL-link en QR-code op je factuur</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Uren, strippenkaarten en kilometers</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>BTW-, jaar- en cashflow-overzicht</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Herinneringen, aanmaningen en incasso</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>E-facturatie: UBL en Peppol</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Gratis extra gebruikers en je boekhouder</li>
          </ul>
          <a href="{{ route('register') }}" class="btn btn-secondary btn-lg" style="width:100%;justify-content:center;">Start 14 dagen gratis</a>
          <div class="pricing-fineprint">Geen creditcard nodig · Opzeggen wanneer je wil</div>
        </div>

        <div class="pricing-card">
          <div class="pricing-badge">Meest gekozen</div>
          <div class="pricing-title">Slim</div>
          <div class="pricing-desc">Alles uit Basis, plus de AI die je administratie invult</div>
          <div class="pricing-price-row">
            <div class="pricing-price"><span class="euro">€</span>17,50</div>
            <div class="pricing-period">/ maand</div>
          </div>
          <div class="pricing-vat">Excl. 21% BTW · € 21,18 incl. BTW</div>
          <ul class="pricing-features">
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><b>Alles uit Basis</b></li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Scan &amp; herken: bonnen en inkoopfacturen automatisch ingevuld</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Postvak IN met automatische boekingsvoorstellen</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Offerte uit tekst: plak je conceptofferte, het formulier vult zich in</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Claude-koppeling: "zet deze offerte in EasyInvoice" — rechtstreeks vanuit je Claude-gesprek</li>
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
<section class="section section-alt" id="faq">
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
          Je data staat op servers in de Europese Unie, dagelijks geback-upt. We zijn AVG-compliant en bieden tweestapsverificatie. Alle verkeer is versleuteld via TLS 1.3. Je houdt de eigendomsrechten op al je data en kunt het op elk moment exporteren.
        </div>
      </details>

      <details class="faq-item">
        <summary>
          Werkt EasyInvoice samen met mijn boekhouder?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Ja. Nodig je boekhouder gratis uit met een eigen "alleen inzien"-rol: die ziet dan je hele administratie, het BTW-overzicht en alle exports (CSV van verkoop én inkoop), maar kan niets wijzigen. Een directe koppeling met Twinfield, e-Boekhouden en Exact volgt later dit jaar.
        </div>
      </details>

      <details class="faq-item">
        <summary>
          Wat zit er ná de gratis proefperiode in het abonnement?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Je kiest uit twee smaken: <b>Basis</b> (€ 12,10 per maand incl. btw) met het volledige facturatiepakket — onbeperkt facturen, offertes, klanten, incasso, e-facturatie — of <b>Slim</b> (€ 21,18 per maand incl. btw) met daarbovenop de AI-functies: bonnen en inkoopfacturen automatisch herkennen en offertes uit tekst. Tijdens de proefperiode probeer je alles.
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
    <div style="margin-top:16px;font-size:13px;opacity:0.8;">Geen creditcard nodig · Daarna vanaf € 12,10/maand incl. btw</div>
  </div>
</section>

<script>
(function () {
  var mock = document.getElementById('heroDash');
  if (!mock) return;

  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var screens = Array.prototype.slice.call(mock.querySelectorAll('.d-screen'));
  var navitems = Array.prototype.slice.call(mock.querySelectorAll('.d-navitem'));
  var urlEl = document.getElementById('dashUrl');
  var progEl = document.getElementById('dashProgress');
  var DWELL = 3800;
  if (!screens.length) return;

  // URL-slug per scherm (zelfde volgorde als de schermen en de menu-items).
  var slugs = ['dashboard', 'facturen', 'offertes', 'uren', 'incasso', 'klantomzet', 'instellingen/handelsnamen'];

  function fmt(n, d) { return n.toLocaleString('nl-NL', { minimumFractionDigits: d, maximumFractionDigits: d }); }
  function setNum(el, val) {
    var d = parseInt(el.getAttribute('data-decimals') || '0', 10);
    el.textContent = (el.getAttribute('data-prefix') || '') + fmt(d ? val : Math.round(val), d) + (el.getAttribute('data-suffix') || '');
  }
  function countUp(el) {
    var target = parseFloat(el.getAttribute('data-count')) || 0;
    if (reduce) { setNum(el, target); return; }
    var dur = 1200, s = null;
    function step(ts) {
      if (s === null) s = ts;
      var p = Math.min((ts - s) / dur, 1);
      setNum(el, target * (1 - Math.pow(1 - p, 3)));
      if (p < 1) requestAnimationFrame(step); else setNum(el, target);
    }
    requestAnimationFrame(step);
  }

  var current = -1, timer = null, hovering = false, started = false;

  function restartProgress() {
    if (!progEl || reduce) return;
    progEl.style.transition = 'none';
    progEl.style.width = '0%';
    void progEl.offsetWidth; // forceer reflow zodat de animatie opnieuw start
    progEl.style.transition = 'width ' + DWELL + 'ms linear';
    progEl.style.width = '100%';
  }

  function show(idx) {
    idx = ((idx % screens.length) + screens.length) % screens.length;
    if (idx === current) return;
    current = idx;
    for (var i = 0; i < screens.length; i++) screens[i].classList.toggle('active', i === idx);
    for (var j = 0; j < navitems.length; j++) navitems[j].classList.toggle('active', j === idx);
    if (urlEl && slugs[idx]) urlEl.textContent = 'app.easyinvoice.nl/' + slugs[idx];
    var nums = screens[idx].querySelectorAll('[data-count]');
    for (var k = 0; k < nums.length; k++) countUp(nums[k]);
    restartProgress();
  }

  function stop() { if (timer) { clearInterval(timer); timer = null; } }
  function play() { stop(); if (reduce) return; timer = setInterval(function () { show(current + 1); }, DWELL); }

  // Klikbaar menu: spring naar het gekozen scherm.
  navitems.forEach(function (item, i) {
    item.addEventListener('click', function () { show(i); if (!hovering && !reduce) play(); });
  });

  // Pauzeer zolang de muis erop staat.
  mock.addEventListener('mouseenter', function () { hovering = true; stop(); });
  mock.addEventListener('mouseleave', function () { hovering = false; if (started) { restartProgress(); play(); } });

  mock.classList.add('js-ready');

  function onEnter() {
    if (!started) { started = true; show(0); }
    if (!hovering) play();
  }

  if (reduce) { show(0); return; }

  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      for (var i = 0; i < entries.length; i++) {
        if (entries[i].isIntersecting) onEnter(); else stop();
      }
    }, { threshold: 0.3 });
    io.observe(mock);
  } else {
    onEnter();
  }
})();
</script>
@endsection
