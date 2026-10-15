@extends('layouts.marketing')

@section('title', \App\Support\Seo::title($example['title'] . ' (pdf) — ' . brand('name'), $example['title'] . ' — ' . brand('name'), $example['title']))
@section('description', \App\Support\Seo::description($example['intro']))

@push('styles')
<style>
  .ex-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: start; max-width: 1100px; margin: 0 auto; }
  @media (max-width: 900px) { .ex-layout { grid-template-columns: 1fr; } }
  .ex-pdf { position: sticky; top: 90px; }
  @media (max-width: 900px) { .ex-pdf { position: static; } }
  .ex-pdf iframe { width: 100%; height: 760px; border: 1px solid var(--border); border-radius: 12px; background: #fff; }
  .ex-point { border-top: 1px solid var(--border); padding: 18px 0; }
  .ex-point h2 { font-size: 18px; margin: 0 0 8px; }
  .ex-point p { color: var(--text-2); line-height: 1.65; margin: 0; }
  .ex-aside a { display: block; font-size: 14.5px; color: var(--text-2); padding: 4px 0; }
  .ex-aside a:hover { color: var(--brand); }
  .ex-aside h3 { font-size: 13px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-4); margin: 24px 0 8px; }
</style>
@endpush

@section('content')
<section class="page-hero" style="padding-bottom:8px;">
  <div class="container">
    @include('marketing.partials.breadcrumbs', ['crumbs' => [['Voorbeeldfacturen', route('voorbeelden')], [$example['title'], null]]])
    <div class="page-hero-inner">
      <h1>{{ $example['title'] }}</h1>
      <p class="lead">{{ $example['intro'] }}</p>
      <div style="margin-top:18px;">
        <a href="{{ route('voorbeelden.pdf', ['slug' => $slug, 'download' => 1]) }}" class="btn btn-primary">Download de pdf →</a>
        <a href="{{ route('gratis-factuur') }}" class="btn btn-ghost">Zelf zo'n factuur maken</a>
      </div>
    </div>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container ex-layout">
    <div class="ex-pdf">
      <iframe src="{{ route('voorbeelden.html', $slug) }}" title="{{ $example['title'] }}" loading="lazy"></iframe>
      <p style="font-size:13px;color:var(--text-3);margin:10px 0 0;">Dit is de factuur zoals hij in de pdf staat. <a href="{{ route('voorbeelden.pdf', $slug) }}" target="_blank" rel="noopener" style="color:var(--brand);">Open de pdf in een nieuw tabblad</a>. De bedrijven in het voorbeeld zijn verzonnen.</p>
    </div>

    <div>
      <h2 style="font-size:22px;margin:0 0 12px;">Wat je op dit voorbeeld ziet</h2>
      @foreach ($example['points'] as [$heading, $text])
        <div class="ex-point">
          <h2>{{ $heading }}</h2>
          <p>{{ $text }}</p>
        </div>
      @endforeach

      @php
        $related = collect($example['related'] ?? [])->filter(fn ($s) => isset($articles[$s]));
        $begrippen = collect($example['begrippen'] ?? [])->filter(fn ($s) => isset($terms[$s]));
      @endphp
      <div class="ex-aside">
        <h3>Andere voorbeelden</h3>
        @foreach ($examples as $key => $other)
          @if ($key !== $slug)
            <a href="{{ route('voorbeelden.voorbeeld', $key) }}">{{ $other['title'] }}</a>
          @endif
        @endforeach
        <a href="{{ route('aanmaning.example') }}">Voorbeeld aanmaning</a>
        @if ($related->isNotEmpty())
          <h3>Lees verder</h3>
          @foreach ($related as $s)
            <a href="{{ route('kennisbank.artikel', $s) }}">{{ $articles[$s]['title'] }}</a>
          @endforeach
        @endif
        @if ($begrippen->isNotEmpty())
          <h3>Begrippen</h3>
          @foreach ($begrippen as $s)
            <a href="{{ route('begrippen.term', $s) }}">{{ $terms[$s]['term'] }}</a>
          @endforeach
        @endif
      </div>

      <div style="margin-top:32px;padding:24px;background:var(--brand-tint);border:1px solid var(--brand-border);border-radius:14px;">
        <h2 style="font-size:18px;margin:0 0 6px;">Zo'n factuur in één minuut</h2>
        <p style="color:var(--text-2);font-size:14.5px;margin:0 0 14px;">{{ brand('name') }} zet alle verplichte gegevens er automatisch op, nummert doorlopend, kiest per regel het btw-tarief en stuurt de factuur met een betaallink. Veertien dagen gratis.</p>
        <a href="{{ route('register') }}" class="btn btn-primary">Probeer gratis →</a>
      </div>
    </div>
  </div>
</section>
@endsection
