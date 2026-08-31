<?php

/*
 * Navigatie en voettekst van de marketingsite, per merk. Links verwijzen naar
 * routenamen (met optioneel een #fragment); labels mogen {brand} bevatten.
 * Routes die in deze omgeving niet bestaan, worden overgeslagen.
 */
$nl = [
    'login' => 'Inloggen',
    'cta' => 'Start gratis →',
    'nav' => [
        ['route' => 'home', 'fragment' => '#waarom', 'label' => 'Waarom {brand}'],
        ['route' => 'home', 'fragment' => '#functies', 'label' => 'Functies'],
        ['route' => 'ai', 'label' => 'Factureren met AI'],
        ['route' => 'home', 'fragment' => '#prijzen', 'label' => 'Prijzen'],
        ['route' => 'gratis-factuur', 'label' => 'Gratis factuur'],
        ['route' => 'faq', 'label' => 'Veelgestelde vragen'],
    ],
    'footer' => [
        ['title' => 'Product', 'links' => [
            ['route' => 'home', 'fragment' => '#waarom', 'label' => 'Waarom {brand}'],
            ['route' => 'home', 'fragment' => '#functies', 'label' => 'Functies'],
            ['route' => 'ai', 'label' => 'Factureren met AI'],
            ['route' => 'home', 'fragment' => '#prijzen', 'label' => 'Prijzen'],
            ['route' => 'changelog', 'label' => 'Wat is nieuw'],
            ['route' => 'roadmap', 'label' => 'Roadmap'],
            ['route' => 'confusion', 'label' => 'Zocht u een ander {brand}?', 'brand_watch' => true],
        ]],
        ['title' => 'Gratis tools', 'links' => [
            ['route' => 'gratis-factuur', 'label' => 'Gratis factuur maken'],
            ['route' => 'btw-calculator', 'label' => 'Btw-calculator'],
            ['route' => 'uurtarief-calculator', 'label' => 'Uurtarief-calculator'],
            ['route' => 'kennisbank', 'label' => 'Kennisbank'],
            ['route' => 'overstappen.van', 'params' => ['wefact'], 'label' => 'Overstappen van WeFact'],
            ['route' => 'overstappen.van', 'params' => ['moneybird'], 'label' => 'Overstappen van Moneybird'],
            ['route' => 'overstappen.van', 'params' => ['e-boekhouden'], 'label' => 'Overstappen van e-Boekhouden'],
        ]],
        ['title' => 'Bedrijf', 'links' => [
            ['route' => 'over', 'label' => 'Over ons'],
            ['route' => 'boekhouders', 'label' => 'Voor boekhouders'],
            ['route' => 'contact', 'label' => 'Contact'],
        ]],
        ['title' => 'Hulp', 'links' => [
            ['route' => 'helpcentrum', 'label' => 'Helpcentrum'],
            ['route' => 'kennisbank', 'label' => 'Kennisbank'],
            ['route' => 'faq', 'label' => 'Veelgestelde vragen'],
            ['mailto' => true, 'label' => 'E-mail support'],
            ['route' => 'status', 'label' => 'Status'],
        ]],
    ],
    'legal' => [
        ['route' => 'voorwaarden', 'label' => 'Algemene voorwaarden'],
        ['route' => 'privacy', 'label' => 'Privacybeleid'],
        ['route' => 'verwerkersovereenkomst', 'label' => 'Verwerkersovereenkomst'],
        ['route' => 'cookies', 'label' => 'Cookies'],
    ],
    'company_line' => 'Creditline B.V. · Torenlaan 5B · 1402 AT Bussum · Nederland',
    'copyright' => '© :year Creditline B.V. · KvK 59683198 · BTW NL853603108B01',
    'trademark_line' => ':brand&reg; is een geregistreerd Benelux-merk van Creditline B.V. &mdash;',
    'trademark_link' => 'BOIP-inschrijving nr.&nbsp;:number',
    'offers' => [
        ['name' => 'Basis', 'price' => '12.10', 'priceCurrency' => 'EUR', 'description' => 'Het volledige facturatiepakket voor € 12,10 per maand (incl. 21% btw), maandelijks opzegbaar.'],
        ['name' => 'Slim', 'price' => '21.18', 'priceCurrency' => 'EUR', 'description' => 'Alles uit Basis plus de AI-functies voor € 21,18 per maand (incl. 21% btw), maandelijks opzegbaar.'],
    ],
];

