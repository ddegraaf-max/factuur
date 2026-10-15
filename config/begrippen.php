<?php

/*
|--------------------------------------------------------------------------
| Begrippenlijst
|--------------------------------------------------------------------------
| Korte, publieke uitleg van termen rond factureren, btw en betaald krijgen
| (/begrippen/{slug}). Elke term is een eigen pagina met een eigen
| deelsitemap-regel; de lijst zelf staat op /begrippen.
|
|   'term'     => de term zoals getoond (kop en titel)
|   'short'    => definitie in één of twee zinnen (ook de meta-description)
|   'updated'  => datum laatste inhoudelijke wijziging (Y-m-d); wordt lastmod in de sitemap
|   'sections' => lijst van [kop, [alinea's...]]
|   'related'  => kennisbank-slugs die erbij horen (config/kennisbank.php)
|   'see'      => andere begrippen-slugs
|   'tool'     => optioneel ['route' => routenaam, 'label' => knoptekst]
|
| Bedragen en regels veranderen: check bij twijfel de Belastingdienst.
*/

return [
    'terms' => [

        'factuur' => [
            'term' => 'Factuur',
            'short' => 'Een factuur is de rekening die je naar je klant stuurt voor geleverde producten of diensten, met alle gegevens die de Belastingdienst voorschrijft.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Wat is een factuur?', [
                    'Een factuur is het document waarmee je een klant laat weten wat hij je verschuldigd is en waarvoor. Voor de Belastingdienst is het bovendien het bewijsstuk van je omzet en van de btw die je in rekening brengt. Daarom gelden er vaste eisen: je naam en adres, die van je klant, een uniek en opeenvolgend factuurnummer, de factuurdatum, een omschrijving van wat je leverde, de bedragen exclusief btw, het btw-tarief en het btw-bedrag, en je KvK- en btw-identificatienummer.',
                    'Een factuur is pas geldig als hij aan die eisen voldoet. Mist er iets, dan kan je klant de btw niet aftrekken en krijg jij bij een controle vragen. Een factuur mag je per e-mail (als pdf) of digitaal als e-factuur versturen; een papieren versie is niet verplicht.',
                ]],
                ['Wanneer moet je factureren?', [
                    'Lever je aan een andere ondernemer, dan moet de factuur uiterlijk op de vijftiende dag van de maand na de levering de deur uit. Voor particuliere klanten geldt in de meeste gevallen geen factuurplicht, maar een factuur sturen mag natuurlijk altijd en is voor je eigen administratie verstandig.',
                    'Een factuur bewaar je zeven jaar, zowel de verstuurde als de ontvangen exemplaren. Voor facturen die met onroerend goed te maken hebben geldt tien jaar.',
                ]],
            ],
            'related' => ['factuureisen', 'eerste-factuur-als-zzper', 'factuurnummering'],
            'see' => ['factuurnummer', 'vereenvoudigde-factuur', 'e-factuur'],
            'tool' => ['route' => 'gratis-factuur', 'label' => 'Gratis een factuur maken'],
        ],

        'factuurnummer' => [
            'term' => 'Factuurnummer',
            'short' => 'Het factuurnummer is het unieke, opeenvolgende nummer dat elke factuur krijgt. Gaten in de reeks zijn niet toegestaan.',
            'updated' => '2026-10-15',
            'sections' => [
                ['De regels voor factuurnummers', [
                    'De Belastingdienst eist dat factuurnummers uniek en doorlopend zijn: elke factuur krijgt een nummer dat precies één hoger is dan de vorige. Een nummer overslaan mag niet, en hetzelfde nummer twee keer gebruiken al helemaal niet. Je mag wel met meerdere reeksen werken, bijvoorbeeld een reeks per boekjaar of per handelsnaam, zolang elke reeks op zichzelf doorlopend is.',
                    'Een veelgebruikte opbouw is het jaartal gevolgd door een volgnummer, zoals 2026-0001. Het jaartal maakt het makkelijk om per boekjaar te zoeken en de reeks start elk jaar opnieuw. Voorloopnullen zijn niet verplicht, maar sorteren wel prettiger.',
                ]],
                ['Wat als je een fout maakt?', [
                    'Een verstuurde factuur verwijder je nooit. Klopt er iets niet, dan maak je een creditnota die de factuur (deels) tegenboekt, en stuur je daarna een nieuwe factuur met een nieuw nummer. Zo blijft de reeks doorlopend en kan de Belastingdienst elke stap volgen.',
                    'Concepten tellen niet mee: een factuurprogramma kent het nummer pas toe op het moment van versturen, zodat een weggegooid concept geen gat in de reeks achterlaat.',
                ]],
            ],
            'related' => ['factuurnummering', 'creditnota-maken'],
            'see' => ['factuur', 'creditnota'],
        ],

        'btw-identificatienummer' => [
            'term' => 'Btw-identificatienummer (btw-id)',
            'short' => 'Het btw-identificatienummer is het nummer waarmee de Belastingdienst jou als btw-ondernemer kent. Het staat verplicht op elke factuur.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Btw-id en omzetbelastingnummer', [
                    'Sinds 2020 hebben eenmanszaken twee nummers: een btw-identificatienummer (btw-id) dat je op facturen en je website zet, en een omzetbelastingnummer dat alleen voor het contact met de Belastingdienst dient. Het btw-id begint met NL, heeft negen cijfers, de letter B en nog twee cijfers, en bevat niet langer je burgerservicenummer. Voor een bv of vof is er maar één nummer, dat als btw-id fungeert.',
                    'Op een factuur hoort altijd het btw-id, nooit het omzetbelastingnummer. Lever je aan een klant in een ander EU-land en verleg je de btw, dan staat ook het btw-id van die klant op de factuur. Controleer dat nummer vooraf via het VIES-systeem van de Europese Commissie.',
                ]],
                ['Wanneer heb je geen btw-id nodig?', [
                    'Doe je mee aan de kleineondernemersregeling, dan ben je vrijgesteld van btw en breng je geen btw in rekening. Je hebt dan wel een btw-id, maar vermeldt op je factuur dat de vrijstelling geldt in plaats van een btw-bedrag.',
                ]],
            ],
            'related' => ['factuureisen', 'factureren-buitenland-btw-verlegd', 'kleineondernemersregeling-kor'],
            'see' => ['kvk-nummer', 'btw-verlegd', 'kleineondernemersregeling'],
        ],

        'kvk-nummer' => [
            'term' => 'KvK-nummer',
            'short' => 'Het KvK-nummer is het achtcijferige nummer van je inschrijving in het Handelsregister van de Kamer van Koophandel en hoort op elke factuur.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Waarom het op je factuur staat', [
                    'Elke onderneming in Nederland staat ingeschreven in het Handelsregister en krijgt daarbij een KvK-nummer van acht cijfers. Op je factuur is het nummer verplicht, net als op je website en in je e-mails, zodat klanten en de Belastingdienst precies kunnen nagaan met wie ze zaken doen. Hetzelfde geldt voor je offertes en je algemene voorwaarden.',
                    'Het KvK-nummer is iets anders dan het vestigingsnummer: dat laatste is een twaalfcijferig nummer per vestiging en hoeft niet op de factuur.',
                ]],
                ['Factureren zonder KvK-nummer', [
                    'Wie incidenteel iets verkoopt zonder onderneming te zijn, kan geen geldige btw-factuur sturen. Zodra je structureel inkomsten hebt en zelfstandig deelneemt aan het economisch verkeer, moet je je inschrijven. Een inschrijving is binnen een dag geregeld en kost eenmalig een vast bedrag.',
                ]],
            ],
            'related' => ['factuureisen', 'factuur-zonder-kvk'],
            'see' => ['btw-identificatienummer', 'eenmanszaak'],
        ],

        'btw-tarieven' => [
            'term' => 'Btw-tarieven',
            'short' => 'Nederland kent drie btw-tarieven: 21 procent (algemeen), 9 procent (verlaagd) en 0 procent, plus vrijstellingen voor bepaalde diensten.',
            'updated' => '2026-10-15',
            'sections' => [
                ['De drie tarieven', [
                    'Het algemene tarief van 21 procent geldt voor de meeste producten en diensten. Het verlaagde tarief van 9 procent geldt voor onder meer voedingsmiddelen, geneesmiddelen, boeken, en een aantal arbeidsintensieve diensten zoals het schilderen en stukadoren van woningen die ouder zijn dan twee jaar. Het 0-procenttarief geldt vooral bij export en bij leveringen aan ondernemers in andere EU-landen: je brengt geen btw in rekening, maar houdt wel recht op aftrek van voorbelasting.',
                    'Daarnaast zijn er vrijgestelde prestaties, zoals medische zorg, onderwijs en bepaalde financiële diensten. Een vrijstelling is iets anders dan 0 procent: bij een vrijstelling mag je de btw op je inkopen niet terugvragen.',
                ]],
                ['Per regel het juiste tarief', [
                    'Op één factuur kunnen meerdere tarieven voorkomen, bijvoorbeeld materialen tegen 21 procent en arbeid tegen 9 procent. Splits de bedragen dan per tarief uit: eerst het bedrag exclusief btw, dan het btw-bedrag, per tarief apart. Een factuurprogramma doet dat automatisch zodra je per regel het tarief kiest.',
                ]],
            ],
            'related' => ['btw-tarieven', 'btw-aangifte-kwartaal-stappenplan'],
            'see' => ['btw-verlegd', 'btw-aangifte', 'kleineondernemersregeling'],
            'tool' => ['route' => 'btw-calculator', 'label' => 'Btw berekenen'],
        ],

        'btw-verlegd' => [
            'term' => 'Btw verlegd',
            'short' => 'Bij btw verlegd brengt de leverancier geen btw in rekening; de afnemer geeft de btw zelf aan. Dit geldt onder meer bij onderaanneming in de bouw.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Hoe de verleggingsregeling werkt', [
                    'Normaal brengt de leverancier btw in rekening en draagt die af. Bij de verleggingsregeling wordt dat omgedraaid: de factuur vermeldt geen btw-bedrag maar de tekst "btw verlegd", en de afnemer geeft de btw in zijn eigen aangifte aan en trekt hem in dezelfde aangifte weer af. Per saldo betaalt niemand iets, maar de Belastingdienst heeft wel zicht op de keten.',
                    'De regeling is verplicht in een aantal situaties: bij onderaanneming in de bouw en de scheepsbouw, bij het uitlenen van personeel in die sectoren, bij de levering van bepaalde goederen zoals mobiele telefoons boven een drempelbedrag, en bij diensten aan ondernemers in andere EU-landen.',
                ]],
                ['Wat er op de factuur moet staan', [
                    'Op een verlegde factuur vermeld je de bedragen exclusief btw, de aanduiding "btw verlegd" en het btw-identificatienummer van je klant. Een btw-bedrag of btw-percentage hoort er niet op. Vergeet je het btw-id van je klant, dan is de factuur niet geldig.',
                    'Werk je als onderaannemer voor een hoofdaannemer, dan is dit de standaardsituatie: je factureert verlegd en de hoofdaannemer handelt de btw af. Stort de hoofdaannemer een deel van de aanneemsom op je G-rekening, dan beperkt hij daarmee zijn ketenaansprakelijkheid.',
                ]],
            ],
            'related' => ['btw-verlegd-bouw-onderaanneming', 'factureren-buitenland-btw-verlegd'],
            'see' => ['g-rekening', 'btw-identificatienummer', 'btw-tarieven'],
        ],

        'kleineondernemersregeling' => [
            'term' => 'Kleineondernemersregeling (KOR)',
            'short' => 'De KOR is een btw-vrijstelling voor ondernemers met een omzet tot 20.000 euro per jaar. Je brengt geen btw in rekening en doet geen btw-aangifte.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Voor wie de KOR interessant is', [
                    'Blijft je omzet in een kalenderjaar onder de 20.000 euro, dan kun je kiezen voor de kleineondernemersregeling. Je brengt dan geen btw in rekening, doet geen btw-aangifte en mag de btw op je inkopen niet terugvragen. Dat scheelt administratie, maar het is alleen voordelig als je klanten vooral particulieren zijn of ondernemers die zelf geen btw kunnen aftrekken. Voor een zzp\'er die vooral aan bedrijven levert, is de KOR meestal nadelig: de btw die je zou rekenen kan de klant toch aftrekken, en jij verliest de aftrek op je eigen inkopen.',
                    'Je meldt je aan via de Belastingdienst; de deelname geldt voor minimaal drie jaar, tenzij je de omzetgrens overschrijdt. Sinds 2025 kun je de regeling ook in andere EU-landen toepassen (de EU-KOR), met een gezamenlijke omzetgrens van 100.000 euro.',
                ]],
                ['Wat op je factuur staat', [
                    'Onder de KOR zet je geen btw op je factuur en vermeld je dat de vrijstelling van toepassing is. Een factuurprogramma dat de KOR kent, laat het btw-blok dan automatisch weg en zet de juiste vermelding op het document.',
                ]],
            ],
            'related' => ['kleineondernemersregeling-kor', 'btw-terugvragen-voorbelasting'],
            'see' => ['btw-tarieven', 'btw-aangifte', 'btw-identificatienummer'],
        ],

        'btw-aangifte' => [
            'term' => 'Btw-aangifte',
            'short' => 'In de btw-aangifte geef je per tijdvak de btw over je omzet op en trek je de btw op je inkopen af. Meestal doe je dat per kwartaal.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Tijdvak en deadline', [
                    'De meeste ondernemers doen per kwartaal aangifte. De aangifte én de betaling moeten binnen zijn op de laatste dag van de maand na het kwartaal: over het eerste kwartaal dus uiterlijk 30 april. Op verzoek kun je per maand of per jaar aangifte doen; per maand is handig als je structureel btw terugkrijgt, per jaar kan alleen bij kleine bedragen.',
                    'Te laat aangeven of betalen levert een verzuimboete op, ook als er niets te betalen is. Een nihilaangifte moet je dus ook op tijd indienen.',
                ]],
                ['Wat je nodig hebt', [
                    'Per tijdvak tel je de omzet per btw-tarief en de bijbehorende btw op, plus de btw die naar jou is verlegd. Daar trek je de voorbelasting af: de btw op je inkoopfacturen en bonnen. Het verschil betaal je of krijg je terug. Houd je verkoop- en inkoopfacturen in één administratie bij, dan rolt de aangifte er in een paar minuten uit.',
                ]],
            ],
            'related' => ['btw-aangifte-kwartaal-stappenplan', 'btw-terugvragen-voorbelasting'],
            'see' => ['btw-tarieven', 'icp-opgave', 'voorbelasting'],
        ],

        'voorbelasting' => [
            'term' => 'Voorbelasting',
            'short' => 'Voorbelasting is de btw die leveranciers jou in rekening brengen. Als btw-ondernemer trek je die in je aangifte af van de btw die je zelf moet afdragen.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Btw terugvragen op inkopen', [
                    'Koop je iets voor je onderneming, dan betaal je daar btw over. Die btw heet voorbelasting en mag je in je btw-aangifte aftrekken, zolang je de inkoop gebruikt voor belaste omzet en je een geldige factuur hebt. Bij gemengd zakelijk en privégebruik, bijvoorbeeld een telefoon of een auto, trek je alleen het zakelijke deel af.',
                    'Een bonnetje van de bouwmarkt telt als vereenvoudigde factuur tot 100 euro inclusief btw. Daarboven heb je een factuur met je bedrijfsgegevens nodig, anders vervalt de aftrek.',
                ]],
                ['Uitzonderingen', [
                    'Op eten en drinken in de horeca, en op relatiegeschenken boven een drempel per ontvanger, mag je de btw niet aftrekken. Onder de kleineondernemersregeling is er helemaal geen aftrek.',
                ]],
            ],
            'related' => ['btw-terugvragen-voorbelasting', 'btw-aangifte-kwartaal-stappenplan'],
            'see' => ['btw-aangifte', 'vereenvoudigde-factuur'],
        ],

        'icp-opgave' => [
            'term' => 'ICP-opgave',
            'short' => 'De opgaaf intracommunautaire prestaties (ICP) is de melding aan de Belastingdienst van wat je aan ondernemers in andere EU-landen hebt geleverd.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Wanneer je een ICP-opgave doet', [
                    'Lever je goederen of diensten aan een btw-ondernemer in een ander EU-land, dan factureer je zonder btw (verlegd of tegen 0 procent) en meld je die omzet per klant in de ICP-opgave. De Belastingdienst wisselt die gegevens uit met het land van je klant, die daar de btw aangeeft. De opgave doe je over hetzelfde tijdvak als je btw-aangifte, meestal per kwartaal.',
                    'Je hebt het btw-identificatienummer van elke buitenlandse klant nodig; zonder geldig nummer mag je niet zonder btw factureren. Controleer nummers vooraf via VIES.',
                ]],
            ],
            'related' => ['factureren-buitenland-btw-verlegd'],
            'see' => ['btw-verlegd', 'btw-aangifte', 'btw-identificatienummer'],
        ],

        'creditnota' => [
            'term' => 'Creditnota',
            'short' => 'Een creditnota is een negatieve factuur waarmee je een eerder verstuurde factuur geheel of gedeeltelijk terugdraait, zonder die factuur te verwijderen.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Waarvoor je een creditnota gebruikt', [
                    'Heb je te veel gefactureerd, een verkeerd btw-tarief gebruikt of komt een klant terug op een bestelling, dan pas je de oorspronkelijke factuur niet aan. Je maakt een creditnota: een document met negatieve bedragen dat verwijst naar het factuurnummer dat je corrigeert. De creditnota krijgt een eigen nummer uit een eigen reeks en vermeldt dezelfde gegevens als een factuur, inclusief btw.',
                    'Daarna stuur je, als dat nodig is, een nieuwe, juiste factuur. Zo blijft je factuurreeks doorlopend en kan iedereen zien wat er is gebeurd.',
                ]],
                ['Btw op een creditnota', [
                    'De btw op de creditnota verlaag je in de aangifte van het tijdvak waarin je hem uitreikt; je klant verlaagt zijn voorbelasting. Verreken je de creditnota met een openstaande factuur, dan betaalt de klant alleen het verschil.',
                ]],
            ],
            'related' => ['creditnota-maken', 'factuurnummering'],
            'see' => ['factuur', 'factuurnummer'],
        ],

        'pro-formafactuur' => [
            'term' => 'Pro-formafactuur',
            'short' => 'Een pro-formafactuur is een voorlopige factuur ter informatie, bijvoorbeeld voor een vooruitbetaling of douane. Het is geen echte factuur en telt niet mee voor de btw.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Wat een pro-forma wel en niet is', [
                    'Een pro-formafactuur ziet eruit als een factuur maar is er geen: hij heeft geen factuurnummer uit je reeks, telt niet mee voor je omzet en geeft je klant geen recht op btw-aftrek. Je gebruikt hem om vooraf te laten zien wat de klant gaat betalen, bijvoorbeeld bij een vooruitbetaling, een aanvraag van een inkoopordernummer of een zending naar het buitenland die door de douane moet.',
                    'Zodra de klant betaalt of de levering plaatsvindt, stuur je een echte factuur. In veel gevallen is een offerte of een voorschotfactuur een beter alternatief: een offerte legt de afspraak vast en een voorschotfactuur is wél een geldige factuur.',
                ]],
            ],
            'related' => ['proforma-factuur', 'voorschotfactuur-aanbetaling'],
            'see' => ['offerte', 'aanbetaling', 'factuur'],
        ],

        'termijnfactuur' => [
            'term' => 'Termijnfactuur',
            'short' => 'Een termijnfactuur (deelfactuur) is een factuur voor een deel van een opdracht, bijvoorbeeld per bouwfase of per maand, gevolgd door een eindfactuur.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Factureren in termijnen', [
                    'Bij langere opdrachten wacht je niet tot het einde met factureren. Je spreekt termijnen af, bijvoorbeeld 30 procent bij opdracht, 40 procent halverwege en 30 procent bij oplevering, of een vast bedrag per maand. Elke termijn is een gewone, geldige factuur met btw. Aan het einde volgt de eindfactuur, waarop je de eerder gefactureerde termijnen in mindering brengt.',
                    'Leg de termijnen vast in je offerte of opdrachtbevestiging. Dan weet de klant wat hij wanneer kan verwachten en heb jij een grond om een termijn op te eisen als het werk zover is.',
                ]],
                ['Btw bij termijnen', [
                    'De btw over een termijn geef je aan in het tijdvak waarin je de termijnfactuur uitreikt, niet pas bij de oplevering. Werk je onder de verleggingsregeling, dan geldt die ook voor elke termijn.',
                ]],
            ],
            'related' => ['deelfactuur-termijnfactuur', 'eindfactuur'],
            'see' => ['aanbetaling', 'factuur', 'offerte'],
        ],

        'aanbetaling' => [
            'term' => 'Aanbetaling (voorschot)',
            'short' => 'Een aanbetaling is een bedrag dat je klant vooruit betaalt, vaak om materialen te kunnen inkopen. Je factureert het met een voorschotfactuur, inclusief btw.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Een aanbetaling vragen', [
                    'Bij opdrachten met veel materiaal of een lange doorlooptijd is een aanbetaling gebruikelijk. Je stuurt daarvoor een voorschotfactuur: een gewone factuur met btw, waarop staat dat het om een vooruitbetaling op de opdracht gaat. De btw ben je verschuldigd zodra je de factuur uitreikt of de betaling ontvangt, wat het eerst gebeurt.',
                    'Op de eindfactuur vermeld je de ontvangen aanbetaling en breng je die in mindering op het totaal. Zo klopt het te betalen bedrag en blijft de btw per factuur juist.',
                ]],
                ['Hoeveel mag je vragen?', [
                    'Er is geen wettelijk maximum; bij consumenten mag je niet meer dan de helft vooruit vragen als je werkt onder algemene voorwaarden die daarin voorzien. Houd het redelijk: een percentage dat je werkelijke voorkosten dekt, is voor klanten het makkelijkst te accepteren.',
                ]],
            ],
            'related' => ['voorschotfactuur-aanbetaling', 'eindfactuur'],
            'see' => ['termijnfactuur', 'pro-formafactuur'],
        ],

        'betalingstermijn' => [
            'term' => 'Betalingstermijn',
            'short' => 'De betalingstermijn is de periode waarbinnen een klant je factuur moet betalen. Spreek je niets af, dan geldt wettelijk 30 dagen.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Wat de wet zegt', [
                    'Tussen bedrijven geldt een betalingstermijn van 30 dagen als er niets is afgesproken. Je mag een langere termijn overeenkomen tot maximaal 60 dagen; een grote onderneming mag een mkb-leverancier niet langer dan 30 dagen laten wachten. Overheden moeten binnen 30 dagen betalen. Een kortere termijn, bijvoorbeeld 14 dagen, is altijd toegestaan en voor kleine opdrachten heel gewoon.',
                    'Zet de termijn op je factuur als een concrete vervaldatum, niet alleen als "14 dagen". Een datum is duidelijker en maakt een herinnering makkelijker.',
                ]],
                ['Na de vervaldatum', [
                    'Betaalt een zakelijke klant niet op tijd, dan is hij vanaf de dag na de vervaldatum automatisch in verzuim en mag je wettelijke handelsrente en een vaste vergoeding voor incassokosten rekenen. Bij consumenten moet je eerst een herinnering met een termijn van veertien dagen sturen voordat incassokosten verschuldigd zijn.',
                ]],
            ],
            'related' => ['betalingstermijn-aanmanen', 'incassokosten-wettelijke-rente-berekenen'],
            'see' => ['betalingsherinnering', 'aanmaning', 'wettelijke-rente'],
        ],

        'betalingsherinnering' => [
            'term' => 'Betalingsherinnering',
            'short' => 'Een betalingsherinnering is de vriendelijke eerste melding aan een klant dat een factuur over de vervaldatum is, voordat je een aanmaning stuurt.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Toon en timing', [
                    'De meeste late betalingen zijn vergeten, niet geweigerd. Een korte herinnering een paar dagen na de vervaldatum, met het factuurnummer, het bedrag en een betaallink, lost het grootste deel op. Houd de toon neutraal en ga ervan uit dat de klant het over het hoofd zag. Een tweede herinnering na een week of twee mag dringender zijn en een nieuwe uiterste datum noemen.',
                    'Verstuur herinneringen automatisch op vaste dagen na de vervaldatum, dan hoef je er niet aan te denken en behandelt je klant ze als een normaal onderdeel van het proces.',
                ]],
                ['Bij consumenten: de veertiendagenbrief', [
                    'Wil je bij een particuliere klant incassokosten rekenen, dan moet je eerst een schriftelijke herinnering sturen die de klant nog veertien dagen geeft om zonder extra kosten te betalen, met daarin het bedrag van de incassokosten die anders volgen. Zonder die brief zijn de kosten niet verschuldigd.',
                ]],
            ],
            'related' => ['betalingstermijn-aanmanen'],
            'see' => ['betalingstermijn', 'aanmaning', 'ingebrekestelling'],
        ],

        'aanmaning' => [
            'term' => 'Aanmaning',
            'short' => 'Een aanmaning is de formele sommatie om een openstaande factuur binnen een laatste termijn te betalen, met vermelding van rente en incassokosten.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Van herinnering naar aanmaning', [
                    'Heeft een klant op een of twee herinneringen niet gereageerd, dan stuur je een aanmaning. Die noemt het openstaande bedrag, de oorspronkelijke vervaldatum, de wettelijke rente die inmiddels loopt en de incassokosten die je in rekening brengt of gaat brengen, en geeft een laatste termijn van bijvoorbeeld vijf werkdagen. Vermeld ook wat er daarna gebeurt: overdracht aan een incassobureau of een gerechtelijke procedure.',
                    'Stuur de aanmaning per e-mail en bewaar het bewijs van verzending. Bij grote bedragen kan een aangetekende brief erbij; in een procedure telt vooral dat de klant de aanmaning aantoonbaar heeft ontvangen.',
                ]],
                ['Zelf doen of uitbesteden', [
                    'Een aanmaning met een duidelijke berekening van rente en kosten werkt vaak beter dan een dreigende toon. Blijft betaling uit, dan is overdragen aan een incassobureau de volgende stap; een goed bureau werkt bij zakelijke vorderingen op basis van de wettelijke incassokosten, zodat het de klant geld kost en jou niet.',
                ]],
            ],
            'related' => ['betalingstermijn-aanmanen', 'incassokosten-wettelijke-rente-berekenen'],
            'see' => ['betalingsherinnering', 'ingebrekestelling', 'incassokosten', 'wettelijke-rente'],
            'tool' => ['route' => 'aanmaning', 'label' => 'Gratis online aanmaning versturen'],
        ],

        'ingebrekestelling' => [
            'term' => 'Ingebrekestelling',
            'short' => 'Een ingebrekestelling is een schriftelijke sommatie met een redelijke termijn, nodig om een schuldenaar in verzuim te brengen als er geen vaste vervaldatum is afgesproken.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Wanneer je er een nodig hebt', [
                    'Staat op je factuur een duidelijke vervaldatum, dan is een klant na die datum automatisch in verzuim en heb je geen aparte ingebrekestelling nodig. Ontbreekt zo\'n termijn, of gaat het om een andere verplichting dan betalen, bijvoorbeeld het leveren van gegevens, dan moet je de wederpartij eerst schriftelijk aanmanen en een redelijke termijn geven om alsnog na te komen. Pas na die termijn is er verzuim en kun je rente, schadevergoeding of ontbinding vorderen.',
                    'Noem in de brief precies wat je verlangt, binnen welke termijn, en wat de gevolgen zijn als dat uitblijft. Een termijn van veertien dagen is in de meeste gevallen redelijk.',
                ]],
            ],
            'related' => ['betalingstermijn-aanmanen'],
            'see' => ['aanmaning', 'betalingstermijn', 'wettelijke-rente'],
        ],

        'incassokosten' => [
            'term' => 'Incassokosten',
            'short' => 'Incassokosten zijn de vaste vergoeding die je mag rekenen als een klant te laat betaalt: een wettelijke staffel vanaf 40 euro, oplopend tot maximaal 6.775 euro.',
            'updated' => '2026-10-15',
            'sections' => [
                ['De wettelijke staffel', [
                    'De Wet incassokosten bepaalt hoeveel je in rekening mag brengen: 15 procent over de eerste 2.500 euro van de hoofdsom, 10 procent over de volgende 2.500 euro, 5 procent over de volgende 5.000 euro, 1 procent over de volgende 190.000 euro en 0,5 procent over het meerdere. Het minimum is 40 euro, het maximum 6.775 euro. Over een factuur van 1.000 euro mag je dus 150 euro incassokosten rekenen.',
                    'Bij consumenten is de staffel dwingend en moet je eerst een veertiendagenbrief sturen. Tussen bedrijven mag je in je voorwaarden een ander bedrag afspreken; spreek je niets af, dan geldt dezelfde staffel en zijn de kosten verschuldigd zodra de klant in verzuim is.',
                ]],
                ['Btw over incassokosten', [
                    'Rekent een incassobureau btw over zijn kosten en kun jij die btw niet aftrekken, dan mag je de staffel met de btw verhogen. Kun je de btw wel aftrekken, zoals de meeste ondernemers, dan rekent je de kosten zonder btw door.',
                ]],
            ],
            'related' => ['incassokosten-wettelijke-rente-berekenen', 'betalingstermijn-aanmanen'],
            'see' => ['aanmaning', 'wettelijke-rente', 'betalingsherinnering'],
            'tool' => ['route' => 'incassokosten-calculator', 'label' => 'Incassokosten berekenen'],
        ],

        'wettelijke-rente' => [
            'term' => 'Wettelijke rente',
            'short' => 'Wettelijke rente is de rente die een schuldenaar verschuldigd is bij te late betaling. Voor handelstransacties geldt een hoger percentage dan voor consumenten.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Twee soorten rente', [
                    'De wettelijke handelsrente geldt voor overeenkomsten tussen bedrijven en met overheden en ligt een stuk hoger dan de gewone wettelijke rente, die voor consumenten geldt. Beide percentages worden elk half jaar opnieuw vastgesteld, per 1 januari en 1 juli; de actuele stand vind je bij de Nederlandsche Bank en de Rijksoverheid.',
                    'De rente loopt vanaf de dag na de vervaldatum tot de dag van betaling en wordt berekend over het openstaande bedrag inclusief btw. Je hoeft er niet apart om te vragen: zodra een zakelijke klant in verzuim is, is de rente verschuldigd. In de praktijk vermeld je hem op de aanmaning, zodat de klant ziet wat uitstel kost.',
                ]],
                ['Contractuele rente', [
                    'In je algemene voorwaarden mag je een andere rente afspreken, bijvoorbeeld een vast percentage per maand. Tegenover consumenten mag die niet hoger zijn dan de wettelijke rente.',
                ]],
            ],
            'related' => ['incassokosten-wettelijke-rente-berekenen'],
            'see' => ['incassokosten', 'betalingstermijn', 'aanmaning'],
        ],

        'debiteur' => [
            'term' => 'Debiteur',
            'short' => 'Een debiteur is een klant die jou nog geld verschuldigd is. Debiteurenbeheer is alles wat je doet om openstaande facturen op tijd betaald te krijgen.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Debiteuren en debiteurenbeheer', [
                    'Zodra je een factuur verstuurt, wordt je klant een debiteur: hij heeft een schuld aan jou tot de factuur is betaald. Het totaal van alle openstaande facturen is je debiteurensaldo. Hoe sneller debiteuren betalen, hoe minder geld er in onbetaalde facturen vastzit en hoe gezonder je cashflow.',
                    'Goed debiteurenbeheer begint bij de factuur zelf: duidelijk, compleet en met een betaallink. Daarna volgt een vaste routine van herinneren, aanmanen en zo nodig overdragen. Een lijst met vervallen facturen, gesorteerd op ouderdom, laat elke week zien waar je actie moet ondernemen.',
                ]],
                ['Kengetal: DSO', [
                    'De gemiddelde betaaltermijn van je klanten in dagen, de DSO, is een nuttig kengetal. Stijgt hij, dan betalen klanten trager en moet je eerder of strakker herinneren.',
                ]],
            ],
            'related' => ['betalingstermijn-aanmanen'],
            'see' => ['crediteur', 'betalingstermijn', 'cashflow'],
        ],

        'crediteur' => [
            'term' => 'Crediteur',
            'short' => 'Een crediteur is een leverancier aan wie jij nog geld verschuldigd bent. Je inkoopfacturen vormen samen je crediteurensaldo.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Crediteuren in je administratie', [
                    'Elke inkoopfactuur die je ontvangt en nog niet hebt betaald, staat bij je crediteuren. Door inkoopfacturen meteen in te boeken, weet je wat er de komende weken van je rekening gaat en kun je betalingen plannen. Bovendien heb je ze nodig voor je btw-aangifte: de btw op inkoopfacturen is je voorbelasting.',
                    'Betaal leveranciers op tijd maar niet te vroeg; een betalingstermijn is er ook voor jou. Bij betalingskorting voor snel betalen kan vroeg betalen wél lonen.',
                ]],
            ],
            'related' => ['btw-terugvragen-voorbelasting'],
            'see' => ['debiteur', 'voorbelasting'],
        ],

        'e-factuur' => [
            'term' => 'E-factuur',
            'short' => 'Een e-factuur is een factuur in een gestructureerd digitaal formaat (zoals UBL) dat de computer van de ontvanger direct kan inlezen. Een pdf is geen e-factuur.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Pdf versus e-factuur', [
                    'Een pdf is een plaatje van een factuur: een mens kan hem lezen, maar een boekhoudpakket moet de gegevens eruit halen door ze te herkennen. Een e-factuur bevat dezelfde gegevens als gestructureerde data, in een standaardformaat zoals UBL. De ontvanger boekt hem zonder overtypen in, met minder fouten en sneller.',
                    'De Nederlandse overheid accepteert van leveranciers alleen nog e-facturen, meestal via het Peppol-netwerk. Steeds meer grote bedrijven vragen hetzelfde, en de Europese Unie werkt aan verplichte e-facturatie voor grensoverschrijdende handel tussen bedrijven vanaf 2030.',
                ]],
                ['Zelf e-facturen versturen', [
                    'Een factuurprogramma met Peppol-koppeling verstuurt je factuur als e-factuur naar klanten die daarop zijn aangesloten en als pdf naar de rest. Je merkt er zelf niets van; de klant krijgt het formaat dat hij kan verwerken.',
                ]],
            ],
            'related' => ['factuureisen', 'factuur-app-kiezen'],
            'see' => ['peppol', 'ubl', 'factuur'],
        ],

        'peppol' => [
            'term' => 'Peppol',
            'short' => 'Peppol is het internationale netwerk voor het veilig uitwisselen van e-facturen tussen bedrijven en overheden, via erkende toegangspunten.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Hoe Peppol werkt', [
                    'Peppol werkt als een postnetwerk: jij levert je e-factuur aan bij jouw toegangspunt (access point), dat hem aflevert bij het toegangspunt van je klant. Elke deelnemer is te vinden op een uniek adres, in Nederland meestal het KvK-nummer. Je hoeft dus geen e-mailadres of portaal van de klant te kennen; het netwerk zoekt het adres op en bevestigt de aflevering.',
                    'Alle Nederlandse overheden en steeds meer bedrijven zijn aangesloten. Het netwerk werkt ook over de grens, in de hele EU en daarbuiten.',
                ]],
                ['Aansluiten', [
                    'Je sluit aan via een dienstverlener of via een factuurprogramma dat een toegangspunt heeft ingebouwd. Na registratie kun je zowel versturen als ontvangen: inkoopfacturen van aangesloten leveranciers komen dan automatisch in je administratie binnen.',
                ]],
            ],
            'related' => ['factuur-app-kiezen'],
            'see' => ['e-factuur', 'ubl'],
        ],

        'ubl' => [
            'term' => 'UBL',
            'short' => 'UBL (Universal Business Language) is het XML-formaat waarin e-facturen worden uitgewisseld. In Nederland en via Peppol is UBL de standaard.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Wat er in een UBL-bestand staat', [
                    'Een UBL-factuur is een XML-bestand met vaste velden: afzender en ontvanger, factuurnummer en -datum, regels met aantal, prijs en btw-tarief, de totalen en de betaalgegevens. Omdat elk veld een vaste betekenis heeft, kan ieder boekhoudpakket het bestand foutloos inlezen. Nederland gebruikt het profiel NLCIUS, dat binnen de Europese norm valt en één op één via Peppol kan.',
                    'Veel factuurprogramma\'s sturen een UBL-bestand als bijlage mee met de pdf in de e-mail. Boekhoudpakketten herkennen die bijlage en boeken de factuur automatisch in.',
                ]],
            ],
            'related' => ['factuur-app-kiezen'],
            'see' => ['e-factuur', 'peppol'],
        ],

        'g-rekening' => [
            'term' => 'G-rekening',
            'short' => 'Een G-rekening is een geblokkeerde bankrekening waarop een opdrachtgever het deel van de factuur voor loonheffingen en btw stort, om ketenaansprakelijkheid te beperken.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Waarom een G-rekening', [
                    'Wie in de bouw werk uitbesteedt of personeel inleent, kan aansprakelijk worden gesteld voor de loonheffingen en btw die de onderaannemer of uitlener niet afdraagt: de keten- en inlenersaansprakelijkheid. Door een deel van de factuur op de G-rekening van de onderaannemer te storten, is de opdrachtgever voor dat bedrag gevrijwaard. Het geld op de G-rekening kan alleen naar de Belastingdienst of naar de G-rekening van een volgende schakel.',
                    'Als onderaannemer vraag je de G-rekening aan bij de Belastingdienst en open je hem bij je bank. Op je factuur vermeld je dan welk deel op de G-rekening moet worden gestort, vaak een vast percentage van de loonsom in de aanneemsom.',
                ]],
            ],
            'related' => ['btw-verlegd-bouw-onderaanneming'],
            'see' => ['btw-verlegd', 'factuur'],
        ],

        'sepa-incasso' => [
            'term' => 'SEPA-incasso (automatische incasso)',
            'short' => 'Met een SEPA-incasso schrijf je het factuurbedrag automatisch af van de rekening van je klant, op basis van een door de klant getekende machtiging.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Machtiging en vooraankondiging', [
                    'Een automatische incasso begint met een machtiging: de klant geeft schriftelijk of digitaal toestemming om bedragen af te schrijven, met een uniek machtigingskenmerk. Voor elke afschrijving stuur je een vooraankondiging, bijvoorbeeld de factuur zelf met de datum van afschrijving. Daarna lever je een incassobestand (pain.008) aan bij je bank, of laat je je factuurprogramma dat doen.',
                    'Een klant kan een afschrijving binnen acht weken zonder opgaaf van reden laten terugboeken; is er geen geldige machtiging, dan zelfs dertien maanden. Bewaar machtigingen daarom zorgvuldig.',
                ]],
                ['Wanneer incasso loont', [
                    'Bij abonnementen, onderhoudscontracten en terugkerende facturen scheelt incasso veel herinneringen: het bedrag komt op de afgesproken dag binnen. Voor eenmalige grote bedragen is een iDEAL-betaallink vaak praktischer.',
                ]],
            ],
            'related' => ['terugkerende-facturen-abonnementen'],
            'see' => ['ideal-betaallink', 'betalingstermijn'],
        ],

        'ideal-betaallink' => [
            'term' => 'iDEAL-betaallink',
            'short' => 'Een iDEAL-betaallink is een link in je factuurmail waarmee de klant de factuur in één keer betaalt. Het bedrag staat meestal dezelfde dag op je rekening.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Sneller betaald met een betaallink', [
                    'Een factuur met een betaallink wordt gemiddeld sneller betaald dan een factuur die de klant zelf moet overtypen: geen IBAN, geen kenmerk, één klik en klaar. De link verwijst naar een betaalpagina met het juiste bedrag en factuurnummer; na betaling wordt de factuur automatisch op betaald gezet.',
                    'Je hebt een betaalprovider nodig, zoals Mollie. Die rekent een klein vast bedrag per transactie. Werk je met je eigen account bij de provider, dan komt het geld rechtstreeks op jouw rekening en blijft er niets achter bij een tussenpartij.',
                ]],
            ],
            'related' => ['betalingstermijn-aanmanen'],
            'see' => ['sepa-incasso', 'betalingstermijn'],
        ],

        'auditfile-xaf' => [
            'term' => 'Auditfile (XAF)',
            'short' => 'Een auditfile is een standaard XML-export (XAF) van je hele administratie over een boekjaar, voor je accountant of de Belastingdienst.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Wat een auditfile bevat', [
                    'De XML Auditfile Financieel (XAF) is de Nederlandse standaard voor het uitwisselen van boekhoudgegevens: grootboekrekeningen, journaalposten, debiteuren, crediteuren en btw-codes over een boekjaar. De Belastingdienst kan bij een controle om een auditfile vragen; accountants gebruiken hem om jouw gegevens in hun eigen software in te lezen zonder overtypen.',
                    'Een factuurprogramma dat een auditfile kan maken, bespaart je accountant werk en jou kosten. Je exporteert het bestand per boekjaar en stuurt het mee met je jaarstukken.',
                ]],
            ],
            'related' => ['bewaarplicht-administratie'],
            'see' => ['grootboek', 'bewaarplicht'],
        ],

        'grootboek' => [
            'term' => 'Grootboek',
            'short' => 'Het grootboek is de verzameling rekeningen waarop alle financiële gebeurtenissen van je onderneming worden geboekt: omzet, kosten, btw, bank en debiteuren.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Rekeningen en journaalposten', [
                    'Elke factuur, bon en bankmutatie wordt als journaalpost op grootboekrekeningen geboekt: een verkoopfactuur bijvoorbeeld op Omzet, Af te dragen btw en Debiteuren. Samen vormen die rekeningen het grootboek, de basis voor je btw-aangifte, je winst-en-verliesrekening en je balans. Het rekeningschema (RGS) is in Nederland gestandaardiseerd, zodat accountants en software dezelfde indeling gebruiken.',
                    'Als zzp\'er hoef je het grootboek niet zelf bij te houden: een factuurprogramma boekt verkoop- en inkoopfacturen automatisch en levert je accountant een auditfile of exports aan.',
                ]],
            ],
            'related' => ['bewaarplicht-administratie'],
            'see' => ['auditfile-xaf', 'debiteur', 'crediteur'],
        ],

        'bewaarplicht' => [
            'term' => 'Bewaarplicht',
            'short' => 'Ondernemers moeten hun administratie zeven jaar bewaren, en gegevens over onroerende zaken tien jaar. Dat geldt ook voor digitale facturen en e-mails.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Wat je moet bewaren', [
                    'Onder de bewaarplicht vallen alle verstuurde en ontvangen facturen, bankafschriften, offertes en overeenkomsten, je grootboek en je btw-aangiften, maar ook agenda\'s, urenstaten en e-mails die voor de administratie van belang zijn. De termijn is zeven jaar na afloop van het boekjaar; voor onroerende zaken, zoals een bedrijfspand, tien jaar.',
                    'Digitaal bewaren mag, zolang de gegevens leesbaar en controleerbaar blijven. Scan je papieren bonnen, dan mag je het papier weggooien als de scan compleet is. Bewaar bestanden op een plek met een back-up: een laptop die kapot gaat is geen excuus.',
                ]],
            ],
            'related' => ['bewaarplicht-administratie'],
            'see' => ['factuur', 'auditfile-xaf'],
        ],

        'urencriterium' => [
            'term' => 'Urencriterium',
            'short' => 'Het urencriterium is de eis van minimaal 1.225 uur per jaar aan je onderneming om in aanmerking te komen voor ondernemersaftrek zoals de zelfstandigenaftrek.',
            'updated' => '2026-10-15',
            'sections' => [
                ['1.225 uur per jaar', [
                    'Voor de zelfstandigenaftrek, de startersaftrek en enkele andere regelingen moet je in een kalenderjaar minstens 1.225 uur aan je onderneming besteden. Alle uren tellen mee, dus ook acquisitie, administratie, reistijd en het bijhouden van je vakkennis, niet alleen de uren die je factureert. Heb je daarnaast een baan, dan moet je bovendien meer dan de helft van je werktijd aan je onderneming besteden, tenzij je starter bent.',
                    'Houd je uren bij, ook als je per project of vaste prijs werkt. Bij een controle moet je aannemelijk maken dat je de uren hebt gemaakt; een urenregistratie in je factuurprogramma, gekoppeld aan klanten en projecten, is daarvoor het meest overtuigend.',
                ]],
            ],
            'related' => ['zelfstandigenaftrek-urencriterium-2026'],
            'see' => ['zzp', 'eenmanszaak'],
        ],

        'zzp' => [
            'term' => 'Zzp\'er',
            'short' => 'Een zzp\'er is een zelfstandige zonder personeel: een ondernemer die alleen werkt, meestal als eenmanszaak, en zelf factureert, btw aangeeft en zijn administratie bijhoudt.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Geen rechtsvorm, wel een ondernemer', [
                    'Zzp is geen juridische rechtsvorm maar een omschrijving. De meeste zzp\'ers hebben een eenmanszaak; sommigen een bv. Voor de Belastingdienst ben je ondernemer als je zelfstandig werkt, meerdere opdrachtgevers hebt, ondernemersrisico loopt en investeert in je bedrijf. Dan heb je recht op ondernemersaftrek, mits je aan het urencriterium voldoet, en ben je btw-ondernemer die factureert en aangifte doet.',
                    'Werk je langdurig voor één opdrachtgever onder diens leiding, dan kan de Belastingdienst de relatie als dienstverband zien. Sinds 2025 wordt daar weer actief op gehandhaafd. Een modelovereenkomst en echte zelfstandigheid in de praktijk, zoals eigen gereedschap, eigen uren en meerdere klanten, beperken dat risico.',
                ]],
                ['Wat je als zzp\'er regelt', [
                    'Een inschrijving bij de KvK, een btw-id, een zakelijke rekening, een factuurprogramma, een urenregistratie en een vaste routine voor je btw-aangifte. Daarmee ben je in een paar uur per maand klaar met je administratie.',
                ]],
            ],
            'related' => ['eerste-factuur-als-zzper', 'zelfstandigenaftrek-urencriterium-2026', 'kilometervergoeding-zzp-2026'],
            'see' => ['eenmanszaak', 'urencriterium', 'kleineondernemersregeling'],
        ],

        'eenmanszaak' => [
            'term' => 'Eenmanszaak',
            'short' => 'Een eenmanszaak is de eenvoudigste rechtsvorm: één eigenaar die privé aansprakelijk is en belasting betaalt over de winst via de inkomstenbelasting.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Kenmerken', [
                    'Een eenmanszaak richt je op door inschrijving bij de KvK; er is geen startkapitaal of notaris nodig. Je bent als eigenaar met je hele privévermogen aansprakelijk voor schulden van de zaak. De winst wordt belast in box 1 van de inkomstenbelasting, na aftrek van de ondernemersaftrek en de mkb-winstvrijstelling. Een eenmanszaak mag wel personeel in dienst hebben; de naam zegt alleen iets over het eigendom.',
                    'Op je factuur gebruik je de naam zoals ingeschreven bij de KvK; een handelsnaam mag erbij. Je KvK-nummer en btw-id zijn verplicht, net als bij elke andere rechtsvorm.',
                ]],
                ['Eenmanszaak of bv', [
                    'Bij een hogere winst of een groter aansprakelijkheidsrisico kan een bv aantrekkelijk zijn: beperkte aansprakelijkheid en vennootschapsbelasting over de winst. Daar staan hogere kosten en meer verplichtingen tegenover, zoals een jaarrekening en een salarisadministratie voor jezelf.',
                ]],
            ],
            'related' => ['eerste-factuur-als-zzper', 'factuur-zonder-kvk'],
            'see' => ['zzp', 'kvk-nummer'],
        ],

        'vereenvoudigde-factuur' => [
            'term' => 'Vereenvoudigde factuur',
            'short' => 'Voor bedragen tot 100 euro inclusief btw volstaat een vereenvoudigde factuur, zoals een kassabon, met minder verplichte gegevens dan een gewone factuur.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Wat erop moet staan', [
                    'Een vereenvoudigde factuur vermeldt de naam en het adres van de leverancier, de datum, een omschrijving van de levering en het btw-bedrag of de gegevens om dat te berekenen, zoals het totaalbedrag en het tarief. De naam van de klant, een doorlopend factuurnummer en de uitsplitsing per tarief zijn niet verplicht. Een kassabon van de bouwmarkt of een parkeerbon voldoet meestal.',
                    'De regeling geldt tot 100 euro inclusief btw en ook bij correcties op een eerdere factuur. Voor leveringen aan ondernemers in andere EU-landen en bij verlegde btw mag je hem niet gebruiken.',
                ]],
            ],
            'related' => ['factuureisen', 'btw-terugvragen-voorbelasting'],
            'see' => ['factuur', 'voorbelasting'],
        ],

        'offerte' => [
            'term' => 'Offerte',
            'short' => 'Een offerte is een schriftelijk aanbod met prijs, omvang en voorwaarden van een opdracht. Accepteert de klant, dan is er een overeenkomst.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Een offerte die werkt', [
                    'Een goede offerte beschrijft precies wat je levert en wat niet, tegen welke prijs, binnen welke termijn en onder welke voorwaarden, zoals betaling in termijnen, meerwerk en geldigheidsduur. Zodra de klant de offerte accepteert, is er een bindende overeenkomst; de offerte is daarmee de basis voor je factuur en voor elke discussie achteraf. Verwijs naar je algemene voorwaarden en stuur die mee.',
                    'Geef een offerte een geldigheidsduur van bijvoorbeeld dertig dagen. Daarna ben je vrij om de prijs aan te passen. Laat de klant digitaal ondertekenen: dat is sneller dan printen en scannen, en je hebt een bewijs met datum en tijd.',
                ]],
                ['Van offerte naar factuur', [
                    'Na acceptatie maak je de factuur uit de offerte, zodat regels, prijzen en btw-tarieven één op één overgaan. Bij grotere opdrachten spreek je in de offerte termijnen af en factureer je per termijn.',
                ]],
            ],
            'related' => ['deelfactuur-termijnfactuur', 'eindfactuur'],
            'see' => ['opdrachtbevestiging', 'termijnfactuur', 'factuur'],
        ],

        'opdrachtbevestiging' => [
            'term' => 'Opdrachtbevestiging',
            'short' => 'Een opdrachtbevestiging legt schriftelijk vast dat de klant akkoord is met je aanbod, met de afspraken over werk, prijs en planning.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Waarom je hem stuurt', [
                    'Een mondeling akkoord is ook een overeenkomst, maar lastig te bewijzen. Met een opdrachtbevestiging, een korte brief of e-mail waarin je de afspraken herhaalt en de klant vraagt die te bevestigen, voorkom je dat er later verschil van inzicht ontstaat over wat precies is afgesproken. Een getekende offerte doet hetzelfde werk: dan is een aparte bevestiging niet nodig.',
                    'Neem erin op: de omschrijving van de opdracht, de prijs of het uurtarief, de planning, de betaalafspraken en de verwijzing naar je algemene voorwaarden. Bewaar de bevestiging bij de opdracht; hij hoort bij je administratie.',
                ]],
            ],
            'related' => ['eerste-factuur-als-zzper'],
            'see' => ['offerte', 'factuur'],
        ],

        'cashflow' => [
            'term' => 'Cashflow',
            'short' => 'Cashflow is het geld dat daadwerkelijk je onderneming in- en uitstroomt. Winst op papier helpt niet als klanten te laat betalen.',
            'updated' => '2026-10-15',
            'sections' => [
                ['Winst is geen cashflow', [
                    'Je kunt winstgevend zijn en toch geen geld hebben: de omzet staat op facturen die nog niet zijn betaald, terwijl je leveranciers, de btw en je eigen salaris wel op tijd moeten worden voldaan. Cashflow gaat over timing. Hoe korter de tijd tussen het werk en het geld op je rekening, hoe gezonder je bedrijf.',
                    'De grootste hefbomen: snel factureren na levering, een korte betalingstermijn, een betaallink op de factuur, een aanbetaling bij grote opdrachten en consequent herinneren. Zet daarnaast elk kwartaal de btw apart, zodat de aangifte geen verrassing is.',
                ]],
            ],
            'related' => ['betalingstermijn-aanmanen', 'voorschotfactuur-aanbetaling'],
            'see' => ['debiteur', 'betalingstermijn', 'aanbetaling'],
        ],
    ],
];
