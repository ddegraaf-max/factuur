@extends('layouts.marketing')

@section('title', $article['title'] . ' — Helpcentrum — EasyInvoice')
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
</style>
@endpush

@section('content')
<section class="page-hero" style="padding-bottom:8px;">
  <div class="container">
    <div class="breadcrumb" style="text-align:center;">
      <a href="{{ route('helpcentrum') }}">Helpcentrum</a> &nbsp;›&nbsp; {{ $article['category'] }}
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
          <a class="side-link {{ $key === $slug ? 'active' : '' }}" href="{{ route('help.article', $key) }}">{{ $a['title'] }}</a>
        @endforeach
      @endforeach
    </aside>

    <article class="prose" style="margin:0;">
      @foreach ($article['sections'] as $section)
        <h2>{{ $section[0] }}</h2>
        @foreach ($section[1] as $paragraph)
          <p>{{ $paragraph }}</p>
        @endforeach
      @endforeach

      <hr class="divider" style="margin:40px 0;">
      <p style="color:var(--text-3);">Niet helemaal duidelijk? <a href="{{ route('contact') }}">Neem contact op</a> of mail <a href="mailto:hallo@easyinvoice.nl">hallo@easyinvoice.nl</a> — we helpen je graag.</p>
    </article>
  </div>
</section>
@endsection
