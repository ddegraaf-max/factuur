<?php

/*
 * Wettelijke rente en incassokosten voor de calculator op /incassokosten-berekenen
 * (App\Support\LegalInterest).
 *
 * De percentages gelden vanaf de genoemde dag tot de volgende regel. Ze worden
 * per 1 januari en 1 juli opnieuw vastgesteld; bron: rijksoverheid.nl
 * (Hoe hoog is de wettelijke rente?). Verandert er iets, voeg dan een regel toe
 * en zet 'checked' op de dag van controle. Een halfjaar zonder wijziging hoeft
 * er niet in.
 */
return [
    // Handelstransacties (bedrijven en overheid onderling), art. 6:119a BW.
    'business' => [
        '2020-01-01' => 8.0,
        '2023-01-01' => 10.5,
        '2023-07-01' => 12.0,
        '2024-01-01' => 12.5,
        '2024-07-01' => 12.25,
        '2025-01-01' => 11.15,
        '2025-07-01' => 10.15,
        '2026-07-01' => 10.4,
    ],

    // Niet-handelstransacties (consumenten), art. 6:119 BW.
    'consumer' => [
        '2020-01-01' => 2.0,
        '2023-01-01' => 4.0,
        '2023-07-01' => 6.0,
        '2024-01-01' => 7.0,
        '2025-01-01' => 6.0,
        '2026-01-01' => 4.0,
    ],

    // Staffel uit het Besluit vergoeding voor buitengerechtelijke incassokosten:
    // [schijf in euro, percentage]; null is het meerdere.
    'scale' => [
        [2500, 15],
        [2500, 10],
        [5000, 5],
        [190000, 1],
        [null, 0.5],
    ],
    'minimum' => 40,
    'maximum' => 6775,

    // De vroegste vervaldatum waarmee de calculator rekent.
    'from' => '2020-01-01',

    // Laatst gecontroleerd bij de bron.
    'checked' => '2026-09-28',
];
