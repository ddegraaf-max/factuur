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

    ],
];
