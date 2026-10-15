@extends('layouts.marketing')

{{--
  Fachora — publiczny profil firmy (/firma/{slug}).
  Zmienne z PublicController::provider(): $provider, $summary (Provider::publicSummary()),
  $reviews, $categories [{key,name}], $claimUrl, $intakeUrl.
  Dwie wersje: firma Fachora Verified (pełny profil, dane kontaktowe nigdy; zlecenie idzie przez Fachora)
  oraz profil nieaktywny (dane z rejestru publicznego CEIDG/KRS: nazwa, miasto, kategorie — bez kontaktu,
  z zachętą do przejęcia profilu; noindex).
--}}

@php
    $v = $summary['verified'];
    $catNames = collect($categories)->pluck('name')->all();
    $plural = function (int $n, string $one, string $few, string $many): string {
        if ($n === 1) return $one;
        $m10 = $n % 10; $m100 = $n % 100;
        return ($m10 >= 2 && $m10 <= 4 && ($m100 < 12 || $m100 > 14)) ? $few : $many;
    };
    $rating = $summary['rating'] ? number_format((float) $summary['rating'], 1, ',', '') : null;
    $ratingCount = (int) ($summary['rating_count'] ?? 0);
    $jobsDone = (int) ($summary['jobs_done'] ?? 0);
    $rates = is_array($summary['rates']) ? array_filter($summary['rates'], fn ($x) => $x !== null && $x !== '') : [];
    $initials = mb_strtoupper(mb_substr($summary['name'], 0, 1));
    $title = $v
        ? $summary['name'] . ' – ' . implode(', ', array_slice($catNames, 0, 2)) . ($summary['city'] ? ' ' . $summary['city'] : '') . ' | Fachora Verified'
        : $summary['name'] . ($summary['city'] ? ' – ' . $summary['city'] : '') . ' | Fachora';
    $desc = $v
        ? $summary['name'] . ' (' . $summary['city'] . '): firma Fachora Verified — ' . implode(', ', $catNames) . '. Zweryfikowana w rejestrach, cena ustalona z góry, bezpieczna płatność Fachora Protect, faktura i gwarancja. Zapytaj o wycenę przez Fachora.'
        : $summary['name'] . ($summary['city'] ? ' (' . $summary['city'] . ')' : '') . ' — profil firmy na Fachora na podstawie rejestru publicznego. Profil nie został jeszcze przejęty ani zweryfikowany. Jesteś właścicielem? Przejmij profil.';

    $ld = null;
    if ($v) {
        $ld = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $summary['name'],
            'url' => $summary['url'],
            'address' => ['@type' => 'PostalAddress', 'addressLocality' => $summary['city'], 'addressCountry' => 'PL'],
        ];
        if ($summary['description']) {
            $ld['description'] = \Illuminate\Support\Str::limit(strip_tags((string) $summary['description']), 300);
        }
        if ($ratingCount > 0 && $summary['rating']) {
            $ld['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => round((float) $summary['rating'], 1), 'reviewCount' => $ratingCount, 'bestRating' => 5, 'worstRating' => 1];
        }
    }
@endphp

@section('title', $title)
@section('description', $desc)

