<?php

namespace Tests\Feature;

use App\Services\NbpService;
use App\Services\WindykacjaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Wettelijke rente en rekompensata volgens de Poolse wet op betalingsachterstanden:
 * rente per halfjaarperiode (NBP-referentie + 10 p.p.) en de vergoeding van
 * 40/70/100 EUR omgerekend tegen de NBP-koers van de laatste werkdag van de maand
 * vóór de vervalmaand — met de vaste koers als vangnet.
 */
class NbpRatesTest extends TestCase
{
    use RefreshDatabase;

    private function nbpTable(array $rates): array
    {
        return ['table' => 'A', 'currency' => 'euro', 'code' => 'EUR', 'rates' => $rates];
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['brand.active' => 'lopra_pl']);
        Cache::flush();
    }

    public function test_compensation_uses_the_nbp_rate_of_the_last_working_day_before_the_due_month(): void
    {
        Http::fake([
            'api.nbp.pl/*' => Http::response($this->nbpTable([
                ['no' => '123/A/NBP/2026', 'effectiveDate' => '2026-06-29', 'mid' => 4.2500],
                ['no' => '124/A/NBP/2026', 'effectiveDate' => '2026-06-30', 'mid' => 4.2711],
            ])),
        ]);

        $w = app(WindykacjaService::class);
        $c = $w->compensation(4000, Carbon::parse('2026-07-15'));

        $this->assertSame(40, $c['eur']);
        $this->assertSame(170.84, $c['pln']);          // 40 × 4,2711
        $this->assertSame(4.2711, $c['rate']);
        $this->assertSame('2026-06-30', $c['rate_date']);
        $this->assertSame('nbp', $c['source']);

        // Venster tot en met 30 juni (laatste dag van de maand vóór juli).
        Http::assertSent(fn ($req) => str_contains($req->url(), '/2026-06-16/2026-06-30/'));

        // Tweede keer uit de cache: geen nieuwe aanroep.
        $w->compensation(9000, Carbon::parse('2026-07-02'));
        Http::assertSentCount(1);
    }

    public function test_fallback_rate_when_nbp_is_unreachable(): void
    {
        Http::fake(['api.nbp.pl/*' => Http::response('', 500)]);

        $c = app(WindykacjaService::class)->compensation(4000, Carbon::parse('2026-07-15'));

        $this->assertSame(172.0, $c['pln']);            // 40 × 4,30 (config/markets.php)
        $this->assertSame('fallback', $c['source']);
        $this->assertNull($c['rate_date']);

        $fx = app(NbpService::class)->eurRateOnOrBefore(Carbon::parse('2026-06-30'));
        $this->assertSame('fallback', $fx['source']);
    }

    public function test_statutory_interest_follows_the_half_year_periods(): void
    {
        $w = app(WindykacjaService::class);

        $this->assertSame(0.14, $w->interestRateOn(Carbon::parse('2026-03-01')));
        $this->assertSame(0.1375, $w->interestRateOn(Carbon::parse('2026-07-01')));
        $this->assertSame(0.1525, $w->interestRateOn(Carbon::parse('2025-12-31')));
        $this->assertSame(0.1675, $w->interestRateOn(Carbon::parse('2020-01-01'))); // vóór de tabel: eerste tarief

        // 10 000 zł, vervaldatum 15 juni 2026, stand 15 juli 2026: 15 dagen à 14% + 15 dagen à 13,75%.
        $i = $w->interestBetween(10000, Carbon::parse('2026-06-15'), Carbon::parse('2026-07-15'));
        $this->assertCount(2, $i['periods']);
        $this->assertSame(['2026-06-16', '2026-06-30', 15, 0.14, 57.53], array_values($i['periods'][0]));
        $this->assertSame(['2026-07-01', '2026-07-15', 15, 0.1375, 56.51], array_values($i['periods'][1]));
        $this->assertSame(114.04, $i['total']);

        // Niet vervallen: niets.
        $this->assertSame(0.0, $w->interestBetween(10000, Carbon::parse('2026-07-15'), Carbon::parse('2026-07-15'))['total']);

        // Eén periode: gelijk aan de eenvoudige formule.
        $one = $w->interestBetween(10000, Carbon::parse('2026-01-31'), Carbon::parse('2026-05-11'));
        $this->assertCount(1, $one['periods']);
        $this->assertSame($w->interest(10000, 100, 0.14), $one['total']);
    }

    public function test_public_rates_endpoint_serves_the_calculator(): void
    {
        Http::fake([
            'api.nbp.pl/*' => Http::response($this->nbpTable([
                ['no' => '084/A/NBP/2026', 'effectiveDate' => '2026-04-30', 'mid' => 4.3128],
            ])),
        ]);

        $res = $this->getJson('/kalkulator-odsetek/stawki?termin=2026-05-10&data=2026-08-30')->assertOk();

        $res->assertJsonPath('eur_pln', 4.3128)
            ->assertJsonPath('eur_pln_date', '2026-04-30')
            ->assertJsonPath('source', 'nbp')
            ->assertJsonPath('rate', 0.1375);
        $this->assertContains(['from' => '2026-07-01', 'rate' => 0.1375], $res->json('periods'));

        // Zonder vervaldatum: vangnet, geen NBP-aanroep.
        $this->getJson('/kalkulator-odsetek/stawki')->assertOk()->assertJsonPath('source', 'fallback');

        // Alleen in de Poolse markt.
        config(['brand.active' => 'easyinvoice']);
        $this->getJson('/kalkulator-odsetek/stawki')->assertNotFound();
    }
}
