<?php

/*
|--------------------------------------------------------------------------
| Voorbeeldfacturen
|--------------------------------------------------------------------------
| Publieke voorbeelden (/voorbeelden/{slug}) met een echte pdf, gemaakt met
| dezelfde generator als de gratis factuur (App\Support\FreeInvoicePdf).
| Elke pagina legt uit wat er op het voorbeeld staat en waarom.
|
|   'title'   => H1
|   'intro'   => inleiding (ook de meta-description)
|   'updated' => datum laatste wijziging (Y-m-d); wordt lastmod in de sitemap
|   'points'  => lijst van [kop, alinea]: wat je op het voorbeeld ziet
|   'data'    => de gegevens voor de pdf (zelfde velden als het gratis-factuurformulier)
|   'related' => kennisbank-slugs
|   'begrippen' => begrippen-slugs
|
| De bedrijfsnamen zijn verzonnen; dat staat ook in de pdf.
*/

return [
    'examples' => [

        'factuur' => [
            'title' => 'Voorbeeld factuur voor een zzp\'er',
            'intro' => 'Een complete factuur van een zelfstandig ontwerper aan een bedrijf: alle verplichte gegevens, btw tegen 21 procent, een duidelijke betalingstermijn en een nette opbouw. Bekijk en download de pdf.',
            'updated' => '2026-10-15',
            'points' => [
                ['Alle verplichte gegevens staan erop', 'Bovenaan de naam, het adres, het KvK-nummer en het btw-identificatienummer van de afzender; daaronder de naam en het adres van de klant. Het factuurnummer is opeenvolgend (2026-0042), de factuurdatum en de vervaldatum staan erbij. Zonder deze gegevens is de factuur niet geldig en kan de klant de btw niet aftrekken.'],
                ['Per regel een omschrijving, aantal en prijs', 'Elke regel beschrijft wat er is geleverd, met aantal, stuksprijs exclusief btw en het btw-tarief. Zo kan de klant controleren wat hij betaalt, en jij later terugvinden wat je deed.'],
                ['Btw uitgesplitst', 'Onderaan staan het subtotaal exclusief btw, het btw-bedrag per tarief en het totaal. Komen er meerdere tarieven op één factuur voor, dan staat elk tarief apart.'],
                ['Betaalinstructie met kenmerk', 'De afsluiting noemt de betalingstermijn, het rekeningnummer en het factuurnummer als betaalkenmerk. In een factuurprogramma komt hier ook een betaallink bij, zodat de klant met één klik betaalt.'],
            ],
            'data' => [
                'van_bedrijf' => 'Studio Voorbeeld — grafisch ontwerp',
                'van_adres' => "Lindelaan 12\n1234 AB Hilversum",
                'van_kvk' => '12345678',
                'van_btw' => 'NL123456789B01',
                'van_iban' => 'NL00 BANK 0123 4567 89',
                'van_email' => 'hallo@studio-voorbeeld.nl',
                'aan_bedrijf' => 'Bakkerij De Korenaar',
                'aan_adres' => "Marktplein 3\n1234 CD Bussum",
                'factuurnummer' => '2026-0042',
                'factuurdatum' => '2026-10-01',
                'vervaldatum' => '2026-10-15',
                'btw_type' => 'normaal',
                'opmerking' => 'Graag binnen 14 dagen betalen op NL00 BANK 0123 4567 89 onder vermelding van factuurnummer 2026-0042. Dit is een voorbeeldfactuur; de bedrijven bestaan niet.',
                'regels' => [
                    ['omschrijving' => 'Ontwerp huisstijl: logo, kleuren en typografie', 'aantal' => 1, 'prijs' => 1250.00, 'btw' => 21],
                    ['omschrijving' => 'Visitekaartjes, drukwerk 350 grams', 'aantal' => 500, 'prijs' => 0.18, 'btw' => 21],
                    ['omschrijving' => 'Reiskosten presentatie op locatie', 'aantal' => 1, 'prijs' => 24.50, 'btw' => 21],
                ],
            ],
            'related' => ['factuureisen', 'eerste-factuur-als-zzper', 'factuurnummering'],
            'begrippen' => ['factuur', 'factuurnummer', 'btw-identificatienummer', 'betalingstermijn'],
        ],

        'factuur-btw-verlegd' => [
            'title' => 'Voorbeeld factuur met btw verlegd',
            'intro' => 'Zo ziet een factuur van een onderaannemer aan een aannemer eruit: geen btw-bedrag, wel de vermelding "btw verlegd" en het btw-nummer van de opdrachtgever, plus het deel voor de G-rekening.',
            'updated' => '2026-10-15',
            'points' => [
                ['Geen btw, wel "btw verlegd"', 'De factuur vermeldt alleen bedragen exclusief btw. In plaats van een btw-bedrag staat er dat de btw is verlegd naar de afnemer. De aannemer geeft de btw in zijn eigen aangifte aan en trekt hem daar weer af.'],
                ['Het btw-nummer van de opdrachtgever', 'Bij een verlegde factuur is het btw-identificatienummer van de klant verplicht. Het staat hier bij de adresgegevens van de aannemer. Ontbreekt het, dan is de factuur niet geldig.'],
                ['G-rekening', 'De aannemer wil een deel van de aanneemsom op de G-rekening storten om zijn ketenaansprakelijkheid te beperken. Het bedrag en het G-rekeningnummer staan daarom in de betaalinstructie.'],
                ['Wanneer je dit gebruikt', 'Bij onderaanneming in de bouw en de scheepsbouw, bij het uitlenen van personeel in die sectoren en bij diensten aan ondernemers in andere EU-landen. Voor particuliere klanten en rechtstreekse opdrachten buiten de bouwketen reken je gewoon btw.'],
            ],
            'data' => [
                'van_bedrijf' => 'Stukadoorsbedrijf Voorbeeld',
                'van_adres' => "Industrieweg 8\n1234 EF Weesp",
                'van_kvk' => '87654321',
                'van_btw' => 'NL987654321B01',
                'van_iban' => 'NL00 BANK 0987 6543 21',
                'van_email' => 'info@stukadoors-voorbeeld.nl',
                'aan_bedrijf' => 'Aannemersbedrijf Bouwmeester B.V.',
                'aan_adres' => "Bouwweg 100\n1234 GH Almere\nBtw-id: NL000000000B01",
                'factuurnummer' => '2026-0117',
                'factuurdatum' => '2026-10-03',
                'vervaldatum' => '2026-11-02',
                'btw_type' => 'verlegd',
                'opmerking' => 'Btw verlegd naar Aannemersbedrijf Bouwmeester B.V. (btw-id NL000000000B01). Van het factuurbedrag graag € 756,00 (loonsomdeel) storten op G-rekening NL00 BANK 0990 0990 99 en het restant op NL00 BANK 0987 6543 21, beide onder vermelding van 2026-0117. Dit is een voorbeeldfactuur; de bedrijven bestaan niet.',
                'regels' => [
                    ['omschrijving' => 'Stukadoren wanden woonkamer en hal, project Lindehof fase 2', 'aantal' => 140, 'prijs' => 22.50, 'btw' => 0],
                    ['omschrijving' => 'Stukadoren plafonds, project Lindehof fase 2', 'aantal' => 60, 'prijs' => 27.00, 'btw' => 0],
                    ['omschrijving' => 'Hoekprofielen en gaas', 'aantal' => 1, 'prijs' => 185.00, 'btw' => 0],
                ],
            ],
            'related' => ['btw-verlegd-bouw-onderaanneming', 'factureren-buitenland-btw-verlegd'],
            'begrippen' => ['btw-verlegd', 'g-rekening', 'btw-identificatienummer'],
        ],

        'factuur-zonder-btw' => [
            'title' => 'Voorbeeld factuur zonder btw (KOR)',
            'intro' => 'Doe je mee aan de kleineondernemersregeling, dan zet je geen btw op je factuur maar wel de vermelding dat de vrijstelling geldt. Dit voorbeeld laat zien hoe dat eruitziet.',
            'updated' => '2026-10-15',
            'points' => [
                ['Geen btw-regels, wel een vermelding', 'Er staat geen btw-tarief en geen btw-bedrag op de factuur; het totaal is gelijk aan het subtotaal. De factuur vermeldt dat de afzender is vrijgesteld van btw op grond van de kleineondernemersregeling.'],
                ['De rest blijft verplicht', 'Naam, adres, KvK-nummer en btw-id van de afzender, een doorlopend factuurnummer, datum en omschrijving: dat blijft allemaal nodig. Een btw-id heb je ook onder de KOR.'],
                ['Voor wie dit werkt', 'Vooral ondernemers met particuliere klanten en een omzet onder de 20.000 euro per jaar. Voor zakelijke klanten maakt de btw niet uit, want die trekken hem af; jij verliest onder de KOR wel de aftrek op je eigen inkopen.'],
                ['Let op de grens', 'Kom je in een jaar boven de 20.000 euro omzet, dan vervalt de vrijstelling vanaf dat moment en breng je over alles daarna gewoon btw in rekening. Houd je omzet dus in de gaten.'],
            ],
            'data' => [
                'van_bedrijf' => 'Yogastudio Voorbeeld',
                'van_adres' => "Dorpsstraat 45\n1234 JK Naarden",
                'van_kvk' => '11223344',
                'van_btw' => 'NL112233445B01',
                'van_iban' => 'NL00 BANK 0112 2334 45',
                'van_email' => 'les@yogastudio-voorbeeld.nl',
                'aan_bedrijf' => 'Mevrouw J. de Vries',
                'aan_adres' => "Meerweg 7\n1234 LM Naarden",
                'factuurnummer' => '2026-0088',
                'factuurdatum' => '2026-10-06',
                'vervaldatum' => '2026-10-20',
                'btw_type' => 'vrijgesteld',
                'opmerking' => 'Vrijgesteld van btw op grond van de kleineondernemersregeling (KOR). Graag binnen 14 dagen betalen op NL00 BANK 0112 2334 45 onder vermelding van 2026-0088. Dit is een voorbeeldfactuur; de bedrijven bestaan niet.',
                'regels' => [
                    ['omschrijving' => 'Tienrittenkaart yogalessen, geldig 3 maanden', 'aantal' => 1, 'prijs' => 120.00, 'btw' => 0],
                    ['omschrijving' => 'Yogamat, antislip', 'aantal' => 1, 'prijs' => 29.50, 'btw' => 0],
                ],
            ],
            'related' => ['kleineondernemersregeling-kor', 'factuureisen'],
            'begrippen' => ['kleineondernemersregeling', 'btw-identificatienummer', 'factuur'],
        ],

        'urenfactuur' => [
            'title' => 'Voorbeeld urenfactuur voor een consultant',
            'intro' => 'Een maandfactuur op basis van uren: per week het aantal uren tegen een uurtarief, reistijd apart, en het inkoopordernummer van de opdrachtgever als referentie. Bekijk en download de pdf.',
            'updated' => '2026-10-15',
            'points' => [
                ['Uren per periode als regels', 'Elke week staat als eigen regel met het aantal uren en het uurtarief. Zo ziet de opdrachtgever de opbouw zonder dat elke dag apart hoeft; een urenspecificatie per dag stuur je als bijlage mee.'],
                ['Reistijd en reiskosten apart', 'Reistijd tegen een lager tarief en reiskosten als eigen regels, met het afgesproken tarief. Dat voorkomt discussie en maakt de factuur controleerbaar tegen het contract.'],
                ['Inkoopordernummer als referentie', 'Grote opdrachtgevers betalen alleen facturen met hun inkoopordernummer. Het staat hier bovenaan in de omschrijving en in de betaalinstructie, zodat de crediteurenadministratie hem direct kan koppelen.'],
                ['Betalingstermijn van dertig dagen', 'Een grote onderneming moet een mkb-leverancier binnen dertig dagen betalen. De vervaldatum staat daarom op precies dertig dagen na de factuurdatum.'],
            ],
            'data' => [
                'van_bedrijf' => 'Voorbeeld Consultancy',
                'van_adres' => "Stationsplein 21\n1234 NP Amersfoort",
                'van_kvk' => '55667788',
                'van_btw' => 'NL556677889B01',
                'van_iban' => 'NL00 BANK 0556 6778 89',
                'van_email' => 'factuur@voorbeeld-consultancy.nl',
                'aan_bedrijf' => 'Logistiek Noord B.V.',
                'aan_adres' => "Havenkade 15\n1234 RS Zwolle\nInkoopordernummer PO-2026-3381",
                'factuurnummer' => '2026-0210',
                'factuurdatum' => '2026-10-01',
                'vervaldatum' => '2026-10-31',
                'btw_type' => 'normaal',
                'opmerking' => 'Inkoopordernummer PO-2026-3381. Urenspecificatie per dag als bijlage. Graag binnen 30 dagen betalen op NL00 BANK 0556 6778 89 onder vermelding van 2026-0210. Dit is een voorbeeldfactuur; de bedrijven bestaan niet.',
                'regels' => [
                    ['omschrijving' => 'Procesadvies logistiek, week 36 (uren)', 'aantal' => 24, 'prijs' => 95.00, 'btw' => 21],
                    ['omschrijving' => 'Procesadvies logistiek, week 37 (uren)', 'aantal' => 32, 'prijs' => 95.00, 'btw' => 21],
                    ['omschrijving' => 'Procesadvies logistiek, week 38 (uren)', 'aantal' => 28, 'prijs' => 95.00, 'btw' => 21],
                    ['omschrijving' => 'Reistijd (uren, 50% tarief)', 'aantal' => 6, 'prijs' => 47.50, 'btw' => 21],
                    ['omschrijving' => 'Reiskosten openbaar vervoer', 'aantal' => 1, 'prijs' => 86.40, 'btw' => 21],
                ],
            ],
            'related' => ['betalingstermijn-aanmanen', 'zelfstandigenaftrek-urencriterium-2026', 'factuureisen'],
            'begrippen' => ['betalingstermijn', 'urencriterium', 'factuur'],
        ],
    ],
];
