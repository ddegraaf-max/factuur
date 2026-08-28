@extends('layouts.marketing')

@section('title', 'Verwerkersovereenkomst EasyInvoice — AVG-afspraken voor jouw administratie')
@section('description', 'De verwerkersovereenkomst tussen jou (verwerkingsverantwoordelijke) en Creditline B.V. / EasyInvoice (verwerker): doel, subverwerkers, beveiliging, datalekken, bewaartermijn en verwijdering. Automatisch van kracht bij gebruik.')

@section('content')
<style>
  .legal{padding:60px 0 80px;}
  .legal .container{max-width:760px;}
  .legal h1{font-size:clamp(30px,5vw,42px);margin-bottom:10px;}
  .legal .meta{color:var(--text-3);font-size:14px;margin-bottom:26px;}
  .legal .entity{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:18px 20px;margin-bottom:34px;box-shadow:var(--shadow-sm);font-size:14.5px;line-height:1.7;color:var(--text-2);}
  .legal .entity strong{color:var(--text);}
  .legal h2{font-size:20px;margin:34px 0 10px;}
  .legal h3{font-size:16px;margin:22px 0 6px;}
  .legal p{color:var(--text-2);margin:0 0 14px;line-height:1.75;}
  .legal ul{color:var(--text-2);margin:0 0 16px;padding-left:20px;line-height:1.75;}
  .legal li{margin-bottom:7px;}
  .legal a{color:var(--brand);font-weight:500;}
  .legal a:hover{text-decoration:underline;}
  .legal table{width:100%;border-collapse:collapse;font-size:14px;margin:0 0 18px;}
  .legal th,.legal td{text-align:left;padding:9px 10px;border-bottom:1px solid var(--border);vertical-align:top;color:var(--text-2);}
  .legal th{color:var(--text);font-weight:600;background:var(--surface-2);}
  .legal .disclaimer{margin-top:38px;padding:14px 16px;background:var(--surface-2);border-radius:10px;font-size:13px;color:var(--text-3);}
</style>

