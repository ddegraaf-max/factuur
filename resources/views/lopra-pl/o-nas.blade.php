@extends('layouts.marketing')

{{-- O nas — Lopra Polska: Lopra jako usługa Creditline B.V. (Bussum, ponad 25 lat w zarządzaniu należnościami) z Creditline Polska jako partnerem windykacyjnym. Bez zmyślonych liczb i osób. --}}

@section('title', 'O nas — ' . brand('name') . ', usługa Creditline')
@section('description', 'Kim jesteśmy: ' . brand('name') . ' to usługa Creditline B.V. z Bussum (Holandia), firmy z ponad 25-letnim doświadczeniem w zarządzaniu należnościami i windykacji, rozwijana w Polsce we współpracy z Creditline Polska. Jedno narzędzie od pierwszej faktury do windykacji.')

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">O nas</div>
    <h1>Od pierwszej faktury do windykacji</h1>
    <p class="lead">{{ brand('name') }} powstała z prostego spostrzeżenia: największym problemem małej firmy nie jest wystawienie faktury, tylko to, że nie zawsze zostaje zapłacona. Zbudowaliśmy więc narzędzie, które pilnuje obu rzeczy.</p>
  </div>
</section>

<section class="section" style="padding-top:48px;">
  <div class="container">
    <div class="prose">
      <h2>Usługa Creditline B.V.</h2>
      <p>{{ brand('name') }} to usługa Creditline B.V. z Bussum w Holandii — firmy, która od ponad 25 lat zajmuje się zarządzaniem należnościami i windykacją. Znamy z codziennej praktyki, a nie z opowieści, co dzieje się z firmą, gdy klienci płacą po terminie albo wcale: napięta płynność, godziny spędzone na ponagleniach i niepewność, czy w ogóle warto walczyć o swoje pieniądze.</p>
      <p>To doświadczenie jest wbudowane w produkt. Przypomnienia, wezwanie do zapłaty z odsetkami ustawowymi i rekompensatą, przekazanie sprawy do windykacji — nie jako dodatek za dopłatą, ale jako stały element każdego abonamentu.</p>

      <h2>Creditline Polska</h2>
      <p>W Polsce {{ brand('name') }} działa we współpracy z <a href="https://creditline.pl" target="_blank" rel="noopener">Creditline Polska</a> — polską częścią Creditline zajmującą się windykacją należności. Gdy przypomnienia i wezwanie nie skutkują, jednym kliknięciem przekazujesz sprawę: kompletne dossier z faktury, historia kontaktu i wezwanie trafiają do zespołu windykacyjnego, który prowadzi windykację polubowną, wpis do rejestrów dłużników i — w razie potrzeby — drogę sądową. Potrzebujesz gotówki od razu? Creditline Polska przygotuje ofertę wykupu wierzytelności w jeden dzień roboczy.</p>
      <p>Każde przekazanie sprawy jest wyceniane przed zleceniem, bez opłat wstępnych. Decyzja zawsze należy do Ciebie.</p>

      <h2>Dlaczego jedno narzędzie?</h2>
      <p>Mała firma zwykle potrzebuje czterech rzeczy naraz: programu do faktur, logo i kolorów, prostej strony www oraz kogoś, kto pomoże, gdy klient nie płaci. Zazwyczaj oznacza to cztery abonamenty, cztery loginy i dane przepisywane z jednego miejsca do drugiego.</p>
      <p>W {{ brand('name') }} wszystko jest w jednym miejscu i ze sobą współpracuje: faktura gotowa do KSeF ma Twoje logo z identyfikacji wizualnej, wizytówka i strona www wyglądają tak samo, zapytanie ze strony zamienia się w ofertę, a niezapłacona faktura — bez przepisywania danych — w wezwanie do zapłaty i sprawę windykacyjną. Jeden abonament, jedna cena, zero księgowego żargonu.</p>

      <h2>Co obiecujemy</h2>
      <ul>
        <li><strong>Prostota.</strong> Zostawiamy tylko to, co naprawdę pomaga prowadzić firmę. Bez stu przycisków.</li>
        <li><strong>Uczciwa cena.</strong> Jeden miesięczny abonament, bez limitów faktur i klientów, bez ukrytych opłat. Rezygnujesz, kiedy chcesz.</li>
        <li><strong>Twoje dane są Twoje.</strong> Serwery w Unii Europejskiej, eksport całej firmy w każdej chwili, zgodność z RODO.</li>
        <li><strong>Wsparcie po polsku.</strong> Odpowiadamy w ciągu jednego dnia roboczego — także w okresie próbnym.</li>
      </ul>
      <p>Masz pytania? <a href="{{ route('pl.kontakt') }}">Napisz do nas</a> albo zajrzyj do <a href="{{ route('pl.faq') }}">najczęstszych pytań</a>.</p>
    </div>
  </div>
</section>

<section class="section section-alt" style="padding-top:64px;padding-bottom:64px;">
  <div class="container">
    <div class="section-header" style="margin-bottom:40px;"><h2>Za czym stoimy</h2></div>
    <div class="card-grid cols-2" style="max-width:900px;margin:0 auto;">
      <div class="info-card"><h3>Faktura to dopiero początek</h3><p>Liczy się to, żeby została zapłacona. Dlatego pilnowanie terminów i windykacja są częścią produktu, a nie osobną usługą.</p></div>
      <div class="info-card"><h3>Zbudowane dla małych firm</h3><p>Dla jednoosobowych działalności, spółek z o.o. i zespołów kilkuosobowych — nie dla działów księgowości.</p></div>
      <div class="info-card"><h3>Doświadczenie z praktyki</h3><p>Ponad 25 lat Creditline w zarządzaniu należnościami przełożone na proste narzędzie.</p></div>
      <div class="info-card"><h3>Prywatność przede wszystkim</h3><p>Dane w Unii Europejskiej, szyfrowane połączenie, logowanie dwuskładnikowe i pełny eksport w każdej chwili.</p></div>
    </div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Gotowy, żeby zacząć?</h2>
    <p>Wypróbuj {{ brand('name') }} przez 14 dni za darmo — faktury, marka, strona www i windykacja w jednym miejscu.</p>
    <a href="{{ route('register') }}" class="btn btn-white btn-lg">Wypróbuj 14 dni za darmo</a>
  </div>
</section>
@endsection
