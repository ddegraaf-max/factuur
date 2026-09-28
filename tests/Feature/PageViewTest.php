<?php

namespace Tests\Feature;

use App\Models\PageView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * De bezoekersteller: een verzoek telt pas als mens na het seintje uit de
 * browser, en de mijlpalen (demo, registratie) nemen de herkomst van het
 * bezoek mee.
 */
class PageViewTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private const BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36';

    private const OTHER = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

    private function visit(string $path, string $agent = self::BROWSER, array $headers = [])
    {
        return $this->withHeaders(['User-Agent' => $agent] + $headers)->get($path);
    }

    public function test_a_visit_counts_as_human_after_the_signal(): void
    {
        $this->visit('/')->assertOk()->assertSee('/m/gezien', false);
        $this->visit('/kennisbank', self::OTHER)->assertOk();

        $this->assertSame(2, PageView::views()->count());
        $this->assertSame(0, PageView::views()->human()->count());

        $this->withHeaders(['User-Agent' => self::BROWSER])->post('/m/gezien')->assertNoContent();

        $this->assertSame(1, PageView::views()->human()->count());
        $this->assertSame('/', PageView::views()->human()->first()->path);
    }

    public function test_bots_that_name_themselves_are_not_recorded(): void
    {
        $this->visit('/', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)')->assertOk();

        $this->assertSame(0, PageView::count());
    }

    public function test_registration_records_the_attempt_and_the_source(): void
    {
        Mail::fake();
        Http::fake();

        $this->visit('/incassokosten-berekenen', self::BROWSER, ['Referer' => 'https://www.google.com/'])->assertOk();

        $payload = [
            'firstName' => 'Sanne', 'lastName' => 'de Boer', 'email' => 'sanne@example.com',
            'password' => 'geheim-wachtwoord-1', 'password_confirmation' => 'geheim-wachtwoord-1',
            'companyName' => 'De Boer Timmerwerken', 'companyType' => array_key_first(\App\Support\Market::companyTypes()),
            'kvkNumber' => '1234567', 'acceptTerms' => true, 'newsletter' => false,
        ];

        // Eerst een poging die strandt op het KvK-nummer, dan een die lukt.
        $this->withHeaders(['User-Agent' => self::BROWSER])->post('/register', $payload)->assertSessionHasErrors('kvkNumber');
        $this->withHeaders(['User-Agent' => self::BROWSER])->post('/register', ['kvkNumber' => '12345678'] + $payload)
            ->assertSessionHasNoErrors();

        $this->assertSame(2, PageView::where('event', PageView::EVENT_REGISTER_TRIED)->count());
        $done = PageView::where('event', PageView::EVENT_REGISTERED)->sole();
        $this->assertSame('www.google.com', $done->referrer_host);
        $this->assertNotNull($done->confirmed_at);

        // Mijlpalen zijn geen paginabezoeken.
        $this->assertSame(1, PageView::views()->count());
    }

    public function test_starting_the_demo_is_a_milestone(): void
    {
        $this->withHeaders(['User-Agent' => self::BROWSER])->post('/demo')->assertRedirect();

        $this->assertSame(1, PageView::where('event', PageView::EVENT_DEMO)->count());
    }

    public function test_the_dashboard_separates_people_from_robots(): void
    {
        $this->visit('/')->assertOk();
        $this->visit('/', self::OTHER, ['Referer' => 'https://www.bing.com/search'])->assertOk();
        $this->withHeaders(['User-Agent' => self::OTHER])->post('/m/gezien')->assertNoContent();

        $this->flushHeaders();
        $response = $this->actingAs($this->demoUser())->get(route('marketing.inzichten'))->assertOk();

        $this->assertSame(1, $response->viewData('totals')['people']);
        $this->assertSame(1, $response->viewData('totals')['robots']);
        $this->assertSame(1, $response->viewData('totals')['search']);
        $this->assertSame('www.bing.com', $response->viewData('sources')->first()->source);
        $this->assertSame('Bezoekers', $response->viewData('funnel')[0]['label']);
        $this->assertSame(1, $response->viewData('funnel')[0]['n']);
    }
}
