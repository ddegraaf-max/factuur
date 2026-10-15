@extends('layouts.marketing')

{{--
  Fachora — lista firm w kategorii, opcjonalnie w mieście (/uslugi/{category}[/{city}]).
  Zmienne z PublicController::category(): $category (klucz), $categoryName, $examples, $city (nazwa|null),
  $citySlug, $providers (Collection<Provider>), $cities [{name,slug,n}], $allCategories [{key,name,icon,examples}], $intakeUrl.
  Tylko firmy Fachora Verified. Bez firm: uczciwy komunikat „wkrótce” zamiast pustej strony-wydmuszki.
--}}

@php
    $icon = config("fachora.categories.{$category}.icon", 'tools');
    $plural = function (int $n, string $one, string $few, string $many): string {
        if ($n === 1) return $one;
        $m10 = $n % 10; $m100 = $n % 100;
        return ($m10 >= 2 && $m10 <= 4 && ($m100 < 12 || $m100 > 14)) ? $few : $many;
    };
    $n = $providers->count();
    $h1 = $city ? $categoryName . ' – ' . $city : $categoryName;
    $title = $city
        ? $categoryName . ' ' . $city . ' – sprawdzone firmy | Fachora'
        : $categoryName . ' – sprawdzone firmy w Polsce | Fachora';
    $desc = ($city ? $categoryName . ' w mieście ' . $city : $categoryName . ' w Polsce') . ': firmy Fachora Verified — sprawdzone w rejestrach, z ceną ustaloną z góry, bezpieczną płatnością Fachora Protect, fakturą i gwarancją.' . ($examples ? ' Np. ' . $examples . '.' : '') . ' Zostaw zlecenie bezpłatnie.';
@endphp

@section('title', $title)
@section('description', $desc)

