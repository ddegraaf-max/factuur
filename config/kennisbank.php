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

        // ---------- MEER ARTIKELEN (3 september 2026) ----------
        'eindfactuur' => [
            'category' => 'Factureren',
            'title' => "Eindfactuur: wat het is en hoe je hem opstelt na deelfacturen",
            'intro' => "Heb je een opdracht in termijnen gefactureerd, dan sluit je af met een eindfactuur. Wat erop moet staan, hoe je eerdere termijnen verrekent en welke fouten je klant en je boekhouder gek maken.",
            'sections' => [
                ["Wat is een eindfactuur?", [
                    "Een eindfactuur is de laatste factuur van een opdracht waarvoor je al eerder één of meer deelfacturen, termijnfacturen of een voorschotfactuur stuurde. Hij maakt de balans op: het totale bedrag van de opdracht, wat je al in rekening bracht, en wat de klant nog moet betalen.",
                    "Fiscaal is een eindfactuur een gewone factuur. Hij moet dus aan alle factuureisen voldoen, een opeenvolgend nummer krijgen en de btw correct vermelden over het bedrag dat je nú factureert.",
                ]],
                ["Wat er op een eindfactuur staat", [
                    "Naast de gebruikelijke gegevens (jouw gegevens, die van je klant, factuurnummer en -datum) zet je er een korte samenvatting van de opdracht op: de totale aanneemsom of het totaal van de gewerkte uren, exclusief btw.",
                    "Daaronder zet je de eerder gefactureerde termijnen, elk met factuurnummer, datum en bedrag exclusief btw. Het verschil tussen het totaal en die termijnen is het bedrag van de eindfactuur. Daarover reken je btw, zoals altijd uitgesplitst per tarief.",
                    "Vermeld duidelijk welke termijnen al betaald zijn. Een klant die nog een termijn open heeft staan, moet dat op de eindfactuur kunnen zien, anders krijg je vragen of, erger, een halve betaling.",
                ]],
                ["Btw op de eindfactuur", [
                    "Je rekent alleen btw over het bedrag dat je met de eindfactuur in rekening brengt. Over de eerdere termijnen heb je die btw al afgedragen in het tijdvak waarin je die facturen stuurde. Trek je de termijnen inclusief btw af van een totaal inclusief btw, dan klopt het meestal ook, maar de uitsplitsing exclusief btw, btw en totaal blijft verplicht.",
                    "Is het btw-tarief tussentijds veranderd, dan geldt voor de eindfactuur het tarief op het moment van de prestatie. Dat komt zelden voor, maar bij lange bouw- of ontwikkeltrajecten gebeurt het.",
                ]],
                ["Meerwerk en minderwerk", [
                    "Werk dat buiten de oorspronkelijke afspraak viel, zet je apart op de eindfactuur als meerwerk, met een korte omschrijving en de afgesproken prijs. Minderwerk trek je af. Zo ziet je klant precies waar het verschil met de offerte vandaan komt, en dat scheelt discussie.",
                    "Leg meerwerk altijd vooraf schriftelijk vast, al is het maar een e-mail. Op de eindfactuur is het te laat om er nog over te onderhandelen.",
                ]],
                ["Veelgemaakte fouten", [
                    "De klassieke fout is een eindfactuur die het hele bedrag nog een keer noemt, zonder de termijnen ervan af te trekken. De klant ziet dan een veel te hoog totaal en belt. De tweede fout: termijnen opnemen zonder factuurnummer, waardoor niemand kan nagaan welke facturen bedoeld zijn.",
                    "Facturatiesoftware die termijnen kent, maakt de eindfactuur automatisch: de eerdere facturen staan er al op, het restant wordt uitgerekend en de btw klopt per definitie.",
                ]],
            ],
        ],

        'deelfactuur-termijnfactuur' => [
            'category' => 'Factureren',
            'title' => "Deelfactuur en termijnfactuur: in delen factureren zonder gedoe",
            'intro' => "Bij een grote opdracht wil je niet maanden op je geld wachten. Met deelfacturen of termijnen factureer je tussentijds. Zo spreek je het af, zo zet je het op de factuur en zo blijft de btw kloppen.",
            'sections' => [
                ["Deelfactuur of termijnfactuur?", [
                    "In de praktijk betekenen ze hetzelfde: je factureert een deel van de opdracht voordat alles klaar is. Termijnfactuur is het gebruikelijke woord in de bouw en bij aanneemsommen (termijn 1 bij start, termijn 2 bij oplevering ruwbouw, enzovoort). Deelfactuur zie je vaker bij projecten op uurbasis of bij mijlpalen.",
                    "Een voorschotfactuur is iets anders: die stuur je vóórdat je begint, als aanbetaling. Daarover lees je in een apart artikel.",
                ]],
                ["Spreek het vooraf af", [
                    "Zet de termijnen in je offerte of opdrachtbevestiging: hoeveel termijnen, welk percentage of bedrag, en op welk moment. Bijvoorbeeld 30% bij opdracht, 40% bij oplevering van het concept en 30% bij afronding. Een klant die dit vooraf accepteert, betaalt zonder discussie.",
                    "Koppel termijnen aan een meetbaar moment, niet aan een datum. Loopt het project uit, dan schuift de termijn mee en hoef je niets uit te leggen.",
                ]],
                ["Zo ziet een deelfactuur eruit", [
                    "Een deelfactuur is een gewone factuur met een eigen opeenvolgend nummer, alle verplichte gegevens en btw over het gefactureerde deel. Zet in de omschrijving om welke termijn het gaat en waar hij bij hoort: termijn 2 van 3, project Nieuwe website, conform offerte 2026-014.",
                    "Vermeld ook het totaal van de opdracht en wat al gefactureerd is. Dat is niet verplicht, maar het voorkomt dat je klant de termijn voor het totaal aanziet.",
                ]],
                ["Btw per termijn", [
                    "Elke deelfactuur draag je btw over af in het tijdvak waarin je hem stuurt, niet pas bij de eindfactuur. Factureer je een termijn in maart, dan zit die btw in de aangifte over het eerste kwartaal. Dat geldt ook als de klant pas later betaalt, tenzij je het kasstelsel mag toepassen.",
                ]],
                ["Uren tussentijds factureren", [
                    "Werk je op uurbasis, dan is maandelijks factureren de eenvoudigste vorm van deelfactureren: elke maand de gewerkte uren, met een urenspecificatie erbij. Spreek dat vooraf af en houd je uren bij vanaf dag één, dan is de factuur op de eerste van de maand in vijf minuten klaar.",
                ]],
                ["De eindfactuur", [
                    "Na de laatste termijn stuur je de eindfactuur: het totaal, de eerdere termijnen ervan af, het restant met btw. Facturatiesoftware die termijnen ondersteunt, houdt bij wat er al gefactureerd en betaald is en maakt de eindfactuur automatisch.",
                ]],
            ],
        ],

        'voorschotfactuur-aanbetaling' => [
            'category' => 'Factureren',
            'title' => "Voorschotfactuur en aanbetaling: regels, btw en een voorbeeld",
            'intro' => "Een aanbetaling vragen is normaal bij grotere opdrachten of nieuwe klanten. Maar hoe factureer je een voorschot, wanneer draag je de btw af en hoe verreken je het later?",
            'sections' => [
                ["Wanneer vraag je een voorschot?", [
                    "Een voorschot dekt je risico: je koopt materiaal in, reserveert tijd of werkt voor een klant die je nog niet kent. Gebruikelijk is 20 tot 50 procent bij opdracht. Bij maatwerk dat je nergens anders kwijt kunt, mag het meer zijn.",
                    "Zet het voorschot in je offerte. Een klant die de offerte accepteert, accepteert daarmee ook de aanbetaling. Begin pas met het werk als het voorschot binnen is; dat is precies waar het voor bedoeld is.",
                ]],
                ["Een voorschotfactuur is een echte factuur", [
                    "Je stuurt voor het voorschot een gewone factuur met een opeenvolgend nummer, alle verplichte gegevens en btw. In de omschrijving zet je waar het voorschot voor is: aanbetaling 30% voor project X, conform offerte 2026-021. Een proforma factuur is hiervoor niet geschikt: die heeft geen btw-gevolgen en telt niet mee in je administratie.",
                ]],
                ["Btw over een voorschot", [
                    "Over een vooruitbetaling ben je de btw verschuldigd op het moment dat je de factuur stuurt, of eerder als de klant al betaalt. Je draagt die btw dus af in het tijdvak van de voorschotfactuur, niet pas als het werk klaar is. Dat verrast starters nog weleens: het geld is binnen, maar een deel is van de Belastingdienst.",
                ]],
                ["Verrekenen op de eindfactuur", [
                    "Als de opdracht klaar is, stuur je de eindfactuur voor het hele bedrag en trek je het voorschot exclusief btw ervan af. Over het restant reken je btw. Vermeld het factuurnummer en de datum van de voorschotfactuur, zodat de koppeling voor iedereen duidelijk is.",
                    "Voorbeeld: opdracht van 5.000 euro exclusief btw, voorschot 30% is 1.500 euro plus 315 euro btw. Eindfactuur: 5.000 euro totaal, minus 1.500 euro voorschot, is 3.500 euro plus 735 euro btw. De klant betaalt in totaal 6.050 euro, precies 5.000 euro plus 21%.",
                ]],
                ["Als de opdracht niet doorgaat", [
                    "Gaat de opdracht na het voorschot alsnog niet door, dan maak je een creditnota voor de voorschotfactuur en betaal je terug wat je niet hebt verdiend. Mag je een deel houden, bijvoorbeeld voor al gemaakte kosten, dan crediteer je alleen het verschil. De btw die je over het voorschot afdroeg, krijg je via de creditnota in de volgende aangifte terug.",
                ]],
                ["Consumenten en de wet", [
                    "Bij particulieren mag je een aanbetaling vragen, maar de wet beperkt bij sommige consumentenkoop de hoogte tot de helft van de prijs. Bij zakelijke klanten geldt die grens niet. Zet bij consumenten de afspraak altijd op papier, inclusief wat er gebeurt bij annulering.",
                ]],
            ],
        ],

        'proforma-factuur' => [
            'category' => 'Factureren',
            'title' => "Proforma factuur: wat is het en wanneer gebruik je hem?",
            'intro' => "Een proforma factuur ziet eruit als een factuur, maar is er geen. Wanneer hij nuttig is, wat het verschil met een offerte is, en waarom je hem nooit in je factuurreeks mag opnemen.",
            'sections' => [
                ["Wat een proforma factuur is", [
                    "Pro forma betekent voor de vorm. Een proforma factuur laat zien hoe de echte factuur eruit gaat zien: dezelfde bedragen, dezelfde btw, dezelfde opmaak. Maar hij is geen factuur in de zin van de btw-wetgeving. Je draagt er geen btw over af, je klant mag er geen btw mee terugvragen en hij krijgt geen nummer uit je factuurreeks.",
                ]],
                ["Wanneer is hij nuttig?", [
                    "Bij export buiten de EU vraagt de douane om een proforma factuur voor de waarde van de zending, nog voordat de echte factuur bestaat. Ook bij vooruitbetaling zonder btw-gevolgen wordt hij gebruikt: de klant ziet wat hij moet betalen, jij factureert pas echt bij levering.",
                    "Sommige klanten, vooral grote organisaties, willen een proforma om intern een inkooporder aan te maken. Dan is de proforma eigenlijk een offerte in factuurvorm.",
                ]],
                ["Proforma of offerte?", [
                    "In de meeste gevallen is een offerte beter. Een offerte is een aanbod dat je klant accepteert, en daarmee een juridische basis voor de opdracht. Een proforma factuur heeft die status niet, maar wekt wel de indruk dat er iets betaald moet worden. Gebruik de proforma dus alleen als er expliciet om gevraagd wordt.",
                ]],
                ["Zo maak je hem goed", [
                    "Zet groot en duidelijk PROFORMA op het document, en de zin: dit is geen factuur; hier kan geen btw op worden teruggevraagd. Geef hem geen nummer uit je factuurreeks. Gebruik een eigen aanduiding, bijvoorbeeld PF-2026-003, of laat het nummer weg.",
                    "Zet er wel alle gegevens op die op de echte factuur komen: beide partijen, omschrijving, bedragen exclusief en inclusief btw. Anders heeft de ontvanger er niets aan.",
                ]],
                ["De veelgemaakte fout", [
                    "De grootste fout is een proforma factuur die in de administratie terechtkomt alsof het een echte is. Dan draag je btw af over iets wat je nog niet hebt geleverd, of, andersom, denkt je klant dat hij betaald heeft terwijl de echte factuur nog moet komen. Houd proforma's buiten je factuurreeks en stuur bij levering altijd de echte factuur, met verwijzing naar de proforma.",
                ]],
            ],
        ],

        'creditnota-maken' => [
            'category' => 'Factureren',
            'title' => "Creditnota maken: wanneer, hoe en met welk nummer",
            'intro' => "Een verstuurde factuur kun je niet weggooien of aanpassen. Fout gemaakt, korting achteraf of retour? Dan maak je een creditnota. Zo doe je dat goed, ook voor de btw.",
            'sections' => [
                ["Wat een creditnota is", [
                    "Een creditnota (creditfactuur) is een factuur met een negatief bedrag die een eerdere factuur geheel of gedeeltelijk ongedaan maakt. Hij krijgt een eigen, opeenvolgend nummer uit je gewone factuurreeks en verwijst naar de oorspronkelijke factuur. Zo blijft je reeks sluitend en kan iedereen, ook de Belastingdienst, volgen wat er is gebeurd.",
                ]],
                ["Wanneer maak je een creditnota?", [
                    "Bij een fout op de factuur: verkeerd bedrag, verkeerde klant, verkeerd btw-tarief. Bij een annulering nadat de factuur al verstuurd is. Bij retour van goederen of een korting achteraf. En bij een deel dat je kwijtscheldt, bijvoorbeeld omdat een klacht terecht was.",
                    "Een factuur die nog niet verstuurd is, hoef je niet te crediteren; die pas je gewoon aan. Het gaat om facturen die de deur uit zijn, ook als de klant ze nog niet betaald heeft.",
                ]],
                ["Zo ziet een creditnota eruit", [
                    "Zet er duidelijk creditnota of creditfactuur op. Vermeld het nummer en de datum van de oorspronkelijke factuur. Neem dezelfde regels op als op die factuur, maar met een minteken, of alleen de regels die je crediteert bij een gedeeltelijke creditering. Splits de btw uit per tarief, net als op een gewone factuur.",
                    "Voeg een korte reden toe: retour twee stuks, korting wegens te late levering, factuur 2026-031 vervalt. Dat scheelt vragen van je klant en van je boekhouder.",
                ]],
                ["Btw op de creditnota", [
                    "De btw op de creditnota trek je af van de btw die je moet afdragen in het tijdvak waarin je de creditnota stuurt. Heb je de oorspronkelijke btw al aangegeven, dan corrigeert de creditnota dat dus in de volgende aangifte. Je klant doet het omgekeerde en verlaagt zijn voorbelasting.",
                ]],
                ["Terugbetalen of verrekenen", [
                    "Heeft de klant al betaald, dan betaal je het bedrag terug of verreken je het met een volgende factuur. Spreek dat af en zet het op de creditnota. Is de factuur nog niet betaald, dan betaalt de klant simpelweg het saldo van factuur en creditnota.",
                ]],
                ["Nieuwe factuur na een fout", [
                    "Was de hele factuur fout, dan crediteer je hem volledig en stuur je een nieuwe, juiste factuur met een nieuw nummer. Stuur nooit een aangepaste versie onder hetzelfde nummer: dan bestaan er twee verschillende facturen met één nummer, en dat is precies wat de Belastingdienst niet wil zien.",
                ]],
            ],
        ],

        'factuur-zonder-kvk' => [
            'category' => 'Factureren',
            'title' => "Factureren zonder KVK-inschrijving: mag dat?",
            'intro' => "Je hebt een klus gedaan en wilt een factuur sturen, maar je staat (nog) niet bij de KVK ingeschreven. Wat mag, wat moet, en wanneer je je wél moet inschrijven.",
            'sections' => [
                ["Een factuur sturen mag iedereen", [
                    "Een factuur is een betaalverzoek met een specificatie. Daar heb je geen KVK-nummer voor nodig. Heb je een eenmalige klus gedaan, dan mag je daar een factuur voor sturen. Alleen: zonder inschrijving ben je meestal geen ondernemer voor de btw, en dan mag je géén btw op je factuur zetten.",
                ]],
                ["Wanneer moet je je inschrijven?", [
                    "Je moet je inschrijven zodra je een onderneming drijft: je levert regelmatig goederen of diensten, vraagt daar een vergoeding voor die meer is dan een kostenvergoeding, en doet dat zelfstandig. Structureel factureren zonder inschrijving is dus niet de bedoeling, en de Belastingdienst en KVK kijken daar naar.",
                    "Twijfel je? De KVK beoordeelt bij de inschrijving of je aan de criteria voldoet. Inschrijven kost een eenmalig bedrag en je krijgt vrijwel meteen je KVK-nummer en, via de Belastingdienst, je btw-nummers.",
                ]],
                ["Wat zet je op een factuur zonder KVK?", [
                    "Je naam en adres, de naam en het adres van je klant, een datum, een omschrijving van wat je deed en het bedrag. Geen btw, geen btw-nummer, geen KVK-nummer. Zet er eventueel bij: niet btw-plichtig. Gebruik wel een volgnummer, zodat je zelf overzicht houdt.",
                ]],
                ["En de inkomstenbelasting?", [
                    "Inkomsten zonder onderneming geef je aan als resultaat uit overig werk. Je betaalt er gewoon inkomstenbelasting over, maar je hebt geen recht op ondernemersaftrek zoals de zelfstandigenaftrek. Kosten die je voor de klus maakte, mag je wel aftrekken. Bewaar de factuur en de bonnen.",
                ]],
                ["Waarom inschrijven vaak beter is", [
                    "Met een inschrijving kun je btw terugvragen over je kosten, doe je mee met de KOR als je klein blijft, kom je in aanmerking voor ondernemersaftrek en kun je zakelijke klanten een normale factuur sturen. Veel bedrijven betalen liever geen factuur zonder KVK-nummer, omdat ze dan het risico lopen dat de fiscus het als loondienst ziet.",
                ]],
                ["Zzp via een platform of payroll", [
                    "Werk je via een bemiddelingsplatform of payrollbedrijf, dan factureert dat bedrijf vaak namens jou of betaalt het je als werknemer uit. Dan hoef je zelf niet te factureren. Check wel wat er in je overeenkomst staat: wie factureert, wie draagt de btw af en wie is aansprakelijk.",
                ]],
            ],
        ],

        'factuur-app-kiezen' => [
            'category' => 'Factureren',
            'title' => "Een factuur-app kiezen als zzp'er: waar let je op?",
            'intro' => "Er zijn tientallen factuur-apps. De verschillen zitten niet in het maken van de factuur, maar in wat eromheen gebeurt: herinneringen, btw, bonnen, je boekhouder en wat het je écht kost. Een checklist.",
            'sections' => [
                ["Begin bij wat je nu fout gaat", [
                    "De meeste zzp'ers stappen niet over omdat ze een mooiere factuur willen, maar omdat ze geld mislopen: vergeten te factureren, te laat herinnerd, btw-aangifte in een paniekweekend. Kies een app op de dingen die je nu tijd of geld kosten, niet op het aantal functies in de vergelijkingstabel.",
                ]],
                ["De factuur zelf", [
                    "Vanzelfsprekend, maar check het: voldoet de factuur automatisch aan de wettelijke eisen, nummert de app doorlopend, kan hij creditnota's en termijnen aan, en kun je meerdere btw-tarieven op één factuur zetten? Kan de klant direct betalen via een betaallink of iDEAL? Dat laatste scheelt gemiddeld dagen op je betaaltermijn.",
                ]],
                ["Betaald krijgen", [
                    "Automatische herinneringen zijn de functie die zichzelf het snelst terugverdient. Kijk of je de teksten en de momenten zelf kunt instellen, of de app ziet wanneer een factuur betaald is (bankkoppeling) en of je bij hardnekkige wanbetalers direct kunt doorzetten naar incasso.",
                ]],
                ["Btw en je boekhouder", [
                    "Kan de app je btw-aangifte per kwartaal klaarzetten, inclusief de voorbelasting uit je inkoopfacturen? Kun je bonnen fotograferen en laat de app ze uitlezen? En kan je boekhouder gratis meekijken of een export in een standaardformaat krijgen? Een app die alleen verkoopfacturen doet, laat je elk kwartaal alsnog met Excel zitten.",
                ]],
                ["De echte prijs", [
                    "Let op verborgen kosten: een limiet op het aantal facturen of klanten, betaald opslag, een toeslag per betaallink, extra kosten voor een tweede gebruiker of voor de bankkoppeling. Reken uit wat je betaalt bij het aantal facturen dat je over twee jaar verwacht, niet bij het aantal van nu.",
                    "Kijk ook naar opzeggen: krijg je je gegevens mee als je weggaat, en in welk formaat? Een app die je data gijzelt, is duurder dan hij lijkt.",
                ]],
                ["Praktische checks", [
                    "Werkt hij op je telefoon, in het Nederlands, met Nederlandse betaalmethoden en Nederlandse btw-regels? Staat je data in de EU en is er een verwerkersovereenkomst? Is er iemand die je kunt mailen als het misgaat op de avond voor de aangifte? Probeer twee of drie apps een week naast elkaar met dezelfde echte factuur; dan weet je het.",
                ]],
            ],
        ],

        'terugkerende-facturen-abonnementen' => [
            'category' => 'Factureren',
            'title' => "Terugkerende facturen: maandelijks factureren en abonnementen",
            'intro' => "Vaste klanten, onderhoudscontracten, abonnementen: elke maand dezelfde factuur maken is zonde van je tijd en foutgevoelig. Zo richt je terugkerende facturatie in, van afspraak tot automatische incasso.",
            'sections' => [
                ["Wanneer terugkerend factureren past", [
                    "Bij alles wat je periodiek levert tegen een vaste prijs: hosting en onderhoud, een strippenkaart, een vast aantal uren per maand, een abonnement op je dienst, huur van apparatuur. Ook bij een langlopend project op uurbasis is een vaste maandfactuur vaak beter dan één grote aan het eind.",
                ]],
                ["Leg de afspraak vast", [
                    "Zet in je offerte of overeenkomst: wat je levert, de prijs per periode, de periode (maand, kwartaal, jaar), of je vooraf of achteraf factureert, de betaaltermijn, hoe je indexeert en wat de opzegtermijn is. Vooraf factureren is voor jou het prettigst; achteraf is bij variabele uren logischer.",
                ]],
                ["Btw per periode", [
                    "Elke periodieke factuur is een gewone factuur met een eigen nummer, en de btw hoort in het tijdvak waarin je factureert. Factureer je een jaarabonnement in één keer vooraf, dan draag je alle btw in dat kwartaal af. Bij een doorlopende dienst mag je ook per periode factureren; de btw volgt dan de facturen.",
                ]],
                ["Automatisch versturen", [
                    "Goede facturatiesoftware maakt van een sjabloon een profiel: klant, regels, prijs, frequentie, startdatum. Op de afgesproken dag wordt de factuur aangemaakt en verstuurd, met een nieuw nummer en de juiste periode in de omschrijving, bijvoorbeeld onderhoud september 2026. Jij ziet alleen nog een melding.",
                    "Zet altijd de periode in de omschrijving. Zonder periode kan je klant twee maandfacturen niet uit elkaar houden en betaalt hij er één dubbel of één niet.",
                ]],
                ["Automatische incasso", [
                    "Bij vaste bedragen is een SEPA-machtiging de logische volgende stap: je klant tekent één keer, en het bedrag wordt na elke factuur automatisch afgeschreven. Geen herinneringen meer, geen wachten. Je hebt er een machtiging met kenmerk voor nodig en je moet de klant vooraf informeren over de afschrijving; software met incasso-ondersteuning regelt beide.",
                ]],
                ["Prijs verhogen en opzeggen", [
                    "Verhoog je de prijs, kondig dat dan minimaal een maand van tevoren aan en pas het profiel aan vanaf de eerste nieuwe periode. Zegt de klant op, dan zet je het profiel stop na de laatste periode; een lopende periode factureer je gewoon uit, tenzij je anders hebt afgesproken.",
                ]],
            ],
        ],

        'btw-aangifte-kwartaal-stappenplan' => [
            'category' => 'Btw & Belastingdienst',
            'title' => "Btw-aangifte per kwartaal: stap voor stap (2026)",
            'intro' => "Elk kwartaal dezelfde vraag: welke rubriek, welk bedrag, wanneer betalen? Dit stappenplan loopt de aangifte omzetbelasting van begin tot eind door, inclusief de deadlines en de fouten die een boete opleveren.",
            'sections' => [
                ["De deadlines", [
                    "De aangifte én de betaling moeten binnen zijn op de laatste dag van de maand na het kwartaal: 30 april, 31 juli, 31 oktober en 31 januari. Te laat aangeven of betalen levert een verzuimboete op, ook als je niets hoeft te betalen. Heb je geen omzet gehad, dan doe je een nihilaangifte; overslaan mag niet.",
                    "Doe je liever per maand of per jaar aangifte, dan kun je dat bij de Belastingdienst aanvragen. Per jaar mag alleen bij een klein btw-bedrag; per maand is handig als je structureel btw terugkrijgt.",
                ]],
                ["Stap 1: je omzet per tarief", [
                    "Tel alle verkoopfacturen van het kwartaal op, gesplitst per btw-tarief. Rubriek 1a is de omzet met 21% en de btw daarover, 1b de omzet met 9%. Leveringen met 0% of naar het buitenland komen in 1e of in rubriek 3. Het gaat om de factuurdatum, niet om de betaaldatum, tenzij je het kasstelsel toepast.",
                ]],
                ["Stap 2: buitenland en verlegd", [
                    "Leveringen aan ondernemers in andere EU-landen zet je in 3b, export buiten de EU in 3a. Heb je zelf diensten of goederen uit het buitenland afgenomen waarbij de btw naar jou is verlegd, dan geef je die btw aan in 4a of 4b én trek je hem meestal weer af als voorbelasting. Per saldo betaal je dan niets, maar de aangifte moet het wel laten zien.",
                ]],
                ["Stap 3: voorbelasting", [
                    "In rubriek 5b vul je de btw in die je zelf betaalde over zakelijke inkopen en kosten: inkoopfacturen, bonnen, abonnementen, telefoon. Alleen met een factuur op naam die aan de eisen voldoet. Btw op eten en drinken in de horeca, en op zaken voor privégebruik, mag je niet aftrekken.",
                ]],
                ["Stap 4: het saldo en de betaling", [
                    "Verschuldigde btw (5a) min voorbelasting (5b) is wat je betaalt of terugkrijgt. Betaal je, gebruik dan het betalingskenmerk uit de aangifte; zonder kenmerk kan de Belastingdienst je betaling niet koppelen. Krijg je terug, dan staat het bedrag meestal binnen enkele weken op je rekening.",
                ]],
                ["Fout gemaakt?", [
                    "Kleine fouten corrigeer je in de volgende aangifte. Grotere correcties, boven een drempelbedrag, moeten via een suppletie. Doe dat zelf voordat de Belastingdienst het ontdekt; dan blijft de boete meestal uit.",
                ]],
                ["Zo wordt het routine", [
                    "De aangifte is alleen een klus als je administratie achterloopt. Verwerk je inkoopbonnen in de week dat je ze krijgt en factureer je op tijd, dan is het kwartaaloverzicht een kwestie van overnemen. Facturatiesoftware met een btw-overzicht zet de rubrieken voor je klaar en herinnert je aan de deadline.",
                ]],
            ],
        ],

        'factureren-buitenland-btw-verlegd' => [
            'category' => 'Btw & Belastingdienst',
            'title' => "Factureren naar het buitenland: btw verlegd, ICP en 0%",
            'intro' => "Een klant in Duitsland, een opdrachtgever in de VS: wat zet je op de factuur, reken je btw en waar geef je het aan? De regels voor zakelijke klanten binnen en buiten de EU, zonder juridisch jargon.",
            'sections' => [
                ["Zakelijke klant in de EU: btw verlegd", [
                    "Lever je diensten aan een ondernemer in een ander EU-land, dan is de btw meestal verlegd naar je klant. Je factureert zonder btw en zet op de factuur: btw verlegd, samen met het btw-identificatienummer van je klant én dat van jezelf. Controleer het nummer van je klant vooraf in het Europese VIES-register en bewaar het resultaat.",
                    "Voor goederen naar een EU-ondernemer geldt het 0%-tarief als intracommunautaire levering, onder dezelfde voorwaarden plus bewijs dat de goederen het land uit zijn.",
                ]],
                ["De ICP-opgaaf", [
                    "Leveringen en diensten aan EU-ondernemers meld je apart bij de Belastingdienst in de opgaaf intracommunautaire prestaties (ICP): per klant het btw-nummer en het totaalbedrag. De bedragen moeten kloppen met rubriek 3b van je btw-aangifte. Vergeet je de ICP, dan krijg je een herinnering en kan er een boete volgen.",
                ]],
                ["Zakelijke klant buiten de EU", [
                    "Diensten aan een ondernemer buiten de EU zijn in Nederland meestal niet belast: de plaats van dienst ligt bij je klant. Je factureert zonder btw en vermeldt dat de btw niet van toepassing is. Een ICP-opgaaf is hier niet nodig. Voor goederen geldt export met 0%, met bewijs van uitvoer, zoals douanedocumenten.",
                ]],
                ["Particulieren in het buitenland", [
                    "Aan particulieren in de EU reken je gewoon Nederlandse btw, behalve bij digitale diensten en afstandsverkopen boven de EU-drempel van 10.000 euro per jaar: dan geldt de btw van het land van de klant, aan te geven via het éénloketsysteem (OSS). Particulieren buiten de EU: diensten meestal zonder btw, goederen als export met 0%.",
                ]],
                ["Wat er op de factuur moet", [
                    "Alles wat op een gewone factuur hoort, plus: het btw-nummer van je klant bij verlegging, de aanduiding btw verlegd (of in het Engels: VAT reverse charged), en bij 0% een verwijzing naar de reden, bijvoorbeeld intracommunautaire levering. Factureren in een andere valuta mag, maar het btw-bedrag, als dat er is, moet ook in euro's staan.",
                ]],
                ["Valuta en betaling", [
                    "Factureer je in dollars of ponden, spreek dan af wie het koersrisico draagt en welke bankkosten voor wie zijn. Vermeld je IBAN en BIC. Een betaallink in de valuta van de klant scheelt vaak dagen en gedoe met internationale overboekingen.",
                ]],
            ],
        ],

        'btw-terugvragen-voorbelasting' => [
            'category' => 'Btw & Belastingdienst',
            'title' => "Btw terugvragen: welke kosten wel en niet (voorbelasting)",
            'intro' => "De btw op je zakelijke kosten krijg je terug via de aangifte. Maar niet op alles, en niet zonder de juiste bon. Wat je mag aftrekken, waar de Belastingdienst streng op is, en hoe je het administratief simpel houdt.",
            'sections' => [
                ["Het principe", [
                    "Ben je btw-ondernemer en lever je belaste prestaties, dan trek je de btw die leveranciers je in rekening brengen af van de btw die je zelf moet afdragen. Dat heet voorbelasting, rubriek 5b in de aangifte. Doe je mee met de KOR of lever je alleen vrijgestelde diensten, dan vervalt dit recht.",
                ]],
                ["Wat je nodig hebt: een goede factuur", [
                    "Aftrek mag alleen met een factuur die aan de eisen voldoet: op naam van je onderneming, met btw-nummer van de leverancier, datum, omschrijving en de btw apart vermeld. Voor kleine bedragen tot 100 euro inclusief btw volstaat een vereenvoudigde factuur, zoals een kassabon. Een pinbon zonder btw-specificatie is niet genoeg.",
                ]],
                ["Wat wél aftrekbaar is", [
                    "Alles wat je zakelijk gebruikt: laptop en telefoon, software en abonnementen, kantoorartikelen, inhuur, reclame, de zakelijke huur, vakliteratuur, cursussen, gereedschap en materiaal, zakelijke ritten met OV of taxi. Gebruik je iets deels privé, dan trek je alleen het zakelijke deel af.",
                ]],
                ["Wat niet mag", [
                    "Eten en drinken in de horeca: die btw is nooit aftrekbaar, ook niet bij een zakenlunch. Kosten die eigenlijk privé zijn. Personeelsvoorzieningen, relatiegeschenken en dergelijke boven een drempel per persoon per jaar (het BUA). En kosten waarvoor je geen factuur op naam hebt.",
                ]],
                ["De auto", [
                    "Bij een auto van de zaak trek je de btw op aanschaf, brandstof en onderhoud af en corrigeer je aan het eind van het jaar voor privégebruik: op basis van een rittenregistratie, of forfaitair een percentage van de catalogusprijs. Rijd je privé en declareer je zakelijke kilometers, dan is er geen btw-aftrek; je trekt dan een bedrag per kilometer af in de inkomstenbelasting.",
                ]],
                ["Timing en correcties", [
                    "Je trekt de btw af in het tijdvak van de factuurdatum, ook als je later betaalt. Ben je een bon vergeten, dan mag die alsnog mee in een volgende aangifte binnen hetzelfde jaar; anders via een suppletie. Bewaar alle inkoopfacturen zeven jaar, digitaal mag.",
                ]],
                ["Houd het bij in de week zelf", [
                    "De meeste gemiste btw zit in bonnetjes die kwijtraken. Fotografeer een bon meteen en laat je software hem uitlezen en koppelen aan de juiste kostensoort. Aan het eind van het kwartaal is de voorbelasting dan al opgeteld.",
                ]],
            ],
        ],

        'zelfstandigenaftrek-urencriterium-2026' => [
            'category' => "Ondernemen als zzp'er",
            'title' => "Zelfstandigenaftrek en urencriterium 2026: bedragen en regels",
            'intro' => "De zelfstandigenaftrek wordt elk jaar kleiner, maar samen met de startersaftrek en de mkb-winstvrijstelling scheelt hij nog steeds honderden tot duizenden euro's belasting. Voorwaarde: het urencriterium. De cijfers voor 2026.",
            'sections' => [
                ["De bedragen in 2026", [
                    "De zelfstandigenaftrek is in 2026 1.200 euro (in 2025 was het 2.470 euro; in 2027 wordt het 900 euro). De startersaftrek komt daar bovenop: 2.123 euro, maximaal drie keer in de eerste vijf jaar van je onderneming. Daarnaast is 12,7% van je winst na deze aftrekposten vrijgesteld via de mkb-winstvrijstelling; daarvoor geldt geen urencriterium.",
                    "Let op: de aftrekposten tellen alleen mee tegen het basistarief van de inkomstenbelasting, niet tegen het hoogste tarief. Controleer de bedragen jaarlijks bij de Belastingdienst; ze veranderen elk jaar.",
                ]],
                ["Het urencriterium: 1.225 uur", [
                    "Voor de zelfstandigenaftrek en de startersaftrek moet je in het kalenderjaar minimaal 1.225 uur aan je onderneming besteden, gemiddeld zo'n 24 uur per week. Bovendien moet meer dan de helft van je totale werktijd naar je onderneming gaan, tenzij je in de afgelopen vijf jaar minstens één jaar geen ondernemer was: dan geldt die 50%-eis niet.",
                    "Begin je halverwege het jaar, dan blijft het 1.225 uur voor dat hele jaar. De uren worden niet naar rato verlaagd.",
                ]],
                ["Welke uren tellen mee?", [
                    "Alle uren voor je onderneming: niet alleen declarabele uren, maar ook acquisitie, administratie, reistijd naar klanten, je website bijhouden, cursussen en netwerken. Wat niet telt: uren in loondienst, en uren die overduidelijk privé zijn.",
                ]],
                ["Houd je uren bij", [
                    "De Belastingdienst mag vragen hoe je aan 1.225 uur komt. Een urenregistratie met datum, activiteit en duur is dan je bewijs, zeker als je naast je onderneming in loondienst werkt. Een agenda achteraf reconstrueren is zwak; een doorlopende registratie in je facturatie- of urensoftware is sterk, omdat die aansluit op je facturen.",
                ]],
                ["Rekenvoorbeeld", [
                    "Winst 40.000 euro. Min zelfstandigenaftrek 1.200 en startersaftrek 2.123 is 36.677 euro. Daarvan is 12,7% vrijgesteld, 4.658 euro, zodat 32.019 euro belast wordt. Zonder de aftrekposten was dat 34.920 euro. Bij een tarief van ruim 35% scheelt dat ongeveer 1.000 euro belasting, en meer als je ook de startersaftrek al niet meer had.",
                ]],
                ["Andere voordelen met het urencriterium", [
                    "Haal je de uren, dan kun je ook doteren aan de oudedagsreserve niet meer (die is afgeschaft), maar wel gebruikmaken van de meewerkaftrek als je partner meewerkt, en van de aftrek voor speur- en ontwikkelingswerk als je daarvoor in aanmerking komt. Voor de meeste zzp'ers blijven de zelfstandigenaftrek en de mkb-winstvrijstelling het belangrijkst.",
                ]],
            ],
        ],

        'kilometervergoeding-zzp-2026' => [
            'category' => "Ondernemen als zzp'er",
            'title' => "Kilometers als zzp'er: 0,23 euro per km, registratie en regels (2026)",
            'intro' => "Rijd je met je privéauto naar klanten, dan mag je per zakelijke kilometer een vast bedrag aftrekken. Hoeveel dat is, wat je moet bijhouden en wanneer een auto op de zaak beter uitpakt.",
            'sections' => [
                ["Het bedrag: 0,23 euro per kilometer", [
                    "Gebruik je je privéauto, fiets, scooter of het openbaar vervoer voor zakelijke ritten, dan trek je in 2026 0,23 euro per zakelijke kilometer af van je winst. Dat bedrag geldt sinds 2024 en dekt brandstof, onderhoud, verzekering en afschrijving in één keer. Werkelijke kosten mag je daarnaast niet aftrekken; parkeerkosten en tol bij een zakelijke rit wél.",
                ]],
                ["Wat is een zakelijke kilometer?", [
                    "Ritten naar klanten, leveranciers, netwerkbijeenkomsten, cursussen en de bank of accountant. Woon-werkverkeer naar een vaste werkplek buiten de deur telt voor ondernemers ook als zakelijk, anders dan voor werknemers. Ritten naar de supermarkt of vakantie zijn privé, ook als je onderweg even een klant belt.",
                ]],
                ["Wat je moet bijhouden", [
                    "Per rit: datum, beginstand en eindstand van de kilometerteller of het aantal kilometers, vertrek- en aankomstadres en het doel van de rit. Een rittenregistratie in je facturatie- of urensoftware, gekoppeld aan een klant of project, is het makkelijkst: dan hangt de rit meteen aan de factuur waar hij bij hoort.",
                    "Schat je achteraf, dan mag de Belastingdienst de aftrek schrappen. Een globale opgave als tweemaal per week naar klant X werkt alleen als je het aannemelijk kunt maken.",
                ]],
                ["Doorberekenen aan je klant", [
                    "Je mag reiskosten aan je klant factureren, tegen welk tarief je maar afspreekt: 0,23 euro, 0,35 euro of een vast bedrag per bezoek. Daarover reken je btw tegen hetzelfde tarief als je dienst. De aftrek van 0,23 euro per kilometer blijft daarnaast gewoon staan; het gefactureerde bedrag is omzet.",
                ]],
                ["Privéauto of auto van de zaak?", [
                    "Rijd je veel zakelijk, zeg meer dan 500 kilometer per week, dan kan een auto op de zaak voordeliger zijn: alle autokosten en de btw zijn dan aftrekbaar, maar je betaalt bijtelling voor privégebruik en corrigeert de btw jaarlijks. Onder de 500 privékilometers per jaar vervalt de bijtelling, met een sluitende rittenregistratie als bewijs. Laat dit één keer doorrekenen; de keuze zit vast aan de auto.",
                ]],
                ["Fiets en OV", [
                    "Voor de fiets geldt dezelfde 0,23 euro per zakelijke kilometer. Bij openbaar vervoer kies je: het vaste bedrag per kilometer, of de werkelijke kosten van het kaartje met btw-aftrek. Bij een duur treinkaartje zijn de werkelijke kosten meestal hoger.",
                ]],
            ],
        ],

        'bewaarplicht-administratie' => [
            'category' => "Ondernemen als zzp'er",
            'title' => "Bewaarplicht: hoe lang bewaar je facturen en administratie?",
            'intro' => "Zeven jaar, meestal. Maar wat valt er precies onder, wanneer begint de termijn, mag het digitaal en wat gebeurt er als je het niet doet? De bewaarplicht voor zzp'ers en mkb in gewone taal.",
            'sections' => [
                ["De hoofdregel: zeven jaar", [
                    "Je bent verplicht je administratie zeven jaar te bewaren, gerekend vanaf het einde van het boekjaar waarop de gegevens betrekking hebben. Een factuur uit maart 2026 bewaar je dus tot en met eind 2033. Gegevens over onroerende zaken, zoals een bedrijfspand, bewaar je tien jaar vanwege de btw-herzieningstermijn.",
                ]],
                ["Wat hoort bij de administratie?", [
                    "In elk geval de basisgegevens: het grootboek, de debiteuren- en crediteurenadministratie, de in- en verkoopadministratie, de voorraadadministratie en de loonadministratie als je personeel hebt. Concreet voor een zzp'er: alle verkoopfacturen en inkoopfacturen, bankafschriften, kasboek, offertes en overeenkomsten, urenregistratie en rittenregistratie, en de btw-aangiften met de onderliggende berekeningen.",
                    "Ook e-mails en agenda's kunnen erbij horen als ze nodig zijn om je administratie te begrijpen, bijvoorbeeld de mail waarin een klant een offerte accepteert.",
                ]],
                ["Digitaal bewaren mag", [
                    "Papier scannen en het origineel weggooien mag, als de scan een volledige en juiste weergave is en gedurende de hele termijn leesbaar en controleerbaar blijft. Andersom, digitale facturen uitprinten, is niet nodig. Wat de Belastingdienst wil: dat je binnen redelijke tijd kunt laten zien wat er is gebeurd.",
                    "Bewaar digitaal wel in het oorspronkelijke formaat, met de bijbehorende gegevens. Een factuur die uit je facturatiesoftware komt, blijft daar het best bewaard, inclusief de verzendgegevens en de betaalstatus.",
                ]],
                ["Overstappen van software", [
                    "Stop je met een pakket, dan blijft de bewaarplicht gewoon gelden. Exporteer je gegevens in een bruikbaar formaat, bij voorkeur als auditfile (XAF) plus PDF's van alle facturen, en bewaar die zelf. Check vooraf of je nieuwe of oude leverancier dat mogelijk maakt; een pakket dat je data niet meegeeft, brengt je bij een controle in de problemen.",
                ]],
                ["Kortere termijn afspreken", [
                    "Voor sommige onderdelen kun je met de Belastingdienst een kortere bewaartermijn afspreken, bijvoorbeeld voor kassarollen of losse bonnen die al in een systeem verwerkt zijn. Dat moet schriftelijk. Voor de basisgegevens geldt altijd zeven jaar.",
                ]],
                ["Als je het niet doet", [
                    "Ontbreekt je administratie bij een controle, dan mag de Belastingdienst je winst en btw schatten, en dat pakt zelden gunstig uit. De bewijslast draait dan om: jij moet aantonen dat de schatting te hoog is. Daarnaast kan een boete volgen. Zeven jaar netjes bewaren is verreweg de goedkoopste optie.",
                ]],
            ],
        ],

        'incassokosten-wettelijke-rente-berekenen' => [
            'category' => 'Betaald krijgen',
            'title' => "Incassokosten en wettelijke rente berekenen: de staffel (2026)",
            'intro' => "Betaalt een klant te laat, dan mag je incassokosten en rente rekenen. Hoeveel precies ligt vast in de wet. De staffel, het verschil tussen zakelijke klanten en consumenten, en hoe je het op de aanmaning zet.",
            'sections' => [
                ["Wanneer mag je kosten rekenen?", [
                    "Zodra de klant in verzuim is: de betaaltermijn op de factuur is verstreken. Bij een zakelijke klant treedt het verzuim automatisch in; je hoeft niet eerst te waarschuwen, al is een herinnering wel netjes. Bij een consument moet je eerst een aanmaning sturen met een termijn van veertien dagen na ontvangst en het bedrag aan incassokosten dat anders volgt. Pas daarna mag je die kosten echt rekenen.",
                ]],
                ["De staffel voor incassokosten", [
                    "De wettelijke incassokosten zijn een percentage van de hoofdsom: 15% over de eerste 2.500 euro, 10% over de volgende 2.500 euro, 5% over de volgende 5.000 euro, 1% over de volgende 190.000 euro en 0,5% over het meerdere. Het minimum is 40 euro, het maximum 6.775 euro.",
                    "Voorbeeld: een factuur van 1.000 euro geeft 150 euro incassokosten. Een factuur van 300 euro geeft rekenkundig 45 euro. Een factuur van 6.000 euro: 375 plus 250 plus 50 is 675 euro. Over de incassokosten zelf reken je geen btw, tenzij je zelf geen btw kunt aftrekken.",
                ]],
                ["Zakelijk: minimaal 40 euro, ook bij een klein bedrag", [
                    "Bij zakelijke klanten mag je bij elke te late betaling minimaal 40 euro rekenen, ook bij een factuur van 25 euro. Hogere kosten mag je zakelijk afspreken in je voorwaarden; de wettelijke staffel geldt dan als ondergrens. Bij consumenten is de staffel juist het maximum.",
                ]],
                ["Wettelijke rente", [
                    "Naast incassokosten mag je rente rekenen over de hoofdsom vanaf de dag na de vervaldatum. Voor zakelijke klanten geldt de wettelijke handelsrente: de ECB-rente plus acht procentpunt, elk half jaar opnieuw vastgesteld. In de eerste helft van 2026 was dat 10,15%. Voor consumenten geldt de gewone wettelijke rente, die een stuk lager ligt. Het actuele percentage staat op rijksoverheid.nl.",
                    "Rekenvoorbeeld: 5.000 euro, 60 dagen te laat, 10,15% handelsrente: 5.000 maal 10,15% maal 60 gedeeld door 365 is ongeveer 83 euro.",
                ]],
                ["Zo zet je het op de aanmaning", [
                    "Vermeld de oorspronkelijke factuur met nummer, datum en bedrag, de vervaldatum, het bedrag aan incassokosten met een verwijzing naar de wettelijke staffel, de rente tot een genoemde datum en het totaal. Geef een korte, duidelijke termijn en zeg wat er daarna gebeurt: overdracht aan een incassobureau. Software die aanmaningen maakt, rekent de kosten en rente automatisch uit op basis van de vervaldatum.",
                ]],
                ["Kosten of klant?", [
                    "Bij een goede klant die één keer te laat is, kun je de kosten laten vallen; noem ze wel, en schrap ze bij snelle betaling. Bij een klant die structureel laat betaalt of niet reageert, reken je ze gewoon. Wie de kosten nooit rekent, leert klanten dat te laat betalen gratis is.",
                ]],
            ],
        ],

    ],
];