@push('styles')
@if(! $v)
<meta name="robots" content="noindex, follow">
@endif
@if($ld)
<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endif
<style>
  .fp-hero { padding: 48px 0 28px; }
  .fp-crumb { font-size: 13px; color: var(--text-3); margin-bottom: 22px; }
  .fp-crumb a { color: var(--text-3); } .fp-crumb a:hover { color: var(--brand); }
  .fp-grid { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 32px; align-items: start; }
  @media (max-width: 960px) { .fp-grid { grid-template-columns: 1fr; } }
  .fp-head { display: flex; gap: 20px; align-items: flex-start; }
  .fp-avatar { width: 84px; height: 84px; border-radius: 18px; flex-shrink: 0; background: var(--brand-tint); color: var(--brand); display: flex; align-items: center; justify-content: center; font-family: var(--font-display); font-weight: 700; font-size: 32px; overflow: hidden; border: 1px solid var(--brand-border); }
  .fp-avatar img { width: 100%; height: 100%; object-fit: cover; }
  .fp-head h1 { font-size: clamp(26px, 4vw, 38px); margin: 0 0 8px; color: var(--brand-darker); }
  .fp-meta { display: flex; flex-wrap: wrap; gap: 8px 16px; align-items: center; font-size: 14.5px; color: var(--text-2); }
  .fp-stars { color: var(--accent); font-weight: 700; }
  .fp-stars small { color: var(--text-3); font-weight: 500; }
  .fp-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 18px; }
  .fp-chip { display: inline-flex; align-items: center; gap: 7px; padding: 7px 13px 7px 9px; border-radius: 100px; background: var(--surface); border: 1px solid var(--border); font-size: 13.5px; font-weight: 600; color: var(--brand); }
  .fp-chip svg { width: 18px; height: 18px; }
  .fp-langs { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
  .fp-lang { display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px; border-radius: 100px; background: var(--brand-tint); font-size: 13px; color: var(--text-2); }
  .fp-lang b { color: var(--text); font-weight: 600; }
  .fp-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 24px; margin-bottom: 20px; }
  .fp-card h2 { font-size: 19px; margin: 0 0 14px; }
  .fp-facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; }
  .fp-fact { padding: 14px; border-radius: 12px; background: var(--bg); border: 1px solid var(--border); }
  .fp-fact .k { font-size: 12px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-3); font-weight: 600; margin-bottom: 4px; }
  .fp-fact .v { font-weight: 700; font-size: 15px; color: var(--text); display: flex; align-items: center; gap: 6px; }
  .fp-fact .v.ok::before { content: '✓'; color: var(--success); font-size: 14px; }
  .fp-fact .v.no { color: var(--text-3); font-weight: 500; }
  .fp-rates { width: 100%; border-collapse: collapse; font-size: 14.5px; }
  .fp-rates td { padding: 10px 0; border-top: 1px solid var(--border); color: var(--text-2); }
  .fp-rates tr:first-child td { border-top: none; }
  .fp-rates td:last-child { text-align: right; font-weight: 700; color: var(--text); white-space: nowrap; }
  .fp-note { font-size: 13px; color: var(--text-3); margin-top: 10px; line-height: 1.55; }
  .fp-desc { color: var(--text-2); line-height: 1.75; white-space: pre-line; }
  .fp-review { padding: 18px 0; border-top: 1px solid var(--border); }
  .fp-review:first-of-type { border-top: none; padding-top: 0; }
  .fp-review .top { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; font-size: 13px; color: var(--text-3); margin-bottom: 6px; }
  .fp-review .text { color: var(--text-2); line-height: 1.65; }
  .fp-reply { margin-top: 10px; padding: 12px 14px; border-left: 3px solid var(--brand-border); background: var(--brand-tint); border-radius: 0 10px 10px 0; font-size: 14px; color: var(--text-2); }
  .fp-reply b { display: block; font-size: 12px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--brand); margin-bottom: 4px; }
  .fp-side { position: sticky; top: 88px; }
  .fp-cta { background: var(--surface); border: 2px solid var(--brand); border-radius: 18px; padding: 24px; box-shadow: var(--shadow-brand); }
  .fp-cta h2 { font-size: 20px; margin: 0 0 8px; color: var(--brand-darker); }
  .fp-cta p { font-size: 14px; color: var(--text-2); line-height: 1.6; margin: 0 0 14px; }
  .fp-cta .btn { width: 100%; justify-content: center; }
  .fp-cta ul { list-style: none; padding: 0; margin: 14px 0 0; font-size: 13.5px; color: var(--text-2); }
  .fp-cta li { padding: 5px 0; display: flex; gap: 8px; align-items: flex-start; }
  .fp-cta li::before { content: '✓'; color: var(--success); font-weight: 700; flex-shrink: 0; }
  .fp-bar { display: none; }
  @media (max-width: 960px) {
    .fp-side { position: static; }
    .fp-bar { display: flex; position: fixed; left: 0; right: 0; bottom: 0; z-index: 40; padding: 10px 16px calc(10px + env(safe-area-inset-bottom)); background: rgba(255,255,255,0.96); border-top: 1px solid var(--border); backdrop-filter: blur(8px); gap: 12px; align-items: center; }
    .fp-bar .btn { flex: 1; justify-content: center; }
    body { padding-bottom: 76px; }
  }
  .fp-inactive { max-width: 720px; margin: 0 auto; }
  .fp-inactive .fp-card { padding: 30px; }
  .fp-status { display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; border-radius: 100px; background: var(--surface-2); color: var(--text-2); font-size: 13px; font-weight: 600; margin-bottom: 16px; }
  .fp-status::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: var(--text-4); }
  .fp-steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
  @media (max-width: 640px) { .fp-steps { grid-template-columns: 1fr; } }
  .fp-step { padding: 16px; border-radius: 12px; background: var(--bg); border: 1px solid var(--border); font-size: 14px; color: var(--text-2); line-height: 1.55; }
  .fp-step b { display: block; color: var(--brand); font-family: var(--font-display); font-size: 15px; margin-bottom: 4px; }
