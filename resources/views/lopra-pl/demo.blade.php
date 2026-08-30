@extends('layouts.marketing')

@section('title', 'Demo — zobacz ' . brand('name') . ' od środka')
@section('description', 'Kliknij i przetestuj prawdziwe ' . brand('name') . ' z przykładowymi danymi. Bez konta, bez karty — wchodzisz jednym kliknięciem.')

@push('styles')
<style>
  .demo-wrap { max-width: 940px; margin: 0 auto; }
  .demo-start { text-align: center; padding: 6px 0 34px; }
  .demo-start .btn-xl { height: 54px; padding: 0 34px; font-size: 16px; font-weight: 600; }
  .demo-note { color: var(--text-3); font-size: 13.5px; margin-top: 14px; }
  .demo-feats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 8px; }
  @media (max-width: 860px) { .demo-feats { grid-template-columns: 1fr; } }
  .demo-feat { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 20px; }
  .demo-feat h2 { font-size: 15px; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
  .demo-feat p { color: var(--text-2); font-size: 13.5px; margin: 0; line-height: 1.6; }
  .demo-feat svg { width: 18px; height: 18px; color: var(--brand); flex: none; }
  .demo-tag { display: inline-block; font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #fff; background: var(--accent); border-radius: 100px; padding: 2px 7px; margin-left: 6px; }
  .demo-safe { background: var(--surface-2); border: 1px solid var(--border); border-radius: 12px; padding: 18px 22px; margin-top: 26px; font-size: 13.5px; color: var(--text-2); line-height: 1.7; }
  .demo-safe b { color: var(--text); }
  .demo-safe ul { margin: 8px 0 0; padding-left: 20px; }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Demo</div>
    <h1>Najpierw rozejrzyj się, potem zdecyduj</h1>
    <p class="lead">
      To nie film ani zrzut ekranu: wchodzisz do <strong>prawdziwego {{ brand('name') }}</strong> wypełnionego
      przykładowymi danymi fikcyjnej firmy. Wystawiaj faktury, księguj płatności, zobacz sprawę windykacyjną —
      dokładnie tak, jak będziesz z niego korzystać.
    </p>
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
        <button type="submit" class="btn btn-primary btn-xl">Uruchom demo →</button>
      </form>
      <div class="demo-note">Bez konta · bez karty · od razu w środku</div>
    </div>

    <div class="demo-feats">
      <div class="demo-feat">
        <h2>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/></svg>
          Faktury
        </h2>
        <p>Kilkanaście faktur we wszystkich statusach: szkic, wysłana, częściowo opłacona, przeterminowana, opłacona — plus korekta. Wystaw własną i pobierz PDF oraz plik do KSeF.</p>
      </div>
      <div class="demo-feat">
        <h2>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9V5a3 3 0 0 0-6 0v4"/><rect x="2" y="9" width="20" height="12" rx="2"/></svg>
          Windykacja<span class="demo-tag">sprzedamfakture.pl</span>
        </h2>
        <p>Przeterminowana faktura z wyliczonymi odsetkami i rekompensatą: pobierz wezwanie do zapłaty, przekaż sprawę do sprzedamfakture.pl albo zgłoś fakturę do wykupu.</p>
      </div>
      <div class="demo-feat">
        <h2>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/></svg>
          Marka, wizytówka i strona www
        </h2>
        <p>Zmień kolor lub szablon faktury i zobacz efekt od razu. Wizytówka cyfrowa i strona www są już włączone — obejrzyj je jak Twój klient.</p>
      </div>
      <div class="demo-feat">
        <h2>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          Rozliczenie VAT
        </h2>
        <p>VAT należny i naliczony per stawka za bieżący miesiąc — w układzie JPK_V7, z terminem do 25. dnia następnego miesiąca.</p>
      </div>
      <div class="demo-feat">
        <h2>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="3" y1="20" x2="21" y2="20"/></svg>
          Pulpit i raporty
        </h2>
        <p>Kwoty do zapłaty, przychód miesięczny i najlepsi klienci — z pół roku przykładowej historii.</p>
      </div>
      <div class="demo-feat">
        <h2>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Import i eksport<span class="demo-tag">Nowość</span>
        </h2>
        <p>Wgraj eksport CSV z Fakturowni, iFirmy, wFirmy lub inFaktu i zobacz, jak Lopra rozpoznaje kolumny. Pobierz eksport dla księgowej.</p>
      </div>
    </div>

    <div class="demo-safe">
      <b>Bezpieczne testowanie.</b> Demo to odseparowane środowisko tylko dla Ciebie:
      <ul>
        <li><b>Żaden e-mail</b> nie trafia do prawdziwych odbiorców — nawet gdy „wyślesz” fakturę.</li>
        <li>Niczego nie musisz wpisywać i <b>nic nie płacisz</b>.</li>
        <li>Po 24 godzinach środowisko jest automatycznie usuwane; możesz też opuścić je od razu.</li>
      </ul>
    </div>

    <div style="text-align:center;margin-top:34px;">
      <p style="color:var(--text-2);margin-bottom:16px;">Przekonany? Twoje własne konto jest gotowe w dwie minuty.</p>
      <a href="{{ route('register') }}" class="btn btn-secondary">Wypróbuj 14 dni za darmo</a>
    </div>

  </div>
</section>
@endsection
