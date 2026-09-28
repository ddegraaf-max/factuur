<?php

namespace Tests\Feature;

use App\Support\LegalInterest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** De calculator voor incassokosten en wettelijke rente op /incassokosten-berekenen. */
class CollectionCostsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-28 10:00:00');
    }

    public function test_collection_costs_follow_the_scale(): void
    {
        $this->assertSame(150.0, LegalInterest::collectionCosts(1000));
        $this->assertSame(45.0, LegalInterest::collectionCosts(300));
        $this->assertSame(40.0, LegalInterest::collectionCosts(150));   // minimum
        $this->assertSame(40.0, LegalInterest::collectionCosts(25));
        $this->assertSame(675.0, LegalInterest::collectionCosts(6000));
        $this->assertSame(875.0, LegalInterest::collectionCosts(10000));
        $this->assertSame(2775.0, LegalInterest::collectionCosts(200000));
        $this->assertSame(6775.0, LegalInterest::collectionCosts(1000000));
        $this->assertSame(6775.0, LegalInterest::collectionCosts(5000000));  // maximum
        $this->assertSame(0.0, LegalInterest::collectionCosts(0));
    }

    public function test_the_rate_of_the_day_applies(): void
    {
        $this->assertSame(10.15, LegalInterest::rateOn(Carbon::parse('2026-06-30'), true));
        $this->assertSame(10.4, LegalInterest::rateOn(Carbon::parse('2026-07-01'), true));
        $this->assertSame(6.0, LegalInterest::rateOn(Carbon::parse('2025-12-31'), false));
        $this->assertSame(4.0, LegalInterest::rateOn(Carbon::parse('2026-01-01'), false));
    }

    public function test_interest_runs_from_the_day_after_the_due_date(): void
    {
        $interest = LegalInterest::interest(5000, Carbon::parse('2026-07-01'), Carbon::parse('2026-08-30'), true);

        $this->assertSame(60, $interest['days']);
        $this->assertSame(85.48, $interest['total']);
        $this->assertCount(1, $interest['periods']);
        $this->assertSame('2026-07-02', $interest['periods'][0]['from']->toDateString());
        $this->assertSame('2026-08-30', $interest['periods'][0]['to']->toDateString());

        // Op de vervaldag zelf is er nog geen rente.
        $this->assertSame(0.0, LegalInterest::interest(5000, Carbon::parse('2026-07-01'), Carbon::parse('2026-07-01'), true)['total']);
    }

    public function test_interest_follows_a_change_of_rate(): void
    {
        $interest = LegalInterest::interest(1000, Carbon::parse('2026-05-31'), Carbon::parse('2026-07-31'), true);

        $this->assertSame(61, $interest['days']);
        $this->assertCount(2, $interest['periods']);
        $this->assertSame([30, 10.15, 8.34], [$interest['periods'][0]['days'], $interest['periods'][0]['rate'], $interest['periods'][0]['interest']]);
        $this->assertSame([31, 10.4, 8.83], [$interest['periods'][1]['days'], $interest['periods'][1]['rate'], $interest['periods'][1]['interest']]);
        $this->assertSame(17.18, $interest['total']);
    }

    public function test_after_a_full_year_the_interest_is_added_to_the_amount(): void
    {
        // 2024 (schrikkeljaar) tegen 7%: 70,00. Daarna 2025 tegen 6% over 1.070: 64,20.
        $interest = LegalInterest::interest(1000, Carbon::parse('2023-12-31'), Carbon::parse('2025-12-31'), false);

        $this->assertSame(731, $interest['days']);
        $this->assertSame(134.2, $interest['total']);
        $this->assertSame(1000.0, $interest['periods'][0]['base']);
        $this->assertSame(1070.0, $interest['periods'][1]['base']);
    }

    public function test_the_page_shows_the_calculation(): void
    {
        $this->get('/incassokosten-berekenen')->assertOk()
            ->assertSee('Incassokosten en rente berekenen')
            ->assertSee('10,4%')
            ->assertDontSee('Totaal te vorderen');

        $this->get('/incassokosten-berekenen?bedrag=1.000%2C00&vervaldatum=2026-07-01&tot=2026-08-30&klant=zakelijk')->assertOk()
            ->assertSee('Totaal te vorderen')
            ->assertSee('€ 150,00')
            ->assertSee('€ 17,10')
            ->assertSee('€ 1.167,10')
            ->assertSee('wettelijke handelsrente')
            ->assertDontSee('Let op bij een consument');

        // Een bedrag met een punt voor de duizendtallen, een consument, en btw over de kosten.
        $this->get('/incassokosten-berekenen?bedrag=1.000&vervaldatum=2026-07-01&tot=2026-08-30&klant=consument&btw=1')->assertOk()
            ->assertSee('€ 31,50')
            ->assertSee('€ 6,58')      // 1.000 × 4% × 60 / 365
            ->assertSee('€ 1.188,08')
            ->assertSee('Let op bij een consument');
    }

    public function test_unreadable_input_gives_a_message_instead_of_a_result(): void
    {
        $this->get('/incassokosten-berekenen?bedrag=veel&vervaldatum=2026-07-01')->assertOk()
            ->assertSee('Vul het factuurbedrag in')
            ->assertDontSee('Totaal te vorderen');

        $this->get('/incassokosten-berekenen?bedrag=100&vervaldatum=2012-01-01')->assertOk()
            ->assertSee('vervaldata vanaf 1 januari 2020')
            ->assertDontSee('Totaal te vorderen');

        $this->get('/incassokosten-berekenen?bedrag=100&vervaldatum=2026-07-01&tot=2026-06-01')->assertOk()
            ->assertSee('De einddatum ligt vóór de vervaldatum')
            ->assertDontSee('Totaal te vorderen');
    }

    public function test_the_article_links_to_the_calculator(): void
    {
        $this->get('/kennisbank/incassokosten-wettelijke-rente-berekenen')->assertOk()
            ->assertSee('Naar de calculator')
            ->assertSee(route('incassokosten-calculator'), false);

        $this->assertContains(url('/incassokosten-berekenen'), app(\App\Services\SitemapService::class)->allUrls());
    }
}
