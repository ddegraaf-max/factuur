@extends('layouts.marketing')

{{-- Company panel demo, English version: one click into the panel of the fictional company Hydro-Serwis Nowak with jobs in every stage (App\Services\Fachora\DemoBuilder). Nothing is sent; the environment is removed after 24 hours. --}}

@section('title', 'Company panel demo — see Fachora from the inside')
@section('description', 'One click into the panel of a fictional Fachora Verified company: new jobs to price, a quote waiting for the customer, a visit tomorrow, a job in progress, acceptance and reviews. No account, no card, nothing is sent.')

@push('styles')
@include('fachora.partials.styles')
<style>
  .demo-wrap { max-width: 960px; margin: 0 auto; }
  .demo-start { text-align: center; padding: 6px 0 34px; }
  .demo-note { color: var(--text-3); font-size: 13.5px; margin-top: 14px; }
  .demo-feats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 8px; }
  @media (max-width: 860px) { .demo-feats { grid-template-columns: 1fr; } }
  .demo-feat { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 20px; }
  .demo-feat h2 { font-size: 15px; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
  .demo-feat p { color: var(--text-2); font-size: 13.5px; margin: 0; line-height: 1.6; }
  .demo-feat svg { color: var(--brand); flex: none; }
  .demo-safe { background: var(--surface-2); border: 1px solid var(--border); border-radius: 12px; padding: 18px 22px; margin-top: 26px; font-size: 13.5px; color: var(--text-2); line-height: 1.6; }
  .demo-safe b { color: var(--text); }
  .demo-safe ul { margin: 8px 0 0; padding-left: 20px; }
  .demo-after { text-align: center; margin-top: 34px; }
  .demo-after p { color: var(--text-2); margin-bottom: 16px; }
  .demo-after .btn { margin: 4px; }
</style>
@endpush

@section('content')

<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Demo</div>
    <h1>The Fachora company panel. The real thing, with sample jobs.</h1>
    <p class="lead">One click and you are inside the panel of the fictional company <strong>Hydro-Serwis Nowak</strong> from Warsaw: two new jobs are waiting for your price, one quote for the customer's decision, a visit tomorrow at 10:00, one job in progress and one awaiting acceptance. Click around, quote, mark work as done. Nothing reaches real customers.</p>
  </div>
</section>

<section class="section" style="padding-top:24px;">
  <div class="container demo-wrap">

    <div class="demo-start">
      @if (session('error'))
        <div style="background:var(--brand-tint);border:1px solid var(--brand-border);color:var(--brand-darker);border-radius:10px;padding:12px 16px;margin-bottom:18px;font-size:14px;">
          {{ session('error') }}
        </div>
      @endif

      <form method="POST" action="{{ route('demo.start') }}">
        @csrf
        <button type="submit" class="btn btn-primary btn-lg">Run the demo →</button>
      </form>
      <div class="demo-note">No account · no card · no e-mails to anyone</div>
    </div>

    <div class="demo-feats">
      <div class="demo-feat">
        <h2>@include('fachora.partials.icon', ['name' => 'clock', 'size' => 18]) New jobs</h2>
        <p>Two jobs are waiting for your price: a dripping tap in Mokotów and a boiler with error E03, written in English and translated for you. You have {{ config('fachora.offer_hours') }} hours to respond.</p>
      </div>
      <div class="demo-feat">
        <h2>@include('fachora.partials.icon', ['name' => 'card', 'size' => 18]) Quote and choice</h2>
        <p>A quote for an air-conditioning installation (3,200 zł) is waiting for the customer's decision. See what your offer looks like and what the customer sees before choosing.</p>
      </div>
      <div class="demo-feat">
        <h2>@include('fachora.partials.icon', ['name' => 'pin', 'size' => 18]) Visit and work in progress</h2>
        <p>A radiator replacement scheduled for tomorrow, paid through Fachora Protect. A burst riser in progress, with a proposed extra for the customer to approve.</p>
      </div>
      <div class="demo-feat">
        <h2>@include('fachora.partials.icon', ['name' => 'check', 'size' => 18]) Acceptance and payout</h2>
        <p>A boiler service marked as done: the customer has {{ config('fachora.auto_accept_days') }} days to accept, after which Protect pays out automatically. The fee is visible straight away.</p>
      </div>
      <div class="demo-feat">
        <h2>@include('fachora.partials.icon', ['name' => 'star', 'size' => 18]) Reviews</h2>
        <p>Four reviews, average 4.75, one of them in English. Reply to a review the way your company would.</p>
      </div>
      <div class="demo-feat">
        <h2>@include('fachora.partials.icon', ['name' => 'shield', 'size' => 18]) Verification</h2>
        <p>A Fachora Verified profile with the full set of checks: register, VAT, e-mail, phone, the Standard, payout account and liability insurance.</p>
      </div>
    </div>

    <div class="demo-safe">
      <b>Safe to try.</b> The demo is a separate environment just for you:
      <ul>
        <li><b>No e-mail or text message</b> reaches anyone, even when you send a quote or mark work as done. The customers in the demo are made up.</li>
        <li>You type nothing in and <b>pay nothing</b>; Fachora Protect is simulated here.</li>
        <li>After 24 hours the environment is removed; you can also leave it straight away with the button in the panel.</li>
      </ul>
    </div>

    <div class="demo-after">
      <p>Your company? Register it, or claim your profile with the code from our letter.</p>
      <a href="{{ route('fachora.apply') }}" class="btn btn-secondary">Register your company</a>
      <a href="{{ route('fachora.claim') }}" class="btn btn-secondary">I have a letter code</a>
    </div>

  </div>
</section>

@endsection
