@extends('layouts.marketing')

{{-- Fachora Protect, English version: why, how the money flows (Stripe, reservation, payout after approval, dispute), what is not covered, timelines, FAQ. --}}

@php
  $fee = config('fachora.fee_percent');
  $warranty = config('fachora.default_warranty_months');
  $offerHours = config('fachora.offer_hours');
  $acceptDays = config('fachora.auto_accept_days');
  $disputeHours = config('fachora.dispute_hours');
  $cancelHours = config('fachora.cancel_free_hours');
  $chev = '<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>';
@endphp

@section('title', 'Fachora Protect — you pay only when the work is done')
@section('description', 'Fachora Protect: you reserve the agreed amount through Stripe (BLIK, Przelewy24, card), the company does the work, you approve within ' . $acceptDays . ' days, and only then is the company paid. In a dispute the money stays blocked. Fachora never holds your money.')

@push('styles')
@include('fachora.partials.styles')
@endpush

@section('content')

<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Fachora Protect</div>
    <h1>The money waits until you say: done.</h1>
    <p class="lead">The agreed amount is reserved before the tradesperson arrives and paid out only after your approval. The company is certain of payment; you are certain you pay for finished work.</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Why</div>
      <h2>Three problems Protect solves</h2>
    </div>
    <div class="fh-steps cols-3">
      <div class="fh-step"><h3>"I'll come tomorrow"</h3><p>The tradesperson who never turns up. With Protect the company knows the money is reserved, and a no-show after a reservation means removal from the platform.</p></div>
      <div class="fh-step"><h3>The price that grows on site</h3><p>The amount in the job confirmation is final. Extra work is a separate line that you approve, or not. Without approval there is no bill.</p></div>
      <div class="fh-step"><h3>Cash with no invoice</h3><p>The payment goes through Stripe, so there is a record, and a Verified company always issues an invoice. That is the basis for the warranty and any complaint.</p></div>
    </div>
  </div>
</section>

