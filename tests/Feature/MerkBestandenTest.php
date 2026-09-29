<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Elk merk verwijst naar bestanden die er ook echt zijn.
 *
 * config/brand.php noemt per merk een stuk of tien paden: het pictogram, de
 * favicon in vier formaten, het woordmerk, het themabestand, de afbeelding voor
 * een gedeelde link. Die paden worden nergens gecontroleerd — ze belanden
 * rechtstreeks in een <link> of een <img>, en een verschrijving levert een 404
 * op die niemand ziet omdat een ontbrekend pictogram geen foutmelding geeft.
 *
 * Dat is dezelfde soort fout als een opmaakklasse die niet bestaat: alles
 * rendert, en het ziet er alleen net verkeerd uit. Bij een nieuw merk is het
 * bovendien het waarschijnlijkst, want dan worden alle paden in één keer
 * overgetypt.
 */
class MerkBestandenTest extends TestCase
{
    /** De sleutels in config/brand.php die naar een bestand in public/ wijzen. */
    private const PADSLEUTELS = [
        'theme_css', 'mark', 'email_mark', 'sidebar_mark', 'icon',
        'favicon_32', 'favicon_512', 'favicon_svg', 'favicon_ico',
        'apple_touch', 'og_image', 'wordmark', 'wordmark_dark',
    ];

    public function test_elk_merk_verwijst_naar_bestanden_die_bestaan(): void
    {
        $ontbreekt = [];

        foreach (config('brand.brands', []) as $merk => $gegevens) {
            foreach (self::PADSLEUTELS as $sleutel) {
                $pad = $gegevens[$sleutel] ?? null;
                if (! is_string($pad) || $pad === '') {
                    continue;   // niet elk merk vult elk pad; dat mag
                }
                if (! file_exists(public_path(ltrim($pad, '/')))) {
                    $ontbreekt[] = "{$merk}.{$sleutel} → {$pad}";
                }
            }
        }

        $this->assertSame([], $ontbreekt,
            "Deze merkbestanden staan in config/brand.php maar niet in public/:\n  "
            . implode("\n  ", $ontbreekt));
    }

    /**
     * De homepage van elk merk moet bestaan.
     *
     * `home_view` wordt rechtstreeks aan view() gegeven; klopt hij niet, dan
     * krijgt een bezoeker een foutpagina op de startpagina — het enige adres
     * waar iedereen binnenkomt.
     */
    public function test_elk_merk_heeft_een_homepage(): void
    {
        foreach (config('brand.brands', []) as $merk => $gegevens) {
            $view = $gegevens['home_view'] ?? 'landing';
            $this->assertTrue(view()->exists($view),
                "Merk {$merk} verwijst naar de view '{$view}', maar die bestaat niet.");
        }
    }

    /**
     * Twee merken met dezelfde kleur of hetzelfde domein is bijna zeker een
     * kopieerfout: bij een nieuw merk wordt het blok van een bestaand merk
     * overgenomen en blijft er iets staan.
     *
     * De kleur vergelijken we binnen één markt: Lopra en Lopra Polska zijn
     * hetzelfde merk in twee landen en delen hun kleur met opzet.
     */
    public function test_merken_zijn_van_elkaar_te_onderscheiden(): void
    {
        $domeinen = [];
        $kleuren = [];

        foreach (config('brand.brands', []) as $merk => $gegevens) {
            $domein = strtolower((string) ($gegevens['domain'] ?? ''));
            $kleur = strtoupper((string) ($gegevens['color'] ?? ''));

            $this->assertNotEmpty($domein, "Merk {$merk} heeft geen domein.");
            $this->assertArrayNotHasKey($domein, $domeinen,
                "Merk {$merk} deelt het domein {$domein} met " . ($domeinen[$domein] ?? '') . '.');
            $domeinen[$domein] = $merk;

            if ($kleur !== '') {
                $sleutel = ($gegevens['market'] ?? 'nl') . ' ' . $kleur;
                $this->assertArrayNotHasKey($sleutel, $kleuren,
                    "Merk {$merk} heeft dezelfde kleur {$kleur} als " . ($kleuren[$sleutel] ?? '') . '.');
                $kleuren[$sleutel] = $merk;
            }
        }
    }
}
