@extends('layouts.marketing')
{{-- Fachora home page, English version (APP_BRAND=fachora, visitor chose EN): for foreigners living in Poland.
     The customer describes the job and picks up to max_offers companies nearby (Fachora Verified on top, register
     companies clearly marked). Verified companies get the request from us; a register company is contacted by the
     customer directly (Fachora sends it nothing). Payment through Fachora Protect or directly after the work.
     Same structure as the Polish page; numbers come from config/fachora.php. --}}
@php
    $fee = config('fachora.fee_percent');
    $intro = config('fachora.intro_fee_percent');
    $lead = (bool) config('fachora.lead_pricing');
    $introDays = config('fachora.intro_days');
    $warranty = config('fachora.default_warranty_months');
    $maxOffers = config('fachora.max_offers');
    $offerHours = config('fachora.offer_hours');
    $acceptDays = config('fachora.auto_accept_days');
    $disputeHours = config('fachora.dispute_hours');
    $candidates = config('fachora.choice_candidates');
@endphp
@section('title', 'Fachora — pick your own tradesperson in Poland. Plumber, electrician, locksmith and more, in English')
@section('description', 'Describe the job and Fachora shows you companies near you: Fachora Verified companies, checked by us, and companies from the business register. You choose who receives your request, compare prices and pay only after the work. Free for customers, in English.')

@push('styles')
@include('fachora.partials.styles')
@endpush

@section('content')

<!-- HERO -->
<section class="hero">
  <div class="container hero-inner">
    <div class="eyebrow">Tradespeople across Poland. You decide.</div>
    <h1>You choose <span class="accent">who gets your job.</span></h1>
    <p class="hero-sub">
      Describe what needs doing, in English if you like, and we show you companies near you: Fachora Verified companies, checked by us, and companies from the Polish business register. You tick who receives your request, compare prices and pay only after the work.
    </p>
    <form class="fh-search" method="get" action="{{ route('fachora.intake') }}" role="search" aria-label="Find a tradesperson">
      <label>
        <span class="lbl">What do you need?</span>
        <select name="kategoria">
          <option value="">Choose a service</option>
          @foreach(config('fachora.categories') as $key => $cat)
            <option value="{{ $key }}">{{ $cat['en'] }}</option>
          @endforeach
        </select>
      </label>
      <label>
        <span class="lbl">Where?</span>
        <input type="text" name="miasto" placeholder="City or postcode" autocomplete="address-level2">
      </label>
      <button type="submit" class="btn btn-primary btn-lg">Find a tradesperson</button>
    </form>
    <ul class="fh-ticks">
      <li>Free for customers</li>
      <li>No account, no calling around</li>
      <li>Your address only for the company you choose</li>
      <li>Invoice and {{ $warranty }}-month warranty from Verified companies</li>
      <li>English-speaking companies</li>
    </ul>
    <div class="fh-pick" aria-label="How you pick the companies">
      <div><b>up to {{ $candidates }}</b><span>companies near you: Fachora Verified at the top, register companies clearly marked.</span></div>
      <div><b>max {{ $maxOffers }}</b><span>you tick yourself and confirm your number with an SMS code. Only they receive your request.</span></div>
      <div><b>1</b><span>you choose after comparing prices. Only then does that company see your address and number.</span></div>
    </div>
  </div>
</section>

<!-- CATEGORIES -->
<section class="section section-alt" id="uslugi">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Services</div>
      <h2>What do you need?</h2>
      <p>{{ count(config('fachora.categories')) }} categories of services. Pick yours and the job form is already filled in.</p>
    </div>
    <div class="fh-cats">
      @foreach(config('fachora.categories') as $key => $cat)
        <a href="{{ route('fachora.intake', ['kategoria' => $key]) }}" class="fh-cat">
          <span class="ic">@include('fachora.partials.icon', ['name' => $cat['icon'], 'size' => 26])</span>
          <b>{{ $cat['en'] }}</b>
          <span>{{ $cat['examples']['en'] }}</span>
        </a>
      @endforeach
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="section" id="jak-to-dziala">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">How it works</div>
      <h2>From problem to tradesperson in four steps</h2>
      <p>No account, no calling around, no guessing. You decide who gets to see your job at all.</p>
    </div>
    <div class="fh-steps">
      <div class="fh-step">
        <div class="nr">1</div>
        <h3>Describe the job</h3>
        <p>What, where and when, in your own words and your own language. Add photos if they help. You confirm by e-mail and receive a secret link to your job.</p>
      </div>
      <div class="fh-step">
        <div class="nr">2</div>
        <h3>Pick the companies</h3>
        <p>We show up to {{ $candidates }} companies near you: Fachora Verified at the top, register companies clearly marked. You tick up to {{ $maxOffers }}. You confirm your phone number with an SMS code, so companies know the request is real.</p>
      </div>
      <div class="fh-step">
        <div class="nr">3</div>
        <h3>Compare prices</h3>
        <p>Verified companies receive the request from us and have {{ $offerHours }} hours to give a price and a date; you see them on your job page. A register company you contact yourself: we show you its public details from the register and a ready-made message in Polish.</p>
      </div>
      <div class="fh-step">
        <div class="nr">4</div>
        <h3>Choose one and approve the work</h3>
        <p>Only now does the chosen company see your address and number. With Fachora Protect you pay after approving the work. A Verified company issues an invoice and gives a {{ $warranty }}-month warranty; at the end you leave a review.</p>
      </div>
    </div>
    <div class="fh-more"><a href="{{ route('fachora.how') }}">See all the steps in detail →</a></div>
  </div>
