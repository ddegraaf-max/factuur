@extends('layouts.marketing')

@section('title', 'Factuurprogramma voor Nederlandse ondernemers — EasyInvoice')
@section('description', 'Facturen, offertes met digitale ondertekening, BTW-overzicht, incasso en AI die je administratie invult. Vanaf € 12,10 per maand. Probeer 14 dagen gratis.')

@section('content')

@push('styles')
<style>
  /* ===== Huisstijl 1.106.0: donkere hero met rood, laptop + telefoon, acht kaarten, vertrouwensbalk ===== */
  .ei-hero { position: relative; overflow: hidden; color: #fff; padding: 56px 0 0; background: linear-gradient(105deg, #0E141B 0%, #171A22 46%, #3A0E12 72%, #C8121B 100%); }
  .ei-hero::before { content: ''; position: absolute; right: -220px; top: -220px; width: 780px; height: 780px; border-radius: 50%; background: radial-gradient(circle, rgba(232,35,31,0.5) 0%, rgba(232,35,31,0) 62%); pointer-events: none; }
  .ei-hero-grid { position: relative; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center; padding-bottom: 60px; }
  .ei-hero-grid > * { min-width: 0; } /* anders rekt het 1080 px brede dashboard de kolom op */
  .ei-pill { display: inline-flex; align-items: center; gap: 9px; padding: 7px 16px 7px 8px; border-radius: 100px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.14); font-size: 14px; font-weight: 600; margin-bottom: 20px; }
  .ei-pill i { width: 22px; height: 22px; border-radius: 50%; background: var(--brand); color: #fff; font-style: normal; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; }
  .ei-hero h1 { color: #fff; font-size: clamp(34px, 3.6vw, 48px); font-weight: 800; line-height: 1.06; letter-spacing: -0.03em; margin: 0 0 16px; }
  .ei-hero h1 .accent { color: #FF4A45; }
  .ei-hero h1 sup { font-size: 0.4em; vertical-align: super; font-weight: 600; margin-left: 2px; }
  .ei-sub { font-size: clamp(16px, 1.3vw, 18px); color: rgba(255,255,255,0.84); line-height: 1.55; max-width: 560px; margin: 0 0 20px; }
  .ei-checks { list-style: none; padding: 0; margin: 0 0 26px; display: grid; gap: 8px; }
  .ei-checks li { display: flex; align-items: center; gap: 11px; font-size: 15.5px; font-weight: 500; color: #fff; }
  .ei-checks li::before { content: '✓'; flex-shrink: 0; width: 22px; height: 22px; border-radius: 6px; background: rgba(232,35,31,0.22); color: #FF4A45; font-weight: 800; font-size: 13px; display: inline-flex; align-items: center; justify-content: center; }
  .ei-ctas { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
  .btn-outline-light { background: rgba(255,255,255,0.06); color: #fff; border-color: rgba(255,255,255,0.4); }
  .btn-outline-light:hover { background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.7); }
  .ei-trust { list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: 6px 22px; font-size: 13.5px; color: rgba(255,255,255,0.75); }
  .ei-trust li::before { content: '✓ '; color: #FF4A45; font-weight: 800; }
  .ei-applink { display: flex; align-items: center; gap: 8px; margin: 16px 0 0; font-size: 14px; color: rgba(255,255,255,0.8); }
  .ei-applink svg { color: #FF4A45; flex-shrink: 0; }
  .ei-applink a { color: #fff; font-weight: 700; text-decoration: underline; text-underline-offset: 3px; }
  .ei-hero-visual { position: relative; min-width: 0; }
  .ei-screen { position: relative; overflow: hidden; width: 100%; background: #0B0E12; border: 10px solid #1B1E25; border-bottom-width: 14px; border-radius: 18px 18px 6px 6px; box-shadow: 0 40px 90px rgba(0,0,0,0.5); }
  .ei-screen .ei-dash { transform-origin: 0 0; }
  /* Dashboard uit het ontwerp (1.106.3), vaste maat 1080x600 en geschaald naar het laptopscherm */
  .ei-dash { width: 1080px; height: 600px; display: grid; grid-template-columns: 214px 1fr; background: #F4F5F7; color: #1C1917; text-align: left; font-family: var(--font-body); }
  .ei-dash .dsb { background: #15181F; color: #fff; padding: 16px 12px; display: flex; flex-direction: column; gap: 1px; }
  .ei-dash .dsb-brand { display: flex; align-items: center; gap: 9px; padding: 2px 8px 14px; font-family: var(--font-display); font-weight: 800; font-size: 17px; letter-spacing: -0.01em; }
  .ei-dash .dsb-brand img { width: 30px; height: 30px; border-radius: 8px; }
  .ei-dash .dsb-brand sup { font-size: 9px; font-weight: 600; }
  .ei-dash .dsb a { display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-radius: 9px; font-size: 13px; color: rgba(255,255,255,0.78); }
  .ei-dash .dsb a svg { flex-shrink: 0; opacity: 0.85; }
  .ei-dash .dsb a.active { background: var(--brand); color: #fff; font-weight: 600; }
  .ei-dash .dmn { padding: 14px 22px 16px; display: flex; flex-direction: column; gap: 11px; min-width: 0; }
  .ei-dash .dtop { display: flex; align-items: center; justify-content: flex-end; gap: 12px; }
  .ei-dash .dsearch { width: 220px; background: #fff; border: 1px solid #E5E7EB; border-radius: 8px; padding: 6px 10px; font-size: 12px; color: #9CA3AF; display: flex; gap: 7px; align-items: center; }
  .ei-dash .dbell { position: relative; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; color: #374151; }
  .ei-dash .dbell i { position: absolute; top: 4px; right: 5px; width: 7px; height: 7px; border-radius: 50%; background: var(--brand); border: 1.5px solid #F4F5F7; }
  .ei-dash .davatar { width: 30px; height: 30px; border-radius: 50%; background: var(--brand); color: #fff; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
  .ei-dash .dhead { display: flex; align-items: flex-start; justify-content: space-between; }
  .ei-dash .dhead h3 { font-family: var(--font-display); font-size: 19px; font-weight: 700; margin: 0; letter-spacing: -0.01em; color: #1C1917; }
  .ei-dash .dhead p { margin: 2px 0 0; font-size: 12px; color: #6B7280; }
  .ei-dash .dsel { font-size: 12px; background: #fff; border: 1px solid #E5E7EB; border-radius: 8px; padding: 6px 10px; color: #374151; }
  .ei-dash .dkpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
  .ei-dash .dkpi { position: relative; background: #fff; border-radius: 12px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(0,0,0,0.04); }
  .ei-dash .dkpi span { display: block; font-size: 11px; color: #6B7280; margin-bottom: 6px; }
  .ei-dash .dkpi b { font-family: var(--font-display); font-size: 21px; letter-spacing: -0.02em; line-height: 1; }
  .ei-dash .dkpi em { position: absolute; right: 12px; bottom: 12px; font-style: normal; font-size: 10.5px; font-weight: 700; border-radius: 100px; padding: 2px 7px; }
  .ei-dash .dkpi em.up { background: #DCFCE7; color: #15803D; }
  .ei-dash .dkpi em.badge { background: var(--brand); color: #fff; width: 20px; height: 20px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 50%; }
  .ei-dash .dkpi em.cal { background: #FEE2E2; color: var(--brand); width: 24px; height: 24px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 6px; }
  .ei-dash .drow { display: grid; grid-template-columns: 1.6fr 1fr; gap: 12px; }
  .ei-dash .dcard { background: #fff; border-radius: 12px; padding: 13px 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.04); min-width: 0; }
  .ei-dash .dcard-h { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
  .ei-dash .dcard-h b { font-family: var(--font-display); font-size: 14px; }
  .ei-dash .pill { font-size: 10.5px; font-weight: 700; color: #15803D; background: #DCFCE7; border-radius: 100px; padding: 3px 8px; }
  .ei-dash .dchart svg { width: 100%; height: 118px; display: block; }
  .ei-dash .dai-h { font-family: var(--font-display); font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
  .ei-dash .dai-h svg { color: var(--brand); }
  .ei-dash .dai-h span { color: var(--brand); }
  .ei-dash .dai p { font-size: 12px; color: #4B5563; margin: 0 0 10px; line-height: 1.45; }
  .ei-dash .dai-in { border: 1px solid #E5E7EB; border-radius: 8px; padding: 8px 10px; font-size: 11.5px; color: #9CA3AF; margin-bottom: 8px; }
  .ei-dash .dai-btn { background: var(--brand); color: #fff; font-size: 12px; font-weight: 700; text-align: center; padding: 8px; border-radius: 8px; }
  .ei-dash .dinv table { width: 100%; border-collapse: collapse; font-size: 12px; }
  .ei-dash .dinv td { padding: 7px 4px; border-top: 1px solid #F3F4F6; }
  .ei-dash .dinv tr:first-child td { border-top: 0; }
  .ei-dash .dinv td:first-child { width: 22px; }
  .ei-dash .dot { width: 16px; height: 16px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 9px; color: #fff; font-weight: 800; }
  .ei-dash .dot.g { background: #22C55E; }
  .ei-dash .dot.a { background: #F59E0B; }
  .ei-dash .dinv td.num { font-family: var(--font-mono); font-size: 11px; color: #6B7280; }
  .ei-dash .dinv td.r { text-align: right; font-weight: 700; }
  .ei-dash .dpay-body { display: flex; align-items: center; gap: 16px; }
  .ei-dash .dpay-body svg { width: 86px; height: 86px; flex-shrink: 0; }
  .ei-dash .dpay-leg b { display: block; font-family: var(--font-display); font-size: 15px; }
  .ei-dash .dpay-leg span { display: block; font-size: 11px; color: #6B7280; margin-bottom: 8px; }
  .ei-base { height: 16px; margin: 0 -5%; background: linear-gradient(180deg, #D6D8DD 0%, #B9BCC3 100%); border-radius: 0 0 14px 14px; position: relative; }
  .ei-base::before { content: ''; position: absolute; left: 50%; top: 0; transform: translateX(-50%); width: 120px; height: 6px; background: #A4A7AE; border-radius: 0 0 6px 6px; }
  .ei-phone { position: absolute; right: -28px; bottom: -22px; width: 176px; padding: 8px 7px 10px; background: #0F1116; border-radius: 26px; box-shadow: 0 30px 60px rgba(0,0,0,0.5); border: 3px solid #2A2D35; z-index: 2; }
  .ei-phone-top { display: flex; align-items: center; gap: 6px; background: var(--brand); color: #fff; border-radius: 16px 16px 4px 4px; padding: 11px 10px; font-weight: 700; font-size: 11.5px; }
  .ei-phone-top img { width: 16px; height: 16px; }
  .ei-phone-top i { margin-left: auto; width: 12px; height: 2px; background: #fff; box-shadow: 0 -4px 0 #fff, 0 4px 0 #fff; border-radius: 2px; }
  .ei-phone-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; background: #F3F4F6; border-radius: 4px; padding: 8px 6px; }
  .ei-phone-grid div { background: #fff; border-radius: 10px; padding: 9px 6px 7px; display: flex; flex-direction: column; align-items: center; gap: 5px; font-size: 9.5px; font-weight: 600; color: #1C1917; }
  .ei-phone-grid svg { color: var(--brand); }
  .ei-phone-btn { margin-top: 6px; background: var(--brand); color: #fff; font-size: 10.5px; font-weight: 700; text-align: center; padding: 8px; border-radius: 10px; }
  @media (max-width: 1000px) {
    .ei-hero-grid { grid-template-columns: 1fr; gap: 32px; }
    .ei-hero-visual { max-width: 720px; }
    .ei-phone { right: 0; }
  }
  @media (max-width: 760px) {
    .ei-hero { padding-top: 36px; }
    .ei-phone { display: none; }
    .ei-base { margin: 0; }
  }
  .ei-cards { background: #F3F4F6; padding: 28px 0 32px; }
  .ei-cards-grid { display: grid; grid-template-columns: repeat(8, 1fr); gap: 12px; }
  @media (max-width: 1180px) { .ei-cards-grid { grid-template-columns: repeat(4, 1fr); } }
  @media (max-width: 640px) { .ei-cards-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; } }
  .ei-card { position: relative; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 6px; padding: 20px 10px 16px; background: #fff; border: 1px solid var(--border); border-radius: 14px; color: inherit; transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s; }
  .ei-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); border-color: var(--brand-border); }
  .ei-card .ic { width: 44px; height: 44px; color: var(--brand); display: flex; align-items: center; justify-content: center; }
  .ei-card b { font-family: var(--font-display); font-size: 15px; letter-spacing: -0.01em; color: var(--text); line-height: 1.2; }
  .ei-card .tx { font-size: 12px; color: var(--text-2); line-height: 1.45; }
  .ei-card .new { position: absolute; top: 10px; right: 10px; background: var(--brand); color: #fff; font-size: 9.5px; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; padding: 3px 7px; border-radius: 100px; }
  .ei-trustbar { background: #EBECED; border-top: 1px solid #E0E2E5; border-bottom: 1px solid #E0E2E5; padding: 14px 0; }
  .ei-trust-inner { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px 28px; font-size: 14px; color: var(--text-2); font-weight: 500; }
  .ei-trust-inner span::before { content: '✓'; color: var(--brand); font-weight: 800; margin-right: 8px; }
  .ei-trust-inner b { color: var(--text); }
  @media (prefers-reduced-motion: reduce) { .ei-card, .ei-card:hover { transition: none; transform: none; } }

  /* ===== Gratis tools onder de hero: twee kaarten, elk met een knop die van kleur verschuift ===== */
  .free-tools { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; max-width: 940px; margin: 0 auto; text-align: left; }
  .free-tool { display: flex; flex-direction: column; gap: 16px; padding: 20px 22px 22px; background: var(--surface); border: 1px solid var(--brand-border); border-radius: 16px; box-shadow: var(--shadow-md); color: inherit; text-decoration: none; transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease; }
  .free-tool:hover { transform: translateY(-3px); box-shadow: var(--shadow-brand); border-color: var(--brand); }
  .ft-top { display: flex; gap: 14px; align-items: flex-start; }
  .ft-icon { flex: none; width: 44px; height: 44px; border-radius: 12px; background: var(--brand-tint); color: var(--brand); display: inline-flex; align-items: center; justify-content: center; }
  .ft-icon svg { width: 22px; height: 22px; }
  .ft-body { display: flex; flex-direction: column; gap: 3px; }
  .ft-kicker { font-size: 11.5px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--brand); }
  .ft-title { font-family: var(--font-display); font-weight: 700; font-size: 20px; line-height: 1.25; letter-spacing: -0.02em; color: var(--text); }
  .ft-text { font-size: 14px; line-height: 1.55; color: var(--text-2); margin-top: 3px; }
  .btn-live { position: relative; isolation: isolate; align-self: flex-start; margin-top: auto; color: #fff; border: 0; text-shadow: 0 1px 1px rgba(0, 0, 0, 0.22); background-image: linear-gradient(115deg, var(--brand-darker) 0%, var(--brand) 26%, #EA580C 50%, var(--brand) 74%, var(--brand-darker) 100%); background-size: 260% 100%; background-position: 0% 50%; animation: live-shift 4.5s ease-in-out infinite; box-shadow: 0 4px 18px rgba(232, 35, 31, 0.30); }
  .btn-live.dark { background-image: linear-gradient(115deg, #1C1917 0%, #44403C 26%, var(--brand) 50%, #44403C 74%, #1C1917 100%); animation-delay: -2.2s; box-shadow: 0 4px 18px rgba(28, 25, 23, 0.28); }
  .free-tool:hover .btn-live { animation-duration: 1.6s; box-shadow: 0 8px 26px rgba(232, 35, 31, 0.42); }
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

</style>
@endpush

<!-- HERO (huisstijl 1.106.0: donker met rood, het geanimeerde dashboard in een laptop, telefoon ernaast) -->
<section class="ei-hero">
  <div class="container ei-hero-grid">
    <div class="ei-hero-text">
      <div class="ei-pill"><i>✓</i>Alles-in-één platform voor ondernemers</div>
      <h1>Factureren, administreren en méér – met <span class="accent">EasyInvoice</span><sup>&reg;</sup></h1>
      <p class="ei-sub">Van offerte tot betaling, van urenregistratie tot boekhouding. Alles in één overzichtelijk platform, inclusief AI-assistent EASY.</p>
      <ul class="ei-checks">
        <li>Professioneel factureren en offertes versturen</li>
        <li>Volledige administratie en btw-aangifte</li>
        <li>Urenregistratie, projecten en planning</li>
        <li>Betalingen, herinneringen en incasso</li>
        <li>AI-assistent EASY en scan-en-herken</li>
        <li>Meerdere bedrijven en handelsnamen</li>
        <li>E-facturatie (UBL / Peppol) en bankafschriften inlezen</li>
      </ul>
      <div class="ei-ctas">
        <a href="{{ route('register') }}" class="btn btn-primary btn-lg">
          Start 14 dagen gratis
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
        <a href="{{ route('demo') }}" class="btn btn-outline-light btn-lg">@include('partials.icon', ['name' => 'play', 'size' => 14]) Bekijk de demo</a>
      </div>
      <ul class="ei-trust"><li>Geen creditcard nodig</li><li>Binnen 2 minuten gestart</li><li>Maandelijks opzegbaar</li></ul>
      <p class="ei-applink">@include('partials.icon', ['name' => 'phone', 'size' => 16]) Ook als app op je telefoon en computer: <a href="{{ route('app-install') }}">zo installeer je {{ brand('name') }}</a></p>
    </div>
    <div class="ei-hero-visual">
      <div class="ei-laptop">
        <div class="ei-screen">
      <div class="ei-dash" aria-hidden="true">
        <aside class="dsb">
          <div class="dsb-brand"><img src="/images/easyinvoice-favicon-180.png" alt="">EasyInvoice<sup>&reg;</sup></div>
        <a class="active">@include('partials.icon', ['name' => 'home', 'size' => 15])Dashboard</a>
        <a>@include('partials.icon', ['name' => 'invoice', 'size' => 15])Facturen</a>
        <a>@include('partials.icon', ['name' => 'edit', 'size' => 15])Offertes</a>
        <a>@include('partials.icon', ['name' => 'user', 'size' => 15])Klanten</a>
        <a>@include('partials.icon', ['name' => 'clock', 'size' => 15])Uren &amp; projecten</a>
        <a>@include('partials.icon', ['name' => 'calendar', 'size' => 15])Planning</a>
        <a>@include('partials.icon', ['name' => 'mail', 'size' => 15])Inkoopfacturen</a>
        <a>@include('partials.icon', ['name' => 'card', 'size' => 15])Betalingen</a>
        <a>@include('partials.icon', ['name' => 'bank', 'size' => 15])Incasso</a>
        <a>@include('partials.icon', ['name' => 'layers', 'size' => 15])Administraties</a>
        <a>@include('partials.icon', ['name' => 'chart', 'size' => 15])Rapportages</a>
        <a>@include('partials.icon', ['name' => 'tools', 'size' => 15])Instellingen</a>
        </aside>
        <div class="dmn">
          <div class="dtop">
            <div class="dsearch">@include('partials.icon', ['name' => 'search', 'size' => 13]) Zoeken...</div>
            <div class="dbell">@include('partials.icon', ['name' => 'bell', 'size' => 16])<i></i></div>
            <div class="davatar">DG</div>
          </div>
          <div class="dhead"><div><h3>Welkom terug, De Graaf BV</h3><p>Hier is je actuele overzicht</p></div><div class="dsel">Deze maand &#9662;</div></div>
          <div class="dkpis">
            <div class="dkpi"><span>Omzet (deze maand)</span><b>&euro; 24.850</b><em class="up">&uarr; +18%</em></div>
            <div class="dkpi"><span>Openstaande facturen</span><b>&euro; 5.320</b><em class="badge">3</em></div>
            <div class="dkpi"><span>Te ontvangen</span><b>&euro; 19.530</b></div>
            <div class="dkpi"><span>Btw (te betalen)</span><b>&euro; 4.142</b><em class="cal">@include('partials.icon', ['name' => 'calendar', 'size' => 13])</em></div>
          </div>
          <div class="drow">
            <div class="dcard dchart">
              <div class="dcard-h"><b>Omzetontwikkeling</b><span class="pill">&uarr; +18% t.o.v. vorige maand</span></div>
              <svg viewBox="0 0 520 150" preserveAspectRatio="none">
                <defs><linearGradient id="eiArea" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#E8231F" stop-opacity="0.22"/><stop offset="1" stop-color="#E8231F" stop-opacity="0"/></linearGradient></defs>
                <g stroke="#EEF0F3" stroke-width="1"><line x1="55" y1="10" x2="515" y2="10"/><line x1="55" y1="39" x2="515" y2="39"/><line x1="55" y1="68" x2="515" y2="68"/><line x1="55" y1="96" x2="515" y2="96"/><line x1="55" y1="125" x2="515" y2="125"/></g>
                <g fill="#9CA3AF" font-size="9" font-family="DM Sans, sans-serif"><text x="0" y="13">&euro; 30.000</text><text x="0" y="42">&euro; 25.000</text><text x="0" y="71">&euro; 20.000</text><text x="0" y="99">&euro; 15.000</text><text x="0" y="128">&euro; 0</text></g>
                <path d="M55 98 L147 83 L239 71 L331 52 L423 41 L515 22 L515 125 L55 125 Z" fill="url(#eiArea)"/>
                <path d="M55 98 L147 83 L239 71 L331 52 L423 41 L515 22" fill="none" stroke="#E8231F" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
                <g fill="#fff" stroke="#E8231F" stroke-width="2"><circle cx="55" cy="98" r="3.5"/><circle cx="147" cy="83" r="3.5"/><circle cx="239" cy="71" r="3.5"/><circle cx="331" cy="52" r="3.5"/><circle cx="423" cy="41" r="3.5"/><circle cx="515" cy="22" r="3.5"/></g>
                <g fill="#9CA3AF" font-size="9" font-family="DM Sans, sans-serif" text-anchor="middle"><text x="55" y="145">Jun</text><text x="147" y="145">Jul</text><text x="239" y="145">Aug</text><text x="331" y="145">Sep</text><text x="423" y="145">Okt</text><text x="515" y="145">Nov</text></g>
              </svg>
            </div>
            <div class="dcard dai">
              <div class="dai-h">@include('partials.icon', ['name' => 'bot', 'size' => 18])AI-assistent <span>EASY</span></div>
              <p>Maak facturen, offertes of herinneringen met AI.</p>
              <div class="dai-in">Bijv. &bdquo;Maak een offerte voor...&rdquo;</div>
              <div class="dai-btn">Genereren &#10022;</div>
            </div>
          </div>
          <div class="drow">
            <div class="dcard dinv">
              <div class="dcard-h"><b>Recente facturen</b></div>
              <table>
                <tr><td><span class="dot g">&#10003;</span></td><td class="num">F2025-0147</td><td>Bouwservice BV</td><td class="r">&euro; 3.250,00</td></tr>
                <tr><td><span class="dot g">&#10003;</span></td><td class="num">F2025-0146</td><td>Installatietechniek NL</td><td class="r">&euro; 1.980,00</td></tr>
                <tr><td><span class="dot a">!</span></td><td class="num">F2025-0145</td><td>Jansen Vastgoed</td><td class="r">&euro; 5.320,00</td></tr>
              </table>
            </div>
            <div class="dcard dpay">
              <div class="dcard-h"><b>Betalingen</b></div>
              <div class="dpay-body">
                <svg viewBox="0 0 86 86"><circle cx="43" cy="43" r="34" fill="none" stroke="#F3F4F6" stroke-width="11"/><circle cx="43" cy="43" r="34" fill="none" stroke="#E8231F" stroke-width="11" stroke-dasharray="185.9 213.6" stroke-linecap="round" transform="rotate(-90 43 43)"/><text x="43" y="48" text-anchor="middle" font-size="16" font-weight="700" fill="#1C1917" font-family="DM Sans, sans-serif">87%</text></svg>
                <div class="dpay-leg"><b>&euro; 21.700</b><span>Betaald</span><b>&euro; 3.150</b><span>Openstaand</span></div>
              </div>
            </div>
          </div>
        </div>
      </div>
        </div>
        <div class="ei-base"></div>
      </div>
      <div class="ei-phone" aria-hidden="true">
        <div class="ei-phone-top"><img src="/images/easyinvoice-logo-white.png" alt=""><span>EasyInvoice</span><i></i></div>
        <div class="ei-phone-grid">
          <div>@include('partials.icon', ['name' => 'invoice', 'size' => 18])Facturen</div>
          <div>@include('partials.icon', ['name' => 'edit', 'size' => 18])Offertes</div>
          <div>@include('partials.icon', ['name' => 'clock', 'size' => 18])Uren</div>
          <div>@include('partials.icon', ['name' => 'layers', 'size' => 18])Projecten</div>
          <div>@include('partials.icon', ['name' => 'card', 'size' => 18])Betalingen</div>
          <div>@include('partials.icon', ['name' => 'chart', 'size' => 18])Administratie</div>
        </div>
        <div class="ei-phone-btn">+ Nieuwe factuur</div>
      </div>
    </div>
  </div>
</section>

<!-- ACHT FUNCTIES IN ÉÉN RIJ -->
<section class="ei-cards" aria-label="Functies in het kort">
  <div class="container">
    <div class="ei-cards-grid">
      <a href="#functies" class="ei-card"><span class="ic">@include('partials.icon', ['name' => 'invoice', 'size' => 30])</span><b>Facturatie</b><span class="tx">Facturen, creditnota's, termijn- en terugkerende facturen.</span></a>
      <a href="#functies" class="ei-card"><span class="ic">@include('partials.icon', ['name' => 'edit', 'size' => 30])</span><b>Offertes</b><span class="tx">Professionele offertes, digitaal ondertekenen met bewijsdossier.</span></a>
      <a href="#functies" class="ei-card"><span class="ic">@include('partials.icon', ['name' => 'clock', 'size' => 30])</span><b>Uren &amp; projecten</b><span class="tx">Urenregistratie, projectcalculatie, planning en strippenkaarten.</span></a>
      <a href="#waarom" class="ei-card"><span class="ic">@include('partials.icon', ['name' => 'layers', 'size' => 30])</span><b>Administraties</b><span class="tx">Meerdere bedrijven en handelsnamen in één account.</span></a>
      <a href="{{ route('ai') }}" class="ei-card"><span class="new">Nieuw</span><span class="ic">@include('partials.icon', ['name' => 'bot', 'size' => 30])</span><b>AI-assistent EASY</b><span class="tx">Facturen, offertes en teksten genereren, scan-en-herken en Claude-koppeling.</span></a>
      <a href="#functies" class="ei-card"><span class="ic">@include('partials.icon', ['name' => 'bank', 'size' => 30])</span><b>Betalingen &amp; incasso</b><span class="tx">iDEAL, QR-codes, herinneringen en incasso via Armaere Gerechtsdeurwaarders.</span></a>
      <a href="#functies" class="ei-card"><span class="ic">@include('partials.icon', ['name' => 'mail', 'size' => 30])</span><b>E-facturatie</b><span class="tx">UBL / Peppol: klaar voor de e-factuur.</span></a>
      <a href="#functies" class="ei-card"><span class="ic">@include('partials.icon', ['name' => 'chart', 'size' => 30])</span><b>Boekhouding</b><span class="tx">Inkoopfacturen, btw-overzichten, jaaroverzicht en cashflow.</span></a>
    </div>
  </div>
</section>

<!-- VERTROUWEN (alleen wat we waar kunnen maken) -->
<section class="ei-trustbar" aria-label="Waarom je op EasyInvoice kunt bouwen">
  <div class="container ei-trust-inner">
    <span><b>EasyInvoice&reg;</b> is een geregistreerd merk</span>
    <span>Veilig: servers in Amsterdam (EU)</span>
    <span>Nederlandse ondersteuning</span>
    <span>AVG-proof: verwerkersovereenkomst inbegrepen</span>
    <span>Door ondernemers, voor ondernemers</span>
  </div>
</section>

<!-- GRATIS TOOLS: direct te gebruiken, zonder account -->
<section class="section-alt" id="gratis-tools" style="padding:44px 0 56px;">
  <div class="container">
    <div class="free-tools">
      <a class="free-tool" href="{{ route('gratis-factuur') }}">
        <span class="ft-top">
          <span class="ft-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="13" y2="17"/></svg></span>
          <span class="ft-body">
            <span class="ft-kicker">Gratis · zonder account</span>
            <span class="ft-title">Gratis factuur maken</span>
            <span class="ft-text">Vul je gegevens in en download je factuur als PDF, met alle verplichte vermeldingen erop.</span>
          </span>
        </span>
        <span class="btn btn-live">Maak je factuur <span class="live-badge">Gratis</span></span>
      </a>
      <a class="free-tool" href="{{ route('aanmaning') }}">
        <span class="ft-top">
          <span class="ft-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><polyline points="22 6 12 13 2 6"/></svg></span>
          <span class="ft-body">
            <span class="ft-kicker">Gratis · zonder account</span>
            <span class="ft-title">Gratis online aanmaning</span>
            <span class="ft-text">Betaalt je klant niet? Verstuur een aanmaning waarop de rente elke dag oploopt en je klant met één klik reageert.</span>
          </span>
        </span>
        <span class="btn btn-live dark">Verstuur een aanmaning <span class="live-badge">Nieuw</span></span>
      </a>
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
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Huisstijl herkennen met AI: upload je huisstijlgids en alles staat goed</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Eerst proberen? De proefperiode bevat alle AI-functies</li>
          </ul>
          <a href="{{ route('register') }}" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;">Start 14 dagen gratis</a>
          <div class="pricing-fineprint">Geen creditcard nodig · Opzeggen wanneer je wil</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Kennisbank: interne links vanaf de homepage, zodat zoekmachines de artikelen vinden -->
<section class="section" id="kennisbank">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Kennisbank</div>
      <h2>Factureren, btw en betaald krijgen: zo zit het.</h2>
      <p>Praktische antwoorden voor zzp'ers en mkb, geschreven door mensen die dagelijks facturen en incasso's zien.</p>
    </div>

    @php
      $kbSlugs = ['factuureisen', 'eindfactuur', 'btw-aangifte-kwartaal-stappenplan', 'zelfstandigenaftrek-urencriterium-2026', 'incassokosten-wettelijke-rente-berekenen', 'factuur-zonder-kvk'];
      $kbArticles = collect(config('kennisbank.articles'))->only($kbSlugs);
    @endphp
    <div class="features-grid">
      @foreach ($kbArticles as $slug => $article)
      <a class="feature-card" href="{{ url('/kennisbank/' . $slug) }}" style="display:block;color:inherit;text-decoration:none;">
        <div class="feature-title">{{ $article['title'] }}</div>
        <div class="feature-desc">{{ Str::limit($article['intro'], 120) }}</div>
      </a>
      @endforeach
    </div>

    <p style="text-align:center;margin-top:28px;">
      <a href="{{ route('kennisbank') }}" style="color:var(--brand);font-weight:600;">Alle artikelen in de kennisbank →</a>
      &nbsp;·&nbsp; <a href="{{ route('gratis-factuur') }}" style="color:var(--brand);font-weight:600;">Gratis factuur maken</a>
      &nbsp;·&nbsp; <a href="{{ route('btw-calculator') }}" style="color:var(--brand);font-weight:600;">Btw-calculator</a>
    </p>
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
  // Laptop in de hero: het dashboard is op 1080 px ontworpen en wordt geschaald naar de schermbreedte.
  var screen = document.querySelector('.ei-screen'), dash = document.querySelector('.ei-dash');
  if (!screen || !dash) return;
  function fit() {
    var s = screen.clientWidth / 1080;
    dash.style.transform = 'scale(' + s + ')';
    screen.style.height = Math.round(dash.offsetHeight * s) + 'px';
  }
  fit();
  if ('ResizeObserver' in window) { new ResizeObserver(fit).observe(screen); } else { window.addEventListener('resize', fit); }
})();
</script>
@endsection
