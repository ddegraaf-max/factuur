<?php

namespace Tests\Feature;

use App\Mail\FachoraMail;
use App\Models\FachoraFile;
use App\Models\Provider;
use App\Models\ServiceJob;
use App\Services\Fachora\JobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * De aanvraag van een klant (/zlecenie/nowe): opslaan, e-mailadres
 * bevestigen via de link in de mail, en pas dan bestaat de opdracht voor
 * bedrijven. Zonder account, met lokveld en limieten per verbinding.
 */
class FachoraIntakeTest extends TestCase
{
    use RefreshDatabase;

    private const IP = '198.51.100.7';

    protected function setUp(): void
    {
        parent::setUp();
        config(['brand.active' => 'fachora', 'inertia.testing.ensure_pages_exist' => false, 'services.anthropic.key' => null, 'services.stripe.secret' => null]);
        Mail::fake();
        Http::fake([
            // Geen geocoder: het vangnet met Poolse steden uit config/fachora.php.
            'nominatim.openstreetmap.org/*' => Http::response([], 200),
            'wl-api.mf.gov.pl/*' => Http::response(['result' => ['subject' => null]], 200),
            '*' => Http::response([], 200),
        ]);
        $this->travelTo(Carbon::parse('2026-10-16 10:00'));
        $this->withServerVariables(['REMOTE_ADDR' => self::IP, 'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Firefox/130.0']);
    }

    /** @return array<string, mixed> */
    private function form(array $override = []): array
    {
        return $override + [
            'category' => 'hydraulik',
            'description' => 'Kapie pod zlewem w kuchni, potrzebna szybka naprawa.',
            'urgency' => 'week',
            'language' => 'pl',
            'customer_name' => 'Anna Kowalska',
            'customer_email' => 'Anna@Example.com',
            'customer_phone' => '600 100 200',
            'postal_code' => '90-001',
            'city' => 'Łódź',
        ];
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

    public function test_store_creates_a_pending_job_with_photos_and_mails_only_the_customer(): void
    {
        $response = $this->post(route('fachora.intake.store'), $this->form(['photos' => [UploadedFile::fake()->image('zlew.jpg', 300, 200)]]));
        $response->assertSessionHasNoErrors();

        $job = ServiceJob::firstOrFail();
        $response->assertRedirect(route('fachora.intake', ['czekaj' => $job->token]));
        $this->assertSame('pending', $job->status);
        $this->assertSame('FA-10001', $job->reference);
        $this->assertSame(64, strlen($job->token));
        $this->assertSame('anna@example.com', $job->customer_email, 'E-mailadres in kleine letters');
        $this->assertSame('pl', $job->customer_locale);
        $this->assertSame('Hydraulik', $job->title, 'Zonder titel: de categorienaam');
        $this->assertSame(self::IP, $job->creator_ip);
        $this->assertSame(1, $job->confirm_mails);
        $this->assertNull($job->confirmed_at);
        // Geocoder niet bereikbaar: het middelpunt van Łódź uit de stadstabel.
        $this->assertEqualsWithDelta(51.7592, $job->lat, 0.001);
        $this->assertEqualsWithDelta(19.456, $job->lng, 0.001);
        $this->assertSame('created', $job->events()->first()?->kind);

        $file = FachoraFile::firstOrFail();
        $this->assertSame(['job', $job->id, 'photo', 'zlew.jpg'], [$file->owner_type, (int) $file->owner_id, $file->kind, $file->filename]);
        $this->assertStringStartsWith('image/', $file->mime_type);
        $this->assertNotEmpty($file->contents());

        Mail::assertSentCount(1);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'confirm' && $m->audience === 'customer' && $m->hasTo('anna@example.com'));
        $html = (new FachoraMail('confirm', 'customer', $job))->render();
        $this->assertStringContainsString($job->confirmUrl(), $html);
        $this->assertStringNotContainsString($job->url().'"', $html, 'De opdrachtpagina zelf staat niet in de bevestigingsmail');

        // Het volgende kenmerk telt door.
        $this->post(route('fachora.intake.store'), $this->form(['title' => 'Cieknący kran']))->assertSessionHasNoErrors();
        $second = ServiceJob::latest('id')->firstOrFail();
        $this->assertSame('FA-10002', $second->reference);
        $this->assertSame('Cieknący kran', $second->title);
    }

    public function test_the_honeypot_field_silently_discards_the_request(): void
    {
        $this->post(route('fachora.intake.store'), $this->form(['website' => 'http://spam.example']))
            ->assertRedirect(route('fachora.intake'));

        $this->assertSame(0, ServiceJob::count());
        Mail::assertNothingSent();
    }