</section>

<!-- FACHORA PROTECT -->
<section class="fh-band" id="protect">
  <div class="container">
    <div class="eyebrow">Fachora Protect · optional</div>
    <h2>The money waits until you say: done.</h2>
    <p class="lead">For a job with a Verified company you can reserve the agreed amount up front. The company knows the payment is certain, and you know you pay only for finished work. You can also pay the company directly after the work, without a reservation.</p>
    <div class="fh-band-grid">
      <div class="fh-flow">
        <div class="fh-flow-item">
          <div class="nr">1</div>
          <div><b>You reserve the amount</b><p>BLIK, Przelewy24 or card. The payment is handled by Mollie, a licensed payment institution supervised by De Nederlandsche Bank.</p></div>
        </div>
        <div class="fh-flow-item">
          <div class="nr">2</div>
          <div><b>The company does the work</b><p>Extra work only as a separate line that you approve on your job page. No surprises at the end.</p></div>
        </div>
        <div class="fh-flow-item">
          <div class="nr">3</div>
          <div><b>You approve</b><p>You have {{ $acceptDays }} days after the company reports "done". If you raise nothing, the job counts as accepted.</p></div>
        </div>
        <div class="fh-flow-item">
          <div class="nr">4</div>
          <div><b>The company is paid</b><p>Only after your approval. Fachora's fees are paid by the company, not by you.</p></div>
        </div>
      </div>
      <div>
        <div class="fh-band-card">
          <div class="cap"><span>Job confirmation</span><span class="pill">Payment reserved</span></div>
          <table>
            <tr><td>Gas boiler repair</td><td class="r">480.00 zł</td></tr>
            <tr><td>Extra work</td><td class="r">only with your approval</td></tr>
            <tr><td>Appointment</td><td class="r">Friday 9:00–11:00</td></tr>
            <tr><td>Warranty</td><td class="r">{{ $warranty }} months</td></tr>
            <tr><td>Invoice</td><td class="r">always</td></tr>
            <tr class="tot"><td>You pay after approval</td><td class="r">480.00 zł</td></tr>
          </table>
          <div class="ex">Example. Amounts and dates are agreed with the company you choose.</div>
        </div>
        <div class="note">
          <b>Dispute?</b> The money stays blocked until the matter is settled. The company has {{ $disputeHours }} hours to respond; if it does not, the decision goes your way. Fachora never holds your money: the whole payment is handled by Mollie.
        </div>
        <div style="margin-top:18px;"><a href="{{ route('fachora.protect') }}" class="btn btn-white">How Fachora Protect works</a></div>
      </div>
    </div>
  </div>
</section>

<!-- FACHORA VERIFIED -->
<section class="section" id="verified">
  <div class="container">
    <div class="fh-split">
      <div>
        <div class="eyebrow">Fachora Verified</div>
        <h2>Not every company makes it onto Fachora.</h2>
        <p>Before a company receives the Fachora Verified mark and its first request, we check it in the registers and with our payment partner. Only then do we vouch for what you see in its profile.</p>
        <p>Companies from public registers that we have not checked yet are shown as a <b>register profile</b>: clearly marked, without the Verified mark and without our guarantees. We send them nothing. If you pick such a company, you see its public contact details from the register and get in touch yourself. Any company can claim its profile and go through verification.</p>
        <p><a href="{{ route('fachora.standard') }}" class="btn btn-secondary">The Fachora Standard: ten rules</a></p>
      </div>
      <div class="fh-checks" style="grid-template-columns:1fr;">
        <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'invoice', 'size' => 20])</span><div><b>Register and tax number</b><p>The company exists, is active in CEIDG or KRS, and its NIP and VAT status check out.</p></div></div>
        <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'shield', 'size' => 20])</span><div><b>Identity and bank account</b><p>The person representing the company and its bank account are confirmed by Mollie.</p></div></div>
        <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'check', 'size' => 20])</span><div><b>Phone, e-mail, terms</b><p>Number confirmed by SMS code, address by link, the Fachora Standard accepted with date and version.</p></div></div>
        <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'star', 'size' => 20])</span><div><b>Liability insurance: visible</b><p>If the company has a policy, you see it in the profile. When it expires the mark disappears until it is renewed.</p></div></div>
      </div>
    </div>
  </div>
