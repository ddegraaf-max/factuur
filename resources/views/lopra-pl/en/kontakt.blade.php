@extends('layouts.marketing')

{{-- Contact — Lopra Polska, English edition. The form POSTs to route('contact.send') (shared handler); success/error messages are shown in English regardless of the session content. --}}

@section('title', 'Contact ' . brand('name') . ' — we reply within one working day')
@section('description', 'Have a question about invoices, KSeF, your subscription or debt collection? Write to the ' . brand('name') . ' team via the form or at ' . brand('email') . ' — we reply within one working day, also during your trial.')

@push('styles')
<style>
  .contact-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 28px; align-items: start; max-width: 1000px; margin: 0 auto; }
  @media (max-width: 760px) { .contact-grid { grid-template-columns: 1fr; } }
  .contact-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 28px; }
  .contact-line { display: flex; gap: 12px; align-items: center; padding: 12px 0; border-top: 1px solid var(--border); }
  .contact-line:first-of-type { border-top: none; }
  .contact-ic { width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0; background: var(--brand-tint); display: inline-flex; align-items: center; justify-content: center; font-size: 16px; }
  .contact-partner { display: flex; align-items: center; gap: 12px; }
  .contact-partner .lg { width: 38px; height: 38px; border-radius: 10px; background: #ec3013; color: #fff; font-family: var(--font-display); font-weight: 700; font-size: 18px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Contact</div>
    <h1>Happy to help</h1>
    <p class="lead">A question, an idea or simply a "hello" — write to us. We reply within one working day, in English or Polish.</p>
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
            <label for="name">Full name</label>
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
          <label for="subject">Subject <span style="color:var(--text-4);font-weight:400;">(optional)</span></label>
          <input id="subject" type="text" name="subject" value="{{ old('subject') }}">
          @error('subject')<div class="m-err">{{ $message }}</div>@enderror
        </div>
        <div class="m-field">
          <label for="message">Message</label>
          <textarea id="message" name="message" rows="6" required>{{ old('message') }}</textarea>
          @error('message')<div class="m-err">{{ $message }}</div>@enderror
        </div>
        @if(config('services.turnstile.sitekey'))
          <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.sitekey') }}" data-language="en" style="margin-bottom:14px;"></div>
          @error('cf-turnstile-response')<div class="m-err">{{ $message }}</div>@enderror
          <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endif
        <button type="submit" class="btn btn-primary btn-lg">Send message</button>
        <div style="font-size:12.5px;color:var(--text-3);margin-top:12px;">By sending this form you agree to the processing of your data so that we can reply to your message — see the <a href="{{ route('pl.prywatnosc') }}" style="color:var(--brand);">privacy policy</a>.</div>
      </form>
    </div>

    <aside>
      <div class="contact-card">
        <h2 style="font-size:18px;margin-bottom:14px;">Direct contact</h2>
        <div class="contact-line">
          <span class="contact-ic">✉</span>
          <div><div style="font-size:13px;color:var(--text-3);">E-mail</div><a href="mailto:{{ brand('email') }}" style="font-weight:600;color:var(--brand);">{{ brand('email') }}</a></div>
        </div>
        <div class="contact-line">
          <span class="contact-ic">⏱</span>
          <div><div style="font-size:13px;color:var(--text-3);">Response time</div><div style="font-weight:600;">Within 1 working day</div></div>
        </div>
        <div class="contact-line">
          <span class="contact-ic">📍</span>
          <div><div style="font-size:13px;color:var(--text-3);">Service provider</div><div style="font-weight:600;">Creditline B.V.</div><div style="font-size:13px;color:var(--text-2);">Torenlaan 5B, 1402 AT Bussum, the Netherlands</div></div>
        </div>
      </div>

      <div class="contact-card" style="margin-top:16px;">
        <div class="contact-partner" style="margin-bottom:10px;">
          <div class="lg">C</div>
          <div><div style="font-weight:700;">Creditline Polska</div><div style="font-size:13px;color:var(--text-3);">Debt collection partner of {{ brand('name') }}</div></div>
        </div>
        <p style="color:var(--text-2);font-size:14px;margin:0 0 12px;line-height:1.6;">Collection cases, receivables purchase and questions about cases already handed over are handled directly by Creditline Polska.</p>
        <a href="https://creditline.pl" target="_blank" rel="noopener" class="btn btn-secondary">creditline.pl →</a>
      </div>

      <div class="contact-card" style="margin-top:16px;">
        <h2 style="font-size:17px;margin-bottom:8px;">Prefer to look it up yourself?</h2>
        <p style="color:var(--text-2);font-size:14px;margin:0 0 12px;">You will find many answers straight away in the frequently asked questions. Want to see the app? Take a look at the demo.</p>
        <a href="{{ route('pl.faq') }}" class="btn btn-secondary" style="margin-right:8px;">Frequently asked questions</a>
        <a href="{{ route('demo') }}" class="btn btn-ghost">Demo</a>
      </div>
    </aside>
  </div>

  <div class="container" style="max-width:1000px;margin-top:36px;">
    <div class="contact-card">
      <h2 style="font-size:19px;margin-bottom:10px;">How can we help?</h2>
      <p style="color:var(--text-2);font-size:14px;line-height:1.7;margin:0 0 10px;">
        <strong>Questions about using the app</strong> — issuing an invoice, the KSeF file, VAT settlement, setting up reminders or importing from another program: most answers are in the
        <a href="{{ route('pl.faq') }}" style="color:var(--brand);font-weight:500;">frequently asked questions</a>.
        If something is missing, we will go through it together with you — also during your trial.
      </p>
      <p style="color:var(--text-2);font-size:14px;line-height:1.7;margin:0 0 10px;">
        <strong>Your subscription or an invoice from us</strong> — questions about the Podstawowy and Smart plans, changing plan or cancelling. Switching from Fakturownia, iFirma, wFirma or inFakt? We will help you move your clients, products and open invoices free of charge.
      </p>
      <p style="color:var(--text-2);font-size:14px;line-height:1.7;margin:0;">
        <strong>Debt collection</strong> — for cases already handed over, quotes and receivables purchase, ask <a href="https://creditline.pl" target="_blank" rel="noopener" style="color:var(--brand);font-weight:500;">Creditline Polska</a> directly.
        Something not working? Check the <a href="{{ route('status') }}" style="color:var(--brand);font-weight:500;">system status</a> first.
      </p>
    </div>
  </div>
</section>
@endsection
