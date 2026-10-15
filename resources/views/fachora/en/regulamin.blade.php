@extends('layouts.marketing')

{{--
  DRAFT — PENDING LEGAL REVIEW.
  Fachora platform terms of service, English edition (convenience translation; the Polish text is binding).
  Numbers (commission, deadlines) come from config/fachora.php. The "draft" marker is deliberately discreet, at the bottom.
--}}

@php
    $fee = rtrim(rtrim(number_format((float) config('fachora.fee_percent'), 1, '.', ''), '0'), '.');
    $introFee = rtrim(rtrim(number_format((float) config('fachora.intro_fee_percent'), 1, '.', ''), '0'), '.');
    $minRating = number_format((float) config('fachora.min_rating'), 1, '.', '');
@endphp

@section('title', 'Fachora platform terms of service')
@section('description', 'Terms of service of the Fachora platform (Budomatch): Fachora as an intermediary, requests and offers, the contract between Customer and Company, Fachora Protect and payments through Stripe, disputes, cancellations, warranty, consumer rights, the commission charged to Companies, reviews, DSA rules, liability, governing law.')

@push('styles')
<style>
  .legal{padding:56px 0 80px;}
  .legal .container{max-width:780px;}
  .legal h1{font-size:clamp(30px,5vw,42px);margin-bottom:10px;color:var(--brand-darker);}
  .legal .meta{color:var(--text-3);font-size:14px;margin-bottom:26px;}
  .legal .entity{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:18px 20px;margin-bottom:30px;box-shadow:var(--shadow-sm);font-size:14.5px;line-height:1.75;color:var(--text-2);}
  .legal .entity strong{color:var(--text);}
  .legal .lang{background:var(--accent-tint);border:1px solid #F8C9A3;border-radius:12px;padding:12px 16px;margin-bottom:26px;font-size:13.5px;line-height:1.6;color:var(--text-2);}
  .legal .toc{background:var(--brand-tint);border:1px solid var(--brand-border);border-radius:14px;padding:18px 22px;margin-bottom:34px;font-size:14px;line-height:1.7;columns:2;column-gap:28px;}
  @media (max-width:640px){.legal .toc{columns:1;}}
  .legal .toc a{color:var(--brand);font-weight:500;display:block;break-inside:avoid;}
  .legal h2{font-size:20px;margin:36px 0 10px;scroll-margin-top:90px;}
  .legal h3{font-size:16px;margin:22px 0 6px;}
  .legal p{color:var(--text-2);margin:0 0 14px;line-height:1.75;}
  .legal ul,.legal ol{color:var(--text-2);margin:0 0 16px;padding-left:20px;line-height:1.75;}
  .legal li{margin-bottom:7px;}
  .legal a{color:var(--brand);font-weight:500;}
  .legal a:hover{text-decoration:underline;}
  .legal .disclaimer{margin-top:44px;padding:12px 16px;background:var(--surface-2);border-radius:10px;font-size:12.5px;color:var(--text-3);}
</style>
@endpush

@section('content')
<section class="legal">
  <div class="container">
    <div class="eyebrow">Documents</div>
    <h1>Fachora platform terms of service</h1>
    <div class="meta">Version {{ config('fachora.terms_version') }} · in force from 16 October 2026</div>

    <div class="lang">This English text is a convenience translation. The <a href="{{ route('lang', 'pl') }}">Polish version</a> is the legally binding one; in case of any discrepancy the Polish text prevails.</div>

    <div class="entity">
      Platform operator (Service Provider):<br>
      <strong>Budomatch Daniël de Graaf</strong>, operating the platform under the <strong>Fachora</strong> brand (fachora.pl)<br>
      Białka 15 · 09-550 Białka · Poland<br>
      NIP 7010869430 · REGON 381430120 · sole proprietorship registered in CEIDG<br>
      E-mail: <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a> (also the point of contact referred to in § 15)
    </div>

    <nav class="toc" aria-label="Contents">
      <a href="#p1">§ 1. Definitions</a>
      <a href="#p2">§ 2. What Fachora is</a>
      <a href="#p3">§ 3. Using the Platform</a>
      <a href="#p4">§ 4. Company account and verification</a>
      <a href="#p5">§ 5. Fachora Standard</a>
      <a href="#p6">§ 6. Requests and offers</a>
      <a href="#p7">§ 7. Contract between Customer and Company</a>
      <a href="#p8">§ 8. Fachora Protect</a>
      <a href="#p9">§ 9. Disputes</a>
      <a href="#p10">§ 10. Cancellations and no-shows</a>
      <a href="#p11">§ 11. Warranty and complaints</a>
      <a href="#p12">§ 12. Consumer rights</a>
      <a href="#p13">§ 13. Fees and commission</a>
      <a href="#p14">§ 14. Reviews</a>
      <a href="#p15">§ 15. Content and notices (DSA)</a>
      <a href="#p16">§ 16. Liability</a>
      <a href="#p17">§ 17. Personal data</a>
      <a href="#p18">§ 18. Complaints to Fachora</a>
      <a href="#p19">§ 19. Changes to the terms</a>
      <a href="#p20">§ 20. Governing law and disputes</a>
      <a href="#p21">§ 21. Final provisions</a>
    </nav>

    <h2 id="p1">§ 1. Definitions</h2>
    <ul>
      <li><strong>Fachora</strong> or <strong>Platform</strong> — the website at fachora.pl, operated by the Service Provider, which lets Customers submit Requests and Companies submit Offers and carry out Jobs.</li>
      <li><strong>Service Provider</strong> — Budomatch Daniël de Graaf, Białka 15, 09-550 Białka, Poland, NIP 7010869430, REGON 381430120 (sole proprietorship registered in CEIDG).</li>
      <li><strong>Customer</strong> — a person who submits a Request through the Platform: a consumer (a natural person acting for purposes outside their trade or business) or a business.</li>
      <li><strong>Company</strong> — a business (sole trader, company or other entity) entered in the relevant register, holding a Company Account on the Platform.</li>
      <li><strong>Fachora Verified Company</strong> (<strong>Verified</strong>) — a Company that has passed the verification described in § 4 and has been given the "Fachora Verified" mark by the Service Provider. Only Verified Companies receive Requests.</li>
      <li><strong>Profile</strong> — a Company's public page on the Platform. A Profile may originate from a public register (inactive profile, no contact details) or be maintained by the Company after claiming it.</li>
      <li><strong>Request</strong> (also "Job") — the description of a service a Customer needs (category, location, description, photos, timing, language), submitted through the Platform and confirmed by the Customer.</li>
      <li><strong>Offer</strong> — a Company's reply to a Request: price (fixed or by rates), proposed date, warranty period and remarks.</li>
      <li><strong>Service Contract</strong> — the contract between the Customer and the Company for carrying out the Request, concluded when the Customer chooses an Offer (§ 7).</li>
      <li><strong>Fachora Protect</strong> (<strong>Protect</strong>) — the way of paying for a Job described in § 8: the Customer reserves the Offer amount with the payment provider, and the Company is paid out after the completed work is approved.</li>
      <li><strong>Payment provider</strong> — Stripe Payments Europe, Ltd. (Ireland) and its group companies, providing payment services under their own terms.</li>
      <li><strong>Fachora Standard</strong> — ten rules a Verified Company undertakes to follow (§ 5).</li>
      <li><strong>Job Page</strong> — the Customer's page, reached through a personal link from the e-mail, where the Customer manages the Request (offers, choice, payment, approval, dispute, review).</li>
      <li><strong>Company Panel</strong> — the part of the Platform available to a Company after logging in.</li>
      <li><strong>Terms</strong> — this document.</li>
    </ul>

    <h2 id="p2">§ 2. What Fachora is — the roles</h2>
    <p>1. Fachora is an intermediary platform. The Service Provider provides the tools through which a Customer describes a need, Companies submit Offers, the Customer chooses a Company, and payment, communication, warranty and reviews proceed in an orderly way.</p>
    <p>2. <strong>The Service Provider does not carry out construction, installation, repair or any other services that are the subject of Requests, and is not a party to the Service Contract.</strong> The Service Contract is concluded solely between the Customer and the Company. The Company carries out the Job for its own account and at its own risk, issues the Customer an invoice or other proof of service, and is responsible for the quality of the work.</p>
    <p>3. The Service Provider provides electronic services to the Customer and the Company: making the Platform available, passing on Requests and Offers, running the Job Page and the Company Panel, operating Fachora Protect, mediating in disputes, publishing reviews and the Fachora Verified mark.</p>
    <p>4. The "Fachora Verified" mark is the result of the verification described in § 4 and the Service Provider's assessment as at the date it was carried out. It is not a guarantee of, or surety for, the Company's performance, subject to the Fachora Guarantee described in § 11.</p>
    <p>5. The Service Provider is a provider of an online platform within the meaning of Regulation (EU) 2022/2065 (Digital Services Act, DSA). The provisions arising from that regulation are in § 4(3), § 14 and § 15.</p>

    <h2 id="p3">§ 3. Using the Platform</h2>
    <p>1. Using the Platform requires: a device with Internet access, a current browser with JavaScript and cookies enabled, and an active e-mail address. A Company additionally needs a mobile phone number and an account with a Polish bank.</p>
    <p>2. Use of the Platform by a Customer is <strong>free of charge</strong>. The Customer pays only the Company for the service performed — through Fachora Protect, or directly to the Company if Protect is not available for the Job.</p>
    <p>3. A Customer does not create an account. Access to the Request is through a personal, confidential link sent to the e-mail address given when submitting the Request. The Customer protects this link like a password; anyone holding it can manage the Request.</p>
    <p>4. It is prohibited to use the Platform unlawfully or contrary to these Terms, in particular: submitting fake Requests or Offers, giving false data, bypassing the Platform for Jobs that came through it (§ 13(6)), harassing the other party, posting illegal content (§ 15), and automated extraction of data from the Platform (scraping).</p>
    <p>5. The Service Provider may restrict or block access to the Platform for anyone who breaches paragraph 4, and may block the tax number (NIP) of a Company whose Account was removed for a serious breach (§ 5(4)).</p>

    <h2 id="p4">§ 4. Company Account and verification</h2>
    <p>1. <strong>Profiles from registers.</strong> The Service Provider may create an inactive Company Profile from data in public registers (CEIDG, KRS, the VAT taxpayer list). Such a Profile contains only the name, town and type of activity; it contains no contact details and is not marked as Verified. The Service Provider does not send such Companies e-mails or text messages; it may send a letter by post with a code for claiming the Profile. A Company may request removal of an inactive Profile at any time (§ 17).</p>
    <p>2. <strong>Claiming a Profile.</strong> A Company claims its Profile by confirming an e-mail address and a phone number and, if it received a letter, by entering the code from the letter. By claiming the Profile the Company declares that it is entitled to represent the business, that the data provided are true and current, and accepts these Terms and the Fachora Standard. On claiming the Profile a contract for the use of the Platform is concluded between the Company and the Service Provider.</p>
    <p>3. <strong>Verification.</strong> To receive the Fachora Verified mark, a Company passes verification consisting of:</p>
    <ul>
      <li>a register check (CEIDG or KRS): active business, matching NIP and REGON numbers, a PKD activity code matching the chosen categories;</li>
      <li>a check of VAT status and bank account in the VAT taxpayer list (the "white list"), where applicable;</li>
      <li>confirmation of the e-mail address and phone number;</li>
      <li>acceptance of the Terms and the Fachora Standard, with the date and version recorded;</li>
      <li>verification of the identity of the person representing the Company and of the bank account, carried out by the payment provider in the Stripe Connect procedure (the Service Provider receives the result, not the documents);</li>
      <li>optionally: uploading a liability insurance policy; information about current insurance is shown on the Profile until the policy expires.</li>
    </ul>
    <p>The information collected in this way corresponds to the trader traceability obligation of Article 30 DSA. The Company updates its data without delay when they change.</p>
    <p>4. <strong>Decision.</strong> Granting, refusing, suspending and withdrawing the Fachora Verified mark is a decision of the Service Provider, taken on the basis of the verification results, the Fachora Standard and the cooperation so far. The Service Provider informs the Company of the decision and its reasons; the Company may appeal to {{ brand('email') }} within 14 days and the Service Provider decides on the appeal within 14 days. A Company has no claim to be granted the mark.</p>
    <p>5. <strong>Account.</strong> The Company is responsible for the confidentiality of its login details and for the actions of persons it gives access to the Company Panel. The Company may delete its Account at any time, subject to completing Jobs in progress and settling commission; deleting the Account does not remove reviews or data required by law (§ 17).</p>
    <p>6. With its Account the Company receives free access to an invoicing tool to the extent needed to settle Jobs. Wider use of it may be subject to separate terms.</p>

    <h2 id="p5">§ 5. Fachora Standard</h2>
    <p>1. A Verified Company undertakes to follow the Fachora Standard:</p>
    <ol>
      <li>It runs a legally registered and active business.</li>
      <li>The identity of the person representing the Company has been verified.</li>
      <li>It gives a clear price up front — in the Offer, before any work starts.</li>
      <li>It does no extra work without the Customer's prior approval given on the Job Page.</li>
      <li>It confirms the agreed appointment; it cancels only for a valid reason and as early as possible.</li>
      <li>It always issues an invoice or other proof of service.</li>
      <li>It gives a warranty on the work where applicable ({{ (int) config('fachora.default_warranty_months') }} months by default).</li>
      <li>It replies to a complaint or dispute within {{ (int) config('fachora.dispute_hours') }} hours.</li>
      <li>It settles Jobs from the Platform according to Fachora's rules (including Fachora Protect, if the Customer chose it).</li>
      <li>It keeps an average rating of at least {{ $minRating }} (with at least 5 reviews) and replies to new Requests within 24 hours.</li>
    </ol>
    <p>2. <strong>Signals and sanctions.</strong> A breach of the Standard is recorded on the Company's Account. Two justified Customer complaints within 90 days lead to suspension of the Profile for 30 days; after that period the Service Provider reassesses the Company. An average rating falling below {{ $minRating }} leads to a warning and, absent improvement, to suspension.</p>
    <p>3. During a suspension the Profile is not marked as Verified, is not shown in listings, and the Company receives no new Requests; Jobs in progress are completed.</p>
    <p>4. <strong>Removal from the Platform</strong> follows a serious breach, in particular: fraud or attempted fraud, not showing up for a Job after a Protect reservation without excuse, accepting payment outside the Platform for a Job that came through it, giving false data in verification, or the business ceasing to exist. The Service Provider may then block the Company's NIP for re-registration.</p>
    <p>5. If a condition of verification lapses (e.g. removal from the register, loss of VAT status, loss of payout capability with the payment provider), the Profile is suspended automatically until it is remedied.</p>

    <h2 id="p6">§ 6. Requests and Offers</h2>
    <p>1. <strong>Submitting a Request.</strong> The Customer describes the need in the form (category, postal code or town, description, optional photos, urgency, service language) and gives an e-mail address. The Request becomes active only after <strong>confirmation through the link in the e-mail</strong>. An unconfirmed Request is deleted after {{ (int) config('fachora.confirm_days') }} days.</p>
    <p>2. The Service Provider may use automated tools (including artificial intelligence) to tidy up the description, translate it into Polish for the Companies and estimate an indicative price range. The estimate is neither an offer nor a quotation and binds no one.</p>
    <p>3. <strong>Passing on to Companies.</strong> A confirmed Request is passed on simultaneously to at most <strong>{{ (int) config('fachora.max_offers') }} Verified Companies</strong> selected by category, distance, language, rating and response speed. A Company has {{ (int) config('fachora.offer_hours') }} hours to reply; in urgent cases the deadline may be shorter. If there is no Verified Company in the area, the Service Provider informs the Customer and may look for a Company by hand; it does not guarantee that a Request will receive any Offer.</p>
    <p>4. <strong>Offer.</strong> The Company submits its Offer through the Platform, stating: a <strong>gross</strong> price (including VAT) — a fixed price for the whole Job or rates (call-out, first hour, further hours) with an estimated number of hours — the proposed date, the warranty period and any conditions. An Offer binds the Company for the period stated in it or, if none is stated, for 7 days.</p>
    <p>5. <strong>A fixed price is binding.</strong> The Company may not demand more than the Offer amount unless the Customer approved extra work under paragraph 6. With hourly rates, the rates are binding; the Company informs the Customer when the actual working time is going to exceed the estimate, and the Customer can then decide whether to continue.</p>
    <p>6. <strong>Extra work.</strong> If during the Job it turns out that work or materials not covered by the Offer are necessary, the Company adds them as a separate priced item on the Job Page. Extra work is carried out and paid <strong>only after the Customer approves it</strong> on the Job Page. Work done without approval is not payable through the Platform.</p>
    <p>7. <strong>Choice.</strong> The Customer compares the Offers on the Job Page and chooses one of them or none. The Customer is under no obligation to choose. The Platform does not show the Customer the Company's phone number or e-mail address before the choice, nor the Company more of the Customer's data than needed to submit an Offer.</p>

    <h2 id="p7">§ 7. Contract between the Customer and the Company</h2>
    <p>1. When the Customer chooses an Offer, a <strong>Service Contract between the Customer and the Company</strong> is concluded on the terms set out in the Request and the Offer. The Service Provider confirms its conclusion to both parties in a <strong>job confirmation</strong> (e-mail and downloadable document) containing: the description of the work, the price, the date, the warranty period, and information on the invoice and the method of payment.</p>
    <p>2. The Service Contract is a distance contract concluded through the Platform. The Company is the trader bound towards a consumer Customer under that contract, including the information duties; the Service Provider helps meet them by providing the Company's details and the job confirmation.</p>
    <p>3. After the choice the Company receives the data needed to carry out the Job (the Customer's name, the address where the service is performed, contact details for arranging the appointment), and the Customer receives the Company's details required by law (name, address, NIP) and the ability to communicate through the Platform.</p>
    <p>4. The Company confirms the appointment, informs about its arrival, and reports the start and completion of the work on the Platform, attaching photos of the work done where possible.</p>
    <p>5. The Service Provider is not a party to the Service Contract, gives no warranty on the Company's work and is not responsible for its performance, subject to the Fachora Guarantee (§ 11) and the Fachora Protect rules (§ 8).</p>

    <h2 id="p8">§ 8. Fachora Protect — payment</h2>
    <p>1. Fachora Protect is available if the chosen Company has an active account with the payment provider with payouts enabled. Availability of Protect is shown on the Profile and in the Offer. If Protect is not available, the Customer pays the Company directly after the service is performed, on the terms of the Offer, without the Fachora Guarantee.</p>
    <p>2. <strong>Reservation.</strong> After choosing an Offer with Protect, the Customer pays the Offer amount (gross) through the payment provider's payment page (BLIK, Przelewy24, card or other methods made available). <strong>The funds are held by the payment provider on the platform's account;</strong> the Service Provider does not operate payment accounts and does not hold Customers' funds in its own name. The payment provider's terms (stripe.com/legal) apply to the payment.</p>
    <p>3. Approved extra work (§ 6(6)) is paid by the Customer in the same way, before it is carried out or immediately after approval.</p>
    <p>4. <strong>Approval.</strong> After the Company reports completion, the Customer checks the work and, on the Job Page, either (a) approves the work or (b) opens a dispute (§ 9). <strong>If the Customer does neither within {{ (int) config('fachora.auto_accept_days') }} days of the completion report, the work is deemed approved</strong> (silent approval). The Service Provider reminds the Customer of this deadline by e-mail.</p>
    <p>5. <strong>Payout.</strong> After approval (express or silent) the payment provider transfers to the Company the Job amount less the Service Provider's commission (§ 13). The Company issues the Customer an invoice for the full Job amount. When the funds reach the Company's account depends on the payment provider.</p>
    <p>6. <strong>Refund.</strong> The funds are returned to the Customer in full if: the Job was cancelled under § 10 before the work started (subject to the call-out fee in § 10(2)), the Company did not show up and no new date was agreed, or the Service Provider decided the dispute that way. The refund is made by the same payment method, within a period that depends on the payment provider (usually up to 14 days).</p>
    <p>7. Using Protect is free for the Customer. The Service Provider may introduce a Protect fee for Customers in the future; it will be shown before payment and will not apply to Jobs started earlier.</p>
    <p>8. The Customer and the Company undertake <strong>not to bypass Protect</strong> for a Job for which it was chosen: the Company does not accept additional payment from the Customer outside the Platform, and the Customer does not pay the Company directly. Payment outside the Platform deprives the Customer of the Fachora Guarantee and exposes the Company to removal from the Platform (§ 5(4)).</p>

    <h2 id="p9">§ 9. Disputes</h2>
    <p>1. If the Customer considers that the work was not done, or not done in accordance with the Service Contract, the Customer opens a <strong>dispute</strong> on the Job Page within the period in § 8(4), describing the problem and attaching photos. Opening a dispute holds the payout.</p>
    <p>2. The Company has <strong>{{ (int) config('fachora.dispute_hours') }} hours</strong> to reply on the Platform (explanation, offer to repair, offer to reduce the price). During this time the parties may agree a solution themselves; an agreement recorded on the Platform ends the dispute.</p>
    <p>3. If the parties do not reach an agreement or the Company does not reply in time, <strong>the Service Provider decides the dispute</strong> on the basis of the Request, the Offer, the job confirmation, photos, correspondence on the Platform and the parties' explanations, guided by reasonableness and good faith. The Service Provider may ask for additional information. The decision may consist of: paying the full amount to the Company, refunding the full amount to the Customer, splitting the amount between the parties, or setting a deadline for repair. Failure of the Company to reply in time is decided in the Customer's favour.</p>
    <p>4. The Service Provider aims to decide within 14 days of the expiry of the Company's reply deadline and communicates the decision to both parties with reasons. The decision concerns only the funds reserved in Protect and is executed by the payment provider.</p>
    <p>5. <strong>The Service Provider's decision does not replace court proceedings or out-of-court consumer dispute resolution</strong> and does not limit the parties' claims under the Service Contract. A party that disagrees with the decision may pursue claims against the other party to the Service Contract on general principles (§ 20). The decision on the release of Protect funds is final as regards the Service Provider.</p>

    <h2 id="p10">§ 10. Cancelling a Job and no-shows</h2>
    <p>1. <strong>By the Customer.</strong> The Customer may cancel a Job on the Job Page <strong>free of charge up to {{ (int) config('fachora.cancel_free_hours') }} hours before the agreed appointment</strong>. Cancelling a Job without Protect is always possible until the work starts, subject to paragraph 2.</p>
    <p>2. On a cancellation later than {{ (int) config('fachora.cancel_free_hours') }} hours before the appointment, or when the Company arrives on site and the Customer cancels, the Company may charge a <strong>call-out fee</strong> in the amount stated in the Offer (if the Offer contains none, no fee is due). With Protect this fee is deducted from the refund. This does not apply to a cancellation for a reason attributable to the Company (e.g. the Company changing the date).</p>
    <p>3. <strong>By the Company.</strong> The Company may cancel a confirmed Job only for a valid reason (illness, breakdown, force majeure), as early as possible and stating the reason on the Platform; where possible it proposes a new date. Cancelling without a reason or <strong>not showing up</strong> at the agreed time without notice breaches the Fachora Standard (§ 5); the Customer receives a full refund of the reserved funds and may have the work done by another Company.</p>
    <p>4. If the Company cannot carry out the Job, the Service Provider may — with the Customer's consent — pass the Request on to other Verified Companies.</p>

    <h2 id="p11">§ 11. Warranty, complaints and the Fachora Guarantee</h2>
    <p>1. <strong>The Company's liability.</strong> The Company is liable for performance of the Service Contract under the applicable law (in particular the Polish Civil Code provisions on contracts for work and statutory warranty, and consumer protection law) and the Offer. Nothing in these Terms limits the Company's statutory liability towards the Customer.</p>
    <p>2. <strong>The Company's warranty.</strong> The Company gives a warranty on the work for the period stated in the Offer ({{ (int) config('fachora.default_warranty_months') }} months from approval by default). The warranty covers defects in the work done; it does not cover normal wear, mechanical damage, interference by third parties or defects in materials supplied by the Customer, unless the Offer says otherwise. The warranty does not exclude statutory rights.</p>
    <p>3. <strong>Complaint during the warranty period.</strong> The Customer reports a defect on the Job Page (the report reopens the Job). The Company replies within {{ (int) config('fachora.dispute_hours') }} hours and agrees a repair date. The Service Provider mediates the communication and monitors the outcome.</p>
    <p>4. <strong>Fachora Guarantee.</strong> For Jobs paid through Fachora Protect the Service Provider additionally provides:</p>
    <ul>
      <li><strong>mediation</strong> — handling the dispute and the warranty complaint under § 9 and paragraph 3, and</li>
      <li><strong>replacement repair</strong> — if the Company does not respond to the complaint, refuses to repair a justified defect, or does not carry out the repair by the agreed date, the Service Provider may, at its own discretion, have the defect remedied by another Verified Company and cover its cost <strong>up to the Job amount paid through Protect</strong>.</li>
    </ul>
    <p>The Fachora Guarantee is a voluntary service of the Service Provider, limited to the above scope; it does not cover consequential damage, lost profits or Jobs paid outside Protect. The Service Provider may refuse a replacement repair if the defect is not covered by the Company's warranty (paragraph 2), the Customer prevented the Company from repairing, or the report was made after the warranty period. A Service Provider that has paid for a replacement repair may recover the cost from the Company.</p>

    <h2 id="p12">§ 12. Consumer rights</h2>
    <p>1. A Customer who is a consumer has the right to <strong>withdraw from a Service Contract concluded at a distance within 14 days</strong> of its conclusion without giving a reason (Article 27 of the Polish Consumer Rights Act of 30 May 2014), by a statement to the Company — also through the Job Page or to {{ brand('email') }}, which we will pass on to the Company. Cancelling the Job on the Job Page counts as a statement of withdrawal.</p>
    <p>2. <strong>Performance before the deadline.</strong> So that the service can be performed before the 14 days have passed (which is typical for repairs), when choosing an Offer and making the reservation the Customer expressly requests that the service begin before the withdrawal period ends, and acknowledges that <strong>once the service has been fully performed the right of withdrawal is lost</strong> (Article 38(1)(1) of the Consumer Rights Act). If the Customer withdraws after the start but before full performance, the Customer pays the Company for what was performed up to the withdrawal, in proportion to the Offer price.</p>
    <p>3. The right of withdrawal also does not apply in the other cases listed in Article 38 of the Consumer Rights Act, in particular for urgent repairs that the consumer expressly requested (Article 38(1)(8)), as regards the work requested.</p>
    <p>4. A consumer may also withdraw from the contract with the Service Provider for use of the Platform within 14 days; as use of the Platform is free for Customers, no settlement is involved.</p>
    <p>5. A sole trader for whom the Service Contract is not of a professional nature enjoys consumer protection to the extent provided by law.</p>
    <p>6. Nothing in these Terms excludes or limits consumer rights arising from mandatory provisions of law; in case of conflict the law prevails.</p>

    <h2 id="p13">§ 13. Fees and commission</h2>
    <p>1. The Platform is free for Customers. A Company pays no subscription, no registration fee and no fees for Requests received or Offers submitted.</p>
    <p>2. <strong>Commission.</strong> For every Job completed and approved (expressly or silently) the Company pays the Service Provider a commission of <strong>{{ $fee }}%</strong> of the gross Job amount, including approved extra work. During the first <strong>{{ (int) config('fachora.intro_days') }} days</strong> after the Fachora Verified mark is granted, the commission is <strong>{{ $introFee }}%</strong>. The rate applicable to a Company is shown in the Company Panel and in every Offer before it is submitted.</p>
    <p>3. For Jobs with Protect the commission is deducted from the payout by the payment provider. For Jobs without Protect the Service Provider invoices the Company for the commission with a 14-day payment term; the Company reports the completion of the Job and the final amount on the Platform.</p>
    <p>4. The commission is a net amount to which the Service Provider adds VAT at the rate applicable in Poland (currently 23%). The Company agrees to receive invoices electronically.</p>
    <p>5. If a Job was decided by a refund to the Customer, no commission is due; where the amount is split, the commission is calculated on the part paid out to the Company. No commission is charged on a call-out fee after a cancellation.</p>
    <p>6. <strong>No circumvention.</strong> If a Company performs work for a Customer that the Customer requested through the Platform but settles it outside the Platform, the commission is due to the Service Provider in full on the Offer amount, irrespective of the sanctions in § 5(4). This does not apply to Jobs the Customer cancelled and then — more than 6 months later — commissioned from the Company outside the Platform.</p>
    <p>7. The Service Provider may change the commission rate with 30 days' notice, informing the Company by e-mail and in the Company Panel. The new rate applies to Offers submitted after it takes effect. A Company that does not accept the change may delete its Account before that date.</p>
    <p>8. Additional services for Companies (e.g. an SMS bundle, promoted listings), if introduced, are charged according to the price list shown in the Company Panel before they are ordered.</p>

    <h2 id="p14">§ 14. Reviews</h2>
    <p>1. A review of a Company (a 1–5 rating and text) may be left only by a Customer <strong>after the Job has been completed and approved</strong> through the Platform, within 30 days of approval. Every review is linked to a specific Job; this is how the Service Provider ensures that reviews come from people who actually used the service.</p>
    <p>2. The Company may <strong>reply</strong> to a review once; the reply is published under the review. The Company cannot delete or change a review.</p>
    <p>3. A review should concern the Job performed and comply with the law and good practice. The Service Provider may refuse to publish or may remove a review that contains illegal content, third parties' personal data, profanity, content unrelated to the Job, or that was given in exchange for a benefit. The Service Provider informs the author and the Company of a removal, stating the reason; both may appeal under § 15(5).</p>
    <p>4. The average rating and number of reviews on a Profile are calculated automatically from all published reviews. The Service Provider does not buy, sell or moderate reviews on the basis of whether they are positive or negative.</p>
    <p>5. Reviews remain visible after a Company's Account is deleted, as long as the Profile remains on the Platform.</p>

    <h2 id="p15">§ 15. Content, notices of illegal content, point of contact</h2>
    <p>1. Content posted on the Platform by users (Request descriptions, photos, Offers, Company descriptions, reviews, replies) is the content of its authors. The Service Provider does not monitor it generally, but acts on notices.</p>
    <p>2. <strong>Point of contact.</strong> For Member State authorities, the European Commission and the European Board for Digital Services (Article 11 DSA) and for users (Article 12 DSA) the Service Provider designates a single point of contact: <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a>, and for written correspondence the registered address at the top of these Terms. Communication may take place in Polish, English and Dutch.</p>
    <p>3. <strong>Notice of illegal content</strong> (Article 16 DSA). Anyone may report content they consider illegal by sending to the above address a message containing: an explanation of why the content is illegal, the exact location of the content (page address), the name and e-mail address of the notifier (except for notices concerning sexual offences), and a statement that the notice is made in good faith and that the information is accurate and complete. The Service Provider acknowledges receipt, processes the notice without undue delay, diligently and objectively, and informs the notifier of its decision.</p>
    <p>4. <strong>Statement of reasons</strong> (Article 17 DSA). When content is removed or its visibility restricted, or an Account is suspended or removed, the Service Provider informs the user concerned, stating the reasons, the basis (law or Terms), the facts and circumstances, and the available means of redress.</p>
    <p>5. <strong>Appeal.</strong> A user affected by a decision under paragraph 4, and a person whose notice was not acted upon, may appeal within 6 months to {{ brand('email') }}. Appeals are handled by a person, not solely by automated means, in a timely, impartial manner and free of charge. After exhausting this route, a certified out-of-court dispute settlement body (Article 21 DSA) or the courts may be used.</p>
    <p>6. The Service Provider may suspend, for a reasonable period and after a prior warning, the processing of notices from persons who frequently submit manifestly unfounded notices.</p>
    <p>7. By posting content on the Platform a user grants the Service Provider a free licence to store, display and transmit it to the extent needed for the Platform to operate (including passing the Request description and photos to Companies, and translation), and declares having the right to do so.</p>

    <h2 id="p16">§ 16. Liability</h2>
    <p>1. The Service Provider is responsible for the proper provision of its own services described in § 2(3). The Service Provider is not responsible for the Company's performance of the Service Contract or for the Customer's conduct towards the Company, subject to the Fachora Guarantee (§ 11(4)).</p>
    <p>2. The Fachora Verified mark, ratings, price estimates and other information on the Platform are prepared with due care from the available sources (public registers, the payment provider, Companies' declarations, Customers' reviews). The Service Provider does not guarantee that they are current at every moment; the Company is responsible for keeping its own data current.</p>
    <p>3. The Service Provider endeavours to keep the Platform running without interruption, but may suspend it for maintenance or for reasons beyond its control (outage of hosting, e-mail or payment providers). It gives advance notice of planned interruptions.</p>
    <p>4. Towards Companies (businesses) the Service Provider's liability under the contract for use of the Platform is limited to actual loss and to the amount of commission paid by the Company in the 12 months preceding the event; the Service Provider is not liable for lost profits. The limitation does not apply to damage caused intentionally.</p>
    <p>5. Towards consumers the Service Provider is liable on the terms provided by law; the limitations in paragraph 4 do not apply to them.</p>

    <h2 id="p17">§ 17. Personal data</h2>
    <p>1. The controller of the personal data of Customers, persons representing Companies and visitors to the Platform is the Service Provider. The processing rules, legal bases, recipients, retention periods and the rights of data subjects are described in the <a href="{{ route('pl.prywatnosc') }}">Privacy Policy</a>, which forms part of the information provided when a contract is concluded.</p>
    <p>2. A Company that receives a Customer's data in order to carry out a Job is a separate controller of those data and uses them solely to perform the Service Contract, issue the invoice and honour the warranty. The Company may not use the Customer's data for marketing without the Customer's consent.</p>
    <p>3. A Company whose inactive Profile was created from a public register may request its removal at {{ brand('email') }}; the Service Provider removes the Profile within 14 days and keeps only the NIP with the date of the request, so as not to create the Profile again.</p>

    <h2 id="p18">§ 18. Complaints about the Service Provider's services</h2>
    <p>1. Complaints about the operation of the Platform, Fachora Protect, verification, reviews or other services of the Service Provider may be sent to <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a> or in writing to the registered address. A complaint should include the complainant's details, the Job number (if applicable) and a description of the problem.</p>
    <p>2. The Service Provider replies to a complaint within 14 days of receipt, electronically. Complaints about the service performed by a Company are handled by the Company; the Service Provider forwards them to the Company and mediates under § 9 and § 11.</p>

    <h2 id="p19">§ 19. Changes to the Terms</h2>
    <p>1. The Service Provider may change these Terms for important reasons: changes in the law or its interpretation, changes in the scope or way the Platform operates, requirements of the payment provider, security reasons, or the need to clarify provisions.</p>
    <p>2. Companies are informed of a change by e-mail and in the Company Panel at least 14 days before it takes effect (30 days for a change in commission). A Company that does not accept the changes may delete its Account before they take effect; continued use of the Platform after that date means acceptance.</p>
    <p>3. A Request is governed by the version of the Terms in force when the Customer confirmed it; changes favourable to the Customer may apply immediately. Each version of the Terms carries a number and date; previous versions are available on request.</p>
    <p>4. Editorial changes and extensions of functionality that do not limit users' rights may take effect on publication.</p>

    <h2 id="p20">§ 20. Governing law and dispute resolution</h2>
    <p>1. Contracts concluded under these Terms — between the Service Provider and a Customer, between the Service Provider and a Company, and the Service Contract — are governed by <strong>Polish law</strong>. This choice of law does not deprive a consumer of the protection afforded by provisions that cannot be derogated from by agreement under the law of the country of the consumer's habitual residence.</p>
    <p>2. Disputes with consumers are decided by the ordinary court having jurisdiction under the Polish Code of Civil Procedure. Disputes between the Service Provider and a Company are decided by the ordinary court for the capital city of Warsaw.</p>
    <p>3. <strong>Out-of-court consumer dispute resolution.</strong> A consumer may seek help from the district (municipal) consumer ombudsman, from consumer organisations (e.g. Federacja Konsumentów), from out-of-court consumer dispute resolution proceedings conducted by the Provincial Inspector of Trade Inspection (applications to the WIIH for the area where the trader operates) and from the permanent consumer arbitration courts at the WIIH. Information: <a href="https://uokik.gov.pl" target="_blank" rel="noopener">uokik.gov.pl</a>. This applies both to disputes with the Service Provider and — as regards the Service Contract — to disputes with a Company. The EU online dispute resolution (ODR) platform ceased operating in July 2025.</p>
    <p>4. The Service Provider declares its willingness to take part in out-of-court consumer dispute resolution concerning its own services. Disputes between a Customer and a Company are mediated by the Service Provider under § 9, which is not ADR proceedings within the meaning of the Polish Act on out-of-court resolution of consumer disputes.</p>

    <h2 id="p21">§ 21. Final provisions</h2>
    <p>1. These Terms are available free of charge at fachora.pl/regulamin in a form that allows them to be downloaded, stored and printed. The Polish version is binding; the English translation is for convenience.</p>
    <p>2. If any provision of these Terms proves invalid or ineffective, the remaining provisions remain in force.</p>
    <p>3. The <a href="{{ route('fachora.standard') }}">Fachora Standard</a> (summarised in § 5), the description of <a href="{{ route('fachora.protect') }}">Fachora Protect</a> and the <a href="{{ route('pl.prywatnosc') }}">Privacy Policy</a> form an integral part of these Terms. In case of discrepancy these Terms prevail.</p>
    <p>4. Contact with the Service Provider: <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a>, the <a href="{{ route('pl.kontakt') }}">contact form</a>, or by post: Budomatch Daniël de Graaf, Białka 15, 09-550 Białka, Poland.</p>
    <p>5. Version {{ config('fachora.terms_version') }} of these Terms takes effect on 16 October 2026.</p>

    <div class="disclaimer">Draft pending legal review. Remarks: <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a>.</div>
  </div>
</section>
@endsection
