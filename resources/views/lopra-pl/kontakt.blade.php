@extends('layouts.marketing')

{{-- Kontakt — Lopra Polska. Formularz wysyła POST na route('contact.send') (wspólny handler); komunikaty sukcesu/błędu pokazujemy po polsku niezależnie od treści z sesji. --}}

@section('title', 'Kontakt z ' . brand('name') . ' — odpowiadamy w ciągu jednego dnia roboczego')
@section('description', 'Masz pytanie o faktury, KSeF, abonament lub windykację? Napisz do zespołu ' . brand('name') . ' przez formularz albo na ' . brand('email') . ' — odpowiadamy w ciągu jednego dnia roboczego, także w okresie próbnym.')

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
    <div class="eyebrow">Kontakt</div>
    <h1>Chętnie pomożemy</h1>
    <p class="lead">Pytanie, pomysł albo po prostu „dzień dobry” — napisz do nas. Odpowiadamy w ciągu jednego dnia roboczego, po polsku.</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container contact-grid">
    <div>
      @if (session('contact_success'))
        <div class="alert-success">Dziękujemy! Twoja wiadomość została wysłana — odpowiemy w ciągu jednego dnia roboczego.</div>
      @endif
      @if (session('contact_error'))
        <div class="alert-error">Coś poszło nie tak przy wysyłce. Napisz do nas bezpośrednio na <a href="mailto:{{ brand('email') }}" style="text-decoration:underline;">{{ brand('email') }}</a>.</div>
      @endif
      <form class="contact-card" method="POST" action="{{ route('contact.send') }}">
        @csrf
        <div class="m-row-2">
          <div class="m-field">
            <label for="name">Imię i nazwisko</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name">
            @error('name')<div class="m-err">{{ $message }}</div>@enderror
          </div>
          <div class="m-field">
            <label for="email">Adres e-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
            @error('email')<div class="m-err">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="m-field">
          <label for="subject">Temat <span style="color:var(--text-4);font-weight:400;">(opcjonalnie)</span></label>
          <input id="subject" type="text" name="subject" value="{{ old('subject') }}">
          @error('subject')<div class="m-err">{{ $message }}</div>@enderror
        </div>
        <div class="m-field">
          <label for="message">Wiadomość</label>
          <textarea id="message" name="message" rows="6" required>{{ old('message') }}</textarea>
          @error('message')<div class="m-err">{{ $message }}</div>@enderror
        </div>
        @if(config('services.turnstile.sitekey'))
          <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.sitekey') }}" data-language="pl" style="margin-bottom:14px;"></div>
          @error('cf-turnstile-response')<div class="m-err">{{ $message }}</div>@enderror
          <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endif
        <button type="submit" class="btn btn-primary btn-lg">Wyślij wiadomość</button>
        <div style="font-size:12.5px;color:var(--text-3);margin-top:12px;">Wysyłając formularz, zgadzasz się na przetwarzanie danych w celu odpowiedzi na Twoją wiadomość — zob. <a href="{{ route('pl.prywatnosc') }}" style="color:var(--brand);">polityka prywatności</a>.</div>
      </form>
    </div>

    <aside>
      <div class="contact-card">
        <h2 style="font-size:18px;margin-bottom:14px;">Kontakt bezpośredni</h2>
        <div class="contact-line">
          <span class="contact-ic">✉</span>
          <div><div style="font-size:13px;color:var(--text-3);">E-mail</div><a href="mailto:{{ brand('email') }}" style="font-weight:600;color:var(--brand);">{{ brand('email') }}</a></div>
        </div>
        <div class="contact-line">
          <span class="contact-ic">⏱</span>
          <div><div style="font-size:13px;color:var(--text-3);">Czas odpowiedzi</div><div style="font-weight:600;">Do 1 dnia roboczego</div></div>
        </div>
        <div class="contact-line">
          <span class="contact-ic">📍</span>
          <div><div style="font-size:13px;color:var(--text-3);">Usługodawca</div><div style="font-weight:600;">Creditline B.V.</div><div style="font-size:13px;color:var(--text-2);">Torenlaan 5B, 1402 AT Bussum, Holandia</div></div>
        </div>
      </div>

      <div class="contact-card" style="margin-top:16px;">
        <div class="contact-partner" style="margin-bottom:10px;">
          <div class="lg">C</div>
          <div><div style="font-weight:700;">Creditline Polska</div><div style="font-size:13px;color:var(--text-3);">Partner windykacyjny {{ brand('name') }}</div></div>
        </div>
        <p style="color:var(--text-2);font-size:14px;margin:0 0 12px;line-height:1.6;">Sprawy windykacyjne, wykup wierzytelności i pytania o przekazane sprawy obsługuje bezpośrednio Creditline Polska.</p>
        <a href="https://creditline.pl" target="_blank" rel="noopener" class="btn btn-secondary">creditline.pl →</a>
      </div>

      <div class="contact-card" style="margin-top:16px;">
        <h2 style="font-size:17px;margin-bottom:8px;">Wolisz poszukać samodzielnie?</h2>
        <p style="color:var(--text-2);font-size:14px;margin:0 0 12px;">Wiele odpowiedzi znajdziesz od razu w najczęstszych pytaniach. Chcesz zobaczyć aplikację? Zajrzyj do demo.</p>
        <a href="{{ route('pl.faq') }}" class="btn btn-secondary" style="margin-right:8px;">Najczęstsze pytania</a>
        <a href="{{ route('demo') }}" class="btn btn-ghost">Demo</a>
      </div>
    </aside>
  </div>

  <div class="container" style="max-width:1000px;margin-top:36px;">
    <div class="contact-card">
      <h2 style="font-size:19px;margin-bottom:10px;">W czym możemy pomóc?</h2>
      <p style="color:var(--text-2);font-size:14px;line-height:1.7;margin:0 0 10px;">
        <strong>Pytania o korzystanie z aplikacji</strong> — wystawienie faktury, plik do KSeF, rozliczenie VAT, ustawienie przypomnień albo import z innego programu: większość odpowiedzi znajdziesz w
        <a href="{{ route('pl.faq') }}" style="color:var(--brand);font-weight:500;">najczęstszych pytaniach</a>.
        Jeśli czegoś brakuje, przejrzymy sprawę razem z Tobą — także w okresie próbnym.
      </p>
      <p style="color:var(--text-2);font-size:14px;line-height:1.7;margin:0 0 10px;">
        <strong>Abonament lub faktura od nas</strong> — pytania o pakiet Podstawowy i Smart, zmianę pakietu lub rezygnację. Przenosisz się z Fakturowni, iFirmy, wFirmy lub inFaktu? Pomożemy bezpłatnie przenieść klientów, produkty i otwarte faktury.
      </p>
      <p style="color:var(--text-2);font-size:14px;line-height:1.7;margin:0;">
        <strong>Windykacja</strong> — o przekazane sprawy, wycenę i wykup wierzytelności pytaj bezpośrednio w <a href="https://creditline.pl" target="_blank" rel="noopener" style="color:var(--brand);font-weight:500;">Creditline Polska</a>.
        Coś nie działa? Sprawdź najpierw <a href="{{ route('status') }}" style="color:var(--brand);font-weight:500;">status systemu</a>.
      </p>
    </div>
  </div>
</section>
@endsection
