@extends('layouts.marketing')

@section('title', 'Helpcentrum ' . brand('name') . ' — antwoorden en handleidingen')
@section('description', 'Vind snel antwoord op je vraag: facturen maken, BTW, herinneringen, incasso, koppelingen en meer. Kom je er niet uit? We helpen je persoonlijk verder.')

@push('styles')
<style>
  .help-search { display: flex; align-items: center; gap: 10px; max-width: 520px; margin: 24px auto 0; background: var(--surface); border: 1px solid var(--border-strong); border-radius: 12px; padding: 0 16px; box-shadow: var(--shadow-sm); }
  .help-search input { flex: 1; border: none; outline: none; background: none; padding: 14px 0; font-size: 15px; font-family: inherit; color: var(--text); }
  .help-cat-title { font-size: 18px; margin-bottom: 6px; }
  .help-link { display: block; font-size: 14px; color: var(--text-2); padding: 4px 0; }
  .help-link:hover { color: var(--brand); }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Helpcentrum</div>
    <h1>Hoe kunnen we helpen?</h1>
    <p class="lead">Vind snel antwoord op je vraag, of neem contact op met ons team.</p>
    <div class="help-search">
      <span>🔍</span>
      <input type="text" placeholder="Zoek in het helpcentrum…" onkeydown="if(event.key==='Enter'){window.location='{{ route('faq') }}'}">
    </div>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="card-grid">
      <div class="info-card">
        <div class="ic-emoji">🚀</div>
        <h2 class="help-cat-title">Aan de slag</h2>
        <p style="margin-bottom:12px;">Account aanmaken en je eerste factuur versturen.</p>
        <a class="help-link" href="{{ route('help.article', 'een-account-aanmaken') }}">Een account aanmaken →</a>
        <a class="help-link" href="{{ route('help.article', 'bedrijfsgegevens-instellen') }}">Je bedrijfsgegevens instellen →</a>
        <a class="help-link" href="{{ route('help.article', 'eerste-factuur-maken') }}">Je eerste factuur maken →</a>
      </div>
      <div class="info-card">
        <div class="ic-emoji">🧾</div>
        <h2 class="help-cat-title">Facturen</h2>
        <p style="margin-bottom:12px;">BTW, creditnota's en nummering.</p>
        <a class="help-link" href="{{ route('help.article', 'urenregistratie') }}">Uren schrijven en factureren →</a>
        <a class="help-link" href="{{ route('help.article', 'strippenkaarten') }}">Strippenkaarten (vooraf betaalde uren) →</a>
        <a class="help-link" href="{{ route('help.article', 'kilometerregistratie') }}">Ritten bijhouden en doorbelasten →</a>
        <a class="help-link" href="{{ route('help.article', 'btw-per-regel') }}">BTW per regel instellen →</a>
        <a class="help-link" href="{{ route('help.article', 'creditfactuur-maken') }}">Een creditfactuur maken →</a>
        <a class="help-link" href="{{ route('help.article', 'factuurnummering') }}">Factuurnummering aanpassen →</a>
        <a class="help-link" href="{{ route('help.article', 'facturen-in-het-engels') }}">Facturen in het Engels →</a>
        <a class="help-link" href="{{ route('help.article', 'peppol-e-facturen') }}">E-facturen via Peppol →</a>
        <a class="help-link" href="{{ route('help.article', 'termijnfacturen') }}">Termijnfacturen (in delen factureren) →</a>
        <a class="help-link" href="{{ route('help.article', 'korting-geven') }}">Korting geven op factuurregels →</a>
      </div>
      <div class="info-card">
        <div class="ic-emoji">💸</div>
        <h2 class="help-cat-title">Betalingen &amp; incasso</h2>
        <p style="margin-bottom:12px;">Herinneringen en het incassotraject.</p>
        <a class="help-link" href="{{ route('help.article', 'betaallink-ideal') }}">Betaald worden met iDEAL →</a>
        <a class="help-link" href="{{ route('help.article', 'betaling-registreren') }}">Een betaling registreren →</a>
        <a class="help-link" href="{{ route('help.article', 'bedankmail-na-betaling') }}">Bedankmail na betaling (met reviewlink) →</a>
        <a class="help-link" href="{{ route('help.article', 'automatische-herinneringen') }}">Automatische herinneringen →</a>
        <a class="help-link" href="{{ route('help.article', 'incassotraject') }}">Het incassotraject →</a>
      </div>
      <div class="info-card">
        <div class="ic-emoji">👥</div>
        <h2 class="help-cat-title">Klanten &amp; producten</h2>
        <p style="margin-bottom:12px;">Klantgegevens en je catalogus.</p>
        <a class="help-link" href="{{ route('help.article', 'klant-toevoegen') }}">Een klant toevoegen →</a>
        <a class="help-link" href="{{ route('help.article', 'producten-beheren') }}">Producten beheren →</a>
      </div>
      <div class="info-card">
        <div class="ic-emoji">🎨</div>
        <h2 class="help-cat-title">Huisstijl</h2>
        <p style="margin-bottom:12px;">Logo, kleuren en sjablonen.</p>
        <a class="help-link" href="{{ route('help.article', 'logo-uploaden') }}">Je logo uploaden →</a>
        <a class="help-link" href="{{ route('help.article', 'sjabloon-kiezen') }}">Een sjabloon kiezen →</a>
        <a class="help-link" href="{{ route('help.article', 'meerdere-handelsnamen') }}">Meerdere handelsnamen →</a>
        <a class="help-link" href="{{ route('help.article', 'e-mailteksten-aanpassen') }}">De factuur- en offertemail aanpassen →</a>
      </div>
      <div class="info-card">
        <div class="ic-emoji">📊</div>
        <h2 class="help-cat-title">Inkoop &amp; BTW</h2>
        <p style="margin-bottom:12px;">Inkoop inboeken en je BTW-aangifte per kwartaal.</p>
        <a class="help-link" href="{{ route('help.article', 'inkoopfacturen-inboeken') }}">Inkoopfacturen inboeken (ook met foto) →</a>
        <a class="help-link" href="{{ route('help.article', 'postvak-in') }}">Inkoopfacturen per e-mail aanleveren →</a>
        <a class="help-link" href="{{ route('help.article', 'vaste-lasten') }}">Vaste lasten automatisch inboeken →</a>
        <a class="help-link" href="{{ route('help.article', 'btw-aangifte-per-kwartaal') }}">Je btw-aangifte: aangifte-klaar (rubrieken, betalingskenmerk) →</a>
        <a class="help-link" href="{{ route('help.article', 'jaaroverzicht') }}">Het jaaroverzicht (omzet, kosten, resultaat) →</a>
        <a class="help-link" href="{{ route('help.article', 'cashflow-prognose') }}">De cashflow-prognose →</a>
        <a class="help-link" href="{{ route('help.article', 'ouderdomsanalyse-debiteuren') }}">Debiteuren: wie staat er hoe lang open? →</a>
      </div>
      <div class="info-card">
        <div class="ic-emoji">🤝</div>
        <h2 class="help-cat-title">Samenwerken &amp; klantenportaal</h2>
        <p style="margin-bottom:12px;">Teamrollen en facturen online laten inzien.</p>
        <a class="help-link" href="{{ route('help.article', 'offertes-digitaal-ondertekenen') }}">Offertes digitaal laten ondertekenen →</a>
        <a class="help-link" href="{{ route('help.article', 'team-en-rollen') }}">Collega's en boekhouder uitnodigen →</a>
        <a class="help-link" href="{{ route('help.article', 'klantenportaal-inzagelog') }}">Het klantenportaal en inzagelog →</a>
      </div>
      <div class="info-card">
        <div class="ic-emoji">🔒</div>
        <h2 class="help-cat-title">Account &amp; beveiliging</h2>
        <p style="margin-bottom:12px;">Wachtwoord en tweestapsverificatie.</p>
        <a class="help-link" href="{{ route('help.article', 'meerdere-administraties') }}">Meerdere administraties →</a>
        <a class="help-link" href="{{ route('help.article', '2fa-instellen') }}">2FA instellen →</a>
        <a class="help-link" href="{{ route('help.article', 'wachtwoord-wijzigen') }}">Je wachtwoord wijzigen →</a>
      </div>
    </div>

    <div class="info-card" style="text-align:center;margin-top:32px;padding:36px;">
      <h2 style="font-size:22px;margin-bottom:8px;">Niet gevonden wat je zocht?</h2>
      <p style="margin-bottom:20px;">Ons team helpt je graag persoonlijk verder.</p>
      <a href="{{ route('contact') }}" class="btn btn-primary" style="margin-right:8px;">Neem contact op</a>
      <a href="mailto:{{ brand('email') }}" class="btn btn-secondary">E-mailondersteuning</a>
    </div>
  </div>
</section>
@endsection