<section class="legal">
  <div class="container">
    <div class="eyebrow">Juridisch</div>
    <h1>Verwerkersovereenkomst</h1>
    <div class="meta">Versie 1.0 · van kracht sinds 29 augustus 2026 · maakt deel uit van de <a href="{{ route('voorwaarden') }}">algemene voorwaarden</a></div>

    <div class="entity">
      <strong>Verwerker:</strong> Creditline B.V., handelend onder de naam <strong>EasyInvoice®</strong><br>
      Torenlaan 5B · 1402 AT Bussum · Nederland · KvK 59683198 · BTW NL853603108B01<br>
      <strong>Verwerkingsverantwoordelijke:</strong> de ondernemer of organisatie die een EasyInvoice-administratie aanmaakt ("jij").<br>
      Vragen: <a href="mailto:hallo@easyinvoice.nl">hallo@easyinvoice.nl</a>
    </div>

    <p>Jij voert in EasyInvoice gegevens in van je klanten, leveranciers en medewerkers. Voor die gegevens ben jij de verwerkingsverantwoordelijke in de zin van de AVG en zijn wij de verwerker. Deze overeenkomst legt vast wat wij met die gegevens mogen doen — en vooral wat niet. Hij geldt automatisch zodra je een administratie aanmaakt; een aparte handtekening is niet nodig. Wil je een ondertekend exemplaar voor je eigen dossier, mail ons dan.</p>

    <h2>1. Onderwerp en doel</h2>
    <p>Wij verwerken persoonsgegevens uitsluitend om de dienst EasyInvoice aan jou te leveren: het opstellen, versturen, innen en bewaren van facturen, offertes en inkoopfacturen, het bijbehorende klantenportaal, herinneringen, rapportages en — als je dat aanzet — de AI-functies (bonherkenning, offerte uit tekst). Wij verwerken niets voor eigen doeleinden en verkopen nooit gegevens.</p>

    <h2>2. Welke gegevens en van wie</h2>
    <table>
      <tr><th>Betrokkenen</th><th>Gegevens</th></tr>
      <tr><td>Je klanten en contactpersonen</td><td>naam, adres, e-mailadres, telefoonnummer, KvK- en btw-nummer, factuur- en betaalhistorie, portaalgebruik (tijdstip, IP-adres)</td></tr>
      <tr><td>Je leveranciers</td><td>naam, adres, IBAN, factuurgegevens en de ingelezen inkoopfacturen</td></tr>
      <tr><td>Je medewerkers/teamleden</td><td>naam, e-mailadres, rol, uren en ritten, in- en uitloggegevens</td></tr>
      <tr><td>Jijzelf</td><td>accountgegevens, bedrijfsgegevens, logboek van handelingen</td></tr>
    </table>

    <h2>3. Instructies</h2>
    <p>Wij verwerken de gegevens alleen volgens jouw instructies, dat wil zeggen: zoals de functies van EasyInvoice werken en zoals jij ze instelt. Vinden wij een instructie in strijd met de AVG, dan melden we dat. Wij kijken niet in je administratie, behalve wanneer jij ons daar bij een supportvraag om vraagt of wanneer dat technisch noodzakelijk is om een storing te verhelpen — en dan alleen zo kort mogelijk.</p>

    <h2>4. Subverwerkers</h2>
    <p>Om de dienst te leveren schakelen wij de volgende partijen in. Wij hebben met elk van hen een verwerkersovereenkomst of gelijkwaardige afspraken (waaronder de EU-modelcontracten waar nodig).</p>
    <table>
      <tr><th>Subverwerker</th><th>Doel</th><th>Locatie</th></tr>
      <tr><td>Railway Corp.</td><td>hosting van applicatie en database</td><td>EU (Amsterdam)</td></tr>
      <tr><td>Resend, Inc.</td><td>verzenden van e-mail (facturen, offertes, portaalcodes, meldingen)</td><td>EU/VS · EU-modelcontracten</td></tr>
      <tr><td>Stripe Payments Europe</td><td>afhandeling van jouw EasyInvoice-abonnement (geen klantgegevens)</td><td>EU</td></tr>
      <tr><td>Recommand BV</td><td>Peppol e-facturatie — alleen als je Peppol activeert</td><td>EU (België)</td></tr>
      <tr><td>Anthropic PBC</td><td>AI-functies: herkennen van bonnen en inkoopfacturen, offerte uit tekst — alleen als je die functies gebruikt; geen training op jouw gegevens</td><td>VS · EU-modelcontracten</td></tr>
      <tr><td>Cloudflare, Inc.</td><td>bescherming van formulieren tegen misbruik (Turnstile)</td><td>EU/VS</td></tr>
    </table>
    <p>Wijzigen wij een subverwerker, dan kondigen wij dat minimaal 30 dagen vooraf aan op de pagina <a href="{{ route('changelog') }}">Wat is nieuw</a> en per e-mail aan de eigenaar van de administratie. Bezwaar? Dan kun je de overeenkomst kosteloos beëindigen en je gegevens exporteren.</p>

    <h2>5. Beveiliging</h2>
    <ul>
      <li>Versleutelde verbindingen (TLS) voor alle verkeer; versleutelde opslag van gevoelige sleutels (bijv. Mollie- en btw-gegevens).</li>
      <li>Tweestapsverificatie voor gebruikers; klantenportaal alleen toegankelijk met een eenmalige code naar het e-mailadres van de klant.</li>
      <li>Strikte scheiding tussen administraties; rollen (eigenaar, medewerker, boekhouder) beperken de toegang.</li>
      <li>Dagelijkse back-ups op een aparte locatie binnen de EU; logboek van alle handelingen per administratie.</li>
      <li>Toegang tot productiesystemen alleen voor de beheerder van EasyInvoice, met tweestapsverificatie.</li>
    </ul>

    <h2>6. Datalekken</h2>
    <p>Ontdekken wij een inbreuk in verband met persoonsgegevens die jouw administratie raakt, dan informeren wij je <strong>zonder onredelijke vertraging en uiterlijk binnen 48 uur</strong> na ontdekking per e-mail, met wat er bekend is over aard, omvang en getroffen gegevens, de gevolgen en de genomen maatregelen. Jij beoordeelt of melding bij de Autoriteit Persoonsgegevens en/of betrokkenen nodig is; wij helpen daarbij.</p>

    <h2>7. Rechten van betrokkenen en bijstand</h2>
    <p>Vraagt een klant of medewerker van jou om inzage, correctie of verwijdering, dan kun je dat vrijwel altijd zelf afhandelen in EasyInvoice. Lukt dat niet, dan helpen wij binnen vijf werkdagen. Verzoeken die wij rechtstreeks ontvangen sturen wij naar jou door.</p>

    <h2>8. Geheimhouding</h2>
    <p>Iedereen die bij ons toegang heeft tot persoonsgegevens is gebonden aan geheimhouding. Wij verstrekken geen gegevens aan derden, tenzij een wettelijke verplichting ons daartoe dwingt — dan informeren wij je vooraf, voor zover toegestaan.</p>

    <h2>9. Audit</h2>
    <p>Je mag ons één keer per jaar (en na een datalek) schriftelijk vragen om aan te tonen dat wij deze overeenkomst naleven. Wij beantwoorden zulke vragen binnen 30 dagen met een beschrijving van de maatregelen en, waar beschikbaar, verklaringen van onze subverwerkers.</p>

    <h2>10. Duur, export en verwijdering</h2>
    <p>Deze overeenkomst geldt zolang je een EasyInvoice-administratie hebt. Je kunt op elk moment je volledige administratie exporteren (Instellingen → Bedrijfsgegevens → Jouw gegevens). Verwijder je je administratie, dan wissen wij alle gegevens direct uit de productiedatabase; back-ups worden na maximaal 30 dagen automatisch overschreven. Wettelijke bewaarplichten (zoals de fiscale bewaartermijn van 7 jaar) rusten op jou — exporteer daarom vóór verwijdering.</p>

    <h2>11. Aansprakelijkheid en recht</h2>
    <p>Voor aansprakelijkheid geldt wat in de <a href="{{ route('voorwaarden') }}">algemene voorwaarden</a> staat. Op deze overeenkomst is Nederlands recht van toepassing; geschillen worden voorgelegd aan de bevoegde rechter in Midden-Nederland.</p>

    <div class="disclaimer">Deze verwerkersovereenkomst is opgesteld conform artikel 28 AVG. Zie ook ons <a href="{{ route('privacy') }}">privacybeleid</a> (hoe wij als verwerkingsverantwoordelijke met jóuw accountgegevens omgaan) en het <a href="{{ route('cookies') }}">cookiebeleid</a>.</div>
  </div>
</section>
@endsection
