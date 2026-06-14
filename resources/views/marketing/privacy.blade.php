@extends('layouts.marketing')

@section('title', 'Privacybeleid — EasyInvoice')
@section('description', 'Hoe EasyInvoice omgaat met je persoonsgegevens. AVG-compliant.')

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
    <h1>Privacybeleid</h1>
    <div class="meta">Laatst bijgewerkt: 14 juni 2026</div>

    <div class="entity">
      Verwerkingsverantwoordelijke:<br>
      <strong>Creditline B.V.</strong> (handelend onder de naam <strong>EasyInvoice®</strong>)<br>
      Torenlaan 5B · 1402 AT Bussum · Nederland<br>
      KvK 59683198 · BTW NL853603108B01<br>
      Privacyvragen: <a href="mailto:hallo@easyinvoice.nl">hallo@easyinvoice.nl</a>
    </div>

    <p>Creditline B.V. respecteert je privacy en verwerkt persoonsgegevens conform de Algemene Verordening Gegevensbescherming (AVG/GDPR). In dit beleid lees je welke gegevens we verzamelen en waarom.</p>

    <h2>Welke gegevens we verwerken</h2>
    <ul>
      <li><strong>Accountgegevens:</strong> naam, e-mailadres en bedrijfsgegevens (KvK, btw, IBAN).</li>
      <li><strong>Factuurgegevens:</strong> de facturen, klanten en producten die je in EasyInvoice invoert.</li>
      <li><strong>Gebruiksgegevens:</strong> technische gegevens zoals IP-adres en logbestanden, om de dienst veilig en betrouwbaar te houden.</li>
    </ul>

    <h2>Waarvoor we ze gebruiken</h2>
    <ul>
      <li>Om de dienst te leveren en je facturatie mogelijk te maken.</li>
      <li>Om je te ondersteunen bij vragen.</li>
      <li>Om de dienst te beveiligen en te verbeteren.</li>
      <li>Om te voldoen aan wettelijke verplichtingen.</li>
    </ul>

    <h2>Delen met derden</h2>
    <p>We verkopen je gegevens nooit. We delen ze alleen met verwerkers die nodig zijn om de dienst te leveren — zoals onze betaalprovider (Stripe) en onze hostingpartij binnen de Europese Unie. Met deze partijen zijn verwerkersovereenkomsten gesloten.</p>

    <h2>Opslag en beveiliging</h2>
    <p>Je gegevens worden versleuteld opgeslagen op servers binnen de Europese Unie. We maken dagelijks back-ups, gebruiken TLS-versleuteling en bieden tweestapsverificatie (2FA) aan.</p>

    <h2>Bewaartermijn</h2>
    <p>We bewaren je gegevens zolang je een account hebt en daarna niet langer dan wettelijk verplicht of redelijkerwijs noodzakelijk.</p>

    <h2>Jouw rechten</h2>
    <p>Je hebt het recht op inzage, correctie, verwijdering en overdraagbaarheid van je gegevens. Je kunt je data op elk moment exporteren vanuit je account. Voor verzoeken mail je <a href="mailto:hallo@easyinvoice.nl">hallo@easyinvoice.nl</a>. Ook kun je een klacht indienen bij de Autoriteit Persoonsgegevens.</p>

    <h2>Cookies</h2>
    <p>We gebruiken alleen functionele en analytische cookies. Lees meer op onze <a href="{{ route('cookies') }}">cookiepagina</a>.</p>

    <h2>Contact</h2>
    <p>Vragen over je privacy? Mail <a href="mailto:hallo@easyinvoice.nl">hallo@easyinvoice.nl</a>.</p>

    <div class="disclaimer">Dit is een voorbeeldtekst en vormt geen juridisch advies. Laat het definitieve privacybeleid controleren door een jurist.</div>
  </div>
</section>
@endsection
