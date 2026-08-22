@extends('layouts.marketing')

@section('title', 'Kennisbank voor zzp\'ers en mkb — factureren, btw en betaald krijgen — EasyInvoice')
@section('description', 'Praktische uitleg over factureren, btw en betaald krijgen voor Nederlandse zzp\'ers en mkb: factuureisen, factuurnummers, btw-tarieven, de KOR en aanmanen.')

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <span class="eyebrow">Kennisbank</span>
    <h1>Factureren, btw en betaald krijgen</h1>
    <p class="lead">Praktische uitleg zonder jargon, geschreven voor Nederlandse zzp'ers en mkb. Gratis te lezen — of je nu klant bent of niet.</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    @php $grouped = collect(config('kennisbank.articles'))->groupBy('category', true); @endphp
    @foreach ($grouped as $category => $articles)
      <h2 style="font-size:22px;margin:34px 0 16px;">{{ $category }}</h2>
      <div class="card-grid">
        @foreach ($articles as $slug => $article)
          <a href="{{ route('kennisbank.artikel', $slug) }}" class="info-card" style="display:block;transition:transform 0.2s, box-shadow 0.2s;">
            <h3>{{ $article['title'] }}</h3>
            <p>{{ \Illuminate\Support\Str::limit($article['intro'], 120) }}</p>
            <p style="margin-top:12px;color:var(--brand);font-weight:600;font-size:14px;">Lees verder →</p>
          </a>
        @endforeach
      </div>
    @endforeach

    <div style="max-width:760px;margin:56px auto 0;background:var(--brand-tint);border:1px solid var(--brand-border);border-radius:16px;padding:28px;text-align:center;">
      <h3 style="font-size:20px;margin-bottom:8px;">Liever meteen aan de slag?</h3>
      <p style="color:var(--text-2);margin:0 0 18px;">Maak <a href="{{ route('gratis-factuur') }}" style="color:var(--brand);font-weight:600;">gratis een factuur</a> zonder account, of reken je <a href="{{ route('uurtarief-calculator') }}" style="color:var(--brand);font-weight:600;">uurtarief</a> en <a href="{{ route('btw-calculator') }}" style="color:var(--brand);font-weight:600;">btw</a> uit met onze gratis tools.</p>
    </div>
  </div>
</section>
@endsection