</section>

<!-- EXAMPLE PROFILE -->
<section class="section section-alt" id="profil">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">What a company profile looks like</div>
      <h2>What you see for every company</h2>
      <p>The cards below are <b>examples</b>: they show the layout of a profile, not real companies. Ratings and rates in real profiles come only from completed jobs.</p>
    </div>
    <div class="fh-companies">
      <div class="fh-company">
        <span class="ex">Example</span>
        <span class="fv-badge">Fachora Verified</span>
        <div class="name">Instalacje Przykładowe</div>
        <div class="meta">Plumber · Heating · Łódź, up to 25 km</div>
        <div class="rating"><b>★ 4.8</b> · rating from confirmed jobs</div>
        <div class="langs"><span>🇵🇱 polski</span><span>🇬🇧 English</span><span>🇩🇪 Deutsch</span></div>
        <ul class="ticks"><li>Invoice</li><li>{{ $warranty }}-month warranty</li><li>Fachora Protect</li><li>Insured</li></ul>
        <div class="rates">Indicative rates: call-out 80 zł · first hour 150 zł</div>
      </div>
      <div class="fh-company">
        <span class="ex">Example</span>
        <span class="fv-badge">Fachora Verified</span>
        <div class="name">Elektro Wzór</div>
        <div class="meta">Electrician · Warsaw, up to 30 km</div>
        <div class="rating"><b>★ 4.9</b> · rating from confirmed jobs</div>
        <div class="langs"><span>🇵🇱 polski</span><span>🇬🇧 English</span><span>🇺🇦 українська</span></div>
        <ul class="ticks"><li>Invoice</li><li>{{ $warranty }}-month warranty</li><li>Fachora Protect</li><li>Responds within 24 h</li></ul>
        <div class="rates">Indicative rates: call-out 60 zł · first hour 140 zł</div>
      </div>
      <div class="fh-company">
        <span class="ex">Example</span>
        <span class="fv-badge">Fachora Verified</span>
        <div class="name">Zamki Demo</div>
        <div class="meta">Locksmith · Kraków, up to 20 km</div>
        <div class="rating"><b>★ 4.7</b> · rating from confirmed jobs</div>
        <div class="langs"><span>🇵🇱 polski</span><span>🇬🇧 English</span></div>
        <ul class="ticks"><li>Invoice</li><li>{{ $warranty }}-month warranty</li><li>Fachora Protect</li><li>Emergencies 24/7</li></ul>
        <div class="rates">Indicative rates: call-out 90 zł · lock opening from 200 zł</div>
      </div>
    </div>
    <p style="text-align:center;font-size:13.5px;color:var(--text-3);margin-top:18px;">A Verified company does not show a phone number: the request, prices and agreements go through your job page, so everything is on record and the warranty applies. With a register company it is the other way round: you get its public details and call or write yourself.</p>
  </div>
</section>

<!-- FOUR PROMISES -->
<section class="section" id="dlaczego">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Why Fachora</div>
      <h2>Four things we promise</h2>
      <p>They apply to Fachora Verified companies and to jobs handled through your job page. With a register company you make your own arrangements.</p>
    </div>
    <div class="fh-usps">
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'shield', 'size' => 22])</span><h3>Verified companies</h3><p>Register, tax number, identity, bank account, phone and e-mail checked before a company gets the Verified mark and its first request.</p></div>
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'clock', 'size' => 22])</span><h3>Reliable appointment</h3><p>The date is part of the job. The company confirms it and can only cancel for a stated reason. A no-show after a reservation means removal from the platform.</p></div>
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'card', 'size' => 22])</span><h3>Safe payments</h3><p>With Fachora Protect you pay only after approving the work. In a dispute the money waits for the decision.</p></div>
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'star', 'size' => 22])</span><h3>Warranty on the work</h3><p>{{ $warranty }} months as standard. A complaint goes back to the job and the company has to respond within {{ $disputeHours }} hours.</p></div>
    </div>
  </div>
</section>

