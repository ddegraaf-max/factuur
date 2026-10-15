@extends('layouts.marketing')

{{-- Fachora home page, English version (APP_BRAND=fachora, visitor chose EN): for foreigners living in Poland.
     Same structure as the Polish page; numbers come from config/fachora.php. --}}

@php
  $fee = config('fachora.fee_percent');
  $intro = config('fachora.intro_fee_percent');
  $introDays = config('fachora.intro_days');
  $warranty = config('fachora.default_warranty_months');
  $maxOffers = config('fachora.max_offers');
  $offerHours = config('fachora.offer_hours');
  $acceptDays = config('fachora.auto_accept_days');
  $disputeHours = config('fachora.dispute_hours');
@endphp

@section('title', 'Fachora — verified tradespeople in Poland. Plumber, electrician, locksmith and more, with a clear price and a guarantee')
@section('description', 'Describe the job and Fachora offers it to a few verified companies near you. Compare prices, choose, agree the price up front, pay safely with Fachora Protect and always get an invoice and a warranty. Free for customers. English-speaking tradespeople available.')

@push('styles')
@include('fachora.partials.styles')
@endpush

@section('content')

<!-- HERO -->
<section class="hero">
  <div class="container hero-inner">
    <div class="eyebrow">Verified companies. Safe services.</div>
    <h1>Trusted tradespeople <span class="accent">across Poland.</span></h1>
    <p class="hero-sub">
      Clear price. Reliable appointment. Guarantee. Describe what needs doing and we offer the job to a few Fachora Verified companies near you. You compare the offers and choose, in English, without calling around.
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
      <li>Price agreed up front</li>
      <li>Invoice, always</li>
      <li>{{ $warranty }}-month warranty</li>
      <li>English-speaking companies</li>
    </ul>
  </div>
</section>

<!-- CATEGORIES -->
<section class="section section-alt" id="uslugi">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Services</div>
      <h2>What do you need?</h2>
      <p>{{ count(config('fachora.categories')) }} categories in which Fachora Verified companies work. Pick yours and the job form is already filled in.</p>
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
      <p>No account, no phone calls in a language you do not speak, no guessing what it will cost.</p>
    </div>
    <div class="fh-steps">
      <div class="fh-step">
        <div class="nr">1</div>
        <h3>Describe the job</h3>
        <p>What, where and when, in English. Add photos if they help. Confirm by e-mail and you get a private link to your job.</p>
      </div>
      <div class="fh-step">
        <div class="nr">2</div>
        <h3>Compare offers</h3>
        <p>The job goes to up to {{ $maxOffers }} Fachora Verified companies. Within {{ $offerHours }} hours you see prices, dates and ratings.</p>
      </div>
      <div class="fh-step">
        <div class="nr">3</div>
        <h3>Choose and fix the price</h3>
        <p>You pick a company. Price, scope, date and warranty go into the job confirmation. Only now does the company see your address.</p>
      </div>
      <div class="fh-step">
        <div class="nr">4</div>
        <h3>Approve the work</h3>
        <p>The company does the work, you approve it. You get an invoice and a warranty, the company gets paid. Then you leave a review.</p>
      </div>
    </div>
    <div class="fh-more"><a href="{{ route('fachora.how') }}">See all the steps in detail →</a></div>
  </div>
</section>

