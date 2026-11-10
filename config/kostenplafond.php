<?php

/*
 * Kostenplafond op betaalde koppelingen (zie App\Services\CostGuard).
 *
 * Elke aanroep die geld kost gaat eerst langs CostGuard::spend(). Per dienst
 * gelden (waar ingevuld):
 *
 *  - dag:    zoveel aanroepen per dag over het hele platform;
 *  - team:   zoveel aanroepen per dag per administratie (of per publiek
 *            onderdeel, zoals het Fachora-formulier);
 *  - maand:  maandbudget in hele euro's, geteld met de prijs per aanroep;
 *  - minuut: zoveel aanroepen per minuut over het hele platform (tegen misbruik
 *            van onze sleutel, ook als een aanroep niets kost).
 *
 * Elk plafond is in te stellen met de env-variabele die erbij staat. Leeg of
 * onleesbaar ("tien", "-3") = de standaard hieronder. 0 = de dienst staat dicht.
 *
 * Noodstop: NOODSTOP=1 zet álle betaalde koppelingen dicht, NOODSTOP_KVK=1 (of
 * _AI, _PEPPOL, _SMS, _POST, _CCBR, _GOOGLE) alleen die ene. Een env-variabele wijzigen
 * in Railway herstart de dienst; er hoeft geen code gedeployd te worden.
 *
 * 'prijs' is in eurocent per aanroep (voor AI een schatting: het werkelijke
 * verbruik wordt na de aanroep verrekend). Onderbouwing staat in
 * opdrachten/2026-11-10-kostenplafond-betaalde-koppelingen.md.
 */

return [
    'noodstop' => env('NOODSTOP'),

    // Werkt de database niet, dan telt CostGuard hier door (gedeeld door alle
    // processen op dezelfde server, met een bestandsslot).
    'bestand' => env('KOSTENPLAFOND_BESTAND', storage_path('framework/cache/kostenplafond.json')),

    // Waarschuwing bij dit deel van het dagplafond of maandbudget.
    'alarm_bij' => 0.8,

    'diensten' => [
        // KvK Basisprofiel (ook Vestigingsprofiel en Naamgeving): € 0,02 per bevraging.
        'kvk' => [
            'naam' => 'KvK-gegevens (basisprofiel)',
            'noodstop' => env('NOODSTOP_KVK'),
            'prijs' => 2,
            'dag' => [env('KVK_DAGLIMIET'), 50],
            'team' => [env('KVK_TEAMLIMIET'), 10],
            'maand' => [env('KVK_MAANDBUDGET'), 20],
            'melding' => 'Automatisch ophalen is nu even niet beschikbaar, vul de gegevens zelf in.',
        ],
        // KvK Zoeken: gratis, maar wel met onze sleutel. Alleen een grens per minuut.
        'kvk_zoeken' => [
            'naam' => 'KvK zoeken (gratis)',
            'noodstop' => env('NOODSTOP_KVK'),
            'prijs' => 0,
            'minuut' => [env('KVK_ZOEKEN_MINUUTLIMIET'), 60],
            'melding' => 'Zoeken in het KvK-register is nu even niet beschikbaar, vul de gegevens zelf in.',
        ],
        // Claude (Anthropic): betaald per token. Prijs is een schatting per aanroep;
        // het werkelijke bedrag wordt achteraf verrekend.
        'ai' => [
            'naam' => 'AI (Claude)',
            'noodstop' => env('NOODSTOP_AI'),
            'prijs' => 8,
            // Dollar per miljoen tokens (Claude Opus 5: $5 in, $25 uit), voor het verrekenen achteraf.
            'tokenprijs' => ['in' => 5, 'uit' => 25],
            'dag' => [env('AI_DAGLIMIET'), 300],
            'team' => [env('AI_TEAMLIMIET'), 50],
            'maand' => [env('AI_MAANDBUDGET'), 150],
            'melding' => 'De AI-hulp is nu even niet beschikbaar. Probeer het later opnieuw, of vul het zelf in.',
        ],
        // Peppol via Recommand: per verstuurd document (prijs: aanname, zie opdracht).
        'peppol' => [
            'naam' => 'Peppol versturen (Recommand)',
            'noodstop' => env('NOODSTOP_PEPPOL'),
            'prijs' => 10,
            'dag' => [env('PEPPOL_DAGLIMIET'), 200],
            'team' => [env('PEPPOL_TEAMLIMIET'), 50],
            'maand' => [env('PEPPOL_MAANDBUDGET'), 50],
            'melding' => 'Versturen via Peppol is nu even niet mogelijk. Probeer het later opnieuw, of verstuur de factuur per e-mail.',
        ],
        // Sms op kosten van het platform: verificatiecodes (Fachora, klantportaal) en de
        // eigen administraties zonder tegoed. Sms uit gekocht tegoed telt hier niet mee.
        'sms' => [
            'naam' => 'Sms op kosten van het platform',
            'noodstop' => env('NOODSTOP_SMS'),
            'prijs' => 8,
            'dag' => [env('SMS_DAGLIMIET'), 100],
            'team' => [env('SMS_TEAMLIMIET'), 50],
            'maand' => [env('SMS_MAANDBUDGET'), 40],
            'melding' => 'Er kan nu even geen sms worden verstuurd. Probeer het later opnieuw.',
        ],
        // Brieven per post via Pingen (Fachora): porto en druk per brief.
        'post' => [
            'naam' => 'Brieven per post (Pingen)',
            'noodstop' => env('NOODSTOP_POST'),
            'prijs' => 150,
            'dag' => [env('POST_DAGLIMIET'), 25],
            'maand' => [env('POST_MAANDBUDGET'), 100],
            'melding' => 'Er kan nu even geen brief worden verstuurd.',
        ],
        // Google Places (Fachora): Text Search en Place Details met beoordelingen en
        // contactgegevens (Enterprise-velden, ca. $ 35-40 per 1.000). Zoeken bij een
        // nieuwe opdracht en het nachtelijk verversen (150 per nacht) tellen als eigen team.
        'google' => [
            'naam' => 'Google Places (Fachora)',
            'noodstop' => env('NOODSTOP_GOOGLE'),
            'prijs' => 4,
            'dag' => [env('GOOGLE_DAGLIMIET'), 400],
            'team' => [env('GOOGLE_TEAMLIMIET'), 250],
            'maand' => [env('GOOGLE_MAANDBUDGET'), 150],
            'melding' => 'Zoeken naar bedrijven is nu even niet beschikbaar.',
        ],
        // Curatele- en bewindregister: gratis, maar het abonnement hoort bij ons
        // account bij de Rechtspraak. Grenzen tegen misbruik, geen kosten.
        'ccbr' => [
            'naam' => 'Curatele- en bewindregister (gratis)',
            'noodstop' => env('NOODSTOP_CCBR'),
            'prijs' => 0,
            'dag' => [env('CCBR_DAGLIMIET'), 200],
            'team' => [env('CCBR_TEAMLIMIET'), 40],
            'minuut' => [env('CCBR_MINUUTLIMIET'), 20],
            'melding' => 'Het register raadplegen is nu even niet mogelijk. Probeer het later opnieuw.',
        ],
    ],
];
