<?php

/*
 * Meerdere merken op één codebase.
 *
 * Het actieve merk komt uit APP_BRAND (per Railway-service één waarde:
 * easyinvoice.nl draait met 'easyinvoice', lopra.nl met 'lopra'). Alles wat
 * merkgebonden is — naam, domein, logo's, kleuren, positionering — staat hier,
 * zodat de code zelf nergens "EasyInvoice" of "Lopra" hoeft te kennen.
 * Gebruik App\Support\Brand (PHP), brand('…') (Blade) of $page.props.brand (Vue).
 *
 * Paden zijn relatief aan public/. Lopra-bestanden staan in public/brand/lopra.
 */
return [
    'active' => env('APP_BRAND', 'easyinvoice'),

    // Per omgeving te overschrijven zonder de merkdefinitie aan te raken
    // (bijv. zolang de mailbox van een nieuw domein nog niet bestaat).
    'overrides' => [
        'email' => env('BRAND_EMAIL'),
        'contact_inbox' => env('BRAND_CONTACT_INBOX'),
        'domain' => env('BRAND_DOMAIN'),
    ],

    'brands' => [

        'easyinvoice' => [
            'name' => 'EasyInvoice',
            'version_prefix' => 'Easy',
            'tagline' => 'Facturatie zonder gedoe',
            'positioning' => 'Nederlandse facturatie voor MKB en ZZP',
            'domain' => 'easyinvoice.nl',
            'email' => 'hallo@easyinvoice.nl',
            'contact_inbox' => null, // = email

            // Kleuren en typografie (de standaard-tokens in app.css / marketing-layout).
            'color' => '#E8231F',
            'color_dark' => '#B81814',
            'accent' => '#E8231F',
            'background' => '#FAFAF9',
            'fonts_url' => null,
            'theme_css' => null,

            // Beeldmateriaal.
            'mark' => '/images/easyinvoice-favicon-180.png',   // vierkant beeldmerk: navigatie
            'email_mark' => '/images/easyinvoice-favicon-180.png', // idem als PNG, voor e-mails (SVG wordt daar vaak niet getoond)
            'icon' => '/images/easyinvoice-icon-512.png',      // groot beeldmerk: inlogpagina, mails
            'favicon_32' => '/images/easyinvoice-favicon-32.png',
            'favicon_512' => '/images/easyinvoice-favicon-512.png',
            'favicon_svg' => null,
            'favicon_ico' => null,
            'apple_touch' => '/images/easyinvoice-favicon-180.png',
            'og_image' => '/images/og-easyinvoice.png',
            'wordmark' => null,          // logo met naam (SVG); null = beeldmerk + tekst
            'wordmark_dark' => null,

            // Teksten voor SEO, schema.org en de PWA.
            'seo_title' => 'EasyInvoice — Facturatie zonder gedoe vanaf € 12,10 per maand',
            'seo_description' => 'EasyInvoice — eenvoudige facturatie voor Nederlandse ondernemers. Facturen, BTW, klanten en incasso vanaf € 12,10 per maand (incl. 21% btw), met AI vanaf € 21,18.',
            'og_description' => 'Eenvoudige facturatie voor Nederlandse ondernemers. Facturen, offertes, BTW en incasso — met AI die je administratie invult.',
            'app_description' => 'Online facturatieprogramma voor Nederlandse ondernemers: facturen, offertes met digitale ondertekening, BTW-overzicht, incasso, urenregistratie en AI die de administratie invult.',
            'pwa_description' => 'Facturen, offertes en inkoop — snel en zonder gedoe.',
            'footer_description' => 'Eenvoudige facturatie voor Nederlandse ondernemers. Vanaf € 12,10 per maand (incl. 21% btw). Gemaakt in Bussum.',
            'auth_title' => 'Facturen maken zonder gedoe',
            'auth_subtitle' => "Nederlandse facturatie voor MKB en ZZP'ers.",
            'login_seo_title' => 'Inloggen bij EasyInvoice — online factuurprogramma',
            'login_seo_description' => 'Log in bij EasyInvoice en ga verder met je administratie: facturen, offertes, BTW en incasso — Nederlandse facturatie zonder gedoe.',
            'register_seo_title' => 'Probeer EasyInvoice 14 dagen gratis — account aanmaken',
            'register_seo_description' => 'Maak in één minuut je EasyInvoice-account aan en probeer alle functies 14 dagen gratis — geen creditcard nodig, daarna vanaf € 12,10 per maand.',

            // Merkregistratie en merkbewaking (alleen voor het geregistreerde merk).
            'registered' => true,
            'trademark' => [
                'number' => '1485323',
                'url' => 'https://www.boip.int/nl/merkenregister?app=%2Fitem%2Fbx1485323&query=easyinvoice',
            ],
            'brand_watch' => true,
        ],

        'lopra' => [
            'name' => 'Lopra',
            'version_prefix' => 'Lopra',
            'tagline' => 'Je hele administratie op één plek',
            'positioning' => 'Administratie en online zichtbaarheid voor startende ondernemers',
            'domain' => 'lopra.nl',
            'email' => 'hallo@lopra.nl',
            'contact_inbox' => null,

            'color' => '#1C4E7A',   // inktblauw
            'color_dark' => '#132F49', // diepblauw
            'accent' => '#C8752A',  // koper — alleen voor de belangrijkste actie
            'background' => '#F4F2EE', // zand
            'fonts_url' => 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap',
            'theme_css' => '/brand/lopra/theme.css',

            'mark' => '/brand/lopra/lopra-icon.svg',
            'email_mark' => '/brand/lopra/lopra-icon-180.png',
            'icon' => '/brand/lopra/lopra-icon-512.png',
            'favicon_32' => '/brand/lopra/lopra-icon-32.png',
            'favicon_512' => '/brand/lopra/lopra-icon-512.png',
            'favicon_svg' => '/brand/lopra/favicon.svg',
            'favicon_ico' => '/brand/lopra/favicon.ico',
            'apple_touch' => '/brand/lopra/lopra-icon-180.png',
            'og_image' => '/brand/lopra/og-lopra.png',
            'wordmark' => '/brand/lopra/lopra-logo.svg',
            'wordmark_dark' => '/brand/lopra/lopra-logo-dark.svg',

            'seo_title' => 'Lopra — je hele administratie op één plek, vanaf € 12,10 per maand',
            'seo_description' => 'Lopra is het startpakket voor beginnende ondernemers: factureren, offertes, btw-overzicht, huisstijl, digitaal visitekaartje en een eigen website — in één abonnement vanaf € 12,10 per maand (incl. btw).',
            'og_description' => 'Factureren, offertes, btw, huisstijl, visitekaartje en je eigen website — alles wat een starter nodig heeft, op één plek.',
            'app_description' => 'Online administratie voor startende ondernemers: facturen, offertes met digitale ondertekening, btw-overzicht, urenregistratie, huisstijl met AI, digitaal visitekaartje en een eigen website.',
            'pwa_description' => 'Facturen, offertes, huisstijl en website — je hele administratie op één plek.',
            'footer_description' => 'Je hele administratie op één plek: factureren, offertes, btw, huisstijl en je eigen website. Voor wie net begint. Gemaakt in Bussum.',
            'auth_title' => 'Je hele administratie op één plek',
            'auth_subtitle' => 'Factureren, offertes, huisstijl en je eigen website — voor starters.',
            'login_seo_title' => 'Inloggen bij Lopra — je administratie op één plek',
            'login_seo_description' => 'Log in bij Lopra en ga verder met je administratie: facturen, offertes, btw, huisstijl en je website.',
            'register_seo_title' => 'Probeer Lopra 14 dagen gratis — account aanmaken',
            'register_seo_description' => 'Maak in één minuut je Lopra-account aan en probeer alles 14 dagen gratis — geen creditcard nodig, daarna vanaf € 12,10 per maand.',

            'registered' => false,
            'trademark' => null,
            'brand_watch' => false,
        ],
    ],
];