</style>
@endpush

@section('content')
@if($v)
{{-- ===================== FIRMA FACHORA VERIFIED ===================== --}}
<section class="fp-hero">
  <div class="container">
    <div class="fp-crumb">
      <a href="{{ url('/') }}">Fachora</a> ›
      @if(! empty($categories))
        <a href="{{ route('fachora.category', $categories[0]['key']) }}">{{ $categories[0]['name'] }}</a> ›
      @endif
      <span>{{ $summary['name'] }}</span>
    </div>

    <div class="fp-grid">
      <div>
        <div class="fp-head">
          <div class="fp-avatar">
            @if($summary['photo'])
              <img src="{{ $summary['photo'] }}" alt="{{ $summary['name'] }}">
            @else
              {{ $initials }}
            @endif
          </div>
          <div style="min-width:0;">
            <div style="margin-bottom:10px;"><span class="fv-badge">Fachora Verified</span></div>
            <h1>{{ $summary['name'] }}</h1>
            <div class="fp-meta">
              @if($summary['city'])
                <span>📍 {{ $summary['city'] }}@if($summary['radius_km']) · do {{ $summary['radius_km'] }} km @endif</span>
              @endif
              @if($ratingCount > 0 && $rating)
                <span class="fp-stars">★ {{ $rating }} <small>({{ $ratingCount }} {{ $plural($ratingCount, 'opinia', 'opinie', 'opinii') }})</small></span>
              @else
                <span style="color:var(--text-3);">Brak opinii — firma dopiero zaczyna na Fachora</span>
              @endif
              @if($jobsDone > 0)
                <span>{{ $jobsDone }} {{ $plural($jobsDone, 'zlecenie', 'zlecenia', 'zleceń') }} przez Fachora</span>
              @endif
              @if($summary['verified_since'])
                <span style="color:var(--text-3);">Zweryfikowana: {{ $summary['verified_since'] }}</span>
              @endif
            </div>
          </div>
        </div>

        @if(! empty($categories))
        <div class="fp-chips">
          @foreach($categories as $c)
            <a class="fp-chip" href="{{ route('fachora.category', $c['key']) }}">
              @include('fachora.partials.icon', ['name' => config("fachora.categories.{$c['key']}.icon", 'tools'), 'size' => 18])
              {{ $c['name'] }}
            </a>
          @endforeach
        </div>
        @endif

        @if(! empty($summary['languages']))
        <div class="fp-langs" aria-label="Języki obsługi">
          @foreach($summary['languages'] as $l)
            <span class="fp-lang">{{ $l['flag'] }} <b>{{ $l['name'] }}</b> · {{ $l['level'] }}</span>
          @endforeach
        </div>
        @endif

        <div style="height:26px;"></div>

        <div class="fp-card">
          <h2>Co gwarantuje ta firma</h2>
          <div class="fp-facts">
            <div class="fp-fact"><div class="k">Gwarancja</div><div class="v ok">{{ $summary['warranty_months'] ? $summary['warranty_months'] . ' ' . $plural((int) $summary['warranty_months'], 'miesiąc', 'miesiące', 'miesięcy') : 'wg oferty' }}</div></div>
            <div class="fp-fact"><div class="k">Faktura</div><div class="v {{ $summary['invoice'] ? 'ok' : '' }}">{{ $summary['invoice'] ? 'Zawsze' : 'Potwierdzenie wykonania' }}</div></div>
            <div class="fp-fact"><div class="k">Fachora Protect</div>@if($summary['protect'])<div class="v ok">Dostępny</div>@else<div class="v no">Niedostępny</div>@endif</div>
            <div class="fp-fact"><div class="k">Ubezpieczenie OC</div>@if($summary['insured'])<div class="v ok">Aktualne</div>@else<div class="v no">Brak informacji</div>@endif</div>
            @if($summary['radius_km'])
            <div class="fp-fact"><div class="k">Zasięg</div><div class="v">do {{ $summary['radius_km'] }} km</div></div>
            @endif
          </div>
          <div class="fp-note">
            Firma Fachora Verified: sprawdzona w rejestrach (CEIDG/KRS, biała lista VAT), z potwierdzonym e-mailem i telefonem, tożsamością i kontem bankowym zweryfikowanymi przez Stripe, i z zaakceptowanym <a href="{{ route('fachora.standard') }}" style="color:var(--brand);font-weight:500;">Standardem Fachora</a>.
            @if($summary['protect']) Z <a href="{{ route('fachora.protect') }}" style="color:var(--brand);font-weight:500;">Fachora Protect</a> płacisz dopiero po zakończeniu pracy — pieniądze czekają u operatora płatności.@endif
          </div>
        </div>

        @if(! empty($rates))
        <div class="fp-card">
          <h2>Stawki orientacyjne</h2>
          <table class="fp-rates">
            @if(! empty($rates['callout']))<tr><td>Dojazd</td><td>{{ \App\Support\Fachora::money((float) $rates['callout']) }}</td></tr>@endif
            @if(! empty($rates['first_hour']))<tr><td>Pierwsza godzina pracy</td><td>{{ \App\Support\Fachora::money((float) $rates['first_hour']) }}</td></tr>@endif
            @if(! empty($rates['extra_hour']))<tr><td>Każda kolejna godzina</td><td>{{ \App\Support\Fachora::money((float) $rates['extra_hour']) }}</td></tr>@endif
          </table>
          <div class="fp-note">Kwoty brutto, podane przez firmę jako orientacja. Wiążąca jest cena w ofercie, którą otrzymasz przed rozpoczęciem pracy. Dodatkowe prace tylko po Twojej zgodzie.</div>
        </div>
        @endif

        @if($summary['description'])
        <div class="fp-card">
          <h2>O firmie</h2>
          <div class="fp-desc">{{ $summary['description'] }}</div>
        </div>
        @endif

        <div class="fp-card">
          <h2>Opinie @if($ratingCount > 0)<span style="color:var(--text-3);font-weight:500;font-size:15px;">({{ $ratingCount }})</span>@endif</h2>
          @if($reviews->isEmpty())
            <p style="color:var(--text-2);margin:0;font-size:14.5px;line-height:1.65;">Ta firma nie ma jeszcze opinii na Fachora. Opinie mogą wystawiać wyłącznie klienci po zakończonym zleceniu — dlatego na Fachora nie ma opinii kupionych ani zmyślonych.</p>
          @else
            @foreach($reviews as $r)
              <div class="fp-review">
                <div class="top">
                  <span class="fp-stars" aria-label="{{ $r->rating }} z 5">{{ str_repeat('★', (int) $r->rating) }}<span style="color:var(--border-strong);">{{ str_repeat('★', 5 - (int) $r->rating) }}</span></span>
                  <span>{{ $r->job?->title ? $r->job->title . ' · ' : '' }}{{ $r->created_at?->translatedFormat('j F Y') }} · zlecenie przez Fachora</span>
                </div>
                @if($r->text)<div class="text">{{ $r->text }}</div>@endif
                @if($r->reply)
                  <div class="fp-reply"><b>Odpowiedź firmy</b>{{ $r->reply }}</div>
                @endif
              </div>
            @endforeach
          @endif
        </div>

        <div class="fp-card">
          <h2>Jak to działa</h2>
          <div class="fp-steps">
            <div class="fp-step"><b>1. Opisz, co trzeba zrobić</b>Kilka zdań i zdjęcia. Zlecenie trafia do maksymalnie {{ (int) config('fachora.max_offers') }} firm Fachora Verified w okolicy — w tym do tej.</div>
            <div class="fp-step"><b>2. Porównaj oferty</b>Cena z góry, termin, gwarancja. Wybierasz Ty — bez telefonów, bez targowania się.</div>
            <div class="fp-step"><b>3. Zapłać po pracy</b>Z Fachora Protect pieniądze czekają u operatora płatności i trafiają do firmy dopiero po Twoim „OK”.</div>
          </div>
        </div>
      </div>

      <aside class="fp-side">
        <div class="fp-cta">
          <h2>Zapytaj o wycenę</h2>
          <p>Nie podajemy numeru telefonu — zlecenie wysyłasz przez Fachora. Firma odpowiada ofertą z ceną i terminem, a Ty decydujesz bez zobowiązań.</p>
          <a href="{{ $intakeUrl }}" class="btn btn-primary btn-lg">Zapytaj o wycenę →</a>
          <ul>
            <li>Bezpłatnie i bez konta</li>
            <li>Cena ustalona przed rozpoczęciem pracy</li>
            @if($summary['protect'])<li>Płatność przez Fachora Protect — po zakończeniu</li>@endif
            <li>Odpowiedź zwykle w ciągu {{ (int) config('fachora.offer_hours') }} godzin</li>
          </ul>
        </div>
        <div class="fp-note" style="padding:0 6px;">Masz już zlecenie na Fachora? Skorzystaj z linku „Moje zlecenie” z e-maila, który od nas dostałeś.</div>
      </aside>
    </div>
  </div>
