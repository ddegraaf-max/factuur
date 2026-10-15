<?php

/*
|--------------------------------------------------------------------------
| Factuurprogramma per beroep
|--------------------------------------------------------------------------
| Landingspagina's per vakgebied (/factuurprogramma-voor/{slug}), elk met
| eigen tekst over wat er in dát vak bij factureren komt kijken. Geen
| sjabloontekst met alleen een andere beroepsnaam: de blokken en vragen
| verschillen per pagina, anders ziet Google het als doorway-pagina's.
|
|   'name'     => meervoud, zoals in "factuurprogramma voor schilders"
|   'title'    => H1
|   'intro'    => inleiding (ook de meta-description)
|   'updated'  => datum laatste inhoudelijke wijziging (Y-m-d)
|   'blocks'   => lijst van [kop, alinea]
|   'faq'      => lijst van [vraag, antwoord] (FAQPage-structured data)
|   'related'  => kennisbank-slugs
|   'begrippen'=> begrippen-slugs
|   'tools'    => routenamen van gratis tools die erbij passen
*/

return [
    'professions' => [

        'zzp' => [
            'name' => 'zzp\'ers',
            'title' => 'Factuurprogramma voor zzp\'ers',
            'intro' => 'Als zzp\'er wil je factureren, betaald krijgen en je btw-aangifte doen zonder er een dagdeel per week aan kwijt te zijn. Zo pak je dat aan.',
            'updated' => '2026-10-15',
            'blocks' => [
                ['Je eerste factuur in vijf minuten', 'Bedrijfsgegevens, logo en btw-id vul je één keer in; daarna staan ze op elke factuur. Je kiest een klant, typt je regels en het programma rekent de btw per tarief uit, nummert doorlopend en zet de vervaldatum erbij. De factuur gaat als pdf en als e-factuur (UBL) naar de klant, met een betaallink erin.'],
                ['Uren bijhouden en in één keer factureren', 'Werk je per uur, dan registreer je je uren per klant of project en maak je aan het einde van de maand één factuur van alle niet-gefactureerde uren. De urenregistratie is meteen je bewijs voor het urencriterium van 1.225 uur, dat je nodig hebt voor de zelfstandigenaftrek.'],
                ['Btw-aangifte zonder rekenwerk', 'Elke verkoop- en inkoopfactuur staat in dezelfde administratie, dus de kwartaalaangifte staat klaar zodra het kwartaal om is: omzet per tarief, verlegde btw, voorbelasting en het te betalen bedrag. Doe je mee aan de kleineondernemersregeling, dan laat het programma de btw gewoon weg.'],
                ['Herinneringen die zichzelf versturen', 'Een paar dagen na de vervaldatum gaat automatisch een vriendelijke herinnering uit, daarna een aanmaning met de wettelijke rente en incassokosten erbij. Jij hoeft er niet achteraan te bellen en je klanten weten precies waar ze aan toe zijn.'],
            ],
            'faq' => [
                ['Heb ik als zzp\'er een factuurprogramma nodig of volstaat Word?', 'Met Word kun je een factuur maken, maar je houdt zelf de nummering, de btw-berekening en de openstaande posten bij. Een factuurprogramma doet dat voor je, bewaart alles zeven jaar en maakt je btw-aangifte in één overzicht. Bij meer dan een handvol facturen per maand verdient het zichzelf in tijd terug.'],
                ['Kan mijn boekhouder meekijken?', 'Ja, je geeft je boekhouder gratis een eigen inlog met leesrechten. Hij ziet alle facturen en exports, en krijgt desgewenst van elke factuur automatisch een kopie. Voor de jaarrekening exporteer je een auditfile (XAF).'],
                ['Wat kost het?', 'Basis kost 10 euro per maand exclusief btw, maandelijks opzegbaar, met onbeperkt facturen en klanten. Je begint met een proefperiode van veertien dagen zonder betaalgegevens.'],
            ],
            'related' => ['eerste-factuur-als-zzper', 'factuureisen', 'zelfstandigenaftrek-urencriterium-2026', 'btw-aangifte-kwartaal-stappenplan'],
            'begrippen' => ['zzp', 'urencriterium', 'kleineondernemersregeling', 'betalingstermijn'],
            'tools' => ['gratis-factuur', 'uurtarief-calculator', 'btw-calculator'],
        ],

        'schilders' => [
            'name' => 'schilders',
            'title' => 'Factuurprogramma voor schilders',
            'intro' => 'Schilderwerk aan woningen tegen 9 procent, materialen soms tegen 21, onderaanneming met btw verlegd: voor een schildersbedrijf moet een factuurprogramma de btw per regel goed doen.',
            'updated' => '2026-10-15',
            'blocks' => [
                ['9 procent of 21 procent, per regel', 'Schilderwerk aan een woning die ouder is dan twee jaar valt onder het verlaagde tarief van 9 procent; aan een nieuwbouwwoning of een bedrijfspand reken je 21 procent. Op één factuur kunnen beide voorkomen. Je kiest per regel het tarief en de factuur splitst de bedragen en de btw netjes uit, precies zoals de Belastingdienst het wil zien.'],
                ['Offerte met foto\'s en handtekening', 'Je maakt de offerte op locatie op je telefoon, met een omschrijving per onderdeel: buitenkozijnen, dakgoten, binnenwerk. De klant ondertekent digitaal, en zodra het werk klaar is maak je met één klik de factuur uit de offerte. Meerwerk zet je er als extra regel bij.'],
                ['Werken voor een aannemer: btw verlegd', 'Werk je als onderaannemer voor een aannemer of een ander schildersbedrijf, dan factureer je zonder btw en met de vermelding "btw verlegd" en het btw-nummer van je opdrachtgever. Eén vinkje op de factuur regelt dat, en de btw-aangifte neemt het automatisch mee.'],
                ['Betaald worden na oplevering', 'Een betaallink in de factuurmail zorgt dat particuliere klanten direct kunnen betalen. Blijft een betaling uit, dan volgen herinnering en aanmaning vanzelf. Voor grote klussen spreek je in de offerte een aanbetaling voor de verf en termijnen per fase af.'],
            ],
            'faq' => [
                ['Welk btw-tarief geldt voor schilderwerk?', 'Voor het schilderen van woningen ouder dan twee jaar geldt 9 procent, inclusief de verf die je daarbij gebruikt. Voor nieuwere woningen, bedrijfspanden en losse verkoop van materialen geldt 21 procent. Twijfel je over de leeftijd van een woning, dan laat je de klant dat schriftelijk bevestigen.'],
                ['Kan ik mijn klanten per project factureren?', 'Ja, je koppelt offertes, uren en facturen aan een project. Zo zie je per klus wat is geoffreerd, gefactureerd en betaald, en of de marge klopt.'],
                ['Hoe werkt een aanbetaling voor materiaal?', 'Je stuurt een voorschotfactuur met btw voor het afgesproken bedrag. Op de eindfactuur wordt die aanbetaling automatisch in mindering gebracht, zodat de klant alleen het restant betaalt.'],
            ],
            'related' => ['btw-tarieven', 'btw-verlegd-bouw-onderaanneming', 'voorschotfactuur-aanbetaling', 'eindfactuur'],
            'begrippen' => ['btw-tarieven', 'btw-verlegd', 'aanbetaling', 'offerte'],
            'tools' => ['gratis-factuur', 'btw-calculator'],
        ],

        'timmerlieden' => [
            'name' => 'timmerlieden',
            'title' => 'Factuurprogramma voor timmerlieden',
            'intro' => 'Van kozijn tot complete verbouwing: een timmerbedrijf factureert materiaal en uren, werkt vaak in termijnen en regelmatig als onderaannemer. Daar hoort een factuurprogramma bij dat de bouw kent.',
            'updated' => '2026-10-15',
            'blocks' => [
                ['Materiaal en uren op één factuur', 'Je zet hout, beslag en bevestigingsmateriaal als regels met aantal en stuksprijs op de factuur, en je uren als aparte regels met je uurtarief. Vaste producten sla je op met prijs en btw-tarief, zodat je ze de volgende keer in één keer selecteert.'],
                ['Termijnen bij een verbouwing', 'Bij een dakkapel of een uitbouw spreek je in de offerte termijnen af: bij opdracht, bij start en bij oplevering. Elke termijn is een gewone factuur; de eindfactuur verrekent automatisch wat al is gefactureerd. Zo blijft de cashflow op peil terwijl het werk loopt.'],
                ['Onderaanneming en G-rekening', 'Werk je voor een hoofdaannemer, dan factureer je met btw verlegd en vermeld je het btw-nummer van de aannemer. Wil de aannemer een deel op je G-rekening storten, dan zet je dat bedrag en het rekeningnummer op de factuur. Het programma onthoudt per klant hoe het moet.'],
                ['Prijsaanvragen bij onderaannemers', 'Heb je zelf een stukadoor of installateur nodig voor een klus, dan stuur je vanuit het project een prijsaanvraag naar meerdere bedrijven tegelijk en vergelijk je de offertes naast elkaar. De gegunde prijs landt als verplichting in je projectcalculatie.'],
            ],
            'faq' => [
                ['Mag ik 9 procent rekenen voor timmerwerk?', 'Nee, timmerwerk valt onder het algemene tarief van 21 procent. Het verlaagde tarief geldt alleen voor specifieke werkzaamheden aan woningen ouder dan twee jaar, zoals schilderen, stukadoren en isoleren (alleen arbeid). Check de lijst van de Belastingdienst bij twijfel.'],
                ['Hoe factureer ik meerwerk?', 'Meerwerk zet je als extra regels op de termijn- of eindfactuur, met een duidelijke omschrijving en een verwijzing naar de afspraak. Laat meerwerk vooraf per e-mail of in de app bevestigen, dan is er bij de factuur geen discussie.'],
                ['Kan ik mijn offerte op de bouwplaats maken?', 'Ja, de app werkt op je telefoon. Je maakt de offerte ter plekke, de klant ondertekent op het scherm en de bevestiging gaat direct naar jullie beiden.'],
            ],
            'related' => ['btw-verlegd-bouw-onderaanneming', 'deelfactuur-termijnfactuur', 'eindfactuur'],
            'begrippen' => ['termijnfactuur', 'btw-verlegd', 'g-rekening', 'offerte'],
            'tools' => ['gratis-factuur', 'uurtarief-calculator'],
        ],

        'elektriciens' => [
            'name' => 'elektriciens',
            'title' => 'Factuurprogramma voor elektriciens',
            'intro' => 'Storingen, groepenkasten, laadpalen en zonnepanelen: een installatiebedrijf voor elektra factureert kleine en grote klussen door elkaar. Snel factureren vanaf de bus is dan het verschil.',
            'updated' => '2026-10-15',
            'blocks' => [
                ['Factureren vanaf de bus', 'Na een storing of een kleine klus maak je de factuur op je telefoon voordat je wegrijdt: voorrijkosten, uren en materiaal uit je productenlijst. De klant heeft de factuur met betaallink in zijn mail voordat jij bij de volgende klant bent, en betaalt vaak dezelfde dag.'],
                ['Materiaal uit de groothandel doorberekenen', 'Inkoopfacturen van de groothandel lees je in met de bonherkenning; de regels kun je met je opslag doorzetten naar de factuur van de klant. Zo vergeet je geen materiaal en klopt je marge per klus.'],
                ['Zonnepanelen: 0 procent of 21 procent', 'Op de levering en installatie van zonnepanelen op of bij een woning geldt het nultarief. Voor bedrijfspanden en voor andere installaties reken je 21 procent. Per regel kies je het tarief; de factuur en de btw-aangifte volgen automatisch.'],
                ['Onderhoudscontracten en keuringen', 'Jaarlijkse keuringen en onderhoud factureer je als terugkerende factuur: één keer instellen, en elke periode gaat hij vanzelf uit, desgewenst met automatische incasso. Je ziet in één overzicht welke contracten lopen en wat ze opleveren.'],
            ],
            'faq' => [
                ['Welk btw-tarief geldt voor zonnepanelen?', 'Sinds 2023 geldt het nultarief voor de levering en installatie van zonnepanelen op of in de onmiddellijke nabijheid van woningen. Voor zonnepanelen op bedrijfsgebouwen en voor losse onderdelen zonder installatie geldt 21 procent.'],
                ['Kan ik voorrijkosten als vaste regel opnemen?', 'Ja, je slaat voorrijkosten op als product met een vaste prijs en voegt het met één klik toe aan de factuur. Hetzelfde geldt voor je uurtarief en veelgebruikte materialen.'],
                ['Werk ik als onderaannemer ook met btw verlegd?', 'Ja, bij onderaanneming in de bouw, bijvoorbeeld de elektra in een nieuwbouwproject voor een aannemer, factureer je met btw verlegd. Voor particuliere klanten en bij rechtstreekse opdrachten van bedrijven buiten de bouwketen reken je gewoon btw.'],
            ],
            'related' => ['btw-tarieven', 'btw-verlegd-bouw-onderaanneming', 'terugkerende-facturen-abonnementen'],
            'begrippen' => ['btw-tarieven', 'sepa-incasso', 'ideal-betaallink', 'btw-verlegd'],
            'tools' => ['gratis-factuur', 'btw-calculator'],
        ],

        'installateurs' => [
            'name' => 'installateurs',
            'title' => 'Factuurprogramma voor installateurs en loodgieters',
            'intro' => 'Cv-ketels, badkamers, warmtepompen en lekkages: een installatiebedrijf werkt met offertes, termijnen, onderhoudscontracten en spoedklussen. Een factuurprogramma moet dat allemaal aankunnen.',
            'updated' => '2026-10-15',
            'blocks' => [
                ['Offerte voor een badkamer of warmtepomp', 'Grotere opdrachten beginnen met een offerte per onderdeel: sanitair, leidingwerk, tegelwerk, arbeid. De klant ondertekent digitaal; de offerte wordt een project met termijnen en de facturen volgen de voortgang. Subsidie op een warmtepomp vraagt de klant zelf aan, maar hij heeft daarvoor jouw factuur met een duidelijke omschrijving nodig.'],
                ['Spoedklussen direct afrekenen', 'Een lekkage op zondagavond factureer je ter plekke met een toeslag als aparte regel, en de klant betaalt via de betaallink. Zo voorkom je dat kleine spoedklussen weken openstaan.'],
                ['Onderhoudscontracten met incasso', 'Jaarlijks ketelonderhoud zet je als terugkerende factuur klaar, met automatische incasso via een machtiging van de klant. Het bedrag komt op de afgesproken dag binnen en jij ziet in één lijst welke contracten deze maand aan de beurt zijn.'],
                ['Btw: 9, 21 of verlegd', 'Isolatiewerk aan woningen ouder dan twee jaar valt voor de arbeid onder 9 procent, de materialen onder 21. Werk je voor een aannemer, dan is de btw verlegd. Per regel kies je het tarief en het programma bewaakt dat de factuur klopt.'],
            ],
            'faq' => [
                ['Welk btw-tarief geldt voor isolatiewerk?', 'Voor het aanbrengen van isolatiemateriaal aan woningen ouder dan twee jaar geldt 9 procent over de arbeid; de materialen vallen onder 21 procent. Splits arbeid en materiaal daarom als aparte regels op de factuur.'],
                ['Kan ik een onderhoudscontract automatisch factureren?', 'Ja, je stelt de factuur één keer in met een frequentie, bijvoorbeeld jaarlijks in maart. Daarna gaat hij automatisch uit, eventueel met SEPA-incasso op basis van een machtiging van de klant.'],
                ['Hoe regel ik een aanbetaling voor een warmtepomp?', 'Je stuurt een voorschotfactuur voor het afgesproken percentage, bijvoorbeeld dertig procent bij opdracht. De eindfactuur na installatie verrekent de aanbetaling automatisch.'],
            ],
            'related' => ['btw-tarieven', 'voorschotfactuur-aanbetaling', 'terugkerende-facturen-abonnementen', 'btw-verlegd-bouw-onderaanneming'],
            'begrippen' => ['sepa-incasso', 'aanbetaling', 'termijnfactuur', 'btw-verlegd'],
            'tools' => ['gratis-factuur', 'btw-calculator'],
        ],

        'stukadoors' => [
            'name' => 'stukadoors',
            'title' => 'Factuurprogramma voor stukadoors',
            'intro' => 'Stukadoren van woningen ouder dan twee jaar tegen 9 procent, werk voor aannemers met btw verlegd en offertes per vierkante meter: zo factureert een stukadoorsbedrijf zonder gedoe.',
            'updated' => '2026-10-15',
            'blocks' => [
                ['Per vierkante meter of per uur', 'Je offreert en factureert in de eenheid die bij de klus past: vierkante meters voor wanden en plafonds, uren voor reparaties en sierpleister. Eenheden en prijzen sla je op als producten, dus een offerte voor een woning is in een paar minuten klaar.'],
                ['9 procent voor bestaande woningen', 'Stukadoorswerk aan woningen die ouder zijn dan twee jaar valt onder het verlaagde tarief van 9 procent. Voor nieuwbouw en bedrijfspanden reken je 21 procent. Het tarief kies je per regel; de factuur vermeldt automatisch de juiste uitsplitsing.'],
                ['Als onderaannemer voor de aannemer', 'Het meeste werk in nieuwbouw loopt via een aannemer. Dan factureer je met btw verlegd, met het btw-nummer van de aannemer op de factuur, en stort de aannemer eventueel een deel op je G-rekening. Per klant leg je dat één keer vast.'],
                ['Oplevering en betaling', 'Na de oplevering stuur je de factuur met een betaallink; bij particulieren wordt die vaak nog dezelfde week betaald. Voor grotere klussen spreek je een aanbetaling en een termijn bij de helft van het werk af.'],
            ],
            'faq' => [
                ['Geldt 9 procent ook voor het materiaal?', 'Ja, bij stukadoren van woningen ouder dan twee jaar geldt het verlaagde tarief voor de hele dienst, inclusief het materiaal dat je daarbij verwerkt. Materiaal dat je los verkoopt, valt onder 21 procent.'],
                ['Hoe bewijs ik dat een woning ouder is dan twee jaar?', 'Vraag de klant om een verklaring, bijvoorbeeld een regel in de offerte die hij ondertekent, of noteer de bouwdatum uit het Kadaster of de WOZ-beschikking bij de klant.'],
                ['Kan ik een prijsaanvraag van een aannemer beantwoorden met een offerte?', 'Ja, je maakt een offerte op naam van de aannemer met btw verlegd. Wordt die gegund, dan factureer je in termijnen vanuit dezelfde offerte.'],
            ],
            'related' => ['btw-tarieven', 'btw-verlegd-bouw-onderaanneming', 'deelfactuur-termijnfactuur'],
            'begrippen' => ['btw-tarieven', 'btw-verlegd', 'g-rekening', 'aanbetaling'],
            'tools' => ['gratis-factuur', 'btw-calculator'],
        ],

        'hoveniers' => [
            'name' => 'hoveniers',
            'title' => 'Factuurprogramma voor hoveniers',
            'intro' => 'Tuinaanleg in fases, onderhoudsabonnementen per seizoen en planten tegen 9 procent naast arbeid tegen 21: een hoveniersbedrijf heeft een factuurprogramma nodig dat met gemengde btw en terugkerend werk overweg kan.',
            'updated' => '2026-10-15',
            'blocks' => [
                ['Planten 9 procent, aanleg 21 procent', 'Bomen, heesters en planten vallen onder het verlaagde tarief van 9 procent; het ontwerp, de aanleg en de bestrating onder 21 procent. Op één factuur staan dus vaak beide tarieven. Je kiest ze per regel en de factuur splitst de btw automatisch uit.'],
                ['Tuinaanleg in termijnen', 'Een complete tuin leg je aan in fases: grondwerk, bestrating, beplanting. In de offerte spreek je termijnen af die je na elke fase factureert. Materiaal dat je vooraf inkoopt, dek je met een aanbetaling. De eindfactuur verrekent alles automatisch.'],
                ['Onderhoud per seizoen, automatisch gefactureerd', 'Voor vast tuinonderhoud zet je een terugkerende factuur klaar per maand of per seizoen, desgewenst met automatische incasso. Je ziet per klant wat er loopt en hoeft nooit meer te denken aan wie er deze maand gefactureerd moet worden.'],
                ['Uren en machines doorberekenen', 'Naast je uren bereken je het gebruik van een minigraver of een versnipperaar per dag door. Je slaat die als producten op met een dagprijs, en de factuur telt alles op.'],
            ],
            'faq' => [
                ['Welk btw-tarief geldt voor tuinonderhoud?', 'Tuinonderhoud en tuinaanleg vallen onder 21 procent. Alleen de levering van planten, bollen en bomen valt onder 9 procent; het planten zelf is arbeid tegen 21 procent.'],
                ['Kan ik een offerte met een tekening meesturen?', 'Ja, je voegt een ontwerp of beplantingsplan als bijlage toe. De klant ziet de offerte en de bijlagen op één pagina en ondertekent digitaal.'],
                ['Hoe regel ik een onderhoudsabonnement?', 'Je maakt een terugkerende factuur met de gewenste frequentie en koppelt eventueel een SEPA-machtiging. Pas je de prijs aan, dan geldt die vanaf de volgende factuur.'],
            ],
            'related' => ['btw-tarieven', 'terugkerende-facturen-abonnementen', 'deelfactuur-termijnfactuur'],
            'begrippen' => ['btw-tarieven', 'termijnfactuur', 'sepa-incasso', 'aanbetaling'],
            'tools' => ['gratis-factuur', 'btw-calculator'],
        ],

        'klusbedrijven' => [
            'name' => 'klusbedrijven',
            'title' => 'Factuurprogramma voor klusbedrijven',
            'intro' => 'Een klusbedrijf doet alles: van een deur afhangen tot een complete badkamer. Veel kleine facturen, af en toe een grote, en altijd materiaal dat doorberekend moet worden. Zo houd je dat bij.',
            'updated' => '2026-10-15',
            'blocks' => [
                ['Kleine klussen direct factureren', 'Na een klus van een paar uur maak je de factuur op je telefoon: uren, voorrijkosten en het materiaal dat je bij de bouwmarkt haalde. De klant krijgt hem met een betaallink in zijn mail. Hoe sneller de factuur, hoe sneller het geld.'],
                ['Bonnetjes van de bouwmarkt inlezen', 'Maak een foto van de bon en de bonherkenning zet leverancier, bedrag en btw klaar als inkoopfactuur. Het materiaal zet je met je opslag door naar de klantfactuur; de btw op de bon is je voorbelasting voor de aangifte.'],
                ['Grote klussen met offerte en termijnen', 'Een badkamer of een zolderverbouwing begint met een offerte die de klant digitaal ondertekent. Je spreekt een aanbetaling en termijnen af en factureert per fase. Meerwerk zet je als extra regel op de eindfactuur.'],
                ['Hulp inhuren voor een klus', 'Heb je een stukadoor of elektricien nodig, dan vraag je vanuit het project bij meerdere bedrijven een prijs op en gun je de beste. Die kosten zie je direct terug in je projectcalculatie, zodat je weet of de klus nog rendabel is.'],
            ],
            'faq' => [
                ['Welk btw-tarief gebruik ik als klusbedrijf?', 'Meestal 21 procent. Alleen schilderen, stukadoren, behangen en isoleren (arbeid) aan woningen ouder dan twee jaar vallen onder 9 procent. Doe je zo\'n klus, dan zet je die regels op 9 en de rest op 21.'],
                ['Kan ik mijn uurtarief en voorrijkosten vast instellen?', 'Ja, je slaat ze op als producten met een vaste prijs. Op de factuur voeg je ze met één klik toe; je past alleen het aantal uren aan.'],
                ['Wat als een klant niet betaalt?', 'Het programma stuurt automatisch herinneringen en daarna een aanmaning met rente en incassokosten. Helpt dat niet, dan draag je de vordering met één klik over aan incasso, met het complete dossier erbij.'],
            ],
            'related' => ['btw-tarieven', 'btw-terugvragen-voorbelasting', 'betalingstermijn-aanmanen', 'voorschotfactuur-aanbetaling'],
            'begrippen' => ['voorbelasting', 'aanbetaling', 'aanmaning', 'vereenvoudigde-factuur'],
            'tools' => ['gratis-factuur', 'uurtarief-calculator', 'incassokosten-calculator'],
        ],

        'dakdekkers' => [
            'name' => 'dakdekkers',
            'title' => 'Factuurprogramma voor dakdekkers',
            'intro' => 'Dakwerk is seizoenswerk met grote bedragen, veel materiaal en vaak een aannemer als opdrachtgever. Een factuurprogramma voor dakdekkers moet termijnen, btw verlegd en G-rekening vanzelfsprekend maken.',
            'updated' => '2026-10-15',
            'blocks' => [
                ['Bitumen, EPDM en pannen als vaste producten', 'Je slaat dakbedekking, isolatie en bevestigingsmateriaal op als producten per vierkante meter of per rol. Een offerte voor een plat dak van tachtig vierkante meter maak je dan in een paar regels, inclusief het verwijderen en afvoeren van de oude bedekking.'],
                ['Aanbetaling voor materiaal', 'Bij een dak van enkele duizenden euro\'s vraag je een aanbetaling voordat je materiaal bestelt. De voorschotfactuur gaat met een betaallink naar de klant en de eindfactuur na oplevering verrekent de aanbetaling automatisch.'],
                ['Werken voor aannemers', 'Nieuwbouw en renovatie lopen vaak via een aannemer: dan factureer je met btw verlegd en stort de aannemer een deel op je G-rekening. Je legt per klant vast dat de btw verlegd is en wat het G-rekeningdeel is; het staat voortaan automatisch goed op de factuur.'],
                ['Garantie en onderhoud', 'Jaarlijkse dakinspecties factureer je als terugkerende factuur. De garantieverklaring stuur je als bijlage mee met de eindfactuur, zodat de klant alles op één plek heeft.'],
            ],
            'faq' => [
                ['Geldt 9 procent voor dakisolatie?', 'Voor het aanbrengen van isolatiemateriaal aan woningen ouder dan twee jaar geldt 9 procent over de arbeid. Het isolatiemateriaal zelf en de dakbedekking vallen onder 21 procent. Zet arbeid en materiaal daarom op aparte regels.'],
                ['Hoe vermeld ik de G-rekening op de factuur?', 'Je zet het bedrag of percentage dat op de G-rekening moet, met het G-rekeningnummer, als tekst op de factuur. Het programma onthoudt die tekst per klant.'],
                ['Kan ik foto\'s van het dak bij de offerte voegen?', 'Ja, foto\'s en een tekening voeg je als bijlage toe aan de offerte. De klant ziet ze bij het ondertekenen en ze blijven bij het project bewaard.'],
            ],
            'related' => ['btw-verlegd-bouw-onderaanneming', 'voorschotfactuur-aanbetaling', 'btw-tarieven'],
            'begrippen' => ['g-rekening', 'btw-verlegd', 'aanbetaling', 'termijnfactuur'],
            'tools' => ['gratis-factuur', 'btw-calculator'],
        ],

        'fotografen' => [
            'name' => 'fotografen',
            'title' => 'Factuurprogramma voor fotografen',
            'intro' => 'Shoots, licenties, reiskosten en nabewerking: een fotograaf factureert meer dan alleen uren. Zo zet je pakketten, aanbetalingen en gebruiksrechten netjes op de factuur.',
            'updated' => '2026-10-15',
            'blocks' => [
                ['Pakketten en losse onderdelen', 'Een bruidsreportage, een bedrijfsshoot of een productserie sla je op als pakket met een vaste prijs, en reiskosten, extra uren en afdrukken als losse producten. De offerte is dan in een minuut klaar en de klant ziet precies wat erbij zit.'],
                ['Aanbetaling bij boeking', 'Voor bruiloften en evenementen vraag je bij de boeking een aanbetaling. Die factureer je als voorschotfactuur met btw; de eindfactuur na de shoot verrekent hem automatisch. Zo is de datum vastgelegd en loop je minder risico bij een annulering.'],
                ['Licenties en gebruiksrechten', 'Lever je beeld aan bedrijven, dan zet je het gebruiksrecht als aparte regel op de factuur: waarvoor, hoe lang en in welk gebied. Dat maakt het verschil tussen de shoot en de licentie duidelijk en voorkomt discussie over hergebruik.'],
                ['Werken voor overheid en grote bedrijven', 'Gemeenten en grote opdrachtgevers vragen steeds vaker een e-factuur via Peppol. Je verstuurt dezelfde factuur, het programma zorgt dat hij in het juiste formaat aankomt, en je hoeft geen portaal van de klant meer in.'],
            ],
            'faq' => [
                ['Welk btw-tarief geldt voor fotografie?', 'Fotografie valt in de regel onder 21 procent. Alleen de verkoop van een door jou gemaakte, door jou gesigneerde en genummerde afdruk in een beperkte oplage kan als kunstvoorwerp onder 9 procent vallen; de shoot en de digitale bestanden niet.'],
                ['Kan ik mijn klant online laten betalen?', 'Ja, elke factuur heeft een betaallink voor iDEAL. De aanbetaling bij een boeking is zo binnen een dag binnen en de factuur staat automatisch op betaald.'],
                ['Hoe lever ik een factuur aan een gemeente?', 'Via Peppol, rechtstreeks vanuit het programma. De gemeente ontvangt een e-factuur; jij krijgt een bevestiging van aflevering.'],
            ],
            'related' => ['voorschotfactuur-aanbetaling', 'factuureisen', 'factuur-app-kiezen'],
            'begrippen' => ['aanbetaling', 'peppol', 'ideal-betaallink', 'offerte'],
            'tools' => ['gratis-factuur', 'uurtarief-calculator'],
        ],

        'webdesigners' => [
            'name' => 'webdesigners en developers',
            'title' => 'Factuurprogramma voor webdesigners en developers',
            'intro' => 'Projecten in fases, hosting en onderhoud per maand, uren voor wijzigingen en klanten in het buitenland: een webbureau of freelance developer factureert op veel manieren tegelijk.',
            'updated' => '2026-10-15',
            'blocks' => [
                ['Projecten in fases, uren apart', 'Een website offreer je in fases: ontwerp, bouw, oplevering. Elke fase factureer je als termijn. Wijzigingen na oplevering schrijf je als uren, die je per maand in één factuur bundelt. Zo blijft helder wat vaste prijs was en wat meerwerk.'],
                ['Hosting en onderhoud als abonnement', 'Hosting, onderhoud en licenties zet je als terugkerende factuur klaar, per maand of per jaar, met automatische incasso. Prijswijzigingen pas je op één plek aan en gelden vanaf de volgende factuur.'],
                ['Klanten in de EU en daarbuiten', 'Werk je voor een bedrijf in België of Duitsland, dan factureer je zonder btw met de vermelding btw verlegd en het btw-nummer van de klant; de levering meld je in de ICP-opgave. Voor klanten buiten de EU reken je ook geen btw. Het programma controleert het btw-nummer en zet de juiste tekst op de factuur.'],
                ['Factureren vanuit je werk', 'Via de Claude-koppeling maak je een concept-offerte of -factuur direct vanuit een gesprek: je beschrijft de opdracht, de regels staan klaar in je administratie en jij controleert en verstuurt. Een pdf met het plan van aanpak hangt er als bijlage bij.'],
            ],
            'faq' => [
                ['Hoe factureer ik een klant in een ander EU-land?', 'Zonder btw, met de tekst "btw verlegd" en het btw-identificatienummer van de klant op de factuur. Controleer het nummer vooraf via VIES en geef de omzet op in je ICP-opgave. Voor particuliere klanten in de EU gelden andere regels.'],
                ['Kan ik maandelijks hosting factureren met incasso?', 'Ja, je maakt een terugkerende factuur met een SEPA-machtiging van de klant. Het bedrag wordt op de afgesproken dag afgeschreven en de factuur staat automatisch op betaald.'],
                ['Kan ik uren koppelen aan een project?', 'Ja, je schrijft uren op een project en klant, met een omschrijving per regel. Aan het eind van de maand zet je alle open uren met één klik op een factuur.'],
            ],
            'related' => ['factureren-buitenland-btw-verlegd', 'terugkerende-facturen-abonnementen', 'deelfactuur-termijnfactuur'],
            'begrippen' => ['icp-opgave', 'btw-verlegd', 'sepa-incasso', 'termijnfactuur'],
            'tools' => ['gratis-factuur', 'uurtarief-calculator'],
        ],

        'consultants' => [
            'name' => 'consultants en adviseurs',
            'title' => 'Factuurprogramma voor consultants en adviseurs',
            'intro' => 'Dagtarieven, retainers, reiskosten en opdrachtgevers die een inkoopordernummer eisen: als consultant of interim-professional factureer je aan grote organisaties. Dat vraagt precisie en snelheid.',
            'updated' => '2026-10-15',
            'blocks' => [
                ['Uren en dagen per opdracht', 'Je registreert uren of dagdelen per opdrachtgever en project en factureert maandelijks op basis van die registratie, met de urenspecificatie als bijlage. Grote opdrachtgevers willen die specificatie zien; met een pdf uit dezelfde administratie klopt hij altijd met de factuur.'],
                ['Inkoopordernummers en referenties', 'Veel organisaties betalen alleen een factuur met hun inkoopordernummer erop. Je zet dat nummer in het referentieveld, en het staat prominent op de factuur en in de onderwerpregel van de mail. Per klant onthoudt het programma de vaste referentie.'],
                ['E-facturen voor overheid en corporates', 'Rijksoverheid, provincies en gemeenten accepteren alleen e-facturen via Peppol, en grote bedrijven volgen. Je verstuurt je factuur zoals altijd; via de Peppol-koppeling komt hij als e-factuur binnen in hun systeem, met een afleverbevestiging voor jou.'],
                ['Betaald binnen dertig dagen', 'Grote ondernemingen moeten mkb-leveranciers binnen dertig dagen betalen. Zet die termijn als vervaldatum op de factuur en laat de herinnering automatisch gaan als hij verstrijkt. Een aanmaning met de wettelijke handelsrente erbij werkt bij een crediteurenafdeling vaak sneller dan een telefoontje.'],
            ],
            'faq' => [
                ['Mag een grote opdrachtgever een betalingstermijn van zestig dagen eisen?', 'Nee. Sinds 2022 mogen grote ondernemingen aan mkb-leveranciers geen langere betalingstermijn dan dertig dagen opleggen. Een langere termijn in hun inkoopvoorwaarden is nietig; dan geldt dertig dagen.'],
                ['Kan ik een urenspecificatie meesturen?', 'Ja, bij facturen uit de urenregistratie voeg je met één klik een specificatie per dag of per week toe als bijlage. Die gaat mee met de factuurmail en staat in het klantenportaal.'],
                ['Hoe lever ik aan via Peppol?', 'Je registreert je administratie één keer op het Peppol-netwerk vanuit het programma. Daarna herkent het programma welke klanten via Peppol bereikbaar zijn en verstuurt het de factuur automatisch als e-factuur.'],
            ],
            'related' => ['betalingstermijn-aanmanen', 'factuureisen', 'factuur-app-kiezen'],
            'begrippen' => ['betalingstermijn', 'peppol', 'wettelijke-rente', 'e-factuur'],
            'tools' => ['uurtarief-calculator', 'incassokosten-calculator'],
        ],
    ],
];
