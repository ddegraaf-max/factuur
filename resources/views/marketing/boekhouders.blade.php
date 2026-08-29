@extends('layouts.marketing')

@section('title', 'Voor boekhouders en accountants — gratis meekijken — ' . brand('name'))
@section('description', 'Werk je als boekhouder of accountant voor zzp\'ers en mkb? Kijk gratis mee in de administratie van je klanten in ' . brand('name') . ': btw-overzicht, jaaroverzicht en exports, met één inlog voor al je klanten.')

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <span class="eyebrow">Voor boekhouders &amp; accountants</span>
    <h1>Kijk gratis mee met je klanten</h1>
    <p class="lead">Klanten die in {{ brand('name') }} factureren, kunnen jou als boekhouder uitnodigen. Jij krijgt leestoegang tot hun administratie — zonder kosten, met één inlog voor al je klanten.</p>
    <div class="hero-ctas" style="margin-top:28px;">
      <a href="{{ route('contact') }}" class="btn btn-primary btn-lg">Neem contact op →</a>
      <a href="{{ route('demo') }}" class="btn btn-secondary btn-lg">Bekijk de demo</a>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <h2>Waarom boekhouders {{ brand('name') }} aanraden</h2>
      <p>Geen schoenendozen met bonnetjes meer, geen heen-en-weer gemailde Excelletjes: de administratie van je klant staat er netjes in — en jij kunt er altijd bij.</p>
    </div>
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></div>
        <div class="feature-title">Gratis leestoegang</div>
        <div class="feature-desc">Je klant nodigt je uit als boekhouder en jij kijkt mee: facturen, inkoop, btw en rapportages. Voor jou is dat gratis — je klant betaalt gewoon zijn eigen abonnement.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a7.5 7.5 0 0 1 13 0"/></svg></div>
        <div class="feature-title">Eén inlog, al je klanten</div>
        <div class="feature-desc">Elke klant die je uitnodigt komt onder dezelfde inlog te hangen. Wisselen tussen administraties is één klik — geen losse wachtwoorden per klant.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
        <div class="feature-title">Btw-overzicht per kwartaal</div>
        <div class="feature-desc">Verschuldigde btw en voorbelasting per tijdvak, aansluitend op de aangifte — als PDF te downloaden of direct in te zien.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div>
        <div class="feature-title">Jaaroverzicht voor de aangifte</div>
        <div class="feature-desc">Omzet, kosten en resultaat over het boekjaar in één document — inclusief kilometeradministratie en een toelichting op de grondslagen.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></div>
        <div class="feature-title">Exports voor je eigen pakket</div>
        <div class="feature-desc">Facturen en boekingen exporteer je zo naar je eigen boekhoudsoftware. Verkoopfacturen zijn er ook als UBL (e-factuur).</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <div class="feature-title">Veilig en read-only</div>
        <div class="feature-desc">Als boekhouder kijk je mee, maar de administratie blijft van je klant: jouw toegang is leesrechten — niets per ongeluk aan te passen of te versturen.</div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="prose">
      <h2>Zo werkt het</h2>
      <ol>
        <li><strong>Je klant nodigt je uit</strong> via Instellingen → Team, met de rol "boekhouder". Heb je al een {{ brand('name') }}-inlog, dan wordt de administratie daaraan gekoppeld.</li>
        <li><strong>Jij kijkt mee wanneer het jou uitkomt</strong> — voor de btw-aangifte pak je het kwartaaloverzicht erbij, voor de IB-aangifte het jaaroverzicht.</li>
        <li><strong>Meerdere klanten?</strong> Elke uitnodiging hangt onder dezelfde inlog. Linksonder wissel je met één klik van administratie.</li>
      </ol>
      <h2>Heb je klanten die nog met Word of Excel factureren?</h2>
      <p>Dan weet je hoe dat aan jouw kant binnenkomt. Wijs ze op {{ brand('name') }}: voor € 12,10 per maand (incl. btw) factureren ze netjes, en jij krijgt een administratie die klopt. Wil je {{ brand('name') }} eerst zelf bekijken? <a href="{{ route('demo') }}">Open de demo</a> — daar staat een complete voorbeeldadministratie in.</p>
      <p>Vragen, of eens sparren over meerdere klanten tegelijk? Mail <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a> — je krijgt antwoord van de makers zelf.</p>
    </div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Administraties die kloppen, klanten die blij zijn</h2>
    <p>Laat je klanten factureren in {{ brand('name') }} en kijk zelf gratis mee. Vragen? We denken graag met je mee.</p>
    <div class="hero-ctas">
      <a href="{{ route('contact') }}" class="btn btn-white btn-lg">Neem contact op →</a>
    </div>
  </div>
</section>
@endsection
