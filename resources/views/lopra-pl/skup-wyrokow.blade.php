@extends('layouts.marketing')

{{-- Skup starych wyroków — Lopra Polska + sprzedamfakture.pl. Waarom een oud tytuł wykonawczy waarde houdt (art. 125 KC: 6 jaar, odsetki 3 jaar; art. 824 §1 pkt 3 KPC: na umorzenie z powodu bezskuteczności begint de 6 jaar opnieuw), wat per dossier wordt getoetst, art. 299 KSH, prijs 10–40% van nominaal. Formulier POST naar route('pl.skup-wyrokow.send'); succes via session('flash'). --}}

@section('title', 'Skup starych wyroków i nakazów zapłaty — ' . brand('name'))
@section('description', 'Masz prawomocny wyrok lub nakaz zapłaty, po którym egzekucja okazała się bezskuteczna? sprzedamfakture.pl wykupuje wierzytelności stwierdzone tytułem wykonawczym — zazwyczaj za 10–40% wartości nominalnej, po ocenie każdej sprawy. Także pojedyncze tytuły i małe portfele.')

@push('styles')
<style>
  .skup-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 28px; }
  .skup-check { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; max-width: 1000px; margin: 26px auto 0; }
  @media (max-width: 760px) { .skup-check { grid-template-columns: 1fr; } }
  .skup-check .it { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 20px 22px; font-size: 14px; color: var(--text-2); line-height: 1.65; }
  .skup-check .it b { display: block; color: var(--text); margin-bottom: 4px; font-size: 15px; }
  .cycle { counter-reset: step; display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; max-width: 1040px; margin: 30px auto 0; }
  @media (max-width: 900px) { .cycle { grid-template-columns: 1fr 1fr; } }
  @media (max-width: 520px) { .cycle { grid-template-columns: 1fr; } }
  .cycle .st { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 20px; font-size: 13.5px; color: var(--text-2); line-height: 1.6; }
  .cycle .st::before { counter-increment: step; content: counter(step); display: inline-flex; width: 28px; height: 28px; border-radius: 100px; background: var(--brand-tint); color: var(--brand); font-weight: 700; font-size: 14px; align-items: center; justify-content: center; margin-bottom: 10px; }
  .cycle .st b { display: block; color: var(--text); margin-bottom: 4px; }
  .skup-warn { background: #FEF3C7; border: 1px solid #FCD34D; border-radius: 12px; padding: 16px 20px; font-size: 14px; color: #78350F; line-height: 1.65; max-width: 1040px; margin: 20px auto 0; }
  .skup-warn b { color: #78350F; }
  .skup-form { max-width: 760px; margin: 0 auto; }
  .skup-hint { font-size: 12.5px; color: var(--text-3); margin-top: 4px; line-height: 1.5; }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Skup starych wyroków</div>
    <h1>Stary wyrok wciąż ma wartość</h1>
    <p class="lead">Prawomocny wyrok albo nakaz zapłaty, po którym komornik umorzył egzekucję, to nie makulatura. sprzedamfakture.pl wykupuje także wierzytelności stwierdzone tytułem wykonawczym — zazwyczaj za 10–40% wartości nominalnej, po ocenie każdej sprawy.</p>
    <div style="margin-top:26px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a href="#zglos" class="btn btn-primary btn-lg">Zgłoś wyrok do bezpłatnej wyceny</a>
      <a href="#dlaczego" class="btn btn-secondary btn-lg">Dlaczego to działa</a>
    </div>
  </div>
</section>

<section class="section" id="dlaczego" style="padding-top:44px;">
  <div class="container">
    <div class="prose">
      <h2>Dlaczego stary tytuł wciąż jest coś wart</h2>
      <p>Roszczenie stwierdzone prawomocnym wyrokiem sądu lub nakazem zapłaty przedawnia się z upływem <strong>sześciu lat</strong> (art. 125 Kodeksu cywilnego). Krócej żyją odsetki: jako świadczenie okresowe przedawniają się <strong>po trzech latach</strong>. Sześć lat to dużo — a to jeszcze nie koniec.</p>
      <p>Złożenie wniosku egzekucyjnego <strong>przerywa</strong> bieg przedawnienia. Gdy komornik umarza egzekucję, bo nie ma z czego jej prowadzić — umorzenie z powodu <strong>bezskuteczności</strong> (art. 824 § 1 pkt 3 Kodeksu postępowania cywilnego) — sześcioletni termin biegnie <strong>od nowa</strong>, od dnia umorzenia. Tytuł wykonawczy wraca do wierzyciela i pozostaje w mocy.</p>
      <p>To zmienia rachunek. Dłużnik, który dziś nie ma majątku, za trzy lata może mieć etat, spadek albo nieruchomość — a wtedy egzekucję można wszcząć ponownie, z tego samego tytułu. Wyrok, który na papierze wygląda na bezwartościowy, jest w istocie opcją na przyszłość. I właśnie tę opcję można sprzedać, zamiast samemu pilnować terminów przez kolejne lata.</p>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Jak to działa</div>
      <h2>Cykl bezskuteczności</h2>
    </div>
    <div class="cycle">
      <div class="st"><b>Tytuł wykonawczy</b>Prawomocny wyrok lub nakaz zapłaty z klauzulą wykonalności. Przedawnienie: 6 lat, odsetki — 3 lata.</div>
      <div class="st"><b>Wniosek egzekucyjny</b>Złożenie wniosku u komornika przerywa bieg przedawnienia na czas postępowania.</div>
      <div class="st"><b>Umorzenie — bezskuteczność</b>Komornik nie znajduje majątku i umarza egzekucję (art. 824 § 1 pkt 3 KPC). Sześć lat biegnie od nowa.</div>
      <div class="st"><b>Ponowna egzekucja</b>Nowa praca, spadek, nieruchomość — z tym samym tytułem można wrócić do komornika. Cykl może się powtórzyć.</div>
    </div>
    <div class="skup-warn">
      <b>Jedno zastrzeżenie, które decyduje o wartości:</b> jeżeli wcześniejsza egzekucja została umorzona z powodu <b>bezczynności wierzyciela</b>, wniosek traktuje się tak, jakby nigdy nie został złożony — przerwanie przedawnienia upada z mocą wsteczną. Dlatego powód poprzedniego umorzenia trzeba znać w każdej sprawie. Od <b>21 sierpnia 2019 r.</b> komornik ma ponadto obowiązek badać przedawnienie i odmawia prowadzenia egzekucji z przedawnionego tytułu.
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Ocena sprawy</div>
      <h2>Co sprawdzamy w każdej sprawie</h2>
    </div>
    <div class="skup-check">
      <div class="it"><b>Powód poprzedniego umorzenia</b>Bezskuteczność czy bezczynność wierzyciela? Od tego zależy, czy przedawnienie rzeczywiście biegnie od nowa — to pierwsza rzecz, którą weryfikujemy w aktach.</div>
      <div class="it"><b>Przedawnienie należności i odsetek</b>Sześć lat dla należności głównej, trzy lata dla odsetek. Liczymy, co z roszczenia realnie pozostało do dochodzenia.</div>
      <div class="it"><b>Forma prawna dłużnika</b>Jednoosobowa działalność, sp. z o.o. czy S.A. — od formy zależą dodatkowe drogi dochodzenia, m.in. odpowiedzialność zarządu z art. 299 KSH.</div>
      <div class="it"><b>Profil i sytuacja dłużnika</b>Wpisy w rejestrach, upadłość lub restrukturyzacja, aktywność gospodarcza — czyli realna perspektywa skutecznej egzekucji w przyszłości.</div>
    </div>
    <p style="max-width:1000px;margin:20px auto 0;color:var(--text-3);font-size:13.5px;line-height:1.65;">Na tej podstawie sprzedamfakture.pl przygotowuje ofertę — zazwyczaj w ciągu kilku dni roboczych. Nie każdy tytuł nadaje się do wykupu; każda oferta poprzedzona jest oceną konkretnej sprawy.</p>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="prose">
      <h2>Dłużnikiem jest spółka z o.o.?</h2>
      <p>Gdy egzekucja wobec spółki z ograniczoną odpowiedzialnością okazała się bezskuteczna, za jej zobowiązania mogą odpowiadać osobiście członkowie zarządu (<strong>art. 299 Kodeksu spółek handlowych</strong>) — martwe roszczenie wobec pustej spółki może wtedy stać się żywym roszczeniem wobec prywatnego majątku. Tam, gdzie majątek wyprowadzono, w grę wchodzi <strong>skarga pauliańska</strong> (art. 527 Kodeksu cywilnego), a w przypadkach oszustwa lub udaremniania egzekucji — przepisy karne (art. 286 i 300 Kodeksu karnego). Żadna z tych dróg nie działa automatycznie i każda wymaga analizy konkretnej sprawy — ale to one sprawiają, że tytuł przeciwko spółce „bez majątku” również podlega wycenie.</p>

      <h2>Ile można dostać — i dla kogo to jest</h2>
      <p>Cena wykupu to zazwyczaj <strong>10–40% wartości nominalnej</strong>, zależnie od jakości tytułu (przedawnienie, powód umorzenia, dokumentacja) i profilu dłużnika. Każda sprawa jest oceniana osobno; ofertę otrzymasz zazwyczaj w ciągu kilku dni roboczych. Zgłoszenie jest bezpłatne i do niczego nie zobowiązuje — do wykupu dochodzi tylko wtedy, gdy zaakceptujesz ofertę.</p>
      <p><strong>Małe portfele i pojedyncze tytuły są mile widziane.</strong> Jeden wierzyciel z pięcioma starymi wyrokami to dokładnie ten adres. Najwięksi nabywcy wierzytelności, tacy jak KRUK czy BEST, skupują masowe portfele bankowe — pojedynczymi tytułami B2B zazwyczaj się nie zajmują. Tu jest odwrotnie.</p>
      <p>Masz siedzibę w Holandii albo w Niemczech i tytuł wykonawczy przeciwko polskiemu dłużnikowi? Takie zgłoszenia również przyjmujemy — wystarczy formularz poniżej.</p>
    </div>
  </div>
</section>

<section class="section" id="zglos">
  <div class="container skup-form">
    <div class="section-header" style="margin-bottom:24px;">
      <div class="eyebrow" style="margin-bottom:16px;">Bezpłatna wycena</div>
      <h2>Zgłoś wyrok do wyceny</h2>
    </div>

    @if (session('flash'))
      <div class="alert-success">Dziękujemy — sprzedamfakture.pl odezwie się w ciągu kilku dni roboczych.</div>
    @endif
    @if ($errors->any())
      <div class="alert-error">Formularz zawiera błędy — sprawdź zaznaczone pola poniżej.</div>
    @endif

    <form class="skup-card" method="POST" action="{{ route('pl.skup-wyrokow.send') }}">
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

      <div class="m-row-2">
        <div class="m-field">
          <label for="phone">Telefon <span style="color:var(--text-4);font-weight:400;">(opcjonalnie)</span></label>
          <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel">
          @error('phone')<div class="m-err">{{ $message }}</div>@enderror
        </div>
        <div class="m-field">
          <label for="firm">Firma <span style="color:var(--text-4);font-weight:400;">(opcjonalnie)</span></label>
          <input id="firm" type="text" name="firm" value="{{ old('firm') }}" autocomplete="organization">
          @error('firm')<div class="m-err">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="m-row-2">
        <div class="m-field">
          <label for="sygnatura">Sygnatura akt</label>
          <input id="sygnatura" type="text" name="sygnatura" value="{{ old('sygnatura') }}" required placeholder="np. VI GNc 1234/19">
          @error('sygnatura')<div class="m-err">{{ $message }}</div>@enderror
        </div>
        <div class="m-field">
          <label for="sad">Sąd, który wydał orzeczenie</label>
          <input id="sad" type="text" name="sad" value="{{ old('sad') }}" placeholder="np. Sąd Rejonowy dla m.st. Warszawy">
          @error('sad')<div class="m-err">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="m-row-2">
        <div class="m-field">
          <label for="data_wyroku">Data wyroku / nakazu zapłaty</label>
          <input id="data_wyroku" type="date" name="data_wyroku" value="{{ old('data_wyroku') }}">
          @error('data_wyroku')<div class="m-err">{{ $message }}</div>@enderror
        </div>
        <div class="m-field">
          <label for="kwota">Należność główna (zł)</label>
          <input id="kwota" type="number" name="kwota" value="{{ old('kwota') }}" required inputmode="decimal" step="0.01" min="0" placeholder="np. 24200">
          <div class="skup-hint">Kwota z tytułu wykonawczego, bez odsetek.</div>
          @error('kwota')<div class="m-err">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="m-row-2">
        <div class="m-field">
          <label for="dluznik">Dłużnik (nazwa)</label>
          <input id="dluznik" type="text" name="dluznik" value="{{ old('dluznik') }}" required>
          @error('dluznik')<div class="m-err">{{ $message }}</div>@enderror
        </div>
        <div class="m-field">
          <label for="dluznik_nip">NIP dłużnika <span style="color:var(--text-4);font-weight:400;">(jeśli znasz)</span></label>
          <input id="dluznik_nip" type="text" name="dluznik_nip" value="{{ old('dluznik_nip') }}" inputmode="numeric">
          @error('dluznik_nip')<div class="m-err">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="m-field">
        <label for="forma">Forma prawna dłużnika</label>
        <select id="forma" name="forma">
          <option value="" {{ old('forma') === null || old('forma') === '' ? 'selected' : '' }}>— wybierz —</option>
          <option value="sp_zoo" {{ old('forma') === 'sp_zoo' ? 'selected' : '' }}>Sp. z o.o.</option>
          <option value="sa" {{ old('forma') === 'sa' ? 'selected' : '' }}>S.A.</option>
          <option value="jdg" {{ old('forma') === 'jdg' ? 'selected' : '' }}>Jednoosobowa działalność (JDG)</option>
          <option value="inna" {{ old('forma') === 'inna' ? 'selected' : '' }}>Inna / nie wiem</option>
        </select>
        @error('forma')<div class="m-err">{{ $message }}</div>@enderror
      </div>

      <div class="m-row-2">
        <div class="m-field">
          <label for="egzekucja">Czy była prowadzona egzekucja?</label>
          <select id="egzekucja" name="egzekucja">
            <option value="none" {{ old('egzekucja', 'none') === 'none' ? 'selected' : '' }}>Nigdy nie prowadzona</option>
            <option value="bezskutecznosc" {{ old('egzekucja') === 'bezskutecznosc' ? 'selected' : '' }}>Umorzona — bezskuteczność</option>
            <option value="inna" {{ old('egzekucja') === 'inna' ? 'selected' : '' }}>Umorzona — inny powód</option>
            <option value="nie_wiem" {{ old('egzekucja') === 'nie_wiem' ? 'selected' : '' }}>Nie wiem</option>
          </select>
          <div class="skup-hint">Powód umorzenia decyduje o wartości: po bezskuteczności sześcioletnie przedawnienie biegnie od nowa; po umorzeniu z powodu bezczynności wierzyciela przerwanie przedawnienia upada.</div>
          @error('egzekucja')<div class="m-err">{{ $message }}</div>@enderror
        </div>
        <div class="m-field">
          <label for="egzekucja_rok">Rok umorzenia egzekucji <span style="color:var(--text-4);font-weight:400;">(jeśli była)</span></label>
          <input id="egzekucja_rok" type="number" name="egzekucja_rok" value="{{ old('egzekucja_rok') }}" min="1990" max="2100" placeholder="np. 2021">
          @error('egzekucja_rok')<div class="m-err">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="m-field">
        <label for="uwagi">Uwagi <span style="color:var(--text-4);font-weight:400;">(opcjonalnie)</span></label>
        <textarea id="uwagi" name="uwagi" rows="4" placeholder="Np. co wiesz o sytuacji dłużnika, częściowe wpłaty, kilka tytułów wobec tego samego dłużnika…">{{ old('uwagi') }}</textarea>
        @error('uwagi')<div class="m-err">{{ $message }}</div>@enderror
      </div>

      @if(config('services.turnstile.sitekey'))
        <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.sitekey') }}" data-language="pl" style="margin-bottom:14px;"></div>
        @error('cf-turnstile-response')<div class="m-err">{{ $message }}</div>@enderror
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
      @endif

      <button type="submit" class="btn btn-primary btn-lg">Wyślij do bezpłatnej wyceny</button>
      <div style="font-size:12.5px;color:var(--text-3);margin-top:12px;line-height:1.55;">Zgłoszenie jest bezpłatne i niezobowiązujące. Wysyłając formularz, zgadzasz się na przekazanie zgłoszenia — wraz z danymi dłużnika — do sprzedamfakture.pl w celu przygotowania wyceny; zob. <a href="{{ route('pl.prywatnosc') }}" style="color:var(--brand);">polityka prywatności</a>. Masz więcej tytułów? Wymień je w polu „Uwagi” albo wyślij osobne zgłoszenia.</div>
    </form>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Nie czekaj, aż przedawnienie zrobi swoje</h2>
    <p>Faktura jeszcze bez wyroku? Policz odsetki i rekompensatę i wyślij wezwanie do zapłaty — a bieżące fakturowanie i pilnowanie płatności zostaw {{ brand('name') }}.</p>
    <div class="hero-ctas">
      <a href="{{ route('pl.kalkulator') }}" class="btn btn-white btn-lg">Policz odsetki i rekompensatę →</a>
      <a href="{{ route('register') }}" class="btn btn-lg" style="background:rgba(255,255,255,0.15);color:white;border-color:rgba(255,255,255,0.3);">Wypróbuj {{ brand('name') }} 14 dni za darmo</a>
    </div>
  </div>
</section>
@endsection
