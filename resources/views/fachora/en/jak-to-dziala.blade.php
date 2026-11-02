@extends('layouts.marketing')
{{-- How Fachora works for the customer, English version: six steps from request to warranty. The customer picks
     the companies (Verified ones get the request from us, a register company is contacted by the customer),
     what you see per company, disputes, costs. Numbers from config/fachora.php. --}}
@php
    $warranty = config('fachora.default_warranty_months');
    $maxOffers = config('fachora.max_offers');
    $offerHours = config('fachora.offer_hours');
    $acceptDays = config('fachora.auto_accept_days');
    $disputeHours = config('fachora.dispute_hours');
    $confirmDays = config('fachora.confirm_days');
    $cancelHours = config('fachora.cancel_free_hours');
    $candidates = config('fachora.choice_candidates');
    $keepDays = (int) config('fachora.register_keep_days');
@endphp
@section('title', 'How Fachora works — you pick the companies, from request to warranty in six steps')
@section('description', 'You describe the job, confirm by e-mail, pick up to ' . $maxOffers . ' companies near you yourself (Fachora Verified and register companies), compare prices, choose one and pay after the work through Fachora Protect or directly. Free for customers.')

@push('styles')
@include('fachora.partials.styles')
@endpush

@section('content')

<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">For customers</div>
    <h1>How Fachora works</h1>
    <p class="lead">From your first description to a warranty on the finished work. Six steps, and at each one you know what is happening, who sees what and who contacts whom.</p>
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
            <li><b>Language:</b> Polish, English, German, Dutch or Ukrainian. Verified companies only receive the request if they speak that language; we translate your description into Polish automatically, so you can write to a register company in Polish.</li>
            <li><b>Contact:</b> your e-mail address and a phone number, which you confirm with an SMS code before the request goes out. The exact address of the job is only shared after you choose a company.</li>
          </ul>
        </div>
      </div>

      <div class="fh-step">
        <div class="nr">2</div>
        <div>
          <h3>You confirm by e-mail</h3>
          <p>You receive a message with a link. One click and the job is live; the link takes you to your "My job" page, where you pick companies, see prices, pay and approve the work. An unconfirmed request disappears after {{ $confirmDays }} days.</p>
          <p style="margin-top:10px;">If there are no companies on Fachora in your area yet, we look through the Polish business register around you. That usually takes a few minutes; you get an e-mail when the list is ready.</p>
          <span class="tag">Keep that link, it is your key to the job</span>
        </div>
      </div>

      <div class="fh-step">
        <div class="nr">3</div>
        <div>
          <h3>You pick the companies yourself</h3>
          <p>You see up to {{ $candidates }} companies near you. The order: Fachora Verified first, then rating, then distance; companies do not pay for a place on the list. For each one you see the profile, languages, distance and a "from the register" label if it is a company we have not checked yet. You tick up to {{ $maxOffers }} and send your request. Two kinds of company, two routes:</p>
          <ul>
            <li><b>A Fachora Verified company:</b> receives the request from us by e-mail, because it claimed its profile and agreed to receive jobs. It replies on your job page.</li>
            <li><b>A register company:</b> receives nothing from us. We show you its public contact details from the register (CEIDG) and a ready-made message in Polish with your job description; you call or write yourself. Details of register companies nobody picked are deleted after {{ $keepDays }} days.</li>
          </ul>
        </div>
      </div>

      <div class="fh-step">
        <div class="nr">4</div>
        <div>
          <h3>You compare prices and choose one</h3>
          <p>Verified companies have {{ $offerHours }} hours to respond. Every offer contains the price (fixed, or an hourly rate plus call-out fee), a proposed date and time, and the company's notes, for instance what needs checking on site. Next to each offer you see the profile: rating from completed jobs, languages, warranty and what has been verified.</p>
          <p style="margin-top:10px;">One click: you enter your address, and the price, scope, date, warranty and the obligation to invoice go into the job confirmation. Only now does the chosen company see your address and phone number; the other companies are told the job is closed. With a register company you arrange everything directly, outside Fachora.</p>
        </div>
      </div>

      <div class="fh-step">
        <div class="nr">5</div>
        <div>
          <h3>You reserve the payment, or pay afterwards</h3>
          <p>With <a href="{{ route('fachora.protect') }}">Fachora Protect</a> you reserve the agreed amount by BLIK, Przelewy24 or card. The payment is processed by Mollie, a licensed payment institution; Fachora never holds your money. The company receives it only after your approval.</p>
          <p style="margin-top:10px;">You can also pay the company directly once the work is done. Then there is no blocked payment and no payment-backed dispute resolution, but the verification, the invoice and the warranty of a Verified company still apply.</p>
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
            <li>After approval a Verified company issues an <b>invoice</b> and receives its payout.</li>
            <li>A Verified company's warranty runs for <b>{{ $warranty }} months</b> as standard. A complaint in that time goes back to the same job.</li>
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
      <p>Everything you need to compare offers. A Verified company: no phone number, contact and payment go through your job page, so everything is on record. A register company: public details from the register, shown only once you pick it.</p>
    </div>
    <div class="fh-checks">
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'shield', 'size' => 20])</span><div><b>The Fachora Verified mark</b><p>The company passed verification: register, tax number, identity, bank account, phone, e-mail and the Fachora Standard. Without the mark: a register profile.</p></div></div>
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'star', 'size' => 20])</span><div><b>Rating and number of jobs</b><p>Only from jobs completed through Fachora. Reviews with the company's reply, never deleted by the company.</p></div></div>
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'lang', 'size' => 20])</span><div><b>Languages and level</b><p>Polish, English, German, Dutch, Ukrainian: native, good or basic.</p></div></div>
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'card', 'size' => 20])</span><div><b>Indicative rates</b><p>Call-out, first hour, each further hour. The final price is always in the offer for your job.</p></div></div>
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'invoice', 'size' => 20])</span><div><b>Invoice, warranty, Protect</b><p>Every Verified company issues an invoice and gives a warranty. You also see whether it has liability insurance.</p></div></div>
      <div class="fh-check"><span class="ic">@include('fachora.partials.icon', ['name' => 'pin', 'size' => 20])</span><div><b>Area and distance</b><p>City, working radius and the distance to your job; for Verified companies also how quickly they usually respond.</p></div></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="fh-split">
      <div>
        <div class="eyebrow">When something goes wrong</div>
        <h2>Fachora settles the dispute, not whoever shouts loudest</h2>
        <p>For a job with a Verified company you open a dispute on your job page: a description, photos, what you expect. The company has {{ $disputeHours }} hours to respond. Then Fachora looks at the job confirmation, the photos and the messages and decides: full payout, partial payout, a repair on a new date, or a refund.</p>
        <p>If the company does not respond in time, the decision goes your way. With Fachora Protect the money is blocked throughout, so nobody has to recover it from anyone.</p>
        <p>Two justified complaints in 90 days suspend a company; a no-show after a reservation or an attempt to work around Fachora means removal from the platform. Details in the <a href="{{ route('fachora.standard') }}" style="color:var(--brand);font-weight:600;">Fachora Standard</a>. With a register company you make your own arrangements, so these rules do not cover it.</p>
      </div>
      <div>
        <div class="fh-note">
          <b>What does it cost?</b><br>
          For customers Fachora is free. You do not pay for posting a job, for picking companies, for the offers, for Fachora Protect or for dispute resolution. You pay only the price agreed with the company, exactly as it appears in the job confirmation. Fachora's fees are paid by the company.
        </div>
        <div class="fh-note warm" style="margin-top:14px;">
          <b>Your job is seen only by the companies you tick yourself.</b><br>
          At most {{ $maxOffers }} from a list of up to {{ $candidates }} companies near you. Verified companies receive it from us; Fachora sends nothing to a register company, you contact it yourself. If there is nobody in your area, we tell you straight away instead of promising offers that will not come.
        </div>
      </div>
    </div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Ready? Describe the job.</h2>
    <p>A few minutes, no account. Verified companies respond within {{ $offerHours }} hours.</p>
    <div class="fh-cta-row">
      <a href="{{ route('fachora.intake') }}" class="btn btn-primary btn-lg">Find a tradesperson</a>
      <a href="{{ route('fachora.protect') }}" class="btn btn-white btn-lg">Fachora Protect</a>
    </div>
  </div>
</section>

@endsection
