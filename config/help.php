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