$pl = [
    'login' => 'Zaloguj się',
    'cta' => 'Zacznij za darmo →',
    'nav' => [
        ['route' => 'home', 'fragment' => '#dlaczego', 'label' => 'Dlaczego Lopra'],
        ['route' => 'home', 'fragment' => '#funkcje', 'label' => 'Funkcje'],
        ['route' => 'home', 'fragment' => '#windykacja', 'label' => 'Windykacja'],
        ['route' => 'home', 'fragment' => '#cennik', 'label' => 'Cennik'],
        ['route' => 'pl.kalkulator', 'label' => 'Kalkulator odsetek'],
        ['route' => 'pl.faq', 'label' => 'Pytania'],
    ],
    'footer' => [
        ['title' => 'Produkt', 'links' => [
            ['route' => 'home', 'fragment' => '#dlaczego', 'label' => 'Dlaczego Lopra'],
            ['route' => 'home', 'fragment' => '#funkcje', 'label' => 'Funkcje'],
            ['route' => 'home', 'fragment' => '#windykacja', 'label' => 'Windykacja i sprzedaż faktur'],
            ['route' => 'home', 'fragment' => '#cennik', 'label' => 'Cennik'],
            ['route' => 'demo', 'label' => 'Demo'],
        ]],
        ['title' => 'Narzędzia', 'links' => [
            ['route' => 'pl.kalkulator', 'label' => 'Kalkulator odsetek'],
            ['route' => 'pl.skup-wyrokow', 'label' => 'Skup starych wyroków'],
            ['route' => 'pl.przenies', 'params' => ['fakturownia'], 'label' => 'Przenieś się z Fakturowni'],
            ['route' => 'pl.przenies', 'params' => ['ifirma'], 'label' => 'Przenieś się z iFirmy'],
            ['route' => 'pl.przenies', 'params' => ['wfirma'], 'label' => 'Przenieś się z wFirmy'],
            ['route' => 'pl.przenies', 'params' => ['infakt'], 'label' => 'Przenieś się z inFaktu'],
        ]],
        ['title' => 'Firma', 'links' => [
            ['route' => 'pl.o-nas', 'label' => 'O nas'],
            ['route' => 'pl.kontakt', 'label' => 'Kontakt'],
            ['url' => 'https://sprzedamfakture.pl', 'label' => 'sprzedamfakture.pl'],
        ]],
        ['title' => 'Pomoc', 'links' => [
            ['route' => 'pl.faq', 'label' => 'Najczęstsze pytania'],
            ['mailto' => true, 'label' => 'Wsparcie e-mail'],
            ['route' => 'status', 'label' => 'Status systemu'],
        ]],
    ],
    'legal' => [
        ['route' => 'pl.regulamin', 'label' => 'Regulamin'],
        ['route' => 'pl.prywatnosc', 'label' => 'Polityka prywatności'],
    ],
    'company_line' => 'Creditline B.V. · Torenlaan 5B · 1402 AT Bussum · Holandia · we współpracy ze sprzedamfakture.pl',
    'copyright' => '© :year Creditline B.V. · KvK 59683198 · VAT NL853603108B01',
    'trademark_line' => '',
    'trademark_link' => '',
    'offers' => [
        ['name' => 'Podstawowy', 'price' => '60.27', 'priceCurrency' => 'PLN', 'description' => 'Pełne fakturowanie, wizytówka i strona www za 49 zł netto (60,27 zł brutto) miesięcznie, bez zobowiązań.'],
        ['name' => 'Smart', 'price' => '97.17', 'priceCurrency' => 'PLN', 'description' => 'Wszystko z Podstawowego plus AI i priorytetowa wycena wykupu faktur za 79 zł netto (97,17 zł brutto) miesięcznie.'],
    ],
];