<section class="fh-band">
  <div class="container">
    <div class="eyebrow">How the money flows</div>
    <h2>Four steps, no surprises</h2>
    <p class="lead">The payment is processed by Stripe, a licensed payment institution. Fachora does not run its own account for customer money and never holds it.</p>
    <div class="fh-band-grid">
      <div class="fh-flow">
        <div class="fh-flow-item"><div class="nr">1</div><div><b>Reservation</b><p>After choosing a company you pay the amount from the job confirmation by BLIK, Przelewy24 or card. The money goes to Stripe and is marked as reserved for this job.</p></div></div>
        <div class="fh-flow-item"><div class="nr">2</div><div><b>Work</b><p>The company confirms the appointment and reports start and finish. You approve any extra work on your job page; then you pay only that extra line.</p></div></div>
        <div class="fh-flow-item"><div class="nr">3</div><div><b>Approval</b><p>You have {{ $acceptDays }} days after the company marks the job as done. You approve or open a dispute. No response after {{ $acceptDays }} days counts as automatic approval.</p></div></div>
        <div class="fh-flow-item"><div class="nr">4</div><div><b>Payout</b><p>Stripe transfers the amount to the company, less Fachora's commission ({{ $fee }}%), which the company pays. You pay exactly what you agreed.</p></div></div>
      </div>
      <div>
        <div class="fh-band-card">
          <div class="cap"><span>Example job, 480 zł</span><span class="pill">Protect</span></div>
          <table>
            <tr><td>You reserve</td><td class="r">480.00 zł</td></tr>
            <tr><td>The company does the work</td><td class="r">money waits</td></tr>
            <tr><td>You approve</td><td class="r">within {{ $acceptDays }} days</td></tr>
            <tr><td>Fachora's commission ({{ $fee }}%)</td><td class="r">paid by the company</td></tr>
            <tr class="tot"><td>You paid</td><td class="r">480.00 zł</td></tr>
          </table>
          <div class="ex">Example. In a dispute the amount stays with Stripe until the matter is settled.</div>
        </div>
        <div class="note">
          <b>Dispute:</b> the money does not move until Fachora has decided. Possible outcomes: full payout, partial payout with the rest refunded, a repair on a new date, or a full refund to your account. The company has {{ $disputeHours }} hours to respond; without a response the decision goes your way.
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Scope</div>
      <h2>What Protect covers, and what it does not</h2>
      <p>Protect applies to what is written in the job confirmation. If something is not in there, we cannot rule on it.</p>
    </div>
    <div class="fh-table-wrap">
      <table class="fh-table">
        <thead><tr><th>Situation</th><th>With Fachora Protect</th><th>Paying afterwards, outside Fachora</th></tr></thead>
        <tbody>
          <tr><td>The company does not turn up</td><td class="yes">Full refund, company removed from the platform</td><td class="no">Nothing paid, new job</td></tr>
          <tr><td>Work done as confirmed</td><td class="yes">Payout after your approval</td><td class="no">You pay the company directly</td></tr>
          <tr><td>Work done badly or not finished</td><td class="yes">Dispute: money blocked, Fachora decides</td><td class="no">Complaint with the company, no blocked money</td></tr>
          <tr><td>Extra work approved on your page</td><td class="yes">Covered, you pay that extra line</td><td class="no">You settle it with the company</td></tr>
          <tr><td>Extra work without your approval</td><td class="no">Not covered: you do not pay for it</td><td class="no">Not covered</td></tr>
          <tr><td>Materials you bought yourself</td><td class="no">Not covered: that is between you and the shop</td><td class="no">Not covered</td></tr>
          <tr><td>Arrangements outside Fachora (phone, cash)</td><td class="no">Not covered: they are not in the confirmation</td><td class="no">Not covered</td></tr>
          <tr><td>A fault within the warranty period ({{ $warranty }} months)</td><td class="yes">Complaint goes back to the job, company responds within {{ $disputeHours }} h</td><td class="yes">The company's warranty still applies</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Timelines</div>
      <h2>Numbers worth knowing</h2>
    </div>
    <div class="fh-times">
      <div class="fh-time"><div class="v">{{ $offerHours }} h</div><span>for companies to send offers after you post the job</span></div>
      <div class="fh-time"><div class="v">{{ $cancelHours }} h</div><span>before the appointment: you can cancel free of charge</span></div>
      <div class="fh-time"><div class="v">{{ $acceptDays }} days</div><span>to approve after the job is marked done; then automatic approval</span></div>
      <div class="fh-time"><div class="v">{{ $disputeHours }} h</div><span>for the company to respond to a dispute or complaint</span></div>
      <div class="fh-time"><div class="v">{{ $warranty }} mo.</div><span>standard warranty on the finished work</span></div>
    </div>
    <div class="fh-note" style="max-width:860px;margin:28px auto 0;">
      <b>Honestly, about who holds the money.</b> Fachora is a service of Budomatch (Białka, Poland) and is not a payment institution. Reservation, safekeeping and payout are handled by Stripe under its European payment licence. Fachora sees the payment status and tells Stripe when to pay out or refund, but never has your money in an account of its own.
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Questions about Protect</div>
      <h2>Frequently asked questions</h2>
    </div>
    <div class="faq-list">
      <details class="faq-item"><summary>Does Protect cost extra?{!! $chev !!}</summary><div class="faq-content">No. For customers Fachora Protect costs nothing extra. The commission on a completed job ({{ $fee }}%) is paid by the company and deducted at payout.</div></details>
      <details class="faq-item"><summary>When does the money leave my account?{!! $chev !!}</summary><div class="faq-content">At the moment of reservation, so after you choose a company. BLIK and Przelewy24 have no "hold" like cards do, so the amount is collected and kept with Stripe for this job. The company has no access to it until you approve.</div></details>
      <details class="faq-item"><summary>What happens if I cancel?{!! $chev !!}</summary><div class="faq-content">Up to {{ $cancelHours }} hours before the appointment: a full refund. Later, the company may keep the call-out fee from its offer and the rest comes back to you. If the company cancels without a reason, you get a full refund and the company a note on its record that Fachora takes into account.</div></details>
      <details class="faq-item"><summary>I missed the {{ $acceptDays }}-day approval window. What now?{!! $chev !!}</summary><div class="faq-content">The job was treated as accepted and the company has been paid. The warranty still runs: within {{ $warranty }} months you can file a complaint on your job page and the company must respond within {{ $disputeHours }} hours.</div></details>
      <details class="faq-item"><summary>How long does a refund take?{!! $chev !!}</summary><div class="faq-content">Stripe sends the refund to the same payment method you used. It usually shows up within a few working days; with cards this sometimes depends on the issuing bank.</div></details>
      <details class="faq-item"><summary>Can I pay without Protect?{!! $chev !!}</summary><div class="faq-content">Yes, directly to the company once the work is done. Then there is no blocked money and no payment-backed dispute resolution. The company's verification, the invoice and the company's warranty still apply.</div></details>
    </div>
    <div class="fh-more"><a href="{{ route('pl.faq') }}">All questions and answers →</a></div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>A job with Protect starts the same way: with a description.</h2>
    <p>Describe what needs doing. You choose Protect once the offers are in.</p>
    <div class="fh-cta-row">
      <a href="{{ route('fachora.intake') }}" class="btn btn-primary btn-lg">Find a tradesperson</a>
      <a href="{{ route('fachora.how') }}" class="btn btn-white btn-lg">How it works</a>
    </div>
  </div>
</section>

@endsection
