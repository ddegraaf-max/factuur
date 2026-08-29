@extends('layouts.marketing')

@section('title', 'Veelgestelde vragen — ' . brand('name'))
@section('description', 'Antwoorden op de meest gestelde vragen over ' . brand('name') . ': prijzen, facturen, BTW, incasso, beveiliging en meer.')

@php
  // Eén bron voor de zichtbare FAQ én het FAQPage-schema (rich results):
  // vraag + antwoord (antwoord mag beperkte HTML bevatten zoals <b>).
  $faqGroups = [
    'Algemeen' => [
      ['Wat is ' . brand('name') . '?', brand('name') . " is eenvoudige facturatiesoftware voor Nederlandse ZZP'ers en het MKB. Je maakt facturen, beheert klanten, houdt je BTW bij en verstuurt herinneringen — alles op één plek."],
      ['Voor wie is het bedoeld?', "Voor ondernemers die snel en professioneel willen factureren zonder ingewikkelde boekhoudsoftware: freelancers, ZZP'ers en kleine bedrijven."],
      ['Heb ik boekhoudkennis nodig?', 'Nee. ' . brand('name') . ' is gemaakt om zonder voorkennis te gebruiken. BTW wordt automatisch berekend en alles is in begrijpelijk Nederlands.'],
    ],
    'Prijs & abonnement' => [
      ['Wat kost ' . brand('name') . '?', 'Er zijn twee abonnementen: <b>Basis</b> voor € 12,10 per maand (incl. 21% btw) met het volledige facturatiepakket, en <b>Slim</b> voor € 21,18 per maand (incl. btw) met daarbovenop de AI-functies — bonnen en inkoopfacturen automatisch herkennen, en offertes uit tekst. Geen verborgen kosten, maandelijks opzegbaar.'],
      ['Kan ik op elk moment opzeggen?', 'Ja, ' . brand('name') . ' is maandelijks opzegbaar. Geen contracten of jaarverplichtingen. Je facturen blijven altijd downloadbaar.'],
      ['Is er een gratis proefperiode?', 'Ja, je probeert ' . brand('name') . ' 14 dagen gratis. Geen creditcard nodig om te starten.'],
    ],
    'Facturen & BTW' => [
      ['Wordt BTW automatisch berekend?', 'Ja, per factuurregel kies je 21%, 9% of 0%. Per kwartaal staat je aangifte klaar: rubriek 1a, 1b en 1e, plus de voorbelasting (5b) uit je ingeboekte inkoopfacturen en het saldo dat je moet betalen — inclusief deadline-waarschuwing en PDF-download.'],
      ['Kan ik ook mijn inkoopfacturen bijhouden?', 'Ja. Boek binnengekomen facturen van leveranciers in — handmatig of met een foto van de bon (op je telefoon opent direct de camera). Je ziet wat er bij leveranciers openstaat en de BTW telt automatisch mee als voorbelasting in je aangifte.'],
      ['Wat als ik onder de KOR-regeling val?', brand('name') . ' ondersteunt de Kleine Ondernemersregeling volledig — je verstuurt facturen zonder BTW, met automatische vermelding van de KOR-regeling.'],
      ['Voldoen de facturen aan de eisen van de Belastingdienst?', "Ja, inclusief doorlopende nummering per jaar en alle verplichte gegevens. Ook creditnota's voldoen aan de Nederlandse boekhoudregels."],
    ],
    'Samenwerken & klantenportaal' => [
      ["Kunnen collega's ook in " . brand('name') . " werken?", "Ja, en extra gebruikers kosten niets. Nodig collega's uit via Instellingen → Team en kies per persoon een rol: beheerder (alles), medewerker (het dagelijkse werk, zonder instellingen en rapporten) of boekhouder (alles inzien, niets wijzigen)."],
      ['Kan mijn boekhouder meekijken?', 'Ja, gratis. De boekhouder-rol mag je hele administratie inzien en juist wél de rapporten en exports gebruiken (klantomzet, BTW-overzicht, CSV van verkoop en inkoop), maar kan niets aanmaken of wijzigen.'],
      ['Ziet mijn klant de factuur ook online?', 'Ja. In elke factuurmail staat een knop "Bekijk factuur online" met een beveiligde link. Voor de zekerheid bevestigt je klant eerst zijn e-mailadres met een eenmalige code. Jij ziet daarna in het inzagelog precies óf en wanneer de factuur is bekeken en gedownload.'],
    ],
    'Betalingen, incasso & veiligheid' => [
      ['Kan ik automatische herinneringen versturen?', 'Ja, je stelt in wanneer herinneringen en aanmaningen automatisch worden verstuurd. Bij uitblijvende betaling draag je in één klik over aan incasso.'],
      ['Hoe veilig is mijn data?', 'Je data staat versleuteld op servers binnen de EU, dagelijks geback-upt. We zijn AVG-compliant en bieden tweestapsverificatie (2FA).'],
    ],
  ];

  $faqLd = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => collect($faqGroups)->flatten(1)->map(fn ($item) => [
      '@type' => 'Question',
      'name' => $item[0],
      'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item[1]],
    ])->values()->all(),
  ];
@endphp

@section('content')
<script type="application/ld+json">{!! json_encode($faqLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Veelgestelde vragen</div>
    <h1>Vragen &amp; antwoorden</h1>
    <p class="lead">De meest gestelde vragen over {{ brand('name') }} op een rij. Staat je vraag er niet bij? <a href="{{ route('contact') }}" style="color:var(--brand);font-weight:600;">Neem contact op</a>.</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="faq-list">
      @foreach($faqGroups as $groupTitle => $items)
        <h2 style="font-size:14px;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-4);margin:{{ $loop->first ? '8px' : '32px' }} 0 14px;">{{ $groupTitle }}</h2>
        @foreach($items as [$question, $answer])
          <details class="faq-item">
            <summary>{{ $question }} <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
            <div class="faq-content">{!! $answer !!}</div>
          </details>
        @endforeach
      @endforeach
    </div>

    <div style="text-align:center;margin-top:40px;">
      <p style="color:var(--text-2);margin-bottom:16px;">Geen antwoord gevonden?</p>
      <a href="{{ route('contact') }}" class="btn btn-primary">Neem contact op</a>
    </div>
  </div>
</section>
@endsection
