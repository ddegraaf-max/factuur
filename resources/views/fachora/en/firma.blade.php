@extends('layouts.marketing')

{{--
  Fachora — public company profile (/firma/{slug}), English edition.
  Variables from PublicController::provider(): $provider, $summary (Provider::publicSummary()),
  $reviews, $categories [{key,name}], $claimUrl, $intakeUrl.
  Two versions: a Fachora Verified company (full profile, never any contact details; the request goes through Fachora)
  and an inactive profile (public-register data only: name, city, categories; no contact details; claim prompt; noindex).
--}}

@php
    $v = $summary['verified'];
    $catNames = collect($categories)->pluck('name')->all();
    $rating = $summary['rating'] ? number_format((float) $summary['rating'], 1, '.', '') : null;
    $ratingCount = (int) ($summary['rating_count'] ?? 0);
    $jobsDone = (int) ($summary['jobs_done'] ?? 0);
    $rates = is_array($summary['rates']) ? array_filter($summary['rates'], fn ($x) => $x !== null && $x !== '') : [];
    $initials = mb_strtoupper(mb_substr($summary['name'], 0, 1));
    $title = $v
        ? $summary['name'] . ' – ' . implode(', ', array_slice($catNames, 0, 2)) . ($summary['city'] ? ' ' . $summary['city'] : '') . ' | Fachora Verified'
        : $summary['name'] . ($summary['city'] ? ' – ' . $summary['city'] : '') . ' | Fachora';
    $desc = $v
        ? $summary['name'] . ' (' . $summary['city'] . '): a Fachora Verified company — ' . implode(', ', $catNames) . '. Checked in the public registers, price agreed up front, secure Fachora Protect payment, invoice and guarantee. Ask for a quote through Fachora.'
        : $summary['name'] . ($summary['city'] ? ' (' . $summary['city'] . ')' : '') . ' — company profile on Fachora based on the public business register. The profile has not been claimed or verified yet. Is this your company? Claim the profile.';

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
{{-- ===================== FACHORA VERIFIED COMPANY ===================== --}}
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
                <span>📍 {{ $summary['city'] }}@if($summary['radius_km']) · up to {{ $summary['radius_km'] }} km @endif</span>
              @endif
              @if($ratingCount > 0 && $rating)
                <span class="fp-stars">★ {{ $rating }} <small>({{ $ratingCount }} {{ $ratingCount === 1 ? 'review' : 'reviews' }})</small></span>
              @else
                <span style="color:var(--text-3);">No reviews yet — this company is new on Fachora</span>
              @endif
              @if($jobsDone > 0)
                <span>{{ $jobsDone }} {{ $jobsDone === 1 ? 'job' : 'jobs' }} through Fachora</span>
              @endif
              @if($summary['verified_since'])
                <span style="color:var(--text-3);">Verified since {{ $summary['verified_since'] }}</span>
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
        <div class="fp-langs" aria-label="Languages spoken">
          @foreach($summary['languages'] as $l)
            <span class="fp-lang">{{ $l['flag'] }} <b>{{ $l['name'] }}</b> · {{ $l['level'] }}</span>
          @endforeach
        </div>
        @endif

        <div style="height:26px;"></div>

        <div class="fp-card">
          <h2>What this company guarantees</h2>
          <div class="fp-facts">
            <div class="fp-fact"><div class="k">Warranty</div><div class="v ok">{{ $summary['warranty_months'] ? $summary['warranty_months'] . ' ' . ((int) $summary['warranty_months'] === 1 ? 'month' : 'months') : 'as quoted' }}</div></div>
            <div class="fp-fact"><div class="k">Invoice</div><div class="v {{ $summary['invoice'] ? 'ok' : '' }}">{{ $summary['invoice'] ? 'Always' : 'Proof of service' }}</div></div>
            <div class="fp-fact"><div class="k">Fachora Protect</div>@if($summary['protect'])<div class="v ok">Available</div>@else<div class="v no">Not available</div>@endif</div>
            <div class="fp-fact"><div class="k">Liability insurance</div>@if($summary['insured'])<div class="v ok">Current</div>@else<div class="v no">Not provided</div>@endif</div>
            @if($summary['radius_km'])
            <div class="fp-fact"><div class="k">Service area</div><div class="v">up to {{ $summary['radius_km'] }} km</div></div>
            @endif
          </div>
          <div class="fp-note">
            A Fachora Verified company has been checked in the public registers (CEIDG/KRS, VAT white list), has a confirmed e-mail and phone number, identity and bank account verified by Mollie, and has accepted the <a href="{{ route('fachora.standard') }}" style="color:var(--brand);font-weight:500;">Fachora Standard</a>.
            @if($summary['protect']) With <a href="{{ route('fachora.protect') }}" style="color:var(--brand);font-weight:500;">Fachora Protect</a> you only pay once the work is done — the money waits with the payment provider.@endif
          </div>
        </div>

        @if(! empty($rates))
        <div class="fp-card">
          <h2>Indicative rates</h2>
          <table class="fp-rates">
            @if(! empty($rates['callout']))<tr><td>Call-out</td><td>{{ \App\Support\Fachora::money((float) $rates['callout']) }}</td></tr>@endif
            @if(! empty($rates['first_hour']))<tr><td>First hour of work</td><td>{{ \App\Support\Fachora::money((float) $rates['first_hour']) }}</td></tr>@endif
            @if(! empty($rates['extra_hour']))<tr><td>Each additional hour</td><td>{{ \App\Support\Fachora::money((float) $rates['extra_hour']) }}</td></tr>@endif
          </table>
          <div class="fp-note">Gross amounts, provided by the company as a guide. The binding price is the one in the offer you receive before any work starts. Extra work only with your approval.</div>
        </div>
        @endif

        @if($summary['description'])
        <div class="fp-card">
          <h2>About the company</h2>
          <div class="fp-desc">{{ $summary['description'] }}</div>
        </div>
        @endif

        <div class="fp-card">
          <h2>Reviews @if($ratingCount > 0)<span style="color:var(--text-3);font-weight:500;font-size:15px;">({{ $ratingCount }})</span>@endif</h2>
          @if($reviews->isEmpty())
            <p style="color:var(--text-2);margin:0;font-size:14.5px;line-height:1.65;">This company has no reviews on Fachora yet. Only customers who completed a job can leave a review — so there are no bought or invented reviews on Fachora.</p>
          @else
            @foreach($reviews as $r)
              <div class="fp-review">
                <div class="top">
                  <span class="fp-stars" aria-label="{{ $r->rating }} out of 5">{{ str_repeat('★', (int) $r->rating) }}<span style="color:var(--border-strong);">{{ str_repeat('★', 5 - (int) $r->rating) }}</span></span>
                  <span>{{ $r->job?->title ? $r->job->title . ' · ' : '' }}{{ $r->created_at?->translatedFormat('j F Y') }} · job through Fachora</span>
                </div>
                @if($r->text)<div class="text">{{ $r->text }}</div>@endif
                @if($r->reply)
                  <div class="fp-reply"><b>Reply from the company</b>{{ $r->reply }}</div>
                @endif
              </div>
            @endforeach
          @endif
        </div>

        <div class="fp-card">
          <h2>How it works</h2>
          <div class="fp-steps">
            <div class="fp-step"><b>1. Describe the job</b>A few sentences and photos. Your request goes to up to {{ (int) config('fachora.max_offers') }} Fachora Verified companies nearby — including this one.</div>
            <div class="fp-step"><b>2. Compare offers</b>Price up front, date, warranty. You choose — no phone calls, no haggling.</div>
            <div class="fp-step"><b>3. Pay after the work</b>With Fachora Protect the money waits with the payment provider and reaches the company only after your "OK".</div>
          </div>
        </div>
      </div>

      <aside class="fp-side">
        <div class="fp-cta">
          <h2>Ask for a quote</h2>
          <p>We do not show a phone number — you send your request through Fachora. The company replies with a price and a date, and you decide without any obligation.</p>
          <a href="{{ $intakeUrl }}" class="btn btn-primary btn-lg">Ask for a quote →</a>
          <ul>
            <li>Free, no account needed</li>
            <li>Price agreed before any work starts</li>
            @if($summary['protect'])<li>Payment through Fachora Protect — after completion</li>@endif
            <li>Reply usually within {{ (int) config('fachora.offer_hours') }} hours</li>
          </ul>
        </div>
        <div class="fp-note" style="padding:0 6px;">Already have a job on Fachora? Use the "My job" link from the e-mail we sent you.</div>
      </aside>
    </div>
  </div>
