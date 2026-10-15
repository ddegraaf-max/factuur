@extends('layouts.marketing')

@section('title', 'Voorbeeldfacturen: zzp, btw verlegd, KOR en uren — ' . brand('name'))
@section('description', 'Bekijk voorbeeldfacturen als pdf: een zzp-factuur, een factuur met btw verlegd, een factuur zonder btw (KOR) en een urenfactuur, met uitleg per onderdeel.')

@section('content')
<section class="page-hero" style="padding-bottom:8px;">
  <div class="container">
    @include('marketing.partials.breadcrumbs', ['crumbs' => [['Voorbeeldfacturen', null]]])
    <div class="page-hero-inner">
      <h1>Voorbeeldfacturen</h1>
      <p class="lead">Hoe ziet een goede factuur eruit? Vier voorbeelden als echte pdf, met uitleg van elk onderdeel: wat erop moet staan, hoe de btw werkt en wat de klant moet doen. Gemaakt met dezelfde generator als onze gratis factuur.</p>
    </div>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="card-grid">
      @foreach ($examples as $slug => $example)
        <a href="{{ route('voorbeelden.voorbeeld', $slug) }}" class="info-card" style="display:block;">
          <h2 style="font-size:18px;margin:0 0 8px;">{{ $example['title'] }}</h2>
          <p style="color:var(--text-2);font-size:14.5px;margin:0 0 12px;">{{ \Illuminate\Support\Str::limit($example['intro'], 160) }}</p>
          <span style="color:var(--brand);font-weight:600;font-size:14px;">Bekijk het voorbeeld →</span>
        </a>
      @endforeach
      <a href="{{ route('aanmaning.example') }}" class="info-card" style="display:block;">
        <h2 style="font-size:18px;margin:0 0 8px;">Voorbeeld aanmaning</h2>
        <p style="color:var(--text-2);font-size:14.5px;margin:0 0 12px;">Zo ziet een laatste aanmaning met wettelijke rente en incassokosten eruit, inclusief de mails die de klant ontvangt.</p>
        <span style="color:var(--brand);font-weight:600;font-size:14px;">Bekijk het voorbeeld →</span>
      </a>
    </div>

    <div style="margin-top:48px;padding:28px;background:var(--brand-tint);border:1px solid var(--brand-border);border-radius:14px;">
      <h2 style="font-size:20px;margin:0 0 8px;">Zelf een factuur maken</h2>
      <p style="color:var(--text-2);margin:0 0 14px;">Met de <a href="{{ route('gratis-factuur') }}" style="color:var(--brand);font-weight:600;">gratis factuurmaker</a> maak je zonder account een factuur als pdf, in dezelfde opmaak als deze voorbeelden. Wil je je facturen bewaren, nummeren en laten herinneren, <a href="{{ route('register') }}" style="color:var(--brand);font-weight:600;">probeer {{ brand('name') }} dan veertien dagen gratis</a>.</p>
    </div>
  </div>
</section>
@endsection
