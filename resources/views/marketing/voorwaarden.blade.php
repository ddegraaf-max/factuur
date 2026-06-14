@extends('layouts.marketing')

@section('title', 'Algemene voorwaarden — EasyInvoice')
@section('description', 'De algemene voorwaarden van EasyInvoice, een dienst van Creditline B.V.')

@section('content')
<style>
  .legal{padding:60px 0 80px;}
  .legal .container{max-width:760px;}
  .legal h1{font-size:clamp(30px,5vw,42px);margin-bottom:10px;}
  .legal .meta{color:var(--text-3);font-size:14px;margin-bottom:26px;}
  .legal .entity{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:18px 20px;margin-bottom:34px;box-shadow:var(--shadow-sm);font-size:14.5px;line-height:1.75;color:var(--text-2);}
  .legal .entity strong{color:var(--text);}
  .legal h2{font-size:20px;margin:34px 0 10px;}
  .legal h3{font-size:16px;margin:22px 0 6px;}
  .legal p{color:var(--text-2);margin:0 0 14px;line-height:1.75;}
  .legal ul{color:var(--text-2);margin:0 0 16px;padding-left:20px;line-height:1.75;}
  .legal li{margin-bottom:7px;}
  .legal a{color:var(--brand);font-weight:500;}
  .legal a:hover{text-decoration:underline;}
  .legal .disclaimer{margin-top:38px;padding:14px 16px;background:var(--surface-2);border-radius:10px;font-size:13px;color:var(--text-3);}
</style>

<section class="legal">
  <div class="container">
    <div class="eyebrow">Juridisch</div>
    <h1>Algemene voorwaarden</h1>
    <div class="meta">Laatst bijgewerkt: 14 juni 2026</div>

    <div class="entity">
      <strong>EasyInvoice®</strong> is een dienst van<br>
      <strong>Creditline B.V.</strong><br>
      Torenlaan 5B · 1402 AT Bussum · Nederland<br>
      KvK 59683198 · BTW NL853603108B01<br>
      E-mail: <a href="mailto:hallo@easyinvoice.nl">hallo@easyinvoice.nl</a>
    </div>

    <p>Deze algemene voorwaarden zijn van toepassing op het gebruik van EasyInvoice, online facturatiesoftware aangeboden door Creditline B.V., gevestigd te Bussum (KvK 59683198, BTW NL853603108B01). Door een account aan te maken ga je akkoord met deze voorwaarden.</p>

    <h2>1. De dienst</h2>
    <p>EasyInvoice is online facturatiesoftware waarmee je facturen kunt opstellen, klanten kunt beheren, btw kunt bijhouden en betalingen kunt opvolgen. We spannen ons in om de dienst beschikbaar en betrouwbaar te houden, maar kunnen geen ononderbroken beschikbaarheid garanderen.</p>

    <h2>2. Gratis proefperiode</h2>
    <p>Nieuwe accounts krijgen een gratis proefperiode van 14 dagen met volledige toegang tot alle functies. Tijdens de proefperiode hoef je geen betaalgegevens op te geven. Na afloop van de proefperiode is een betaald abonnement nodig om te blijven werken; je gegevens blijven bewaard.</p>

    <h2>3. Abonnement en betaling</h2>
    <p>Het abonnement kost €2,50 per maand (exclusief 21% btw). Na het afsluiten wordt het abonnement automatisch elke 30 dagen verlengd en geïncasseerd, totdat je opzegt. Betaling verloopt veilig via onze betaalprovider Stripe; wij bewaren geen volledige kaartgegevens.</p>
    <p>Je kunt je abonnement op elk moment maandelijks opzeggen via Instellingen → Abonnement. Na opzegging behoud je toegang tot het einde van de reeds betaalde periode. Er volgt geen restitutie voor een lopende periode.</p>

    <h2>4. Gebruik</h2>
    <p>Je bent verantwoordelijk voor de juistheid van de gegevens die je invoert en voor het naleven van de fiscale en wettelijke verplichtingen die op jouw onderneming van toepassing zijn. Misbruik van de dienst is niet toegestaan.</p>

    <h2>5. Jouw gegevens</h2>
    <p>Je behoudt te allen tijde de eigendomsrechten op je eigen gegevens. Je kunt je data op elk moment exporteren. Zie ons <a href="{{ route('privacy') }}">privacybeleid</a> voor hoe we met je gegevens omgaan.</p>

    <h2>6. Aansprakelijkheid</h2>
    <p>Creditline B.V. is niet aansprakelijk voor indirecte schade. Onze aansprakelijkheid is in alle gevallen beperkt tot het bedrag dat je in de twaalf maanden voorafgaand aan het schadeveroorzakende feit aan ons hebt betaald.</p>

    <h2>7. Opzegging</h2>
    <p>Je kunt je abonnement op elk moment opzeggen via Instellingen → Abonnement. Na opzegging blijven je facturen gedurende een redelijke termijn downloadbaar.</p>

    <h2>8. Wijzigingen</h2>
    <p>We kunnen deze voorwaarden van tijd tot tijd aanpassen. Bij belangrijke wijzigingen informeren we je per e-mail.</p>

    <h2>9. Toepasselijk recht</h2>
    <p>Op deze voorwaarden en het gebruik van EasyInvoice is Nederlands recht van toepassing. Geschillen worden voorgelegd aan de bevoegde rechter in het arrondissement waar Creditline B.V. is gevestigd.</p>

    <h2>10. Contact</h2>
    <p>Vragen over deze voorwaarden? Mail ons via <a href="mailto:hallo@easyinvoice.nl">hallo@easyinvoice.nl</a> of bekijk de <a href="{{ route('contact') }}">contactpagina</a>.</p>

    <div class="disclaimer">Dit is een voorbeeldtekst en vormt geen juridisch advies. Laat de definitieve voorwaarden controleren door een jurist.</div>
  </div>
</section>
@endsection
