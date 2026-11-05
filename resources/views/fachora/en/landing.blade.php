@extends('layouts.marketing')
{{-- Fachora home page, English version (APP_BRAND=fachora, visitor chose EN), house style 1.105.0 ("A good
     tradesperson. No stress."): navy hero with the search bar and photo, category tiles with photos
     (public/brand/fachora/cat/<key>.jpg; gradient when there is none), four steps next to an example profile.
     The customer describes the job and picks up to max_offers companies nearby (Fachora Verified on top, companies
     with good Google reviews and register companies clearly marked). Verified companies get the request from us;
     a register company is contacted by the customer directly (Fachora sends it nothing). The customer pays the
     company after the work; Fachora Protect (a Stripe reservation) is shown only when fachora_protect() is on.
     Same structure as the Polish page; numbers come from config/fachora.php. --}}
@php
    $protect = fachora_protect();
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
    $photo = fn (string $key) => file_exists(public_path("brand/fachora/cat/{$key}.jpg")) ? "/brand/fachora/cat/{$key}.jpg" : null;
@endphp
@section('title', 'Fachora — a good tradesperson, no stress. Pick your own company in Poland: plumber, electrician, locksmith and more, in English')
@section('description', 'Find trusted local tradespeople for work in your home, flat or business in Poland. Describe the job and Fachora shows you companies near you: Fachora Verified, companies with good Google reviews and companies from the business register. You choose who receives your request, compare prices and pay only after the work. Free for customers, in English.')

@push('styles')
@include('fachora.partials.styles')
@endpush

@section('content')

<!-- HERO -->
<section class="fh-hero">
  <div class="container fh-hero-grid">
    <div class="fh-hero-text">
      <h1>A good tradesperson.<br><span class="accent">No stress.</span></h1>
      <p class="fh-hero-sub">Find trusted local tradespeople for work in your home, flat or business, in English. Describe what needs doing and pick yourself who receives your request: Fachora Verified companies, companies with good Google reviews and companies from the business register.</p>
      <form class="fh-search" method="get" action="{{ route('fachora.intake') }}" role="search" aria-label="Find a tradesperson">
        <label>
          <span class="fi">@include('fachora.partials.icon', ['name' => 'tools', 'size' => 20])</span>
          <span class="lbl">What do you need?</span>
          <select name="kategoria">
            <option value="">What service do you need?</option>
            @foreach(config('fachora.categories') as $key => $cat)
              <option value="{{ $key }}">{{ $cat['en'] }}</option>
            @endforeach
          </select>
        </label>
        <label>
          <span class="fi">@include('fachora.partials.icon', ['name' => 'pin', 'size' => 20])</span>
          <span class="lbl">Where?</span>
          <input type="text" name="miasto" placeholder="City or postcode" autocomplete="address-level2">
        </label>
        <button type="submit" class="btn btn-primary btn-lg">@include('fachora.partials.icon', ['name' => 'search', 'size' => 18]) Find a tradesperson</button>
      </form>
      <ul class="fh-usp3">
        <li><span class="ic">@include('fachora.partials.icon', ['name' => 'tag', 'size' => 20])</span><span>Free<small>for customers</small></span></li>
        <li><span class="ic">@include('fachora.partials.icon', ['name' => 'shield', 'size' => 20])</span><span>Verified<small>companies and tradespeople</small></span></li>
        <li><span class="ic">@include('fachora.partials.icon', ['name' => 'card', 'size' => 20])</span><span>Payment<small>after the work is done</small></span></li>
      </ul>
    </div>
    <div class="fh-hero-visual">
      <img src="/brand/fachora/hero-fachowiec.jpg" alt="Smiling tradesperson in work clothes with the Fachora logo" width="668" height="420" fetchpriority="high">
      <div class="fh-float fh-float-1"><span class="ic">@include('fachora.partials.icon', ['name' => 'check', 'size' => 18])</span><div><b>Verified company</b><span>Fachora Verified</span></div></div>
      <div class="fh-float fh-float-2"><span class="ic star">★</span><div><b>Good Google reviews</b><span>companies rated 4.5 and up</span></div></div>
      <div class="fh-float fh-float-3"><span class="ic shield">@include('fachora.partials.icon', ['name' => 'shield', 'size' => 18])</span><div><b>{{ $warranty }}-month warranty</b><span>from Fachora Verified companies</span></div></div>
    </div>
  </div>
</section>

<!-- HOW YOU PICK THE COMPANIES -->
<section class="fh-pickband">
  <div class="container">
    <div class="fh-pick" aria-label="How you pick the companies">
      <div><b>up to {{ $candidates }}</b><span>companies near you: Fachora Verified at the top, companies with good Google reviews and register companies clearly marked.</span></div>
      <div><b>max {{ $maxOffers }}</b><span>you tick yourself and confirm your number with an SMS code. Only they receive your request.</span></div>
      <div><b>1</b><span>you choose after comparing prices. Only then does that company see your address and number.</span></div>
    </div>
  </div>
</section>

<!-- CATEGORIES -->
<section class="section" id="uslugi" style="padding:64px 0 72px;">
  <div class="container">
    <div class="fh-sec-head">
      <h2>How can we help?</h2>
      <a href="{{ route('fachora.intake') }}">See all services →</a>
    </div>
    <div class="fh-tiles">
      @foreach(config('fachora.categories') as $key => $cat)
        <a href="{{ route('fachora.intake', ['kategoria' => $key]) }}" class="fh-tile">
          <span class="ph{{ $photo($key) ? ' has-photo' : '' }}"@if($photo($key)) style="background-image:url('{{ $photo($key) }}')"@endif></span>
          <span class="ic">@include('fachora.partials.icon', ['name' => $cat['icon'], 'size' => 22])</span>
          <span class="tx"><b>{{ $cat['en'] }}</b><span class="ex">{{ $cat['examples']['en'] }}</span><span class="go">@include('fachora.partials.icon', ['name' => 'arrow', 'size' => 18])</span></span>
        </a>
      @endforeach
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="section section-alt" id="jak-to-dziala">
  <div class="container fh-how">
    <div class="fh-how-intro">
      <h2>How does it work?</h2>
      <p>Post your job in a few simple steps and find a trusted tradesperson near you. No account, no calling around: you decide who gets to see your job at all.</p>
      <div class="fh-how-steps">
        <div class="fh-how-step"><span class="ic"><i>1</i>@include('fachora.partials.icon', ['name' => 'edit', 'size' => 26])</span><h3>Post your job</h3><p>Describe what you need and where the work is to be done. You confirm by e-mail and receive a link to your job.</p></div>
        <div class="fh-how-step"><span class="ic"><i>2</i>@include('fachora.partials.icon', ['name' => 'offers', 'size' => 26])</span><h3>Pick the companies</h3><p>We show up to {{ $candidates }} companies near you. You tick up to {{ $maxOffers }} and confirm your number with an SMS code. Only they get the request.</p></div>
        <div class="fh-how-step"><span class="ic"><i>3</i>@include('fachora.partials.icon', ['name' => 'calendar', 'size' => 26])</span><h3>Compare offers</h3><p>Verified companies have {{ $offerHours }} hours to give a price and a date. A register company you contact yourself, with a ready-made message.</p></div>
        <div class="fh-how-step"><span class="ic"><i>4</i>@include('fachora.partials.icon', ['name' => 'thumbs', 'size' => 26])</span><h3>Get it done and rate</h3><p>Only the chosen company sees your address. You pay after the work, a Verified company issues an invoice, and you leave a review.</p></div>
      </div>
      <div class="fh-more" style="text-align:left;margin-top:24px;"><a href="{{ route('fachora.how') }}">See all the steps in detail →</a></div>
    </div>
    <div class="fh-excard">
      <span class="ex">Example</span>
      <span class="fv-badge">Fachora Verified</span>
      <div class="top">
        <div class="av">IP</div>
        <div><div class="name">Instalacje Przykładowe</div><div class="meta">Plumber · Heating · Heat pumps</div></div>
      </div>
      <div class="row"><span class="st">★</span><b>4.8</b><span>· rating from confirmed jobs</span></div>
      <div class="row">@include('fachora.partials.icon', ['name' => 'pin', 'size' => 16])<span>Łódź and surroundings, up to 25 km</span></div>
      <div class="row">@include('fachora.partials.icon', ['name' => 'lang', 'size' => 16])<span>polski · English · Deutsch</span></div>
      <div class="row">@include('fachora.partials.icon', ['name' => 'invoice', 'size' => 16])<span>Invoice and {{ $warranty }}-month warranty</span></div>
      <a href="#profil" class="btn btn-secondary">See what a profile shows →</a>
    </div>
  </div>
</section>

@if($protect)
<!-- FACHORA PROTECT (only when switched on) -->
<section class="fh-band" id="protect">
  <div class="container">
    <div class="eyebrow">Fachora Protect · optional</div>
    <h2>The money waits until you say: done.</h2>
    <p class="lead">For a job with a Verified company you can reserve the agreed amount up front. The company knows the payment is certain, and you know you pay only for finished work. You can also pay the company directly after the work, without a reservation.</p>
    <div class="fh-band-grid">
      <div class="fh-flow">
        <div class="fh-flow-item">
          <div class="nr">1</div>
          <div><b>You reserve the amount</b><p>BLIK, Przelewy24 or card. The payment is handled by Stripe, an e-money institution supervised by the Central Bank of Ireland.</p></div>
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
          <b>Dispute?</b> The money stays blocked until the matter is settled. The company has {{ $disputeHours }} hours to respond; if it does not, the decision goes your way. Fachora never holds your money: the whole payment is handled by Stripe.
        </div>
        <div style="margin-top:18px;"><a href="{{ route('fachora.protect') }}" class="btn btn-white">How Fachora Protect works</a></div>
      </div>
    </div>
  </div>
</section>
@endif

<!-- FACHORA VERIFIED -->
<section class="section" id="verified">
  <div class="container">
    <div class="fh-split">
      <div>
        <div class="eyebrow">Fachora Verified</div>
        <h2>Not every company makes it onto Fachora.</h2>
        <p>Before a company receives the Fachora Verified mark and its first request, we check it in the registers and with our payment partner. Only then do we vouch for what you see in its profile.</p>
        <p>Companies with good Google reviews are shown with their rating and number of reviews; companies from public registers that we have not checked yet are shown as a <b>register profile</b>: clearly marked, without the Verified mark and without our guarantees. We send them nothing. If you pick such a company, you see its public contact details from the register and get in touch yourself. Any company can claim its profile and go through verification.</p>
        <p><a href="{{ route('fachora.standard') }}" class="btn btn-secondary">The Fachora Standard: ten rules</a></p>
      </div>
      <div class="fh-checks" style="grid-template-columns:1fr;">
        <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'invoice', 'size' => 20])</span><div><b>Register and tax number</b><p>The company exists, is active in CEIDG or KRS, and its NIP and VAT status check out.</p></div></div>
        @if($protect)<div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'shield', 'size' => 20])</span><div><b>Identity and bank account</b><p>The person representing the company and its bank account are confirmed by Stripe.</p></div></div>@endif
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
        <ul class="ticks"><li>Invoice</li><li>{{ $warranty }}-month warranty</li>@if($protect)<li>Fachora Protect</li>@endif<li>Insured</li></ul>
        <div class="rates">Indicative rates: call-out 80 zł · first hour 150 zł</div>
      </div>
      <div class="fh-company">
        <span class="ex">Example</span>
        <span class="fv-badge">Fachora Verified</span>
        <div class="name">Elektro Wzór</div>
        <div class="meta">Electrician · Warsaw, up to 30 km</div>
        <div class="rating"><b>★ 4.9</b> · rating from confirmed jobs</div>
        <div class="langs"><span>🇵🇱 polski</span><span>🇬🇧 English</span><span>🇺🇦 українська</span></div>
        <ul class="ticks"><li>Invoice</li><li>{{ $warranty }}-month warranty</li>@if($protect)<li>Fachora Protect</li>@endif<li>Responds within 24 h</li></ul>
        <div class="rates">Indicative rates: call-out 60 zł · first hour 140 zł</div>
      </div>
      <div class="fh-company">
        <span class="ex">Example</span>
        <span class="fv-badge">Fachora Verified</span>
        <div class="name">Zamki Demo</div>
        <div class="meta">Locksmith · Kraków, up to 20 km</div>
        <div class="rating"><b>★ 4.7</b> · rating from confirmed jobs</div>
        <div class="langs"><span>🇵🇱 polski</span><span>🇬🇧 English</span></div>
        <ul class="ticks"><li>Invoice</li><li>{{ $warranty }}-month warranty</li>@if($protect)<li>Fachora Protect</li>@endif<li>Emergencies 24/7</li></ul>
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
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'shield', 'size' => 22])</span><h3>Verified companies</h3><p>Register, tax number,{{ $protect ? ' identity, bank account,' : '' }} phone and e-mail checked before a company gets the Verified mark and its first request.</p></div>
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'clock', 'size' => 22])</span><h3>Reliable appointment</h3><p>The date is part of the job. The company confirms it and can only cancel for a stated reason. A no-show after a reservation means removal from the platform.</p></div>
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'card', 'size' => 22])</span>@if($protect)<h3>Safe payments</h3><p>With Fachora Protect you pay only after approving the work. In a dispute the money waits for the decision.</p>@else<h3>Pay after the work</h3><p>You know the price up front and pay only when the work is done, directly to the company. A Verified company always issues an invoice.</p>@endif</div>
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
        <div class="faq-content">For customers Fachora is free: you do not pay for posting a job, for picking companies{{ $protect ? ', for the offers or for Fachora Protect' : ' or for the offers' }}. You pay only the price agreed with the company. Fachora's fees are paid by the company.</div>
      </details>
      <details class="faq-item">
        <summary>Who will see my job?<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Only the companies you tick yourself, at most {{ $maxOffers }}. Verified companies receive the request from us; a register company you message yourself, because Fachora sends it nothing. Your address and phone number go only to the company you finally choose.</div>
      </details>
      <details class="faq-item">
        <summary>What if the work is done badly?<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">For a job with a Verified company you open a dispute on your job page, with photos and a description. The company has {{ $disputeHours }} hours to respond and Fachora decides: {{ $protect ? 'payout, partial payout, a repair or a refund' : 'a repair on a new date or a lower price' }}.@if($protect) With Fachora Protect the money stays blocked the whole time.@endif</div>
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
