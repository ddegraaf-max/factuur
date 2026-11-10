<?php

/*
 * De merken van het VvE/NextPro-pakket die via EasyInvoice factureren.
 *
 * ── Waarom een lijst en niet één VvEMaat ──────────────────────────────────
 *
 * Dezelfde software draait onder zes namen, elk als eigen omgeving met een
 * eigen adres en een eigen koppelvlaksleutel (KOPPELVLAK_SLEUTEL aan de kant
 * van het merk). Tot 1.110 kende EasyInvoice alleen VvEMaat en alleen "betaald
 * tot"; bijgekochte bundels en brieven per post werden daardoor nergens
 * gefactureerd.
 *
 * ── Wie factureert ────────────────────────────────────────────────────────
 *
 * Alle facturen van de merken (abonnement en extra verbruik) lopen via één
 * EasyInvoice-administratie: die van NapoleonSoftware B.V. Het id daarvan
 * staat in MERKEN_TEAM_ID; per merk kan MERK_<CODE>_TEAM_ID afwijken. Zonder
 * team wordt een merk overgeslagen, met een melding op het eigenaarsscherm.
 *
 * Per merk (CODE in hoofdletters, bijv. NEXTPRO):
 *   MERK_<CODE>_KOPPELVLAK_SLEUTEL  dezelfde waarde als KOPPELVLAK_SLEUTEL bij het merk
 *   MERK_<CODE>_URL                 afwijkend adres (standaard het domein hieronder)
 *   MERK_<CODE>_TEAM_ID             afwijkende administratie (optioneel)
 *   MERK_<CODE>_HANDELSNAAM_ID      handelsnaam (brand profile) in die administratie (optioneel)
 *
 * VvEMaat valt terug op de oude namen VVEMAAT_URL en VVEMAAT_KOPPELVLAK_SLEUTEL,
 * zodat een bestaande omgeving blijft werken zoals hij deed.
 */

$merken = [
    'nextpro' => ['naam' => 'NextPro', 'url' => 'https://nextpro.nl'],
    'napoleon' => ['naam' => 'Napoleon', 'url' => 'https://napoleonvvesoftware.nl'],
    'vvemaat' => ['naam' => 'VvEMaat', 'url' => 'https://vvemaat.nl'],
    'vveklik' => ['naam' => 'VvEklik', 'url' => 'https://vveklik.nl'],
    'vvenova' => ['naam' => 'VvEnova', 'url' => 'https://vvenova.nl'],
    'vveslim' => ['naam' => 'VvESlim', 'url' => 'https://vveslim.nl'],
];

$uit = [];
foreach ($merken as $code => $m) {
    $env = 'MERK_'.strtoupper($code).'_';
    $url = (string) env($env.'URL', '');
    $sleutel = (string) env($env.'KOPPELVLAK_SLEUTEL', '');
    if ($code === 'vvemaat') {
        $url = $url !== '' ? $url : (string) env('VVEMAAT_URL', '');
        $sleutel = $sleutel !== '' ? $sleutel : (string) env('VVEMAAT_KOPPELVLAK_SLEUTEL', '');
    }
    $team = env($env.'TEAM_ID');
    $handelsnaam = env($env.'HANDELSNAAM_ID');

    $uit[$code] = [
        'code' => $code,
        'naam' => $m['naam'],
        'url' => rtrim($url !== '' ? $url : $m['url'], '/'),
        'sleutel' => $sleutel,
        'team_id' => filled($team) ? (int) $team : null,
        'handelsnaam_id' => filled($handelsnaam) ? (int) $handelsnaam : null,
    ];
}

return [
    'team_id' => filled(env('MERKEN_TEAM_ID')) ? (int) env('MERKEN_TEAM_ID') : null,

    // Het ophalen loopt over alle klanten van een merk; ruimer dan de 5 s van de betaalmelding.
    'timeout' => (int) env('MERKEN_TIMEOUT', 20),

    'merken' => $uit,
];
