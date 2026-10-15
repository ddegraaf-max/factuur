@extends('layouts.marketing')

@section('title', 'Begrippenlijst: factureren, btw en betaald krijgen — ' . brand('name'))
@section('description', 'Korte, heldere uitleg van ' . count($terms) . ' begrippen rond factureren, btw en betaald krijgen: van btw verlegd en KOR tot creditnota, Peppol, G-rekening en wettelijke rente.')

@section('content')
<section class="page-hero" style="padding-bottom:8px;">
  <div class="container">
    @include('marketing.partials.breadcrumbs', ['crumbs' => [['Begrippenlijst', null]]])
    <div class="page-hero-inner">
      <h1>Begrippenlijst</h1>
      <p class="lead">Wat betekent btw verlegd, wanneer stuur je een creditnota en wat is een G-rekening? {{ count($terms) }} begrippen uit de praktijk van factureren, btw en betaald krijgen, in gewoon Nederlands.</p>
    </div>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    @php
      $letters = collect($terms)->groupBy(fn ($t) => mb_strtoupper(mb_substr($t['term'], 0, 1)))->sortKeys();
    @endphp
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:28px;">
      @foreach ($letters as $letter => $items)
        <a href="#letter-{{ $letter }}" class="btn btn-ghost" style="padding:6px 12px;">{{ $letter }}</a>
      @endforeach
    </div>
    @foreach ($letters as $letter => $items)
      <h2 id="letter-{{ $letter }}" style="font-size:22px;margin:34px 0 16px;">{{ $letter }}</h2>
      <div class="card-grid">
        @foreach ($items as $slug => $term)
          <a href="{{ route('begrippen.term', $slug) }}" class="info-card" style="display:block;">
            <h3 style="font-size:17px;margin:0 0 8px;">{{ $term['term'] }}</h3>
            <p style="color:var(--text-2);font-size:14.5px;margin:0;">{{ \Illuminate\Support\Str::limit($term['short'], 140) }}</p>
          </a>
        @endforeach
      </div>
    @endforeach

    <div style="margin-top:48px;padding:28px;background:var(--brand-tint);border:1px solid var(--brand-border);border-radius:14px;">
      <h2 style="font-size:20px;margin:0 0 8px;">Meer lezen</h2>
      <p style="color:var(--text-2);margin:0 0 14px;">Uitgebreidere artikelen staan in de <a href="{{ route('kennisbank') }}" style="color:var(--brand);font-weight:600;">kennisbank</a>. Bekijk ook de <a href="{{ route('voorbeelden') }}" style="color:var(--brand);font-weight:600;">voorbeeldfacturen</a> of maak direct <a href="{{ route('gratis-factuur') }}" style="color:var(--brand);font-weight:600;">gratis een factuur</a>.</p>
    </div>
  </div>
</section>

<script type="application/ld+json">
{!! json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'DefinedTermSet',
  'name' => 'Begrippenlijst factureren, btw en betaald krijgen',
  'url' => route('begrippen'),
  'inLanguage' => 'nl',
  'hasDefinedTerm' => collect($terms)->map(fn ($t, $slug) => [
      '@type' => 'DefinedTerm', 'name' => $t['term'], 'description' => $t['short'], 'url' => route('begrippen.term', $slug),
  ])->values()->all(),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endsection