// Lopra Polska in het Engels (PL/EN-schakelaar): dezelfde routes en ankers, Engelse labels.
$plEn = [
    'login' => 'Log in',
    'cta' => 'Start for free →',
    'nav' => [
        ['route' => 'home', 'fragment' => '#dlaczego', 'label' => 'Why Lopra'],
        ['route' => 'home', 'fragment' => '#funkcje', 'label' => 'Features'],
        ['route' => 'home', 'fragment' => '#windykacja', 'label' => 'Debt collection'],
        ['route' => 'home', 'fragment' => '#cennik', 'label' => 'Pricing'],
        ['route' => 'pl.kalkulator', 'label' => 'Interest calculator'],
        ['route' => 'pl.faq', 'label' => 'FAQ'],
    ],
    'footer' => [
        ['title' => 'Product', 'links' => [
            ['route' => 'home', 'fragment' => '#dlaczego', 'label' => 'Why Lopra'],
            ['route' => 'home', 'fragment' => '#funkcje', 'label' => 'Features'],
            ['route' => 'home', 'fragment' => '#windykacja', 'label' => 'Collection toolkit & invoice sale'],
            ['route' => 'home', 'fragment' => '#cennik', 'label' => 'Pricing'],
            ['route' => 'demo', 'label' => 'Demo'],
        ]],
        ['title' => 'Tools', 'links' => [
            ['route' => 'pl.kalkulator', 'label' => 'Interest calculator'],
            ['route' => 'pl.skup-wyrokow', 'label' => 'We buy old judgments'],
            ['route' => 'pl.przenies', 'params' => ['fakturownia'], 'label' => 'Switch from Fakturownia'],
            ['route' => 'pl.przenies', 'params' => ['ifirma'], 'label' => 'Switch from iFirma'],
            ['route' => 'pl.przenies', 'params' => ['wfirma'], 'label' => 'Switch from wFirma'],
            ['route' => 'pl.przenies', 'params' => ['infakt'], 'label' => 'Switch from inFakt'],
        ]],
        ['title' => 'Company', 'links' => [
            ['route' => 'pl.o-nas', 'label' => 'About us'],
            ['route' => 'pl.kontakt', 'label' => 'Contact'],
            ['url' => 'https://sprzedamfakture.pl', 'label' => 'sprzedamfakture.pl'],
        ]],
        ['title' => 'Support', 'links' => [
            ['route' => 'pl.faq', 'label' => 'Frequently asked questions'],
            ['mailto' => true, 'label' => 'E-mail support'],
            ['route' => 'status', 'label' => 'System status'],
        ]],
    ],
    'legal' => [
        ['route' => 'pl.regulamin', 'label' => 'Terms of service'],
        ['route' => 'pl.prywatnosc', 'label' => 'Privacy policy'],
    ],
    'company_line' => 'Creditline B.V. · Torenlaan 5B · 1402 AT Bussum · the Netherlands · in partnership with sprzedamfakture.pl',
    'copyright' => '© :year Creditline B.V. · KvK 59683198 · VAT NL853603108B01',
    'trademark_line' => '',
    'trademark_link' => '',
    'offers' => [
        ['name' => 'Podstawowy', 'price' => '60.27', 'priceCurrency' => 'PLN', 'description' => 'Full invoicing, business card and website for 49 zł net (60.27 zł gross) a month, no commitment.'],
        ['name' => 'Smart', 'price' => '97.17', 'priceCurrency' => 'PLN', 'description' => 'Everything in Podstawowy plus AI and priority invoice-purchase quotes for 79 zł net (97.17 zł gross) a month.'],
    ],
];

return [
    'easyinvoice' => $nl,
    'lopra' => $nl,
    'lopra_pl' => $pl,
    'lopra_pl_en' => $plEn,
];
