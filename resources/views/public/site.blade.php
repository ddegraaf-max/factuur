@extends('layouts.public-brand', ['madeWith' => 'website'])
@section('title', $content['seo']['title'] ?: ($content['hero']['title'] ?: $company->publicName()))
@section('description', $content['seo']['description'] ?: $content['hero']['subtitle'])
@section('styles')
  .wrap { max-width: 1040px; margin: 0 auto; padding: 0 20px; }
  .nav { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 0; }
  .brand { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 18px; }
  .brand img { height: 40px; max-width: 160px; object-fit: contain; }
  .brand .mono { width: 40px; height: 40px; border-radius: 10px; background: var(--brand); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; }
  .links { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
  .links a { padding: 8px 12px; border-radius: 8px; font-size: 14px; font-weight: 500; color: var(--text-2); }
  .links a:hover { background: #f5f5f4; color: var(--text); }
  .hero { padding: 64px 0 56px; display: grid; gap: 18px; max-width: 760px; }
  .hero h1 { font-size: clamp(30px, 5vw, 46px); line-height: 1.12; letter-spacing: -.02em; }
  .hero p { font-size: 18px; color: var(--text-2); }
  .hero .cta { display: flex; gap: 10px; flex-wrap: wrap; }
  section { padding: 44px 0; }
  h2 { font-size: 26px; letter-spacing: -.01em; margin-bottom: 18px; }
  .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; }
  .box { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 20px; }
  .box h3 { font-size: 17px; margin-bottom: 6px; }
  .box p { color: var(--text-2); font-size: 15px; }
  .usps { background: var(--brand); color: #fff; border-radius: 18px; padding: 28px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; }
  .usps h3 { font-size: 16px; margin-bottom: 4px; } .usps p { font-size: 14px; opacity: .9; }
  .about p { color: var(--text-2); font-size: 16px; max-width: 720px; white-space: pre-line; }
  .contact { display: grid; grid-template-columns: 1.2fr 1fr; gap: 24px; align-items: start; }
  @media (max-width: 760px) { .contact { grid-template-columns: 1fr; } }
  form label { display: block; font-size: 13px; font-weight: 600; margin: 12px 0 5px; }
  form input, form textarea { width: 100%; padding: 11px 12px; border: 1px solid var(--border); border-radius: 10px; font: inherit; font-size: 15px; background: #fff; }
  form textarea { min-height: 120px; resize: vertical; }
  .err { color: #b42318; font-size: 13px; margin-top: 4px; }
  .ok { background: #e8f7ee; color: #157347; padding: 12px 14px; border-radius: 10px; margin-bottom: 12px; font-weight: 600; }
  .hp { position: absolute; left: -9999px; }
  .details p { margin: 6px 0; color: var(--text-2); } .details b { color: var(--text); }
  footer { border-top: 1px solid var(--border); padding: 22px 0 6px; font-size: 13px; color: var(--text-2); display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
@endsection
@section('content')
<div class="wrap">
  <nav class="nav">
    <a href="#top" class="brand">
      @if($company->logo_data)<img src="{{ $company->logo_data }}" alt="{{ $company->publicName() }}">@else<span class="mono">{{ mb_strtoupper(mb_substr($company->publicName(), 0, 1)) }}</span><span>{{ $company->publicName() }}</span>@endif
    </a>
    <div class="links">
      @if($content['services'])<a href="#diensten">Diensten</a>@endif
      @if($content['about']['text'])<a href="#over">Over ons</a>@endif
      <a href="#contact" class="btn btn-brand" style="padding:9px 14px;">{{ $content['hero']['cta'] }}</a>
    </div>
  </nav>

  <header class="hero" id="top">
    <h1>{{ $content['hero']['title'] }}</h1>
    @if($content['hero']['subtitle'])<p>{{ $content['hero']['subtitle'] }}</p>@endif
    <div class="cta">
      <a class="btn btn-brand" href="#contact">{{ $content['hero']['cta'] }}</a>
      @if($company->phone)<a class="btn" href="tel:{{ preg_replace('/[^0-9+]/', '', $company->phone) }}">Bel {{ $company->phone }}</a>@endif
    </div>
  </header>

  @if($content['services'])
  <section id="diensten"><h2>Wat we doen</h2><div class="grid">@foreach($content['services'] as $s)<div class="box"><h3>{{ $s['title'] }}</h3><p>{{ $s['description'] }}</p></div>@endforeach</div></section>
  @endif

  @if($content['usps'])
  <section><div class="usps">@foreach($content['usps'] as $u)<div><h3>{{ $u['title'] }}</h3><p>{{ $u['text'] }}</p></div>@endforeach</div></section>
  @endif

  @if($content['about']['text'])
  <section id="over" class="about"><h2>{{ $content['about']['title'] }}</h2><p>{{ $content['about']['text'] }}</p></section>
  @endif

  <section id="contact">
    <h2>{{ $content['contact']['title'] }}</h2>
    <div class="contact">
      <div class="box">
        @if($content['contact']['text'])<p style="color:var(--text-2);margin-bottom:6px;">{{ $content['contact']['text'] }}</p>@endif
        @if(session('site_success'))<div class="ok">{{ session('site_success') }}</div>@endif
        <form method="post" action="{{ route('site.lead', $company->public_slug) }}">
          @csrf
          <input class="hp" type="text" name="website_url" tabindex="-1" autocomplete="off">
          <label for="name">Naam</label><input id="name" name="name" value="{{ old('name') }}" required>
          @error('name')<div class="err">{{ $message }}</div>@enderror
          <label for="email">E-mail</label><input id="email" name="email" type="email" value="{{ old('email') }}" required>
          @error('email')<div class="err">{{ $message }}</div>@enderror
          <label for="phone">Telefoon (optioneel)</label><input id="phone" name="phone" value="{{ old('phone') }}">
          <label for="message">Waar kunnen we je mee helpen?</label><textarea id="message" name="message" required>{{ old('message') }}</textarea>
          @error('message')<div class="err">{{ $message }}</div>@enderror
          <button class="btn btn-brand" type="submit" style="margin-top:14px;">Verstuur bericht</button>
        </form>
      </div>
      <div class="details">
        <h3 style="margin-bottom:8px;">{{ $company->publicName() }}</h3>
        @if($company->phone)<p>Telefoon: <b><a href="tel:{{ preg_replace('/[^0-9+]/', '', $company->phone) }}">{{ $company->phone }}</a></b></p>@endif
        @if($company->email)<p>E-mail: <b><a href="mailto:{{ $company->email }}">{{ $company->email }}</a></b></p>@endif
        @if($company->full_address)<p>Adres: <b>{{ $company->full_address }}</b></p>@endif
        @if($company->kvk_number)<p>KvK: <b>{{ $company->kvk_number }}</b></p>@endif
        @if($card_url)<p><a class="btn" href="{{ $card_url }}" style="margin-top:8px;">Digitaal visitekaartje</a></p>@endif
      </div>
    </div>
  </section>

  <footer><span>© {{ date('Y') }} {{ $company->publicName() }}</span>@if($card_url)<a href="{{ $card_url }}">Visitekaartje</a>@endif</footer>
</div>
@endsection
