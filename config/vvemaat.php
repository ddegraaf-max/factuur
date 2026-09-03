<?php

/*
 * De koppeling met VvEMaat.
 *
 * env() staat hier en nergens anders: dat is de regel in dit project
 * (Larastan noEnvCallsOutsideOfConfig), en het is ook de enige manier waarop
 * `config:cache` betrouwbaar werkt — buiten config leest env() na het cachen
 * niets meer.
 *
 * Zonder sleutel doet de koppeling niets. Dat is met opzet: een halve
 * koppeling die af en toe een melding stuurt is verwarrender dan een die
 * uitstaat, en aan de andere kant weigert VvEMaat zonder sleutel toch alles.
 */
return [
    'url' => rtrim((string) env('VVEMAAT_URL', 'https://vvemaat.nl'), '/'),

    'sleutel' => (string) env('VVEMAAT_KOPPELVLAK_SLEUTEL', ''),

    /*
     * Kort. Dit gebeurt tijdens het opslaan van een betaling, dus terwijl er
     * iemand op een scherm zit te wachten. Lukt het niet binnen deze tijd, dan
     * probeert de planner het later opnieuw — dat is beter dan een gebruiker
     * laten wachten op een systeem dat er niets mee te maken heeft.
     */
    'timeout' => (int) env('VVEMAAT_TIMEOUT', 5),
];
