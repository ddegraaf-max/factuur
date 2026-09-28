<?php

namespace App\Support;

/**
 * Landnaam of -code → ISO 3166-1 alpha-2. Het land op een klant is meestal al
 * een code, maar geïmporteerde klanten hebben soms een naam ("Nederland").
 * Onbekend of leeg: het land van de markt.
 */
class CountryCode
{
    public static function of(?string $country): string
    {
        $c = trim((string) $country);
        if ($c === '') {
            return Market::country();
        }
        if (strlen($c) === 2) {
            return strtoupper($c);
        }

        return match (mb_strtolower($c)) {
            'nederland', 'the netherlands', 'netherlands', 'holland', 'holandia' => 'NL',
            'belgië', 'belgie', 'belgium', 'belgia' => 'BE',
            'duitsland', 'germany', 'deutschland', 'niemcy' => 'DE',
            'frankrijk', 'france', 'francja' => 'FR',
            'luxemburg', 'luxembourg' => 'LU',
            'verenigd koninkrijk', 'united kingdom' => 'GB',
            'spanje', 'spain' => 'ES',
            'italië', 'italie', 'italy' => 'IT',
            'polen', 'poland', 'polska' => 'PL',
            default => Market::country(),
        };
    }
}
