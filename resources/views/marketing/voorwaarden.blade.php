@extends('layouts.marketing')

@section('title', 'Algemene voorwaarden — EasyInvoice')
@section('description', 'De algemene voorwaarden van EasyInvoice.')

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Juridisch</div>
    <h1>Algemene voorwaarden</h1>
    <p class="lead">Laatst bijgewerkt: 1 januari 2026</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="prose">
      <p>Deze algemene voorwaarden zijn van toepassing op het gebruik van EasyInvoice, aangeboden door EasyInvoice B.V., gevestigd te Amsterdam (KvK 87654321). Door een account aan te maken ga je akkoord met deze voorwaarden.</p>

      <h2>1. De dienst</h2>
      <p>EasyInvoice is online facturatiesoftware waarmee je facturen kunt opstellen, klanten kunt beheren, BTW kunt bijhouden en betalingen kunt opvolgen. We spannen ons in om de dienst beschikbaar en betrouwbaar te houden, maar kunnen geen ononderbroken beschikbaarheid garanderen.</p>

      <h2>2. Abonnement en betaling</h2>
      <p>Het abonnement kost €2,50 per maand (exclusief 21% BTW). De proefperiode bedraagt 14 dagen. Het abonnement is maandelijks opzegbaar via je account-instellingen. Betaling vindt vooraf plaats per maand.</p>

      <h2>3. Gebruik</h2>
      <p>Je bent verantwoordelijk voor de juistheid van de gegevens die je invoert en voor het naleven van de fiscale en wettelijke verplichtingen die op jouw onderneming van toepassing zijn. Misbruik van de dienst is niet toegestaan.</p>

      <h2>4. Jouw gegevens</h2>
      <p>Je behoudt te allen tijde de eigendomsrechten op je eigen gegevens. Je kunt je data op elk moment exporteren. Zie ons <a href="{{ route('privacy') }}">privacybeleid</a> voor hoe we met je gegevens omgaan.</p>

      <h2>5. Aansprakelijkheid</h2>
      <p>EasyInvoice is niet aansprakelijk voor indirecte schade. Onze aansprakelijkheid is in alle gevallen beperkt tot het bedrag dat je in de twaalf maanden voorafgaand aan het schadeveroorzakende feit aan ons hebt betaald.</p>

      <h2>6. Opzegging</h2>
      <p>Je kunt op elk moment opzeggen. Na opzegging blijven je facturen gedurende een redelijke termijn downloadbaar.</p>

      <h2>7. Wijzigingen</h2>
      <p>We kunnen deze voorwaarden van tijd tot tijd aanpassen. Bij belangrijke wijzigingen informeren we je per e-mail.</p>

      <h2>8. Contact</h2>
      <p>Vragen over deze voorwaarden? Mail ons via <a href="mailto:hallo@easyinvoice.nl">hallo@easyinvoice.nl</a> of bekijk de <a href="{{ route('contact') }}">contactpagina</a>.</p>

      <p style="font-size:13px;color:var(--text-3);margin-top:32px;">Dit is een voorbeeldtekst en vormt geen juridisch advies. Laat de definitieve voorwaarden controleren door een jurist.</p>
    </div>
  </div>
</section>
@endsection
