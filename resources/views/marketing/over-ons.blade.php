@extends('layouts.marketing')

@section('title', 'Over ons — EasyInvoice')
@section('description', 'Het verhaal achter EasyInvoice: facturatie terug naar de basis, voor elke Nederlandse ondernemer.')

@push('styles')
<style>
  .team-avatar { width: 64px; height: 64px; border-radius: 50%; margin: 0 auto 14px; background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%); color: #fff; font-weight: 700; font-size: 20px; font-family: var(--font-display); display: flex; align-items: center; justify-content: center; }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Over ons</div>
    <h1>Facturatie, terug naar de basis</h1>
    <p class="lead">EasyInvoice is ontstaan uit frustratie: bestaande boekhoudsoftware is duur, log en overweldigend voor een gewone ondernemer. Dat kon beter.</p>
  </div>
</section>

<section class="section" style="padding-top:48px;">
  <div class="container">
    <div class="prose">
      <h2>Onze missie</h2>
      <p>We willen dat elke Nederlandse ondernemer met een gerust hart kan factureren en sneller betaald krijgt — zonder een boekhouddiploma of een duur abonnement.</p>
      <p>Daarom bouwen we de eenvoudigste facturatietool van Nederland. Eén heldere prijs, alle functies die je écht nodig hebt, en ondersteuning van echte mensen in Amsterdam.</p>
    </div>
  </div>
</section>

<section class="section section-alt" style="padding-top:64px;padding-bottom:64px;">
  <div class="container">
    <div class="section-header" style="margin-bottom:40px;"><h2>Waar we voor staan</h2></div>
    <div class="card-grid cols-2" style="max-width:900px;margin:0 auto;">
      <div class="info-card"><h3>Eenvoud voorop</h3><p>Facturatie hoeft niet ingewikkeld te zijn. We laten alles weg wat niet helpt.</p></div>
      <div class="info-card"><h3>Eerlijke prijs</h3><p>Eén lage prijs voor iedereen. Geen verborgen kosten, geen verkooppraatjes.</p></div>
      <div class="info-card"><h3>Voor het MKB</h3><p>Gebouwd voor ZZP'ers en kleine ondernemers, niet voor accountants.</p></div>
      <div class="info-card"><h3>Privacy first</h3><p>Jouw data is van jou en blijft veilig binnen de EU.</p></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header" style="margin-bottom:40px;"><h2>Het team</h2></div>
    <div class="card-grid" style="max-width:840px;margin:0 auto;">
      <div class="info-card" style="text-align:center;"><div class="team-avatar">DG</div><h3>Daan de Graaf</h3><p>Oprichter &amp; product</p></div>
      <div class="info-card" style="text-align:center;"><div class="team-avatar">EM</div><h3>Eva Mulder</h3><p>Engineering</p></div>
      <div class="info-card" style="text-align:center;"><div class="team-avatar">JV</div><h3>Joris Visser</h3><p>Support &amp; klantgeluk</p></div>
    </div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Werk met ons mee</h2>
    <p>We zijn altijd op zoek naar mensen die het MKB vooruit willen helpen.</p>
    <a href="{{ route('vacatures') }}" class="btn btn-white btn-lg">Bekijk vacatures</a>
  </div>
</section>
@endsection