<!-- FOR COMPANIES -->
<section class="section section-alt" id="dla-firm">
  <div class="container">
    <div class="fh-split">
      <div>
        <div class="eyebrow">For companies</div>
        @if ($lead)
          <h2>New customers in your area. No subscription.</h2>
          <p>The customer picks your company. You view the request for free and pay only for the one you want to answer: {{ (int) config('fachora.lead_min') }} to {{ (int) config('fachora.lead_max') }} zł net, depending on the size of the job. The first one is free. Plus a profile with reviews from real customers and the Fachora Verified mark.</p>
        @else
          <h2>New customers in your area. You pay only for completed jobs.</h2>
          <p>0 zł subscription, 0 zł to register, 0 zł per job. A {{ $fee }}% commission only on completed, approved work, and just {{ $intro }}% for the first {{ $introDays }} days. Plus a profile with reviews from real customers and the Fachora Verified mark.</p>
        @endif
        <p>No messages without your consent: requests from us go only to companies that have claimed their profile. A register company is contacted by the customer directly.</p>
        <div class="fh-cta-row" style="justify-content:flex-start;">
          <a href="{{ route('fachora.for-companies') }}" class="btn btn-primary">See the offer for companies</a>
          <a href="{{ route('fachora.claim') }}" class="btn btn-secondary">Claim your company profile</a>
        </div>
      </div>
      <div class="fh-prices" style="grid-template-columns:1fr 1fr;">
        <div class="fh-price"><div class="v">0 <small>zł</small></div><b>subscription</b><span>no monthly fee</span></div>
        @if ($lead)
          <div class="fh-price"><div class="v">0 <small>zł</small></div><b>viewing a request</b><span>description, photos and date for free</span></div>
          <div class="fh-price hl"><div class="v">{{ (int) config('fachora.lead_min') }}–{{ (int) config('fachora.lead_max') }}<small> zł</small></div><b>to unlock</b><span>the first request is free</span></div>
        @else
          <div class="fh-price"><div class="v">0 <small>zł</small></div><b>per job</b><span>no charge for the contact or the offer</span></div>
          <div class="fh-price hl"><div class="v">{{ $fee }}<small>%</small></div><b>on completed work</b><span>{{ $intro }}% for the first {{ $introDays }} days</span></div>
        @endif
        <div class="fh-price"><div class="v">0 <small>zł</small></div><b>verification</b><span>the Fachora Verified mark at no charge</span></div>
      </div>
    </div>
  </div>
</section>

<!-- LANGUAGES -->
<section class="section" id="jezyki" style="padding-top:0;">
  <div class="container">
    <div class="section-header" style="margin-bottom:28px;">
      <div class="eyebrow" style="margin-bottom:16px;">Don't speak Polish?</div>
      <h2>Choose the language you want to deal with the company in</h2>
      <p>You pick a language when posting the job. Verified companies only receive it if they speak that language, and you see their level in the profile. We translate your description into Polish automatically, so you can contact a register company with a ready-made message in Polish.</p>
    </div>
    <div class="fh-langs">
      @foreach(config('fachora.languages') as $code => $lang)
        <span class="fh-lang"><span class="flag" aria-hidden="true">{{ $lang['flag'] }}</span>{{ $lang['en'] }}</span>
      @endforeach
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="section section-alt" id="faq">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Frequently asked</div>
      <h2>Three things people ask most</h2>
    </div>
    <div class="faq-list">
      <details class="faq-item">
        <summary>What does it cost?<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">For customers Fachora is free: you do not pay for posting a job, for picking companies, for the offers or for Fachora Protect. You pay only the price agreed with the company. Fachora's fees are paid by the company.</div>
      </details>
      <details class="faq-item">
        <summary>Who will see my job?<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Only the companies you tick yourself, at most {{ $maxOffers }}. Verified companies receive the request from us; a register company you message yourself, because Fachora sends it nothing. Your address and phone number go only to the company you finally choose.</div>
      </details>
      <details class="faq-item">
        <summary>What if the work is done badly?<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">For a job with a Verified company you open a dispute on your job page, with photos and a description. The company has {{ $disputeHours }} hours to respond and Fachora decides: payout, partial payout, a repair or a refund. With Fachora Protect the money stays blocked the whole time.</div>
      </details>
    </div>
    <div class="fh-more"><a href="{{ route('pl.faq') }}">All questions and answers →</a></div>
  </div>
</section>

<!-- CTA -->
<section class="cta-final">
  <div class="container cta-inner">
    <h2>Describe the job. You pick the companies.</h2>
    <p>A few minutes to describe it, a list of companies near you, and a price agreed before the tradesperson arrives.</p>
    <div class="fh-cta-row">
      <a href="{{ route('fachora.intake') }}" class="btn btn-primary btn-lg">Find a tradesperson</a>
      <a href="{{ route('fachora.how') }}" class="btn btn-white btn-lg">How it works</a>
    </div>
  </div>
</section>

@endsection
