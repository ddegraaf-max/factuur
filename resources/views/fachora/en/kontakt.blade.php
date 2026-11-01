@extends('layouts.marketing')

{{-- Contact — Fachora (customers and companies), English edition. The form POSTs to route('contact.send') (shared handler: name, email, subject, message, Turnstile); messages are shown in English regardless of the session content. Companies that want to join are sent to the application form (fachora.apply). --}}

@section('title', 'Contact Fachora — for customers and companies')
@section('description', 'A question about a job, an offer, a Fachora Protect payment, a dispute or company verification? Write to the Fachora team via the form or at ' . brand('email') . ' — we reply within one working day, in English or Polish.')

@push('styles')
<style>
  .contact-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 28px; align-items: start; max-width: 1000px; margin: 0 auto; }
  @media (max-width: 760px) { .contact-grid { grid-template-columns: 1fr; } }
  .contact-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 28px; }
  .contact-line { display: flex; gap: 12px; align-items: center; padding: 12px 0; border-top: 1px solid var(--border); }
  .contact-line:first-of-type { border-top: none; }
  .contact-ic { width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0; background: var(--brand-tint); display: inline-flex; align-items: center; justify-content: center; font-size: 16px; }
  .contact-card h2 { font-size: 18px; margin-bottom: 12px; }
  .contact-card p { color: var(--text-2); font-size: 14px; line-height: 1.65; margin: 0 0 12px; }
  .contact-card a.lnk { color: var(--brand); font-weight: 500; }
  .contact-help { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; max-width: 1000px; margin: 36px auto 0; }
  @media (max-width: 860px) { .contact-help { grid-template-columns: 1fr; } }
  .contact-help h3 { font-size: 16px; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
  .contact-help p { color: var(--text-2); font-size: 14px; line-height: 1.65; margin: 0 0 8px; }
  .contact-help ul { margin: 0; padding-left: 18px; color: var(--text-2); font-size: 14px; line-height: 1.65; }
  .contact-urgent { max-width: 1000px; margin: 20px auto 0; padding: 14px 18px; border-radius: 12px; background: var(--accent-tint); border: 1px solid #F8C9A3; font-size: 13.5px; color: var(--text-2); line-height: 1.6; }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Contact</div>
    <h1>We are on both sides of the job</h1>
    <p class="lead">Customer or company — write to us. We reply within one working day, in English or Polish.</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container contact-grid">
    <div>
      @if (session('contact_success'))
        <div class="alert-success">Thank you! Your message has been sent — we will reply within one working day.</div>
      @endif
      @if (session('contact_error'))
        <div class="alert-error">Something went wrong while sending. Write to us directly at <a href="mailto:{{ brand('email') }}" style="text-decoration:underline;">{{ brand('email') }}</a>.</div>
      @endif
      <form class="contact-card" method="POST" action="{{ route('contact.send') }}">
        @csrf
        <div class="m-row-2">
          <div class="m-field">
            <label for="name">Your name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name">
            @error('name')<div class="m-err">{{ $message }}</div>@enderror
          </div>
          <div class="m-field">
            <label for="email">E-mail address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
            @error('email')<div class="m-err">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="m-field">
          <label for="subject">Subject <span style="color:var(--text-4);font-weight:400;">(optional — e.g. your job number FA-…)</span></label>
          <input id="subject" type="text" name="subject" value="{{ old('subject') }}">
          @error('subject')<div class="m-err">{{ $message }}</div>@enderror
        </div>
        <div class="m-field">
          <label for="message">Message</label>
          <textarea id="message" name="message" rows="6" required placeholder="Tell us how we can help. If it concerns a specific job, include its number — you will find it in our e-mail.">{{ old('message') }}</textarea>
          @error('message')<div class="m-err">{{ $message }}</div>@enderror
        </div>
        @if(config('services.turnstile.sitekey'))
          <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.sitekey') }}" data-language="en" style="margin-bottom:14px;"></div>
          @error('cf-turnstile-response')<div class="m-err">{{ $message }}</div>@enderror
          <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endif
        <button type="submit" class="btn btn-primary btn-lg">Send message</button>
        <div style="font-size:12.5px;color:var(--text-3);margin-top:12px;">By sending the form you agree to the processing of your data so that we can reply — see the <a href="{{ route('pl.prywatnosc') }}" style="color:var(--brand);">privacy policy</a>.</div>
      </form>
    </div>

    <aside>
      <div class="contact-card">
        <h2>Direct contact</h2>
        <div class="contact-line">
          <span class="contact-ic">✉</span>
          <div><div style="font-size:13px;color:var(--text-3);">E-mail</div><a href="mailto:{{ brand('email') }}" style="font-weight:600;color:var(--brand);">{{ brand('email') }}</a></div>
        </div>
        <div class="contact-line">
          <span class="contact-ic">⏱</span>
          <div><div style="font-size:13px;color:var(--text-3);">Response time</div><div style="font-weight:600;">Within 1 working day</div><div style="font-size:13px;color:var(--text-2);">Fachora Protect disputes: we keep both sides informed while the case is being reviewed.</div></div>
        </div>
        <div class="contact-line">
          <span class="contact-ic">📍</span>
          <div><div style="font-size:13px;color:var(--text-3);">Platform operator</div><div style="font-weight:600;">Creditline B.V.</div><div style="font-size:13px;color:var(--text-2);">Torenlaan 5B, 1402 AT Bussum, the Netherlands<br>KvK 59683198 · VAT NL853603108B01</div></div>
        </div>
      </div>

      <div class="contact-card" style="margin-top:16px;border-color:var(--brand-border);background:var(--brand-tint);">
        <h2>Run a company and want to join?</h2>
        <p>Skip the form — apply directly. We check your company in the registers and you complete verification in the panel. No subscription, a commission only on completed jobs.</p>
        <a href="{{ route('fachora.apply') }}" class="btn btn-primary" style="margin-right:8px;margin-bottom:8px;">Apply as a company →</a>
        <a href="{{ route('fachora.claim') }}" class="btn btn-secondary" style="margin-bottom:8px;">I have a code from a letter</a>
        <p style="margin:8px 0 0;font-size:13px;color:var(--text-3);">Received a letter or a customer request from us? We never send register companies advertising, newsletters or text messages. The only e-mail such a company can get from us is a concrete request from a customer who chose that company, with information on where we got its details and an unsubscribe link.</p>
      </div>

      <div class="contact-card" style="margin-top:16px;">
        <h2>Already have a job on Fachora?</h2>
        <p>The quickest way is your job page — the "My job" link is in the e-mail we sent you. That is where you choose an offer, approve the work, open a dispute and leave a review.</p>
        <a href="{{ route('pl.faq') }}" class="btn btn-secondary">Frequently asked questions</a>
      </div>
    </aside>
  </div>

  <div class="container">
    <div class="contact-help">
      <div class="contact-card">
        <h3>👤 For customers</h3>
        <p>Questions about a job, offers, appointments, <a class="lnk" href="{{ route('fachora.protect') }}">Fachora Protect</a>, refunds or a dispute with a company. Include your job number — we will answer faster.</p>
        <ul>
          <li>No offers received? Write to us — we will look for a company by hand.</li>
          <li>The company did not show up? That breaches the Fachora Standard, please report it.</li>
          <li>You open a dispute on your job page; the company has {{ (int) config('fachora.dispute_hours') }} hours to reply.</li>
        </ul>
      </div>
      <div class="contact-card">
        <h3>🔧 For companies</h3>
        <p>Verification, the panel, payouts through Mollie, commission invoices, a suspended profile or removal of your register data. <a class="lnk" href="{{ route('login') }}">Log in</a> to check your verification status.</p>
        <ul>
          <li>Want to join? Use the <a class="lnk" href="{{ route('fachora.apply') }}">application form</a>.</li>
          <li>Do not want to be listed on Fachora? Write to us — we will remove the register profile.</li>
        </ul>
      </div>
      <div class="contact-card">
        <h3>⚖️ Content, legal, data</h3>
        <p>Reporting illegal content (for example a review or a company description), the point of contact for authorities and users (DSA), your GDPR rights and questions about the <a class="lnk" href="{{ route('pl.regulamin') }}">terms of service</a>: the same address, <a class="lnk" href="mailto:{{ brand('email') }}">{{ brand('email') }}</a>.</p>
        <ul>
          <li>Put "Content report", "GDPR" or "DSA" in the subject line.</li>
          <li>See also the <a class="lnk" href="{{ route('pl.prywatnosc') }}">privacy policy</a>.</li>
        </ul>
      </div>
    </div>

    <div class="contact-urgent">
      <strong>Emergency?</strong> Fachora is not an emergency service. If you smell gas, see a fire or someone's life is in danger, call <strong>112</strong> (gas emergency: <strong>992</strong>). Urgent but safe repairs can be submitted as a "right now" request — Verified companies also receive those by text message.
    </div>
  </div>
</section>
@endsection
