<?php

/*
|--------------------------------------------------------------------------
| Helpcentrum-artikelen
|--------------------------------------------------------------------------
| Inhoud voor het publieke helpcentrum. Elke sleutel is de URL-slug
| (/helpcentrum/{slug}). Voeg gerust artikelen toe of pas teksten aan.
|
| Structuur per artikel:
|   'category' => groepsnaam (voor navigatie/labels)
|   'title'    => titel van het artikel
|   'intro'    => korte inleiding
|   'sections' => lijst van [kop, [alinea's...]]
*/

return [
    'articles' => [

        // ---------- AAN DE SLAG ----------
        'een-account-aanmaken' => [
            'category' => 'Aan de slag',
            'title' => 'Een account aanmaken',
            'intro' => 'In een paar minuten heb je een EasyInvoice-account en kun je je eerste factuur versturen.',
            'sections' => [
                ['Account aanmaken', [
                    'Klik rechtsboven op "Start gratis" en vul je naam, e-mailadres en een wachtwoord in. Je kunt direct aan de slag — een creditcard is niet nodig.',
                ]],
                ['E-mailadres bevestigen', [
                    'Na registratie ontvang je een e-mail met een verificatiecode. Vul deze code in om je account te activeren. Geen e-mail ontvangen? Controleer je spammap of vraag een nieuwe code aan.',
                ]],
                ['Volgende stap', [
                    'Vul daarna je bedrijfsgegevens in, zodat ze automatisch op je facturen verschijnen.',
                ]],
            ],
        ],

        'bedrijfsgegevens-instellen' => [
            'category' => 'Aan de slag',
            'title' => 'Je bedrijfsgegevens instellen',
            'intro' => 'Je bedrijfsgegevens verschijnen automatisch op elke factuur. Vul ze één keer in bij Instellingen → Bedrijfsgegevens.',
            'sections' => [
                ['Welke gegevens', [
                    'Vul je bedrijfsnaam, adres, KvK-nummer, btw-nummer (indien van toepassing) en IBAN in. Deze gegevens zijn verplicht op een correcte factuur.',
                ]],
                ['Aanpassen', [
                    'Je kunt je gegevens op elk moment wijzigen via Instellingen → Bedrijfsgegevens. Wijzigingen gelden voor nieuwe facturen; al verstuurde facturen blijven ongewijzigd.',
                ]],
            ],
        ],

        'eerste-factuur-maken' => [
            'category' => 'Aan de slag',
            'title' => 'Je eerste factuur maken',
            'intro' => 'Een factuur opstellen en versturen duurt minder dan een minuut.',
            'sections' => [
                ['Nieuwe factuur', [
                    'Ga naar Facturen → Nieuwe factuur. Kies een klant (of maak er direct een aan) en voeg factuurregels toe met omschrijving, aantal, prijs en btw-tarief.',
                ]],
                ['Controleren en versturen', [
                    'Het totaal en de btw worden automatisch berekend. Controleer de factuur, sla op en verstuur hem direct per e-mail naar je klant of download de PDF.',
                ]],
                ['Status volgen', [
                    'Op het factuuroverzicht zie je live of een factuur openstaat, betaald of achterstallig is.',
                ]],
            ],
        ],

        // ---------- FACTUREN ----------
        'btw-per-regel' => [
            'category' => 'Facturen',
            'title' => 'BTW per regel instellen',
            'intro' => 'EasyInvoice berekent de btw automatisch per factuurregel — 21%, 9% of 0%.',
            'sections' => [
                ['Tarief kiezen', [
                    'Kies bij elke factuurregel het juiste btw-tarief. Het hoge tarief is 21%, het lage 9%, en 0% gebruik je bijvoorbeeld bij btw-verlegd of export.',
                ]],
                ['KOR / geen btw', [
                    'Val je onder de Kleine Ondernemersregeling (KOR)? Dan stel je 0% in en vermeldt EasyInvoice automatisch de KOR-regeling op je factuur.',
                ]],
                ['Btw-overzicht', [
                    'Per kwartaal vind je een overzicht van de berekende btw, dat je eenvoudig overneemt bij je aangifte.',
                ]],
            ],
        ],

        'creditfactuur-maken' => [
            'category' => 'Facturen',
            'title' => 'Een creditfactuur maken',
            'intro' => 'Met een creditnota corrigeer je een eerder verstuurde factuur, volledig of gedeeltelijk.',
            'sections' => [
                ['Crediteren', [
                    'Open de betreffende factuur en kies "Crediteren". Je kunt het volledige bedrag of een deel daarvan crediteren.',
                ]],
                ['Nummering', [
                    'De creditnota krijgt een eigen, doorlopend nummer en voldoet aan de Nederlandse boekhoudregels.',
                ]],
            ],
        ],

        'factuurnummering' => [
            'category' => 'Facturen',
            'title' => 'Factuurnummering aanpassen',
            'intro' => 'EasyInvoice nummert je facturen automatisch en doorlopend per jaar.',
            'sections' => [
                ['Reeks instellen', [
                    'Bij Instellingen → Nummering bepaal je het startnummer en het formaat van je factuurnummers. Elk jaar start een nieuwe doorlopende reeks.',
                ]],
                ['Let op', [
                    'De Belastingdienst vereist een doorlopende nummering zonder gaten. Pas het nummer daarom alleen aan bij de start van een nieuw jaar of bij het overstappen vanaf een ander pakket.',
                ]],
            ],
        ],

        // ---------- BETALINGEN & INCASSO ----------
        'betaling-registreren' => [
            'category' => 'Betalingen & incasso',
            'title' => 'Een betaling registreren',
            'intro' => 'Leg (deel)betalingen vast zodat je altijd weet wat er nog openstaat.',
            'sections' => [
                ['Betaling vastleggen', [
                    'Open de factuur en kies "Betaling registreren". Vul het betaalde bedrag en de datum in. Bij een volledige betaling springt de status automatisch op "Betaald".',
                ]],
                ['Deelbetalingen', [
                    'Heb je een deel ontvangen? Registreer het deelbedrag — de factuur krijgt de status "Deels betaald" en het resterende bedrag blijft zichtbaar.',
                ]],
            ],
        ],

        'automatische-herinneringen' => [
            'category' => 'Betalingen & incasso',
            'title' => 'Automatische herinneringen',
            'intro' => 'Laat EasyInvoice je betalingsherinneringen versturen, zodat je er zelf niet aan hoeft te denken.',
            'sections' => [
                ['Instellen', [
                    'Bij Instellingen → Herinneringen bepaal je wanneer een herinnering wordt verstuurd, bijvoorbeeld een aantal dagen na de vervaldatum.',
                ]],
                ['Opvolging', [
                    'Blijft een betaling uit, dan kun je gefaseerd opschalen naar een aanmaning en uiteindelijk naar incasso.',
                ]],
            ],
        ],

        'incassotraject' => [
            'category' => 'Betalingen & incasso',
            'title' => 'Het incassotraject',
            'intro' => 'Een achterstallige factuur draag je in fases over, netjes en vastgelegd.',
            'sections' => [
                ['De fases', [
                    'Het traject loopt van een vriendelijke herinnering, naar een formele aanmaning, naar overdracht aan incasso. Per fase houd je de status en datum bij.',
                ]],
                ['Overdragen', [
                    'Op de Incasso-pagina zie je alle lopende trajecten. Met één klik draag je een factuur over aan de volgende fase.',
                ]],
            ],
        ],

        // ---------- KLANTEN & PRODUCTEN ----------
        'klant-toevoegen' => [
            'category' => 'Klanten & producten',
            'title' => 'Een klant toevoegen',
            'intro' => 'Bewaar je klantgegevens één keer en hergebruik ze op elke factuur.',
            'sections' => [
                ['Nieuwe klant', [
                    'Ga naar Klanten → Nieuwe klant en vul de gegevens in: naam, adres, en voor zakelijke klanten ook KvK- en btw-nummer.',
                ]],
                ['Zakelijk of particulier', [
                    'Geef aan of het een zakelijke of particuliere klant is. EasyInvoice past de factuur hierop aan.',
                ]],
            ],
        ],

        'producten-beheren' => [
            'category' => 'Klanten & producten',
            'title' => 'Producten beheren',
            'intro' => 'Leg veelgebruikte producten en diensten vast om sneller te factureren.',
            'sections' => [
                ['Product aanmaken', [
                    'Bij Producten voeg je een omschrijving, prijs en standaard btw-tarief toe. Bij het opstellen van een factuur selecteer je het product en zijn de gegevens meteen ingevuld.',
                ]],
            ],
        ],

        // ---------- HUISSTIJL ----------
        'logo-uploaden' => [
            'category' => 'Huisstijl',
            'title' => 'Je logo uploaden',
            'intro' => 'Zet je eigen logo op je facturen voor een professionele uitstraling.',
            'sections' => [
                ['Uploaden', [
                    'Ga naar Instellingen → Huisstijl en upload je logo (PNG of JPG). Het verschijnt automatisch bovenaan je facturen en in de PDF.',
                ]],
            ],
        ],

        'sjabloon-kiezen' => [
            'category' => 'Huisstijl',
            'title' => 'Een sjabloon kiezen',
            'intro' => 'Kies een factuursjabloon dat bij je merk past.',
            'sections' => [
                ['Sjabloon en kleur', [
                    'Bij Instellingen → Huisstijl kies je een sjabloon en stel je je eigen accentkleur in. De wijziging zie je direct terug in de voorbeeldfactuur.',
                ]],
            ],
        ],

        // ---------- ACCOUNT & BEVEILIGING ----------
        '2fa-instellen' => [
            'category' => 'Account & beveiliging',
            'title' => 'Tweestapsverificatie (2FA) instellen',
            'intro' => 'Beveilig je account met een extra stap bij het inloggen.',
            'sections' => [
                ['Activeren', [
                    'Ga naar Instellingen → Beveiliging en kies "Tweestapsverificatie inschakelen". Scan de QR-code met een authenticator-app (zoals Google Authenticator of 1Password).',
                ]],
                ['Herstelcodes', [
                    'Bewaar de herstelcodes op een veilige plek. Hiermee kom je weer binnen als je je telefoon kwijtraakt.',
                ]],
            ],
        ],

        'wachtwoord-wijzigen' => [
            'category' => 'Account & beveiliging',
            'title' => 'Je wachtwoord wijzigen',
            'intro' => 'Houd je account veilig met een sterk, uniek wachtwoord.',
            'sections' => [
                ['Wijzigen', [
                    'Je wijzigt je wachtwoord bij Instellingen → Beveiliging. Kies een sterk wachtwoord dat je nergens anders gebruikt.',
                ]],
                ['Wachtwoord vergeten', [
                    'Ben je je wachtwoord kwijt? Gebruik de link "Wachtwoord vergeten" op de inlogpagina om het opnieuw in te stellen.',
                ]],
            ],
        ],

        // ---------- KLANTENPORTAAL ----------
        'klantenportaal-inzagelog' => [
            'category' => 'Klantenportaal',
            'title' => 'Het klantenportaal en het inzagelog',
            'intro' => 'Je klant bekijkt facturen online via een beveiligde link — en jij ziet precies óf en wanneer je factuur is bekeken.',
            'sections' => [
                ['Hoe je klant de factuur bekijkt', [
                    'In elke factuurmail en betalingsherinnering staat de knop "Bekijk factuur online". Voor de veiligheid bevestigt je klant eerst zijn e-mailadres met een eenmalige 6-cijferige code (10 minuten geldig). Daarna ziet hij de factuur, de betaalstatus, de betaalinstructies en kan hij de PDF downloaden.',
                    'Je klant kan ook rechtstreeks inloggen op /portaal met zijn e-mailadres — daar staan alle facturen die naar dat adres zijn verstuurd.',
                ]],
                ['Het inzagelog', [
                    'Op de factuurpagina zie je onder "Inzage door klant" wanneer de factuur voor het eerst is bekeken en elk volgend inzagemoment, inclusief PDF-downloads. In de facturenlijst verschijnt een groen oogje zodra een factuur is bekeken.',
                    'Met "Kopieer portaallink" deel je de beveiligde link ook zelf, bijvoorbeeld via WhatsApp.',
                ]],
            ],
        ],

        // ---------- BTW & RAPPORTEN ----------
        'btw-aangifte-per-kwartaal' => [
            'category' => 'BTW & rapporten',
            'title' => 'Je BTW-aangifte per kwartaal',
            'intro' => 'Onder Rapporten → BTW-aangifte staat per kwartaal precies wat je invult bij de Belastingdienst.',
            'sections' => [
                ['Wat je ziet', [
                    'Per kwartaal: de grondslag en BTW per tarief (rubriek 1a en 1b), het nultarief (1e), de voorbelasting uit je ingeboekte inkoopfacturen (5b) en het saldo dat je per kwartaal betaalt of terugkrijgt.',
                    'Is een kwartaal voorbij maar de aangiftetermijn nog niet? Dan kleurt de kaart en zie je bovenaan een waarschuwing met het bedrag en de uiterste datum.',
                ]],
                ['Goed om te weten', [
                    'De cijfers worden berekend op factuurdatum (factuurstelsel) over alle verstuurde facturen en creditnota\'s. De voorbelasting is zo volledig als je inkoopadministratie — boek dus al je inkoop in.',
                    'Met "Download PDF" bewaar je het overzicht voor je administratie of stuur je het naar je boekhouder.',
                ]],
            ],
        ],

        // ---------- INKOOP ----------
        'inkoopfacturen-inboeken' => [
            'category' => 'Inkoop',
            'title' => 'Inkoopfacturen inboeken (ook met een foto)',
            'intro' => 'Boek binnengekomen facturen van leveranciers in — de BTW telt automatisch mee als voorbelasting in je aangifte.',
            'sections' => [
                ['Inboeken', [
                    'Ga naar Inkoop → Inkoopfacturen → "Inkoopfactuur inboeken". Vul de leverancier (met suggesties uit eerdere boekingen), het factuurnummer, de kostencategorie en de bedragen in. Je kunt bedragen exclusief óf inclusief BTW invoeren — EasyInvoice rekent het andere bedrag uit.',
                ]],
                ['Met een foto of PDF', [
                    'Voeg een foto of PDF van de bon toe: kies een bestand of tik op "Foto maken" (op je telefoon opent direct de camera). De foto verschijnt groot naast het formulier, zodat je overtypt terwijl je de bon ziet.',
                ]],
                ['Scan & herken (automatisch invullen)', [
                    'Na het toevoegen van een foto of PDF verschijnt de knop "Scan & herken". De AI leest de bon en vult de leverancier, het factuurnummer, de datum en de bedragen per BTW-tarief automatisch in — jij controleert ze met de bon ernaast. Klopt het totaal van de herkende regels niet met de bon, dan zie je direct een waarschuwing.',
                ]],
                ['Betalen en bijhouden', [
                    'Markeer facturen als betaald met datum en betaalwijze. Op het overzicht zie je wat er bij leveranciers openstaat, wat er over de vervaldatum is en hoeveel voorbelasting je dit kwartaal hebt opgebouwd. Via "CSV-export" download je alles voor je boekhouder.',
                ]],
            ],
        ],

        // ---------- HUISSTIJL ----------
        'meerdere-handelsnamen' => [
            'category' => 'Huisstijl',
            'title' => 'Factureren onder meerdere handelsnamen',
            'intro' => 'Werk je onder verschillende namen? Geef elke handelsnaam een eigen logo, kleur en sjabloon — binnen één administratie.',
            'sections' => [
                ['Handelsnamen toevoegen', [
                    'Ga naar Instellingen → Handelsnamen en voeg een handelsnaam toe. Per handelsnaam kies je een eigen logo, factuurkleur, sjabloon en voetnoot. Wat je leeg laat, valt terug op je standaard huisstijl. Zonder eigen logo toont de factuur een letter-embleem — bewust niet het logo van je hoofdbedrijf.',
                ]],
                ['Factureren en offreren als', [
                    'Bij het maken van een factuur of offerte verschijnt (zodra je minstens één handelsnaam hebt) de keuze "Factureren als" of "Offerte als". De PDF, de mail — inclusief de afzendernaam die je klant ziet — de betalingsherinneringen en het klantenportaal volgen automatisch die huisstijl. Zet je een offerte om naar een factuur, dan erft die de handelsnaam; terugkerende profielen onthouden hem ook.',
                ]],
                ['Eén administratie', [
                    'Juridisch verandert er niets: je KvK-nummer, BTW-nummer, IBAN en factuurnummering blijven die van je bedrijf, en alles telt mee in dezelfde BTW-aangifte en rapporten. Verwijder je een handelsnaam, dan vallen de bestaande facturen terug op je standaard huisstijl.',
                ]],
            ],
        ],

        'betaallink-ideal' => [
            'category' => 'Betalingen',
            'title' => 'Betaald worden met iDEAL (betaallink)',
            'intro' => 'Laat klanten het openstaande bedrag direct met iDEAL betalen vanuit de factuurmail en het klantenportaal — via je eigen Mollie-account.',
            'sections' => [
                ['Mollie koppelen', [
                    'Maak een gratis account op mollie.com en kopieer je API-key (Developers → API-keys). Plak die bij Instellingen → Bedrijfsgegevens onder "Online betalingen". Het geld gaat rechtstreeks naar jouw rekening — EasyInvoice zit er niet tussen en rekent er niets voor (Mollie rekent per transactie een klein bedrag, zie mollie.com/pricing). Met een test_-key kun je eerst veilig proefdraaien.',
                ]],
                ['Zo betaalt je klant', [
                    'In de factuurmail verandert de knop in "Bekijk en betaal online (iDEAL)". In het beveiligde klantenportaal staat een betaalknop met het openstaande bedrag — deelbetalingen en verrekeningen worden automatisch afgetrokken. Na het afronden bij de bank wordt de betaling direct op de factuur geboekt en springt de status op betaald (of deels betaald).',
                ]],
                ['Goed om te weten', [
                    'Elke betaalpoging zie je terug in het inzagelog van de factuur ("Online betaling gestart"). Wordt een betaling geannuleerd, dan kan de klant het gewoon opnieuw proberen of alsnog handmatig overmaken. De betaalknop verschijnt alleen op openstaande facturen; per administratie koppel je een eigen Mollie-account.',
                ]],
            ],
        ],

        'facturen-in-het-engels' => [
            'category' => 'Facturen',
            'title' => 'Facturen en offertes in het Engels',
            'intro' => 'Voor internationale opdrachtgevers: zet de taal op de klantkaart op Engels en alle documenten en e-mails aan die klant zijn Engelstalig.',
            'sections' => [
                ['Instellen', [
                    'Open de klant en zet bij Voorkeuren "Taal van factuur & offerte" op Engels. Vanaf dat moment zijn nieuwe facturen en offertes voor deze klant Engelstalig: de PDF (alle sjablonen), de begeleidende e-mail — inclusief onderwerpregel — en de datums ("19 August 2026").',
                ]],
                ['Wat er precies vertaald wordt', [
                    'Alle vaste teksten: kopjes als factuurdatum en vervaldatum, de tabelkoppen, totalen, de betaalinstructie en de portaalknop. Je eigen teksten — regel-omschrijvingen, notities, de begeleidende offertetekst en je voetnoot — blijven zoals jij ze typt: die schrijf je voor een Engelse klant dus zelf in het Engels. Bedragen houden de Europese notatie (€ 1.234,56).',
                    'Ook de PDF-bijlage bij betalingsherinneringen en het incassodossier gebruiken de documenttaal. De teksten van herinneringen zelf komen uit je eigen sjablonen (Instellingen → Herinneringen) — pas die aan als veel van je klanten Engelstalig zijn.',
                ]],
                ['Per document vastgelegd', [
                    'De taal wordt bij het aanmaken op het document vastgelegd. Wijzig je de klantinstelling later, dan veranderen eerder gemaakte facturen en offertes dus niet — de klant houdt precies wat hij ontving. Creditnota\'s erven de taal van de oorspronkelijke factuur, en een offerte die je omzet naar een factuur houdt zijn taal.',
                ]],
            ],
        ],

        // ---------- UREN ----------
        'urenregistratie' => [
            'category' => 'Uren',
            'title' => 'Uren schrijven en met één klik factureren',
            'intro' => 'Houd gewerkte uren bij per klant of project — handmatig of met de timer — en zet ze in één keer op een conceptfactuur.',
            'sections' => [
                ['Uren schrijven', [
                    'Ga naar Verkoop → Uren. Vul datum, klant, eventueel een project, de omschrijving en de duur in. De duur typ je als "1:30" (anderhalf uur) of als decimaal "1,5" — allebei goed. De omschrijving komt straks letterlijk op de factuur.',
                    'Liever niet achteraf invullen? Klik op "Start timer" en ga aan het werk. Bij het stoppen wordt de gewerkte tijd automatisch als urenregel opgeslagen.',
                ]],
                ['Uurtarieven', [
                    'Stel één standaardtarief in bij Instellingen → Bedrijfsgegevens. Wijkt een klant af, zet dan een eigen tarief op de klantkaart. En per urenregel kun je het tarief altijd nog overschrijven — de regel gebruikt het meest specifieke tarief dat is ingevuld.',
                ]],
                ['Factureren', [
                    'In het blok "Klaar om te factureren" zie je per klant hoeveel uur er openstaat en voor welk bedrag. Eén klik op "Maak factuur" bundelt alle openstaande uren van die klant op een conceptfactuur: per regel de omschrijving, de datum en het aantal uur. Je controleert het concept en verstuurt zoals je gewend bent.',
                    'Gefactureerde uren worden vergrendeld en linken naar de factuur. Verwijder je de conceptfactuur, dan komen de uren automatisch weer vrij. Uren die je niet wilt doorbelasten zet je op "niet-factureerbaar" — die tellen wel mee in je overzicht, maar nooit op een factuur.',
                ]],
            ],
        ],

        'kilometerregistratie' => [
            'category' => 'Uren',
            'title' => 'Zakelijke ritten bijhouden en doorbelasten',
            'intro' => 'Registreer je zakelijke kilometers — belast ze door aan je klant of bewaar ze als kilometeradministratie voor je aangifte.',
            'sections' => [
                ['Ritten registreren', [
                    'Ga naar Verkoop → Ritten. Vul datum, vertrek en bestemming, de afstand en eventueel het doel van de rit in. Vink "retour" aan en de terugreis telt automatisch mee. Koppel een klant als je de rit wilt doorbelasten; zonder klant (of met "doorbelasten" uitgevinkt) telt de rit alleen mee in je eigen kilometeradministratie.',
                ]],
                ['Tarief', [
                    'Het standaardtarief staat op € 0,23 per kilometer — het onbelaste tarief van de Belastingdienst. Je past het aan bij Instellingen → Bedrijfsgegevens, en per rit kun je altijd een afwijkend tarief invullen.',
                ]],
                ['Doorbelasten', [
                    'In het blok "Klaar om te factureren" zie je per klant de openstaande ritten, kilometers en het bedrag. Eén klik maakt een conceptfactuur met per rit een reiskostenregel: "Reiskosten: Bussum – Amsterdam (retour)", de datum en het aantal kilometers. Gefactureerde ritten worden vergrendeld en linken naar de factuur; verwijder je het concept, dan komen ze weer vrij.',
                ]],
            ],
        ],

        'meerdere-administraties' => [
            'category' => 'Account',
            'title' => 'Meerdere administraties onder één inlog',
            'intro' => 'Beheer meerdere bedrijven — elk met eigen KvK, klanten, facturen en nummering — en wissel moeiteloos via het menu linksonder.',
            'sections' => [
                ['Een administratie toevoegen', [
                    'Klik linksonder op je naam en kies "Administraties beheren", of ga direct naar /administraties. Met "Nieuwe administratie" start je een extra bedrijf: vul de naam en het KvK-nummer in en je wordt automatisch beheerder. De nieuwe administratie begint met een eigen gratis proefperiode van 14 dagen en een eigen abonnement, en je wisselt er direct naartoe om de bedrijfsgegevens aan te vullen.',
                ]],
                ['Wisselen', [
                    'Klik linksonder op je naam: onder "Administraties" staan al je bedrijven, met een vinkje bij de actieve. Eén klik en je zit in de andere administratie — met de klanten, facturen, huisstijl en instellingen van dát bedrijf. Administraties zien elkaars gegevens nooit, en je rol kan per administratie verschillen.',
                ]],
                ['Eén inlog voor je boekhouder of collega', [
                    'Nodig je via Instellingen → Team iemand uit die al een EasyInvoice-account heeft, dan hoeft die geen nieuw account te maken: de uitnodigingslink koppelt jouw administratie aan zijn bestaande inlog. Zo werkt een boekhouder met één inlog voor al zijn klanten.',
                ]],
            ],
        ],

        // ---------- SAMENWERKEN ----------
        'team-en-rollen' => [
            'category' => 'Samenwerken',
            'title' => 'Collega\'s en je boekhouder uitnodigen (rollen)',
            'intro' => 'Werk met meerdere mensen in dezelfde omgeving, ieder met eigen rechten — extra gebruikers kosten niets.',
            'sections' => [
                ['Uitnodigen', [
                    'Ga naar Instellingen → Team, vul het e-mailadres in en kies een rol. De genodigde ontvangt een e-mail met een beveiligde link (7 dagen geldig), kiest zelf een wachtwoord en zit direct in jouw omgeving.',
                ]],
                ['De drie rollen', [
                    'Beheerder: volledige toegang, inclusief instellingen, abonnement en teambeheer.',
                    'Medewerker: het dagelijkse werk — offertes, facturen, klanten, producten en inkoop. Geen instellingen, rapporten of abonnement.',
                    'Boekhouder (alleen inzien): mag alles bekijken en juist wél de rapporten en exports gebruiken, maar niets wijzigen.',
                ]],
                ['Beheren', [
                    'Als beheerder pas je rollen aan of verwijder je teamleden. Er blijft altijd minstens één beheerder over, en openstaande uitnodigingen kun je opnieuw versturen of intrekken.',
                ]],
            ],
        ],

    ],
];
