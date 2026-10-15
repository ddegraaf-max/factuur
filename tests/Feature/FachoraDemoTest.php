<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Provider;
use App\Models\ProviderCheck;
use App\Models\ServiceJob;
use App\Services\Fachora\Notifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Demo van het Fachora-bedrijvenpanel: eigen pagina (pl/en), één klik bouwt
 * een geverifieerd demo-bedrijf met zaken in elke fase, demo-zaken mailen
 * nooit, en "Demo verlaten" ruimt alles op.
 */
class FachoraDemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'brand.active' => 'fachora', 'inertia.testing.ensure_pages_exist' => false,
            'services.anthropic.key' => null, 'services.stripe.secret' => null,
            'services.marketing_stats.emails' => 'eigenaar@example.com',
        ]);
        Mail::fake();
        Http::fake(['*' => Http::response([], 200)]);
        $this->travelTo(Carbon::parse('2026-10-16 10:00'));
    }

    public function test_demo_page_is_the_panel_demo_not_the_invoicing_demo(): void
    {
        $this->get('/demo')->assertOk()
            ->assertSee('Hydro-Serwis Nowak')->assertSee('Uruchom demo')
            ->assertDontSee('Lopra')->assertDontSee('fakturę');

        $this->get('/demo?lang=en')->assertOk()->assertSee('Run the demo')->assertSee('Hydro-Serwis Nowak');
    }

    public function test_starting_the_demo_builds_a_verified_company_with_jobs_in_every_stage(): void
    {
        $this->post('/demo')->assertRedirect(route('dashboard'));

        $company = Company::query()->where('is_demo', true)->firstOrFail();
        $provider = Provider::query()->where('company_id', $company->id)->firstOrFail();
        $this->assertSame('verified', $provider->status);
        $this->assertNotNull($provider->user_id);
        $this->assertTrue($provider->isDemo());
        $this->assertSame(count(Provider::CHECKS), ProviderCheck::query()->where('provider_id', $provider->id)->where('status', 'passed')->count());
        $this->assertEqualsCanonicalizing(['hydraulik', 'ogrzewanie', 'klimatyzacja'], DB::table('provider_category')->where('provider_id', $provider->id)->pluck('category')->all());

        $jobs = ServiceJob::query()->get();
        $this->assertCount(13, $jobs);
        $this->assertTrue($jobs->every(fn (ServiceJob $j) => $j->isDemo()), 'Elke demo-klant heeft een adres op het demo-domein');
        $this->assertEqualsCanonicalizing(
            ['open', 'open', 'quoted', 'scheduled', 'in_progress', 'done', 'accepted', 'accepted', 'accepted', 'accepted', 'accepted', 'accepted', 'cancelled'],
            $jobs->pluck('status')->all()
        );
        $this->assertSame(13, $jobs->pluck('reference')->unique()->count());

        // Ingelogd als het demo-bedrijf: het dashboard van het panel met alles erop.
        $this->get('/dashboard')->assertRedirect(route('fachora.dashboard'));
        $this->get(route('fachora.dashboard'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Fachora/Provider/Dashboard')
            ->where('provider.display_name', 'Hydro-Serwis Nowak')
            ->where('demo', true)
            ->has('offers', 3)
            ->has('active', 3)
            ->has('closed', 7)
            ->has('reviews', 4));
    }

    public function test_demo_jobs_never_send_mail(): void
    {
        $this->post('/demo');
        $job = ServiceJob::query()->firstOrFail();
        $provider = Provider::query()->firstOrFail();
        $notifier = app(Notifier::class);

        $this->assertFalse($notifier->customer($job, 'confirmed'));
        $this->assertFalse($notifier->provider($provider, 'offer', [], $job));
        $this->assertFalse($notifier->owner('dispute', [], $job));
        $this->assertFalse($notifier->owner('provider_pending', [], null, $provider));
        Mail::assertNothingSent();
    }

    public function test_leaving_the_demo_removes_the_company_its_profile_and_its_jobs(): void
    {
        $this->post('/demo');
        $this->assertSame(1, Provider::query()->count());
        $this->assertGreaterThan(0, DB::table('service_job_offers')->count());

        $this->post('/demo/verlaten')->assertRedirect();

        $this->assertGuest();
        $this->assertSame(0, Company::query()->where('is_demo', true)->count());
        $this->assertSame(0, Provider::query()->count());
        $this->assertSame(0, ServiceJob::query()->count());
        $this->assertSame(0, DB::table('service_job_offers')->count());
        $this->assertSame(0, DB::table('service_reviews')->count());
        $this->assertSame(0, DB::table('provider_checks')->count());
    }
}
