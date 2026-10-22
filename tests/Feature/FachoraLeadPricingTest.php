<?php

namespace Tests\Feature;

use App\Models\Provider;
use App\Models\ServiceJob;
use App\Models\ServiceJobOffer;
use App\Services\Fachora\JobService;
use App\Services\Fachora\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Fachora 1.87: gratis voor de klant (met sms-code), het bedrijf betaalt per
 * lead die het ontgrendelt: 5% van de geschatte klusprijs, tussen 15 en 150 zł,
 * de eerste gratis. Provisie alleen nog via Protect.
 */
class FachoraLeadPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'brand.active' => 'fachora', 'inertia.testing.ensure_pages_exist' => false,
            'services.anthropic.key' => null, 'services.stripe.secret' => null,
            'services.smstools.client_id' => null, 'services.smstools.client_secret' => null,
            'services.marketing_stats.emails' => 'eigenaar@example.com',
            'fachora.customer_choice' => true, 'fachora.request_fee' => 0, 'fachora.lead_pricing' => true,
            'fachora.lead_percent' => 5, 'fachora.lead_min' => 15, 'fachora.lead_max' => 150, 'fachora.lead_default' => 30, 'fachora.lead_free_count' => 1,
        ]);
        Mail::fake();
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([], 200),
            '*' => Http::response([], 200),
        ]);
        $this->travelTo(Carbon::parse('2026-10-22 10:00'));
    }

    private function provider(string $name, array $attrs = []): Provider
    {
        $slug = Str::slug($name).'-'.Str::lower(Str::random(5));
        $provider = Provider::create($attrs + [
            'legal_name' => $name, 'display_name' => $name, 'slug' => $slug, 'status' => 'unclaimed',
            'email' => $slug.'@example.com', 'city' => 'Szczecin', 'postal_code' => '70-001',
            'lat' => 53.4285, 'lng' => 14.5528, 'radius_km' => 25, 'source' => 'ceidg',
            'optout_token' => bin2hex(random_bytes(32)),
        ]);
        $provider->syncCategories(['hydraulik']);

        return $provider->refresh();
    }

    /** Een opdracht in de kiesfase, met de gekozen bedrijven verstuurd. */
    private function sentJob(array $providers, array $attrs = []): ServiceJob
    {
        $job = ServiceJob::create($attrs + [
            'reference' => ServiceJob::nextReference(), 'token' => bin2hex(random_bytes(32)), 'status' => 'pending',
            'category' => 'hydraulik', 'title' => 'Cieknący kran', 'description' => 'Kran w kuchni cieknie, potrzebna wymiana.',
            'urgency' => 'week', 'language' => 'pl', 'customer_name' => 'Anna Nowak', 'customer_email' => 'anna@example.com',
            'customer_locale' => 'pl', 'postal_code' => '70-001', 'city' => 'Szczecin', 'lat' => 53.4285, 'lng' => 14.5528,
        ]);
        app(JobService::class)->confirm($job, request());
        app(JobService::class)->selectProviders($job->fresh(), array_map(fn (Provider $p) => $p->id, $providers));

        return $job->fresh();
    }

    private function offerFor(ServiceJob $job, Provider $provider): ServiceJobOffer
    {
        return $job->offers()->where('provider_id', $provider->id)->firstOrFail();
    }

    public function test_the_lead_price_follows_the_size_of_the_job(): void
    {
        $p = $this->provider('Hydro A');
        $leads = app(LeadService::class);
        $cases = [[150, 250, 15.0], [1000, 1400, 60.0], [15000, 25000, 150.0], [null, null, 30.0]];
        foreach ($cases as [$low, $high, $expected]) {
            $job = $this->sentJob([$p], ['estimate_low' => $low, 'estimate_high' => $high]);
            $job->forceFill(['estimate_low' => $low, 'estimate_high' => $high])->save();
            $this->assertSame($expected, $leads->price($this->offerFor($job, $p)->fresh()), "schatting $low-$high");
        }
    }

    public function test_the_first_lead_is_free_and_the_next_one_goes_through_checkout(): void
    {
        config(['services.stripe.secret' => 'sk_test_x']);
        $p = $this->provider('Hydro A', ['claimed_at' => now(), 'status' => 'claimed']);
        $first = $this->offerFor($this->sentJob([$p]), $p);
        $quote = ['price' => 250, 'price_type' => 'fixed', 'available_at' => '2026-10-24 09:00'];

        $this->post(route('fachora.offer.unlock', $first->token))->assertSessionHasNoErrors();
        $first->refresh();
        $this->assertNotNull($first->unlocked_at);
        $this->assertTrue($first->lead_free);
        $this->assertSame(0.0, $first->lead_fee);
        $this->post(route('fachora.offer.respond', $first->token), $quote)->assertSessionHasNoErrors();
        $this->assertSame('responded', $first->fresh()->status);

        // Tweede lead: niet meer gratis. Zonder ontgrendelen geen prijs.
        $job2 = $this->sentJob([$p]);
        $job2->forceFill(['estimate_low' => 1000, 'estimate_high' => 1400])->save();
        $second = $this->offerFor($job2, $p);
        $this->post(route('fachora.offer.respond', $second->token), $quote)->assertSessionHasErrors('offer');
        $this->assertSame('sent', $second->fresh()->status);

        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake([
            'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_lead_2', 'url' => 'https://checkout.stripe.com/c/pay/cs_lead_2'], 200),
            'api.stripe.com/v1/checkout/sessions/cs_lead_2' => Http::response(['id' => 'cs_lead_2', 'payment_status' => 'paid', 'metadata' => ['kind' => 'fachora_lead', 'offer_id' => (string) $second->id]], 200),
            '*' => Http::response([], 200),
        ]);
        $this->post(route('fachora.offer.unlock', $second->token))->assertRedirect('https://checkout.stripe.com/c/pay/cs_lead_2');
        Http::assertSent(fn ($r) => str_contains($r->url(), 'checkout/sessions') && ($r->data()['line_items'][0]['price_data']['unit_amount'] ?? $r->data()['line_items[0][price_data][unit_amount]'] ?? null) == 6000);
        $this->assertNull($second->fresh()->unlocked_at);

        $this->get(route('fachora.offer.unlocked', $second->token))->assertRedirect($second->url());
        $second->refresh();
        $this->assertNotNull($second->unlocked_at);
        $this->assertFalse($second->lead_free);
        $this->assertSame(60.0, $second->lead_fee);
        $this->post(route('fachora.offer.respond', $second->token), $quote)->assertSessionHasNoErrors();
        $this->assertSame('responded', $second->fresh()->status);
    }

    public function test_an_unclaimed_company_accepts_the_terms_before_unlocking(): void
    {
        $p = $this->provider('Hydro B');
        $offer = $this->offerFor($this->sentJob([$p]), $p);

        $this->post(route('fachora.offer.unlock', $offer->token))->assertSessionHasErrors('offer');
        $this->assertNull($offer->fresh()->unlocked_at);

        $this->post(route('fachora.offer.unlock', $offer->token), ['accept_terms' => 1])->assertSessionHasNoErrors();
        $this->assertNotNull($offer->fresh()->unlocked_at);
        $this->assertNotNull($p->fresh()->terms_accepted_at);
    }

    public function test_the_customer_confirms_the_phone_by_sms_before_the_request_goes_out(): void
    {
        config(['services.smstools.client_id' => 'id', 'services.smstools.client_secret' => 'secret', 'services.smstools.url' => 'https://sms.example.test/v1']);
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake([
            'sms.example.test/*' => Http::response(['messageid' => 'm1'], 200),
            '*' => Http::response([], 200),
        ]);
        $p = $this->provider('Hydro C');
        $job = ServiceJob::create([
            'reference' => ServiceJob::nextReference(), 'token' => bin2hex(random_bytes(32)), 'status' => 'pending',
            'category' => 'hydraulik', 'title' => 'Kran', 'description' => 'Kran w kuchni cieknie, potrzebna wymiana.',
            'urgency' => 'week', 'language' => 'pl', 'customer_name' => 'Anna Nowak', 'customer_email' => 'anna@example.com',
            'customer_locale' => 'pl', 'postal_code' => '70-001', 'city' => 'Szczecin', 'lat' => 53.4285, 'lng' => 14.5528,
        ]);
        app(JobService::class)->confirm($job, request());
        $job->refresh();
        $this->assertTrue(app(JobService::class)->smsRequired($job));

        $this->post(route('fachora.job.select', $job->token), ['providers' => [$p->id]])->assertSessionHasErrors('providers');
        $this->assertSame(0, $job->offers()->count());

        $this->post(route('fachora.job.sms.send', $job->token), ['phone' => '+48 600 100 200'])->assertSessionHasNoErrors();
        $job->refresh();
        $this->assertNotNull($job->phone_code);

        $this->post(route('fachora.job.sms.verify', $job->token), ['code' => '000000' === $job->phone_code ? '111111' : '000000'])->assertSessionHasErrors('code');
        $this->post(route('fachora.job.sms.verify', $job->token), ['code' => $job->phone_code])->assertSessionHasNoErrors();
        $this->assertNotNull($job->fresh()->phone_verified_at);

        $this->post(route('fachora.job.select', $job->token), ['providers' => [$p->id]])->assertSessionHasNoErrors();
        $this->assertSame('open', $job->fresh()->status);
        $this->assertSame(1, $job->offers()->count());
    }

    public function test_no_commission_on_directly_paid_jobs_with_lead_pricing(): void
    {
        $p = $this->provider('Hydro D', ['claimed_at' => now(), 'status' => 'claimed']);
        $job = $this->sentJob([$p]);
        $offer = $this->offerFor($job, $p);
        $jobs = app(JobService::class);
        $jobs->respond($offer, ['price' => 300, 'price_type' => 'fixed']);
        $jobs->choose($job->fresh(), $offer->fresh(), ['address_line' => 'ul. Wały Chrobrego 1']);
        $jobs->accept($job->fresh(), 'owner');

        $job->refresh();
        $this->assertSame('accepted', $job->status);
        $this->assertSame(0.0, (float) $job->fee_amount);
    }
}
