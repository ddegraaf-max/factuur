<?php

namespace Tests\Feature;

use App\Mail\FachoraMail;
use App\Models\Provider;
use App\Models\ServiceJob;
use App\Services\Fachora\CeidgClient;
use App\Services\Fachora\ConsentPolicy;
use App\Services\Fachora\JobService;
use App\Services\Fachora\ProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Fachora 1.86: de klant kiest zelf tot vijf bedrijven en betaalt een kleine
 * bijdrage per aanvraag. De aanvraag gaat ook naar bedrijven die hun profiel
 * nog niet claimden (een concreet verzoek van een klant, geen reclame); zo'n
 * bedrijf gaat bij het reageren akkoord met de voorwaarden. Daarnaast de
 * regio-import uit CEIDG: alleen actieve bedrijven van minstens een jaar oud,
 * met een e-mailadres, binnen de straal van een startregio.
 */
class FachoraCustomerChoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'brand.active' => 'fachora', 'inertia.testing.ensure_pages_exist' => false,
            'services.anthropic.key' => null, 'services.stripe.secret' => null,
            'services.marketing_stats.emails' => 'eigenaar@example.com',
            'fachora.customer_choice' => true, 'fachora.request_fee' => 10,
        ]);
        Mail::fake();
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([], 200),
            'wl-api.mf.gov.pl/*' => Http::response(['result' => ['subject' => null]], 200),
            '*' => Http::response([], 200),
        ]);
        $this->travelTo(Carbon::parse('2026-10-22 10:00'));
    }

    private function registerProvider(string $name, array $attrs = [], array $categories = ['hydraulik']): Provider
    {
        $slug = Str::slug($name).'-'.Str::lower(Str::random(5));
        $provider = Provider::create($attrs + [
            'legal_name' => $name, 'display_name' => $name, 'slug' => $slug, 'status' => 'unclaimed',
            'email' => $slug.'@example.com', 'city' => 'Szczecin', 'postal_code' => '70-001',
            'lat' => 53.4285, 'lng' => 14.5528, 'radius_km' => 25, 'source' => 'ceidg',
            'optout_token' => bin2hex(random_bytes(32)),
        ]);
        $provider->syncCategories($categories);

        return $provider->refresh();
    }

    private function pendingJob(array $attrs = []): ServiceJob
    {
        return ServiceJob::create($attrs + [
            'reference' => ServiceJob::nextReference(), 'token' => bin2hex(random_bytes(32)), 'status' => 'pending',
            'category' => 'hydraulik', 'title' => 'Myszy w piwnicy', 'description' => 'Mamy myszy w piwnicy, potrzebna pomoc fachowca.',
            'urgency' => 'week', 'language' => 'nl', 'customer_name' => 'Daan de Vries', 'customer_email' => 'daan@example.com',
            'customer_locale' => 'en', 'postal_code' => '70-001', 'city' => 'Szczecin', 'lat' => 53.4285, 'lng' => 14.5528,
        ]);
    }

    public function test_a_request_may_go_to_an_unclaimed_company_but_marketing_may_not(): void
    {
        $p = $this->registerProvider('Hydro Szczecin');
        $this->assertFalse(ConsentPolicy::mayEmail($p));
        $this->assertTrue(ConsentPolicy::mayEmailRequest($p));

        $p->forceFill(['email_blocked_at' => now()])->save();
        $this->assertFalse(ConsentPolicy::mayEmailRequest($p->refresh()));

        $q = $this->registerProvider('Hydro Bez Maila', ['email' => null]);
        $this->assertFalse(ConsentPolicy::mayEmailRequest($q));

        config(['fachora.customer_choice' => false]);
        $this->assertFalse(ConsentPolicy::mayEmailRequest($this->registerProvider('Hydro Uit')));
    }

    public function test_confirming_lets_the_customer_choose_from_nearby_companies(): void
    {
        $near = $this->registerProvider('Hydro Blisko');
        $this->registerProvider('Hydro Daleko', ['city' => 'Warszawa', 'lat' => 52.2297, 'lng' => 21.0122]);
        $this->registerProvider('Hydro Bez Maila', ['email' => null]);
        $this->registerProvider('Hydro Bez VAT', ['vat_status' => 'none']);
        $this->registerProvider('Elektryk', [], ['elektryk']);
        $job = $this->pendingJob();

        $this->post(route('fachora.job.confirm', $job->token))->assertRedirect($job->url());
        $job->refresh();
        $this->assertSame('selecting', $job->status);
        $this->assertSame(0, $job->offers()->count());

        $ids = app(ProviderService::class)->choiceCandidates($job)->pluck('id')->all();
        $this->assertSame([$near->id], $ids);
    }

    public function test_without_a_fee_the_request_goes_straight_to_the_chosen_companies(): void
    {
        // Geen Stripe-sleutel: dan is de aanvraag gratis en gaat hij meteen weg.
        $a = $this->registerProvider('Hydro A');
        $b = $this->registerProvider('Hydro B');
        $c = $this->registerProvider('Hydro C');
        $job = $this->pendingJob();
        app(JobService::class)->confirm($job, request());
        $job->refresh();
        $this->assertSame(0.0, app(JobService::class)->requestFee());

        $this->post(route('fachora.job.select', $job->token), ['providers' => [$a->id, $b->id]])->assertRedirect($job->url());
        $job->refresh();
        $this->assertSame('open', $job->status);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $job->offers()->pluck('provider_id')->all());
        foreach ([$a, $b] as $provider) {
            Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'job' && $m->hasTo($provider->email) && ($m->extra['request'] ?? false) === true);
        }
        Mail::assertNotSent(FachoraMail::class, fn (FachoraMail $m) => $m->hasTo($c->email));
    }

    public function test_more_than_five_or_unknown_companies_are_refused(): void
    {
        $providers = collect(range(1, 6))->map(fn ($i) => $this->registerProvider("Hydro $i"));
        $job = $this->pendingJob();
        app(JobService::class)->confirm($job, request());

        $this->post(route('fachora.job.select', $job->token), ['providers' => $providers->pluck('id')->all()])->assertSessionHasErrors('providers');
        $this->post(route('fachora.job.select', $job->token), ['providers' => [999999]])->assertSessionHasErrors('providers');
        $this->assertSame('selecting', $job->fresh()->status);
    }

    public function test_with_a_fee_the_customer_pays_first_and_then_the_request_goes_out(): void
    {
        config(['services.stripe.secret' => 'sk_test_x']);
        $a = $this->registerProvider('Hydro A');
        $job = $this->pendingJob();
        app(JobService::class)->confirm($job, request());
        $job->refresh();
        $this->assertSame(10.0, $job->request_fee);

        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake([
            'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_test_1', 'url' => 'https://checkout.stripe.com/c/pay/cs_test_1'], 200),
            'api.stripe.com/v1/checkout/sessions/cs_test_1' => Http::response(['id' => 'cs_test_1', 'payment_status' => 'paid', 'metadata' => ['kind' => 'fachora_request', 'job_id' => (string) $job->id]], 200),
            '*' => Http::response([], 200),
        ]);

        // Zonder vinkje (uitvoering meteen) geen betaling.
        $this->post(route('fachora.job.select', $job->token), ['providers' => [$a->id]])->assertSessionHasErrors('express');

        $this->post(route('fachora.job.select', $job->token), ['providers' => [$a->id], 'express' => 1])
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_1');
        $job->refresh();
        $this->assertSame('selecting', $job->status);
        $this->assertSame(0, $job->offers()->count());

        $this->get(route('fachora.job.request.paid', $job->token))->assertRedirect($job->url());
        $job->refresh();
        $this->assertNotNull($job->request_paid_at);
        $this->assertSame('open', $job->status);
        $this->assertSame([$a->id], $job->offers()->pluck('provider_id')->all());
    }

    public function test_an_unclaimed_company_accepts_the_terms_when_it_gives_a_price(): void
    {
        $a = $this->registerProvider('Hydro A');
        $job = $this->pendingJob();
        app(JobService::class)->confirm($job, request());
        app(JobService::class)->selectProviders($job->fresh(), [$a->id]);
        $offer = $job->offers()->firstOrFail();
        $quote = ['price' => 250, 'price_type' => 'fixed', 'available_at' => '2026-10-24 09:00'];

        $this->post(route('fachora.offer.respond', $offer->token), $quote)->assertSessionHasErrors('offer');
        $this->assertSame('sent', $offer->fresh()->status);

        $this->post(route('fachora.offer.respond', $offer->token), $quote + ['accept_terms' => 1])->assertSessionHasNoErrors();
        $a->refresh();
        $this->assertSame('responded', $offer->fresh()->status);
        $this->assertNotNull($a->terms_accepted_at);
        $this->assertSame(config('fachora.terms_version'), $a->terms_version);
        $this->assertTrue(ConsentPolicy::mayEmail($a));
    }

    public function test_the_region_import_keeps_only_old_enough_nearby_companies_with_email(): void
    {
        config(['fachora.ceidg_token' => 'jwt', 'fachora.regions' => ['szczecin' => ['name' => 'Szczecin', 'voivodeship' => 'zachodniopomorskie', 'center' => [53.4285, 14.5528]]],
            'fachora.categories' => ['hydraulik' => ['pl' => 'Hydraulik', 'en' => 'Plumber', 'icon' => 'tap', 'pkd' => ['43.22.Z']]]]);
        Cache::forget(CeidgClient::CURSOR);
        $firm = fn (string $id, string $nip, string $city, string $start) => [
            'id' => $id, 'nazwa' => "Firma $id", 'dataRozpoczecia' => $start, 'status' => 'AKTYWNY',
            'adresDzialalnosci' => ['miasto' => $city, 'kod' => '70-001', 'ulica' => 'ul. Testowa', 'budynek' => '1'],
            'wlasciciel' => ['imie' => 'Jan', 'nazwisko' => "Test $id", 'nip' => $nip],
        ];
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake([
            'dane.biznes.gov.pl/api/ceidg/v3/firmy*' => Http::sequence()
                ->push(['firmy' => [
                    $firm('ok', '5260250274', 'Szczecin', '2019-05-01'),
                    $firm('young', '7010869430', 'Szczecin', '2026-06-01'),
                    $firm('far', '5632397247', 'Warszawa', '2015-01-01'),
                    $firm('nomail', '5651043094', 'Szczecin', '2015-01-01'),
                ], 'count' => 4])
                ->push(['firmy' => [], 'count' => 0]),
            'dane.biznes.gov.pl/api/ceidg/v3/firma/ok' => Http::response(['firma' => [['email' => 'biuro@hydro-ok.pl', 'telefon' => '600100200', 'pkd' => [['kod' => '43.22.Z']]]]], 200),
            'dane.biznes.gov.pl/api/ceidg/v3/firma/nomail' => Http::response(['firma' => [['telefon' => '600100300']]], 200),
            '*' => Http::response([], 200),
        ]);

        $result = app(CeidgClient::class)->importRegionsStep(50, 5);

        $this->assertSame(1, $result['created']);
        $this->assertTrue($result['done']);
        $provider = Provider::query()->where('nip', '5260250274')->firstOrFail();
        $this->assertSame('biuro@hydro-ok.pl', $provider->email);
        $this->assertSame('unclaimed', $provider->status);
        $this->assertNotNull($provider->lat);
        $this->assertSame(1, Provider::query()->count());
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/firma/far') || str_contains($request->url(), '/firma/young'));
    }
}
