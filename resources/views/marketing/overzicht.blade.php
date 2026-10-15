@extends('layouts.marketing')

@section('title', 'Overzicht van alle pagina\'s — ' . brand('name'))
@section('description', 'Alle openbare pagina\'s van ' . brand('name') . ' op één plek: product, gratis tools, helpcentrum, kennisbank, begrippenlijst, pagina\'s per beroep en voorbeeldfacturen.')

@push('styles')
<style>
  .site-groups { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 32px; }
  @media (max-width: 900px) { .site-groups { grid-template-columns: 1fr 1fr; } }
  @media (max-width: 600px) { .site-groups { grid-template-columns: 1fr; } }
  .site-group h2 { font-size: 17px; margin: 0 0 10px; }
  .site-group ul { list-style: none; margin: 0; padding: 0; }
  .site-group li { padding: 3px 0; font-size: 14.5px; line-height: 1.45; }
  .site-group a { color: var(--text-2); }
  .site-group a:hover { color: var(--brand); }
</style>
@endpush

@section('content')
<section class="page-hero" style="padding-bottom:8px;">
  <div class="container">
    @include('marketing.partials.breadcrumbs', ['crumbs' => [['Overzicht van alle pagina\'s', null]]])
    <div class="page-hero-inner">
      <h1>Alle pagina's op een rij</h1>
      <p class="lead">Zoek je iets specifieks? Hier staat elke openbare pagina van {{ brand('name') }}, gegroepeerd per onderwerp. Dezelfde indeling als onze <a href="{{ url('/sitemap.xml') }}" style="color:var(--brand);">sitemap voor zoekmachines</a>.</p>
    </div>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="site-groups">
      @foreach ($groups as $group)
        <div class="site-group">
          <h2>{{ $group['title'] }} <span style="color:var(--text-4);font-weight:400;font-size:13px;">({{ count($group['links']) }})</span></h2>
          <ul>
            @foreach ($group['links'] as $link)
              <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
            @endforeach
          </ul>
        </div>
      @endforeach
    </div>
  </div>
</section>
@endsection
