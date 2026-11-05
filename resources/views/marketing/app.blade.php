@extends('layouts.marketing')
{{-- /app (1.106.1): uitleg hoe je de webapp (PWA: manifest.webmanifest + public/sw.js) op telefoon en computer zet.
     Er is bewust geen app in de App Store of Play Store; dit is de plek waar de site dat vertelt. --}}

@section('title', brand('name') . ' als app op je telefoon en computer — zo installeer je hem')
@section('description', brand('name') . ' staat niet in de App Store, maar zet je in een paar tikken op je beginscherm: iPhone, Android, Windows en Mac. Daarna opent hij als app, met eigen icoon en een snelkoppeling naar een nieuwe factuur.')

@push('styles')
<style>
  .pwa-steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
  @media (max-width: 900px) { .pwa-steps { grid-template-columns: 1fr; } }
  .pwa-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 26px 24px; }
  .pwa-card .ic { width: 46px; height: 46px; border-radius: 12px; background: var(--brand-tint); color: var(--brand); display: flex; align-items: center; justify-content: center; margin-bottom: 14px; }
  .pwa-card h3 { font-size: 19px; margin-bottom: 4px; }
  .pwa-card .sub { font-size: 13px; color: var(--text-3); margin-bottom: 14px; }
  .pwa-card ol { margin: 0; padding-left: 22px; color: var(--text-2); font-size: 14.5px; line-height: 1.6; }
  .pwa-card li { margin-bottom: 6px; }
  .pwa-card b { color: var(--text); }
  .pwa-perks { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
  @media (max-width: 760px) { .pwa-perks { grid-template-columns: 1fr; } }
  .pwa-perk { display: flex; gap: 12px; align-items: flex-start; background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 16px 18px; font-size: 14.5px; color: var(--text-2); line-height: 1.55; }
  .pwa-perk b { display: block; color: var(--text); margin-bottom: 2px; }
  .pwa-perk .ic { flex-shrink: 0; width: 34px; height: 34px; border-radius: 10px; background: var(--brand-tint); color: var(--brand); display: flex; align-items: center; justify-content: center; }
  .pwa-note { max-width: 760px; margin: 28px auto 0; background: var(--brand-tint); border: 1px solid var(--brand-border); border-radius: 14px; padding: 16px 20px; font-size: 14.5px; color: var(--text-2); line-height: 1.6; }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">App</div>
    <h1>{{ brand('name') }} als app op je telefoon en computer</h1>
    <p class="lead">Je hoeft niets te downloaden uit een app store. {{ brand('name') }} is een webapp die je in een paar tikken op je beginscherm zet. Daarna opent hij schermvullend als een app, met eigen icoon en een snelkoppeling naar een nieuwe factuur.</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="section-header" style="margin-bottom:32px;">
      <h2>Zo installeer je de app</h2>
      <p>Open <b>{{ brand('domain') }}</b> in de browser van je telefoon of computer en log in. Kies daarna de stappen voor jouw apparaat.</p>
    </div>
    <div class="pwa-steps">
      <div class="pwa-card">
        <div class="ic">@include('partials.icon', ['name' => 'phone', 'size' => 24])</div>
        <h3>iPhone en iPad</h3>
        <div class="sub">Safari</div>
        <ol>
          <li>Open {{ brand('domain') }} in Safari en log in.</li>
          <li>Tik onderaan op <b>Delen</b> (het vierkantje met de pijl omhoog).</li>
          <li>Kies <b>Zet op beginscherm</b> en tik op <b>Voeg toe</b>.</li>
        </ol>
      </div>
      <div class="pwa-card">
        <div class="ic">@include('partials.icon', ['name' => 'phone', 'size' => 24])</div>
        <h3>Android</h3>
        <div class="sub">Chrome, Samsung Internet of Edge</div>
        <ol>
          <li>Open {{ brand('domain') }} in Chrome en log in. Vaak verschijnt dan al een balk <b>{{ brand('name') }} installeren</b>.</li>
          <li>Anders: tik rechtsboven op het menu (drie puntjes).</li>
          <li>Kies <b>App installeren</b> of <b>Toevoegen aan startscherm</b> en bevestig.</li>
        </ol>
      </div>
      <div class="pwa-card">
        <div class="ic">@include('partials.icon', ['name' => 'laptop', 'size' => 24])</div>
        <h3>Windows en Mac</h3>
        <div class="sub">Chrome of Edge; Safari op de Mac</div>
        <ol>
          <li>Open {{ brand('domain') }} en log in.</li>
          <li>Klik in de adresbalk op het <b>installatie-icoon</b> (een schermpje met een pijl), of kies in het menu <b>{{ brand('name') }} installeren</b> (Edge: Apps → Deze site installeren als app).</li>
          <li>Bevestig. De app staat daarna in je startmenu of in de Dock. In Safari op de Mac: <b>Archief → Voeg toe aan Dock</b>.</li>
        </ol>
      </div>
    </div>
    <div class="pwa-note">
      <b>Zie je de installatieknop niet?</b> Open de site dan in Chrome, Edge of Safari zelf, niet in de ingebouwde browser van een andere app (zoals Facebook of een mailprogramma). Klopt het icoon of de naam na een update niet meer, verwijder de app dan van je beginscherm en installeer hem opnieuw.
    </div>
  </div>
</section>

<section class="section section-alt" style="padding-top:64px;padding-bottom:64px;">
  <div class="container">
    <div class="section-header" style="margin-bottom:32px;"><h2>Wat je krijgt</h2><p>Dezelfde {{ brand('name') }} als in de browser, maar dan als app op je toestel.</p></div>
    <div class="pwa-perks">
      <div class="pwa-perk"><span class="ic">@include('partials.icon', ['name' => 'home', 'size' => 18])</span><div><b>Eigen icoon, schermvullend</b>Opent zonder adresbalk, direct op je dashboard.</div></div>
      <div class="pwa-perk"><span class="ic">@include('partials.icon', ['name' => 'invoice', 'size' => 18])</span><div><b>Snelkoppeling "Nieuwe factuur"</b>Houd het icoon ingedrukt en je zit meteen in een nieuwe factuur.</div></div>
      <div class="pwa-perk"><span class="ic">@include('partials.icon', ['name' => 'download', 'size' => 18])</span><div><b>Altijd de nieuwste versie</b>Geen updates installeren: elke verbetering staat er vanzelf in.</div></div>
      <div class="pwa-perk"><span class="ic">@include('partials.icon', ['name' => 'layers', 'size' => 18])</span><div><b>Op elk apparaat hetzelfde</b>Telefoon, tablet en computer: één account, dezelfde gegevens.</div></div>
      <div class="pwa-perk"><span class="ic">@include('partials.icon', ['name' => 'shield', 'size' => 18])</span><div><b>Even veilig als in de browser</b>Zelfde inlog, zelfde beveiliging, gegevens in de EU.</div></div>
      <div class="pwa-perk"><span class="ic">@include('partials.icon', ['name' => 'clock', 'size' => 18])</span><div><b>Geen verbinding? Een nette melding</b>Je hebt internet nodig om te werken; zonder verbinding zie je een korte melding in plaats van een foutpagina.</div></div>
    </div>
  </div>
</section>

<section class="section" style="padding-top:64px;">
  <div class="container">
    <div class="section-header"><h2>Vragen over de app</h2></div>
    <div class="faq-list">
      <details class="faq-item">
        <summary>Waarom staat {{ brand('name') }} niet in de App Store of Play Store?<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Een webapp werkt op elk toestel, is altijd actueel en kost je geen extra download. Je installeert hem rechtstreeks vanuit de browser, zonder tussenkomst van een app store.</div>
      </details>
      <details class="faq-item">
        <summary>Werkt de app ook zonder internet?<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Nee. Facturen maken, versturen en bekijken vraagt een verbinding, net als in de browser. Zonder verbinding zie je een korte melding; zodra je weer online bent, werkt alles gewoon verder.</div>
      </details>
      <details class="faq-item">
        <summary>Kost de app extra?<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Nee. De app hoort bij je abonnement en bij de gratis proefperiode van 14 dagen.</div>
      </details>
      <details class="faq-item">
        <summary>Ik werk al in de browser. Moet ik iets doen?<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Nee, dat hoeft niet. De app is handig op je telefoon of als je {{ brand('name') }} de hele dag open hebt staan; in de browser werkt alles precies hetzelfde.</div>
      </details>
    </div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Nog geen account?</h2>
    <p>Probeer {{ brand('name') }} 14 dagen gratis en zet hem daarna in een paar tikken op je telefoon.</p>
    <a href="{{ route('register') }}" class="btn btn-white btn-lg">Start 14 dagen gratis</a>
  </div>
</section>
@endsection
