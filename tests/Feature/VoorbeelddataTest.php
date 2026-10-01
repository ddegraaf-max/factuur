<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * De voorbeeldadministratie van de seeder.
 *
 * ── Waarom hier een test op staat ─────────────────────────────────────────
 *
 * Op 28-08-2026 liep DatabaseSeeder na een deploy in de productiedatabase.
 * Daarmee stond "Vries Design B.V." tussen de echte administraties, ging er een
 * incassodossier naar de incassopartner, en kwam er een gebruiker op internet
 * te staan — `demo@easyinvoice.test` — met het wachtwoord dat in README.md,
 * DEPLOYMENT.md en install.sh gepubliceerd stond. Deze repo is openbaar, dus
 * dat wachtwoord was door iedereen te lezen. Het account was eigenaar van twee
 * administraties en had geen tweefactor. Op 01-10-2026 zijn die administraties
 * en die gebruiker verwijderd.
 *
 * Er zaten twee fouten in, en die worden hier allebei vastgezet:
 *
 *  1. de seeder kón in productie draaien;
 *  2. het wachtwoord lag vast, dus één ongeluk was genoeg.
 *
 * De tweede test kijkt in de broncode in plaats van naar gedrag. Dat is
 * ongebruikelijk, maar het is wél wat er misging: niet dat de code een verkeerd
 * wachtwoord kiest, maar dat er een werkend wachtwoord in een openbaar bestand
 * staat. Daar is een tekstcontrole de eerlijke test voor.
 */
class VoorbeelddataTest extends TestCase
{
    use RefreshDatabase;

    public function test_de_seeder_weigert_in_productie(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('db:seed')->assertExitCode(0);

        $this->assertDatabaseMissing('companies', ['name' => 'Vries Design B.V.']);
        $this->assertDatabaseMissing('users', ['email' => 'demo@easyinvoice.test']);
    }

    public function test_er_staat_nergens_een_vast_wachtwoord_voor_de_demo(): void
    {
        $seeder = file_get_contents(database_path('seeders/DatabaseSeeder.php'));

        $this->assertStringNotContainsString(
            "Hash::make('password')",
            $seeder,
            'de seeder hoort een willekeurig wachtwoord te maken, geen vast wachtwoord'
        );

        // De bestanden die je als buitenstaander kunt lezen. Een e-mailadres mag
        // erin staan; een wachtwoord ernaast niet.
        foreach (['README.md', 'DEPLOYMENT.md', 'install.sh'] as $bestand) {
            $tekst = file_get_contents(base_path($bestand));

            foreach ($this->regelsMetEenWachtwoord($tekst) as $regel) {
                $this->fail("{$bestand} publiceert een wachtwoord: {$regel}");
            }
        }
    }

    /**
     * Regels die een wachtwoord prijsgeven: "Wachtwoord: <iets>" of
     * "/ password". Verwijzingen zonder waarde ("een willekeurig wachtwoord",
     * "het wachtwoord staat hierboven") glippen er terecht door.
     *
     * @return list<string>
     */
    private function regelsMetEenWachtwoord(string $tekst): array
    {
        $raak = [];

        foreach (preg_split('/\R/', $tekst) as $regel) {
            if (preg_match('/(wachtwoord|password)\s*[:=]\s*\S/i', $regel)
                && ! str_contains($regel, '<')
                && ! preg_match('/\$\{|env\(|DB_PASSWORD|MAIL_PASSWORD|REDIS_PASSWORD/i', $regel)) {
                $raak[] = trim($regel);
            }

            if (preg_match('#/\s*password\s*"?$#i', $regel)) {
                $raak[] = trim($regel);
            }
        }

        return $raak;
    }
}
