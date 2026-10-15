@extends('layouts.marketing')

{{--
  DRAFT — PENDING LEGAL REVIEW.
  Fachora privacy policy (GDPR), English edition (convenience translation; the Polish text is binding).
  Controller: Budomatch Daniël de Graaf Covers customers, companies (register data under Art. 6(1)(f), claimed profile under (b),
  verification, Stripe Connect as an independent controller), companies' representatives and visitors.
  The "draft" marker is deliberately discreet, at the bottom.
--}}

@section('title', 'Fachora privacy policy')
@section('description', 'How Fachora (Budomatch) processes personal data of customers, companies and visitors: which data, for what purpose and on what legal basis, who processes them for us (Stripe, Resend, Railway, Anthropic, OpenStreetMap), how long we keep them, how we deal with companies from public registers, cookies and your GDPR rights.')

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
  .legal .tbl-wrap{overflow-x:auto;margin:0 0 20px;}
  .legal .tbl{width:100%;border-collapse:collapse;font-size:13.5px;background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;}
  .legal .tbl th,.legal .tbl td{padding:10px 12px;border-bottom:1px solid var(--border);text-align:left;vertical-align:top;color:var(--text-2);line-height:1.55;}
  .legal .tbl th{background:var(--surface-2);color:var(--text);font-weight:600;white-space:nowrap;}
  .legal .tbl tr:last-child td{border-bottom:none;}
  .legal .box{background:var(--accent-tint);border:1px solid #F8C9A3;border-radius:12px;padding:14px 18px;margin:0 0 16px;font-size:14px;line-height:1.7;color:var(--text-2);}
  .legal .box b{color:var(--text);}
  .legal .disclaimer{margin-top:44px;padding:12px 16px;background:var(--surface-2);border-radius:10px;font-size:12.5px;color:var(--text-3);}
</style>
@endpush

@section('content')
<section class="legal">
  <div class="container">
    <div class="eyebrow">Documents</div>
    <h1>Privacy policy</h1>
    <div class="meta">Version of 16 October 2026</div>

    <div class="lang">This English text is a convenience translation. The <a href="{{ route('lang', 'pl') }}">Polish version</a> is the binding one; in case of any discrepancy the Polish text prevails.</div>

    <div class="entity">
      Data controller:<br>
      <strong>Budomatch Daniël de Graaf</strong>, operating the platform the <strong>Fachora</strong> platform (fachora.pl)<br>
      Białka 15 · 09-550 Białka · Poland<br>
      NIP 7010869430 · REGON 381430120 · sole proprietorship registered in CEIDG<br>
      Contact for data protection matters: <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a>
    </div>

    <p>Fachora connects customers with companies that carry out work in their homes and businesses. For that to work, we process data of customers, companies and the people who represent them. Below we explain — in line with Regulation (EU) 2016/679 (GDPR) — which data these are, where we get them, why and on what basis we process them, who processes them for us, how long we keep them and what your rights are.</p>

    <nav class="toc" aria-label="Contents">
      <a href="#s1">1. Who this policy covers</a>
      <a href="#s2">2. Which data we process and where from</a>
      <a href="#s3">3. Purposes, legal bases, retention</a>
      <a href="#s4">4. Companies from public registers</a>
      <a href="#s5">5. Who we share data with</a>
      <a href="#s6">6. Transfers outside the EEA</a>
      <a href="#s7">7. E-mails, text messages and marketing</a>
      <a href="#s8">8. Automated tools and AI</a>
      <a href="#s9">9. Cookies and statistics</a>
      <a href="#s10">10. Your rights</a>
      <a href="#s11">11. Security</a>
      <a href="#s12">12. Changes to this policy</a>
      <a href="#s13">13. Contact</a>
    </nav>

    <h2 id="s1">1. Who this policy covers</h2>
    <ul>
      <li><strong>Customers</strong> — people who submit a request through fachora.pl, choose a company, pay through Fachora Protect, open disputes and leave reviews.</li>
      <li><strong>Companies and the people representing them</strong> — businesses from public registers (inactive profile), companies that have claimed their profile, companies under verification and Fachora Verified companies, as well as owners, board members and employees who use the panel on their behalf.</li>
      <li><strong>Visitors</strong> to fachora.pl, including those using the contact form and the company application form.</li>
    </ul>

    <h2 id="s2">2. Which data we process and where we get them</h2>
    <h3>Customers</h3>
    <ul>
      <li><strong>Contact details:</strong> e-mail address (always), name and phone number (if you provide them or they are needed to arrange the appointment), preferred service language.</li>
      <li><strong>Job description:</strong> category, description of the problem, photos, urgency, postal code or town. Photos may incidentally contain personal data (e.g. the inside of a flat) — please photograph only what concerns the fault.</li>
      <li><strong>Address where the service is performed</strong> — given only after a company is chosen and passed only to that company.</li>
      <li><strong>Payment:</strong> payment status, amount, transaction identifier with the payment provider, payment method (e.g. "BLIK"). <strong>We do not receive or store card numbers or bank login details</strong> — they are handled solely by Stripe.</li>
      <li><strong>Course of the job:</strong> offers, choice, approved extra work, completion reports, disputes with description and photos, review, correspondence through the platform, dates and IP addresses of actions (confirmation, choice, approval).</li>
    </ul>
    <p>Source: you, and for payment status, the payment provider.</p>

    <h3>Companies and the people representing them</h3>
    <ul>
      <li><strong>Register data</strong> (inactive profile): name, the trader's full name (for a sole trader these are personal data), NIP, REGON, KRS, legal form, registered address, PKD activity codes, business status, VAT status and account numbers from the VAT taxpayer list. Source: CEIDG (dane.biznes.gov.pl), KRS (Ministry of Justice), the VAT taxpayer list (Ministry of Finance). Details in section 4.</li>
      <li><strong>Claimed-profile data:</strong> e-mail address and phone number of the person claiming the profile, their name, the code from the letter, display name, description, photo or logo, categories, languages and their level, service radius, indicative rates, warranty period, invoice declaration, Fachora Protect opt-in. Source: the company.</li>
      <li><strong>Verification data:</strong> results of checks in the registers and the VAT list (with date and response), e-mail and phone confirmation (codes, dates), date and version of the accepted terms and Fachora Standard, liability insurance policy (if you upload one: file, insurer, scope, expiry date), notes from verification and from handling reports.</li>
      <li><strong>Stripe Connect:</strong> account identifier with the payment provider, status of the ability to accept payments and payouts, list of outstanding requirements. <strong>Identity documents, beneficial-owner data and the bank account number are collected and verified by Stripe as an independent controller</strong> (section 5); we only see the result.</li>
      <li><strong>Activity on the platform:</strong> requests received, offers, jobs in progress, reports, disputes, reviews and replies, average rating, number of jobs, response time, breaches of the Fachora Standard, commission invoices, payouts.</li>
      <li><strong>Account:</strong> login, hashed password, login dates, IP addresses, activity log.</li>
    </ul>

    <h3>Visitors</h3>
    <ul>
      <li>Contact form and company application form: name, e-mail, subject, message, company details (if you provide them).</li>
      <li>Technical data: IP address, browser, pages visited, time — in server logs and in anonymous statistics (section 9).</li>
    </ul>

    <h2 id="s3">3. Purposes, legal bases and retention periods</h2>
    <div class="tbl-wrap">
      <table class="tbl">
        <tr><th>Purpose</th><th>Data</th><th>Legal basis (GDPR)</th><th>How long</th></tr>
        <tr><td>Receiving and confirming a request, passing it to companies, collecting offers</td><td>customer's contact details and job description</td><td>Art. 6(1)(b) — contract for use of the platform (at the customer's request)</td><td>unconfirmed request: deleted after {{ (int) config('fachora.confirm_days') }} days; request without a chosen offer: 6 months from submission</td></tr>
        <tr><td>Conclusion and performance of the contract between customer and company: job confirmation, appointment, extra work, approval, invoice</td><td>course of the job, service address</td><td>Art. 6(1)(b); towards the company — (b) (contract for use of the platform)</td><td>warranty period ({{ (int) config('fachora.default_warranty_months') }} months by default) and the limitation period for claims — up to 6 years from completion of the job</td></tr>
        <tr><td>Fachora Protect: reservation, payout, refund, dispute decisions</td><td>payment status, amounts, dispute with description and photos, correspondence</td><td>Art. 6(1)(b); Art. 6(1)(c) — tax and accounting law</td><td>as above; accounting records 5 years from the end of the tax year</td></tr>
        <tr><td>Reviews and their publication, companies' replies</td><td>rating, text, job number, date</td><td>Art. 6(1)(f) — legitimate interest: reliable information about companies for customers</td><td>as long as the company's profile is on the platform; at the author's request the text can be anonymised</td></tr>
        <tr><td>Inactive profile from a public register</td><td>register data</td><td>Art. 6(1)(f) — legitimate interest (section 4)</td><td>until a removal request or up to 12 months after deregistration; after removal only the NIP and the date of the request</td></tr>
        <tr><td>Claiming a profile, running the company account, the panel, communication about jobs</td><td>claimed-profile data, account, activity on the platform</td><td>Art. 6(1)(b) — contract for use of the platform</td><td>duration of the contract and 6 years after it ends (limitation of claims)</td></tr>
        <tr><td>Verification and the Fachora Verified mark, trader traceability (Art. 30 DSA), enforcing the Fachora Standard</td><td>verification data, Stripe Connect result, policy, breaches</td><td>Art. 6(1)(c) — Regulation (EU) 2022/2065; Art. 6(1)(f) — safety of customers and the platform</td><td>duration of the contract and 6 months after it ends (DSA); records of serious breaches and a blocked NIP — 5 years</td></tr>
        <tr><td>Commission: invoices, settlements, debt collection</td><td>company data, job amounts, invoices</td><td>Art. 6(1)(b) and (c) (tax law)</td><td>5 years from the end of the tax year</td></tr>
        <tr><td>Handling reports, complaints, appeals and content notices (DSA)</td><td>content of the report, the reporter's details, decision and reasons</td><td>Art. 6(1)(c) (DSA, consumer law); (f) — defence against claims</td><td>3 years from the closing of the case</td></tr>
        <tr><td>Contact form</td><td>form data</td><td>Art. 6(1)(f) — replying to your message</td><td>12 months from the last message</td></tr>
        <tr><td>Security, abuse prevention, logs</td><td>IP addresses, dates, activity log</td><td>Art. 6(1)(f) — platform security</td><td>up to 12 months</td></tr>
        <tr><td>Visit statistics</td><td>anonymised data (section 9)</td><td>Art. 6(1)(f) — improving the service</td><td>indefinitely, as they cannot be linked to a person</td></tr>
      </table>
    </div>
    <p>Providing data is voluntary, but without an e-mail address we cannot accept a request, and without verification data a company cannot become Fachora Verified. When the periods expire we delete or anonymise the data; backups are overwritten within 30 days.</p>

    <h2 id="s4">4. Companies from public registers — on what basis</h2>
    <div class="box"><b>In short:</b> a company from a register is shown only by name, town and type of activity. We do not publish its phone number, e-mail or address. We do not send it e-mails or text messages. The only contact from our side is a letter by post with a code for claiming the profile. A company can request removal of its profile at any time.</div>
    <p>We create inactive profiles of companies in the trades served by Fachora from data made public by CEIDG, KRS and the VAT taxpayer list, so that a customer can check whether a given company is already Fachora Verified and a company can claim its profile. The basis is our legitimate interest (Art. 6(1)(f) GDPR): building a reliable directory of companies and enabling them to join the platform. We have assessed that this interest does not override the traders' rights because: the data are already public for the purposes of business dealings, we publish a minimal set without contact details, we send these companies no electronic communication, and a profile is removed on simple request.</p>
    <p>We update register data periodically from the same sources. After a removal request we keep only the NIP with the date of the request, so as not to create the profile again on the next import; this gives effect to your objection and is not further processing for the original purpose.</p>
    <p>If you received a letter from us: the postal address comes from the register, and the code in the letter serves only to confirm that the person claiming the profile has access to the company's address. The code expires once used.</p>

    <h2 id="s5">5. Who we share data with</h2>
    <h3>Fachora Verified companies (independent controllers)</h3>
    <p>A company that receives your request sees, before submitting an offer: category, description, photos, town or area, urgency and language. <strong>After you choose its offer</strong> it additionally receives your name, the service address and the contact details needed to arrange the appointment. The company is an independent controller of these data and may use them only to carry out the job, issue the invoice and honour the warranty — it commits to this in the terms of service. A company whose offer was not chosen loses access to the request.</p>
    <h3>Processors and other recipients</h3>
    <div class="tbl-wrap">
      <table class="tbl">
        <tr><th>Recipient</th><th>Role</th><th>Purpose</th><th>Where</th></tr>
        <tr><td>Stripe Payments Europe, Ltd. (Dublin, Ireland) and Stripe group companies</td><td>payment provider — <strong>independent controller</strong> for payments and company verification (KYC); partly a processor</td><td>Fachora Protect: taking the customer's payment, holding the funds, paying out the company, refunds; Stripe Connect: verifying the company's identity and bank account. Stripe applies its own privacy policy (stripe.com/privacy)</td><td>EU; Stripe may transfer data to the USA under standard contractual clauses</td></tr>
        <tr><td>Resend, Inc. (USA)</td><td>processor</td><td>sending e-mails (confirmations, offers, reminders, invoices) and delivery logs</td><td>USA — standard contractual clauses</td></tr>
        <tr><td>Railway Corp. (USA)</td><td>processor</td><td>hosting of the application and database</td><td>servers in the EU region (Amsterdam); administrative access from the USA possible under standard contractual clauses</td></tr>
        <tr><td>Anthropic, PBC (USA)</td><td>processor</td><td>AI tools: tidying up and translating the job description, indicative price range, reading data from a liability policy, a dispute summary for the person deciding it (section 8). Submitted content is not used to train models</td><td>USA — standard contractual clauses</td></tr>
        <tr><td>OpenStreetMap Foundation (United Kingdom) — Nominatim service</td><td>processor / service provider</td><td>converting a postal code or town into coordinates to match companies nearby. We send only the postal code or town name, never a name or full address</td><td>United Kingdom — Commission adequacy decision</td></tr>
        <tr><td>Smstools (Belgium)</td><td>processor</td><td>sending text messages: verification codes for companies, notifications of urgent requests, appointment reminders</td><td>EU</td></tr>
        <tr><td>Cloudflare, Inc. (USA) — Turnstile</td><td>processor</td><td>protecting forms against bots; processes the IP address and browser data when a form is submitted</td><td>EU/USA — standard contractual clauses, Data Privacy Framework</td></tr>
        <tr><td>Accountants, tax advisers, lawyers</td><td>processors or independent controllers</td><td>accounting for commission, handling legal disputes</td><td>EU</td></tr>
        <tr><td>Public authorities (e.g. courts, police, data protection authorities, Member State authorities under the DSA)</td><td>independent controllers</td><td>only on a request based on law</td><td>—</td></tr>
      </table>
    </div>
    <p>We conclude data processing agreements with processors (Art. 28 GDPR). We do not sell data and do not share them with advertisers.</p>

    <h2 id="s6">6. Transfers outside the European Economic Area</h2>
    <p>The application and database run on servers in the European Union. Some providers (Resend, Anthropic, Cloudflare, Railway as infrastructure operator, Stripe for support) are based in the USA; data are transferred under standard contractual clauses approved by the European Commission (Art. 46(2)(c) GDPR) and, where the provider is certified, under the EU-US Data Privacy Framework decision. Transfers to the United Kingdom (OpenStreetMap) rely on the Commission's adequacy decision. A copy of the safeguards applied is available on request.</p>

    <h2 id="s7">7. E-mails, text messages and marketing</h2>
    <ul>
      <li><strong>Customers</strong> receive only messages about their job: confirmation, offers, reminders to choose and to approve, payment, dispute, review request, warranty reminder. We do not send customers newsletters without consent.</li>
      <li><strong>Companies that have claimed their profile</strong> receive messages about jobs, verification, payouts, invoices and changes to the terms — these are necessary to perform the contract. News about new features is sent only to companies that opted in in the panel.</li>
      <li><strong>We do not e-mail or text companies from public registers</strong> — neither marketing nor "informational" messages. The first electronic message from us comes only after the company itself has claimed its profile.</li>
      <li><strong>Every e-mail</strong> from Fachora contains a link to opt out of further messages of that kind. Opting out does not cover messages necessary for a job in progress (e.g. information about a dispute).</li>
    </ul>

    <h2 id="s8">8. Automated tools and artificial intelligence</h2>
    <p>We use language models (Anthropic) to: tidy up and translate the job description for companies, estimate an indicative price range from anonymised jobs in the same category and region, read the insurer and expiry date from an uploaded policy, and prepare a dispute summary and a proposed resolution for the person who decides it.</p>
    <p><strong>We do not make decisions about you based solely on automated processing that produce legal effects or similarly significantly affect you</strong> (Art. 22 GDPR). Granting and withdrawing the Fachora Verified mark, deciding a dispute, removing a review and removing a company from the platform are decisions made by a person. Only the following are automated: selecting the companies a request goes to (category, distance, language, rating, response time), recalculating the average rating, and temporarily suspending a profile when a verification condition lapses (e.g. loss of VAT status) — in the last case the company is informed and can appeal to a person.</p>

    <h2 id="s9">9. Cookies and statistics</h2>
    <p>fachora.pl uses only <strong>essential cookies</strong>: the session (keeping a company logged in and protecting forms with a CSRF token), remembering the chosen interface language (Polish/English) and — on forms — the Cloudflare Turnstile cookie to tell people from bots. We use no advertising or tracking cookies and embed no third-party analytics, so we do not show a cookie consent banner.</p>
    <p>We count visits with <strong>our own anonymous counter</strong>: we record the page visited, date, country and type of device, without the IP address and without any identifier that would let us recognise the same person on a later visit. These data cannot be linked to you.</p>
    <p>You can block cookies in your browser; logging in to the company panel and sending forms will then not be possible.</p>

    <h2 id="s10">10. Your rights</h2>
    <p>You have the right to: <strong>access</strong> your data and receive a copy; <strong>rectification</strong> of inaccurate data; <strong>erasure</strong> ("right to be forgotten") — except for data we must keep by law or to defend claims; <strong>restriction of processing</strong>; <strong>data portability</strong> of data we process under a contract by automated means (you receive them in a machine-readable format); <strong>objection</strong> to processing based on legitimate interest — including the profile from a public register (section 4) and marketing, which we always stop on objection; and <strong>withdrawal of consent</strong> at any time where processing is based on consent, without affecting the lawfulness of earlier processing.</p>
    <p>To exercise these rights, write to <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a> (company: preferably from the address linked to the account; customer: from the address that received the job confirmation). We reply within one month at the latest; in complex cases we may extend this by a further two months, and will tell you if so. We may ask you to confirm your identity if the request comes from another address.</p>
    <p>You also have the right to <strong>lodge a complaint with a supervisory authority</strong>: in Poland the President of the Personal Data Protection Office (Prezes UODO, ul. Stawki 2, 00-193 Warsaw, <a href="https://uodo.gov.pl" target="_blank" rel="noopener">uodo.gov.pl</a>).</p>

    <h2 id="s11">11. Security</h2>
    <p>Connections are encrypted (TLS). Passwords are stored only as hashes. Links to job and offer pages are long random tokens that cannot be guessed; forms have attempt limits and anti-spam protection. Access to production systems is limited to the people who need it and is logged. Photos and documents are stored in the database, not at publicly reachable addresses. We make daily backups. Card data and identity documents never enter our systems — they are handled by the payment provider. If a data breach is likely to result in a high risk to your rights, we will inform you without undue delay.</p>

    <h2 id="s12">12. Changes to this policy</h2>
    <p>We update this policy when the law, our providers or the way the platform works change. We inform companies of significant changes by e-mail and in the panel, and customers in messages about their job. The current dated version is always available at fachora.pl/polityka-prywatnosci; previous versions are available on request.</p>

    <h2 id="s13">13. Contact</h2>
    <p>For data protection matters write to <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a> (subject "GDPR") or by post: Budomatch Daniël de Graaf, Białka 15, 09-550 Białka, Poland. The rules for using the platform are in the <a href="{{ route('pl.regulamin') }}">terms of service</a>.</p>

    <div class="disclaimer">Draft pending legal review. Privacy questions: <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a>.</div>
  </div>
</section>
@endsection
