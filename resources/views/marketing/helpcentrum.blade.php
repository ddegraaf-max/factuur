@extends('layouts.marketing')

@section('title', 'Helpcentrum — EasyInvoice')
@section('description', 'Vind snel antwoord op je vraag in het EasyInvoice helpcentrum.')

@push('styles')
<style>
  .help-search { display: flex; align-items: center; gap: 10px; max-width: 520px; margin: 24px auto 0; background: var(--surface); border: 1px solid var(--border-strong); border-radius: 12px; padding: 0 16px; box-shadow: var(--shadow-sm); }
  .help-search input { flex: 1; border: none; outline: none; background: none; padding: 14px 0; font-size: 15px; font-family: inherit; color: var(--text); }
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
        <h3>Aan de slag</h3>
        <p style="margin-bottom:12px;">Account aanmaken en je eerste factuur versturen.</p>
        <a class="help-link" href="{{ route('help.article', 'een-account-aanmaken') }}">Een account aanmaken →</a>
        <a class="help-link" href="{{ route('help.article', 'bedrijfsgegevens-instellen') }}">Je bedrijfsgegevens instellen →</a>
        <a class="help-link" href="{{ route('help.article', 'eerste-factuur-maken') }}">Je eerste factuur maken →</a>
      </div>
      <div class="info-card">
        <div class="ic-emoji">🧾</div>
        <h3>Facturen</h3>
        <p style="margin-bottom:12px;">BTW, creditnota's en nummering.</p>
        <a class="help-link" href="{{ route('help.article', 'btw-per-regel') }}">BTW per regel instellen →</a>
        <a class="help-link" href="{{ route('help.article', 'creditfactuur-maken') }}">Een creditfactuur maken →</a>
        <a class="help-link" href="{{ route('help.article', 'factuurnummering') }}">Factuurnummering aanpassen →</a>
      </div>
      <div class="info-card">
        <div class="ic-emoji">💸</div>
        <h3>Betalingen &amp; incasso</h3>
        <p style="margin-bottom:12px;">Herinneringen en het incassotraject.</p>
        <a class="help-link" href="{{ route('help.article', 'betaling-registreren') }}">Een betaling registreren →</a>
        <a class="help-link" href="{{ route('help.article', 'automatische-herinneringen') }}">Automatische herinneringen →</a>
        <a class="help-link" href="{{ route('help.article', 'incassotraject') }}">Het incassotraject →</a>
      </div>
      <div class="info-card">
        <div class="ic-emoji">👥</div>
        <h3>Klanten &amp; producten</h3>
        <p style="margin-bottom:12px;">Klantgegevens en je catalogus.</p>
        <a class="help-link" href="{{ route('help.article', 'klant-toevoegen') }}">Een klant toevoegen →</a>
        <a class="help-link" href="{{ route('help.article', 'producten-beheren') }}">Producten beheren →</a>
      </div>
      <div class="info-card">
        <div class="ic-emoji">🎨</div>
        <h3>Huisstijl</h3>
        <p style="margin-bottom:12px;">Logo, kleuren en sjablonen.</p>
        <a class="help-link" href="{{ route('help.article', 'logo-uploaden') }}">Je logo uploaden →</a>
        <a class="help-link" href="{{ route('help.article', 'sjabloon-kiezen') }}">Een sjabloon kiezen →</a>
      </div>
      <div class="info-card">
        <div class="ic-emoji">🔒</div>
        <h3>Account &amp; beveiliging</h3>
        <p style="margin-bottom:12px;">Wachtwoord en tweestapsverificatie.</p>
        <a class="help-link" href="{{ route('help.article', '2fa-instellen') }}">2FA instellen →</a>
        <a class="help-link" href="{{ route('help.article', 'wachtwoord-wijzigen') }}">Je wachtwoord wijzigen →</a>
      </div>
    </div>

    <div class="info-card" style="text-align:center;margin-top:32px;padding:36px;">
      <h3 style="font-size:22px;margin-bottom:8px;">Niet gevonden wat je zocht?</h3>
      <p style="margin-bottom:20px;">Ons team helpt je graag persoonlijk verder.</p>
      <a href="{{ route('contact') }}" class="btn btn-primary" style="margin-right:8px;">Neem contact op</a>
      <a href="mailto:hallo@easyinvoice.nl" class="btn btn-secondary">E-mailondersteuning</a>
    </div>
  </div>
</section>
@endsection
