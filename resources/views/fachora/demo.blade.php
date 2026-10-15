@extends('layouts.marketing')

{{-- Demo panelu firmy: jedno kliknięcie i jesteś w panelu fikcyjnej firmy Hydro-Serwis Nowak ze zleceniami w każdej fazie (App\Services\Fachora\DemoBuilder). Nic nie jest wysyłane; środowisko znika po 24 godzinach. --}}

@section('title', 'Demo panelu firmy — zobacz Fachorę od środka')
@section('description', 'Wejdź jednym kliknięciem do panelu fikcyjnej firmy Fachora Verified: nowe zlecenia do wyceny, wycena czekająca na klienta, wizyta na jutro, praca w toku, odbiór i opinie. Bez konta, bez karty, nic nie jest wysyłane.')

@push('styles')
@include('fachora.partials.styles')
<style>
  .demo-wrap { max-width: 960px; margin: 0 auto; }
  .demo-start { text-align: center; padding: 6px 0 34px; }
  .demo-note { color: var(--text-3); font-size: 13.5px; margin-top: 14px; }
  .demo-feats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 8px; }
  @media (max-width: 860px) { .demo-feats { grid-template-columns: 1fr; } }
  .demo-feat { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 20px; }
  .demo-feat h2 { font-size: 15px; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
  .demo-feat p { color: var(--text-2); font-size: 13.5px; margin: 0; line-height: 1.6; }
  .demo-feat svg { color: var(--brand); flex: none; }
  .demo-safe { background: var(--surface-2); border: 1px solid var(--border); border-radius: 12px; padding: 18px 22px; margin-top: 26px; font-size: 13.5px; color: var(--text-2); line-height: 1.6; }
  .demo-safe b { color: var(--text); }
  .demo-safe ul { margin: 8px 0 0; padding-left: 20px; }
  .demo-after { text-align: center; margin-top: 34px; }
  .demo-after p { color: var(--text-2); margin-bottom: 16px; }
  .demo-after .btn { margin: 4px; }
</style>
@endpush

@section('content')

<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Demo</div>
    <h1>Panel firmy Fachora. Prawdziwy, z przykładowymi zleceniami.</h1>
    <p class="lead">Jedno kliknięcie i jesteś w panelu fikcyjnej firmy <strong>Hydro-Serwis Nowak</strong> z Warszawy: dwa nowe zlecenia czekają na Twoją cenę, jedna wycena na decyzję klienta, wizyta jutro o 10:00, jedna praca w toku i jedna do odbioru. Klikaj, wyceniaj, oznaczaj jako wykonane. Nic nie trafia do prawdziwych klientów.</p>
  </div>
</section>

<section class="section" style="padding-top:24px;">
  <div class="container demo-wrap">

    <div class="demo-start">
      @if (session('error'))
        <div style="background:var(--brand-tint);border:1px solid var(--brand-border);color:var(--brand-darker);border-radius:10px;padding:12px 16px;margin-bottom:18px;font-size:14px;">
          {{ session('error') }}
        </div>
      @endif

      <form method="POST" action="{{ route('demo.start') }}">
        @csrf
        <button type="submit" class="btn btn-primary btn-lg">Uruchom demo →</button>
      </form>
      <div class="demo-note">Bez konta · bez karty · bez e-maili do kogokolwiek</div>
    </div>

    <div class="demo-feats">
      <div class="demo-feat">
        <h2>@include('fachora.partials.icon', ['name' => 'clock', 'size' => 18]) Nowe zlecenia</h2>
        <p>Dwa zlecenia czekają na Twoją cenę: kapiąca bateria na Mokotowie i kocioł z błędem E03, opisany po angielsku i przetłumaczony dla Ciebie. Na odpowiedź masz {{ config('fachora.offer_hours') }} godzin.</p>
      </div>
      <div class="demo-feat">
        <h2>@include('fachora.partials.icon', ['name' => 'card', 'size' => 18]) Wycena i wybór</h2>
        <p>Wycena montażu klimatyzacji (3 200 zł) czeka na decyzję klienta. Zobacz, jak wygląda Twoja oferta i co klient widzi, zanim wybierze.</p>
      </div>
      <div class="demo-feat">
        <h2>@include('fachora.partials.icon', ['name' => 'pin', 'size' => 18]) Wizyta i praca w toku</h2>
        <p>Wymiana grzejnika zaplanowana na jutro, opłacona przez Fachora Protect. Awaria pionu w toku, z propozycją pracy dodatkowej do zatwierdzenia przez klienta.</p>
      </div>
      <div class="demo-feat">
        <h2>@include('fachora.partials.icon', ['name' => 'check', 'size' => 18]) Odbiór i wypłata</h2>
        <p>Przegląd kotła oznaczony jako wykonany: klient ma {{ config('fachora.auto_accept_days') }} dni na odbiór, potem Protect wypłaca automatycznie. Prowizja widoczna od razu.</p>
      </div>
      <div class="demo-feat">
        <h2>@include('fachora.partials.icon', ['name' => 'star', 'size' => 18]) Opinie</h2>
        <p>Cztery opinie, średnia 4,75, w tym jedna po angielsku. Odpowiedz na opinię tak, jak zrobiłaby to Twoja firma.</p>
      </div>
      <div class="demo-feat">
        <h2>@include('fachora.partials.icon', ['name' => 'shield', 'size' => 18]) Weryfikacja</h2>
        <p>Profil Fachora Verified z kompletem sprawdzeń: rejestr, VAT, e-mail, telefon, Standard, konto do wypłat i polisa OC.</p>
      </div>
    </div>

    <div class="demo-safe">
      <b>Bezpieczne testowanie.</b> Demo to odseparowane środowisko tylko dla Ciebie:
      <ul>
        <li><b>Żaden e-mail ani SMS</b> nie trafia do nikogo, nawet gdy wyślesz wycenę albo oznaczysz pracę jako wykonaną. Klienci w demo są zmyśleni.</li>
        <li>Nic nie wpisujesz i <b>nic nie płacisz</b>; Fachora Protect działa tu na niby.</li>
        <li>Po 24 godzinach środowisko jest usuwane; możesz je też opuścić od razu przyciskiem w panelu.</li>
      </ul>
    </div>

    <div class="demo-after">
      <p>Twoja firma? Zgłoś ją albo przejmij profil kodem z listu.</p>
      <a href="{{ route('fachora.apply') }}" class="btn btn-secondary">Zgłoś firmę</a>
      <a href="{{ route('fachora.claim') }}" class="btn btn-secondary">Mam kod z listu</a>
    </div>

  </div>
</section>

@endsection
