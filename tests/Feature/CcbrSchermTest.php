<?php

namespace Tests\Feature;

use App\Models\CcbrCheck;
use App\Models\Customer;
use App\Services\CustomerScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * De controle op curatele en bewind bij een klant.
 *
 * ── Waar het hier om draait ───────────────────────────────────────────────
 *
 * Twee dingen die geen van beide vanzelf goed blijven:
 *
 *  1. De uitkomst staat in de klantscore-kaart, maar telt niet mee in de score.
 *     De gebruiksvoorwaarden van de Rechtspraak staan één doel toe —
 *     handelspartijen informeren over de maatregel — en een cijfer waarin het
 *     meeweegt is een ander doel. In dezelfde kaart zetten is daarom een
 *     keuze die bewaakt moet worden: één regel in CustomerScoreService en het
 *     zou er stil in kunnen glippen.
 *  2. De bewaartermijn. Artikel 2 eist vernietiging binnen zes maanden na
 *     beëindiging van de maatregel; artikel 5 laat de Raad het abonnement
 *     opzeggen als dat niet gebeurt.
 */
class CcbrSchermTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.ccbr' => [
            'username' => 'TESTGEBRUIKER',
            'password' => 'testwachtwoord',
            'sts' => 'https://sts.rechtspraak.nl/adfs/services/trust/13/usernamemixed',
            'url' => 'https://ccbrservice.rechtspraak.nl/ccbrdataservice.svc',
            'realm' => 'https://ccbrservice.rechtspraak.nl/',
            'cacert' => resource_path('certs/pkioverheid-private-root-g1.pem'),
        ]]);
    }

    private function bericht(string $naam): string
    {
        return file_get_contents(base_path("tests/fixtures/ccbr/{$naam}.xml"));
    }

    /** @var array{0: string, 1: int} */
    private array $antwoord = ['', 200];

    private function doeAlsof(string $zoek, ?string $raadpleeg = null): void
    {
        $this->antwoord = [$zoek, 200];
        $raadpleegBericht = $raadpleeg;

        Http::fake(function ($verzoek) use ($raadpleegBericht) {
            if (str_contains($verzoek->url(), 'sts.rechtspraak.nl')) {
                return Http::response($this->bericht('token-antwoord'), 200);
            }

            // Welke bewerking het is, staat in het Content-Type (SOAP 1.2).
            $actie = (string) $verzoek->header('Content-Type')[0];
            if ($raadpleegBericht && str_contains($actie, 'RaadpleegRegisterkaart')) {
                return Http::response($raadpleegBericht, 200);
            }

            return Http::response(...$this->antwoord);
        });
    }

    private function particulier(): Customer
    {
        $klant = Customer::orderBy('id')->firstOrFail();
        $klant->forceFill(['type' => 'consumer', 'name' => 'Jan Jansen'])->save();

        return $klant;
    }

    // ------------------------------------------------------------ de controle

    public function test_een_treffer_wordt_vastgelegd_en_op_het_scherm_getoond(): void
    {
        $this->actingAs($this->demoUser());
        $klant = $this->particulier();
        $this->doeAlsof($this->bericht('zoek-antwoord'), $this->bericht('raadpleeg-antwoord'));

        $this->post(route('customers.ccbr.check', $klant), [
            'achternaam' => 'Jansen',
            'geboortedatum' => '1991-04-01',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $check = CcbrCheck::where('customer_id', $klant->id)->firstOrFail();
        $this->assertTrue($check->gevonden);
        $this->assertSame('bewind', $check->maatregel);
        $this->assertSame('BM00001/0001', $check->kaartnummer);
        $this->assertSame('Rechtbank Noord-Holland, zittingsplaats Lutjebroek', $check->rechtbank);

        $this->get(route('customers.show', $klant))->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('ccbr.check.gevonden', true)
                ->where('ccbr.check.maatregel', 'bewind'));
    }

    public function test_niets_gevonden_wordt_ook_vastgelegd(): void
    {
        $this->actingAs($this->demoUser());
        $klant = $this->particulier();

        // Hetzelfde bericht, maar zonder registerkaarten.
        $leeg = preg_replace('#<b:Registerkaarten>.*</b:Registerkaarten>#s', '<b:Registerkaarten/>', $this->bericht('zoek-antwoord'));
        $this->doeAlsof($leeg);

        $this->post(route('customers.ccbr.check', $klant), [
            'achternaam' => 'Jansen', 'geboortedatum' => '1991-04-01',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $check = CcbrCheck::where('customer_id', $klant->id)->firstOrFail();
        $this->assertFalse($check->gevonden);

        // "Niet gevonden" zegt alleen iets samen met de vraag die gesteld is.
        $this->assertSame('Jansen', $check->achternaam);
        $this->assertSame('1991-04-01', $check->geboortedatum->format('Y-m-d'));
    }

    public function test_zonder_geboortegegeven_wordt_het_register_niet_bevraagd(): void
    {
        $this->actingAs($this->demoUser());
        $klant = $this->particulier();
        Http::fake();

        $this->post(route('customers.ccbr.check', $klant), ['achternaam' => 'Jansen'])
            ->assertSessionHasErrors('geboortedatum');

        Http::assertNothingSent();
        $this->assertSame(0, CcbrCheck::count());
    }

    public function test_bij_een_bedrijf_heeft_de_controle_geen_zin(): void
    {
        $this->actingAs($this->demoUser());
        $klant = Customer::orderBy('id')->firstOrFail();
        $klant->forceFill(['type' => 'business'])->save();
        Http::fake();

        $this->post(route('customers.ccbr.check', $klant), [
            'achternaam' => 'Jansen', 'geboortedatum' => '1991-04-01',
        ])->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_een_naamgenoot_kan_worden_weggehaald(): void
    {
        $this->actingAs($this->demoUser());
        $klant = $this->particulier();
        $this->doeAlsof($this->bericht('zoek-antwoord'), $this->bericht('raadpleeg-antwoord'));

        $this->post(route('customers.ccbr.check', $klant), ['achternaam' => 'Jansen', 'geboortejaar' => 1991]);
        $this->assertSame(1, CcbrCheck::count());

        $this->delete(route('customers.ccbr.forget', $klant))->assertRedirect();
        $this->assertSame(0, CcbrCheck::count());
    }

    // --------------------------------------------------- buiten de klantscore

    public function test_de_uitkomst_telt_niet_mee_in_de_klantscore(): void
    {
        /*
         * Dit is de afspraak met de Rechtspraak, in een test gezet omdat hij
         * anders stil sneuvelt: één regel in CustomerScoreService die 'ccbr'
         * aan de bronnen toevoegt, en het register bepaalt ineens een cijfer.
         * Dat is volgens artikel 2 een ander doel, en daarmee onrechtmatig.
         */
        $this->actingAs($this->demoUser());
        $klant = $this->particulier();

        $dienst = app(CustomerScoreService::class);
        $voor = $dienst->score($klant, true);

        $this->doeAlsof($this->bericht('zoek-antwoord'), $this->bericht('raadpleeg-antwoord'));
        $this->post(route('customers.ccbr.check', $klant), ['achternaam' => 'Jansen', 'geboortedatum' => '1991-04-01']);
        $this->assertTrue(CcbrCheck::where('customer_id', $klant->id)->firstOrFail()->gevonden);

        $na = $dienst->score($klant->fresh(), true);

        $this->assertSame($voor['score'], $na['score'], 'het register hoort het cijfer niet te veranderen');
        $this->assertSame($voor['grade'], $na['grade']);

        $bronnen = collect($na['sources'] ?? [])->pluck('key')->all();
        $this->assertNotContains('ccbr', $bronnen, 'curatele en bewind hoort geen bron van de score te zijn');

        foreach ($na['signals'] as $signaal) {
            $this->assertStringNotContainsStringIgnoringCase('curatele', $signaal['label']);
            $this->assertStringNotContainsStringIgnoringCase('bewind', $signaal['label']);
        }
    }

    // ------------------------------------------------------- de bewaartermijn

    public function test_de_vernietigingsdatum_is_de_vroegste_van_twee(): void
    {
        // Maatregel loopt nog: zes maanden na de controle.
        $this->assertSame(
            now()->addMonths(6)->format('Y-m-d'),
            CcbrCheck::vernietigingsdatum(now())->format('Y-m-d')
        );

        // Maatregel is vorige maand geëindigd: dán gaat die datum voor.
        $einde = now()->subMonth();
        $this->assertSame(
            $einde->copy()->addMonths(6)->format('Y-m-d'),
            CcbrCheck::vernietigingsdatum(now(), $einde)->format('Y-m-d')
        );

        // Een einddatum in de toekomst maakt de termijn niet langer.
        $this->assertSame(
            now()->addMonths(6)->format('Y-m-d'),
            CcbrCheck::vernietigingsdatum(now(), now()->addYear())->format('Y-m-d')
        );
    }

    public function test_verlopen_uitkomsten_worden_vernietigd_en_lopende_niet(): void
    {
        $this->actingAs($this->demoUser());
        $klant = $this->particulier();

        $verlopen = CcbrCheck::create([
            'customer_id' => $klant->id, 'checked_at' => now()->subMonths(7),
            'achternaam' => 'Oud', 'gevonden' => true, 'maatregel' => 'bewind',
            'vernietigen_op' => today()->subDay(),
        ]);
        $vers = CcbrCheck::create([
            'customer_id' => $klant->id, 'checked_at' => now(),
            'achternaam' => 'Nieuw', 'gevonden' => true, 'maatregel' => 'bewind',
            'vernietigen_op' => today()->addMonths(6),
        ]);

        $this->artisan('ccbr:opruimen')->assertExitCode(0);

        $this->assertNull(CcbrCheck::find($verlopen->id), 'de verlopen uitkomst hoort vernietigd te zijn');
        $this->assertNotNull(CcbrCheck::find($vers->id), 'een lopende uitkomst hoort te blijven staan');
    }

    public function test_de_opruimtaak_staat_ingepland(): void
    {
        // Zonder planning wordt de termijn nooit gehaald, en dat is een
        // voorwaarde van het abonnement — geen detail.
        $taken = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->map(fn ($e) => $e->command ?? '')
            ->filter(fn ($c) => str_contains($c, 'ccbr:opruimen'));

        $this->assertCount(1, $taken, 'ccbr:opruimen hoort dagelijks te draaien');
    }
}
