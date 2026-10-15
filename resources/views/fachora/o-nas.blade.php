@extends('layouts.marketing')

{{-- O nas — Fachora: skąd się wzięła, co obiecuje, kto za nią stoi (Creditline B.V., twórca EasyInvoice i Lopra), zasady. Bez zmyślonych liczb i osób. --}}

@section('title', 'O nas — Fachora, usługa Creditline B.V.')
@section('description', 'Dlaczego powstała Fachora: dla obcokrajowców w Polsce i dla Polaków, którzy mają dość „przyjdę jutro”. Kto za nią stoi (Creditline B.V., twórca oprogramowania do faktur EasyInvoice i Lopra) i jakich zasad się trzymamy: nie każda firma trafia na Fachora, zero zimnych e-maili, cena z góry, pieniądze bezpieczne.')

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
    <div class="eyebrow">O nas</div>
    <h1>Fachowiec, któremu można zaufać, zanim otworzysz drzwi</h1>
    <p class="lead">Fachora powstała z jednego pytania: skąd mam wiedzieć, że ten hydraulik przyjdzie, zrobi to dobrze i nie policzy trzy razy więcej, niż mówił? Zbudowaliśmy platformę, która odpowiada na nie zanim ktokolwiek przyjdzie.</p>
  </div>
</section>

<section class="section" style="padding-top:48px;">
  <div class="container">
    <div class="prose">
      <h2>Dlaczego Fachora</h2>
      <p>Zaczęło się od obcokrajowców w Polsce. Kto nie mówi po polsku, nie ma jak sprawdzić firmy, dogadać ceny ani zareagować, gdy coś idzie nie tak. Polecenia od znajomych szybko się kończą, a wpisanie „hydraulik” w wyszukiwarkę daje listę telefonów, z których połowa nie odbiera. Pierwsza wersja Fachory jest więc dla nich: obsługa po angielsku, niemiecku, niderlandzku i ukraińsku, firmy, które mówią w Twoim języku, i cała komunikacja przez platformę.</p>
      <p>Szybko okazało się, że Polacy mają ten sam problem, tylko inaczej go nazywają: <em>„przyjdę jutro”</em>. Fachowiec, który nie przychodzi. Wycena „na miejscu”, która rośnie w trakcie pracy. Brak faktury, brak gwarancji, brak kogokolwiek, do kogo można się zwrócić. Fachora jest dla każdego, kto ma dość zgadywania.</p>

      <h2>Co obiecujemy</h2>
      <p>Nie sprzedajemy hydraulika. Sprzedajemy pewność, że zlecenie przebiegnie tak, jak ustalono. Dlatego każde zlecenie na Fachora ma trzy stałe elementy: <strong>cenę ustaloną z góry</strong> (dodatkowe prace tylko po Twojej zgodzie), <strong>pewny termin</strong> (firma potwierdza, a odwołuje tylko z powodu) i <strong>gwarancję</strong> (standardowo {{ (int) config('fachora.default_warranty_months') }} miesięcy, zawsze z fakturą). Z <a href="{{ route('fachora.protect') }}">Fachora Protect</a> dochodzi czwarty: płacisz dopiero, gdy praca jest skończona i zaakceptowana — do tego czasu pieniądze czekają u operatora płatności, nie u firmy i nie u nas.</p>
    </div>

    <div class="about-promise" style="margin-top:40px;">
      <span class="tag">Jasna cena. Pewny termin. Gwarancja.</span>
      <h2>Nie każda firma trafia na Fachora</h2>
      <p>Firma jest na Fachora dopiero jako <strong>Fachora Verified</strong>: po sprawdzeniu w CEIDG lub KRS i na białej liście VAT, po potwierdzeniu e-maila i telefonu, po weryfikacji tożsamości właściciela i konta bankowego przez Stripe, i po zaakceptowaniu <a href="{{ route('fachora.standard') }}" style="color:#fff;text-decoration:underline;">Standardu Fachora</a> — dziesięciu zasad, których nieprzestrzeganie kończy się zawieszeniem.</p>
      <p>Firmy z rejestrów publicznych, które nie przeszły tej drogi, widać u nas tylko z nazwy i miasta. Nie kierujemy do nich zleceń i nie pokazujemy ich kontaktu.</p>
    </div>
  </div>
</section>

