@extends('layouts.marketing')

{{-- For companies, English version: what you get, what it costs (0/0/0 + commission), three ways to join, and the honest note on which messages register companies can get (customer requests only). --}}

@php
  $fee = config('fachora.fee_percent');
  $intro = config('fachora.intro_fee_percent');
  $introDays = config('fachora.intro_days');
  $warranty = config('fachora.default_warranty_months');
  $maxOffers = config('fachora.max_offers');
  $offerHours = config('fachora.offer_hours');
  $acceptDays = config('fachora.auto_accept_days');
  $disputeHours = config('fachora.dispute_hours');
  $zl = fn ($n) => number_format($n, 0, '.', ',') . ' zł';
  $lead = (bool) config('fachora.lead_pricing');
  $leadMin = (int) config('fachora.lead_min');
  $leadMax = (int) config('fachora.lead_max');
  $leadPct = rtrim(rtrim(number_format((float) config('fachora.lead_percent'), 1, '.', ''), '0'), '.');
  $chev = '<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>';
@endphp

@section('title', $lead ? 'Fachora for companies — customers choose your company, you pay only for requests you respond to' : 'Fachora for companies — new customers in your area, you pay only for completed jobs')
@section('description', $lead
  ? 'The customer chooses your company and sends a request. Viewing a job is free, unlocking costs ' . $leadMin . ' to ' . $leadMax . ' zł depending on the size of the job, the first request is free. 0 zł subscription, no commission when the customer pays you directly, a profile with reviews.'
  : 'Jobs with description, photos and date by e-mail, your own price, the address once the customer chooses you, certain payment through Fachora Protect, a profile with reviews. 0 zł subscription, 0 zł per job, ' . $fee . '% commission on completed work, ' . $intro . '% for the first ' . $introDays . ' days.')

@push('styles')
@include('fachora.partials.styles')
@endpush

@section('content')