</section>

<div class="fp-bar" aria-hidden="true">
  <a href="{{ $intakeUrl }}" class="btn btn-primary">Zapytaj o wycenę →</a>
</div>

@else
{{-- ===================== PROFIL NIEAKTYWNY (rejestr, nieprzejęty / w trakcie) ===================== --}}
<section class="fp-hero">
  <div class="container fp-inactive">
    <div class="fp-crumb">
      <a href="{{ url('/') }}">Fachora</a> ›
      @if(! empty($categories))
        <a href="{{ route('fachora.category', $categories[0]['key']) }}">{{ $categories[0]['name'] }}</a> ›
      @endif
      <span>{{ $summary['name'] }}</span>
    </div>

    <div class="fp-card">
      <div class="fp-status">{{ $summary['status'] === 'suspended' ? 'Profil tymczasowo zawieszony' : 'Profil nieaktywny' }}</div>
      <h1 style="font-size:clamp(24px,4vw,34px);margin:0 0 10px;color:var(--brand-darker);">{{ $summary['name'] }}</h1>
      <div class="fp-meta" style="margin-bottom:16px;">
        @if($summary['city'])<span>📍 {{ $summary['city'] }}</span>@endif
        @if(! empty($catNames))<span>{{ implode(' · ', $catNames) }}</span>@endif
      </div>

      @if($summary['status'] === 'suspended')
        <p style="color:var(--text-2);line-height:1.7;margin:0 0 14px;">Ten profil jest tymczasowo zawieszony i firma nie otrzymuje obecnie zleceń przez Fachora. Zawieszenie wynika z zasad <a href="{{ route('fachora.standard') }}" style="color:var(--brand);font-weight:500;">Standardu Fachora</a> (np. wygasła kontrola lub nierozstrzygnięte zgłoszenia) i jest ponownie oceniane po upływie wyznaczonego okresu.</p>
      @else
        <p style="color:var(--text-2);line-height:1.7;margin:0 0 14px;"><strong>Ten profil nie jest jeszcze aktywny.</strong> Dane pochodzą z publicznego rejestru przedsiębiorców (CEIDG lub KRS): nazwa, miejscowość i rodzaj działalności. Firma nie przejęła jeszcze swojego profilu i nie przeszła weryfikacji Fachora — dlatego nie oznaczamy jej jako Fachora Verified i nie kierujemy do niej zleceń.</p>
        <p style="color:var(--text-2);line-height:1.7;margin:0 0 14px;">Nie publikujemy danych kontaktowych firm, które się u nas nie zarejestrowały, i nie wysyłamy im wiadomości e-mail ani SMS bez ich zgody. Obecność w rejestrze nie oznacza, że firma jest sprawdzona — <a href="{{ route('fachora.standard') }}" style="color:var(--brand);font-weight:500;">Fachora Verified</a> to coś więcej.</p>
      @endif

      <div style="margin-top:22px;padding:22px;border-radius:14px;background:var(--brand-tint);border:1px solid var(--brand-border);">
        <div style="font-family:var(--font-display);font-weight:700;font-size:18px;color:var(--brand-darker);margin-bottom:6px;">Jesteś właścicielem tej firmy?</div>
        <p style="color:var(--text-2);font-size:14.5px;line-height:1.6;margin:0 0 14px;">Przejmij profil, uzupełnij dane i przejdź weryfikację. Bez abonamentu, bez opłat za rejestrację — prowizja tylko od wykonanych zleceń.</p>
        <a href="{{ $claimUrl }}" class="btn btn-primary btn-lg">Jesteś właścicielem? Przejmij profil →</a>
        <div style="font-size:13px;color:var(--text-3);margin-top:12px;">Dostałeś od nas list z kodem? Wpisz go na następnej stronie.</div>
      </div>
    </div>

    <div class="fp-card" style="background:var(--bg);">
      <h2 style="font-size:18px;">Szukasz fachowca{{ ! empty($catNames) ? ' (' . mb_strtolower($catNames[0]) . ')' : '' }}?</h2>
      <p style="color:var(--text-2);font-size:14.5px;line-height:1.65;margin:0 0 14px;">Zostaw zlecenie — skierujemy je do firm Fachora Verified w Twojej okolicy. Cena z góry, bezpieczna płatność, gwarancja.</p>
      <a href="{{ $intakeUrl }}" class="btn btn-secondary">Zostaw zlecenie</a>
      @if(! empty($categories))
        <a href="{{ route('fachora.category', $categories[0]['key']) }}" class="btn btn-ghost">Sprawdzone firmy: {{ $categories[0]['name'] }}</a>
      @endif
    </div>
  </div>
</section>
@endif
@endsection
