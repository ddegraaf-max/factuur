@extends('layouts.marketing')

{{-- Kontakt — Fachora (klienci i firmy). Formularz wysyła POST na route('contact.send') (wspólny handler: name, email, subject, message, Turnstile); komunikaty pokazujemy po polsku niezależnie od treści z sesji. Firmy, które chcą dołączyć, kierujemy do formularza zgłoszenia (fachora.apply). --}}

@section('title', 'Kontakt z Fachora — dla klientów i firm')
@section('description', 'Pytanie o zlecenie, ofertę, płatność Fachora Protect, spór albo weryfikację firmy? Napisz do zespołu Fachora przez formularz albo na ' . brand('email') . ' — odpowiadamy w ciągu jednego dnia roboczego, po polsku i po angielsku.')

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
    <div class="eyebrow">Kontakt</div>
    <h1>Jesteśmy po obu stronach zlecenia</h1>
    <p class="lead">Klient czy firma — napisz do nas. Odpowiadamy w ciągu jednego dnia roboczego, po polsku i po angielsku.</p>
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
          <label for="subject">Temat <span style="color:var(--text-4);font-weight:400;">(opcjonalnie — np. numer zlecenia FA-…)</span></label>
          <input id="subject" type="text" name="subject" value="{{ old('subject') }}">
          @error('subject')<div class="m-err">{{ $message }}</div>@enderror
        </div>
        <div class="m-field">
          <label for="message">Wiadomość</label>
          <textarea id="message" name="message" rows="6" required placeholder="Opisz, w czym możemy pomóc. Jeśli chodzi o konkretne zlecenie, podaj jego numer — znajdziesz go w e-mailu od nas.">{{ old('message') }}</textarea>
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
        <h2>Kontakt bezpośredni</h2>
        <div class="contact-line">
          <span class="contact-ic">✉</span>
          <div><div style="font-size:13px;color:var(--text-3);">E-mail</div><a href="mailto:{{ brand('email') }}" style="font-weight:600;color:var(--brand);">{{ brand('email') }}</a></div>
        </div>
        <div class="contact-line">
          <span class="contact-ic">⏱</span>
          <div><div style="font-size:13px;color:var(--text-3);">Czas odpowiedzi</div><div style="font-weight:600;">Do 1 dnia roboczego</div><div style="font-size:13px;color:var(--text-2);">Spory w ramach Fachora Protect: w trakcie rozpatrywania informujemy obie strony na bieżąco.</div></div>
        </div>
        <div class="contact-line">
          <span class="contact-ic">📍</span>
          <div><div style="font-size:13px;color:var(--text-3);">Operator platformy</div><div style="font-weight:600;">Budomatch Daniël de Graaf</div><div style="font-size:13px;color:var(--text-2);">Białka 15, 09-550 Białka, Polska<br>NIP 7010869430 · REGON 381430120</div></div>
        </div>
      </div>

      <div class="contact-card" style="margin-top:16px;border-color:var(--brand-border);background:var(--brand-tint);">
        <h2>Prowadzisz firmę i chcesz dołączyć?</h2>
        <p>Nie pisz przez formularz — zgłoś firmę bezpośrednio. Sprawdzimy ją w rejestrach, a Ty przejdziesz weryfikację w panelu. Bez abonamentu, prowizja tylko od wykonanych zleceń.</p>
        <a href="{{ route('fachora.apply') }}" class="btn btn-primary" style="margin-right:8px;margin-bottom:8px;">Zgłoś firmę →</a>
        <a href="{{ route('fachora.claim') }}" class="btn btn-secondary" style="margin-bottom:8px;">Mam kod z listu</a>
        <p style="margin:8px 0 0;font-size:13px;color:var(--text-3);">Dostałeś od nas list lub zapytanie klienta? Firmom z rejestru nie wysyłamy reklam, newsletterów ani SMS-ów. Jedyny e-mail, jaki taka firma może od nas dostać, to konkretne zapytanie klienta, który sam ją wybrał — z informacją, skąd mamy jej dane, i linkiem do rezygnacji.</p>
      </div>

      <div class="contact-card" style="margin-top:16px;">
        <h2>Masz zlecenie na Fachora?</h2>
        <p>Najszybciej załatwisz sprawę na stronie swojego zlecenia — link „Moje zlecenie” jest w e-mailu, który od nas dostałeś. Tam wybierasz ofertę, akceptujesz pracę, zgłaszasz spór i wystawiasz opinię.</p>
        <a href="{{ route('pl.faq') }}" class="btn btn-secondary">Najczęstsze pytania</a>
      </div>
    </aside>
  </div>

  <div class="container">
    <div class="contact-help">
      <div class="contact-card">
        <h3>👤 Dla klientów</h3>
        <p>Pytania o zlecenie, oferty, termin, <a class="lnk" href="{{ route('fachora.protect') }}">Fachora Protect</a>, zwrot pieniędzy lub spór z firmą. Podaj numer zlecenia — odpowiemy szybciej.</p>
        <ul>
          <li>Nie dostałeś żadnej oferty? Napisz — poszukamy firmy ręcznie.</li>
          <li>Firma się nie zjawiła? To naruszenie Standardu Fachora, zgłoś nam to.</li>
          <li>Spór otwierasz na stronie zlecenia; firma ma {{ (int) config('fachora.dispute_hours') }} godzin na odpowiedź.</li>
        </ul>
      </div>
      <div class="contact-card">
        <h3>🔧 Dla firm</h3>
        <p>Weryfikacja, panel, wypłaty przez Stripe, faktury za prowizję, zawieszenie profilu albo usunięcie danych z rejestru. <a class="lnk" href="{{ route('login') }}">Zaloguj się</a>, żeby sprawdzić status weryfikacji.</p>
        <ul>
          <li>Chcesz dołączyć? Skorzystaj z <a class="lnk" href="{{ route('fachora.apply') }}">formularza zgłoszenia</a>.</li>
          <li>Nie chcesz figurować na Fachora? Napisz — usuniemy profil z rejestru.</li>
        </ul>
      </div>
      <div class="contact-card">
        <h3>⚖️ Treści, prawo, dane</h3>
        <p>Zgłoszenie treści niezgodnych z prawem (np. opinii lub opisu firmy), punkt kontaktowy dla organów i użytkowników (DSA), prawa z RODO i pytania do <a class="lnk" href="{{ route('pl.regulamin') }}">regulaminu</a>: ten sam adres, <a class="lnk" href="mailto:{{ brand('email') }}">{{ brand('email') }}</a>.</p>
        <ul>
          <li>W temacie wpisz „Zgłoszenie treści”, „RODO” albo „DSA”.</li>
          <li>Zobacz też <a class="lnk" href="{{ route('pl.prywatnosc') }}">politykę prywatności</a>.</li>
        </ul>
      </div>
    </div>

    <div class="contact-urgent">
      <strong>Nagły wypadek?</strong> Fachora nie jest pogotowiem technicznym. Przy ulatniającym się gazie, pożarze lub zagrożeniu życia dzwoń pod <strong>112</strong> (gazownia: <strong>992</strong>). Pilne, ale bezpieczne naprawy zgłoś jako zlecenie „na teraz” — firmy Verified dostają je także SMS-em.
    </div>
  </div>
</section>
@endsection
