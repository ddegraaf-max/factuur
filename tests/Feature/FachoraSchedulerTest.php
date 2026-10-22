<?php

namespace Tests\Feature;

use App\Mail\FachoraMail;
use App\Models\Provider;
use App\Models\ServiceJob;
use App\Services\Fachora\JobService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * De planner van Fachora (fachora:tick, elk uur): één herinnering aan
 * bedrijven die niet reageerden, een tweede ronde na de reactietermijn,
 * daarna "geen bedrijf", en stilzwijgende goedkeuring. Alleen onder het
 * merk fachora.
 */
class FachoraSchedulerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Deze tests gaan over de klassieke route (Fachora biedt zelf aan); de klantkeuze van 1.86 staat in FachoraCustomerChoiceTest.
        config(['fachora.customer_choice' => false]);
        config([
            'brand.active' => 'fachora', 'inertia.testing.ensure_pages_exist' => false,
            'services.anthropic.key' => null, 'services.stripe.secret' => null,
            'services.marketing_stats.emails' => 'eigenaar@example.com',
        ]);
        Mail::fake();
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([], 200),
            'wl-api.mf.gov.pl/*' => Http::response(['result' => ['subject' => null]], 200),
            '*' => Http::response([], 200),
        ]);
        $this->travelTo(Carbon::parse('2026-10-16 10:00'));
    }

    private function verifiedProvider(string $name, array $attrs = [], array $categories = ['hydraulik']): Provider
    {
        $slug = Str::slug($name).'-'.Str::lower(Str::random(5));
        $provider = Provider::create($attrs + [
            'legal_name' => $name, 'display_name' => $name, 'slug' => $slug, 'status' => 'verified',
            'email' => $slug.'@example.com', 'city' => 'Łódź', 'postal_code' => '90-001',
            'lat' => 51.7592, 'lng' => 19.4560, 'radius_km' => 25, 'languages' => ['pl' => 'native', 'en' => 'b2'],
            'claimed_at' => now()->subMonth(), 'consent_at' => now()->subMonth(), 'consent_source' => 'claim',
            'verified_at' => now()->subDays(100), 'optout_token' => bin2hex(random_bytes(32)), 'protect_opt_in' => true,
        ]);
        $provider->syncCategories($categories);

        return $provider->refresh();
    }

    private function openJob(array $attrs = []): ServiceJob
    {
        return ServiceJob::create($attrs + [
            'reference' => ServiceJob::nextReference(), 'token' => bin2hex(random_bytes(32)), 'status' => 'open', 'confirmed_at' => now(),
            'category' => 'hydraulik', 'title' => 'Wyciek pod zlewem', 'description' => 'Kapie pod zlewem w kuchni, potrzebna szybka naprawa.',
            'urgency' => 'week', 'language' => 'pl', 'customer_name' => 'Anna Kowalska', 'customer_email' => 'anna@example.com',
            'customer_locale' => 'pl', 'postal_code' => '90-001', 'city' => 'Łódź', 'lat' => 51.7592, 'lng' => 19.4560,
        ]);
    }

    public function test_one_reminder_goes_to_a_company_that_has_not_responded_after_a_day(): void
    {
        $a = $this->verifiedProvider('Hydro A');
        $b = $this->verifiedProvider('Hydro B');
        $jobs = app(JobService::class);
        $job = $this->openJob();
        $jobs->dispatch($job);
        $offers = $job->offers()->get()->keyBy('provider_id');
        $jobs->respond($offers[$b->id], ['price' => 300, 'price_type' => 'fixed']);

        // Na 23 uur nog niets; een opdracht van later op de dag telt ook nog niet mee.
        $this->travelTo(Carbon::parse('2026-10-17 09:00'));
        $this->assertSame(0, $jobs->remindOffers());
        $later = $this->openJob();
        $jobs->dispatch($later);

        $this->travelTo(Carbon::parse('2026-10-17 11:00'));
        $this->artisan('fachora:tick')->expectsOutputToContain('Herinneringen: 1')->assertExitCode(0);

        $this->assertNotNull($offers[$a->id]->fresh()->reminded_at);
        $this->assertNull($offers[$b->id]->fresh()->reminded_at, 'Wie al een prijs gaf, krijgt geen herinnering');
        $this->assertNull($later->offers()->firstOrFail()->reminded_at);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'reminder' && $m->hasTo($a->email) && $m->offer?->id === $offers[$a->id]->id && str_contains($m->render(), $offers[$a->id]->url()));
        $this->assertSame(1, Mail::sent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'reminder')->count());

        // Nooit twee keer; ook niet als de opdracht inmiddels dicht is.
        $this->travelTo(Carbon::parse('2026-10-17 15:00'));
        $this->assertSame(0, $jobs->remindOffers());
        $jobs->cancel($later, 'customer');
        $this->travelTo(Carbon::parse('2026-10-18 15:00'));
        $this->assertSame(0, $jobs->remindOffers());
        $this->assertSame(1, Mail::sent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'reminder')->count());
    }

    public function test_after_the_deadline_without_a_price_a_second_round_goes_out_and_then_the_job_is_no_match(): void
    {
        config(['fachora.max_offers' => 1]);
        $a = $this->verifiedProvider('Hydro A', ['rating_avg' => 5.0]);
        $b = $this->verifiedProvider('Hydro B', ['rating_avg' => 4.0]);
        $jobs = app(JobService::class);
        $job = $this->openJob();
        $jobs->dispatch($job);
        $this->assertSame([$a->id], $job->offers()->pluck('provider_id')->all());

        $this->travelTo(Carbon::parse('2026-10-18 09:00'));
        $this->assertSame(0, $jobs->expireOffers(), 'Binnen 48 uur gebeurt er niets');

        $this->travelTo(Carbon::parse('2026-10-18 10:00'));
        $this->assertSame(1, $jobs->expireOffers());
        $job->refresh();
        $this->assertSame('open', $job->status);
        $this->assertSame(2, $job->waves);
        $this->assertSame(['expired', 'sent'], $job->offers()->pluck('status')->all());
        $this->assertSame([$a->id, $b->id], $job->offers()->pluck('provider_id')->all());
        $this->assertTrue($job->offers_deadline_at->equalTo(Carbon::parse('2026-10-20 10:00')));
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'job' && $m->hasTo($b->email));
        Mail::assertNotSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'no_match');

        $this->travelTo(Carbon::parse('2026-10-20 10:00'));
        $this->artisan('fachora:tick')->expectsOutputToContain('Verlopen rondes: 1')->assertExitCode(0);
        $job->refresh();
        $this->assertSame('no_match', $job->status);
        $this->assertNull($job->offers_deadline_at);
        $this->assertSame(['expired', 'expired'], $job->offers()->pluck('status')->all());
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'no_match' && $m->audience === 'owner' && $m->hasTo('eigenaar@example.com'));
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'no_match' && $m->audience === 'customer' && $m->hasTo('anna@example.com'));
        $this->assertSame(0, $jobs->expireOffers());
    }

    public function test_after_the_deadline_with_a_price_the_customer_keeps_choosing(): void
    {
        $a = $this->verifiedProvider('Hydro A');
        $b = $this->verifiedProvider('Hydro B');
        $jobs = app(JobService::class);
        $job = $this->openJob();
        $jobs->dispatch($job);
        $offers = $job->offers()->get()->keyBy('provider_id');
        $jobs->respond($offers[$a->id], ['price' => 300, 'price_type' => 'fixed']);

        $this->travelTo(Carbon::parse('2026-10-18 10:00'));
        // De teller telt elke afgehandelde opdracht, ook een met prijs (termijn gewist, geen nieuwe ronde).
        $this->assertSame(1, $jobs->expireOffers());

        $job->refresh();
        $this->assertSame('quoted', $job->status);
        $this->assertNull($job->offers_deadline_at);
        $this->assertSame(1, $job->waves);
        $this->assertSame('responded', $offers[$a->id]->fresh()->status);
        $this->assertSame('expired', $offers[$b->id]->fresh()->status, 'Wie niet reageerde, is te laat');
        Mail::assertNotSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'no_match');

        $jobs->choose($job, $offers[$a->id]->fresh(), ['address_line' => 'ul. Prosta 1']);
        $this->assertSame('scheduled', $job->fresh()->status);
        // De verlopen aanbieding van B staat niet meer op de klantpagina (geen prijs).
        $this->get(route('fachora.job.show', $job->token))->assertInertia(fn ($p) => $p->has('offers', 1)->where('offers.0.status', 'chosen'));
    }

    public function test_silence_after_done_is_acceptance_after_the_waiting_period(): void
    {
        $a = $this->verifiedProvider('Hydro A');
        $job = $this->openJob(['status' => 'done', 'provider_id' => $a->id, 'agreed_amount' => 300, 'agreed_type' => 'fixed', 'done_at' => now()]);
        $fresh = $this->openJob(['status' => 'done', 'provider_id' => $a->id, 'agreed_amount' => 300, 'agreed_type' => 'fixed', 'done_at' => Carbon::parse('2026-10-19 10:00')]);

        $this->travelTo(Carbon::parse('2026-10-21 09:59'));
        $this->assertSame(0, app(JobService::class)->autoAccept());
        $this->travelTo(Carbon::parse('2026-10-21 10:00'));
        $this->artisan('fachora:tick')->expectsOutputToContain('Automatisch goedgekeurd: 1')->assertExitCode(0);

        $job->refresh();
        $this->assertSame('accepted', $job->status);
        $this->assertSame('auto', $job->accepted_by);
        $this->assertEqualsWithDelta(30.0, $job->fee_amount, 0.001);
        $this->assertSame('done', $fresh->fresh()->status);
        $this->assertSame(1, $a->fresh()->jobs_done);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'accepted' && $m->audience === 'customer' && $m->hasTo('anna@example.com'));
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'accepted' && $m->audience === 'provider' && $m->hasTo($a->email));
    }

    public function test_under_another_brand_the_tick_does_nothing(): void
    {
        $a = $this->verifiedProvider('Hydro A');
        $job = $this->openJob();
        app(JobService::class)->dispatch($job);
        $this->travelTo(Carbon::parse('2026-10-18 10:00'));

        config(['brand.active' => 'lopra_pl']);
        $this->artisan('fachora:tick')->expectsOutputToContain('Niet het merk fachora')->assertExitCode(0);
        $this->assertNull($job->offers()->firstOrFail()->reminded_at);
        $this->assertSame('open', $job->fresh()->status);
        Mail::assertNotSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'reminder');

        // Terug onder fachora: nu wel.
        config(['brand.active' => 'fachora']);
        $this->artisan('fachora:tick')->expectsOutputToContain('Herinneringen: 1')->assertExitCode(0);
        $this->assertNotNull($job->offers()->firstOrFail()->reminded_at);
    }

    public function test_tick_and_weekly_checks_are_scheduled(): void
    {
        $commands = collect(app(Schedule::class)->events())->map(fn ($e) => (string) ($e->command ?? ''));

        $this->assertCount(1, $commands->filter(fn ($c) => str_contains($c, 'fachora:tick')), 'fachora:tick hoort elk uur te draaien');
        $this->assertCount(1, $commands->filter(fn ($c) => str_contains($c, 'fachora:checks')), 'fachora:checks hoort wekelijks te draaien');

        $tick = collect(app(Schedule::class)->events())->first(fn ($e) => str_contains((string) ($e->command ?? ''), 'fachora:tick'));
        $this->assertSame('20 * * * *', $tick->expression);
        $checks = collect(app(Schedule::class)->events())->first(fn ($e) => str_contains((string) ($e->command ?? ''), 'fachora:checks'));
        $this->assertSame('10 6 * * 1', $checks->expression);
        $this->assertSame('Europe/Warsaw', $checks->timezone);
    }
}
