<?php

namespace Tests\Feature;

use App\Models\Provider;
use App\Services\Fachora\CeidgClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * CEIDG (dane.biznes.gov.pl): records van eenmanszaken naar Fachora-
 * bedrijven, met PKD-codes naar categorieën. Ze komen als 'unclaimed'
 * binnen en krijgen nooit mail.
 */
class FachoraCeidgTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['brand.active' => 'fachora', 'services.anthropic.key' => null, 'fachora.ceidg_token' => null]);
        Mail::fake();
        $this->travelTo(Carbon::parse('2026-10-16 10:00'));
    }

    /** @return array<string, mixed> */
    private function firm(array $override = []): array
    {
        return $override + [
            'nazwa' => 'HYDRO-MAX JAN NOWAK',
            'wlasciciel' => ['imie' => 'Jan', 'nazwisko' => 'Nowak', 'nip' => '1234563218', 'regon' => '123456789'],
            'adresDzialalnosci' => ['ulica' => 'Prosta', 'budynek' => '1', 'lokal' => '2', 'kod' => '90-001', 'miasto' => 'Łódź', 'wojewodztwo' => 'łódzkie'],
            'pkd' => [['kod' => '4322Z']],
            'telefon' => '600100200',
            'email' => 'jan@hydromax.pl',
            'www' => 'hydromax.pl',
            'status' => 'AKTYWNY',
        ];
    }

    private function fakeCeidg(array $firms): void
    {
        Http::fake([
            'dane.biznes.gov.pl/*' => fn (ClientRequest $r) => str_contains($r->url(), 'page=0')
                ? Http::response(['firmy' => $firms, 'count' => count($firms)], 200)
                : Http::response(['firmy' => [], 'count' => count($firms)], 200),
            'nominatim.openstreetmap.org/*' => Http::response([], 200),
            '*' => Http::response([], 200),
        ]);
    }

    public function test_a_ceidg_record_becomes_an_import_record_with_categories_from_the_pkd_codes(): void
    {
        $record = app(CeidgClient::class)->normalize($this->firm());

        $this->assertSame('HYDRO-MAX JAN NOWAK', $record['name']);
        $this->assertSame('HYDRO-MAX JAN NOWAK', $record['legal_name']);
        $this->assertSame('1234563218', $record['nip']);
        $this->assertSame('123456789', $record['regon']);
        $this->assertSame('jdg', $record['legal_form']);
        $this->assertSame('Prosta 1/2', $record['address']);
        $this->assertSame('90-001', $record['postal_code']);
        $this->assertSame('Łódź', $record['city']);
        $this->assertSame('łódzkie', $record['voivodeship']);
        $this->assertSame('600100200', $record['phone']);
        $this->assertSame('jan@hydromax.pl', $record['email']);
        $this->assertSame('hydromax.pl', $record['website']);
        $this->assertSame(['hydraulik', 'ogrzewanie', 'klimatyzacja'], $record['categories'], 'Elke categorie waar 43.22.Z bij hoort');
        $this->assertFalse($record['consent']);
        $this->assertSame('ceidg', $record['source']);
        $this->assertStringContainsString('AKTYWNY', $record['note']);
        $this->assertStringContainsString('PKD 4322Z', $record['note']);

        // Zonder bedrijfsnaam: de naam van de ondernemer; de hoofd-PKD telt mee; een kapot e-mailadres vervalt.
        $record = app(CeidgClient::class)->normalize($this->firm(['nazwa' => null, 'pkdGlowny' => ['kod' => '43.21.Z'], 'email' => 'geen-adres', 'adresDzialalnosci' => ['ulica' => 'Prosta', 'budynek' => '1', 'kod' => '90-001', 'miasto' => 'Łódź']]));
        $this->assertSame('Jan Nowak', $record['name']);
        $this->assertSame('Prosta 1', $record['address']);
        $this->assertNull($record['email']);
        $this->assertNull($record['voivodeship']);
        $this->assertSame(['hydraulik', 'elektryk', 'ogrzewanie', 'klimatyzacja'], $record['categories']);

        // Een vrijwel leeg record breekt niets.
        $empty = app(CeidgClient::class)->normalize([]);
        $this->assertSame('', $empty['name']);
        $this->assertNull($empty['nip']);
        $this->assertSame([], $empty['categories']);
    }

    public function test_the_list_endpoint_has_no_pkd_so_the_searched_code_is_the_fallback_and_empty_regon_becomes_null(): void
    {
        // Echte lijstrespons (okt 2026): id, nazwa, adresDzialalnosci, wlasciciel, dataRozpoczecia, status, link; geen pkd/telefon/e-mail.
        $record = app(CeidgClient::class)->normalize([
            'id' => 'E7854341-77B6-4131-A8AD-FB41232779E0', 'nazwa' => 'Instalacje Ziemne Konrad Pietrzyk',
            'adresDzialalnosci' => ['ulica' => 'ul. Rubinowa', 'budynek' => '18', 'miasto' => 'Sadków', 'wojewodztwo' => 'MAZOWIECKIE', 'kraj' => 'PL', 'kod' => '26-613'],
            'wlasciciel' => ['imie' => 'Konrad', 'nazwisko' => 'Pietrzyk', 'nip' => '7962648883', 'regon' => ''],
            'dataRozpoczecia' => '2026-10-15', 'status' => 'AKTYWNY',
        ], ['43.22.Z']);

        $this->assertSame('7962648883', $record['nip']);
        $this->assertNull($record['regon']);
        $this->assertSame('ul. Rubinowa 18', $record['address']);
        $this->assertNull($record['phone']);
        $this->assertNull($record['email']);
        $this->assertContains('hydraulik', $record['categories']);
        $this->assertStringContainsString('PKD 43.22.Z', $record['note']);
    }

    public function test_pkd_codes_map_to_categories_regardless_of_dots_and_case(): void
    {
        $this->assertSame(['elektryk'], CeidgClient::categoriesForPkd(['43.21.z']));
        $this->assertSame(['elektryk'], CeidgClient::categoriesForPkd(['4321Z', '43 21 Z']));
        $this->assertSame(['hydraulik', 'ogrzewanie', 'klimatyzacja', 'agd', 'ogrod'], CeidgClient::categoriesForPkd(['95.22.Z', '4322Z']));
        $this->assertSame(['slusarz', 'okna-drzwi'], CeidgClient::categoriesForPkd(['43.32.Z']));
        $this->assertSame(['remont'], CeidgClient::categoriesForPkd(['43.31.Z', '43.39.Z']));
        $this->assertSame([], CeidgClient::categoriesForPkd(['99.99.Z']));
        $this->assertSame([], CeidgClient::categoriesForPkd([]));
    }

    public function test_import_creates_companies_once_and_skips_them_the_second_time(): void
    {
        config(['fachora.ceidg_token' => 'jwt-token']);
        $this->fakeCeidg([
            $this->firm(),
            $this->firm(['nazwa' => 'ELEKTRO NOWAK', 'wlasciciel' => ['nip' => '5260250274', 'regon' => '987654321'], 'pkd' => [['kod' => '4321Z']], 'email' => null]),
        ]);
        $client = app(CeidgClient::class);
        $this->assertTrue($client->configured());

        $result = $client->import('43.22.Z', 'łódzkie');

        $this->assertSame(['created' => 2, 'skipped' => 0, 'pages' => 2], $result);
        Http::assertSent(fn (ClientRequest $r) => str_contains($r->url(), 'dane.biznes.gov.pl/api/ceidg/v3/firmy')
            && str_contains($r->url(), 'pkd=4322Z') && str_contains($r->url(), 'status=AKTYWNY') && str_contains($r->url(), 'page=0')
            && $r->hasHeader('Authorization', 'Bearer jwt-token'));
        $this->assertSame(2, Provider::count());

        $hydro = Provider::where('nip', '1234563218')->firstOrFail();
        $this->assertSame('unclaimed', $hydro->status);
        $this->assertSame('ceidg', $hydro->source);
        $this->assertSame('jdg', $hydro->legal_form);
        $this->assertSame('HYDRO-MAX JAN NOWAK', $hydro->display_name);
        $this->assertSame('Prosta 1/2', $hydro->address_line);
        $this->assertSame('łódzkie', $hydro->voivodeship);
        $this->assertSame('jan@hydromax.pl', $hydro->email);
        $this->assertNull($hydro->consent_at, 'Een openbaar e-mailadres is geen toestemming');
        $this->assertNull($hydro->claimed_at);
        $this->assertSame(['hydraulik', 'klimatyzacja', 'ogrzewanie'], $hydro->categories());
        $this->assertEqualsWithDelta(51.7592, $hydro->lat, 0.001, 'Gegeocodeerd via de stadstabel');
        $this->assertNotNull($hydro->source_fetched_at);
        $this->assertStringContainsString('CEIDG AKTYWNY', (string) $hydro->notes);
        $this->assertSame(['elektryk'], Provider::where('nip', '5260250274')->firstOrFail()->categories());

        // Tweede keer: niets nieuws, niemand dubbel.
        $this->assertSame(['created' => 0, 'skipped' => 2, 'pages' => 2], $client->import('43.22.Z', 'łódzkie'));
        $this->assertSame(2, Provider::count());
        Mail::assertNothingSent();
    }

    public function test_a_failing_api_stops_the_import_without_breaking(): void
    {
        config(['fachora.ceidg_token' => 'jwt-token']);
        Http::fake(['dane.biznes.gov.pl/*' => Http::response(['message' => 'Unauthorized'], 401), '*' => Http::response([], 200)]);

        $this->assertSame(['created' => 0, 'skipped' => 0, 'pages' => 0], app(CeidgClient::class)->import('43.22.Z'));
        $this->assertSame(0, Provider::count());
        $this->expectException(\RuntimeException::class);
        app(CeidgClient::class)->search(['pkd' => '4322Z']);
    }

    public function test_the_command_refuses_without_a_token_and_reports_per_pkd_code_with_one(): void
    {
        $this->assertFalse(app(CeidgClient::class)->configured());
        $this->artisan('fachora:import-ceidg', ['pkd' => ['43.22.Z']])->expectsOutputToContain('CEIDG_TOKEN')->assertExitCode(1);
        $this->assertSame(0, Provider::count());

        config(['fachora.ceidg_token' => 'jwt-token']);
        $this->fakeCeidg([$this->firm()]);
        $this->artisan('fachora:import-ceidg', ['pkd' => ['43.22.Z', '43.21.Z'], '--miasto' => 'Łódź', '--pages' => 3])
            ->expectsOutputToContain('43.22.Z: 1 toegevoegd, 0 overgeslagen, 2 pagina\'s')
            ->expectsOutputToContain('43.21.Z: 0 toegevoegd, 1 overgeslagen, 2 pagina\'s')
            ->assertExitCode(0);
        $this->assertSame(1, Provider::count());
        Http::assertSent(fn (ClientRequest $r) => str_contains($r->url(), 'miasto=') && str_contains($r->url(), 'pkd=4321Z'));
    }
}
