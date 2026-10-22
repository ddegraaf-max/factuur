@extends('layouts.marketing')

{{-- About — Fachora, English edition: why it exists, the promise, who is behind it (Budomatch, a Polish company that connects customers with tradespeople), principles. No invented numbers or people. --}}

@section('title', 'About us — Fachora, a Budomatch service')
@section('description', 'Why Fachora exists: for foreigners in Poland and for Poles who are tired of "I will come tomorrow". Who is behind it (Budomatch, a Polish company that connects customers with tradespeople) and the principles we keep: not every company gets on Fachora, no advertising to companies, price up front, money kept safe.')

@push('styles')
<style>
  .about-principles { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; max-width: 960px; margin: 0 auto; }
  @media (max-width: 720px) { .about-principles { grid-template-columns: 1fr; } }
  .about-principle { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 24px; }
  .about-principle .n { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 10px; background: var(--brand); color: #fff; font-family: var(--font-display); font-weight: 700; font-size: 14px; margin-bottom: 12px; }
  .about-principle h3 { font-size: 18px; margin-bottom: 6px; }
  .about-principle p { color: var(--text-2); font-size: 14.5px; line-height: 1.65; margin: 0; }
  .about-promise { max-width: 760px; margin: 0 auto; background: var(--brand); color: #fff; border-radius: 20px; padding: 32px 36px; }
  .about-promise h2 { color: #fff; font-size: 24px; margin-bottom: 10px; }
  .about-promise p { color: rgba(255,255,255,0.85); line-height: 1.7; margin: 0 0 10px; }
  .about-promise .tag { display: inline-block; color: var(--accent-light); font-family: var(--font-display); font-weight: 700; font-size: 20px; letter-spacing: -0.01em; }
  .about-who { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; max-width: 960px; margin: 24px auto 0; }
  @media (max-width: 760px) { .about-who { grid-template-columns: 1fr; } }
  .about-who div { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 20px; font-size: 14px; color: var(--text-2); line-height: 1.6; }
  .about-who b { display: block; font-family: var(--font-display); font-size: 16px; color: var(--text); margin-bottom: 4px; }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">About us</div>
    <h1>A tradesperson you can trust before you open the door</h1>
    <p class="lead">Fachora grew out of one question: how do I know this plumber will turn up, do the job properly and not charge three times what he said? We built a platform that answers it before anyone arrives.</p>
  </div>
</section>

<section class="section" style="padding-top:48px;">
  <div class="container">
    <div class="prose">
      <h2>Why Fachora</h2>
      <p>It started with foreigners in Poland. If you do not speak Polish, you have no way to check a company, agree a price or push back when something goes wrong. Recommendations from friends run out quickly, and typing "hydraulik" into a search engine gives you a list of phone numbers, half of which never pick up. So the first version of Fachora is for them: service in English, German, Dutch and Ukrainian, companies that speak your language, and all communication through the platform.</p>
      <p>It soon turned out that Poles have the same problem, they just call it something else: <em>"przyjdę jutro"</em> — "I'll come tomorrow". The tradesperson who never comes. The "quote on site" that keeps growing while the work is being done. No invoice, no warranty, nobody to turn to. Fachora is for anyone who is tired of guessing.</p>

      <h2>What we promise</h2>
      <p>We do not sell a plumber. We sell the certainty that a job will go the way it was agreed. That is why every job on Fachora has three fixed elements: <strong>a price agreed up front</strong> (extra work only with your approval), <strong>a reliable appointment</strong> (the company confirms, and cancels only for a reason) and <strong>a warranty</strong> ({{ (int) config('fachora.default_warranty_months') }} months by default, always with an invoice). With <a href="{{ route('fachora.protect') }}">Fachora Protect</a> there is a fourth: you only pay once the work is finished and approved — until then the money waits with the payment provider, not with the company and not with us.</p>
    </div>

    <div class="about-promise" style="margin-top:40px;">
      <span class="tag">Clear price. Reliable appointment. Warranty.</span>
      <h2>Not every company gets on Fachora</h2>
      <p>A company is on Fachora only as <strong>Fachora Verified</strong>: after a check in CEIDG or KRS and the VAT white list, after confirming its e-mail and phone, after Stripe has verified the owner's identity and bank account, and after accepting the <a href="{{ route('fachora.standard') }}" style="color:#fff;text-decoration:underline;">Fachora Standard</a> — ten rules whose breach leads to suspension.</p>
      <p>Companies from the public registers that have not gone through this are shown only by name and town. We do not send them jobs and we do not show their contact details.</p>
    </div>
  </div>
</section>

<section class="section section-alt" style="padding-top:64px;padding-bottom:64px;">
  <div class="container">
    <div class="section-header" style="margin-bottom:32px;">
      <h2>Who is behind it</h2>
      <p>Fachora is a service of Budomatch — a Polish company from Białka in Mazovia that connects customers with tradespeople and builds tools for small businesses.</p>
    </div>
    <div class="about-who">
      <div><b>Budomatch</b>Platform operator. Budomatch Daniël de Graaf · Białka 15, 09-550 Białka, Poland · NIP 7010869430 · REGON 381430120. Years of working with tradespeople taught us one thing: the most expensive agreements are the ones nobody wrote down.</div>
      <div><b>Invoicing software included</b>Fachora Verified companies issue their invoices and settle jobs in the software they get together with their profile — ready for KSeF, the Polish e-invoicing system.</div>
      <div><b>Fachora</b>The same technology that makes sure an invoice gets paid now makes sure a service is delivered as agreed. A Polish brand, service in five languages.</div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header" style="margin-bottom:36px;"><h2>The principles we keep</h2></div>
    <div class="about-principles">
      <div class="about-principle"><span class="n">1</span><h3>Not every company gets on Fachora</h3><p>Registers, identity, bank account, the Fachora Standard. A company whose rating drops below {{ number_format((float) config('fachora.min_rating'), 1, '.', '') }} or that collects justified complaints is removed from the list. We would rather have fewer companies than companies we cannot vouch for.</p></div>
      <div class="about-principle"><span class="n">2</span><h3>No advertising to companies</h3><p>We never send companies from public registers advertising, newsletters or text messages. A company gets an e-mail from us only when a customer chose it for their job — always with information on where we got its details and an unsubscribe link.</p></div>
      <div class="about-principle"><span class="n">3</span><h3>Price up front</h3><p>You receive an offer with a gross price before anyone arrives. Extra work only as a separate line that you approve on your job page. No surprises on the way out.</p></div>
      <div class="about-principle"><span class="n">4</span><h3>Money kept safe</h3><p>With Fachora Protect you reserve the amount through Stripe. We never hold your money. The company is paid out after your approval, and in a dispute we decide — based on photos, the description and both sides' replies.</p></div>
      <div class="about-principle"><span class="n">5</span><h3>Real reviews</h3><p>Only a customer who completed a job can leave a review. The company can reply but cannot delete it. We do not publish statistics we do not have.</p></div>
      <div class="about-principle"><span class="n">6</span><h3>Free for customers</h3><p>Fachora earns a commission from the company, and only on completed jobs. No subscription, no registration fee, no paying for leads. A company pays once it has earned.</p></div>
    </div>
  </div>
</section>

<section class="section section-alt" style="padding:56px 0;">
  <div class="container">
    <div class="prose" style="text-align:center;">
      <h2 style="margin-top:0;">Contact</h2>
      <p>Questions, remarks, ideas — critical ones too — go to <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a> or through the <a href="{{ route('pl.kontakt') }}">contact form</a>. We reply within one working day, in English or Polish. Run a company and want to join? See the <a href="{{ route('fachora.for-companies') }}">page for companies</a>.</p>
    </div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Describe what needs doing</h2>
    <p>Offers from Fachora Verified companies near you — free, no account, no phone calls.</p>
    <a href="{{ route('fachora.intake') }}" class="btn btn-white btn-lg">Find a tradesperson →</a>
  </div>
</section>
@endsection
