@extends('layouts.marketing')

{{-- Frequently asked questions about Fachora, English version: for customers and for companies. One source for the list and the FAQPage schema. --}}

@php
  $fee = config('fachora.fee_percent');
  $intro = config('fachora.intro_fee_percent');
  $introDays = config('fachora.intro_days');
  $warranty = config('fachora.default_warranty_months');
  $maxOffers = config('fachora.max_offers');
  $offerHours = config('fachora.offer_hours');
  $acceptDays = config('fachora.auto_accept_days');
  $disputeHours = config('fachora.dispute_hours');
  $cancelHours = config('fachora.cancel_free_hours');
  $langs = implode(', ', array_map(fn ($l) => $l['en'], config('fachora.languages')));

  $faqGroups = [
    'For customers' => [
      ['Is Fachora free for customers?', 'Yes. You do not pay for posting a job, for the offers, for Fachora Protect or for dispute resolution. You pay only the price agreed with the company, exactly as it appears in the job confirmation. The commission on a completed job is paid by the company.'],
      ['How do you check companies?', 'Before a company receives the Fachora Verified mark we check: its entry and status in CEIDG or KRS plus its tax number (NIP) and VAT status, the identity of the person representing it and its bank account (through Stripe), its phone number (SMS code) and e-mail address (link), and its acceptance of the Fachora Standard with date and version. Liability insurance is not compulsory, but if the company has it you see that in the profile. The checks continue afterwards: register status, VAT, policy and ratings are rechecked periodically. Details: <a href="' . route('fachora.standard') . '">the Fachora Standard</a>.'],
      ['Will I get the company\'s phone number?', 'Not before you choose. Offers, questions and agreements go through your job page, so everything is on record and can serve as the basis for a warranty claim or a dispute. After you choose, the company receives your address and contact details to arrange the visit.'],
      ['I do not speak Polish. Is that a problem?', 'No. When you post a job you choose a language: ' . $langs . '. The job only goes to companies that declare that language, and in the profile you see their level (native, good, basic). The Fachora website is available in Polish and English.'],
      ['What is Fachora Protect and do I have to use it?', 'Fachora Protect means reserving the agreed amount before the tradesperson arrives. You pay by BLIK, Przelewy24 or card to Stripe, and the company receives the money only after your approval. In a dispute the money stays blocked. You do not have to use it: you can pay the company directly after the work, but then there is no blocked payment and no payment-backed dispute. Fachora never holds your money; the whole payment is handled by Stripe. More: <a href="' . route('fachora.protect') . '">Fachora Protect</a>.'],
      ['Can I cancel a job?', 'Yes. Up to ' . $cancelHours . ' hours before the appointment free of charge, with a full refund of the reservation. Later, the company may keep the call-out fee from its offer and the rest comes back to you. If the company cancels without a reason, you get a full refund and the company a note on its record that Fachora takes into account.'],
      ['What happens if the work is done badly?', 'On your job page you open a dispute: a description, photos, what you expect. The company has ' . $disputeHours . ' hours to respond. Fachora looks at the job confirmation, the photos and the messages and decides: full payout, partial payout, a repair on a new date, or a refund. If the company does not respond in time, the decision goes your way. You have ' . $acceptDays . ' days to raise it from the moment the company marks the job as done; after that the job counts as accepted, but the warranty still applies.'],
      ['How does the warranty work?', 'Every Verified company gives a warranty on the work, ' . $warranty . ' months as standard (an offer may say longer). You report a fault within that time on your job page; the complaint goes back to the same job and the company has ' . $disputeHours . ' hours to respond. The warranty does not cover materials you bought yourself or work that was not in the job confirmation.'],
      ['Will I get an invoice?', 'Always. An invoice is rule 06 of the Fachora Standard. The company issues it after your approval, from the job\'s amount and description, in the invoicing software it gets from us, and sends it to your e-mail.'],
      ['What if there are no verified companies in my area yet?', 'We tell you straight away rather than promise offers that will not come. Jobs go only to Fachora Verified companies. Companies from public registers that we have not checked yet are shown as an inactive profile and do not receive jobs; we do not send them any messages either. We are building the database region by region, so it is worth checking again later.'],
      ['What do you do with my data?', 'The job description, photos and preferred language are seen only by the companies the job is offered to (at most ' . $maxOffers . '). Your address and contact details go only to the company you choose. Payment data is processed by Stripe; Fachora does not see your card number. The data controller is Creditline B.V. (Bussum, the Netherlands). Details in the <a href="' . route('pl.prywatnosc') . '">privacy policy</a>.'],
    ],
    'For companies' => [
      ['What does a company pay?', '0 zł subscription, 0 zł to register, 0 zł per job. The commission is ' . $fee . '% of completed, approved work, and ' . $intro . '% for the first ' . $introDays . ' days after verification. It is deducted at payout and you receive an invoice for it. A job that does not go ahead costs nothing. The KSeF-ready invoicing software is free for as long as the company has Verified status. More: <a href="' . route('fachora.for-companies') . '">for companies</a>.'],
      ['Why is my profile "inactive" and how do I claim it?', 'We build the company database from public registers (CEIDG, KRS). A company from a register is shown as an inactive profile: no phone number, no jobs, no Verified mark. You claim it in one of three ways: with the code from the letter we send by post, by looking up the profile by tax number on the <a href="' . route('fachora.claim') . '">claim profile</a> page, or by <a href="' . route('fachora.apply') . '">registering the company</a> if it is not in our database. Then you confirm your e-mail and phone, onboard with Stripe and accept the Standard.'],
      ['Does Fachora send companies e-mails or text messages without asking?', 'No. We never send electronic messages to companies that have not claimed their profile and given consent. Companies from the registers may only receive a letter by post from us. That is why a claimed, verified profile is the only way jobs can reach you.'],
      ['What happens when a company breaks the Standard?', 'Two justified complaints in 90 days, an expired policy shown in the profile or loss of active status in the register mean a 30-day suspension followed by a reassessment. Fraud, a no-show after a customer\'s reservation or settling a Fachora job outside Fachora mean removal from the platform. Every decision is explained in writing and can be responded to.'],
    ],
    'Contact' => [
      ['How do I contact you?', 'Write to <a href="mailto:' . brand('email') . '">' . brand('email') . '</a> or use the <a href="' . route('pl.kontakt') . '">contact page</a>. For anything about a specific job, the buttons on your job page are fastest, because we immediately see what it is about. Fachora is a service of Creditline B.V., Torenlaan 5B, 1402 AT Bussum, the Netherlands.'],
    ],
  ];
  $chev = '<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>';
  $ld = [];
  foreach ($faqGroups as $items) foreach ($items as [$q, $a]) {
    $ld[] = ['@type' => 'Question', 'name' => $q, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($a)]];
  }
