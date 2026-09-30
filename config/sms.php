<?php

/*
 * Sms-tegoed: wat een bundel kost.
 *
 * De prijs per sms is wat Smstools ons rekent bij die hoeveelheid, plus onze
 * opslag per sms. Alle bedragen zijn exclusief btw.
 *
 * Wijzigt Smstools zijn prijzen, pas dan de inkoopprijzen hieronder aan.
 */
return [
    // Onze opslag per sms, bovenop de inkoopprijs.
    'markup' => (float) env('SMS_MARKUP', 0.05),

    // Aantal sms'en => inkoopprijs per sms bij Smstools.
    'bundles' => [
        100 => 0.050,
        200 => 0.050,
        500 => 0.045,
        1000 => 0.040,
    ],

    // Btw over het tegoed.
    'vat_rate' => 21.0,
];
