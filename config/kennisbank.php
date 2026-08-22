<?php

/*
|--------------------------------------------------------------------------
| Kennisbank-artikelen
|--------------------------------------------------------------------------
| Publieke, SEO-gerichte artikelen over factureren, btw en betaald krijgen —
| gericht op zzp'ers en mkb die (nog) geen klant zijn. Elke sleutel is de
| URL-slug (/kennisbank/{slug}). Zelfde structuur als config/help.php:
|
|   'category' => groepsnaam (voor navigatie/labels)
|   'title'    => titel van het artikel
|   'intro'    => korte inleiding (ook de meta-description)
|   'sections' => lijst van [kop, [alinea's...]]
|
| Let op: fiscale bedragen en regels veranderen — check bij twijfel de
| Belastingdienst en houd de artikelen actueel.
*/

return [
    'articles' => [

        // ---------- FACTUREREN ----------
        'factuureisen' => [
            'category' => 'Factureren',
            'title' => 'Wat moet er verplicht op een factuur staan?',
            'intro' => 'De Belastingdienst stelt duidelijke eisen aan een factuur. Dit is de complete checklist — plus de veelgemaakte fouten die je btw-aftrek bij je klant in gevaar brengen.',
            'sections' => [
                ['De verplichte onderdelen', [
                    'Op een gewone factuur tussen bedrijven moeten in elk geval staan: je volledige bedrijfsnaam en die van je klant (de juridische naam; een handelsnaam mag óók, als die samen met adres bekend is bij de KvK), beide adressen, je KvK-nummer, je btw-identificatienummer, een uniek opeenvolgend factuurnummer, de factuurdatum, de datum waarop je leverde (als die afwijkt), een duidelijke omschrijving van wat je hebt geleverd en hoeveel, het bedrag exclusief btw per btw-tarief, het gehanteerde btw-tarief en het btw-bedrag in euro\'s.',
                    'Lever je aan een klant in een ander EU-land of geldt een verlegging, dan vermeld je ook het btw-identificatienummer van je klant en de aanduiding "btw verlegd".',
                ]],
                ['Bedragen: exclusief, btw en totaal', [
                    'Splits je bedragen altijd uit: eerst het bedrag exclusief btw per tarief, dan het btw-bedrag per tarief, en tot slot het totaal. Gebruik je meerdere tarieven op één factuur (bijvoorbeeld 21% en 9%), dan laat je die uitsplitsing per tarief zien.',
                    'Het btw-bedrag moet in euro\'s op de factuur staan, ook als je in een andere valuta factureert.',
                ]],
                ['De vereenvoudigde factuur (tot € 100)', [
                    'Voor kleine bedragen — tot € 100 inclusief btw — mag een vereenvoudigde factuur: naam en adres van jou als leverancier, de factuurdatum, een omschrijving van wat je leverde en het te betalen btw-bedrag (of de gegevens om dat te berekenen) volstaan. Een kassabon is daar een voorbeeld van. Voor afstandsverkopen en leveringen aan andere EU-landen mag dit niet.',
                ]],
                ['Wanneer moet je factureren?', [
                    'Aan ondernemers en rechtspersonen ben je verplicht een factuur te sturen. De factuur moet uiterlijk op de 15e dag van de maand ná de maand van levering verstuurd zijn. Aan particulieren hoef je meestal geen factuur te sturen (uitzonderingen daargelaten, zoals afstandsverkopen).',
                ]],
                ['Bewaarplicht: 7 jaar', [
                    'Je moet je facturen — verkoop én inkoop — minimaal 7 jaar bewaren; gegevens over onroerende zaken zelfs 10 jaar. Digitaal bewaren mag, zolang de administratie binnen redelijke tijd controleerbaar is.',
                ]],
                ['Veelgemaakte fouten', [
                    'De klassiekers: een factuurnummer dat niet opeenvolgend is of dubbel voorkomt, het btw-nummer vergeten, geen uitsplitsing per btw-tarief, en "btw verlegd" vermelden zonder het btw-nummer van de afnemer erbij. Een onjuiste factuur kan bij jouw klant de btw-aftrek in gevaar brengen — en levert jou gedoe op bij een controle.',
                ]],
            ],
        ],

        'factuurnummering' => [
            'category' => 'Factureren',
            'title' => 'Factuurnummers: de regels en een goed systeem',
            'intro' => 'Factuurnummers moeten opeenvolgend en uniek zijn — maar hoe richt je dat handig in? De regels, de valkuilen en drie bewezen nummeringssystemen.',
            'sections' => [
                ['Wat de Belastingdienst eist', [
                    'Factuurnummers moeten doorlopend (opeenvolgend) zijn, met elk nummer uniek. Er mogen geen gaten in de reeks vallen en je mag een nummer nooit twee keer gebruiken. De Belastingdienst moet aan je administratie kunnen zien dat er geen facturen "verdwenen" zijn.',
                    'Meerdere reeksen naast elkaar mag wél — bijvoorbeeld een aparte reeks per boekjaar of per handelsnaam — zolang elke reeks zelf doorlopend is.',
                ]],
                ['Drie systemen die goed werken', [
                    'Doorlopend nummeren: 1, 2, 3, … Simpel en altijd goed. Nadeel: je klant ziet precies hoeveel facturen je stuurt.',
                    'Jaartal plus volgnummer: 2026-001, 2026-002, … Verreweg het populairst: overzichtelijk, elk jaar een frisse start, en het jaartal helpt bij het archiveren.',
                    'Jaartal, maand en volgnummer: 202608-01. Handig bij grote aantallen facturen per maand, maar voor de meeste zzp\'ers onnodig complex.',
                ]],
                ['Mag ik met een hoger nummer beginnen?', [
                    'Ja. Je hoeft niet bij 1 te beginnen — starten met bijvoorbeeld 2026-047 mag gewoon, zolang de reeks daarná netjes doorloopt. Veel starters doen dit om niet met "factuur 1" bij hun eerste klant aan te komen.',
                ]],
                ['Wat als een factuur vervalt?', [
                    'Een verstuurde factuur "weggooien" kan niet — dan valt er een gat in je reeks. Is een factuur onjuist of gaat de opdracht niet door, dan maak je een creditfactuur die de oorspronkelijke factuur (geheel of gedeeltelijk) tegenboekt. Zo blijft de reeks kloppend én is voor iedereen te volgen wat er gebeurd is.',
                ]],
                ['Laat het nummeren aan je software over', [
                    'Handmatig nummeren in Word of Excel gaat vroeg of laat mis: een dubbel nummer, een vergeten factuur, een gat in de reeks. Facturatiesoftware houdt de reeks automatisch bij en maakt van elke correctie netjes een creditfactuur.',
                ]],
            ],
        ],

        'eerste-factuur-als-zzper' => [
            'category' => 'Factureren',
            'title' => 'Je eerste factuur sturen als zzp\'er: stappenplan',
            'intro' => 'Eerste opdracht binnen? Zo stuur je een factuur die klopt — van de verplichte gegevens tot de betalingstermijn, in zes stappen.',
            'sections' => [
                ['Stap 1: check je basisgegevens', [
                    'Voor je eerste factuur heb je nodig: je KvK-nummer (krijg je bij inschrijving), je btw-identificatienummer (krijg je van de Belastingdienst na je KvK-inschrijving) en een zakelijke bankrekening — die laatste is niet verplicht, maar houdt privé en zakelijk gescheiden.',
                ]],
                ['Stap 2: bepaal je btw-tarief', [
                    'De meeste diensten vallen onder het algemene tarief van 21%. Sommige branches gebruiken 9% (onder meer voedingsmiddelen en kappers) en bij export of EU-leveringen kan 0% of "btw verlegd" gelden. Doe je mee met de kleineondernemersregeling (KOR), dan factureer je zónder btw en vermeld je de vrijstelling op de factuur.',
                ]],
                ['Stap 3: zet de verplichte gegevens erop', [
                    'Beide bedrijfsnamen en adressen, je KvK- en btw-nummer, een uniek opeenvolgend factuurnummer, factuurdatum, een duidelijke omschrijving (wat, hoeveel, wanneer), bedrag exclusief btw, het btw-tarief en -bedrag, en het totaal. Twijfel je? Gebruik een generator of facturatieprogramma dat de verplichte velden afdwingt.',
                ]],
                ['Stap 4: kies een betalingstermijn', [
                    'Gebruikelijk is 14 of 30 dagen. Spreek je niets af, dan geldt wettelijk 30 dagen. Zet de termijn expliciet op de factuur ("Graag betalen vóór 15 september 2026") — een concrete datum werkt beter dan "binnen 14 dagen".',
                ]],
                ['Stap 5: verstuur als PDF', [
                    'Stuur je factuur als PDF per e-mail — nooit als Word-bestand dat de ontvanger kan aanpassen. Zet het factuurnummer en je bedrijfsnaam in de bestandsnaam en de onderwerpregel, dat scheelt jouw klant (en zijn boekhouder) zoekwerk.',
                ]],
                ['Stap 6: bewaak de betaling', [
                    'Zet een herinnering in je agenda voor de vervaldatum — of gebruik software die automatisch herinnert. Te laat betaald? Een vriendelijke herinnering na een paar dagen lost het meestal op. Blijft het liggen, dan mag je wettelijke rente en incassokosten rekenen.',
                ]],
            ],
        ],

        // ---------- BTW & BELASTINGDIENST ----------
        'btw-tarieven' => [
            'category' => 'Btw & Belastingdienst',
            'title' => 'Btw-tarieven: wanneer reken je 21%, 9% of 0%?',
            'intro' => 'Nederland kent drie btw-tarieven en een handvol vrijstellingen. Zo weet je welk tarief jij op je factuur zet — en wat het verschil is tussen 0% en vrijgesteld.',
            'sections' => [
                ['21%: het algemene tarief', [
                    'Het uitgangspunt is simpel: alles is belast met 21%, tenzij de wet een uitzondering maakt. Vrijwel alle diensten van zzp\'ers — advies, ontwerp, IT, bouw, marketing, coaching (niet-medisch) — vallen onder het algemene tarief.',
                ]],
                ['9%: het verlaagde tarief', [
                    'Het verlaagde tarief geldt voor een beperkte lijst, waaronder voedingsmiddelen, boeken en tijdschriften (ook digitaal), geneesmiddelen, kappersdiensten, en reparatie van fietsen, schoenen en kleding. Sta je niet duidelijk op de lijst? Ga dan uit van 21% of vraag het na — het verlaagde tarief is de uitzondering, niet de regel.',
                ]],
                ['0%: vooral voor het buitenland', [
                    'Het 0%-tarief geldt vooral bij export buiten de EU en bij leveringen aan btw-plichtige ondernemers in andere EU-landen (intracommunautaire leveringen). Je brengt dan geen Nederlandse btw in rekening, maar houdt wél recht op aftrek van je voorbelasting. Bij diensten aan EU-ondernemers wordt de btw meestal "verlegd": je klant geeft de btw in zijn eigen land aan, en jij zet "btw verlegd" plus zijn btw-nummer op de factuur.',
                ]],
                ['Vrijgesteld is iets anders dan 0%', [
                    'Sommige branches zijn vrijgesteld van btw — onder meer zorg, onderwijs en verzekeringen. Het grote verschil met 0%: wie vrijgesteld presteert, mag de btw op eigen inkopen níet aftrekken. Bij 0% mag dat wel. Ook wie meedoet met de kleineondernemersregeling (KOR) factureert zonder btw en verliest de aftrek.',
                ]],
                ['Meerdere tarieven op één factuur', [
                    'Dat mag gewoon: splits per tarief het bedrag exclusief btw en het btw-bedrag uit. Goede facturatiesoftware doet dat automatisch zodra je per factuurregel een tarief kiest.',
                ]],
                ['En de btw-aangifte?', [
                    'Elk kwartaal geef je de btw over je omzet aan en trek je de btw op je zakelijke inkopen (voorbelasting) af. Wie zijn verkoop- en inkoopfacturen netjes bijhoudt, leest de aangifte zó uit zijn administratie af. Bewaar de onderbouwing: bij een controle wil de Belastingdienst de facturen achter de cijfers zien.',
                ]],
            ],
        ],

        'kleineondernemersregeling-kor' => [
            'category' => 'Btw & Belastingdienst',
            'title' => 'De KOR: factureren zonder btw — slim of niet?',
            'intro' => 'Blijft je omzet onder de € 20.000 per jaar, dan kun je met de kleineondernemersregeling (KOR) zonder btw factureren. Zo werkt het — en zo bepaal je of het voor jou gunstig is.',
            'sections' => [
                ['Wat de KOR inhoudt', [
                    'Doe je mee met de kleineondernemersregeling, dan ben je vrijgesteld van btw: je berekent geen btw aan klanten, doet geen btw-aangifte en vermeldt op je facturen dat de levering onder de vrijstelling valt. Voorwaarde is dat je omzet in een kalenderjaar onder de € 20.000 blijft.',
                    'De keerzijde: je mag de btw die jíj betaalt over zakelijke inkopen niet meer aftrekken.',
                ]],
                ['Wanneer is de KOR gunstig?', [
                    'Vooral als je klanten particulieren zijn. Zonder btw ben je voor hen effectief goedkoper (of jij houdt meer marge over). Lever je vooral aan bedrijven, dan schiet je er weinig mee op: zakelijke klanten trekken de btw toch af — terwijl jij je aftrek op inkopen verliest.',
                    'Grote investeringen op de planning (apparatuur, een bus, verbouwing)? Dan kan de KOR juist ongunstig zijn, omdat je de btw daarover niet terugkrijgt.',
                ]],
                ['Aan- en afmelden', [
                    'Je meldt je aan bij de Belastingdienst; sinds 2025 is de regeling flexibeler geworden en kun je je ook weer afmelden als de KOR niet meer past. Groei je door de omzetgrens van € 20.000 heen, dan vervalt de vrijstelling vanaf dat moment en factureer je vanaf die levering mét btw. Houd je omzet dus goed in de gaten.',
                    'Sinds 2025 bestaat er ook een EU-variant van de KOR, waarmee je onder voorwaarden de vrijstelling in andere EU-landen kunt gebruiken. De details luisteren nauw — check de site van de Belastingdienst voor de actuele voorwaarden.',
                ]],
                ['Wat zet je op je factuur?', [
                    'Een factuur zonder btw met een duidelijke vermelding van de vrijstelling, bijvoorbeeld: "Factuur vrijgesteld van omzetbelasting op grond van de kleineondernemersregeling (artikel 25 Wet OB)". Zet er géén btw-bedrag of tarief op — wie btw vermeldt, moet die ook afdragen.',
                ]],
                ['Blijf wél administreren', [
                    'Ook met de KOR houd je gewoon een administratie bij: facturen met doorlopende nummers, je omzet (om de grens te bewaken) en je kosten voor de inkomstenbelasting. De KOR is een btw-vrijstelling — geen vrijstelling van administratie.',
                ]],
            ],
        ],

        // ---------- BETAALD KRIJGEN ----------
        'betalingstermijn-aanmanen' => [
            'category' => 'Betaald krijgen',
            'title' => 'Betalingstermijnen, herinneren en aanmanen: zo krijg je betaald',
            'intro' => 'Van de wettelijke betalingstermijn tot incassokosten: dit zijn de regels als een klant niet betaalt — en de aanpak die facturen daadwerkelijk betaald krijgt.',
            'sections' => [
                ['De wettelijke betalingstermijnen', [
                    'Spreek je niets af, dan geldt tussen bedrijven een betalingstermijn van 30 dagen. Afwijken mag: tot 60 dagen kun je gewoon overeenkomen. Grote ondernemingen mogen aan mkb-leveranciers en zzp\'ers wettelijk maximaal 30 dagen hanteren.',
                    'Tip: zet op je factuur een concrete vervaldatum ("graag vóór 15 september") in plaats van "binnen 14 dagen" — dat voorkomt rekenwerk en discussie.',
                ]],
                ['De vervaldatum is verstreken — wat nu?', [
                    'Begin vriendelijk: veruit de meeste late betalingen zijn vergeetachtigheid, geen onwil. Een korte herinnering ("wellicht aan je aandacht ontsnapt — hierbij nogmaals de factuur") binnen een week na de vervaldatum lost het meestal op. Stuur de factuur altijd opnieuw mee.',
                    'Blijft betaling uit, stuur dan een tweede, zakelijkere herinnering met een duidelijke termijn ("binnen 7 dagen"). Bellen werkt vaak beter dan een derde mail: het is persoonlijker en je hoort meteen of er iets anders speelt, zoals een dispuut over het werk.',
                ]],
                ['Rente en incassokosten: je staat sterker dan je denkt', [
                    'Bij zakelijke klanten treedt na de vervaldatum automatisch verzuim in: je mag wettelijke handelsrente rekenen en incassokosten in rekening brengen. De vergoeding voor incassokosten is wettelijk geregeld (de WIK-staffel) en begint bij € 40 — ook als je factuur klein is.',
                    'Bij particuliere klanten moet je eerst een kosteloze aanmaning sturen met een termijn van minimaal 14 dagen ("veertiendagenbrief") waarin je de incassokosten aankondigt, vóór je die daadwerkelijk mag rekenen.',
                ]],
                ['De laatste stap: incasso of rechter', [
                    'Helpt ook een formele aanmaning niet, dan kun je naar een incassobureau of — bij een onbetwiste vordering — zelf naar de kantonrechter. Weeg de kosten en de relatie af: soms is een betalingsregeling in termijnen voor beide kanten de beste uitkomst. Leg elke afspraak schriftelijk vast.',
                ]],
                ['Voorkomen is beter', [
                    'Factureer direct na levering (niet eind van de maand), maak betalen makkelijk met een betaallink, hanteer voor nieuwe of grote klanten een aanbetaling, en herinner automatisch. Wie binnen een week na de vervaldatum herinnert, wordt aantoonbaar sneller betaald dan wie weken wacht.',
                ]],
            ],
        ],
    ],
];