<!-- FACHORA PROTECT -->
<section class="fh-band" id="protect">
  <div class="container">
    <div class="eyebrow">Fachora Protect</div>
    <h2>The money waits until you say: done.</h2>
    <p class="lead">With Fachora Protect you reserve the agreed amount up front. The company knows payment is certain, and you know you only pay for work that is finished.</p>
    <div class="fh-band-grid">
      <div class="fh-flow">
        <div class="fh-flow-item">
          <div class="nr">1</div>
          <div><b>You reserve the amount</b><p>BLIK, Przelewy24 or card. The payment is processed by Stripe, a licensed payment institution.</p></div>
        </div>
        <div class="fh-flow-item">
          <div class="nr">2</div>
          <div><b>The company does the work</b><p>Extra work only as a separate line that you approve on your job page. No surprises at the end.</p></div>
        </div>
        <div class="fh-flow-item">
          <div class="nr">3</div>
          <div><b>You approve</b><p>You have {{ $acceptDays }} days after the company marks the job as done. If you do not respond, the job counts as accepted.</p></div>
        </div>
        <div class="fh-flow-item">
          <div class="nr">4</div>
          <div><b>The company is paid</b><p>Only after your approval. Fachora's commission is paid by the company, not by you.</p></div>
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
          <b>A dispute?</b> The money stays blocked until the matter is settled. The company has {{ $disputeHours }} hours to respond; if it does not, the decision goes your way. Fachora never holds your money: the whole payment is handled by Stripe.
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
        <h2>Not every company gets on Fachora.</h2>
        <p>Before a company receives the Fachora Verified mark and its first job, we check it in the public registers and with our payment partner. Only then do we vouch for what you see in its profile.</p>
        <p>Companies from public registers that we have not checked yet are shown as an <b>inactive profile</b>: no phone number, no jobs. We never send them e-mails or text messages. They can claim their own profile and go through verification.</p>
        <p><a href="{{ route('fachora.standard') }}" class="btn btn-secondary">The Fachora Standard: ten rules</a></p>
      </div>
      <div class="fh-checks" style="grid-template-columns:1fr;">
        <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'invoice', 'size' => 20])</span><div><b>Register and tax number</b><p>The company exists, is active in CEIDG or KRS, and its NIP and VAT status match.</p></div></div>
        <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'shield', 'size' => 20])</span><div><b>Identity and bank account</b><p>The person representing the company and its bank account are verified by Stripe.</p></div></div>
        <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'check', 'size' => 20])</span><div><b>Phone, e-mail, terms</b><p>Phone number confirmed by SMS code, e-mail by link, the Fachora Standard accepted with date and version.</p></div></div>
        <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'star', 'size' => 20])</span><div><b>Liability insurance: visible</b><p>If the company has a policy, you see it in the profile. When it expires, the mark disappears until it is renewed.</p></div></div>
      </div>
    </div>
  </div>
</section>

<!-- SAMPLE PROFILE -->
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
        <div class="name">Sample Installations</div>
        <div class="meta">Plumber · Heating · Łódź, up to 25 km</div>
        <div class="rating"><b>★ 4.8</b> · rating from confirmed jobs</div>
        <div class="langs"><span>🇵🇱 Polish</span><span>🇬🇧 English</span><span>🇩🇪 German</span></div>
        <ul class="ticks"><li>Invoice</li><li>{{ $warranty }}-month warranty</li><li>Fachora Protect</li><li>Insured</li></ul>
        <div class="rates">Indicative rates: call-out 80 zł · first hour 150 zł</div>
      </div>
      <div class="fh-company">
        <span class="ex">Example</span>
        <span class="fv-badge">Fachora Verified</span>
        <div class="name">Electro Example</div>
        <div class="meta">Electrician · Warsaw, up to 30 km</div>
        <div class="rating"><b>★ 4.9</b> · rating from confirmed jobs</div>
        <div class="langs"><span>🇵🇱 Polish</span><span>🇬🇧 English</span><span>🇺🇦 Ukrainian</span></div>
        <ul class="ticks"><li>Invoice</li><li>{{ $warranty }}-month warranty</li><li>Fachora Protect</li><li>Replies within 24 h</li></ul>
        <div class="rates">Indicative rates: call-out 60 zł · first hour 140 zł</div>
      </div>
      <div class="fh-company">
        <span class="ex">Example</span>
        <span class="fv-badge">Fachora Verified</span>
        <div class="name">Demo Locks</div>
        <div class="meta">Locksmith · Kraków, up to 20 km</div>
        <div class="rating"><b>★ 4.7</b> · rating from confirmed jobs</div>
        <div class="langs"><span>🇵🇱 Polish</span><span>🇬🇧 English</span></div>
        <ul class="ticks"><li>Invoice</li><li>{{ $warranty }}-month warranty</li><li>Fachora Protect</li><li>Emergencies 24/7</li></ul>
        <div class="rates">Indicative rates: call-out 90 zł · lock-out from 200 zł</div>
      </div>
    </div>
    <p style="text-align:center;font-size:13.5px;color:var(--text-3);margin-top:18px;">We do not show phone numbers. Communication and payment go through Fachora; that is what makes the guarantee work.</p>
  </div>
</section>

