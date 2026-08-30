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
            'market' => 'nl',            // zie config/markets.php
            'home_view' => 'landing',
            'version_prefix' => 'Easy',
            'tagline' => 'Facturatie zonder gedoe',
            'assistant' => 'EASY',        // naam van de AI-assistent in de app
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
            'sidebar_mark' => null,      // beeldmerk voor een donkere zijbalk; null = 'mark'
            'dark_sidebar' => false,
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
            'market' => 'nl',
            'home_view' => 'lopra.landing',
            'version_prefix' => 'Lopra',
            'tagline' => 'Je hele administratie op één plek',
            'assistant' => 'Lo',
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
            'sidebar_mark' => '/brand/lopra/lopra-mark-white.svg',
            'dark_sidebar' => true,
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

        // Lopra Polska: hetzelfde merk, markt 'pl' (taal, PLN, btw 23/8/5/0, NIP,
        // BLIK/Przelewy24, KSeF) en sprzedamfakture.pl als factuurkoper (wykup wierzytelności; geen incasso).
        'lopra_pl' => [
            'name' => 'Lopra',
            'market' => 'pl',
            'home_view' => 'lopra-pl.landing',
            'version_prefix' => 'Lopra',
            'tagline' => 'Cała Twoja firma w jednym miejscu',
            'assistant' => 'Lo',
            'positioning' => 'Fakturowanie, marka i sprzedaż faktur dla firm w Polsce',
            'domain' => 'lopra.pl',
            'email' => 'kontakt@lopra.pl',
            'contact_inbox' => null,

            'color' => '#1C4E7A',
            'color_dark' => '#132F49',
            'accent' => '#C8752A',
            'background' => '#F4F2EE',
            'fonts_url' => 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap&subset=latin-ext',
            'theme_css' => '/brand/lopra/theme.css',

            'mark' => '/brand/lopra/lopra-icon.svg',
            'email_mark' => '/brand/lopra/lopra-icon-180.png',
            'sidebar_mark' => '/brand/lopra/lopra-mark-white.svg',
            'dark_sidebar' => true,
            'icon' => '/brand/lopra/lopra-icon-512.png',
            'favicon_32' => '/brand/lopra/lopra-icon-32.png',
            'favicon_512' => '/brand/lopra/lopra-icon-512.png',
            'favicon_svg' => '/brand/lopra/favicon.svg',
            'favicon_ico' => '/brand/lopra/favicon.ico',
            'apple_touch' => '/brand/lopra/lopra-icon-180.png',
            'og_image' => '/brand/lopra/og-lopra-pl.png',
            'wordmark' => '/brand/lopra/lopra-logo.svg',
            'wordmark_dark' => '/brand/lopra/lopra-logo-dark.svg',

            'seo_title' => 'Lopra — faktury, marka i sprzedaż faktur w jednym miejscu, od 49 zł miesięcznie',
            'seo_description' => 'Lopra to kompletne narzędzie dla polskich firm: faktury i oferty (gotowe do KSeF), rozliczenie VAT, identyfikacja wizualna z AI, wizytówka i strona www, import z Fakturowni, iFirmy, wFirmy i inFaktu oraz sprzedaż faktur do sprzedamfakture.pl. 14 dni za darmo.',
            'og_description' => 'Faktury, oferty, VAT, marka, strona www i sprzedaż faktur — cała Twoja firma w jednym miejscu.',
            'app_description' => 'Program do fakturowania i prowadzenia firmy w Polsce: faktury VAT gotowe do KSeF, oferty z podpisem online, rozliczenie VAT, identyfikacja wizualna z AI, wizytówka cyfrowa, strona www oraz sprzedaż nieopłaconych faktur do sprzedamfakture.pl.',
            'pwa_description' => 'Faktury, oferty, marka i sprzedaż faktur — cała Twoja firma w jednym miejscu.',
            'footer_description' => 'Cała Twoja firma w jednym miejscu: faktury, oferty, VAT, marka, strona www i sprzedaż faktur do sprzedamfakture.pl. Od startu po dojrzały biznes.',
            'auth_title' => 'Cała Twoja firma w jednym miejscu',
            'auth_subtitle' => 'Faktury, oferty, identyfikacja wizualna, strona www i sprzedaż faktur — od pierwszego dnia działalności.',
            'login_seo_title' => 'Logowanie do Lopra — Twoja firma w jednym miejscu',
            'login_seo_description' => 'Zaloguj się do Lopra i wróć do swoich faktur, ofert, rozliczeń VAT, marki i strony www.',
            'register_seo_title' => 'Wypróbuj Lopra 14 dni za darmo — załóż konto',
            'register_seo_description' => 'Załóż konto w Lopra w minutę i przetestuj wszystko 14 dni za darmo — bez karty, potem od 49 zł netto miesięcznie.',

            // Engelse interfacetaal (PL/EN-schakelaar): merkteksten per taal. Wat hier
            // ontbreekt valt terug op het Pools. Gelezen door Brand::get().
            'i18n' => [
                'en' => [
                    'tagline' => 'Your whole business in one place',
                    'positioning' => 'Invoicing, brand and invoice sale for businesses in Poland',
                    'seo_title' => 'Lopra — invoices, brand and invoice sale in one place, from 49 zł a month',
                    'seo_description' => 'Lopra is the complete toolkit for businesses in Poland: KSeF-ready invoices and quotes, VAT return (JPK_V7), AI brand identity, digital business card and website, import from Fakturownia, iFirma, wFirma and inFakt, and invoice sale to sprzedamfakture.pl. In Polish and English. 14 days free.',
                    'og_description' => 'Invoices, quotes, VAT, brand, website and invoice sale — your whole business in one place.',
                    'app_description' => 'Invoicing and business software for Poland: KSeF-ready VAT invoices, quotes with online signature, VAT return, AI brand identity, digital business card, website and invoice sale to sprzedamfakture.pl.',
                    'pwa_description' => 'Invoices, quotes, brand and invoice sale — your whole business in one place.',
                    'footer_description' => 'Your whole business in one place: invoices, quotes, VAT, brand, website and invoice sale to sprzedamfakture.pl. From day one to a mature business.',
                    'auth_title' => 'Your whole business in one place',
                    'auth_subtitle' => 'Invoices, quotes, brand identity, website and invoice sale — from the first day of your business.',
                    'login_seo_title' => 'Log in to Lopra — your business in one place',
                    'login_seo_description' => 'Log in to Lopra and get back to your invoices, quotes, VAT returns, brand and website.',
                    'register_seo_title' => 'Try Lopra free for 14 days — create an account',
                    'register_seo_description' => 'Create your Lopra account in a minute and test everything free for 14 days — no card needed, then from 49 zł net a month.',
                ],
            ],
            'registered' => false,
            'trademark' => null,
            'brand_watch' => false,
        ],
    ],
];