    public function test_validation_rejects_bad_postal_code_short_description_and_unknown_keys(): void
    {
        $this->post(route('fachora.intake.store'), $this->form([
            'postal_code' => '90001',
            'description' => 'krótko',
            'category' => 'kominiarz',
            'language' => 'xx',
            'urgency' => 'yesterday',
            'customer_email' => 'geen-adres',
        ]))->assertSessionHasErrors(['postal_code', 'description', 'category', 'language', 'urgency', 'customer_email']);

        $this->post(route('fachora.intake.store'), $this->form(['city' => '', 'customer_name' => '']))
            ->assertSessionHasErrors(['city', 'customer_name']);

        $this->post(route('fachora.intake.store'), $this->form(['photos' => [UploadedFile::fake()->create('plan.pdf', 10, 'application/pdf')]]))
            ->assertSessionHasErrors(['photos.0']);

        $this->assertSame(0, ServiceJob::count());
        Mail::assertNothingSent();
    }

    public function test_after_five_requests_per_hour_the_connection_is_limited(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->post(route('fachora.intake.store'), $this->form())->assertSessionHasNoErrors();
        }
        $this->post(route('fachora.intake.store'), $this->form())->assertSessionHasErrors(['limiet']);
        $this->assertSame(5, ServiceJob::count());