@endphp

@section('title', 'Frequently asked questions — Fachora')
@section('description', 'Answers to questions about Fachora: is it free, how we check companies, languages, Fachora Protect, cancelling a job, disputes, warranty, invoices, the commission for companies, claiming a profile, personal data and contact.')

@push('styles')
@include('fachora.partials.styles')
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $ld], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')

<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Help</div>
    <h1>Frequently asked questions</h1>
    <p class="lead">For customers and for companies. If you cannot find your answer, write to us: we reply in English and Polish.</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    @foreach($faqGroups as $group => $items)
    <div class="fh-faq-group">{{ $group }}</div>
    <div class="faq-list">
      @foreach($items as [$q, $a])
      <details class="faq-item">
        <summary>{{ $q }}{!! $chev !!}</summary>
        <div class="faq-content">{!! $a !!}</div>
      </details>
      @endforeach
    </div>
    @endforeach
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Got a job? Describe it.</h2>
    <p>No account, no charge. Offers from verified companies within {{ $offerHours }} hours.</p>
    <div class="fh-cta-row">
      <a href="{{ route('fachora.intake') }}" class="btn btn-primary btn-lg">Find a tradesperson</a>
      <a href="{{ route('fachora.for-companies') }}" class="btn btn-white btn-lg">I run a company</a>
    </div>
  </div>
</section>

@endsection
