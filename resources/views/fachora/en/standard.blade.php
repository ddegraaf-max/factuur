@extends('layouts.marketing')

{{-- The Fachora Standard, English version: ten rules for Fachora Verified companies, sanctions and how verification works. --}}

@php
  $warranty = config('fachora.default_warranty_months');
  $disputeHours = config('fachora.dispute_hours');
  $minRating = config('fachora.min_rating');
  $version = config('fachora.terms_version');
@endphp

@section('title', 'The Fachora Standard — ten rules every Fachora Verified company follows')
@section('description', 'A legally registered, active company, verified identity, clear prices up front, no extra work without approval, confirmed appointments, an invoice every time, a ' . $warranty . '-month warranty, complaints answered within ' . $disputeHours . ' hours, payment through Fachora Protect, a minimum rating of ' . $minRating . '. Sanctions and how verification works.')

@push('styles')
@include('fachora.partials.styles')
@endpush

@section('content')

<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">The Fachora Standard</div>
    <h1>Ten rules every Fachora Verified company follows.</h1>
    <p class="lead">Not every company gets on Fachora. The ones that do sign up to these rules, with a date and a version. Breaking them has consequences, from suspension to removal.</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="fh-rules">
      <div class="fh-rule"><span class="no">01</span><div><b>Legally registered, active company</b><p>Listed in CEIDG or KRS, a valid tax number (NIP), status active. We check this at verification and again periodically.</p></div></div>
      <div class="fh-rule"><span class="no">02</span><div><b>Identity of the representative verified</b><p>Identity and bank account confirmed by Stripe. Fachora does not store identity documents.</p></div></div>
      <div class="fh-rule"><span class="no">03</span><div><b>Clear prices up front</b><p>An offer contains a fixed price or an hourly rate plus call-out fee. The price in the job confirmation is binding.</p></div></div>
      <div class="fh-rule"><span class="no">04</span><div><b>No extra work without the customer's approval</b><p>Extra work is a separate line the customer approves on their page. Without approval there is no bill.</p></div></div>
      <div class="fh-rule"><span class="no">05</span><div><b>Appointment confirmed, cancellation only with a reason</b><p>The company confirms the appointment and keeps it. Cancelling requires a reason; a no-show after a reservation means removal from the platform.</p></div></div>
      <div class="fh-rule"><span class="no">06</span><div><b>Invoice or proof of service, always</b><p>After every job the customer receives an invoice. The company gets free KSeF-ready software to issue it.</p></div></div>
      <div class="fh-rule"><span class="no">07</span><div><b>Warranty on the work where applicable</b><p>{{ $warranty }} months as standard. A company may offer longer; shorter only where a warranty makes no sense for the kind of service, and it must say so in the offer.</p></div></div>
      <div class="fh-rule"><span class="no">08</span><div><b>Complaints answered within {{ $disputeHours }} hours</b><p>A dispute or complaint reaches the company through Fachora. No answer in time means the matter is decided in the customer's favour.</p></div></div>
      <div class="fh-rule"><span class="no">09</span><div><b>Payment according to Fachora rules (Protect)</b><p>A job that came through Fachora is settled through Fachora. Steering the customer towards cash "on the side" means removal from the platform.</p></div></div>
      <div class="fh-rule"><span class="no">10</span><div><b>Minimum rating of {{ $minRating }} and a response within 24 hours</b><p>The average review score from completed jobs (counted from five reviews) stays at {{ $minRating }} or above, and the company responds to new jobs within a day.</p></div></div>
    </div>
    <p style="text-align:center;font-size:13px;color:var(--text-3);margin-top:18px;">Standard version: {{ $version }}. The company accepts it in its panel, with the date and version recorded.</p>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="fh-split">
      <div>
        <div class="eyebrow">Sanctions</div>
        <h2>What happens when a company breaks the rules</h2>
        <p><b>Suspension for 30 days:</b> two justified complaints within 90 days, an expired insurance policy shown in the profile, or loss of active status in the register. The company disappears from results and is reassessed after 30 days. A rating below {{ $minRating }} is first a warning and a conversation; without improvement it also means suspension.</p>
        <p><b>Removal from the platform:</b> fraud, a no-show after a customer's reservation, or settling a Fachora job outside Fachora. The company's tax number is blocked for new applications.</p>
        <p>Every decision is explained in writing and the company can respond to it. Fachora decides, not an algorithm.</p>
      </div>
      <div class="fh-note">
        <b>What does this mean for you as a customer?</b><br>
        The Fachora Verified mark is not a claim by the company; it is the result of checks that continue after the mark is granted: register status, VAT status, insurance and ratings are rechecked periodically. A company that stops meeting the Standard stops receiving jobs.
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Verification</div>
      <h2>How a company becomes Fachora Verified</h2>
      <p>Most steps are automatic. The last one is done by a person.</p>
    </div>
    <div class="fh-steps cols-3">
      <div class="fh-step"><div class="nr">1</div><h3>Claiming the profile</h3><p>The company claims its profile from our database (a code from a letter, finding its profile, or applying) and confirms its e-mail and phone number with an SMS code.</p></div>
      <div class="fh-step"><div class="nr">2</div><h3>Registers</h3><p>Automatic checks in CEIDG or KRS and the VAT taxpayer list: the company is active, the tax number is valid, the business profile matches the category.</p></div>
      <div class="fh-step"><div class="nr">3</div><h3>Identity and bank account</h3><p>Onboarding with Stripe: the representative's identity and a bank account in the company's name. Fachora receives only the result, not the documents.</p></div>
      <div class="fh-step"><div class="nr">4</div><h3>Standard and terms</h3><p>The company accepts the Fachora Standard and the terms, fills in languages, working area, indicative rates and warranty. It can add its liability policy to the profile.</p></div>
      <div class="fh-step"><div class="nr">5</div><h3>Review by Fachora</h3><p>A member of the Fachora team checks everything and grants the Verified mark. Only then does the company see jobs and appear in results.</p></div>
      <div class="fh-step"><div class="nr">6</div><h3>Ongoing checks</h3><p>Register status, VAT status, insurance validity and the average rating are rechecked periodically. The company is reminded before anything expires.</p></div>
    </div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Do you run a company that works like this?</h2>
    <p>{{ config('fachora.lead_pricing') ? '0 zł subscription, you pay only for requests you respond to; the first one is free.' : '0 zł subscription, commission only on completed jobs.' }} We never send companies advertising or newsletters, so the first step is yours.</p>
    <div class="fh-cta-row">
      <a href="{{ route('fachora.for-companies') }}" class="btn btn-primary btn-lg">Offer for companies</a>
      <a href="{{ route('fachora.claim') }}" class="btn btn-white btn-lg">Claim your company profile</a>
    </div>
  </div>
</section>

@endsection
