@extends('layouts.marketing')

@section('title', 'Wat is nieuw in ' . brand('name') . ' — updates en verbeteringen')
@section('description', 'Elke maand nieuwe functies in ' . brand('name') . ': van AI-bonherkenning en de Claude-koppeling tot termijnfacturen en eigen briefpapier. Bekijk alle updates op een rij.')

@push('styles')
<style>
  .timeline { max-width: 760px; margin: 0 auto; position: relative; padding-left: 28px; }
  .timeline::before { content: ''; position: absolute; left: 7px; top: 8px; bottom: 8px; width: 2px; background: var(--border); }
  .tl-item { position: relative; padding-bottom: 36px; }
  .tl-item:last-child { padding-bottom: 0; }
  .tl-dot { position: absolute; left: -28px; top: 4px; width: 16px; height: 16px; border-radius: 50%; background: var(--brand); border: 3px solid var(--bg); }
  .tl-meta { display: flex; align-items: center; gap: 12px; font-size: 13px; color: var(--text-3); flex-wrap: wrap; }
  .tl-item h2 { font-size: 20px; margin: 8px 0 10px; }
  .tl-list { margin: 0; padding-left: 18px; color: var(--text-2); line-height: 1.7; }
  .tl-list li { margin-bottom: 6px; }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Wat is nieuw</div>
    <h1>Elke maand beter</h1>
    <p class="lead">We verbeteren {{ brand('name') }} continu op basis van wat ondernemers nodig hebben. Hier vind je de laatste updates.</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="timeline">
      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Fix</span> 1 september 2026 · {{ brand('version_prefix') }} 1.46.1</div>
        <h2>Kleine verbeteringen</h2>
        <ul class="tl-list">
          <li><b>Adres /logout</b> — wie dat adres rechtstreeks intikt, komt nu netjes op het dashboard of de inlogpagina in plaats van op een kale foutmelding. Uitloggen zelf doe je nog steeds via het gebruikersmenu.</li>
          <li><b>Foutpagina in huisstijl</b> — een adres dat alleen via een knop of formulier werkt, toont voortaan een nette pagina met de weg terug.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 31 augustus 2026 · {{ brand('version_prefix') }} 1.46.0</div>
        <h2>Onder de motorkap: vorderingen te gelde maken (Poolse omgeving)</h2>
        <ul class="tl-list">
          <li><b>Factuur verkopen</b> — in de Poolse omgeving bied je een onbetaalde factuur met één klik te koop aan bij de factuurkoper; offerte binnen één werkdag.</li>
          <li><b>Oude vonnissen</b> — ook vorderingen met een executoriale titel (wyrok, nakaz zapłaty) kun je aanbieden, per dossier beoordeeld; met een publieke informatiepagina en aanmeldformulier.</li>
          <li><b>Wettelijke rente exact</b> — rente per halfjaarperiode en de vaste vergoeding tegen de officiële NBP-koers van de wettelijk juiste dag.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 30 augustus 2026 · {{ brand('version_prefix') }} 1.45.0</div>
        <h2>Onder de motorkap: de interface in meerdere talen</h2>
        <ul class="tl-list">
          <li><b>Taalkeuze per omgeving</b> — waar een omgeving meer dan één taal biedt, kies je die zelf: in het gebruikersmenu, op de inlogpagina en op de website. Je keuze wordt onthouden bij je account. In Nederland blijft alles Nederlands; de Poolse omgeving biedt Pools en Engels.</li>
          <li><b>Documenten volgen de klant</b> — de taal van facturen, offertes en klantmails stel je per klant in (Nederlands, Engels, Pools) en staat los van de taal van je scherm.</li>
          <li><b>Bedragen en datums blijven vertrouwd</b> — geld, getallen en datums houden de schrijfwijze van je land, ook als je scherm in een andere taal staat.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 30 augustus 2026 · {{ brand('version_prefix') }} 1.44.0</div>
        <h2>Onder de motorkap: één platform, meerdere merken</h2>
        <ul class="tl-list">
          <li><b>Merkonafhankelijke basis</b> — naam, logo, kleuren en teksten komen nu uit één merkconfiguratie, zodat dezelfde software onder meer dan één merk kan draaien. Voor jou verandert er niets; alle functies blijven gelijk.</li>
          <li><b>App-installatie (PWA) vernieuwd</b> — manifest en offline-melding worden per omgeving opgebouwd; installeer de app opnieuw als je icoon of naam niet meer klopt.</li>
          <li><b>Helpcentrum en e-mails</b> — alle systeemmails, PDF-voetteksten en helpartikelen gebruiken dezelfde merkgegevens; e-mailknoppen volgen de merkkleur.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 29 augustus 2026 · {{ brand('version_prefix') }} 1.43.0</div>
        <h2>Voor starters: huisstijl, visitekaartje en website</h2>
        <ul class="tl-list">
          <li><b>Digitaal visitekaartje</b> — één link (en QR-code) met al je contactgegevens in je eigen huisstijl: bellen, mailen, WhatsApp, opslaan in contacten. In te richten onder Instellingen → Visitekaartje, met je eigen adres zoals {{ brand('domain') }}/k/jouw-bedrijf.</li>
          <li><b>Huisstijl ontwerpen met AI</b> — nog geen huisstijl? Vertel wat je doet en voor wie, en kies uit drie voorstellen met kleuren, lettertype, factuursjabloon, slogan en een logo. Eén klik en alles staat op je facturen, offertes en mails (Slim).</li>
          <li><b>Je eigen website</b> — een complete one-pager in je huisstijl met diensten, over ons, waarom jij en een contactformulier; de tekst laat je schrijven door AI uit vier vragen (Slim) of schrijf je zelf. Berichten komen als leads binnen in {{ brand('name') }} én in je mail.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 29 augustus 2026 · {{ brand('version_prefix') }} 1.42.0</div>
        <h2>Automatische bankkoppeling</h2>
        <ul class="tl-list">
          <li><b>Bank koppelen via Ponto</b> — bij Bank &amp; transacties koppel je met een paar klikken je zakelijke rekening (alle Nederlandse en Belgische banken). Nieuwe transacties komen daarna drie keer per dag vanzelf binnen, met dezelfde slimme koppelsuggesties als bij een geïmporteerd afschrift. Afschriften uploaden blijft gewoon werken.</li>
          <li><b>Saldo en toestemming in beeld</b> — per rekening zie je het actuele saldo, wanneer er voor het laatst is bijgewerkt en wanneer je bank om een nieuwe toestemming vraagt. Rekeningen die je niet wilt volgen zet je met één vinkje uit.</li>
          <li><b>Kosten</b> — € 5 per gekoppelde rekening per maand (excl. btw), bovenop je abonnement en alleen voor rekeningen die je laat synchroniseren; het bedrag beweegt automatisch mee. Afschriften importeren blijft gratis.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 29 augustus 2026 · {{ brand('version_prefix') }} 1.41.0</div>
        <h2>Groot onderhoud: incasso, auditfile, eigen domein, overstappen en zoeken</h2>
        <ul class="tl-list">
          <li><b>Automatische incasso (SEPA)</b> — leg per klant een machtiging vast en bundel open facturen in één incassobestand voor je bank (Verkoop → Automatische incasso). CORE en B2B, eerste en vervolgincasso's, alles volgens de bankstandaard pain.008.</li>
          <li><b>Auditfile voor je accountant (XAF 3.2)</b> — bij Export boekhouder download je per boekjaar het standaardbestand dat Twinfield, Exact, e-Boekhouden en de Belastingdienst direct inlezen: verkoop-, inkoop- en bankboek met btw per tarief.</li>
          <li><b>Mail vanaf je eigen domein</b> — koppel je domein bij Instellingen → Koppelingen (twee DNS-records) en je facturen, offertes en herinneringen gaan uit als bijvoorbeeld facturen@jouwbedrijf.nl.</li>
          <li><b>Overstapwizard</b> — klanten, producten en openstaande facturen uit WeFact, Moneybird, e-Boekhouden of Excel importeren met automatische kolomherkenning (Instellingen → Overstappen).</li>
          <li><b>Zoeken met Ctrl-K</b> — vind elke factuur, offerte, klant of product vanaf elke pagina, en spring met een paar letters naar acties als "nieuwe offerte".</li>
          <li><b>Installeerbaar als app</b> op telefoon en desktop, en een opslagmeter bij Bedrijfsgegevens.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 29 augustus 2026 · {{ brand('version_prefix') }} 1.40.0</div>
        <h2>Logboek, jouw gegevens en bewaking</h2>
        <ul class="tl-list">
          <li><b>Logboek</b> — bij Instellingen → Logboek zie je voortaan wie wat deed en wanneer: elke wijziging aan facturen, offertes, klanten, producten, betalingen en instellingen, elke verzending en herinnering, en elke aanmelding. Met filters en CSV-export voor je accountant of bij een geschil.</li>
          <li><b>Jouw gegevens, jouw keuze (AVG)</b> — bij Instellingen → Bedrijfsgegevens exporteer je met één klik je complete administratie als ZIP (CSV + JSON), en kun je als eigenaar de administratie definitief verwijderen. De nieuwe <a href="{{ route('verwerkersovereenkomst') }}">verwerkersovereenkomst</a> legt vast wat wij met de gegevens van jouw klanten mogen doen — en wat niet.</li>
          <li><b>Achter de schermen</b> — dagelijkse back-ups naar een aparte locatie, automatische foutmeldingen naar ons team en een doorlopende gezondheidscheck van het platform. Je merkt er niets van, en dat is precies de bedoeling.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Fix</span> 28 augustus 2026 · {{ brand('version_prefix') }} 1.39.1</div>
        <h2>Geplande taken en klantenportaal</h2>
        <ul class="tl-list">
          <li><b>Dagelijkse taken draaien weer betrouwbaar</b> — het dagelijks overzicht, automatische herinneringen en aanmaningen, terugkerende en ingeplande facturen, de btw-herinnering en het scannen van het Postvak IN worden nu gegarandeerd elke dag uitgevoerd. Stond een taak stil, dan pakt {{ brand('name') }} hem vanaf vandaag weer op.</li>
          <li><b>Klantenportaal</b> — {{ brand('name') }}-logo in de kop, "Offerte" in plaats van "Factuur" bij het bevestigen van een offerte, en foutmeldingen worden nu getoond in plaats van stilzwijgend genegeerd.</li>
          <li><b>Mollie</b> — nettere knop om de koppeling te verwijderen; de uitleg verwijst naar de nieuwe menunaam bij Mollie.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 28 augustus 2026 · {{ brand('version_prefix') }} 1.39.0</div>
        <h2>Zocht u een ander {{ brand('name') }}?</h2>
        <ul class="tl-list">
          <li><b>Verkeerde deur?</b> — er bestaan meer bedrijven met een naam die op de onze lijkt. Kwam u hier terecht terwijl u een ander bedrijf zocht, laat het ons dan weten via het nieuwe vinkje op het contactformulier of via de pagina <a href="{{ route('confusion') }}">Zocht u een ander {{ brand('name') }}?</a> in de voettekst. Zo kunnen we u snel de goede kant op wijzen.</li>
          <li>Onder de motorkap: de maandelijkse rapportage over het gebruik van het platform is geautomatiseerd.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 27 augustus 2026 · {{ brand('version_prefix') }} 1.38.0</div>
        <h2>Peppol: e-facturen verzenden én ontvangen</h2>
        <ul class="tl-list">
          <li><b>Je eigen Peppol-deelnemer</b> — activeer Peppol bij Instellingen → Koppelingen: je administratie wordt geregistreerd op het netwerk (op je KvK-nummer) en na een eenmalige online identiteitscontrole kun je verzenden en ontvangen. Geen aparte abonnementen of certificaten.</li>
          <li><b>Afleveren in het boekhoudpakket van je klant</b> — op facturen van klanten die op Peppol zitten staat "Via Peppol afleveren"; de e-factuur (NLCIUS of Peppol BIS 3, afgestemd op wat de ontvanger accepteert) komt rechtstreeks in hun administratie.</li>
          <li><b>Inkoopfacturen automatisch in je Postvak IN</b> — e-facturen van leveranciers verschijnen met alle gegevens ingevuld (leverancier, nummer, datums, btw per tarief) en een PDF-weergave; één klik en hij is ingeboekt.</li>
          <li>Ook nieuw: de bevestiging van een offerte-akkoord aan jou in de {{ brand('name') }}-huisstijl, en het klantenportaal stuurt nu ook een toegangscode aan klanten die alleen een offerte hebben.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 26 augustus 2026 · {{ brand('version_prefix') }} 1.37.0</div>
        <h2>Bevestiging naar de klant na akkoord op een offerte</h2>
        <ul class="tl-list">
          <li><b>"Bedankt voor uw akkoord"</b> — ondertekent je klant een offerte in het portaal, dan krijgt hij direct een bevestiging in jouw huisstijl: datum en naam van de ondertekenaar, het totaal, het termijnplan als dat er is, een "hoe nu verder"-tekst en de ondertekende offerte als PDF. Zo hebben jullie allebei hetzelfde document.</li>
          <li><b>Ook bij een akkoord per telefoon</b> — markeer je een offerte zelf als geaccepteerd, dan kies je met één vinkje of de bevestiging meegaat. Op de offertepagina zie je wanneer die is gemaild en kun je hem opnieuw sturen.</li>
          <li><b>Eigen tekst en voorbeeld</b> — bij Instellingen → E-mailteksten pas je onderwerp en "hoe nu verder"-tekst aan (met variabelen als {ondertekenaar} en {akkoorddatum}) en bekijk je de mail vooraf. Tweetalig: klanten met taal Engels krijgen de Engelse standaardtekst.</li>
          <li><b>Klantenportaal: offertes erbij</b> — je klant ziet in het portaal nu ook zijn offertes naast zijn facturen, met status, geldigheid en een directe "bekijk en onderteken"-link; een offerte die op reactie wacht staat bovenaan. Ook opgelost: klanten die alleen nog een offerte hadden (nog geen factuur) kregen geen toegangscode.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 26 augustus 2026 · {{ brand('version_prefix') }} 1.36.0</div>
        <h2>Btw-aangifte: aangifte-klaar, met betalingskenmerk</h2>
        <ul class="tl-list">
          <li><b>Alle rubrieken klaar</b> — per tijdvak (kwartaal, maand of jaar) staan 1a t/m 5c precies in de indeling van Mijn Belastingdienst Zakelijk, in hele euro's en afgerond in je voordeel. Klik op een bedrag om het te kopiëren; onder "Onderbouwing" zie je welke facturen in welke rubriek zitten. Export en EU-leveringen (3a/3b) worden op klantland herkend.</li>
          <li><b>Vul aan wat {{ brand('name') }} niet weet</b> — verlegde btw, inkoop uit het buitenland, privégebruik en niet-ingeboekte voorbelasting vul je per tijdvak zelf in; het wordt bewaard en meegeteld.</li>
          <li><b>Zo overgemaakt</b> — bedrag, het (nieuwe) rekeningnummer van de Belastingdienst en het betalingskenmerk staan klaar. Het kenmerk berekent {{ brand('name') }} uit je omzetbelastingnummer volgens de officiële specificatie van de Belastingdienst — of je plakt het kenmerk uit je ingestuurde aangifte.</li>
          <li><b>Niets meer vergeten</b> — vink "aangegeven" en "betaald" af, krijg twee weken en drie dagen vóór de deadline een herinnering met de cijfers erbij, en zie op het dashboard direct of er een aangifte openstaat. Het btw-kpi op het dashboard toont nu het saldo ná voorbelasting.</li>
          <li><b>Offertes op het dashboard</b> — je laatste offertes met status, wat er openstaat bij klanten (en wat bijna verloopt), wat geaccepteerd is maar nog gefactureerd moet worden, en je acceptatiegraad van dit jaar. Plus "Nieuwe offerte" bij de snelle acties.</li>
          <li><b>Klantpagina</b> — klik op een klant en zie alles bij elkaar: openstaand bedrag, omzet dit jaar en in totaal, betaalgedrag (gemiddeld aantal dagen en hoe vaak op tijd), offertes met acceptatiegraad, nog te factureren uren, alle facturen en offertes, gegevens en notities. Met knoppen om direct een factuur of offerte voor die klant te maken. Op de klantenkaarten staat nu ook het aantal offertes.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 25 augustus 2026 · {{ brand('version_prefix') }} 1.35.0</div>
        <h2>Bedankmail na betaling — met reviewlink</h2>
        <ul class="tl-list">
          <li><b>Automatisch bedanken</b> — zodra een factuur volledig is betaald, krijgt je klant een vriendelijke bedankmail in jouw huisstijl: een overzicht van de betaling, een knop naar het klantenportaal en de factuur met het stempel BETAALD als PDF (een betaalbewijs voor zijn administratie). Werkt bij de bankkoppeling en bij iDEAL-betalingen; boek je een betaling handmatig, dan kies je het per betaling met één vinkje.</li>
          <li><b>Vraag om een review</b> — vul je Google- of Trustpilot-link in bij Instellingen → E-mailteksten en de bedankmail krijgt een knop "Laat een review achter". Direct na een betaling is hét moment om erom te vragen.</li>
          <li><b>Eigen tekst, voorbeeld en historie</b> — onderwerp en tekst pas je aan met variabelen zoals {klant}, {bedrag} en {betaaldatum}; met "Bekijk voorbeeld" zie je de mail vooraf. In de factuurhistorie zie je precies wanneer de bedankmail is verstuurd. Voor bestaande administraties staat de functie uit tot je 'm aanzet.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 25 augustus 2026 · {{ brand('version_prefix') }} 1.34.0</div>
        <h2>Prijzen incl. of excl. btw intypen — en negatieve regels</h2>
        <ul class="tl-list">
          <li><b>Schakelaar op het formulier</b> — kies bij het maken van een factuur of offerte direct of je prijzen inclusief of exclusief btw intypt. Al ingevulde regels rekenen automatisch mee om; de btw wordt voor je teruggerekend. De standaard stel je nog steeds in bij Instellingen → Bedrijfsgegevens.</li>
          <li><b>Negatieve regels</b> — een korting of verrekende aanbetaling als losse regel met een negatief bedrag kan nu gewoon, op facturen én offertes. Alleen het totaal mag niet onder nul: daarvoor is er de creditnota.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 22 augustus 2026 · {{ brand('version_prefix') }} 1.33.0</div>
        <h2>Gratis tools en een kennisbank — ook zonder account</h2>
        <ul class="tl-list">
          <li><b>Gratis factuur maken</b> — op <a href="{{ route('gratis-factuur') }}">{{ brand('domain') }}/gratis-factuur-maken</a> maakt iedereen zonder account een professionele factuur-PDF, met correcte btw, btw verlegd of KOR-vrijstelling. Zonder watermerk, en er wordt niets opgeslagen.</li>
          <li><b>Btw- en uurtarief-calculator</b> — reken snel btw om (incl. ↔ excl.) of bepaal welk uurtarief je als zzp'er moet vragen.</li>
          <li><b>Kennisbank</b> — praktische artikelen over factuureisen, factuurnummers, btw-tarieven, de KOR en aanmanen. Gratis te lezen voor iedereen.</li>
          <li><b>Nieuwe pagina's</b> — een eigen pagina over <a href="{{ route('ai') }}">factureren met AI</a> en een pagina voor <a href="{{ route('boekhouders') }}">boekhouders en accountants</a> die gratis met hun klanten meekijken.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 21 augustus 2026 · {{ brand('version_prefix') }} 1.32.0</div>
        <h2>Statusstempels op je factuur-PDF</h2>
        <ul class="tl-list">
          <li><b>In één oogopslag duidelijk</b> — factuur-PDF's dragen nu een subtiel schuin stempel bij hun status: CONCEPT, BETAALD, VERVALLEN of GECREDITEERD. Een gewone openstaande factuur blijft schoon.</li>
          <li><b>Herinneringen en aanmaningen</b> — de PDF-bijlage bij een betalingsherinnering of aanmaning draagt automatisch het stempel HERINNERING of AANMANING, zodat de ontvanger direct de urgentie ziet.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 21 augustus 2026 · {{ brand('version_prefix') }} 1.31.0</div>
        <h2>Jouw huisstijl, jouw briefpapier</h2>
        <ul class="tl-list">
          <li><b>Huisstijl herkennen met AI</b> — upload je huisstijlgids, briefpapier of een oude factuur en de merkkleur, accentkleur, het lettertype en het best passende sjabloon worden automatisch voor je ingesteld. Jij bekijkt het live voorbeeld en bevestigt.</li>
          <li><b>Eigen briefpapier</b> — heb je een volledig ontworpen huisstijl (bijv. door AI gemaakt)? Upload hem als vierde sjabloon "Briefpapier": jouw ontwerp is de ondergrond en {{ brand('name') }} zet er alleen de factuurinhoud op, met instelbare marges. Werkt overal — factuurmail, herinneringen, portaal en incasso.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 21 augustus 2026 · {{ brand('version_prefix') }} 1.30.0</div>
        <h2>Dubbelcontrole op inkoopfacturen</h2>
        <ul class="tl-list">
          <li><b>Nooit meer per ongeluk dubbel boeken</b> — bij het scannen van een bon of factuur controleert {{ brand('name') }} automatisch of hij al in je administratie staat (op factuurnummer, of op leverancier + bedrag). Je krijgt dan een duidelijke waarschuwing op het formulier én op het kaartje in het Postvak IN.</li>
          <li><b>"Direct inboeken" beschermt je</b> — een vermoedelijke dubbel boek je niet met één klik in; via "Controleer eerst" kan het bewust alsnog.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 20 augustus 2026 · {{ brand('version_prefix') }} 1.28.0</div>
        <h2>Bijlagen bij offertes — ook rechtstreeks vanuit Claude</h2>
        <ul class="tl-list">
          <li><b>Bijlagen bij je offerte</b> — voeg een specificatie, plan van aanpak of tekening toe aan een offerte. Bijlagen voor de klant gaan automatisch mee met de offertemail; interne bijlagen blijven privé.</li>
          <li><b>Claude stuurt het document mee</b> — schreef je met Claude een uitgebreid offertedocument? Claude stuurt de tekst mee bij het aanmaken en {{ brand('name') }} maakt er een verzorgde PDF-bijlage van, in jouw administratie, klaar om mee te sturen. Een echt bestand (PDF of afbeelding) meegeven kan ook.</li>
          <li><b>In je eigen huisstijl</b> — de PDF die {{ brand('name') }} van een Claude-document maakt, draagt automatisch je logo, merkkleur en lettertype. En Claude kan een <b>handelsnaam</b> kiezen: "maak deze offerte onder handelsnaam X".</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 20 augustus 2026 · {{ brand('version_prefix') }} 1.27.0</div>
        <h2>Claude-koppeling — "zet deze offerte in {{ brand('name') }}"</h2>
        <ul class="tl-list">
          <li><b>Werk rechtstreeks vanuit je Claude-gesprek</b> — koppel {{ brand('name') }} eenmalig aan Claude (claude.ai of de desktopapp) en zeg voortaan gewoon: "zet deze offerte als concept in {{ brand('name') }}". Claude zoekt de klant op en maakt de concept-offerte of -factuur direct in je administratie aan.</li>
          <li><b>Jij houdt de controle</b> — alles wat Claude aanmaakt is een concept: versturen doe je altijd zelf in {{ brand('name') }}. Claude kan ook je openstaande facturen opvragen ("wie moet mij nog betalen?").</li>
          <li><b>Veilig gekoppeld</b> — via een geheime koppel-URL per administratie die je met één klik kunt vernieuwen of intrekken (Instellingen → Koppelingen). Onderdeel van het Slim-abonnement.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 20 augustus 2026 · {{ brand('version_prefix') }} 1.26.0</div>
        <h2>Inzicht in je AI-gebruik</h2>
        <ul class="tl-list">
          <li><b>Verbruiksteller</b> — op de abonnementspagina zie je precies hoeveel AI-acties (bonscans en offerteherkenningen) je deze maand hebt gebruikt. De teller staat elke maand weer op nul.</li>
          <li><b>Ruime fair-use</b> — het Slim-abonnement bevat 250 AI-acties per maand: meer dan genoeg voor dagelijks gebruik, en een vangnet tegen uitschieters. Handmatig inboeken blijft altijd onbeperkt.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 20 augustus 2026 · {{ brand('version_prefix') }} 1.25.0</div>
        <h2>Twee abonnementen: Basis en Slim</h2>
        <ul class="tl-list">
          <li><b>Basis (€ 10/maand excl. btw)</b> — het volledige facturatiepakket: onbeperkt facturen, offertes, klanten, inkoop, uren, BTW-overzicht, incasso, e-facturatie en het klantenportaal.</li>
          <li><b>Slim (€ 17,50/maand excl. btw)</b> — alles uit Basis, plus de AI-assistent: Scan &amp; herken voor bonnen en inkoopfacturen, automatische boekingsvoorstellen in het Postvak IN en Offerte uit tekst.</li>
          <li><b>Proefperiode = alles</b> — tijdens de 14 dagen gratis proberen zijn álle functies beschikbaar, inclusief AI. Bestaande abonnees behouden hun huidige prijs.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 20 augustus 2026 · {{ brand('version_prefix') }} 1.24.0</div>
        <h2>Offerte uit tekst — plak je conceptofferte, het formulier vult zich in</h2>
        <ul class="tl-list">
          <li><b>Schrijf je je offertes met Claude of ChatGPT?</b> Plak de tekst bovenaan het offerteformulier en klik op "Formulier invullen": de AI zet klant, offerteregels (met aantal, eenheid, prijs en btw), inleiding en voorwaarden op de juiste plek. Jij controleert en verstuurt.</li>
          <li><b>Slim meegedacht</b> — een herkende klantnaam wordt automatisch gekoppeld aan je bestaande klant, prijzen inclusief btw worden teruggerekend naar exclusief, en als de regels niet optellen tot het totaal in je tekst krijg je een waarschuwing.</li>
          <li><b>Niets gaat vanzelf de deur uit</b> — de AI vult alleen het formulier in; er wordt pas iets opgeslagen als jij op "Opslaan als concept" of "Versturen" klikt.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 20 augustus 2026 · {{ brand('version_prefix') }} 1.23.0</div>
        <h2>Verrekening op inkoopfacturen — "te betalen" klopt nu altijd</h2>
        <ul class="tl-list">
          <li><b>Al ontvangen bedragen verrekenen</b> — staat er op een inkoopfactuur een al ontvangen of ingehouden bedrag (bijv. door een deurwaarder ontvangen gelden, of een aanbetaling)? Zet het als verrekening op de factuur: je kosten en voorbelasting blijven volledig staan voor de BTW-aangifte, alleen het te betalen bedrag gaat omlaag.</li>
          <li><b>Scan &amp; herken leest verrekeningen mee</b> — vermeldt het document "reeds ontvangen" of "door u te voldoen", dan vult de AI-herkenning de verrekening automatisch in en controleert of het te betalen bedrag klopt met het document.</li>
          <li><b>Overal het juiste bedrag</b> — de openstaand-tellers, het crediteurenoverzicht en de CSV-export voor de boekhouder rekenen voortaan met het werkelijk te betalen bedrag (nieuwe kolommen "Verrekend" en "Te betalen").</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 19 augustus 2026 · {{ brand('version_prefix') }} 1.22.0</div>
        <h2>Vijf nieuwe functies: van termijnfacturen tot een slimmer postvak</h2>
        <ul class="tl-list">
          <li><b>Postvak IN verwerkt zichzelf</b> — aangeleverde bonnen en facturen worden nu automatisch herkend. Op elk kaartje staat een kant-en-klaar boekingsvoorstel (leverancier, datum, bedrag, categorie): met <b>Direct inboeken</b> bevestig je met één klik, met <b>Controleer eerst</b> open je het vooringevulde formulier. Forward je bon en je bent klaar.</li>
          <li><b>Termijnfacturen</b> — grote projecten in delen factureren: stel op een offerte een termijnplan op (bijv. 30% bij opdracht, 70% bij oplevering) en maak per termijn met één klik de factuur. De laatste termijn is automatisch het restant, zodat de som exact op de offertesom uitkomt.</li>
          <li><b>Korting per factuurregel</b> — geef per regel een kortingspercentage, op facturen én offertes. De klant ziet de originele prijs en de korting netjes op de PDF; de BTW rekent automatisch mee over het verlaagde bedrag, ook op creditnota's en in de UBL-e-factuur.</li>
          <li><b>Ouderdomsanalyse debiteuren</b> — nieuw rapport: wie staat er hoe lang open (1–30, 31–60, 61–90 en 90+ dagen), per klant en met de langst vervallen facturen bovenaan. Zo weet je precies waar je achteraan moet.</li>
          <li><b>Winstgevendheid per klant</b> — het rapport Klantomzet toont nu ook de bestede uren en het <b>effectieve uurtarief</b> (omzet ÷ uren) per klant: zo zie je welke klant écht wat oplevert.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 19 augustus 2026 · {{ brand('version_prefix') }} 1.21.0</div>
        <h2>QR-code betalen, cashflow-prognose &amp; eigen e-mailteksten</h2>
        <ul class="tl-list">
          <li><b>Scan &amp; betaal op de factuur-PDF</b> — heb je Mollie gekoppeld, dan staat er voortaan automatisch een QR-code naast de betaalinstructie op de factuur. Je klant scant met de telefoon en betaalt direct via iDEAL — ook als de factuur geprint op de deurmat ligt.</li>
          <li><b>Cashflow-prognose</b> — nieuw rapport dat laat zien wat er de komende maanden naar verwachting binnenkomt en uitgaat: openstaande facturen op vervaldatum, terugkerende facturen, openstaande inkoop en je vaste lasten. Inclusief wat er nú al vervallen (opeisbaar) is.</li>
          <li><b>Eigen e-mailteksten</b> — bepaal zelf het onderwerp en de tekst van je factuur- en offertemail, met variabelen zoals <b>{klant}</b>, <b>{factuurnummer}</b> en <b>{bedrag}</b>. De portaalknop en PDF-bijlage blijven automatisch staan. Leeg laten = de standaardtekst, die vanzelf de taal van de klant volgt.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 19 augustus 2026 · {{ brand('version_prefix') }} 1.20.0</div>
        <h2>Inkoopfacturen per e-mail aanleveren (Postvak IN)</h2>
        <ul class="tl-list">
          <li><b>Je eigen inboek-adres</b> — elke administratie krijgt een uniek e-mailadres. Stuur (of forward) bonnen en facturen met een PDF of foto als bijlage en ze verschijnen automatisch in het nieuwe Postvak IN onder Inkoop. Je kunt het adres ook direct aan leveranciers geven.</li>
          <li><b>Inboeken in twee klikken</b> — vanuit het postvak open je het inkoopformulier met het bestand er groot naast, inclusief <b>Scan &amp; herken</b>: de AI vult leverancier, datum en bedragen alvast in. Bij het opslaan wordt het bestand automatisch als bijlage gekoppeld.</li>
          <li><b>Veilig</b> — alleen bijlagen van het juiste type tellen, het adres is uniek per administratie en met één klik te vernieuwen als het bij spammers bekend raakt.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 19 augustus 2026 · {{ brand('version_prefix') }} 1.19.0</div>
        <h2>Vaste lasten — terugkerende inkoop automatisch inboeken</h2>
        <ul class="tl-list">
          <li><b>Eén keer instellen, nooit meer vergeten</b> — huur, software-abonnementen, verzekeringen: zet ze klaar als vaste last (wekelijks t/m jaarlijks) en {{ brand('name') }} boekt ze voortaan automatisch in als inkoopfactuur. De BTW telt vanzelf mee als voorbelasting in je aangifte.</li>
          <li><b>Incasso? Direct op betaald</b> — vaste lasten via automatische incasso worden meteen als betaald ingeboekt, met de juiste betaalwijze. Pauzeren, een einddatum of aanpassen kan altijd.</li>
          <li><b>Snelstart vanaf een bestaande factuur</b> — op elke inkoopfactuur staat nu "Maak terugkerend": één klik en dezelfde kosten worden voortaan maandelijks ingeboekt. Gemiste periodes worden automatisch ingehaald.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 19 augustus 2026 · {{ brand('version_prefix') }} 1.18.0</div>
        <h2>Jaaroverzicht — omzet, kosten en resultaat in één rapport</h2>
        <ul class="tl-list">
          <li><b>Je hele jaar in één oogopslag</b> — omzet (excl. btw, creditnota's verrekend), kosten per categorie, de kilometeraftrek (€ 0,23/km) en het resultaat, per kwartaal én vergeleken met vorig jaar.</li>
          <li><b>PDF voor je boekhouder</b> — download het complete overzicht en stuur het mee: alles uit je facturatie in één document, inclusief kilometeradministratie.</li>
          <li><b>Eerlijk over wat het is</b> — dit is de basis voor je aangifte inkomstenbelasting, geen complete fiscale winst-en-verliesrekening. Het rapport benoemt expliciet wat je boekhouder nog toevoegt: afschrijvingen, eventuele loonkosten, bijtelling en de ondernemersaftrekken.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 19 augustus 2026 · {{ brand('version_prefix') }} 1.17.0</div>
        <h2>Strippenkaarten &amp; tegoeden</h2>
        <ul class="tl-list">
          <li><b>Urenbundels vooraf verkopen</b> — maak op de urenpagina een strippenkaart aan (bijv. 10 uur voor € 850), factureer 'm met één klik, en het tegoed staat klaar.</li>
          <li><b>Automatisch aftellen</b> — geschreven uren (ook timeruren) worden automatisch van het tegoed afgeschreven. Je ziet per kaart een balkje met wat er is gebruikt en wat er nog over is, en gedekte uren komen nooit dubbel op een factuur.</li>
          <li><b>Voorspelbaar</b> — past een urenregel niet meer volledig in het resterende tegoed, dan blijft hij gewoon factureerbaar. Optioneel geef je een kaart een geldigheidsdatum; daarna schrijft hij niet meer af.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 19 augustus 2026 · {{ brand('version_prefix') }} 1.16.0</div>
        <h2>Offertes digitaal ondertekenen</h2>
        <ul class="tl-list">
          <li><b>Akkoord in twee minuten</b> — in de offertemail staat "Bekijk en onderteken online". Je klant opent de offerte in het beveiligde portaal, zet zijn handtekening in het tekenveld (met muis of vinger) en klaar — geen geprint papier, geen scanner.</li>
          <li><b>Rechtsgeldig bewijsdossier</b> — bij de handtekening worden het geverifieerde e-mailadres, de naam, het tijdstip en het IP-adres vastgelegd. De handtekening komt óók op de offerte-PDF te staan.</li>
          <li><b>Direct door naar de factuur</b> — jij krijgt meteen een mailtje ("Offerte is ondertekend 🎉"), de offerte springt op geaccepteerd en je zet hem met één klik om naar een factuur. Afwijzen kan ook, met een toelichting die jij te zien krijgt.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 19 augustus 2026 · {{ brand('version_prefix') }} 1.15.0</div>
        <h2>Betaallink op de factuur — betaald worden met iDEAL</h2>
        <ul class="tl-list">
          <li><b>Klanten betalen in twee klikken</b> — in de factuurmail staat "Bekijk en betaal online", en in het klantenportaal betaalt je klant het openstaande bedrag direct met iDEAL. De betaling wordt automatisch op de factuur geboekt en de status springt op betaald.</li>
          <li><b>Jouw eigen Mollie-account</b> — koppel je Mollie API-key bij Instellingen → Bedrijfsgegevens. Het geld gaat rechtstreeks naar jouw rekening; {{ brand('name') }} zit er niet tussen en rekent er niets voor.</li>
          <li><b>Slim en veilig</b> — deelbetalingen worden verrekend (de knop toont altijd het resterende bedrag), betaalpogingen zie je terug in het inzagelog, en de key wordt versleuteld opgeslagen.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 19 augustus 2026 · {{ brand('version_prefix') }} 1.14.0</div>
        <h2>Meerdere administraties onder één inlog</h2>
        <ul class="tl-list">
          <li><b>Meerdere bedrijven, één account</b> — start extra administraties (elk met eigen KvK, klanten, facturen, huisstijl én eigen nummering) en wissel moeiteloos via het menu linksonder. Elke administratie heeft een eigen abonnement en start met een eigen gratis proefperiode.</li>
          <li><b>Strikt gescheiden</b> — administraties zien elkaars gegevens nooit; je rol kan per administratie verschillen (beheerder in de één, boekhouder in de ander).</li>
          <li><b>Eén inlog voor je boekhouder</b> — wordt iemand met een bestaand {{ brand('name') }}-account uitgenodigd, dan koppelt die administratie zich aan diezelfde inlog. Ideaal voor boekhouders die voor meerdere klanten werken.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 19 augustus 2026 · {{ brand('version_prefix') }} 1.13.0</div>
        <h2>Facturen en offertes in het Engels</h2>
        <ul class="tl-list">
          <li><b>Taal per klant</b> — zet op de klantkaart de taal op Engels en alles wat die klant ontvangt is Engelstalig: de factuur- en offerte-PDF (alle sjablonen), de begeleidende e-mails én de datums ("19 August 2026").</li>
          <li><b>Slim vastgelegd</b> — de taal wordt per document vastgelegd bij het aanmaken. Verander je de klantinstelling later, dan blijven eerder verstuurde facturen exact zoals de klant ze kreeg. Ook creditnota's, herinnering-bijlagen en het incassodossier volgen de documenttaal.</li>
          <li>Je eigen teksten (omschrijvingen, notities, voetnoot) blijven zoals jij ze typt — jij bepaalt de taal daarvan zelf. Bedragen houden de vertrouwde notatie (€ 1.234,56).</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 19 augustus 2026 · {{ brand('version_prefix') }} 1.12.0</div>
        <h2>Handelsnamen nu ook op offertes</h2>
        <ul class="tl-list">
          <li><b>Offerte als …</b> — kies bij het maken van een offerte onder welke handelsnaam die de deur uitgaat. De offerte-PDF, de voorvertoning en de offertemail (inclusief afzendernaam) volgen automatisch die huisstijl.</li>
          <li><b>Van offerte tot factuur in dezelfde huisstijl</b> — zet je een geaccepteerde offerte om naar een factuur, dan erft die automatisch de handelsnaam. Je klant ziet van offerte tot betaling één consistent merk.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 19 augustus 2026 · {{ brand('version_prefix') }} 1.11.0</div>
        <h2>Kilometerregistratie — ritten bijhouden en doorbelasten</h2>
        <ul class="tl-list">
          <li><b>Ritten registreren</b> — datum, van–naar, afstand en het doel van de rit. Vink "retour" aan en de terugreis telt automatisch mee. Het nieuwe menu-item Ritten staat naast Uren.</li>
          <li><b>Doorbelasten met één klik</b> — alle openstaande ritten van een klant worden gebundeld op één conceptfactuur, als nette reiskostenregels met datum en afstand ("Reiskosten: Bussum – Amsterdam (retour), 62 km").</li>
          <li><b>Of alleen voor je administratie</b> — ritten zonder klant of zonder doorbelasting bewaar je als kilometeradministratie voor je aangifte, met totalen per maand en per jaar.</li>
          <li>Het standaardtarief staat op <b>€ 0,23 per km</b> (het onbelaste tarief van de Belastingdienst) en is per bedrijf en per rit aan te passen.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 19 augustus 2026 · {{ brand('version_prefix') }} 1.10.0</div>
        <h2>Meerdere handelsnamen — factureer onder verschillende merken</h2>
        <ul class="tl-list">
          <li><b>Eigen huisstijl per handelsnaam</b> — voeg bij Instellingen → Handelsnamen je handelsnamen toe, elk met een eigen logo, factuurkleur, sjabloon en voetnoot. Bij het maken van een factuur kies je simpelweg "Factureren als …".</li>
          <li><b>Overal consequent</b> — de PDF, de factuurmail (inclusief afzendernaam), de betalingsherinneringen en het klantenportaal volgen automatisch de huisstijl van de gekozen handelsnaam. Ook terugkerende facturen onthouden 'm.</li>
          <li><b>Eén administratie</b> — KvK, BTW-nummer, IBAN en je factuurnummering blijven gewoon één geheel; alleen de presentatie verschilt. Op de factuurpagina zie je altijd onder welke naam er is gefactureerd.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 19 augustus 2026 · {{ brand('version_prefix') }} 1.9.0</div>
        <h2>Urenregistratie — uren schrijven en met één klik factureren</h2>
        <ul class="tl-list">
          <li><b>Uren schrijven per klant of project</b> — snel invoeren met datum, omschrijving en duur (typ "1:30" of "1,5"), of start de ingebouwde <b>timer</b> en ga aan het werk.</li>
          <li><b>Met één klik factureren</b> — alle openstaande uren van een klant worden gebundeld op één conceptfactuur, met per regel de omschrijving, datum en het aantal uur. Controleren, versturen, klaar.</li>
          <li><b>Uurtarieven zoals jij wilt</b> — één standaardtarief bij je bedrijfsgegevens, een afwijkend tarief per klant, of een eigen tarief per urenregel.</li>
          <li>Je ziet direct hoeveel uur je deze week en maand schreef en welk bedrag er nog te factureren staat. Gefactureerde uren zijn vergrendeld en linken naar de factuur.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 19 augustus 2026 · {{ brand('version_prefix') }} 1.8.0</div>
        <h2>Bonnetjes automatisch herkennen (scan &amp; herken)</h2>
        <ul class="tl-list">
          <li><b>Foto maken, klaar</b> — maak bij het inboeken van een inkoopfactuur een foto van de bon (of upload een PDF) en klik op <b>Scan &amp; herken</b>. De AI leest leverancier, factuurnummer, datum en de bedragen per BTW-tarief en vult het formulier voor je in.</li>
          <li><b>Jij houdt de controle</b> — de bon staat groot naast het formulier, zodat je de ingevulde waarden in één oogopslag controleert. Klopt het totaal niet met de bon, dan krijg je automatisch een waarschuwing.</li>
          <li>Grote foto's worden vóór het versturen verkleind, zodat het scannen ook op mobiel vlot werkt.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 19 augustus 2026 · {{ brand('version_prefix') }} 1.7.0</div>
        <h2>Verzending via Peppol</h2>
        <ul class="tl-list">
          <li><b>Peppol-bereikbaarheid</b> — {{ brand('name') }} controleert automatisch (via de officiële Peppol Directory) of je klant is aangesloten op het Peppol-netwerk. Is dat zo, dan zie je een ⚡-badge op de factuur.</li>
          <li><b>E-factuur rechtstreeks afleveren</b> — met één klik lever je de UBL-factuur (NLCIUS) af in het boekhoudpakket van je klant, via een gecertificeerd Peppol Access Point. Geen mailbox ertussen, geen overtypen bij de ontvanger.</li>
          <li>Eigen Peppol-ID per klant instelbaar (standaard afgeleid van het KvK-nummer); afleveringen verschijnen in de historie van de factuur.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--success-bg);color:var(--success);border-color:#6EE7B7;">Verbetering</span> 19 augustus 2026 · {{ brand('version_prefix') }} 1.6.1</div>
        <h2>Sneller laden</h2>
        <ul class="tl-list">
          <li>Serveroptimalisaties (PHP-opcache en voorgecachte configuratie/templates) en een fors kleinere paginapayload — het bedrijfslogo wordt niet langer bij elke pagina meegestuurd.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 19 augustus 2026 · {{ brand('version_prefix') }} 1.6.0</div>
        <h2>Klanten toevoegen via het KvK-register</h2>
        <ul class="tl-list">
          <li><b>Zoek in het Handelsregister</b> — typ bij een nieuwe klant een bedrijfsnaam of KvK-nummer en kies het juiste bedrijf uit de lijst.</li>
          <li><b>Automatisch ingevuld</b> — bedrijfsnaam, KvK-nummer, adres, postcode en plaats staan direct goed, rechtstreeks uit de officiële KvK API. Geen typfouten meer in je klantgegevens.</li>
          <li>Werkt met de gratis KvK Zoeken API; het volledige vestigingsadres komt uit het Basisprofiel (optioneel).</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 19 augustus 2026 · {{ brand('version_prefix') }} 1.5.0</div>
        <h2>Bank &amp; transacties — afschriften importeren en koppelen</h2>
        <ul class="tl-list">
          <li><b>Bankafschriften importeren</b> — sleep je CAMT.053- of MT940-bestand (te downloaden bij elke Nederlandse bank) in het nieuwe Bank-menu. Dubbele transacties worden automatisch overgeslagen.</li>
          <li><b>Slimme koppel-suggesties</b> — {{ brand('name') }} herkent factuurnummer, bedrag en klantnaam in de omschrijving en stelt de juiste factuur voor. Eén klik en de betaling is geboekt.</li>
          <li><b>Ook voor inkoop</b> — afschrijvingen koppel je aan inkoopfacturen, die daarmee direct op betaald staan.</li>
          <li><b>Alles terug te draaien</b> — verwerkte transacties kun je ontkoppelen (de betaling wordt teruggedraaid) en genegeerde weer herstellen.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 19 augustus 2026 · {{ brand('version_prefix') }} 1.4.0</div>
        <h2>Slimmere factuurpagina, verrekeningen &amp; bijlagen voor de klant</h2>
        <ul class="tl-list">
          <li><b>Verrekening "reeds doorgestort"</b> — al doorgestorte deelbetalingen komen op de factuur-PDF in mindering op het te betalen bedrag, mét vermelding — zonder effect op je totaal, omzet of BTW. Ook achteraf toe te voegen.</li>
          <li><b>Afboeken zonder BTW-effect</b> — wikkel een factuur af bij een betalingsverschil of kwijtschelding, zonder creditnota en zonder dat je aangifte verandert.</li>
          <li><b>Factuurpagina vernieuwd</b> — dupliceren, PDF-voorvertoning naast de regels, facturen inplannen voor automatische verzending, een interne notitie (nooit zichtbaar voor de klant) en een volledige historie-tijdlijn.</li>
          <li><b>Bijlagen voor de klant</b> — voeg bestanden toe bij het opstellen; per bijlage kies je of hij met de factuurmail meegaat en in het klantenportaal staat. Downloads verschijnen in het inzagelog.</li>
          <li>Bugfix: de standaard-betalingstermijn uit je instellingen wordt nu echt gebruikt in het factuurformulier.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 18 augustus 2026 · {{ brand('version_prefix') }} 1.3.0</div>
        <h2>Klantenportaal, complete BTW-aangifte, inkoopfacturen &amp; teamrollen</h2>
        <ul class="tl-list">
          <li><b>Klantenportaal</b> — je klant bekijkt zijn facturen voortaan ook online, via een beveiligde link in de factuurmail (met eenmalige toegangscode per e-mail). Jij ziet in een inzagelog precies óf en wanneer je factuur is bekeken — inclusief een groen oogje in je facturenlijst.</li>
          <li><b>BTW-aangifte per kwartaal</b> — per kwartaal precies wat je invult bij de Belastingdienst: rubriek 1a, 1b en 1e, mét voorbelasting (5b) en het saldo dat je per kwartaal betaalt. Inclusief deadline-waarschuwing en PDF-download voor je administratie.</li>
          <li><b>Inkoopfacturen (crediteuren)</b> — boek binnengekomen facturen in, handmatig of met een foto van de bon (op je telefoon opent direct de camera). De BTW telt automatisch mee als voorbelasting in je aangifte, en je ziet altijd wat er bij leveranciers openstaat.</li>
          <li><b>Team &amp; rollen</b> — nodig collega's of je boekhouder gratis uit met een eigen rol: beheerder, medewerker of boekhouder (alleen inzien). Rechten worden ook server-side afgedwongen.</li>
          <li><b>Winst per maand op je dashboard</b> — de omzetgrafiek toont nu ook je inkoop en de winst (of het verlies) per maand, met vorig jaar als stippellijn erdoorheen zodat je de groei direct ziet.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 15 augustus 2026 · {{ brand('version_prefix') }} 1.2.0</div>
        <h2>Offertes — van voorstel naar factuur in één klik</h2>
        <ul class="tl-list">
          <li>Maak een offerte met dezelfde regels, producten en btw-berekening als een factuur.</li>
          <li>Versturen gaat per e-mail met een eigen offerte-PDF in je huisstijl, inclusief geldigheidsdatum.</li>
          <li>Houd bij wat de klant besloot: geaccepteerd, afgewezen of verlopen — offertes verlopen automatisch.</li>
          <li>Akkoord? Eén klik en de offerte wordt een concept-factuur.</li>
          <li>Daarnaast: een <strong>dagelijks overzicht</strong> in je mailbox, een knop om zelf een <strong>herinnering</strong> te sturen, en de mogelijkheid om <strong>prijzen inclusief btw</strong> in te voeren.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 15 augustus 2026 · {{ brand('version_prefix') }} 1.1.0</div>
        <h2>Terugkerende facturen, e-facturatie (UBL) &amp; export voor je boekhouder</h2>
        <ul class="tl-list">
          <li><b>Terugkerende facturen</b> — zet elke factuur met één klik om in een terugkerend profiel. {{ brand('name') }} factureert daarna automatisch per week, maand, kwartaal, half jaar of jaar: als concept om zelf te controleren, of direct verstuurd.</li>
          <li><b>UBL / e-facturatie</b> — elke verstuurde factuur bevat nu automatisch een UBL 2.1-bijlage (NLCIUS), die boekhoudpakketten direct kunnen inlezen. Ook los te downloaden op de factuurpagina.</li>
          <li><b>Export naar boekhouder</b> — download al je facturen als CSV met grondslag en BTW per tarief én controletotalen. Kies zelf de periode, bijvoorbeeld per kwartaal voor de BTW-aangifte.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 15 mei 2026 · {{ brand('version_prefix') }} 1.0.0</div>
        <h2>{{ brand('assistant') }}-assistent: je slimme hulp</h2>
        <ul class="tl-list">
          <li>Stel vragen over je administratie en krijg direct antwoord.</li>
          <li>Suggesties terwijl je een factuur opstelt.</li>
          <li>Snelkoppelingen naar veelgebruikte acties.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--success-bg);color:var(--success);border-color:#6EE7B7;">Verbetering</span> 2 april 2026 · {{ brand('name') }} 0.9</div>
        <h2>Incassotraject in fases</h2>
        <ul class="tl-list">
          <li>Gefaseerd traject: herinnering → aanmaning → incasso.</li>
          <li>Per fase de status en datum bijhouden.</li>
          <li>Overzichtspagina met alle lopende trajecten.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--brand-tint);color:var(--brand-darker);border-color:var(--brand-border);">Nieuw</span> 10 maart 2026 · {{ brand('name') }} 0.8</div>
        <h2>Creditfacturen &amp; deelbetalingen</h2>
        <ul class="tl-list">
          <li>Maak in één klik een creditnota op een bestaande factuur.</li>
          <li>Registreer deelbetalingen met automatische statusupdate.</li>
        </ul>
      </article>

      <article class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-meta"><span class="value-pill" style="background:var(--success-bg);color:var(--success);border-color:#6EE7B7;">Verbetering</span> 5 februari 2026 · {{ brand('name') }} 0.7</div>
        <h2>Nieuwe factuursjablonen</h2>
        <ul class="tl-list">
          <li>Modern sjabloon met meer ruimte voor je huisstijl.</li>
          <li>Eigen accentkleur instelbaar.</li>
        </ul>
      </article>
    </div>

    <div style="text-align:center;margin-top:40px;">
      <a href="{{ route('roadmap') }}" class="btn btn-secondary">Bekijk de roadmap →</a>
    </div>
  </div>
</section>
@endsection