        // Een andere verbinding mag gewoon.
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])->post(route('fachora.intake.store'), $this->form())->assertSessionHasNoErrors();
        $this->assertSame(6, ServiceJob::count());
    }

    public function test_the_wait_page_masks_the_address_and_resending_is_limited(): void
    {
        $this->post(route('fachora.intake.store'), $this->form())->assertSessionHasNoErrors();
        $job = ServiceJob::firstOrFail();

        $this->get(route('fachora.intake', ['czekaj' => $job->token]))->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Fachora/Intake')
            ->where('wait.reference', 'FA-10001')
            ->where('wait.email', 'an••@example.com')
            ->where('wait.mailFailed', false)
            ->where('wait.resent', false)
            ->where('wait.resendLimit', false));
        // Een onbekend token toont geen wachtblok.
        $this->get(route('fachora.intake', ['czekaj' => str_repeat('a', 64)]))->assertInertia(fn (Assert $p) => $p->where('wait', null));

        // Opnieuw versturen: drie keer, daarna is het genoeg.
        for ($i = 1; $i <= 3; $i++) {
            $this->post(route('fachora.intake.resend'), ['token' => $job->token])
                ->assertRedirect(route('fachora.intake', ['czekaj' => $job->token, 'opnieuw' => 1]));
        }
        $this->assertSame(4, $job->fresh()->confirm_mails);
        $this->post(route('fachora.intake.resend'), ['token' => $job->token])
            ->assertRedirect(route('fachora.intake', ['czekaj' => $job->token, 'opnieuw' => 'limiet']));
        $this->assertSame(4, $job->fresh()->confirm_mails);
        Mail::assertSent(FachoraMail::class, 4);
        $this->get(route('fachora.intake', ['czekaj' => $job->token, 'opnieuw' => 'limiet']))
            ->assertInertia(fn (Assert $p) => $p->where('wait.resendLimit', true)->where('wait.resent', false));

        // Onbekend token: terug naar het formulier, zonder mail.
        $this->post(route('fachora.intake.resend'), ['token' => str_repeat('b', 64)])->assertRedirect(route('fachora.intake'));
        Mail::assertSent(FachoraMail::class, 4);
    }

    public function test_confirming_opens_the_job_and_offers_it_to_a_verified_company_once(): void
    {
        $provider = $this->verifiedProvider('Hydro-Max');
        $this->post(route('fachora.intake.store'), $this->form())->assertSessionHasNoErrors();
        $job = ServiceJob::firstOrFail();

        // Vóór de bevestiging: de opdrachtpagina stuurt door naar de bevestiging; openen alleen bevestigt niets.
        $this->get(route('fachora.job.show', $job->token))->assertRedirect($job->confirmUrl());
        $this->get(route('fachora.job.confirm.show', $job->token))->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Fachora/Confirm')->where('job.reference', 'FA-10001')->where('job.status', 'pending')->where('token', $job->token));
        $this->assertSame('pending', $job->fresh()->status);
        $this->assertSame(0, $job->offers()->count());

        $this->post(route('fachora.job.confirm', $job->token))->assertRedirect($job->url())->assertSessionHas('flash');
        $job->refresh();
        $this->assertSame('open', $job->status);
        $this->assertNotNull($job->confirmed_at);
        $this->assertSame(1, $job->waves);
        $this->assertNotNull($job->offered_at);
        $this->assertTrue($job->offers_deadline_at->equalTo(now()->addHours(48)));

        $offer = $job->offers()->firstOrFail();
        $this->assertSame($provider->id, $offer->provider_id);
        $this->assertSame('sent', $offer->status);
        $this->assertSame(1, $offer->wave);
        $this->assertNotNull($offer->sent_at);
        $this->assertSame(64, strlen($offer->token));

        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'job' && $m->audience === 'provider' && $m->hasTo($provider->email) && $m->offer?->id === $offer->id);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'confirmed' && $m->audience === 'customer' && $m->hasTo('anna@example.com'));
        $this->assertStringContainsString($offer->url(), (new FachoraMail('job', 'provider', $job, $offer, $provider))->render());

        // Nog eens bevestigen (dubbelklik, scanner): geen tweede ronde, geen tweede mail.
        $this->post(route('fachora.job.confirm', $job->token))->assertRedirect($job->url());
        $this->get(route('fachora.job.confirm.show', $job->token))->assertRedirect($job->url());
        $this->assertSame(1, $job->offers()->count());
        $this->assertSame(1, $job->fresh()->waves);
        $this->assertCount(1, Mail::sent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'job'));
        $this->assertCount(1, Mail::sent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'confirmed'));

        $this->get(route('fachora.job.show', $job->token))->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Fachora/Job')
            ->where('job.status', 'open')
            ->where('job.customer_email', 'anna@example.com')
            ->where('job.can_cancel', true)
            ->where('job.can_pay', false)
            ->has('offers', 1)
            ->where('offers.0.status', 'sent')
            ->where('offers.0.provider.name', 'Hydro-Max'));

        $this->get(route('fachora.job.show', str_repeat('c', 64)))->assertNotFound();
        $this->post(route('fachora.job.confirm', str_repeat('c', 64)))->assertNotFound();
    }

    public function test_without_a_verified_company_nearby_the_owner_hears_it(): void
    {
        config(['services.marketing_stats.emails' => 'eigenaar@example.com']);
        // Wel een bedrijf, maar in Warschau en met een werkstraal van 25 km.
        $this->verifiedProvider('Daleko', ['city' => 'Warszawa', 'lat' => 52.2297, 'lng' => 21.0122]);

        $this->post(route('fachora.intake.store'), $this->form())->assertSessionHasNoErrors();
        $job = ServiceJob::firstOrFail();
        $this->post(route('fachora.job.confirm', $job->token))->assertRedirect($job->url());

        $job->refresh();
        $this->assertSame('no_match', $job->status);
        $this->assertSame(0, $job->offers()->count());
        $this->assertNotNull($job->owner_notified_at);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'no_match' && $m->audience === 'owner' && $m->hasTo('eigenaar@example.com'));
        Mail::assertNotSent(FachoraMail::class, fn (FachoraMail $m) => $m->audience === 'provider');
    }

    public function test_unconfirmed_requests_are_purged_after_the_confirm_period_with_their_files(): void
    {
        $this->post(route('fachora.intake.store'), $this->form(['photos' => [UploadedFile::fake()->image('oud.jpg')]]))->assertSessionHasNoErrors();
        $old = ServiceJob::latest('id')->firstOrFail();

        $this->travelTo(Carbon::parse('2026-10-20 10:00'));
        $this->post(route('fachora.intake.store'), $this->form())->assertSessionHasNoErrors();
        $recent = ServiceJob::latest('id')->firstOrFail();
        $this->post(route('fachora.intake.store'), $this->form())->assertSessionHasNoErrors();
        $confirmed = ServiceJob::latest('id')->firstOrFail();
        $this->post(route('fachora.job.confirm', $confirmed->token))->assertRedirect();

        $this->travelTo(Carbon::parse('2026-10-24 10:00'));
        $this->assertSame(1, app(JobService::class)->purgeUnconfirmed());

        $this->assertNull(ServiceJob::find($old->id));
        $this->assertSame(0, FachoraFile::where('owner_type', 'job')->where('owner_id', $old->id)->count());
        $this->assertNotNull(ServiceJob::find($recent->id), 'Jonger dan zeven dagen: blijft');
        $this->assertNotNull(ServiceJob::find($confirmed->id), 'Bevestigd: blijft');
        $this->get(route('fachora.intake', ['czekaj' => $old->token]))->assertInertia(fn (Assert $p) => $p->where('wait', null));
    }
}
