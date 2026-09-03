<?php

namespace Tests\Feature;

use App\Support\Brand;
use App\Support\IndexNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * IndexNow: het sleutelbestand bewijst dat wij de site zijn, en de planner
 * meldt alle sitemap-URL's één keer per uitgebrachte versie aan.
 */
class IndexNowTest extends TestCase
{
    use RefreshDatabase;

    public function test_key_file_is_served_only_for_our_own_key(): void
    {
        $key = IndexNow::key();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $key);

        $this->get("/{$key}.txt")->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee($key);
        $this->get('/' . str_repeat('0', 32) . '.txt')->assertNotFound();
    }

    public function test_command_submits_all_sitemap_urls_once_per_version(): void
    {
        Http::fake(['api.indexnow.org/*' => Http::response('', 202)]);
        Cache::flush();

        $this->artisan('seo:indexnow', ['--force' => true])->assertSuccessful();

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === IndexNow::ENDPOINT
                && $body['host'] === parse_url(config('app.url'), PHP_URL_HOST)
                && $body['key'] === IndexNow::key()
                && $body['keyLocation'] === IndexNow::keyUrl()
                && in_array(url('/kennisbank/eindfactuur'), $body['urlList'], true)
                && in_array(url('/gratis-factuur-maken'), $body['urlList'], true)
                && count($body['urlList']) > 50;
        });

        // Dezelfde versie nog een keer: niets versturen.
        $this->artisan('seo:indexnow', ['--force' => true])->assertSuccessful();
        Http::assertSentCount(1);

        // Nieuwe versie: opnieuw aanmelden.
        config(['app.version' => Brand::version() . '-test']);
        $this->artisan('seo:indexnow', ['--force' => true])->assertSuccessful();
        Http::assertSentCount(2);
    }

    public function test_command_does_nothing_outside_production_without_force(): void
    {
        Http::fake();
        $this->artisan('seo:indexnow')->assertSuccessful();
        Http::assertNothingSent();
    }

    public function test_rejection_is_reported_and_not_remembered(): void
    {
        Http::fake(['api.indexnow.org/*' => Http::response('Invalid key', 403)]);
        Cache::flush();

        $this->artisan('seo:indexnow', ['--force' => true])->assertFailed();
        $this->artisan('seo:indexnow', ['--force' => true])->assertFailed();
        Http::assertSentCount(2);
    }
}