<section class="section section-alt" style="padding-top:64px;padding-bottom:64px;">
  <div class="container">
    <div class="section-header" style="margin-bottom:32px;">
      <h2>Kto za tym stoi</h2>
      <p>Fachora jest usługą Creditline B.V. z Bussum w Holandii — firmy, która od ponad 25 lat zajmuje się zarządzaniem należnościami i tworzy oprogramowanie dla małych firm.</p>
    </div>
    <div class="about-who">
      <div><b>Creditline B.V.</b>Operator platformy. Torenlaan 5B, 1402 AT Bussum, Holandia · KvK 59683198 · VAT NL853603108B01. Doświadczenie z windykacji nauczyło nas jednego: najdroższe są ustalenia, których nikt nie spisał.</div>
      <div><b>EasyInvoice i Lopra</b>Nasze programy do faktur: EasyInvoice w Holandii i <a href="https://lopra.pl" target="_blank" rel="noopener" style="color:var(--brand);font-weight:500;">Lopra</a> w Polsce. Firmy Fachora Verified wystawiają faktury i rozliczają zlecenia na tym samym silniku — gotowym na KSeF.</div>
      <div><b>Fachora</b>Trzeci krok: ta sama technologia, która pilnuje, żeby faktura została zapłacona, pilnuje teraz, żeby usługa została wykonana tak, jak ustalono. Marka polska, obsługa w pięciu językach.</div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header" style="margin-bottom:36px;"><h2>Zasady, których się trzymamy</h2></div>
    <div class="about-principles">
      <div class="about-principle"><span class="n">1</span><h3>Nie każda firma trafia na Fachora</h3><p>Rejestry, tożsamość, konto bankowe, Standard Fachora. Firma, której ocena spada poniżej {{ number_format((float) config('fachora.min_rating'), 1, ',', '') }} albo która zbiera uzasadnione skargi, znika z listy. Wolimy mieć mniej firm niż firmy, za które nie możemy ręczyć.</p></div>
      <div class="about-principle"><span class="n">2</span><h3>Zero zimnych e-maili</h3><p>Nie wysyłamy firmom e-maili ani SMS-ów bez ich zgody — także tym z rejestrów publicznych. Firma słyszy od nas dopiero wtedy, gdy sama przejmie profil. Jedyny wyjątek to list pocztą z kodem do przejęcia profilu.</p></div>
      <div class="about-principle"><span class="n">3</span><h3>Cena z góry</h3><p>Ofertę z ceną brutto dostajesz, zanim ktokolwiek przyjdzie. Dodatkowe prace tylko jako osobna pozycja, którą zatwierdzasz na swojej stronie zlecenia. Bez niespodzianek przy wyjściu.</p></div>
      <div class="about-principle"><span class="n">4</span><h3>Pieniądze bezpieczne</h3><p>Z Fachora Protect rezerwujesz kwotę przez Stripe. My nigdy nie trzymamy Twoich pieniędzy. Firma dostaje wypłatę po Twojej akceptacji, a w razie sporu decydujemy my — na podstawie zdjęć, opisu i odpowiedzi obu stron.</p></div>
      <div class="about-principle"><span class="n">5</span><h3>Prawdziwe opinie</h3><p>Opinię może wystawić tylko klient po zakończonym zleceniu. Firma może odpowiedzieć, ale nie może usunąć. Nie publikujemy statystyk, których nie mamy.</p></div>
      <div class="about-principle"><span class="n">6</span><h3>Dla klientów bezpłatnie</h3><p>Fachora zarabia na prowizji od firmy, i to tylko od wykonanych zleceń. Bez abonamentu, bez opłat za rejestrację, bez płacenia za leady. Firma płaci, gdy zarobiła.</p></div>
    </div>
  </div>
</section>

<section class="section section-alt" style="padding:56px 0;">
  <div class="container">
    <div class="prose" style="text-align:center;">
      <h2 style="margin-top:0;">Kontakt</h2>
      <p>Pytania, uwagi, pomysły — także krytyczne — kieruj na <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a> albo przez <a href="{{ route('pl.kontakt') }}">formularz kontaktowy</a>. Odpowiadamy w ciągu jednego dnia roboczego, po polsku i po angielsku. Prowadzisz firmę i chcesz dołączyć? Zobacz <a href="{{ route('fachora.for-companies') }}">stronę dla firm</a>.</p>
    </div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Opisz, co trzeba zrobić</h2>
    <p>Oferty od firm Fachora Verified w Twojej okolicy — bezpłatnie, bez konta, bez telefonu.</p>
    <a href="{{ route('fachora.intake') }}" class="btn btn-white btn-lg">Znajdź fachowca →</a>
  </div>
</section>
@endsection
