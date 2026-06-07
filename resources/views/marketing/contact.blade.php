@extends('layouts.marketing')

@section('title', 'Contact — EasyInvoice')
@section('description', 'Neem contact op met EasyInvoice. We reageren binnen één werkdag.')

@push('styles')
<style>
  .contact-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 28px; align-items: start; max-width: 1000px; margin: 0 auto; }
  @media (max-width: 760px) { .contact-grid { grid-template-columns: 1fr; } }
  .contact-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 28px; }
  .contact-line { display: flex; gap: 12px; align-items: center; padding: 12px 0; border-top: 1px solid var(--border); }
  .contact-line:first-of-type { border-top: none; }
  .contact-ic { width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0; background: var(--brand-tint); display: inline-flex; align-items: center; justify-content: center; font-size: 16px; }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Contact</div>
    <h1>We helpen je graag</h1>
    <p class="lead">Een vraag, idee of gewoon hallo zeggen — stuur ons een bericht en we reageren binnen één werkdag.</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container contact-grid">
    <div>
      @if (session('contact_success'))
        <div class="alert-success">{{ session('contact_success') }}</div>
      @endif
      @if (session('contact_error'))
        <div class="alert-error">{{ session('contact_error') }}</div>
      @endif
      <form class="contact-card" method="POST" action="{{ route('contact.send') }}">
        @csrf
        <div class="m-row-2">
          <div class="m-field">
            <label for="name">Naam</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required>
            @error('name')<div class="m-err">{{ $message }}</div>@enderror
          </div>
          <div class="m-field">
            <label for="email">E-mailadres</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required>
            @error('email')<div class="m-err">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="m-field">
          <label for="subject">Onderwerp <span style="color:var(--text-4);font-weight:400;">(optioneel)</span></label>
          <input id="subject" type="text" name="subject" value="{{ old('subject') }}">
          @error('subject')<div class="m-err">{{ $message }}</div>@enderror
        </div>
        <div class="m-field">
          <label for="message">Bericht</label>
          <textarea id="message" name="message" rows="6" required>{{ old('message') }}</textarea>
          @error('message')<div class="m-err">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary btn-lg">Verstuur bericht</button>
      </form>
    </div>

    <aside>
      <div class="contact-card">
        <h3 style="font-size:18px;margin-bottom:14px;">Direct contact</h3>
        <div class="contact-line">
          <span class="contact-ic">✉</span>
          <div><div style="font-size:13px;color:var(--text-3);">E-mail</div><a href="mailto:hallo@easyinvoice.nl" style="font-weight:600;color:var(--brand);">hallo@easyinvoice.nl</a></div>
        </div>
        <div class="contact-line">
          <span class="contact-ic">⏱</span>
          <div><div style="font-size:13px;color:var(--text-3);">Reactietijd</div><div style="font-weight:600;">Binnen 1 werkdag</div></div>
        </div>
        <div class="contact-line">
          <span class="contact-ic">📍</span>
          <div><div style="font-size:13px;color:var(--text-3);">Adres</div><div style="font-weight:600;">Amsterdam, Nederland</div></div>
        </div>
      </div>
      <div class="contact-card" style="margin-top:16px;">
        <h3 style="font-size:17px;margin-bottom:8px;">Liever zelf zoeken?</h3>
        <p style="color:var(--text-2);font-size:14px;margin:0 0 12px;">Veel antwoorden vind je direct in ons helpcentrum of de veelgestelde vragen.</p>
        <a href="{{ route('helpcentrum') }}" class="btn btn-secondary" style="margin-right:8px;">Helpcentrum</a>
        <a href="{{ route('faq') }}" class="btn btn-ghost">FAQ</a>
      </div>
    </aside>
  </div>
</section>
@endsection