@push('styles')
<style>
  .fc-hero { padding: 48px 0 24px; }
  .fc-crumb { font-size: 13px; color: var(--text-3); margin-bottom: 22px; }
  .fc-crumb a { color: var(--text-3); } .fc-crumb a:hover { color: var(--brand); }
  .fc-head { display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap; }
  .fc-icon { width: 72px; height: 72px; border-radius: 18px; background: var(--brand); color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .fc-icon svg { width: 34px; height: 34px; }
  .fc-head h1 { font-size: clamp(28px, 4.5vw, 44px); margin: 0 0 8px; color: var(--brand-darker); }
  .fc-ex { color: var(--text-2); font-size: 16px; line-height: 1.55; max-width: 640px; margin: 0 0 16px; }
  .fc-ctas { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
  .fc-ctas small { color: var(--text-3); font-size: 13px; }
  .fc-count { font-size: 14px; color: var(--text-3); margin: 34px 0 14px; display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; align-items: baseline; }
  .fc-count h2 { font-size: 22px; }
  .fc-cards { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; }
  @media (max-width: 800px) { .fc-cards { grid-template-columns: 1fr; } }
  .fc-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 22px; display: flex; flex-direction: column; gap: 12px; transition: transform .15s, box-shadow .15s, border-color .15s; }
  .fc-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); border-color: var(--border-strong); }
  .fc-card .top { display: flex; gap: 14px; align-items: flex-start; }
  .fc-av { width: 52px; height: 52px; border-radius: 13px; flex-shrink: 0; background: var(--brand-tint); color: var(--brand); display: flex; align-items: center; justify-content: center; font-family: var(--font-display); font-weight: 700; font-size: 20px; overflow: hidden; border: 1px solid var(--brand-border); }
  .fc-av img { width: 100%; height: 100%; object-fit: cover; }
  .fc-card h3 { font-size: 18px; margin: 0 0 4px; }
  .fc-card h3 a { color: var(--brand-darker); }
  .fc-card h3 a:hover { color: var(--accent-dark); }
  .fc-meta { font-size: 13.5px; color: var(--text-3); display: flex; flex-wrap: wrap; gap: 4px 12px; }
  .fc-stars { color: var(--accent); font-weight: 700; }
  .fc-tags { display: flex; flex-wrap: wrap; gap: 6px; font-size: 12.5px; }
  .fc-tag { padding: 4px 10px; border-radius: 100px; background: var(--bg); border: 1px solid var(--border); color: var(--text-2); }
  .fc-tag.ok { background: var(--success-bg); border-color: #A7F3D0; color: #065F46; }
  .fc-flags { font-size: 15px; letter-spacing: 2px; }
  .fc-foot { margin-top: auto; display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }
  .fc-empty { background: var(--surface); border: 1px dashed var(--border-strong); border-radius: 16px; padding: 36px 28px; text-align: center; max-width: 680px; margin: 0 auto; }
  .fc-empty h2 { font-size: 22px; margin-bottom: 8px; }
  .fc-empty p { color: var(--text-2); line-height: 1.65; margin: 0 0 18px; }
  .fc-cities { display: flex; flex-wrap: wrap; gap: 8px; }
  .fc-city { display: inline-flex; align-items: center; gap: 6px; padding: 7px 13px; border-radius: 100px; background: var(--surface); border: 1px solid var(--border); font-size: 13.5px; font-weight: 600; color: var(--brand); }
  .fc-city:hover { border-color: var(--brand); }
  .fc-city small { color: var(--text-3); font-weight: 500; }
  .fc-city.is-active { background: var(--brand); color: #fff; border-color: var(--brand); }
  .fc-city.is-active small { color: rgba(255,255,255,0.7); }
  .fc-cats { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; }
  @media (max-width: 1000px) { .fc-cats { grid-template-columns: repeat(3, 1fr); } }
  @media (max-width: 600px) { .fc-cats { grid-template-columns: repeat(2, 1fr); } }
  .fc-cat { display: flex; flex-direction: column; gap: 8px; padding: 16px; border-radius: 14px; background: var(--surface); border: 1px solid var(--border); color: var(--text); transition: border-color .15s, transform .15s; }
  .fc-cat:hover { border-color: var(--brand); transform: translateY(-2px); }
  .fc-cat.is-active { border-color: var(--brand); background: var(--brand-tint); }
  .fc-cat svg { width: 24px; height: 24px; color: var(--brand); }
  .fc-cat b { font-family: var(--font-display); font-size: 15px; }
  .fc-cat small { font-size: 12px; color: var(--text-3); line-height: 1.45; }
  .fc-seo { max-width: 760px; color: var(--text-2); line-height: 1.75; font-size: 15px; }
  .fc-seo a { color: var(--brand); font-weight: 500; }
  .fc-h2 { font-size: 22px; margin-bottom: 14px; }
</style>
@endpush

@section('content')
<section class="fc-hero">
  <div class="container">
    <div class="fc-crumb">
      <a href="{{ url('/') }}">Fachora</a> › <a href="{{ url('/') }}#uslugi">Usługi</a> ›
      @if($city)<a href="{{ route('fachora.category', $category) }}">{{ $categoryName }}</a> › <span>{{ $city }}</span>@else<span>{{ $categoryName }}</span>@endif
    </div>
    <div class="fc-head">
      <div class="fc-icon">@include('fachora.partials.icon', ['name' => $icon, 'size' => 34])</div>
      <div style="flex:1;min-width:260px;">
        <h1>{{ $h1 }}</h1>
        @if($examples)<p class="fc-ex">Na przykład: {{ $examples }}. Tylko firmy Fachora Verified — sprawdzone w rejestrach, z ceną z góry, fakturą i gwarancją.</p>@endif
        <div class="fc-ctas">
          <a href="{{ $intakeUrl }}" class="btn btn-primary btn-lg">Zostaw zlecenie →</a>
          <small>Bezpłatnie, bez konta. Oferty od maks. {{ (int) config('fachora.max_offers') }} firm.</small>
        </div>
      </div>
    </div>

    @if($n > 0)
      <div class="fc-count">
        <h2>{{ $n }} {{ $plural($n, 'firma Fachora Verified', 'firmy Fachora Verified', 'firm Fachora Verified') }}{{ $city ? ' · ' . $city : '' }}</h2>
        <span>Kolejność: ocena, liczba zleceń</span>
      </div>
      <div class="fc-cards">
        @foreach($providers as $p)
          @php $s = $p->publicSummary(); $rc = (int) ($s['rating_count'] ?? 0); @endphp
          <div class="fc-card">
            <div class="top">
              <div class="fc-av">@if($s['photo'])<img src="{{ $s['photo'] }}" alt="">@else{{ mb_strtoupper(mb_substr($s['name'], 0, 1)) }}@endif</div>
              <div style="min-width:0;flex:1;">
                <h3><a href="{{ $s['url'] }}">{{ $s['name'] }}</a></h3>
                <div class="fc-meta">
                  @if($s['city'])<span>📍 {{ $s['city'] }}@if($s['radius_km']) · do {{ $s['radius_km'] }} km @endif</span>@endif
                  @if($rc > 0 && $s['rating'])<span class="fc-stars">★ {{ number_format((float) $s['rating'], 1, ',', '') }} <span style="color:var(--text-3);font-weight:500;">({{ $rc }})</span></span>@else<span>Nowa firma na Fachora</span>@endif
                  @if(($s['jobs_done'] ?? 0) > 0)<span>{{ $s['jobs_done'] }} {{ $plural((int) $s['jobs_done'], 'zlecenie', 'zlecenia', 'zleceń') }}</span>@endif
                </div>
              </div>
              <span class="fv-badge" title="Fachora Verified">Verified</span>
            </div>
            <div class="fc-tags">
              @if($s['warranty_months'])<span class="fc-tag ok">✓ Gwarancja {{ $s['warranty_months'] }} mies.</span>@endif
              @if($s['invoice'])<span class="fc-tag ok">✓ Faktura</span>@endif
              @if($s['protect'])<span class="fc-tag ok">✓ Fachora Protect</span>@endif
              @if($s['insured'])<span class="fc-tag ok">✓ OC</span>@endif
              @foreach($s['categories'] as $ck)
                @if($ck !== $category)<span class="fc-tag">{{ \App\Support\Fachora::categoryName($ck) }}</span>@endif
              @endforeach
            </div>
            <div class="fc-foot">
              <span class="fc-flags" aria-label="Języki">@foreach($s['languages'] as $l)<span title="{{ $l['name'] }} · {{ $l['level'] }}">{{ $l['flag'] }}</span>@endforeach</span>
              <a href="{{ $s['url'] }}" class="btn btn-secondary" style="padding:9px 16px;">Zobacz profil →</a>
            </div>
          </div>
        @endforeach
      </div>
    @else
      <div class="fc-empty" style="margin-top:34px;">
        <div style="font-size:30px;margin-bottom:8px;">🔧</div>
        <h2>Wkrótce{{ $city ? ' w mieście ' . $city : '' }}</h2>
        <p>Nie mamy jeszcze firmy Fachora Verified {{ $city ? 'w tej okolicy' : 'w tej kategorii' }}, którą moglibyśmy Ci z czystym sumieniem polecić. Nie pokazujemy firm, których nie sprawdziliśmy.<br>Zostaw zlecenie — znajdziemy firmę, sprawdzimy ją i wrócimy do Ciebie z ofertą.</p>
        <a href="{{ $intakeUrl }}" class="btn btn-primary btn-lg">Zostaw zlecenie – znajdziemy firmę →</a>
      </div>
    @endif
  </div>
</section>

@if($cities->isNotEmpty())
<section class="section" style="padding:40px 0 10px;">
  <div class="container">
    <h2 class="fc-h2">{{ $categoryName }} — miasta</h2>
    <div class="fc-cities">
      @foreach($cities as $c)
        <a class="fc-city{{ $citySlug === $c['slug'] ? ' is-active' : '' }}" href="{{ route('fachora.category.city', [$category, $c['slug']]) }}">{{ $c['name'] }} <small>{{ $c['n'] }}</small></a>
      @endforeach
    </div>
  </div>
</section>
@endif

<section class="section" style="padding:40px 0 10px;">
  <div class="container">
    <h2 class="fc-h2">Inne usługi{{ $city ? ' · ' . $city : '' }}</h2>
    <div class="fc-cats">
      @foreach($allCategories as $c)
        <a class="fc-cat{{ $c['key'] === $category ? ' is-active' : '' }}" href="{{ $city && $citySlug ? route('fachora.category.city', [$c['key'], $citySlug]) : route('fachora.category', $c['key']) }}">
          @include('fachora.partials.icon', ['name' => $c['icon'], 'size' => 24])
          <b>{{ $c['name'] }}</b>
          <small>{{ $c['examples'] }}</small>
        </a>
      @endforeach
    </div>
  </div>
</section>

<section class="section" style="padding:40px 0 70px;">
  <div class="container">
    <h2 class="fc-h2">Jak sprawdzamy firmy: {{ mb_strtolower($categoryName) }}</h2>
    <p class="fc-seo">Zanim firma z kategorii „{{ $categoryName }}” pojawi się na tej liście, sprawdzamy ją w CEIDG lub KRS (czy działa legalnie i czy jej PKD pasuje do usługi), w białej liście VAT, potwierdzamy e-mail i telefon, a tożsamość właściciela i konto bankowe weryfikuje Stripe podczas podłączania płatności. Firma akceptuje <a href="{{ route('fachora.standard') }}">Standard Fachora</a>: cena z góry, żadnych dodatkowych prac bez Twojej zgody, faktura, gwarancja i odpowiedź na reklamację w {{ (int) config('fachora.dispute_hours') }} godzin. Jeśli ocena spada poniżej {{ number_format((float) config('fachora.min_rating'), 1, ',', '') }} albo pojawiają się uzasadnione skargi, firma znika z listy. Płacisz przez <a href="{{ route('fachora.protect') }}">Fachora Protect</a> dopiero po zakończeniu pracy. Więcej: <a href="{{ route('fachora.how') }}">jak to działa</a>.</p>
  </div>
</section>
@endsection
