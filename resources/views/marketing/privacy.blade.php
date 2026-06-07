@extends('layouts.marketing')

@section('title', 'Privacybeleid — EasyInvoice')
@section('description', 'Hoe EasyInvoice omgaat met je persoonsgegevens. AVG-compliant.')

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Juridisch</div>
    <h1>Privacybeleid</h1>
    <p class="lead">Laatst bijgewerkt: 1 januari 2026</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="prose">
      <p>EasyInvoice B.V. respecteert je privacy en verwerkt persoonsgegevens conform de Algemene Verordening Gegevensbescherming (AVG/GDPR). In dit beleid lees je welke gegevens we verzamelen en waarom.</p>

      <h2>Welke gegevens we verwerken</h2>
      <ul>
        <li><strong>Accountgegevens:</strong> naam, e-mailadres en bedrijfsgegevens (KvK, BTW, IBAN).</li>
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

      <h2>Opslag en beveiliging</h2>
      <p>Je gegevens worden versleuteld opgeslagen op servers binnen de Europese Unie. We maken dagelijks back-ups, gebruiken TLS-versleuteling en bieden tweestapsverificatie (2FA) aan.</p>

      <h2>Bewaartermijn</h2>
      <p>We bewaren je gegevens zolang je een account hebt en daarna niet langer dan wettelijk verplicht of redelijkerwijs noodzakelijk.</p>

      <h2>Jouw rechten</h2>
      <p>Je hebt het recht op inzage, correctie, verwijdering en overdraagbaarheid van je gegevens. Je kunt je data op elk moment exporteren vanuit je account. Voor verzoeken mail je <a href="mailto:privacy@easyinvoice.nl">privacy@easyinvoice.nl</a>.</p>

      <h2>Cookies</h2>
      <p>We gebruiken alleen functionele en analytische cookies. Lees meer op onze <a href="{{ route('cookies') }}">cookiepagina</a>.</p>

      <h2>Contact</h2>
      <p>Vragen over je privacy? Mail <a href="mailto:privacy@easyinvoice.nl">privacy@easyinvoice.nl</a>.</p>

      <p style="font-size:13px;color:var(--text-3);margin-top:32px;">Dit is een voorbeeldtekst en vormt geen juridisch advies. Laat het definitieve privacybeleid controleren door een jurist.</p>
    </div>
  </div>
</section>
@endsection
