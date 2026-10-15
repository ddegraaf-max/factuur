<?php

/*
 * Fachora — platform voor gecontroleerde vakmannen in Polen (merk 'fachora',
 * markt pl). Wat hier staat is wat per omgeving of per beleid kan verschillen:
 * categorieën, talen, de platformfee, de termijnen in de opdrachtcyclus en de
 * sleutels van de externe bronnen. De regels zelf staan in App\Services\Fachora.
 *
 * Uitgangspunt bij alles wat met bedrijven te maken heeft: Fachora stuurt
 * NOOIT ongevraagd e-mail of sms naar een bedrijf uit een openbaar register.
 * Een bedrijf hoort pas iets van ons nadat het zelf zijn profiel heeft
 * geclaimd (of op een gekochte opt-in-lijst staat met vastgelegde toestemming).
 * Zie App\Services\Fachora\ConsentPolicy.
 */
return [
    // env() alleen hier: config wordt in productie gecachet.
    'protect' => (bool) env('FACHORA_PROTECT', true),           // betaling via Stripe Connect met uitgestelde uitbetaling
    'ceidg_token' => env('CEIDG_TOKEN'),                          // JWT van dane.biznes.gov.pl (CEIDG API v3)
    'ceidg_url' => rtrim(env('CEIDG_URL', 'https://dane.biznes.gov.pl/api/ceidg/v3'), '/'),
    'geocode_url' => rtrim(env('FACHORA_GEOCODE_URL', 'https://nominatim.openstreetmap.org'), '/'),
    'geocode_contact' => env('FACHORA_GEOCODE_CONTACT', 'czesc@fachora.pl'),

    // Platformfee over een uitgevoerde opdracht (percentage van het brutobedrag).
    'fee_percent' => (float) env('FACHORA_FEE_PERCENT', 10),
    // De eerste maanden na verificatie een lager tarief: instappen kost niets extra's.
    'intro_fee_percent' => (float) env('FACHORA_INTRO_FEE_PERCENT', 5),
    'intro_days' => 90,

    // Opdrachtcyclus.
    'max_offers' => 5,          // aan zoveel bedrijven tegelijk aanbieden
    'offer_hours' => 48,        // reageren binnen zoveel uur
    'remind_hours' => 24,       // één herinnering aan een bedrijf dat nog niet reageerde
    'auto_accept_days' => 5,    // na "klaar" zonder reactie van de klant: stilzwijgend akkoord
    'dispute_hours' => 48,      // het bedrijf moet binnen zoveel uur op een geschil reageren
    'confirm_days' => 7,        // een onbevestigde aanvraag verdwijnt na zoveel dagen
    'cancel_free_hours' => 24,  // tot zoveel uur voor de afspraak kan de klant kosteloos annuleren
    'default_warranty_months' => 12,
    'min_rating' => 4.2,        // Fachora Standard 10: onder dit cijfer (bij ≥ 5 beoordelingen) volgt een signaal
    'terms_version' => '2026-10',

    // Hoeveel brieven per pdf (post naar unclaimed bedrijven: geen elektronische communicatie, dus toegestaan).
    'letters_per_batch' => 100,

    /*
     * Categorieën. De sleutel is de slug in de URL (/uslugi/hydraulik), 'pkd'
     * de Poolse PKD-codes die erbij horen (voor de import uit CEIDG), 'icon'
     * een beeldmerk uit de set in resources/js/fachora-icons.js en in de Blade-partial.
     */
    'categories' => [
        'hydraulik' => ['pl' => 'Hydraulik', 'en' => 'Plumber', 'icon' => 'tap', 'pkd' => ['43.22.Z'],
            'examples' => ['pl' => 'wyciek, zatkany odpływ, wymiana baterii, montaż WC', 'en' => 'leak, blocked drain, tap replacement, toilet installation']],
        'elektryk' => ['pl' => 'Elektryk', 'en' => 'Electrician', 'icon' => 'bolt', 'pkd' => ['43.21.Z'],
            'examples' => ['pl' => 'gniazdka, oświetlenie, rozdzielnia, awaria', 'en' => 'sockets, lighting, fuse box, power failure']],
        'ogrzewanie' => ['pl' => 'Ogrzewanie', 'en' => 'Heating', 'icon' => 'radiator', 'pkd' => ['43.22.Z'],
            'examples' => ['pl' => 'piec, kocioł gazowy, grzejniki, przegląd', 'en' => 'boiler, gas furnace, radiators, service']],
        'klimatyzacja' => ['pl' => 'Klimatyzacja', 'en' => 'Air conditioning', 'icon' => 'snow', 'pkd' => ['43.22.Z'],
            'examples' => ['pl' => 'montaż, serwis, czyszczenie, pompa ciepła', 'en' => 'installation, service, cleaning, heat pump']],
        'slusarz' => ['pl' => 'Ślusarz', 'en' => 'Locksmith', 'icon' => 'key', 'pkd' => ['80.20.Z', '95.29.Z', '43.32.Z'],
            'examples' => ['pl' => 'awaryjne otwarcie, wymiana zamka, zatrzaśnięte drzwi', 'en' => 'lock-out, lock replacement, slammed door']],
        'okna-drzwi' => ['pl' => 'Okna i drzwi', 'en' => 'Windows and doors', 'icon' => 'window', 'pkd' => ['43.32.Z'],
            'examples' => ['pl' => 'regulacja, montaż, uszczelki, rolety', 'en' => 'adjustment, installation, seals, blinds']],
        'dach' => ['pl' => 'Dach', 'en' => 'Roofing', 'icon' => 'roof', 'pkd' => ['43.91.Z'],
            'examples' => ['pl' => 'przeciek, rynny, dachówki, obróbki', 'en' => 'leak, gutters, tiles, flashing']],
        'remont' => ['pl' => 'Remonty', 'en' => 'Renovation', 'icon' => 'roller', 'pkd' => ['43.31.Z', '43.33.Z', '43.34.Z', '43.39.Z', '43.99.Z'],
            'examples' => ['pl' => 'malowanie, płytki, gładzie, łazienka', 'en' => 'painting, tiling, plastering, bathroom']],
        'agd' => ['pl' => 'AGD', 'en' => 'Appliances', 'icon' => 'washer', 'pkd' => ['95.22.Z'],
            'examples' => ['pl' => 'pralka, zmywarka, lodówka, piekarnik', 'en' => 'washing machine, dishwasher, fridge, oven']],
        'mechanik' => ['pl' => 'Mechanik', 'en' => 'Car mechanic', 'icon' => 'car', 'pkd' => ['45.20.Z'],
            'examples' => ['pl' => 'przegląd, hamulce, opony, diagnostyka', 'en' => 'service, brakes, tyres, diagnostics']],
        'ogrod' => ['pl' => 'Sprzęt ogrodniczy', 'en' => 'Garden equipment', 'icon' => 'mower', 'pkd' => ['95.22.Z', '33.12.Z'],
            'examples' => ['pl' => 'kosiarka, piła, nożyce, serwis i ostrzenie', 'en' => 'lawn mower, chainsaw, hedge trimmer, service and sharpening']],
        'szkodniki' => ['pl' => 'Szkodniki', 'en' => 'Pest control', 'icon' => 'bug', 'pkd' => ['81.29.Z'],
            'examples' => ['pl' => 'myszy, szczury, muchy, komary, osy, dezynsekcja', 'en' => 'mice, rats, flies, mosquitoes, wasps, fumigation']],
        'kominiarz' => ['pl' => 'Kominiarz i kominki', 'en' => 'Chimney and fireplace', 'icon' => 'fireplace', 'pkd' => ['81.22.Z', '43.29.Z'],
            'examples' => ['pl' => 'przegląd i czyszczenie komina, serwis kominka, wkład, piec', 'en' => 'chimney inspection and sweeping, fireplace service, insert, stove']],
    ],

    // Talen die een bedrijf kan spreken (harde filter voor de klant) en de niveaus.
    'languages' => [
        'pl' => ['pl' => 'Polski', 'en' => 'Polish', 'flag' => '🇵🇱'],
        'en' => ['pl' => 'Angielski', 'en' => 'English', 'flag' => '🇬🇧'],
        'de' => ['pl' => 'Niemiecki', 'en' => 'German', 'flag' => '🇩🇪'],
        'nl' => ['pl' => 'Niderlandzki', 'en' => 'Dutch', 'flag' => '🇳🇱'],
        'ua' => ['pl' => 'Ukraiński', 'en' => 'Ukrainian', 'flag' => '🇺🇦'],
    ],
    'levels' => [
        'native' => ['pl' => 'ojczysty', 'en' => 'native'],
        'b2' => ['pl' => 'dobrze', 'en' => 'good'],
        'b1' => ['pl' => 'podstawowo', 'en' => 'basic'],
    ],

    // Urgentie van een aanvraag.
    'urgencies' => ['now', 'week', 'month', 'date'],

    /*
     * Middelpunten van Poolse steden: vangnet als de geocoder niet bereikbaar
     * is (of in tests). Sleutel zonder diakrieten en in kleine letters.
     */
    'cities' => [
        'warszawa' => [52.2297, 21.0122], 'krakow' => [50.0647, 19.9450], 'lodz' => [51.7592, 19.4560],
        'wroclaw' => [51.1079, 17.0385], 'poznan' => [52.4064, 16.9252], 'gdansk' => [54.3520, 18.6466],
        'szczecin' => [53.4285, 14.5528], 'bydgoszcz' => [53.1235, 18.0084], 'lublin' => [51.2465, 22.5684],
        'bialystok' => [53.1325, 23.1688], 'katowice' => [50.2649, 19.0238], 'gdynia' => [54.5189, 18.5305],
        'czestochowa' => [50.8118, 19.1203], 'radom' => [51.4027, 21.1471], 'torun' => [53.0138, 18.5984],
        'sosnowiec' => [50.2863, 19.1040], 'rzeszow' => [50.0412, 21.9991], 'kielce' => [50.8661, 20.6286],
        'gliwice' => [50.2945, 18.6714], 'olsztyn' => [53.7784, 20.4801], 'zabrze' => [50.3249, 18.7857],
        'bielsko-biala' => [49.8224, 19.0584], 'bytom' => [50.3484, 18.9157], 'zielona gora' => [51.9356, 15.5062],
        'rybnik' => [50.1022, 18.5463], 'ruda slaska' => [50.2558, 18.8555], 'opole' => [50.6751, 17.9213],
        'tychy' => [50.1372, 18.9664], 'gorzow wielkopolski' => [52.7368, 15.2288], 'elblag' => [54.1522, 19.4088],
        'plock' => [52.5463, 19.7065], 'dabrowa gornicza' => [50.3249, 19.1950], 'walbrzych' => [50.7714, 16.2843],
        'wloclawek' => [52.6483, 19.0678], 'tarnow' => [50.0121, 20.9858], 'chorzow' => [50.2975, 18.9540],
        'koszalin' => [54.1943, 16.1715], 'kalisz' => [51.7611, 18.0910], 'legnica' => [51.2070, 16.1553],
        'grudziadz' => [53.4837, 18.7536], 'jaworzno' => [50.2050, 19.2749], 'slupsk' => [54.4641, 17.0287],
        'jastrzebie-zdroj' => [49.9500, 18.5833], 'nowy sacz' => [49.6249, 20.6917], 'jelenia gora' => [50.9044, 15.7344],
        'siedlce' => [52.1676, 22.2902], 'myslowice' => [50.2080, 19.1668], 'konin' => [52.2230, 18.2511],
        'piotrkow trybunalski' => [51.4055, 19.7030], 'inowroclaw' => [52.7983, 18.2612], 'lubin' => [51.4007, 16.2013],
        'ostrowiec swietokrzyski' => [50.9294, 21.3854], 'suwalki' => [54.1115, 22.9309], 'stargard' => [53.3367, 15.0501],
        'gniezno' => [52.5349, 17.5826], 'ostrow wielkopolski' => [51.6494, 17.8107], 'siemianowice slaskie' => [50.3275, 19.0292],
        'glogow' => [51.6638, 16.0845], 'pabianice' => [51.6646, 19.3546], 'leszno' => [51.8406, 16.5749],
        'zory' => [50.0450, 18.7006], 'zamosc' => [50.7231, 23.2516], 'pruszkow' => [52.1706, 20.8119],
        'lomza' => [53.1781, 22.0593], 'elk' => [53.8282, 22.3647], 'tarnowskie gory' => [50.4446, 18.8617],
        'tomaszow mazowiecki' => [51.5308, 20.0088], 'chelm' => [51.1431, 23.4716], 'mielec' => [50.2874, 21.4238],
        'kedzierzyn-kozle' => [50.3498, 18.2261], 'przemysl' => [49.7838, 22.7677], 'stalowa wola' => [50.5826, 22.0533],
        'tczew' => [54.0922, 18.7779], 'biala podlaska' => [52.0325, 23.1149], 'belchatow' => [51.3686, 19.3570],
        'swidnica' => [50.8441, 16.4902], 'zgierz' => [51.8556, 19.4064], 'piekary slaskie' => [50.3835, 18.9521],
        'legionowo' => [52.4042, 20.9262], 'ostroleka' => [53.0862, 21.5720], 'bedzin' => [50.3263, 19.1270],
        'skierniewice' => [51.9546, 20.1573], 'zyrardow' => [52.0490, 20.4459],
        'lowicz' => [52.1069, 19.9450], 'sochaczew' => [52.2289, 20.2387], 'kutno' => [52.2305, 19.3644],
        'sieradz' => [51.5956, 18.7301], 'radomsko' => [51.0676, 19.4426], 'piaseczno' => [52.0742, 21.0224],
        'wolomin' => [52.3446, 21.2422], 'otwock' => [52.1053, 21.2614], 'grodzisk mazowiecki' => [52.1056, 20.6238],
        'minsk mazowiecki' => [52.1793, 21.5723], 'marki' => [52.3202, 21.1053], 'zabki' => [52.2926, 21.1114],
        'jozefow' => [52.1384, 21.2367], 'konstancin-jeziorna' => [52.0797, 21.1153], 'lomianki' => [52.3339, 20.8830],
        'wieliczka' => [49.9873, 20.0646], 'skawina' => [49.9748, 19.8280], 'zakopane' => [49.2992, 19.9496],
        'sopot' => [54.4418, 18.5601], 'swinoujscie' => [53.9100, 14.2473], 'kolobrzeg' => [54.1757, 15.5834],
    ],
];
