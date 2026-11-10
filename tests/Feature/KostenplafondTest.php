<?php

namespace Tests\Feature;

use App\Services\Ai\AiBudget;
use App\Services\CostCapReached;
use App\Services\CostGuard;
use App\Services\KvkService;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Kostenplafond op betaalde koppelingen (opdracht 2026-11-10): de KvK eerst,
 * daarna AI, Peppol, sms op kosten van het platform, post en CCBR. Een
 * grapjas met een script mag nooit een torenhoge rekening veroorzaken.
 */
class KostenplafondTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private const MELDING = 'Automatisch ophalen is nu even niet beschikbaar, vul de gegevens zelf in.';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.kvk.key' => 'test',
            'services.kvk.base' => 'https://api.kvk.nl/test',
            'kostenplafond.diensten.kvk.dag' => [null, 50],
            'kostenplafond.diensten.kvk.team' => [null, 10],
            'kostenplafond.diensten.kvk.maand' => [null, 20],
        ]);
    }

    private function fakeKvk(int $status = 200): void
    {
        Http::fake([
            'api.kvk.nl/test/api/v1/basisprofielen/*' => $status === 200
                ? Http::response([
                    'kvkNummer' => '11998877', 'naam' => 'De Vries Bouw B.V.', 'formeleRegistratiedatum' => '20120519',
                    'materieleRegistratie' => ['datumAanvang' => '20120519'],
                    '_embedded' => [
                        'eigenaar' => ['rechtsvorm' => 'Besloten vennootschap'],
                        'hoofdvestiging' => ['adressen' => [['type' => 'bezoekadres', 'straatnaam' => 'Dorpsstraat', 'huisnummer' => 1, 'postcode' => '3441AB', 'plaats' => 'Woerden']]],
                    ],
                ], 200)
                : Http::response(['fout' => [['code' => 'IPD0004']]], $status),
            'api.kvk.nl/test/api/v2/zoeken*' => Http::response(['resultaten' => [['kvkNummer' => '11998877', 'naam' => 'De Vries Bouw B.V.', 'type' => 'hoofdvestiging', 'adres' => ['binnenlandsAdres' => ['straatnaam' => 'Dorpsstraat', 'plaats' => 'Woerden']]]]], 200),
        ]);
    }

    private function kvkCalls(): int
    {
        return Http::recorded(fn ($request) => str_contains($request->url(), '/basisprofielen/'))->count();
    }

    private function counter(string $service, string $scope, ?string $period = null): ?object
    {
        return DB::table('cost_counters')->where(['service' => $service, 'scope' => $scope, 'period' => $period ?? now()->format('Y-m-d')])->first();
    }

    /* ===================== Plafond per dag ===================== */

    public function test_the_platform_day_cap_stops_paid_kvk_lookups_for_everyone(): void
    {
        config(['kostenplafond.diensten.kvk.dag' => [null, 3]]);
        $this->fakeKvk();
        $kvk = app(KvkService::class);

        foreach (['10000001', '10000002', '10000003'] as $i => $number) {
            $this->assertNotNull($kvk->profile($number, $i + 1), "Opvraging {$number} hoort te lukken");
        }
        $this->assertNull($kvk->profile('10000004', 9));
        $this->assertSame(self::MELDING, $kvk->refusal());
        $this->assertSame(3, $this->kvkCalls(), 'Boven het plafond gaat er niets naar buiten');
        $this->assertSame(3, $this->counter('kvk', 'platform')->calls);
        $this->assertSame(6, $this->counter('kvk', 'platform', now()->format('Y-m'))->cents, 'Drie bevragingen van € 0,02');
    }

    public function test_the_team_day_cap_stops_one_team_but_not_the_others(): void
    {
        config(['kostenplafond.diensten.kvk.team' => [null, 2]]);
        $this->fakeKvk();
        $kvk = app(KvkService::class);

        $this->assertNotNull($kvk->profile('10000001', 7));
        $this->assertNotNull($kvk->profile('10000002', 7));
        $this->assertNull($kvk->profile('10000003', 7), 'Derde opvraging van hetzelfde team');
        $this->assertSame(self::MELDING, $kvk->refusal());
        $this->assertNotNull($kvk->profile('10000003', 8), 'Een ander team kan gewoon door');
        $this->assertSame(3, $this->kvkCalls());
        $this->assertSame(2, $this->counter('kvk', 'team:7')->calls, 'Een geweigerde aanroep telt niet mee');
    }

    public function test_the_monthly_budget_closes_the_service(): void
    {
        config(['kostenplafond.diensten.kvk.maand' => ['0,04', 20]]);
        $this->fakeKvk();
        $kvk = app(KvkService::class);

        $this->assertNotNull($kvk->profile('10000001', 1));
        $this->assertNotNull($kvk->profile('10000002', 1));
        $this->assertNull($kvk->profile('10000003', 1));
        $this->assertSame(2, $this->kvkCalls());
    }

    /* ===================== Geheugen ===================== */

    public function test_a_second_lookup_of_the_same_number_costs_nothing(): void
    {
        $this->fakeKvk();
        $kvk = app(KvkService::class);

        $this->assertSame('Dorpsstraat 1', $kvk->profile('11998877', 1)['address_line']);
        $this->assertSame('Dorpsstraat 1', $kvk->profile('11998877', 2)['address_line']);
        $this->assertSame('Besloten vennootschap', $kvk->facts('11998877', false, 3)['legal_form'], 'Profiel en klantscore delen één geheugen');
        $this->assertSame(1, $this->counter('kvk', 'platform')->calls);
        $this->travel(23)->hours();
        $this->assertNotNull($kvk->facts('11998877', false, 3), 'Minstens 24 uur onthouden');

        $this->assertSame(1, $this->kvkCalls());
    }

    public function test_not_found_is_remembered_but_an_outage_is_not(): void
    {
        Http::fake([
            'api.kvk.nl/test/api/v1/basisprofielen/12345678' => Http::response(['fout' => [['code' => 'IPD0004']]], 404),
            'api.kvk.nl/test/api/v1/basisprofielen/87654321' => Http::response('Service Unavailable', 503),
        ]);
        $kvk = app(KvkService::class);
        $this->assertNull($kvk->profile('12345678', 1));
        $this->assertNull($kvk->profile('12345678', 1));
        $this->assertNull($kvk->facts('12345678', false, 1));
        $this->assertSame(1, $this->kvkCalls(), '"Niet gevonden" wordt onthouden: nummers aflopen kost niet elke keer geld');
        $this->assertNull($kvk->refusal(), 'Niet gevonden is geen plafond');

        $this->assertNull($kvk->profile('87654321', 1));
        $this->assertNull($kvk->profile('87654321', 1));
        $this->assertSame(3, $this->kvkCalls(), 'Een storing wordt niet onthouden (1 + 2)');
    }

    public function test_fresh_refreshes_at_most_once_a_day_per_number_per_team(): void
    {
        $this->fakeKvk();
        $kvk = app(KvkService::class);

        $kvk->facts('11998877', true, 1);
        $kvk->facts('11998877', true, 1);
        $kvk->facts('11998877', true, 1);
        $this->assertSame(1, $this->kvkCalls(), 'Drie keer "vers" op één dag is één opvraging');

        $kvk->facts('11998877', true, 2);
        $this->assertSame(2, $this->kvkCalls(), 'Een ander team mag zelf één keer verversen');

        $this->travel(25)->hours();
        $kvk->facts('11998877', true, 1);
        $this->assertSame(3, $this->kvkCalls(), 'Na 24 uur mag het weer');
    }

    public function test_the_customer_score_refresh_button_cannot_be_used_to_run_up_costs(): void
    {
        $this->fakeKvk();
        $user = $this->demoUser();
        $this->actingAs($user);
        Http::fake(['ec.europa.eu/*' => Http::response(['valid' => true, 'name' => 'X'], 200)]);
        $customer = \App\Models\Customer::create([
            'name' => 'De Vries Bouw B.V.', 'type' => 'business', 'email' => 'info@devries.test', 'kvk_number' => '11998877', 'country' => 'NL',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('customers.score', $customer))->assertRedirect();
        }
        $this->assertSame(1, $this->kvkCalls());
    }

    /* ===================== Noodstop ===================== */

    public function test_the_emergency_stop_closes_a_service_without_a_deploy(): void
    {
        $this->fakeKvk();
        $kvk = app(KvkService::class);

        config(['kostenplafond.diensten.kvk.noodstop' => '1']);
        $this->assertNull($kvk->profile('11998877', 1));
        $this->assertSame(self::MELDING, $kvk->refusal());
        $this->assertSame(0, $this->kvkCalls());

        // NOODSTOP_KVK raakt alleen de KvK.
        $this->assertSame(8, app(CostGuard::class)->spend('ai', 1));

        // NOODSTOP zet alles dicht.
        config(['kostenplafond.diensten.kvk.noodstop' => null, 'kostenplafond.noodstop' => 'true']);
        foreach (app(CostGuard::class)->services() as $service) {
            try {
                app(CostGuard::class)->spend($service, 1);
                $this->fail("{$service} hoort dicht te staan");
            } catch (CostCapReached $e) {
                $this->assertSame('noodstop', $e->reason);
            }
        }
    }

    public function test_zero_closes_a_service_and_an_unreadable_value_falls_back_to_the_default(): void
    {
        $guard = app(CostGuard::class);

        config(['kostenplafond.diensten.kvk.dag' => ['tien', 50], 'kostenplafond.diensten.kvk.team' => ['-3', 10], 'kostenplafond.diensten.kvk.maand' => ['20,50', 20]]);
        $this->assertSame(['dag' => 50, 'team' => 10, 'maand' => 2050, 'minuut' => null, 'prijs' => 2], $guard->limits('kvk'));

        config(['kostenplafond.diensten.kvk.dag' => ['0', 50]]);
        $this->expectException(CostCapReached::class);
        $guard->spend('kvk', 1);
    }

    /* ===================== Melding ===================== */

    public function test_the_user_gets_a_friendly_message_without_amounts_and_the_app_keeps_working(): void
    {
        config(['kostenplafond.diensten.kvk.dag' => [null, 1]]);
        $this->fakeKvk();
        $user = $this->demoUser();
        $this->actingAs($user);

        $this->getJson(route('kvk.profile', '10000001'))->assertOk()->assertJsonPath('result.name', 'De Vries Bouw B.V.')->assertJsonMissingPath('message');

        $response = $this->getJson(route('kvk.profile', '10000002'))->assertOk()->assertJsonPath('result', null);
        $message = $response->json('message');
        $this->assertSame(self::MELDING, $message);
        $this->assertDoesNotMatchRegularExpression('/\d|€|plafond|limiet/i', $message, 'Geen plafond of bedrag in de melding');

        // Zoeken en de rest van de app werken gewoon door.
        $this->getJson(route('kvk.search', ['q' => 'De Vries']))->assertOk()->assertJsonPath('results.0.kvk_number', '11998877');
        $this->get(route('customers.create'))->assertOk();
        $this->get(route('invoices.index'))->assertOk();
    }

    public function test_the_customer_score_says_the_register_is_unavailable_when_the_cap_is_reached(): void
    {
        config(['kostenplafond.diensten.kvk.dag' => [null, 0]]);
        $this->fakeKvk();
        Http::fake(['ec.europa.eu/*' => Http::response(['valid' => true, 'name' => 'X'], 200)]);
        $user = $this->demoUser();
        $this->actingAs($user);
        $customer = \App\Models\Customer::create(['name' => 'De Vries Bouw B.V.', 'type' => 'business', 'email' => 'a@b.test', 'kvk_number' => '11998877', 'country' => 'NL']);

        $sources = collect(app(\App\Services\CustomerScoreService::class)->score($customer, true)['sources'])->keyBy('key');
        $this->assertSame('unknown', $sources['kvk']['status']);
        $this->assertStringContainsString('nu even niet', $sources['kvk']['text']);
        $this->assertSame(0, $this->kvkCalls());
    }

    /* ===================== Zoeken ===================== */

    public function test_searching_does_not_count_towards_the_costs_but_has_a_per_minute_limit(): void
    {
        config(['kostenplafond.diensten.kvk.dag' => [null, 1], 'kostenplafond.diensten.kvk_zoeken.minuut' => [null, 3]]);
        $this->fakeKvk();
        $kvk = app(KvkService::class);

        $this->assertCount(1, $kvk->search('De Vries'));
        $this->assertCount(1, $kvk->search('Jansen'));
        $this->assertCount(1, $kvk->search('Pietersen'));
        $this->assertNull($this->counter('kvk', 'platform'), 'Zoeken telt niet mee voor de betaalde KvK');
        $this->assertSame(0, (int) DB::table('cost_counters')->where('service', 'kvk_zoeken')->sum('cents'));
        $this->assertNotNull($kvk->profile('11998877', 1), 'Het betaalde plafond is nog helemaal vrij');

        try {
            $kvk->search('Klaassen');
            $this->fail('Vierde zoekopdracht in één minuut hoort te worden geweigerd');
        } catch (\DomainException $e) {
            $this->assertStringNotContainsString('3', $e->getMessage());
        }

        $this->travel(2)->minutes();
        $this->assertCount(1, $kvk->search('Klaassen'), 'De volgende minuut mag het weer');
    }

    /* ===================== Storing ===================== */

    public function test_a_broken_counter_never_lifts_the_cap(): void
    {
        config(['kostenplafond.diensten.kvk.dag' => [null, 2]]);
        $guard = app(CostGuard::class);
        Schema::drop('cost_counters');

        $guard->spend('kvk', 1);
        $guard->spend('kvk', 2);
        try {
            $guard->spend('kvk', 3);
            $this->fail('Zonder database hoort het plafond nog steeds te gelden (bestand)');
        } catch (CostCapReached $e) {
            $this->assertSame('dag', $e->reason);
        }

        // Ook het bestand werkt niet: dan telt het werkgeheugen door.
        config(['kostenplafond.bestand' => '/bestaat/niet/kostenplafond.json', 'kostenplafond.diensten.ai.dag' => [null, 1]]);
        $guard->spend('ai', 1);
        $this->expectException(CostCapReached::class);
        $guard->spend('ai', 2);
    }

    public function test_the_file_fallback_is_shared_and_keeps_counting(): void
    {
        config(['kostenplafond.diensten.kvk.dag' => [null, 2]]);
        Schema::drop('cost_counters');

        app(CostGuard::class)->spend('kvk', 1);
        CostGuard::flushMemory(); // een ander proces: werkgeheugen leeg, bestand gedeeld
        app(CostGuard::class)->spend('kvk', 1);
        CostGuard::flushMemory();

        $this->expectException(CostCapReached::class);
        app(CostGuard::class)->spend('kvk', 1);
    }

    /* ===================== Alarm ===================== */

    public function test_the_owner_gets_one_mail_at_eighty_percent_and_one_when_the_cap_is_reached(): void
    {
        config(['kostenplafond.diensten.kvk.dag' => [null, 5], 'services.marketing_stats.emails' => 'beheer@example.com']);
        $guard = app(CostGuard::class);
        $subjects = fn () => collect(app('mailer')->getSymfonyTransport()->messages())->map(fn ($m) => $m->getOriginalMessage()->getSubject())->values()->all();

        for ($i = 0; $i < 3; $i++) {
            $guard->spend('kvk', 1);
        }
        $this->assertSame([], $subjects());

        $guard->spend('kvk', 1); // 4 van 5 = 80%
        $guard->spend('kvk', 2); // 5 van 5 = bereikt
        try {
            $guard->spend('kvk', 3);
        } catch (CostCapReached) {
        }
        try {
            $guard->spend('kvk', 3);
        } catch (CostCapReached) {
        }

        $this->assertSame([
            'Kostenplafond: KvK-gegevens (basisprofiel) op 80% van het dagplafond',
            'Kostenplafond: KvK-gegevens (basisprofiel) heeft het dagplafond bereikt',
        ], $subjects(), 'Eén mail per soort per dag');
        $to = collect(app('mailer')->getSymfonyTransport()->messages())->first()->getOriginalMessage()->getTo()[0]->getAddress();
        $this->assertSame('beheer@example.com', $to);
    }

    /* ===================== Overzicht ===================== */

    public function test_the_owner_sees_todays_and_this_months_usage_per_service(): void
    {
        $this->fakeKvk();
        $user = $this->demoUser();
        config(['services.marketing_stats.emails' => $user->email]);
        $this->actingAs($user);
        app(KvkService::class)->profile('11998877', $user->company_id);
        app(CostGuard::class)->spend('ai', $user->company_id);

        $this->get(route('owner.companies.index'))->assertOk()->assertInertia(fn ($page) => $page
            ->component('Stats/Administraties')
            ->where('costs.storing', false)
            ->where('costs.diensten', function ($services) {
                $kvk = collect($services)->firstWhere('key', 'kvk');
                $ai = collect($services)->firstWhere('key', 'ai');

                return $kvk['today_calls'] === 1 && $kvk['month_cents'] === 2 && $kvk['limits']['dag'] === 50
                    && $ai['today_calls'] === 1 && $ai['month_cents'] === 8;
            }));
    }

    /* ===================== Andere diensten ===================== */

    public function test_ai_calls_stop_at_the_cap_and_are_settled_with_the_real_token_costs(): void
    {
        config(['kostenplafond.diensten.ai.dag' => [null, 1]]);
        $message = (object) ['usage' => (object) ['inputTokens' => 2000, 'outputTokens' => 4000]];

        $this->assertSame($message, AiBudget::call(fn () => $message, 5));
        // 2.000 × $5 + 4.000 × $25 per miljoen = 11 cent.
        $this->assertSame(11, $this->counter('ai', 'platform', now()->format('Y-m'))->cents);

        $called = false;
        try {
            AiBudget::call(function () use (&$called) {
                $called = true;
            }, 5);
            $this->fail('AI boven het plafond');
        } catch (CostCapReached $e) {
            $this->assertStringContainsString('niet beschikbaar', $e->getMessage());
        }
        $this->assertFalse($called, 'Boven het plafond gaat er niets naar Anthropic');

        // Het publieke Fachora-formulier telt als eigen team.
        config(['kostenplafond.diensten.ai.dag' => [null, 10], 'kostenplafond.diensten.ai.team' => [null, 1]]);
        AiBudget::as('fachora', fn () => AiBudget::call(fn () => $message));
        $this->assertSame(1, $this->counter('ai', 'team:fachora')->calls);
        $this->expectException(CostCapReached::class);
        AiBudget::as('fachora', fn () => AiBudget::call(fn () => $message));
    }

    public function test_platform_sms_stops_at_the_cap(): void
    {
        config([
            'services.smstools.client_id' => 'id', 'services.smstools.client_secret' => 'geheim',
            'kostenplafond.diensten.sms.dag' => [null, 1],
        ]);
        Http::fake(['api.smsgatewayapi.com/*' => Http::response(['messageid' => 'abc'], 200)]);
        $sms = app(SmsService::class);

        $this->assertTrue($sms->platform('0612345678', 'Fachora: Twój kod to 123456'));
        $this->assertFalse($sms->platform('0612345679', 'Fachora: Twój kod to 654321'));
        Http::assertSentCount(1);
    }
}