<!-- FOUR PROMISES -->
<section class="section" id="dlaczego">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Why Fachora</div>
      <h2>Four things we promise</h2>
    </div>
    <div class="fh-usps">
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'shield', 'size' => 22])</span><h3>Verified companies</h3><p>Register, tax number, identity, bank account, phone and e-mail checked before a company sees its first job.</p></div>
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'clock', 'size' => 22])</span><h3>Reliable appointment</h3><p>The date is part of the job. The company confirms it and may only cancel with a reason. A no-show after a reservation means removal from the platform.</p></div>
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'card', 'size' => 22])</span><h3>Safe payments</h3><p>With Fachora Protect you pay only after approving the work. In a dispute the money waits for the outcome.</p></div>
      <div class="fh-usp"><span class="ic">@include('fachora.partials.icon', ['name' => 'star', 'size' => 22])</span><h3>Guarantee on the work</h3><p>{{ $warranty }} months as standard. A complaint reopens the job and the company must respond within {{ $disputeHours }} hours.</p></div>
    </div>
  </div>
</section>

<!-- FOR COMPANIES -->
<section class="section section-alt" id="dla-firm">
  <div class="container">
    <div class="fh-split">
      <div>
        <div class="eyebrow">For companies</div>
        <h2>New customers in your area. You pay only for completed jobs.</h2>
        <p>0 zł subscription, 0 zł to register, 0 zł per job. A {{ $fee }}% commission only on work that is done and approved, and just {{ $intro }}% for your first {{ $introDays }} days. Plus free KSeF-ready invoicing software and a profile with reviews from real customers.</p>
        <div class="fh-cta-row" style="justify-content:flex-start;">
          <a href="{{ route('fachora.for-companies') }}" class="btn btn-primary">See the offer for companies</a>
          <a href="{{ route('fachora.claim') }}" class="btn btn-secondary">Claim your company profile</a>
        </div>
      </div>
      <div class="fh-prices" style="grid-template-columns:1fr 1fr;">
        <div class="fh-price"><div class="v">0 <small>zł</small></div><b>subscription</b><span>no monthly fee</span></div>
        <div class="fh-price"><div class="v">0 <small>zł</small></div><b>per job request</b><span>no charge for a lead or an offer</span></div>
        <div class="fh-price hl"><div class="v">{{ $fee }}<small>%</small></div><b>on completed work</b><span>{{ $intro }}% for the first {{ $introDays }} days</span></div>
        <div class="fh-price"><div class="v">0 <small>zł</small></div><b>invoicing software</b><span>KSeF-ready invoices, free while you are Verified</span></div>
      </div>
    </div>
  </div>
</section>

<!-- LANGUAGES -->
<section class="section" id="jezyki" style="padding-top:0;">
  <div class="container">
    <div class="section-header" style="margin-bottom:28px;">
      <div class="eyebrow" style="margin-bottom:16px;">No Polish? No problem.</div>
      <h2>Choose the language you want to speak with the company</h2>
      <p>You pick a language when you post the job. It only goes to companies that speak it, and you see their level in the profile.</p>
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
      <div class="eyebrow" style="margin-bottom:16px;">Frequently asked questions</div>
      <h2>The three questions we hear most</h2>
    </div>
    <div class="faq-list">
      <details class="faq-item">
        <summary>What does it cost?<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">For customers Fachora is free: no charge for posting a job, for the offers or for Fachora Protect. You pay only the price agreed with the company. The commission on a completed job is paid by the company.</div>
      </details>
      <details class="faq-item">
        <summary>Will I get the company's phone number?<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Not before you choose a company. Offers, questions and agreements go through your job page, so everything is written down and the guarantee applies. After you choose, the company receives your address and you can settle the details together.</div>
      </details>
      <details class="faq-item">
        <summary>What if the work is done badly?<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">You open a dispute on your job page with photos and a description. The company has {{ $disputeHours }} hours to respond and Fachora decides: payout, partial payout, repair or refund. With Fachora Protect the money stays blocked the whole time.</div>
      </details>
    </div>
    <div class="fh-more"><a href="{{ route('pl.faq') }}">All questions and answers →</a></div>
  </div>
</section>

<!-- CTA -->
<section class="cta-final">
  <div class="container cta-inner">
    <h2>Describe the job. We take care of the rest.</h2>
    <p>A few minutes to describe it, offers within {{ $offerHours }} hours, the price agreed before anyone turns up.</p>
    <div class="fh-cta-row">
      <a href="{{ route('fachora.intake') }}" class="btn btn-primary btn-lg">Find a tradesperson</a>
      <a href="{{ route('fachora.how') }}" class="btn btn-white btn-lg">How it works</a>
    </div>
  </div>
</section>

@endsection
