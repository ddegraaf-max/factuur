@extends('layouts.marketing')

@section('title', 'Factuurprogramma per beroep: van zzp tot bouw — ' . brand('name'))
@section('description', 'Per beroep uitgelegd wat er bij factureren komt kijken: btw-tarieven, btw verlegd, termijnen, uren en abonnementen. Voor zzp, bouw, creatief en advies.')

@section('content')
<section class="page-hero" style="padding-bottom:8px;">
  <div class="container">
    @include('marketing.partials.breadcrumbs', ['crumbs' => [['Factuurprogramma per beroep', null]]])
    <div class="page-hero-inner">
      <h1>Factureren in jouw vak</h1>
      <p class="lead">Een schilder factureert anders dan een consultant: andere btw-tarieven, andere afspraken, andere klanten. Per beroep leggen we uit waar je op let en hoe {{ brand('name') }} dat voor je regelt.</p>
    </div>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="card-grid">
      @foreach ($professions as $slug => $p)
        <a href="{{ route('beroepen.beroep', $slug) }}" class="info-card" style="display:block;">
          <h2 style="font-size:18px;margin:0 0 8px;">{{ $p['title'] }}</h2>
          <p style="color:var(--text-2);font-size:14.5px;margin:0;">{{ \Illuminate\Support\Str::limit($p['intro'], 150) }}</p>
        </a>
      @endforeach
      <a href="{{ route('bouw') }}" class="info-card" style="display:block;">
        <h2 style="font-size:18px;margin:0 0 8px;">Factuurprogramma voor de bouw</h2>
        <p style="color:var(--text-2);font-size:14.5px;margin:0;">Btw verlegd, G-rekening, termijnen en prijsaanvragen bij onderaannemers: alles voor aannemers en bouwbedrijven op één pagina.</p>
      </a>
    </div>

    <div style="margin-top:48px;padding:28px;background:var(--brand-tint);border:1px solid var(--brand-border);border-radius:14px;">
      <h2 style="font-size:20px;margin:0 0 8px;">Staat jouw beroep er niet bij?</h2>
      <p style="color:var(--text-2);margin:0 0 14px;">{{ brand('name') }} werkt voor elke ondernemer die factureert. Begin met de <a href="{{ route('beroepen.beroep', 'zzp') }}" style="color:var(--brand);font-weight:600;">pagina voor zzp'ers</a>, bekijk de <a href="{{ route('voorbeelden') }}" style="color:var(--brand);font-weight:600;">voorbeeldfacturen</a> of <a href="{{ route('register') }}" style="color:var(--brand);font-weight:600;">probeer het veertien dagen gratis</a>.</p>
    </div>
  </div>
</section>
@endsection
