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
            /*
             * Het beeldmerk van dit merk zoals Stripe het kent.
             *
             * Een Stripe-account heeft één huisstijl, en dit account bedient
             * alle merken. Wat je in het dashboard instelt zou dus ook een
             * EasyBookkeeper-klant te zien krijgen. Een Checkout-sessie kan het
             * per keer overschrijven (branding_settings), en dat gebeurt met
             * deze bestand-id's — zie StripeService::createCheckoutSession.
             *
             * De id's horen bij bestanden die met purpose business_icon en
             * business_logo in Stripe staan; scripts/stripe-merkbestanden.sh
             * zet ze erin en is opnieuw te draaien zonder dubbele kopieën.
             */
            'stripe_icon' => 'file_1ULTP8Bp1NB4xbZwYNh7VkQi',
            'stripe_logo' => 'file_1ULTP8Bp1NB4xbZwklBtGfBF',
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
            // Zie de toelichting bij EasyInvoice hierboven.
            'stripe_icon' => 'file_1ULTPABp1NB4xbZwKwUaGHTj',
            'stripe_logo' => 'file_1ULTPBBp1NB4xbZwBSEVvrKj',
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
            // Zie de toelichting bij EasyInvoice hierboven.
            'stripe_icon' => 'file_1ULTPABp1NB4xbZwKwUaGHTj',
            'stripe_logo' => 'file_1ULTPBBp1NB4xbZwBSEVvrKj',
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

        /*
         * Fachora — platform voor gecontroleerde vakmannen in Polen.
         *
         * Geen factuurprogramma met een ander jasje, maar een marktplaats op
         * dezelfde motor: de klant beschrijft een klus, Fachora biedt haar aan
         * bij gecontroleerde bedrijven (Fachora Verified), de prijs wordt
         * vooraf vastgelegd en via Fachora Protect betaald. Elk aangesloten
         * bedrijf krijgt daarbij een eigen administratie in de markt pl (de
         * Lopra-motor: KSeF-facturen, offertes, planning).
         *
         * Zet APP_BRAND=fachora op de Railway-service die op fachora.pl draait.
         * Routes, teksten en regels: routes/web.php (blok FACHORA),
         * config/fachora.php en App\Services\Fachora.
         */
        'fachora' => [
            'name' => 'Fachora',
            'market' => 'pl',
            'home_view' => 'fachora.landing',
            'version_prefix' => 'Fachora',
            'tagline' => 'Sprawdzone firmy. Bezpieczne usługi.',
            'assistant' => 'Fa',
            'positioning' => 'Zaufani fachowcy w całej Polsce: jasna cena, pewny termin, gwarancja',
            'domain' => 'fachora.pl',
            'email' => 'kontakt@fachora.pl',
            'contact_inbox' => null,

            // Nachtblauw voor navigatie en koppen, oranje alleen voor de belangrijkste actie.
            'color' => '#0B2545',
            'color_dark' => '#071A33',
            'accent' => '#F4781F',
            'background' => '#F6F8FB',
            'fonts_url' => null,
            'theme_css' => '/brand/fachora/theme.css',

            'mark' => '/brand/fachora/fachora-mark.svg',
            'email_mark' => '/brand/fachora/fachora-icon-180.png',
            'sidebar_mark' => '/brand/fachora/fachora-mark-white.svg',
            'dark_sidebar' => true,
            'icon' => '/brand/fachora/fachora-icon-512.png',
            'favicon_32' => '/brand/fachora/fachora-icon-32.png',
            'favicon_512' => '/brand/fachora/fachora-icon-512.png',
            'favicon_svg' => '/brand/fachora/favicon.svg',
            'favicon_ico' => null,
            'apple_touch' => '/brand/fachora/fachora-icon-180.png',
            'og_image' => '/brand/fachora/og-fachora.png',
            // Nog geen eigen merkbestanden in Stripe: de Checkout toont dan de standaardhuisstijl van het account.
            'stripe_icon' => null,
            'stripe_logo' => null,
            'wordmark' => '/brand/fachora/fachora-logo.svg',
            'wordmark_dark' => '/brand/fachora/fachora-logo-dark.svg',

            'seo_title' => 'Fachora — sprawdzeni fachowcy: jasna cena, pewny termin, gwarancja',
            'seo_description' => 'Fachora łączy klientów ze zweryfikowanymi firmami w Polsce: hydraulik, elektryk, ogrzewanie, klimatyzacja, ślusarz, dach, remonty, AGD, mechanik. Cena ustalona z góry, bezpieczna płatność Fachora Protect, faktura i gwarancja. Obsługa po polsku, angielsku, niemiecku, niderlandzku i ukraińsku.',
            'og_description' => 'Sprawdzone firmy. Bezpieczne usługi. Jasna cena, pewny termin, gwarancja.',
            'app_description' => 'Platforma zweryfikowanych fachowców w Polsce: zlecenie online, oferty od firm Fachora Verified, bezpieczna płatność Fachora Protect, faktura i 12 miesięcy gwarancji.',
            'pwa_description' => 'Sprawdzone firmy. Bezpieczne usługi.',
            'footer_description' => 'Fachora: zweryfikowani fachowcy, jasna cena ustalona z góry, bezpieczna płatność i gwarancja. Dla klientów bezpłatnie.',
            'auth_title' => 'Panel firmy Fachora',
            'auth_subtitle' => 'Zlecenia, profil, weryfikacja i faktury — w jednym miejscu.',
            'login_seo_title' => 'Logowanie do Fachora — panel firmy',
            'login_seo_description' => 'Zaloguj się do panelu firmy Fachora: nowe zlecenia, profil, weryfikacja i faktury.',
            'register_seo_title' => 'Zostań firmą Fachora Verified',
            'register_seo_description' => 'Przejmij profil swojej firmy, przejdź weryfikację i odbieraj zlecenia od klientów w Twojej okolicy. 0 zł abonamentu, prowizja tylko od wykonanych zleceń.',

            'i18n' => [
                'en' => [
                    'tagline' => 'Verified companies. Safe services.',
                    'positioning' => 'Trusted tradespeople across Poland: clear price, reliable appointment, guarantee',
                    'seo_title' => 'Fachora — verified tradespeople in Poland: clear price, reliable appointment, guarantee',
                    'seo_description' => 'Fachora connects you with verified companies in Poland: plumber, electrician, heating, air conditioning, locksmith, roofing, renovation, appliances, car mechanic. Price agreed up front, secure Fachora Protect payment, invoice and guarantee. Service in English, Polish, German, Dutch and Ukrainian.',
                    'og_description' => 'Verified companies. Safe services. Clear price, reliable appointment, guarantee.',
                    'app_description' => 'Platform of verified tradespeople in Poland: request online, offers from Fachora Verified companies, secure Fachora Protect payment, invoice and 12-month guarantee.',
                    'pwa_description' => 'Verified companies. Safe services.',
                    'footer_description' => 'Fachora: verified tradespeople, a clear price agreed up front, secure payment and a guarantee. Free for customers.',
                    'auth_title' => 'Fachora company panel',
                    'auth_subtitle' => 'Jobs, profile, verification and invoices in one place.',
                    'login_seo_title' => 'Log in to Fachora — company panel',
                    'login_seo_description' => 'Log in to the Fachora company panel: new jobs, profile, verification and invoices.',
                    'register_seo_title' => 'Become a Fachora Verified company',
                    'register_seo_description' => 'Claim your company profile, pass verification and receive jobs from customers near you. No subscription, commission only on completed jobs.',
                ],
            ],
            // De aanbieder van Fachora is Budomatch (Pools, CEIDG), niet Creditline B.V.
            'owner_name' => 'Budomatch Daniël de Graaf',
            'owner_city' => 'Białka',
            'owner_country' => 'PL',

            /*
             * Het portaal voor een Pools bedrijf: alleen wat een fachowiec gebruikt.
             * 'menu' zijn de routes die in de zijbalk staan (de rest verdwijnt),
             * 'hidden' de routenaam-voorvoegsels die onder dit merk 404 geven
             * (App\Http\Middleware\PortalScope), 'home' waar /dashboard heen gaat en
             * 'free' dat er geen proefperiode of abonnement is (de administratie hoort
             * bij het profiel op Fachora).
             */
            'portal' => [
                'home' => 'fachora.dashboard',
                'free' => true,
                'menu' => [
                    'fachora.dashboard', 'fachora.profile', 'fachora.verification',
                    'quotes.index', 'invoices.index', 'customers.index', 'products.index',
                    'vat.index',
                    'settings.company', 'settings.brand', 'settings.numbering', 'settings.emails', 'settings.team', 'settings.security',
                    'marketing.inzichten', 'owner.companies.index', 'fachora.admin.providers', 'fachora.admin.jobs',
                ],
                'hidden' => [
                    'hours.', 'trips.', 'recurring.', 'projects.', 'direct-debit.', 'incasso.', 'windykacja.', 'wykup.',
                    'purchases.', 'tenders.', 'bank.', 'ledger.', 'stats.', 'yearreport.', 'cashflow.', 'aging.', 'mollie.',
                    'export.', 'import.', 'billing.', 'brand.', 'demands.', 'peppol.',
                    'settings.brands', 'settings.card', 'settings.site', 'settings.reminders', 'settings.sms', 'settings.integrations', 'settings.activity',
                    'customers.ccbr', 'customers.peppol', 'customers.score', 'invoices.peppol',
                ],
            ],
            'registered' => false,
            'trademark' => null,
            'brand_watch' => false,
        ],

        /*
         * EasyBookkeeper — boekhouden, niet alleen factureren.
         *
         * Draait op dezelfde motor als EasyInvoice; het verschil is waar het
         * verhaal begint. EasyInvoice gaat over de factuur die de deur uit
         * gaat, EasyBookkeeper over de administratie die eronder ligt: een
         * echt grootboek, een sluitende balans en een auditfile die de
         * accountant zonder morren inleest.
         *
         * Zet APP_BRAND=easybookkeeper op de Railway-service die op
         * easybookkeeper.nl draait. Verder deelt hij alles: dezelfde
         * facturatie, dezelfde bankkoppeling, dezelfde btw-aangifte.
         */
        'easybookkeeper' => [
            'name' => 'EasyBookkeeper',
            'market' => 'nl',
            'home_view' => 'easybookkeeper.landing',
            'version_prefix' => 'EasyBookkeeper',
            'tagline' => 'Boekhouden dat klopt',
            'assistant' => 'Boek',
            'positioning' => 'Online boekhouden voor zzp en mkb, met een echt grootboek',
            'domain' => 'easybookkeeper.nl',
            'email' => 'hallo@easybookkeeper.nl',
            'contact_inbox' => null,

            // Groen: de kleur die mensen met boekhouden verbinden, en ver
            // genoeg van het rood van EasyInvoice en het blauw van Lopra.
            'color' => '#146B4F',
            'color_dark' => '#0E4D39',
            // Koper als tweede kleur, maar niet op de knoppen: op dit groen
            // wordt het modderig. De primaire actie is groen (theme.css).
            'accent' => '#C87A2C',
            'background' => '#F7F6F3',
            'fonts_url' => null,
            'theme_css' => '/brand/easybookkeeper/theme.css',

            'mark' => '/brand/easybookkeeper/eb-icon.svg',
            'email_mark' => '/brand/easybookkeeper/eb-icon-180.png',
            'sidebar_mark' => '/brand/easybookkeeper/eb-mark-white.svg',
            'dark_sidebar' => true,
            'icon' => '/brand/easybookkeeper/eb-icon-512.png',
            'favicon_32' => '/brand/easybookkeeper/eb-icon-32.png',
            'favicon_512' => '/brand/easybookkeeper/eb-icon-512.png',
            'favicon_svg' => '/brand/easybookkeeper/favicon.svg',
            'favicon_ico' => '/brand/easybookkeeper/favicon.ico',
            'apple_touch' => '/brand/easybookkeeper/eb-icon-180.png',
            'og_image' => '/brand/easybookkeeper/og-easybookkeeper.png',
            // Zie de toelichting bij EasyInvoice hierboven.
            'stripe_icon' => 'file_1ULTP9Bp1NB4xbZwX0WeRaaA',
            'stripe_logo' => null,
            'wordmark' => '/brand/easybookkeeper/eb-logo.svg',
            'wordmark_dark' => '/brand/easybookkeeper/eb-logo-dark.svg',

            'seo_title' => 'EasyBookkeeper — online boekhouden met een echt grootboek',
            'seo_description' => 'Boekhouden voor zzp en mkb: facturen, bank, btw-aangifte en een volledig '
                . 'grootboek met balans, proefbalans en auditfile. Uw accountant leest het zo in. '
                . '14 dagen gratis proberen.',
            'og_description' => 'Facturen, bank, btw en een echt grootboek — met een auditfile die uw '
                . 'accountant zonder morren inleest.',
            'app_description' => 'Online boekhouden voor zzp en mkb: facturen en offertes, bankkoppeling, '
                . 'btw-aangifte, en een volledig grootboek met proefbalans, balans, grootboekkaarten '
                . 'en de auditfile voor de accountant.',
            'pwa_description' => 'Facturen, bank, btw en een echt grootboek — boekhouden dat klopt.',
            'footer_description' => 'Online boekhouden met een echt grootboek: facturen, bank, btw-aangifte, '
                . 'balans en auditfile. Gemaakt in Nederland.',
            'auth_title' => 'Boekhouden dat klopt',
            'auth_subtitle' => 'Facturen, bank, btw en een volledig grootboek — op één plek.',
            'login_seo_title' => 'Inloggen bij EasyBookkeeper',
            'login_seo_description' => 'Log in bij EasyBookkeeper en ga verder met uw boekhouding: '
                . 'facturen, bank, btw en grootboek.',
            'register_seo_title' => 'Probeer EasyBookkeeper 14 dagen gratis — account aanmaken',
            'register_seo_description' => 'Maak in één minuut uw account aan en probeer alles 14 dagen '
                . 'gratis. Geen creditcard nodig.',

            'registered' => false,
            'trademark' => null,
            'brand_watch' => false,
        ],
    ],
];
