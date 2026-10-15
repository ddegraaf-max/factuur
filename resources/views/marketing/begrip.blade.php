@extends('layouts.marketing')

@section('title', \App\Support\Seo::title($term['term'] . ': betekenis en uitleg — Begrippenlijst — ' . brand('name'), $term['term'] . ': betekenis en uitleg — ' . brand('name'), $term['term'] . ' — ' . brand('name'), $term['term']))
@section('description', \App\Support\Seo::description($term['short']))

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
  .term-def { font-size: 18px; line-height: 1.6; color: var(--text); background: var(--surface-2); border-left: 4px solid var(--brand); padding: 16px 20px; border-radius: 0 12px 12px 0; margin: 0 0 28px; }
</style>
@endpush

@section('content')
<section class="page-hero" style="padding-bottom:8px;">
  <div class="container">
    @include('marketing.partials.breadcrumbs', ['crumbs' => [['Begrippenlijst', route('begrippen')], [$term['term'], null]]])
    <div class="page-hero-inner">
      <h1>{{ $term['term'] }}: betekenis en uitleg</h1>
    </div>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container article-layout">
    <aside class="article-side">
      <div class="side-cat">Begrippen</div>
      @foreach ($terms as $key => $t)
        <a class="side-link {{ $key === $slug ? 'active' : '' }}" href="{{ route('begrippen.term', $key) }}">{{ $t['term'] }}</a>
      @endforeach
    </aside>

    <article class="prose" style="margin:0;">
      <p class="term-def">{{ $term['short'] }}</p>

      @if (! empty($term['tool']) && \Illuminate\Support\Facades\Route::has($term['tool']['route']))
        <p><a href="{{ route($term['tool']['route']) }}" class="btn btn-primary">{{ $term['tool']['label'] }} →</a></p>
      @endif

      @foreach ($term['sections'] as $section)
        <h2>{{ $section[0] }}</h2>
        @foreach ($section[1] as $paragraph)
          <p>{{ $paragraph }}</p>
        @endforeach
      @endforeach

      @php
        $see = collect($term['see'] ?? [])->filter(fn ($s) => isset($terms[$s]));
        $related = collect($term['related'] ?? [])->filter(fn ($s) => isset($articles[$s]));
      @endphp
      @if ($see->isNotEmpty() || $related->isNotEmpty())
        <h2>Verder lezen</h2>
        <ul>
          @foreach ($see as $s)
            <li><a href="{{ route('begrippen.term', $s) }}">{{ $terms[$s]['term'] }}</a> (begrip)</li>
          @endforeach
          @foreach ($related as $s)
            <li><a href="{{ route('kennisbank.artikel', $s) }}">{{ $articles[$s]['title'] }}</a> (kennisbank)</li>
          @endforeach
        </ul>
      @endif

      <p style="margin-top:32px;font-size:13px;color:var(--text-3);">Dit is algemene informatie, geen fiscaal of juridisch advies. Bedragen en regels veranderen; controleer de actuele stand bij de Belastingdienst. Laatst bijgewerkt op {{ \Carbon\Carbon::parse($term['updated'])->translatedFormat('j F Y') }}.</p>

      <div class="article-cta">
        <h3>Factureren zonder erover na te denken</h3>
        <p>{{ brand('name') }} zet de verplichte gegevens automatisch op je factuur, kiest per regel het juiste btw-tarief en herinnert je klanten als ze te laat zijn. Veertien dagen gratis, daarna € 10 per maand excl. btw.</p>
        <a href="{{ route('register') }}" class="btn btn-primary">Probeer gratis →</a>
        <a href="{{ route('voorbeelden') }}" class="btn btn-ghost">Bekijk voorbeeldfacturen</a>
      </div>
    </article>
  </div>
</section>

<script type="application/ld+json">
{!! json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'DefinedTerm',
  'name' => $term['term'],
  'description' => $term['short'],
  'url' => url()->current(),
  'inLanguage' => 'nl',
  'inDefinedTermSet' => ['@type' => 'DefinedTermSet', 'name' => 'Begrippenlijst factureren, btw en betaald krijgen', 'url' => route('begrippen')],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endsection
