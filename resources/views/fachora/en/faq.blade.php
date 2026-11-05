@extends('layouts.marketing')
{{-- Frequently asked questions about Fachora, English version: for customers and for companies. One source for the
     list and the FAQPage schema. The customer picks the companies; Verified companies get the request from us, a
     register company gets nothing from Fachora (the customer contacts it). Numbers from config/fachora.php. --}}
@php
    $protect = fachora_protect();
    $fee = config('fachora.fee_percent');
    $intro = config('fachora.intro_fee_percent');
    $introDays = config('fachora.intro_days');
    $warranty = config('fachora.default_warranty_months');
    $maxOffers = config('fachora.max_offers');
    $offerHours = config('fachora.offer_hours');
    $acceptDays = config('fachora.auto_accept_days');
    $disputeHours = config('fachora.dispute_hours');
    $cancelHours = config('fachora.cancel_free_hours');
    $candidates = config('fachora.choice_candidates');
    $keepDays = (int) config('fachora.register_keep_days');
    $langs = implode(', ', array_map(fn ($l) => $l['en'], config('fachora.languages')));

    $faqGroups = [
        'For customers' => [
            ['Is Fachora free for customers?', 'Yes. You do not pay for posting a job, for picking companies, for the offers' . ($protect ? ', for Fachora Protect' : '') . ' or for dispute resolution. You pay only the price agreed with the company, exactly as it appears in the job confirmation. Fachora\'s fees are paid by the company.'],
            ['Who picks the companies: me or Fachora?', 'You do. After confirming by e-mail you see a list of up to ' . $candidates . ' companies near you: Fachora Verified first, then by rating and distance. Companies from the business register are clearly marked. You tick up to ' . $maxOffers . ' and send your request. Companies do not pay for a place on the list, and Fachora does not send your job to anyone you did not tick yourself.'],
            ['What is the difference between a Verified company and a register company?', 'A Fachora Verified company has passed our checks, claimed its profile and agreed to receive jobs: it gets your request from us by e-mail, replies on your job page and is bound by the Fachora Standard (price up front, invoice, ' . $warranty . '-month warranty, a reply to complaints within ' . $disputeHours . ' hours). A register company is an entry from the Polish business register (CEIDG) that we have not checked yet: Fachora sends it nothing. If you tick it, we show you its public contact details and a ready-made message in Polish, and you call or write yourself and make your own arrangements. Our guarantees and dispute resolution do not cover it.'],
            ['How do you check companies?', 'Before a company receives the Fachora Verified mark we check: its entry and status in CEIDG or KRS plus its tax number (NIP) and VAT status, ' . ($protect ? 'the identity of the person representing it and its bank account (through Stripe), ' : '') . 'its phone number (SMS code) and e-mail address (link), and its acceptance of the Fachora Standard with date and version. Liability insurance is not compulsory, but if the company has it you see that in the profile. The checks continue afterwards: register status, VAT, policy and ratings are checked periodically. Details in the <a href="' . route('fachora.standard') . '">Fachora Standard</a>.'],
            ['Will I get the company\'s phone number?', 'It depends on the company. A Verified company does not show its number before you choose: offers, questions and agreements go through your job page, so everything is on record and can serve as the basis for a warranty claim or a dispute; after you choose, the company receives your address and number and contacts you. With a register company it is the other way round: once you tick it, you see its public contact details from the register (e-mail, phone), because you are the one who gets in touch.'],
            ['Why the SMS code?', 'Before your request goes out to companies, you confirm your phone number with a code from a text message. That way companies know the request is real, and nobody can send requests pretending to be you. Only the company you finally choose sees your number.'],
            ['I do not speak Polish. Is that a problem?', 'No. When you post a job you choose a language: ' . $langs . '. Verified companies only receive the request if they declare that language, and in the profile you see their level (native, good, basic). We translate your description into Polish automatically, so you can contact a register company with a ready-made message in Polish. The Fachora website is available in Polish and English.'],
            ...($protect ? [['What is Fachora Protect and do I have to use it?', 'Fachora Protect means reserving the agreed amount before the tradesperson arrives, available for a job with a Verified company. You pay by BLIK, Przelewy24 or card to Stripe, and the company receives the money only after your approval. In a dispute the money stays blocked. You do not have to use it: you can pay the company directly after the work, but then there is no blocked payment and no payment-backed dispute. Fachora never holds your money; the whole payment is handled by Stripe. More: <a href="' . route('fachora.protect') . '">Fachora Protect</a>.']] : []),
            ['Can I cancel a job?', 'Yes. Up to ' . $cancelHours . ' hours before the appointment free of charge' . ($protect ? ', with a full refund of the reservation. Later, the company may keep the call-out fee from its offer and the rest comes back to you. If the company cancels without a reason, you get a full refund and the company a note on its record that Fachora takes into account.' : '. Later, the company may charge the call-out fee from its offer. If the company cancels without a reason, it gets a note on its record that Fachora takes into account.') . ' This applies to jobs with a Verified company; with a register company you make your own arrangements.'],
            ['What happens if the work is done badly?', 'For a job with a Verified company you open a dispute on your job page: a description, photos, what you expect. The company has ' . $disputeHours . ' hours to respond. Fachora looks at the job confirmation, the photos and the messages and ' . ($protect ? 'decides: full payout, partial payout, a repair on a new date, or a refund' : 'gives a decision: a repair on a new date or a lower price; a company that ignores it loses the Verified mark') . '. If the company does not respond in time, the decision goes your way. You have ' . $acceptDays . ' days to raise it from the moment the company marks the job as done; after that the job counts as accepted and the warranty continues to apply.'],
            ['How does the warranty work?', 'Every Verified company gives a warranty on the work, ' . $warranty . ' months as standard (an offer may say longer). You report a fault within that time on your job page; the complaint goes back to the same job and the company has ' . $disputeHours . ' hours to respond. The warranty does not cover materials you bought yourself or work that was not in the job confirmation. A register company\'s warranty is something you agree with it yourself.'],
            ['Will I get an invoice?', 'From a Verified company, always: an invoice is rule 06 of the Fachora Standard. The company issues it after your approval, from the job\'s amount and description, and sends it to your e-mail. A register company is not bound by the Standard, so ask it for an invoice when you agree the work.'],
            ['What if there are no verified companies in my area yet?', 'We show you companies from the business register that work in your category near you. If we do not have them yet, we search the register within a few minutes of your confirmation and let you know by e-mail when the list is ready. If there really is nobody, we tell you straight away rather than promise offers that will not come.'],
            ['What do you do with my data?', 'The job description, photos and preferred language are seen only by the Verified companies you tick yourself (at most ' . $maxOffers . '). Fachora sends nothing to a register company; you decide what you write to it. Your address and phone number go only to the company you choose. Details of register companies nobody picked are deleted after ' . $keepDays . ' days. ' . ($protect ? 'Payment data is processed by Stripe; Fachora does not see your card number. ' : '') . 'The data controller is Creditline B.V. (Bussum, the Netherlands). Details in the <a href="' . route('pl.prywatnosc') . '">privacy policy</a>.'],
        ],
        'For companies' => [
            ['What does a company pay?', (config('fachora.lead_pricing')
                ? '0 zł subscription and 0 zł to register. Viewing a request is free; you pay only for the one you want to respond to: ' . config('fachora.lead_percent') . '% of the estimated value of the work, from ' . (int) config('fachora.lead_min') . ' to ' . (int) config('fachora.lead_max') . ' zł net. The first request is free. When the customer pays you directly there is no commission' . ($protect ? '; with Fachora Protect ' . $fee . '% is deducted at payout' : '') . '. You receive an invoice. '
                : '0 zł subscription, 0 zł to register, 0 zł per job. The commission is ' . $fee . '% of completed, approved work, and ' . $intro . '% for the first ' . $introDays . ' days after verification. It is deducted at payout and you receive an invoice for it. A job that does not go ahead costs nothing. ')
                . 'More: <a href="' . route('fachora.for-companies') . '">for companies</a>.'],
            ['How does a customer find my company and how do I receive requests?', 'The customer describes the job and picks up to ' . $maxOffers . ' companies from a list: Verified companies are at the top, then rating and distance decide. If the customer ticks your Verified company, you receive an e-mail with the description, photos, date and the customer\'s language; you give a price and a date through a link, no login needed. You see the customer\'s address and phone number only once they choose your offer.'],
            ['Why is my profile "from the register" and how do I claim it?', 'We build the company database from public Google Business Profiles (companies with good reviews) and from public registers (CEIDG, KRS). A company from a register is shown as a register profile: name, town and type of activity, with no Verified mark, no ratings and no requests from us. You claim it in one of three ways: with the code from the letter we send by post, by looking up the profile by tax number on the <a href="' . route('fachora.claim') . '">claim profile</a> page, or by <a href="' . route('fachora.apply') . '">registering the company</a> if it is not in our database. Then you confirm your e-mail and phone, pass verification and receive the Verified mark.'],
            ['Does Fachora send companies e-mails or text messages without asking?', 'No. We send nothing to a register company: no advertising, no newsletters, no requests. If a customer picks such a profile, the customer contacts the company directly using its public details from the register. Messages from Fachora (customer requests and job updates) go only to companies that have claimed their profile or registered themselves; every one of them can be unsubscribed from with the link in the footer. Details of register companies nobody picked are deleted after ' . $keepDays . ' days.'],
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
@section('description', 'Answers to questions about Fachora: is it free, who picks the companies, Verified versus register companies, how we check companies, languages, ' . ($protect ? 'Fachora Protect, ' : '') . 'cancelling a job, disputes, warranty, invoices, fees for companies, claiming a profile, personal data and contact.')

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
    <p>No account, no charge. You pick the companies; Verified companies respond within {{ $offerHours }} hours.</p>
    <div class="fh-cta-row">
      <a href="{{ route('fachora.intake') }}" class="btn btn-primary btn-lg">Find a tradesperson</a>
      <a href="{{ route('fachora.for-companies') }}" class="btn btn-white btn-lg">I run a company</a>
    </div>
  </div>
</section>

@endsection
