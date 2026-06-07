@extends('layouts.marketing')

@section('title', 'Over ons — EasyInvoice')
@section('description', 'Het verhaal achter EasyInvoice: facturatie terug naar de basis, voor elke Nederlandse ondernemer.')

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

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Klaar om te beginnen?</h2>
    <p>Probeer EasyInvoice 14 dagen gratis en ervaar zelf hoe eenvoudig factureren kan zijn.</p>
    <a href="{{ route('register') }}" class="btn btn-white btn-lg">Start 14 dagen gratis</a>
  </div>
</section>
@endsection
