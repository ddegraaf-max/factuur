@extends('layouts.marketing')

{{-- How Fachora works for the customer, English version: six steps from request to warranty, what you see per company, disputes, costs. --}}

@php
  $warranty = config('fachora.default_warranty_months');
  $maxOffers = config('fachora.max_offers');
  $offerHours = config('fachora.offer_hours');
  $acceptDays = config('fachora.auto_accept_days');
  $disputeHours = config('fachora.dispute_hours');
  $confirmDays = config('fachora.confirm_days');
  $cancelHours = config('fachora.cancel_free_hours');
@endphp

@section('title', 'How Fachora works — from request to warranty in six steps')
@section('description', 'You describe the job, confirm by e-mail, receive offers from up to ' . $maxOffers . ' verified companies within ' . $offerHours . ' hours, choose, reserve the payment with Fachora Protect, approve the work and get an invoice and a warranty. Free for customers.')

@push('styles')
@include('fachora.partials.styles')
@endpush

@section('content')

<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">For customers</div>
    <h1>How Fachora works</h1>
    <p class="lead">From your first description to a warranty on the finished work. Six steps, and at each one you know what is happening and who sees what.</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="fh-steps list">
      <div class="fh-step">
        <div class="nr">1</div>
        <div>
          <h3>You describe the job</h3>
          <p>No account needed. Choose a category, enter your city or postcode and describe in your own words what is going on, in English if you like. Photos help companies quote without a visit.</p>
          <ul>
            <li><b>When:</b> now (emergency), this week, within a month, or a specific date.</li>
            <li><b>Language:</b> Polish, English, German, Dutch or Ukrainian. The job only goes to companies that speak it.</li>
            <li><b>Contact:</b> your e-mail address. The exact address of the job is only shared after you choose a company.</li>
          </ul>
        </div>
      </div>
      <div class="fh-step">
        <div class="nr">2</div>
        <div>
          <h3>You confirm by e-mail</h3>
          <p>You receive a message with a link. One click and the job is live; the link takes you to your "My job" page, where you see offers, choose, pay and approve the work. An unconfirmed request disappears after {{ $confirmDays }} days.</p>
          <span class="tag">Keep that link, it is your key to the job</span>
        </div>
      </div>
      <div class="fh-step">
        <div class="nr">3</div>
        <div>
          <h3>Offers from verified companies</h3>
          <p>Fachora offers the job to up to {{ $maxOffers }} Fachora Verified companies in your area that speak your language. They have {{ $offerHours }} hours to respond. Every offer contains:</p>
          <ul>
            <li>the price: fixed, or an hourly rate plus call-out fee,</li>
            <li>a proposed date and time,</li>
            <li>the company's notes, for instance what needs checking on site.</li>
          </ul>
          <p style="margin-top:10px;">Next to each offer you see the company's profile: its rating from completed jobs, languages, warranty and what has been verified.</p>
        </div>
      </div>
      <div class="fh-step">
        <div class="nr">4</div>
        <div>
          <h3>You choose a company and share your address</h3>
          <p>One click. Price, scope, date, warranty and the obligation to invoice go into the job confirmation. Only now does the chosen company see your address and contact details; the other companies are told the job is closed.</p>
        </div>
      </div>
      <div class="fh-step">
        <div class="nr">5</div>
        <div>
          <h3>You reserve the payment, or pay afterwards</h3>
          <p>With <a href="{{ route('fachora.protect') }}">Fachora Protect</a> you reserve the agreed amount by BLIK, Przelewy24 or card. The payment is processed by Stripe; Fachora never holds your money. The company receives it only after your approval.</p>
          <p style="margin-top:10px;">You can also pay the company directly once the work is done. Then there is no blocked payment and no payment-backed dispute resolution, but the company's verification, the invoice and the company's warranty still apply.</p>
          <span class="tag">Up to {{ $cancelHours }} hours before the appointment you can cancel free of charge</span>
        </div>
      </div>
      <div class="fh-step">
        <div class="nr">6</div>
        <div>
          <h3>Work, approval, invoice, warranty</h3>
          <p>The company confirms the appointment and reports when it starts. If more turns out to be needed on site, the company adds a separate line and you approve it on your page. Without your approval, extra work does not exist.</p>
          <ul>
            <li>After the company marks the job as done you have <b>{{ $acceptDays }} days</b> to approve or open a dispute. No response counts as approval.</li>
            <li>After approval the company issues an <b>invoice</b> and receives its payout.</li>
            <li>The warranty runs for <b>{{ $warranty }} months</b> as standard. A complaint in that time goes back to the same job.</li>
            <li>You leave a <b>review</b>. Reviews come only from jobs completed through Fachora and the company cannot delete them.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Company profile</div>
      <h2>What you see for every company</h2>
      <p>Everything you need to compare offers. No phone number: contact and payment go through Fachora, so everything is on record.</p>
    </div>
    <div class="fh-checks">
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'shield', 'size' => 20])</span><div><b>The Fachora Verified mark</b><p>The company passed verification: register, tax number, identity, bank account, phone, e-mail and the Fachora Standard.</p></div></div>
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'star', 'size' => 20])</span><div><b>Rating and number of jobs</b><p>Only from jobs completed through Fachora. Reviews with the company's reply, never deleted by the company.</p></div></div>
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'lang', 'size' => 20])</span><div><b>Languages and level</b><p>Polish, English, German, Dutch, Ukrainian: native, good or basic.</p></div></div>
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'card', 'size' => 20])</span><div><b>Indicative rates</b><p>Call-out, first hour, each further hour. The final price is always in the offer for your job.</p></div></div>
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'invoice', 'size' => 20])</span><div><b>Invoice, warranty, Protect</b><p>Every Verified company issues an invoice and gives a warranty. You also see whether it has liability insurance.</p></div></div>
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'pin', 'size' => 20])</span><div><b>Area and response time</b><p>City and working radius, and how quickly the company usually responds to jobs.</p></div></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="fh-split">
      <div>
        <div class="eyebrow">When something goes wrong</div>
        <h2>Fachora settles the dispute, not whoever shouts loudest</h2>
        <p>You open a dispute on your job page: a description, photos, what you expect. The company has {{ $disputeHours }} hours to respond. Then Fachora looks at the job confirmation, the photos and the messages and decides: full payout, partial payout, a repair on a new date, or a refund.</p>
        <p>If the company does not respond in time, the decision goes your way. With Fachora Protect the money is blocked throughout, so nobody has to recover it from anyone.</p>
        <p>Two justified complaints in 90 days suspend a company; a no-show after a reservation or an attempt to work around Fachora means removal from the platform. Details in the <a href="{{ route('fachora.standard') }}" style="color:var(--brand);font-weight:600;">Fachora Standard</a>.</p>
      </div>
      <div>
        <div class="fh-note">
          <b>What does it cost?</b><br>
          For customers Fachora is free. You do not pay for posting a job, for the offers, for Fachora Protect or for dispute resolution. You pay only the price agreed with the company, exactly as it appears in the job confirmation. The commission on a completed job is paid by the company.
        </div>
        <div class="fh-note warm" style="margin-top:14px;">
          <b>Your job goes only to the companies you choose.</b><br>
          You pick up to {{ config('fachora.max_offers') }} companies near you: Fachora Verified companies are at the top of the list, companies from the register are clearly marked. If there is nobody in your area, we tell you straight away instead of promising offers that will not come.
        </div>
      </div>
    </div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Ready? Describe the job.</h2>
    <p>A few minutes, no account. Offers within {{ $offerHours }} hours.</p>
    <div class="fh-cta-row">
      <a href="{{ route('fachora.intake') }}" class="btn btn-primary btn-lg">Find a tradesperson</a>
      <a href="{{ route('fachora.protect') }}" class="btn btn-white btn-lg">Fachora Protect</a>
    </div>
  </div>
</section>

@endsection
