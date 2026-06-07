@extends('layouts.marketing')

@section('title', 'Pers — EasyInvoice')
@section('description', 'Perskit en nieuws van EasyInvoice. Feiten, logo en aankondigingen voor pers.')

@push('styles')
<style>
  .fact-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
  @media (max-width: 720px) { .fact-grid { grid-template-columns: repeat(2, 1fr); } }
  .fact { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 24px; text-align: center; }
  .fact-num { font-family: var(--font-display); font-weight: 700; font-size: 26px; color: var(--brand); }
  .fact-label { font-size: 14px; color: var(--text-3); margin-top: 4px; }
  .logo-box { border: 1px solid var(--border); border-radius: 14px; padding: 44px; display: flex; align-items: center; justify-content: center; gap: 12px; font-family: var(--font-display); font-weight: 700; font-size: 24px; }
  .logo-mark { width: 44px; height: 44px; border-radius: 11px; background: var(--brand); color: #fff; display: inline-flex; align-items: center; justify-content: center; }
  .news-row { display: flex; gap: 16px; align-items: baseline; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 16px 20px; margin-bottom: 12px; }
  .news-date { font-size: 13px; color: var(--text-3); white-space: nowrap; min-width: 110px; }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Pers</div>
    <h1>Perskit &amp; nieuws</h1>
    <p class="lead">Schrijf je over EasyInvoice? Hier vind je de feiten, ons logo en de laatste aankondigingen. Voor persvragen mail je <a href="mailto:pers@easyinvoice.nl" style="color:var(--brand);font-weight:600;">pers@easyinvoice.nl</a>.</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <h2 style="font-size:24px;margin-bottom:22px;">In het kort</h2>
    <div class="fact-grid">
      <div class="fact"><div class="fact-num">2024</div><div class="fact-label">Opgericht</div></div>
      <div class="fact"><div class="fact-num">Amsterdam</div><div class="fact-label">Hoofdkantoor</div></div>
      <div class="fact"><div class="fact-num">2.500+</div><div class="fact-label">Gebruikers</div></div>
      <div class="fact"><div class="fact-num">€2,50</div><div class="fact-label">Per maand</div></div>
    </div>
  </div>
</section>

<section class="section section-alt" style="padding-top:56px;padding-bottom:56px;">
  <div class="container">
    <h2 style="font-size:24px;margin-bottom:22px;">Logo &amp; merk</h2>
    <div class="card-grid cols-2">
      <div class="logo-box" style="background:var(--surface);">
        <span class="logo-mark"><svg viewBox="0 0 32 32" width="26" height="26" fill="none"><circle cx="16" cy="14" r="11" stroke="currentColor" stroke-width="2.5"/><path d="M11 10h10v3h-7v2h6v3h-6v2h7v3H11z" fill="currentColor"/></svg></span>
        EasyInvoice
      </div>
      <div class="logo-box" style="background:var(--text);color:#fff;">
        <span class="logo-mark"><svg viewBox="0 0 32 32" width="26" height="26" fill="none"><circle cx="16" cy="14" r="11" stroke="currentColor" stroke-width="2.5"/><path d="M11 10h10v3h-7v2h6v3h-6v2h7v3H11z" fill="currentColor"/></svg></span>
        EasyInvoice
      </div>
    </div>
    <p style="margin-top:14px;font-size:14px;color:var(--text-3);">Gebruik het logo niet vervormd of in andere kleuren. De merkkleur is <strong style="color:var(--brand);">#E8231F</strong>.</p>
  </div>
</section>

<section class="section">
  <div class="container" style="max-width:760px;">
    <h2 style="font-size:24px;margin-bottom:22px;">Persberichten</h2>
    <div class="news-row"><span class="news-date">15 mei 2026</span><span>EasyInvoice lanceert EASY-assistent, een slimme assistent voor ondernemers.</span></div>
    <div class="news-row"><span class="news-date">2 april 2026</span><span>Nieuw gefaseerd incassotraject helpt ondernemers sneller betaald te krijgen.</span></div>
    <div class="news-row"><span class="news-date">10 januari 2026</span><span>EasyInvoice bereikt de mijlpaal van 2.500 actieve ondernemers.</span></div>
  </div>
</section>
@endsection
