@extends('layouts.marketing')

@section('title', 'Zocht u een ander EasyInvoice? — EasyInvoice')
@section('description', 'EasyInvoice (easyinvoice.nl) is het Nederlandse factuurprogramma voor zzp en mkb. Zocht u een ander bedrijf met een vergelijkbare naam? Laat het ons weten.')

@push('styles')
<style>
  .vw-wrap { max-width: 640px; margin: 0 auto; }
  .vw-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 26px 28px; margin-top: 24px; }
  .vw-card h2 { font-size: 18px; margin-bottom: 8px; }
  .vw-field { margin-bottom: 14px; }
  .vw-field label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
  .vw-field input, .vw-field textarea { width: 100%; }
  .vw-ok { background: var(--success-bg); border: 1px solid var(--success-border); color: var(--success); border-radius: 10px; padding: 14px 16px; margin-top: 24px; font-size: 14px; }
  .m-err { color: var(--brand); font-size: 12.5px; margin-top: 4px; }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Even checken</div>
    <h1>Zocht u een ander EasyInvoice?</h1>
    <p class="lead">Wij zijn <b>EasyInvoice</b>, het Nederlandse factuurprogramma voor zzp'ers en mkb (easyinvoice.nl). Er bestaan bedrijven met een vergelijkbare naam. Zoekt u bijvoorbeeld informatie over uitbetalingen, affiliates of een account dat u niet bij ons heeft aangemaakt, dan bent u waarschijnlijk hier verkeerd terechtgekomen.</p>
  </div>
</section>

<section class="section" style="padding-top:20px;">
  <div class="container vw-wrap">
    @if(session('confusion_sent'))
      <div class="vw-ok">Bedankt voor uw bericht — dat helpt ons om verwarring te voorkomen. U hoeft verder niets te doen.</div>
    @else
      <div class="vw-card">
        <h2>Laat het ons weten</h2>
        <p style="font-size:14px;color:var(--text-3);margin-bottom:16px;">Twee vragen, dertig seconden. Uw antwoord gebruiken we alleen om vast te leggen dat er verwarring is ontstaan; we nemen geen contact op tenzij u dat wilt.</p>
        <form method="POST" action="{{ route('confusion.send') }}">
          @csrf
          <div class="vw-field">
            <label for="looking_for">Wat of wie zocht u precies?</label>
            <textarea id="looking_for" name="looking_for" rows="3" required placeholder="Bijv. 'mijn uitbetaling van vorige maand' of 'de affiliate-omgeving'">{{ old('looking_for') }}</textarea>
            @error('looking_for')<div class="m-err">{{ $message }}</div>@enderror
          </div>
          <div class="vw-field">
            <label for="how">Hoe kwam u bij ons terecht? <span style="color:var(--text-4);font-weight:400;">(optioneel)</span></label>
            <input id="how" type="text" name="how" value="{{ old('how') }}" placeholder="Bijv. via Google, een e-mail, een link in een app">
          </div>
          <div class="vw-field">
            <label for="email">Uw e-mailadres <span style="color:var(--text-4);font-weight:400;">(optioneel, alleen als u een reactie wilt)</span></label>
            <input id="email" type="email" name="email" value="{{ old('email') }}">
            @error('email')<div class="m-err">{{ $message }}</div>@enderror
          </div>
          @if(config('services.turnstile.sitekey'))
            <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.sitekey') }}" style="margin-bottom:14px;"></div>
            @error('cf-turnstile-response')<div class="m-err">{{ $message }}</div>@enderror
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
          @endif
          <button type="submit" class="btn btn-primary">Versturen</button>
        </form>
      </div>
    @endif

    <div class="vw-card">
      <h2>Wel bij ons aan het juiste adres?</h2>
      <p style="font-size:14px;color:var(--text-2);line-height:1.7;">Dan helpen we graag: <a href="{{ route('helpcentrum') }}" style="color:var(--brand);font-weight:600;">helpcentrum</a>, <a href="{{ route('faq') }}" style="color:var(--brand);font-weight:600;">veelgestelde vragen</a> of <a href="{{ route('contact') }}" style="color:var(--brand);font-weight:600;">contact</a>. Klanten van een ondernemer die EasyInvoice gebruikt, vinden hun facturen en offertes in het <a href="{{ route('portal.login') }}" style="color:var(--brand);font-weight:600;">klantenportaal</a>.</p>
    </div>
  </div>
</section>
@endsection