<section class="hero">
  <div class="container hero-inner">
    <div class="eyebrow">For companies</div>
    <h1>New customers in your area. <span class="accent">{{ $lead ? 'No subscription.' : 'You pay only for completed jobs.' }}</span></h1>
    <p class="hero-sub">{{ $lead ? 'The customer chooses your company and describes the job, you name your price and date. No subscription: you pay only for a request you respond to, and the first one is free.' : 'The customer describes the job, you name your price and date. If they choose you, the amount is reserved before you set off. No charge for contacts, no subscription.' }}</p>
    <div class="hero-ctas">
      <a href="{{ route('fachora.apply') }}" class="btn btn-primary btn-lg">Register your company</a>
      <a href="{{ route('fachora.claim') }}" class="btn btn-secondary btn-lg">I have a letter code: claim profile</a>
    </div>
    <div class="hero-trust">Already have an account? <a href="{{ route('login') }}" style="color:var(--brand);font-weight:600;">Log in to the company panel</a> · Want to see the panel first? <a href="{{ route('demo') }}" style="color:var(--brand);font-weight:600;">Run the demo</a></div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">What you get</div>
      <h2>Jobs, not "leads"</h2>
      <p>Every job is a real customer with a concrete problem, a confirmed e-mail address and a date. You respond only to the ones that suit you.</p>
    </div>
    <div class="fh-usps cols-3">
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'invoice', 'size' => 22])</span><h3>The job by e-mail</h3><p>Description, photos, date, the customer's language and the rough scope. You have {{ $offerHours }} hours to give a price and a date through a link, no login needed.</p></div>
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'card', 'size' => 22])</span><h3>Your price</h3><p>A fixed price or an hourly rate plus call-out. No race to the bottom: next to your price the customer sees your rating, languages and warranty.</p></div>
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'pin', 'size' => 22])</span><h3>The address once chosen</h3><p>Only when the customer picks you do you receive the address and contact details. You compete with up to {{ $maxOffers }} companies, not twenty.</p></div>
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'shield', 'size' => 22])</span><h3>Certain payment</h3><p>With Fachora Protect the customer reserves the amount before you arrive. After their approval (or {{ $acceptDays }} days without a response) Stripe transfers the money to your account.</p></div>
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'check', 'size' => 22])</span><h3>The customer chooses you</h3><p>The customer sees your profile, reviews, languages and distance and decides who gets the request. You do not race twenty companies for every phone call.</p></div>
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'lang', 'size' => 22])</span><h3>Profile with reviews and languages</h3><p>Reviews only after jobs completed through Fachora, with your reply. Speak English, German or Ukrainian? You get jobs from foreign customers that others cannot reach.</p></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">What it costs</div>
      @if ($lead)
      <h2>No subscription. You pay only for a request you want to answer</h2>
      <p>The customer chooses up to five companies. You view the job for free; if you want to give a price, you unlock it with one click (BLIK, Przelewy24, card) and receive an invoice.</p>
    </div>
    <div class="fh-prices">
      <div class="fh-price"><div class="v">0 <small>zł</small></div><b>subscription</b><span>no monthly fee and no point packages</span></div>
      <div class="fh-price"><div class="v">0 <small>zł</small></div><b>viewing a job</b><span>description, photos, date and town are always free to see</span></div>
      <div class="fh-price hl"><div class="v">{{ $leadMin }}–{{ $leadMax }}<small> zł</small></div><b>to unlock</b><span>{{ $leadPct }}% of the estimated value of the work; the first request is free</span></div>
      <div class="fh-price"><div class="v">0 <small>%</small></div><b>commission on direct payment</b><span>with Fachora Protect: {{ $fee }}% deducted at payout</span></div>
    </div>
    <div class="fh-note" style="max-width:760px;margin:24px auto 0;">
      <b>Example:</b> replacing a socket (estimate {{ $zl(200) }}) costs {{ $zl($leadMin) }}. Fitting a door (estimate {{ $zl(1200) }}) costs {{ $zl(max($leadMin, min($leadMax, round(1200 * (float) config('fachora.lead_percent') / 100)))) }}. A bathroom renovation (estimate {{ $zl(20000) }}) costs at most {{ $zl($leadMax) }}. You see the price before unlocking. A fake request? Report it within 7 days and we refund the fee.
    </div>
      @else
      <h2>Zero, zero, zero and a commission on completed work</h2>
      <p>We earn only when you earn. The commission is deducted at payout and you receive an invoice for it.</p>
    </div>
    <div class="fh-prices">
      <div class="fh-price"><div class="v">0 <small>zł</small></div><b>subscription</b><span>no monthly fee, no trial period, because there is nothing to trial</span></div>
      <div class="fh-price"><div class="v">0 <small>zł</small></div><b>registration</b><span>claiming your profile and verification cost nothing</span></div>
      <div class="fh-price"><div class="v">0 <small>zł</small></div><b>per job request</b><span>no charge for receiving a job or for making an offer</span></div>
      <div class="fh-price hl"><div class="v">{{ $fee }}<small>%</small></div><b>on completed, approved work</b><span>for the first {{ $introDays }} days after verification: {{ $intro }}%</span></div>
    </div>
    <div class="fh-note" style="max-width:760px;margin:24px auto 0;">
      <b>Example:</b> a job of {{ $zl(500) }} gross. The {{ $fee }}% commission is {{ $zl(500 * $fee / 100) }}, and {{ $zl(500 - 500 * $fee / 100) }} lands in your account. For the first {{ $introDays }} days the commission is {{ $zl(500 * $intro / 100) }}. A job that does not go ahead costs nothing.
    </div>
      @endif
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">How to join</div>
      <h2>Three ways in, one goal: a Fachora Verified profile</h2>
    </div>
    <div class="fh-ways">
      <div class="fh-way"><span class="k">You received a letter</span><h3>Enter the code from the letter</h3><p>We send companies from the public registers a letter by post with a code. The code links you to your profile; you confirm your e-mail and phone, and the profile is yours.</p><a href="{{ route('fachora.claim') }}" class="btn btn-primary">I have a letter code</a></div>
      <div class="fh-way"><span class="k">You are in our database</span><h3>Find your profile</h3><p>Your company may already be in our database as an inactive profile, with no phone number and no jobs. Look it up by tax number (NIP) and claim it.</p><a href="{{ route('fachora.claim') }}" class="btn btn-secondary">Find profile by NIP</a></div>
      <div class="fh-way"><span class="k">You are not in the database</span><h3>Register your company</h3><p>Enter your NIP, categories, working area and languages. We check the registers, ask you to onboard with Stripe and accept the Standard, then grant the Verified mark.</p><a href="{{ route('fachora.apply') }}" class="btn btn-secondary">Register your company</a></div>
    </div>
    <div class="fh-note warm" style="max-width:860px;margin:28px auto 0;">
      <b>No advertising, no newsletters.</b> If your company is in our database from a public register, the only thing you can receive from us is a request from a customer who chose your company, with an unsubscribe link. A claimed, verified profile gives you more: the Verified mark, a higher place in the list and jobs from your whole working area.
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="fh-split">
      <div>
        <div class="eyebrow">The Fachora Standard</div>
        <h2>Ten rules that protect you too</h2>
        <p>A clear price up front, extra work only with approval, an invoice every time, a reply to complaints within {{ $disputeHours }} hours. These rules keep the companies that spoil the market off the platform and give you a customer who knows what they are paying for.</p>
        <p>The Standard works both ways: a customer who does not approve the work within {{ $acceptDays }} days approves it automatically, and a cancellation later than {{ config('fachora.cancel_free_hours') }} hours before the appointment means you keep the call-out fee.</p>
        <a href="{{ route('fachora.standard') }}" class="btn btn-secondary">Read the Fachora Standard</a>
      </div>
      <ul class="fh-ticks left" style="display:grid;gap:10px;font-size:15px;">
        <li>The price in the job confirmation binds both sides</li>
        <li>Fachora settles disputes on the basis of the record and photos</li>
        <li>Reviews only from jobs completed through Fachora</li>
        <li>You have the right to reply to every review</li>
        <li>Suspension only after two justified complaints in 90 days</li>
        <li>{{ $warranty }}-month warranty as standard; a longer one is your edge in the profile</li>
      </ul>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Questions from companies</div>
      <h2>Frequently asked questions</h2>
    </div>
    <div class="faq-list">
      <details class="faq-item"><summary>Do I have to respond to every job?{!! $chev !!}</summary><div class="faq-content">No. You respond to the ones that suit you; for the others you click "not this time", ideally with a short reason. Response time counts, not the number of offers. A job you do not answer within {{ $offerHours }} hours simply expires.</div></details>
      <details class="faq-item"><summary>When do I get paid?{!! $chev !!}</summary><div class="faq-content">With Fachora Protect: after the customer's approval, or {{ $acceptDays }} days after you mark the job as done if the customer does not respond. Stripe transfers the amount less commission to the company bank account given at onboarding. Without Protect the customer pays you directly{{ $lead ? ', without any commission.' : ' and we settle the commission separately.' }}</div></details>
      <details class="faq-item"><summary>What if there turns out to be more work on site?{!! $chev !!}</summary><div class="faq-content">You add an "extra work" line with a price and description. The customer approves it on their job page and, with Protect, pays for it straight away. Without approval you do not do that work, because nobody will pay you for it.</div></details>
      <details class="faq-item"><summary>Do I need liability insurance?{!! $chev !!}</summary><div class="faq-content">It is not compulsory. If you have it, you add the policy to your profile and the customer sees an "insured" mark, which in practice helps win jobs. You get a reminder before the policy expires.</div></details>
      <details class="faq-item"><summary>Can I use the invoicing software for customers outside Fachora?{!! $chev !!}</summary><div class="faq-content">Yes, for all your customers. Invoices, quotes, contacts with NIP verification, XML for KSeF. The software is free for as long as your company has Verified status.</div></details>
      <details class="faq-item"><summary>What does the invoice look like?{!! $chev !!}</summary><div class="faq-content">Fachora is a service of Budomatch, a Polish company. We invoice unlock fees and commission with a Polish VAT invoice (23%). You receive the invoices by e-mail.</div></details>
    </div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>The first step is yours.</h2>
    <p>Claim your profile or register your company. Verification is free and you pay commission only from your first completed job.</p>
    <div class="fh-cta-row">
      <a href="{{ route('fachora.apply') }}" class="btn btn-primary btn-lg">Register your company</a>
      <a href="{{ route('fachora.claim') }}" class="btn btn-white btn-lg">Claim profile</a>
      <a href="{{ route('login') }}" class="btn btn-white btn-lg">Company panel</a>
    </div>
  </div>
</section>

@endsection