</section>

<div class="fp-bar" aria-hidden="true">
  <a href="{{ $intakeUrl }}" class="btn btn-primary">Ask for a quote →</a>
</div>

@else
{{-- ===================== INACTIVE PROFILE (register data, unclaimed / in progress) ===================== --}}
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
      <div class="fp-status">{{ $summary['status'] === 'suspended' ? 'Profile temporarily suspended' : 'Profile not active' }}</div>
      <h1 style="font-size:clamp(24px,4vw,34px);margin:0 0 10px;color:var(--brand-darker);">{{ $summary['name'] }}</h1>
      <div class="fp-meta" style="margin-bottom:16px;">
        @if($summary['city'])<span>📍 {{ $summary['city'] }}</span>@endif
        @if(! empty($catNames))<span>{{ implode(' · ', $catNames) }}</span>@endif
      </div>

      @if($summary['status'] === 'suspended')
        <p style="color:var(--text-2);line-height:1.7;margin:0 0 14px;">This profile is temporarily suspended and the company is currently not receiving jobs through Fachora. A suspension follows from the <a href="{{ route('fachora.standard') }}" style="color:var(--brand);font-weight:500;">Fachora Standard</a> (for example an expired check or unresolved complaints) and is reviewed again after the set period.</p>
      @else
        <p style="color:var(--text-2);line-height:1.7;margin:0 0 14px;"><strong>This profile is not active yet.</strong> The data comes from the public business register (CEIDG or KRS): name, town and type of activity. The company has not claimed its profile and has not passed Fachora's verification — so we do not mark it as Fachora Verified and we do not send it any jobs.</p>
        <p style="color:var(--text-2);line-height:1.7;margin:0 0 14px;">We do not publish contact details of companies that have not registered with us, and we only e-mail them a request from a customer who chose them, never advertising. Being listed in a register does not mean a company has been checked — <a href="{{ route('fachora.standard') }}" style="color:var(--brand);font-weight:500;">Fachora Verified</a> means more than that.</p>
      @endif

      <div style="margin-top:22px;padding:22px;border-radius:14px;background:var(--brand-tint);border:1px solid var(--brand-border);">
        <div style="font-family:var(--font-display);font-weight:700;font-size:18px;color:var(--brand-darker);margin-bottom:6px;">Is this your company?</div>
        <p style="color:var(--text-2);font-size:14.5px;line-height:1.6;margin:0 0 14px;">Claim the profile, complete your details and pass verification. No subscription, no registration fee — a commission only on completed jobs.</p>
        <a href="{{ $claimUrl }}" class="btn btn-primary btn-lg">Is this your company? Claim the profile →</a>
        <div style="font-size:13px;color:var(--text-3);margin-top:12px;">Did you receive a letter from us with a code? Enter it on the next page.</div>
      </div>
    </div>

    <div class="fp-card" style="background:var(--bg);">
      <h2 style="font-size:18px;">Looking for a {{ ! empty($catNames) ? mb_strtolower($catNames[0]) : 'tradesperson' }}?</h2>
      <p style="color:var(--text-2);font-size:14.5px;line-height:1.65;margin:0 0 14px;">Leave a request — we will pass it to Fachora Verified companies near you. Price up front, secure payment, warranty.</p>
      <a href="{{ $intakeUrl }}" class="btn btn-secondary">Leave a request</a>
      @if(! empty($categories))
        <a href="{{ route('fachora.category', $categories[0]['key']) }}" class="btn btn-ghost">Verified companies: {{ $categories[0]['name'] }}</a>
      @endif
    </div>
  </div>
</section>
@endif
@endsection
