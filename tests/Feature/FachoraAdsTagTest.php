<?php

namespace Tests\Feature;

use App\Models\ServiceJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** 1.101.1: Google Ads-tag op fachora.pl, met één conversie-event op de pagina direct na een bevestigde aanvraag. */
class FachoraAdsTagTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['brand.active' => 'fachora', 'inertia.testing.ensure_pages_exist' => false, 'services.anthropic.key' => null, 'fachora.customer_choice' => true]);
        Cache::flush();
        Mail::fake();
        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response(['address' => ['county' => 'powiat gostyniński']], 200),
            'nominatim.openstreetmap.org/*' => Http::response([['lat' => '52.4410', 'lon' => '19.5300']], 200),
            '*' => Http::response([], 200),
        ]);
    }

    public function test_every_fachora_page_carries_the_google_tag_and_only_the_page_after_confirmation_fires_the_conversion(): void
    {
        $home = (string) $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('googletagmanager.com/gtag/js?id=AW-18521175925', $home);
        $this->assertStringNotContainsString('YSIXCNr9iLIdEPXuyv9E', $home, 'geen conversie-event op gewone pagina\'s');

        $job = ServiceJob::create([
            'reference' => ServiceJob::nextReference(), 'token' => bin2hex(random_bytes(32)), 'status' => 'pending',
            'category' => 'kominiarz', 'title' => 'Komin', 'description' => 'Czyszczenie komina.', 'urgency' => 'week', 'language' => 'pl',
            'customer_name' => 'Anna Nowak', 'customer_email' => 'anna@example.com', 'customer_locale' => 'pl',
            'postal_code' => '09-550', 'city' => 'Szczawin Kościelny', 'lat' => 52.4410, 'lng' => 19.5300,
        ]);
        $this->post(route('fachora.job.confirm', $job->token))->assertRedirect($job->url())->assertSessionHas('ads_conversion', 'request');

        $after = (string) $this->get($job->url())->assertOk()->getContent();
        $this->assertSame(1, substr_count($after, "'send_to': 'AW-18521175925/YSIXCNr9iLIdEPXuyv9E'"), 'precies één conversie-event');

        $again = (string) $this->get($job->url())->assertOk()->getContent();
        $this->assertStringNotContainsString('YSIXCNr9iLIdEPXuyv9E', $again, 'verversen telt niet nog een keer');
        $this->assertStringContainsString('gtag/js?id=AW-18521175925', $again);
    }

    public function test_other_brands_do_not_get_the_tag(): void
    {
        config(['brand.active' => 'easyinvoice']);
        $this->assertStringNotContainsString('googletagmanager', (string) $this->get('/')->assertOk()->getContent());
    }
}
