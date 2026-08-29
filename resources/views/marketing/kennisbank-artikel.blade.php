@extends('layouts.marketing')

@section('title', $article['title'] . ' — Kennisbank — ' . brand('name'))
@section('description', $article['intro'])

@push('styles')
<style>
  .article-layout { display: grid; grid-template-columns: 240px 1fr; gap: 48px; align-items: start; max-width: 1000px; margin: 0 auto; }
  @media (max-width: 800px) { .article-layout { grid-template-columns: 1fr; gap: 32px; } }
  .article-side { position: sticky; top: 90px; }
  @media (max-width: 800px) { .article-side { position: static; } }
  .side-cat { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; color: var(--text-4); margin: 18px 0 8px; }
  .side-cat:first-child { margin-top: 0; }
  .side-link { display: block; font-size: 14px; color: var(--text-2); padding: 5px 0; line-height: 1.4; }
  .side-link:hover { color: var(--brand); }
  .side-link.active { color: var(--brand); font-weight: 600; }
  .article-cta { background: var(--brand-tint); border: 1px solid var(--brand-border); border-radius: 14px; padding: 24px; margin-top: 40px; }
  .article-cta h3 { font-size: 18px; margin-bottom: 6px; }
  .article-cta p { color: var(--text-2); font-size: 14.5px; margin: 0 0 14px; }
</style>
@endpush

@section('content')
<section class="page-hero" style="padding-bottom:8px;">
  <div class="container">
    <div class="breadcrumb" style="text-align:center;">
      <a href="{{ route('kennisbank') }}">Kennisbank</a> &nbsp;›&nbsp; {{ $article['category'] }}
    </div>
    <div class="page-hero-inner">
      <h1>{{ $article['title'] }}</h1>
      <p class="lead">{{ $article['intro'] }}</p>
    </div>
  </div>
</section>

<section class="section" style="padding-top:48px;">
  <div class="container article-layout">
    <aside class="article-side">
      @php $grouped = collect($articles)->groupBy('category', true); @endphp
      @foreach ($grouped as $cat => $items)
        <div class="side-cat">{{ $cat }}</div>
        @foreach ($items as $key => $a)
          <a class="side-link {{ $key === $slug ? 'active' : '' }}" href="{{ route('kennisbank.artikel', $key) }}">{{ $a['title'] }}</a>
        @endforeach
      @endforeach
      <div class="side-cat">Gratis tools</div>
      <a class="side-link" href="{{ route('gratis-factuur') }}">Gratis factuur maken</a>
      <a class="side-link" href="{{ route('btw-calculator') }}">Btw-calculator</a>
      <a class="side-link" href="{{ route('uurtarief-calculator') }}">Uurtarief-calculator</a>
    </aside>

    <article class="prose" style="margin:0;">
      @foreach ($article['sections'] as $section)
        <h2>{{ $section[0] }}</h2>
        @foreach ($section[1] as $paragraph)
          <p>{{ $paragraph }}</p>
        @endforeach
      @endforeach

      <p style="margin-top:32px;font-size:13px;color:var(--text-3);">Dit artikel is algemene informatie, geen fiscaal advies. Bedragen en regels veranderen — controleer de actuele stand bij de <a href="https://www.belastingdienst.nl" target="_blank" rel="noopener">Belastingdienst</a> of vraag het je boekhouder.</p>

      <div class="article-cta">
        <h3>Factureren zonder erover na te denken</h3>
        <p>{{ brand('name') }} zet automatisch alle verplichte gegevens op je factuur, nummert netjes door en herinnert je klanten als ze te laat zijn. Vanaf € 12,10 per maand, 14 dagen gratis te proberen.</p>
        <a href="{{ route('register') }}" class="btn btn-primary">Probeer gratis →</a>
        <a href="{{ route('gratis-factuur') }}" class="btn btn-ghost">Of maak eerst gratis een factuur</a>
      </div>
    </article>
  </div>
</section>

<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "Article",
  "headline": {!! json_encode($article['title']) !!},
  "description": {!! json_encode($article['intro']) !!},
  "inLanguage": "nl",
  "mainEntityOfPage": {!! json_encode(url()->current()) !!},
  "author": { "@@type": "Organization", "name": {!! json_encode(brand('name')) !!}, "url": {!! json_encode(url('/')) !!} },
  "publisher": { "@@type": "Organization", "name": "Creditline B.V." }
}
</script>
@endsection
