@extends('layouts.marketing')

@section('title', 'Demo — bekijk EasyInvoice van binnen')
@section('description', 'Klik door de échte EasyInvoice met voorbeeldgegevens. Geen account, geen creditcard — je bent er in één klik.')

@push('styles')
<style>
  .demo-wrap { max-width: 940px; margin: 0 auto; }
  .demo-start { text-align: center; padding: 6px 0 34px; }
  .demo-start .btn-xl { height: 54px; padding: 0 34px; font-size: 16px; font-weight: 600; }
  .demo-note { color: var(--text-3); font-size: 13.5px; margin-top: 14px; }
  .demo-feats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 8px; }
  @media (max-width: 860px) { .demo-feats { grid-template-columns: 1fr; } }
  .demo-feat { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 20px; }
  .demo-feat h2 { font-size: 15px; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
  .demo-feat p { color: var(--text-2); font-size: 13.5px; margin: 0; line-height: 1.6; }
  .demo-feat svg { width: 18px; height: 18px; color: var(--brand); flex: none; }
  .demo-tag { display: inline-block; font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #fff; background: var(--brand); border-radius: 100px; padding: 2px 7px; margin-left: 6px; vertical-align: 1px; }
  .demo-safe { background: var(--surface-2); border: 1px solid var(--border); border-radius: 12px; padding: 18px 22px; margin-top: 26px; font-size: 13.5px; color: var(--text-2); line-height: 1.7; }
  .demo-safe b { color: var(--text); }
  .demo-safe ul { margin: 8px 0 0; padding-left: 20px; }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Demo</div>
    <h1>Kijk eerst rond, beslis daarna</h1>
    <p class="lead">
      Dit is geen filmpje en geen plaatje: je stapt in de <strong>echte EasyInvoice</strong>, gevuld met
      voorbeeldgegevens van een fictief webdesignbureau. Maak facturen, registreer betalingen,
      bekijk het incassodossier — precies zoals je het straks zelf gebruikt.
    </p>
  </div>
</section>

<section class="section" style="padding-top:24px;">
  <div class="container demo-wrap">

    <div class="demo-start">
      @if (session('error'))
        <div style="background:var(--brand-tint);border:1px solid var(--brand-border);color:var(--brand-darker);border-radius:10px;padding:12px 16px;margin-bottom:18px;font-size:14px;">
          {{ session('error') }}
        </div>
      @endif

      <form method="POST" action="{{ route('demo.start') }}">
        @csrf
        <button type="submit" class="btn btn-primary btn-xl">Start de demo →</button>
      </form>
      <div class="demo-note">Geen account nodig · geen creditcard · direct binnen</div>
    </div>

    <div class="demo-feats">
      <div class="demo-feat">
        <h2>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/></svg>
          Facturen
        </h2>
        <p>Twaalf facturen in alle statussen: concept, openstaand, deels betaald, vervallen, betaald — plus een creditnota. Maak er zelf een bij en bekijk de PDF.</p>
      </div>
      <div class="demo-feat">
        <h2>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
          Terugkerend<span class="demo-tag">Nieuw</span>
        </h2>
        <p>Twee lopende abonnementsprofielen die automatisch factureren. Zie precies wanneer de volgende factuur eruit gaat.</p>
      </div>
      <div class="demo-feat">
        <h2>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Export &amp; UBL<span class="demo-tag">Nieuw</span>
        </h2>
        <p>Download de boekhoudexport met BTW per tarief, of haal een factuur op als UBL 2.1 e-factuur (NLCIUS).</p>
      </div>
      <div class="demo-feat">
        <h2>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="m14.5 12.5-8 8a2.119 2.119 0 1 1-3-3l8-8"/><path d="m16 16 6-6"/><path d="m8 8 6-6"/><path d="m9 7 8 8"/><path d="m21 11-8-8"/></svg>
          Incasso
        </h2>
        <p>Een lopend incassodossier met het volledige verloop: herinnering, aanmaning en overdracht aan de deurwaarder.</p>
      </div>
      <div class="demo-feat">
        <h2>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="3" y1="20" x2="21" y2="20"/></svg>
          Dashboard &amp; rapporten
        </h2>
        <p>Openstaand bedrag, omzet per maand en je beste klanten — gevuld met een half jaar aan voorbeeldhistorie.</p>
      </div>
      <div class="demo-feat">
        <h2>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="13.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="10.5" r="2.5"/><circle cx="8.5" cy="7.5" r="2.5"/><circle cx="6.5" cy="12.5" r="2.5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>
          Huisstijl &amp; instellingen
        </h2>
        <p>Verander de accentkleur of het factuursjabloon en zie het meteen terug in de PDF. Alles mag — het is jouw sandbox.</p>
      </div>
    </div>

    <div class="demo-safe">
      <b>Veilig uitproberen.</b> De demo is een afgeschermde omgeving die alleen van jou is:
      <ul>
        <li>Er gaat <b>geen e-mail</b> naar echte ontvangers — ook niet als je een factuur “verstuurt”.</li>
        <li>Je hoeft niets in te vullen en er wordt <b>niet betaald</b>.</li>
        <li>Na 24 uur wordt de omgeving automatisch opgeruimd; je kunt hem ook zelf direct verlaten.</li>
      </ul>
    </div>

    <div style="text-align:center;margin-top:34px;">
      <p style="color:var(--text-2);margin-bottom:16px;">Overtuigd? Je eigen omgeving is in twee minuten klaar.</p>
      <a href="{{ route('register') }}" class="btn btn-secondary">Start 14 dagen gratis</a>
    </div>

  </div>
</section>
@endsection
