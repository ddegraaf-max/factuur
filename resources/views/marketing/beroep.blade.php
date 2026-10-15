@extends('layouts.marketing')

@section('title', \App\Support\Seo::title($profession['title'] . ' — ' . brand('name'), $profession['title']))
@section('description', \App\Support\Seo::description($profession['intro']))

@push('styles')
<style>
  .pro-blocks { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 8px 0 40px; }
  @media (max-width: 800px) { .pro-blocks { grid-template-columns: 1fr; } }
  .pro-block { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 24px; }
  .pro-block h2 { font-size: 18px; margin: 0 0 10px; }
  .pro-block p { color: var(--text-2); font-size: 15px; line-height: 1.65; margin: 0; }
  .pro-faq details { border-top: 1px solid var(--border); padding: 14px 0; }
  .pro-faq details:last-child { border-bottom: 1px solid var(--border); }
  .pro-faq summary { font-weight: 600; cursor: pointer; font-size: 16px; }
  .pro-faq p { color: var(--text-2); margin: 10px 0 0; line-height: 1.65; }
  .pro-aside { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-top: 40px; }
  @media (max-width: 800px) { .pro-aside { grid-template-columns: 1fr; } }
  .pro-aside h3 { font-size: 13px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-4); margin: 0 0 10px; }
  .pro-aside a { display: block; font-size: 14.5px; color: var(--text-2); padding: 4px 0; }
  .pro-aside a:hover { color: var(--brand); }
  .pro-cta { background: var(--brand-tint); border: 1px solid var(--brand-border); border-radius: 14px; padding: 28px; margin-top: 40px; text-align: center; }
  .pro-cta h2 { font-size: 22px; margin: 0 0 8px; }
  .pro-cta p { color: var(--text-2); margin: 0 0 16px; }
</style>
@endpush

@section('content')
<section class="page-hero" style="padding-bottom:8px;">
  <div class="container">
    @include('marketing.partials.breadcrumbs', ['crumbs' => [['Factuurprogramma per beroep', route('beroepen')], [ucfirst($profession['name']), null]]])
    <div class="page-hero-inner">
      <h1>{{ $profession['title'] }}</h1>
      <p class="lead">{{ $profession['intro'] }}</p>
      <div style="margin-top:18px;">
        <a href="{{ route('register') }}" class="btn btn-primary">14 dagen gratis proberen →</a>
        <a href="{{ route('demo') }}" class="btn btn-ghost">Bekijk de demo</a>
      </div>
    </div>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container" style="max-width:1000px;">
    <div class="pro-blocks">
      @foreach ($profession['blocks'] as [$heading, $text])
        <div class="pro-block">
          <h2>{{ $heading }}</h2>
          <p>{{ $text }}</p>
        </div>
      @endforeach
    </div>

    <h2 style="font-size:24px;margin:0 0 16px;">Veelgestelde vragen van {{ $profession['name'] }}</h2>
    <div class="pro-faq">
      @foreach ($profession['faq'] as [$question, $answer])
        <details {{ $loop->first ? 'open' : '' }}>
          <summary>{{ $question }}</summary>
          <p>{{ $answer }}</p>
        </details>
      @endforeach
    </div>

    @php
      $related = collect($profession['related'] ?? [])->filter(fn ($s) => isset($articles[$s]));
      $begrippen = collect($profession['begrippen'] ?? [])->filter(fn ($s) => isset($terms[$s]));
      $tools = collect($profession['tools'] ?? [])->filter(fn ($r) => \Illuminate\Support\Facades\Route::has($r));
      $toolLabels = ['gratis-factuur' => 'Gratis factuur maken', 'btw-calculator' => 'Btw-calculator', 'uurtarief-calculator' => 'Uurtarief-calculator', 'incassokosten-calculator' => 'Incassokosten berekenen', 'aanmaning' => 'Gratis online aanmaning'];
    @endphp
    <div class="pro-aside">
      <div>
        <h3>Lees verder</h3>
        @foreach ($related as $s)
          <a href="{{ route('kennisbank.artikel', $s) }}">{{ $articles[$s]['title'] }}</a>
        @endforeach
        <a href="{{ route('voorbeelden') }}">Voorbeeldfacturen</a>
      </div>
      <div>
        <h3>Begrippen</h3>
        @foreach ($begrippen as $s)
          <a href="{{ route('begrippen.term', $s) }}">{{ $terms[$s]['term'] }}</a>
        @endforeach
      </div>
      <div>
        <h3>Gratis tools</h3>
        @foreach ($tools as $r)
          <a href="{{ route($r) }}">{{ $toolLabels[$r] ?? $r }}</a>
        @endforeach
        <a href="{{ route('beroepen') }}">Andere beroepen</a>
      </div>
    </div>

    <div class="pro-cta">
      <h2>Probeer {{ brand('name') }} voor {{ $profession['name'] }}</h2>
      <p>Veertien dagen gratis, zonder betaalgegevens. Daarna € 10 per maand excl. btw, maandelijks opzegbaar, onbeperkt facturen en klanten.</p>
      <a href="{{ route('register') }}" class="btn btn-primary">Account aanmaken →</a>
      <a href="{{ route('gratis-factuur') }}" class="btn btn-ghost">Eerst gratis een factuur maken</a>
    </div>
  </div>
</section>

<script type="application/ld+json">
{!! json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'FAQPage',
  'mainEntity' => collect($profession['faq'])->map(fn ($qa) => [
      '@type' => 'Question', 'name' => $qa[0],
      'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
  ])->values()->all(),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endsection
