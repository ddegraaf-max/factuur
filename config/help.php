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
            'title' => 'Je btw-aangifte: aangifte-klaar',
            'intro' => 'Onder Rapporten → Btw-aangifte staan per tijdvak álle rubrieken van het aangifteformulier klaar — in de indeling van Mijn Belastingdienst Zakelijk, met betaalgegevens en betalingskenmerk erbij.',
            'sections' => [
                ['Wat je ziet', [
                    'Per tijdvak (kwartaal, maand of jaar — zoals de Belastingdienst het aan je heeft toegewezen) de grondslag en btw per rubriek: 1a en 1b (21% en 9%), 1e (0% binnenland), 3a en 3b (uitvoer en EU, op basis van het land van de klant), de voorbelasting uit je ingeboekte inkoopfacturen (5b) en het saldo 5c dat je betaalt of terugkrijgt.',
                    'Klik op "Aangifte-klaar" bij een tijdvak: je ziet de rubrieken in hele euro\'s, afgerond in je voordeel zoals de Belastingdienst toestaat. Klik op een bedrag om het te kopiëren en plak het in Mijn Belastingdienst Zakelijk. Onder "Onderbouwing" zie je welke facturen in welke rubriek zitten.',
                ]],
                ['Wat je zelf aanvult', [
                    'Wat EasyInvoice niet kan weten, vul je per tijdvak zelf in: verlegde btw (2a), inkoop uit het buitenland (4a/4b), privégebruik (1d), overige tarieven (1c) en voorbelasting die je niet hebt ingeboekt. Easy bewaart het per tijdvak en telt het op in 5a en 5b.',
                ]],
                ['Betalen en afvinken', [
                    'Bij een te betalen saldo staan het bedrag, het rekeningnummer van de Belastingdienst (sinds 1 mei 2026: NL04 RABO 0200 1122 44) en het betalingskenmerk klaar om te kopiëren. Zet het kenmerk in het veld "Betalingskenmerk" van je overschrijving — zonder kenmerk kan de Belastingdienst je betaling niet verwerken.',
                    'Het betalingskenmerk berekent Easy uit je omzetbelastingnummer (Instellingen op de btw-pagina). Let op: bij een eenmanszaak is dat een ander nummer dan het btw-id op je facturen. Geen nummer ingesteld? Plak dan het kenmerk dat je na het insturen ziet in Mijn Belastingdienst Zakelijk. Vink daarna "Aangifte ingediend" en "Betaald" af; de historie staat op de kaart.',
                ]],
                ['Herinnering en dashboard', [
                    'Twee weken en drie dagen vóór de deadline (een maand na afloop van het tijdvak; bij jaaraangifte 31 maart) krijg je een e-mail met de cijfers en de betaalgegevens — zolang je het tijdvak niet als aangegeven hebt gemarkeerd. Op het dashboard zie je een balk zodra een aangifte openstaat.',
                    'De cijfers worden berekend op factuurdatum (factuurstelsel) over alle verstuurde facturen en creditnota\'s. De voorbelasting is zo volledig als je inkoopadministratie — boek dus al je inkoop in. Controleer de aangifte altijd met je boekhouder; EasyInvoice stuurt (nog) niets naar de Belastingdienst.',
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
                ['QR-code op de factuur-PDF', [
                    'Met Mollie gekoppeld staat er automatisch een QR-code naast de betaalinstructie op de factuur-PDF ("Scan & betaal"). Je klant scant de code met de telefoon, komt in het beveiligde portaal en betaalt direct via iDEAL — handig als de factuur geprint wordt of intern wordt doorgestuurd. De QR verschijnt alleen op openstaande, verstuurde facturen; op concepten, creditnota\'s en betaalde facturen niet.',
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

        'jaaroverzicht' => [
            'category' => 'Rapporten',
            'title' => 'Het jaaroverzicht: omzet, kosten en resultaat',
            'intro' => 'Alles uit je facturatie in één rapport — de basis voor je aangifte inkomstenbelasting of voor je boekhouder.',
            'sections' => [
                ['Wat erin staat', [
                    'Ga naar Rapporten → Jaaroverzicht. Je ziet per jaar de omzet (exclusief btw, op factuurdatum, creditnota\'s tellen negatief, concepten niet mee), de kosten per categorie uit je ingeboekte inkoopfacturen, en de kilometeraftrek: al je geregistreerde zakelijke kilometers × € 0,23 (het tarief voor privévervoermiddelen). Onderaan de streep: het resultaat per kwartaal en per jaar, vergeleken met vorig jaar.',
                ]],
                ['PDF voor je boekhouder', [
                    'Met "Download PDF" krijg je het complete overzicht — inclusief kilometeradministratie en een toelichting op de grondslagen — als één document om mee te sturen naar je boekhouder.',
                ]],
                ['Wat je boekhouder nog toevoegt', [
                    'Het jaaroverzicht is bewust géén complete fiscale winst-en-verliesrekening. Wat er nog bij komt voor de aangifte inkomstenbelasting: afschrijvingen op bedrijfsmiddelen (zoals een bus of apparatuur), eventuele loonkosten, bijtelling bij een zakelijke auto, voorraadmutaties en de ondernemersaftrekken (zelfstandigenaftrek, startersaftrek, MKB-winstvrijstelling). Die vragen fiscale keuzes die bij een boekhouder of belastingadviseur thuishoren — het rapport benoemt dit ook zelf, zodat er nooit een half beeld ontstaat.',
                ]],
            ],
        ],

        'cashflow-prognose' => [
            'category' => 'Rapporten',
            'title' => 'De cashflow-prognose',
            'intro' => 'Zie in één oogopslag wat er de komende maanden naar verwachting binnenkomt en uitgaat — op basis van wat er al in Easy staat.',
            'sections' => [
                ['Wat je ziet', [
                    'Ga naar Rapporten → Cashflow. Per maand (de huidige plus drie vooruit) zie je de verwachte ontvangsten en uitgaven, het netto verschil en het cumulatieve verloop. De regel "Al vervallen" toont wat er nú al opeisbaar is: facturen waarvan de vervaldatum is verstreken, en inkoop die je zelf nog moet betalen.',
                ]],
                ['Waar de cijfers vandaan komen', [
                    'Ontvangsten: je openstaande facturen op hun vervaldatum, plus je terugkerende facturen (verwachte ontvangst = factuurdatum + betaaltermijn). Uitgaven: openstaande inkoopfacturen op hun vervaldatum, plus je vaste lasten op de boekingsdatum. Hoe beter je terugkerende facturen en vaste lasten zijn ingevuld, hoe scherper de prognose.',
                ]],
                ['Wat het bewust niet is', [
                    'Dit is een prognose, geen banksaldo: klanten betalen soms later dan de vervaldatum, en privé-opnames, belastingen en loonkosten staan niet in EasyInvoice. Facturen in het incassotraject tellen niet mee — die ontvangst is te onzeker. Gebruik het rapport om krappe maanden vroeg te zien aankomen, niet als boekhoudkundige waarheid.',
                ]],
            ],
        ],

        'e-mailteksten-aanpassen' => [
            'category' => 'Huisstijl',
            'title' => 'De factuur- en offertemail aanpassen',
            'intro' => 'Bepaal zelf het onderwerp en de tekst van de e-mails die met je facturen en offertes worden meegestuurd.',
            'sections' => [
                ['Zo werkt het', [
                    'Ga naar Instellingen → E-mailteksten. Vul een eigen onderwerp en/of bericht in voor de factuurmail en de offertemail. Gebruik variabelen zoals {klant}, {factuurnummer}, {bedrag} en {vervaldatum} — die worden bij het versturen automatisch ingevuld. Laat je een veld leeg, dan gebruikt Easy de standaardtekst.',
                    'Begin je factuurbericht zelf met een aanhef (bijv. "Beste {klant},"): bij een eigen tekst vervalt de standaard-aanhef. Bij de offertemail staat de aanhef er als kop automatisch boven.',
                ]],
                ['Wat automatisch blijft', [
                    'De PDF-bijlage, de knop naar het klantenportaal ("Bekijk en betaal online"), verrekeningsmeldingen en je voetnoot blijven gewoon onder je tekst staan — daar hoef je niets voor te doen. Vul je bij een specifieke offerte een eigen introtekst in, dan gaat die vóór op de standaardtekst uit de instellingen.',
                ]],
                ['Engelstalige klanten', [
                    'Een eigen tekst wordt letterlijk gebruikt voor álle klanten — ook klanten met taalinstelling Engels. De standaardteksten volgen wél automatisch de taal van de klant. Werk je veel internationaal, overweeg dan de standaardtekst te houden, of schrijf je eigen tekst tweetalig. De teksten van herinneringen en aanmaningen stel je apart in onder Instellingen → Herinneringen.',
                ]],
            ],
        ],

        'bedankmail-na-betaling' => [
            'category' => 'Betalingen & incasso',
            'title' => 'Bedankmail na betaling',
            'intro' => 'Zodra een factuur volledig is betaald, kan EasyInvoice je klant automatisch een vriendelijk bedankje sturen — in jouw huisstijl, met de factuur als betaalbewijs.',
            'sections' => [
                ['Wat je klant ontvangt', [
                    'Een korte, vriendelijke e-mail met een overzicht van de betaling (factuurnummer, bedrag, ontvangstdatum en betaalwijze), een knop naar het klantenportaal en de factuur als PDF met het stempel BETAALD — handig als betaalbewijs voor zijn administratie. Afzendernaam, logo en kleur volgen je huisstijl of handelsnaam; de standaardtekst volgt automatisch de taal van de klant (Nederlands of Engels).',
                ]],
                ['Aanzetten en aanpassen', [
                    'Ga naar Instellingen → E-mailteksten en zet "Bedankmail na betaling" aan. Wil je een eigen onderwerp of tekst? Gebruik variabelen zoals {klant}, {factuurnummer}, {bedrag}, {betaaldatum} en {betaalwijze}. Met "Bekijk voorbeeld" zie je de mail vooraf, precies zoals je klant hem krijgt.',
                    'Nieuwe administraties hebben de bedankmail standaard aan; bestaande administraties zetten hem zelf aan, zodat er niets ongevraagd verandert.',
                ]],
                ['Wanneer hij wordt verstuurd', [
                    'Bij een betaling via de bankkoppeling of via iDEAL (Mollie) gaat de bedankmail automatisch, direct nadat de factuur volledig is voldaan. Boek je een betaling handmatig, dan staat in het formulier een vinkje "Klant bedanken per e-mail" — voorgevinkt als de instelling aanstaat, en per betaling uit te zetten.',
                    'Er wordt nooit bedankt bij een deelbetaling, een afboeking, een verrekening of een creditnota, en nooit als de klant geen e-mailadres heeft. Per factuur gaat er maximaal één bedankmail automatisch uit; via de knop "Bedankmail sturen" op de factuurpagina kun je hem altijd handmatig (opnieuw) versturen. In de factuurhistorie zie je wanneer hij is verstuurd.',
                ]],
                ['Vraag om een review', [
                    'Vul bij de instelling een reviewlink in (bijvoorbeeld je Google-, Trustpilot- of Klantenvertellen-pagina) en de bedankmail krijgt een knop "Laat een review achter". Direct na een betaling is hét moment om erom te vragen: de klus is af en de klant is tevreden.',
                ]],
            ],
        ],

        'peppol-e-facturen' => [
            'category' => 'Facturen',
            'title' => 'E-facturen via Peppol verzenden en ontvangen',
            'intro' => 'Met Peppol lever je facturen rechtstreeks af in het boekhoudpakket van je klant en ontvang je inkoopfacturen automatisch in je Postvak IN — zonder e-mail ertussen.',
            'sections' => [
                ['Activeren', [
                    'Ga naar Instellingen → Koppelingen en klik op "Peppol activeren". Je administratie wordt op je KvK-nummer geregistreerd als Peppol-deelnemer (ID 0106:kvknummer). Daarna rondt een tekenbevoegd persoon eenmalig een online identiteitscontrole af — dat is verplicht voor iedereen op het netwerk en duurt een paar minuten. Niet zelf tekenbevoegd? Stuur de link door. Zodra de status op "Actief" staat, kun je verzenden en ontvangen.',
                    'Zorg dat KvK-nummer, adres en e-mailadres bij Bedrijfsgegevens zijn ingevuld; die gegevens gaan mee in de registratie.',
                ]],
                ['Verzenden', [
                    'EasyInvoice controleert per klant (op KvK-nummer of een eigen Peppol-ID op de klantkaart) of die op Peppol bereikbaar is; je ziet dan een ⚡-badge op de factuur. Na het versturen verschijnt de knop "Via Peppol afleveren". De e-factuur gaat als NLCIUS — of als Peppol BIS 3 wanneer de ontvanger alleen dat accepteert — en de referentie komt in de factuurhistorie.',
                ]],
                ['Ontvangen', [
                    'Geef je leveranciers je Peppol-ID door (staat bij Koppelingen). Hun e-facturen verschijnen automatisch in Postvak IN, met leverancier, factuurnummer, datums en btw per tarief al ingevuld en een PDF-weergave. Controleren, categorie kiezen, inboeken — klaar. Creditnota\'s komen met negatieve bedragen binnen.',
                ]],
            ],
        ],

        'postvak-in' => [
            'category' => 'Inkoop',
            'title' => 'Inkoopfacturen per e-mail aanleveren (Postvak IN)',
            'intro' => 'Stuur bonnen en facturen naar je eigen inboek-adres — ze staan klaar in het Postvak IN, inclusief scan & herken.',
            'sections' => [
                ['Je inboek-adres', [
                    'Ga naar Inkoop → Postvak IN: daar staat je persoonlijke inboek-adres (bijv. bon-a1b2c3d4e5f6@…). Stuur of forward e-mails met een PDF of foto als bijlage naar dat adres — van jezelf, of rechtstreeks van leveranciers. Alleen de bijlagen (PDF, PNG, JPG of WEBP, max 10 MB) worden bewaard; de mailtekst niet.',
                    'Raakt het adres bekend bij spammers? Met "Nieuw adres" genereer je direct een vers adres; het oude vervalt meteen.',
                ]],
                ['Automatisch herkend: het boekingsvoorstel', [
                    'Nieuwe items worden binnen een paar minuten automatisch gelezen door de AI-herkenning. Op het kaartje verschijnt dan een boekingsvoorstel: leverancier, datum, bedrag en categorie. Klopt het? Met "Direct inboeken" staat de inkoopfactuur er met één klik in, inclusief het bestand als bijlage. Liever eerst kijken? "Controleer eerst" opent het inkoopformulier met alles vooringevuld en het bestand ernaast.',
                    'Sloten de herkende bedragen niet helemaal op elkaar aan, dan staat er een waarschuwing bij het voorstel — controleer dan eerst. Lukt het herkennen niet (bijv. een onscherpe foto), dan boek je gewoon handmatig in zoals voorheen.',
                ]],
                ['Inboeken vanuit het postvak', [
                    'Elk aangeleverd bestand verschijnt als kaartje onder "Te verwerken", met een voorbeeld en de afzender. Klik op "Inboeken" en het inkoopformulier opent met het bestand er groot naast — inclusief de knop Scan & herken, die leverancier, datum en bedragen alvast invult. Bij het opslaan wordt het bestand automatisch als bijlage aan de inkoopfactuur gekoppeld en verhuist het item naar "Afgehandeld".',
                    'Niets mee doen? Wijs het item af (blijft zichtbaar onder Afgehandeld) of verwijder het definitief.',
                ]],
            ],
        ],

        'termijnfacturen' => [
            'category' => 'Facturen',
            'title' => 'Termijnfacturen: een offerte in delen factureren',
            'intro' => 'Grote projecten factureer je in termijnen — bijvoorbeeld 30% bij opdracht en 70% bij oplevering.',
            'sections' => [
                ['Een termijnplan opstellen', [
                    'Open een verstuurde of geaccepteerde offerte en klik op "In termijnen". Kies een voorinstelling (30/70, 50/50 of 3×⅓) of stel je eigen verdeling samen; de percentages moeten samen 100% zijn. Elke termijn krijgt een omschrijving die straks letterlijk op de factuur komt, zoals "Termijn 1: bij opdracht".',
                ]],
                ['Factureren per termijn', [
                    'Op de offerte zie je het plan met de voortgang. Klik bij de eerstvolgende open termijn op "Maak factuur": er verschijnt een gewone conceptfactuur met het termijnbedrag, netjes uitgesplitst per BTW-tarief, onder dezelfde handelsnaam en in dezelfde taal als de offerte. Je controleert en verstuurt hem zoals altijd — herinneringen, iDEAL-betaallink en het klantenportaal werken gewoon.',
                    'Termijnen factureer je op volgorde. De laatste termijn is automatisch het restant, zodat de som van alle termijnfacturen tot op de cent gelijk is aan de offertesom — ook als de percentages tot afrondingsverschillen zouden leiden.',
                ]],
                ['Goed om te weten', [
                    'Zolang er nog geen termijn is gefactureerd kun je het plan aanpassen of verwijderen; daarna ligt het vast. Een offerte met termijnplan kan niet meer in één keer worden omgezet (en andersom). Op de offerte zie je per termijn de factuurstatus, dus ook of een termijn al betaald is.',
                ]],
            ],
        ],

        'korting-geven' => [
            'category' => 'Facturen',
            'title' => 'Korting geven op factuurregels',
            'intro' => 'Geef per regel een kortingspercentage — de klant ziet de originele prijs én de korting op de factuur.',
            'sections' => [
                ['Zo werkt het', [
                    'In het factuur- en offerteformulier staat naast de prijs een kolom "Korting". Vul daar een percentage in (bijv. 10) en het regeltotaal rekent direct mee. De BTW wordt automatisch berekend over het verlaagde bedrag — korting gaat immers vóór de BTW.',
                ]],
                ['Wat de klant ziet', [
                    'Op de PDF verschijnt (alleen als er ergens korting is gegeven) een kolom Korting: de originele stuksprijs blijft staan, de korting staat ernaast en het regeltotaal is het verlaagde bedrag. Zo ziet je klant precies wat hij bespaart. De korting verhuist automatisch mee bij offerte → factuur, terugkerende facturen, dupliceren en creditnota\'s, en de UBL-e-factuur blijft kloppend.',
                ]],
                ['Vast kortingsbedrag of totaalkorting', [
                    'Wil je een vast bedrag korting geven in plaats van een percentage, voeg dan een aparte regel toe met een negatief bedrag (bijv. "Actiekorting − € 50"). Voor korting op het hele document zet je hetzelfde percentage op alle regels.',
                ]],
            ],
        ],

        'ouderdomsanalyse-debiteuren' => [
            'category' => 'Rapporten',
            'title' => 'Debiteuren: wie staat er hoe lang open?',
            'intro' => 'De ouderdomsanalyse laat per klant zien hoe lang facturen al openstaan — zodat je weet waar je achteraan moet.',
            'sections' => [
                ['De emmers', [
                    'Ga naar Rapporten → Debiteuren. Openstaande facturen staan per klant verdeeld over "nog niet vervallen", 1–30, 31–60, 61–90 en 90+ dagen over de vervaldatum. Hoe verder naar rechts, hoe groter het risico dat er nooit betaald wordt — rijen met posten ouder dan 60 dagen lichten op. Onderaan staan de langst vervallen facturen, met directe links.',
                ]],
                ['Wat je ermee doet', [
                    'De automatische herinneringen en aanmaningen (Instellingen → Herinneringen) vangen het gros af; dit rapport is voor het overzicht en de hardnekkige gevallen. Voor facturen die maar blijven staan is er het incassotraject — facturen die daar al in zitten zijn in het rapport gemarkeerd.',
                    'Tip: het rapport Klantomzet toont sinds deze update ook de bestede uren en het effectieve uurtarief per klant — samen met de ouderdomsanalyse zie je zo welke klanten waardevol zijn én netjes betalen.',
                ]],
            ],
        ],

        'vaste-lasten' => [
            'category' => 'Inkoop',
            'title' => 'Vaste lasten automatisch inboeken',
            'intro' => 'Huur, software-abonnementen, verzekeringen: stel ze eenmalig in en Easy boekt ze voortaan automatisch in als inkoopfactuur.',
            'sections' => [
                ['Een vaste last aanmaken', [
                    'Ga naar Inkoop → Vaste lasten en klik op "Nieuwe vaste last". Vul de leverancier, categorie, frequentie (wekelijks t/m jaarlijks), de datum van de volgende inboeking en het bedrag per periode in. Vinkje "direct op betaald zetten" aan voor kosten via automatische incasso — de inboeking krijgt dan meteen de juiste betaalstatus en betaalwijze.',
                    'Sneller: open een bestaande inkoopfactuur en klik op "Maak terugkerend" — dezelfde kosten worden dan voortaan maandelijks ingeboekt (frequentie daarna aan te passen).',
                ]],
                ['Wat er automatisch gebeurt', [
                    'Elke ochtend boekt Easy de vaste lasten in die aan de beurt zijn, als gewone inkoopfactuur met de notitie "Automatisch ingeboekt". De BTW telt direct mee als voorbelasting (rubriek 5b) in je BTW-overzicht, en de kosten verschijnen in je dashboards en het jaaroverzicht. Stond de app een tijdje stil, dan worden gemiste periodes dag voor dag ingehaald.',
                ]],
                ['Beheren', [
                    'Pauzeren en hervatten kan met één klik; een einddatum stopt het profiel vanzelf (handig bij opzeggingen). Verwijder je een profiel, dan blijven de al ingeboekte inkoopfacturen gewoon bewaard. Maandprofielen houden vast aan de dag van de startdatum — gestart op de 31e wordt in februari de 28e en in maart weer de 31e.',
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

        'strippenkaarten' => [
            'category' => 'Uren',
            'title' => 'Strippenkaarten: vooraf betaalde urenbundels',
            'intro' => 'Verkoop een urenbundel vooraf — geschreven uren tellen automatisch af van het tegoed en komen nooit dubbel op een factuur.',
            'sections' => [
                ['Een strippenkaart verkopen', [
                    'Ga naar Verkoop → Uren en klik op "Nieuwe strippenkaart". Kies de klant, het aantal uren en de bundelprijs (en eventueel een geldigheidsdatum). Met "Factureer" maak je direct de conceptfactuur voor de bundel — één regel met het tegoed erop, die je zoals altijd controleert en verstuurt.',
                ]],
                ['Automatisch aftellen', [
                    'Schrijf je daarna uren voor die klant (handmatig of met de timer), dan worden ze automatisch van het tegoed afgeschreven — je ziet het al bij het invoeren ("wordt afgeschreven van…"). Gedekte uren krijgen de status "Strippenkaart" en verschijnen niet meer in "Klaar om te factureren": ze zijn immers al betaald. Per kaart zie je een balkje met het gebruikte en resterende tegoed.',
                ]],
                ['Spelregels', [
                    'Een urenregel wordt alleen afgeschreven als hij volledig in het resterende tegoed past — anders blijft hij gewoon factureerbaar, zodat er nooit halve afschrijvingen ontstaan. Een verlopen kaart schrijft niet meer af (bestaande afschrijvingen blijven staan). Verwijder je een kaart, dan komen de gedekte uren weer als factureerbare uren in de lijst.',
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

        'offertes-digitaal-ondertekenen' => [
            'category' => 'Samenwerken',
            'title' => 'Offertes digitaal laten ondertekenen',
            'intro' => 'Je klant geeft akkoord met een digitale handtekening in het beveiligde portaal — rechtsgeldig, zonder printen en scannen.',
            'sections' => [
                ['Zo werkt het voor je klant', [
                    'In de offertemail staat de knop "Bekijk en onderteken online". Je klant bevestigt eerst zijn e-mailadres met een eenmalige code (dezelfde beveiliging als het factuurportaal), ziet de volledige offerte en zet zijn handtekening in het tekenveld — met de muis of gewoon met een vinger op de telefoon. Naam invullen, akkoord aanvinken, klaar.',
                    'Liever niet akkoord? Afwijzen kan ook, met een toelichting die jij te lezen krijgt.',
                ]],
                ['Rechtsgeldig bewijsdossier', [
                    'Bij de handtekening legt EasyInvoice vast: het geverifieerde e-mailadres, de ingevulde naam, de handtekening zelf, het tijdstip en het IP-adres. Dat dossier zie je op de offertepagina, en de handtekening komt ook op de offerte-PDF te staan — die kan je klant direct downloaden voor zijn eigen administratie.',
                ]],
                ['Bevestiging voor je klant', [
                    'Zodra je klant tekent, krijgt hij automatisch een bevestiging in jouw huisstijl: datum en naam van de ondertekenaar, het totaal, het termijnplan als dat er is, een "hoe nu verder"-tekst en de ondertekende offerte als PDF — zo hebben jullie allebei hetzelfde document. Markeer je een offerte zelf als geaccepteerd (bijv. akkoord per telefoon), dan kies je per offerte of de bevestiging meegaat; opnieuw sturen kan altijd vanaf de offertepagina.',
                    'Onderwerp en tekst pas je aan bij Instellingen → E-mailteksten (met "Bekijk voorbeeld"); daar zet je de automatische bevestiging ook uit als je hem niet wilt.',
                ]],
                ['En daarna', [
                    'Jij krijgt direct een e-mail zodra er is ondertekend (of afgewezen). De offerte springt op geaccepteerd en je zet hem met één klik om naar een conceptfactuur. Handig: op de offertepagina kun je de ondertekenlink ook kopiëren om hem bijvoorbeeld via WhatsApp te sturen.',
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
