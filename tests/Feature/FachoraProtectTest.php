<?php

namespace Tests\Feature;

use App\Mail\FachoraMail;
use App\Models\Provider;
use App\Models\ProviderCheck;
use App\Models\ServiceJob;
use App\Models\User;
use App\Services\Fachora\JobService;
use App\Services\Fachora\ProtectService;
use App\Services\Fachora\ProviderService;
use App\Services\Fachora\VerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Fachora Protect via Stripe Connect: de klant reserveert het afgesproken
 * bedrag bij het platform, na goedkeuring gaat het minus de fee als transfer
 * naar het bedrijf; bij een geschil beslist de eigenaar (uitbetalen,
 * terugbetalen, delen). Stripe is volledig nagebootst.
 */
class FachoraProtectTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    protected function setUp(): void
    {
        parent::setUp();
        // Deze tests gaan over de klassieke route (Fachora biedt zelf aan); de klantkeuze van 1.86 staat in FachoraCustomerChoiceTest.
        config(['fachora.customer_choice' => false, 'fachora.lead_pricing' => false]);
        config([
            'brand.active' => 'fachora', 'inertia.testing.ensure_pages_exist' => false,
            'services.anthropic.key' => null,
            'services.stripe.secret' => 'sk_test_x', 'services.stripe.webhook_secret' => 'whsec_test',
            'fachora.protect' => true,
            'services.marketing_stats.emails' => 'eigenaar@example.com',
        ]);
        Mail::fake();
        $this->fakeStripe();
        $this->travelTo(Carbon::parse('2026-10-16 10:00'));
    }

    private function fakeStripe(array $overrides = []): void
    {
        Http::fake($overrides + [
            'api.stripe.com/v1/accounts/acct_1' => Http::response(['id' => 'acct_1', 'charges_enabled' => true, 'payouts_enabled' => true, 'details_submitted' => true, 'requirements' => ['currently_due' => []]], 200),
            'api.stripe.com/v1/accounts' => Http::response(['id' => 'acct_1'], 200),
            'api.stripe.com/v1/account_links' => Http::response(['url' => 'https://connect.stripe.com/x'], 200),
            'api.stripe.com/v1/checkout/sessions/cs_1' => function () {
                $job = ServiceJob::query()->latest('id')->first();

                return Http::response(['id' => 'cs_1', 'payment_status' => 'paid', 'payment_intent' => 'pi_1', 'metadata' => ['kind' => 'fachora_job', 'job_id' => (string) $job?->id]], 200);
            },
            'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_1', 'url' => 'https://checkout.stripe.com/x'], 200),
            'api.stripe.com/v1/payment_intents/pi_1' => Http::response(['id' => 'pi_1', 'latest_charge' => 'ch_1'], 200),
            'api.stripe.com/v1/transfers' => Http::response(['id' => 'tr_1'], 200),
            'api.stripe.com/v1/refunds' => Http::response(['id' => 're_1'], 200),
            'nominatim.openstreetmap.org/*' => Http::response([], 200),
            'wl-api.mf.gov.pl/*' => Http::response(['result' => ['subject' => null]], 200),
            '*' => Http::response([], 200),
        ]);
    }

    private function asGuest(): void
    {
        Inertia::flushShared();
        $this->flushSession();
        $this->app['auth']->forgetGuards();
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
            'stripe_account_id' => 'acct_1', 'stripe_payouts_enabled' => true, 'stripe_charges_enabled' => true,
        ]);
        $provider->syncCategories($categories);

        return $provider->refresh();
    }

    private function withUser(Provider $provider): User
    {
        $status = $provider->status;
        $provider->forceFill(['claim_email' => $provider->email, 'claim_name' => 'Jan '.$provider->display_name])->save();
        $user = app(ProviderService::class)->confirmClaim($provider, 'tajne-haslo-123');
        $provider->forceFill(['status' => $status])->save();
        $provider->refresh();

        return $user;
    }

    private function owner(): User
    {
        $owner = $this->demoUser();
        $owner->forceFill(['email' => 'eigenaar@example.com'])->save();

        return $owner->fresh();
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

    /** Opdracht waarvoor de klant een vaste prijs van 480 zł koos (status agreed, wacht op de reservering). */
    private function agreedJob(Provider $provider, float $price = 480): ServiceJob
    {
        $jobs = app(JobService::class);
        $job = $this->openJob();
        $jobs->dispatch($job);
        $offer = $job->offers()->firstOrFail();
        $jobs->respond($offer, ['price' => $price, 'price_type' => 'fixed', 'available_at' => '2026-10-17 09:00']);
        $jobs->choose($job->fresh(), $offer->fresh(), ['address_line' => 'ul. Piotrkowska 10/5']);

        return $job->fresh();
    }

    /** Agreed → betaald (zoals de webhook dat doet) → ingepland. */
    private function paidJob(Provider $provider, float $price = 480): ServiceJob
    {
        $job = $this->agreedJob($provider, $price);
        $job->forceFill(['stripe_session_id' => 'cs_1'])->save();
        $session = app(ProtectService::class)->retrieveSession('cs_1');
        app(JobService::class)->markPaid(app(ProtectService::class)->completeSession($session));

        return $job->fresh();
    }

    private function transfers(): Collection
    {
        return Http::recorded(fn (ClientRequest $r) => $r->url() === 'https://api.stripe.com/v1/transfers')->map(fn ($pair) => $pair[0]);
    }

    private function refunds(): Collection
    {
        return Http::recorded(fn (ClientRequest $r) => $r->url() === 'https://api.stripe.com/v1/refunds')->map(fn ($pair) => $pair[0]);
    }

    public function test_a_fixed_price_with_a_protect_ready_company_waits_for_the_reservation_and_pay_goes_to_stripe(): void
    {
        $a = $this->verifiedProvider('Hydro A');
        $this->withUser($a);
        $job = $this->agreedJob($a);

        $this->assertSame('agreed', $job->status);
        $this->assertTrue($job->protect);
        $this->assertNull($job->paid_at);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'agreed' && $m->hasTo('anna@example.com') && str_contains($m->render(), $job->url()));
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'chosen' && $m->hasTo($a->email));

        $this->get(route('fachora.job.show', $job->token))->assertInertia(fn (Assert $p) => $p->where('job.status', 'agreed')->where('job.can_pay', true)->where('protect_enabled', true));

        // Consumentenrecht: zonder het vinkje "uitvoering binnen de bedenktermijn" geen reservering.
        $this->post(route('fachora.job.pay', $job->token))->assertSessionHasErrors(['express']);
        $this->assertNull($job->fresh()->stripe_session_id);
        Http::assertNotSent(fn (ClientRequest $r) => $r->url() === 'https://api.stripe.com/v1/checkout/sessions');

        // Gewoon verzoek: 302 naar Stripe; Inertia-verzoek: 409 met de locatie in de kop.
        $this->post(route('fachora.job.pay', $job->token), ['express' => 1])->assertRedirect('https://checkout.stripe.com/x');
        $this->post(route('fachora.job.pay', $job->token), ['express' => 1], ['X-Inertia' => 'true'])->assertStatus(409)->assertHeader('X-Inertia-Location', 'https://checkout.stripe.com/x');
        $this->assertSame('cs_1', $job->fresh()->stripe_session_id);
        Http::assertSent(fn (ClientRequest $r) => $r->url() === 'https://api.stripe.com/v1/checkout/sessions'
            && (int) $r['line_items[0][price_data][unit_amount]'] === 48000
            && $r['line_items[0][price_data][currency]'] === 'pln'
            && $r['metadata[kind]'] === 'fachora_job'
            && (int) $r['metadata[job_id]'] === $job->id
            && $r['payment_intent_data[transfer_group]'] === $job->reference
            && $r['customer_email'] === 'anna@example.com');

        // De terugkeerpagina haalt de sessie op en legt de betaling vast (zonder webhook).
        $this->get(route('fachora.job.paid', $job->token))->assertRedirect($job->url());
        $job->refresh();
        $this->assertSame('scheduled', $job->status);
        $this->assertNotNull($job->paid_at);
        $this->assertSame('pi_1', $job->stripe_payment_intent);
        $this->assertSame('ch_1', $job->stripe_charge_id);
        $this->assertTrue($job->isPaid());
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'reserved' && $m->audience === 'customer' && $m->hasTo('anna@example.com'));
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'reserved' && $m->audience === 'provider' && $m->hasTo($a->email));

        // Nog eens terugkeren of betalen: niets dubbel.
        $this->get(route('fachora.job.paid', $job->token))->assertRedirect($job->url());
        $this->post(route('fachora.job.pay', $job->token), ['express' => 1])->assertRedirect($job->url())->assertSessionHas('error');
        $this->assertSame(1, Mail::sent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'reserved' && $m->audience === 'customer')->count());
        // Twee keer op "betalen" geklikt vóór de betaling = twee Checkout-sessies; ná de betaling komt er geen bij.
        $this->assertSame(2, Http::recorded(fn (ClientRequest $r) => $r->url() === 'https://api.stripe.com/v1/checkout/sessions')->count());
    }

    public function test_an_hourly_rate_or_a_company_without_stripe_skips_protect(): void
    {
        $jobs = app(JobService::class);
        $a = $this->verifiedProvider('Hydro A');
        $job = $this->openJob();
        $jobs->dispatch($job);
        $offer = $job->offers()->firstOrFail();
        $jobs->respond($offer, ['price' => 150, 'price_type' => 'hourly']);
        $jobs->choose($job->fresh(), $offer->fresh(), ['address_line' => 'x']);
        $this->assertSame('scheduled', $job->fresh()->status);
        $this->assertFalse($job->fresh()->protect);

        $b = $this->verifiedProvider('Hydro B', ['stripe_account_id' => null, 'stripe_payouts_enabled' => false]);
        $job2 = $this->openJob();
        $jobs->dispatch($job2, [$b->id]);
        $offer2 = $job2->offers()->firstOrFail();
        $jobs->respond($offer2, ['price' => 400, 'price_type' => 'fixed']);
        $jobs->choose($job2->fresh(), $offer2->fresh(), ['address_line' => 'x']);
        $this->assertSame('scheduled', $job2->fresh()->status);
        $this->assertFalse($job2->fresh()->protect);
        $this->post(route('fachora.job.pay', $job2->token), ['express' => 1])->assertRedirect($job2->url())->assertSessionHas('error');
    }

    public function test_the_stripe_webhook_marks_the_reservation(): void
    {
        $a = $this->verifiedProvider('Hydro A');
        $job = $this->agreedJob($a);
        $payload = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => [
            'id' => 'cs_1', 'payment_status' => 'paid', 'payment_intent' => 'pi_1', 'metadata' => ['kind' => 'fachora_job', 'job_id' => (string) $job->id],
        ]]]);
        $t = now()->timestamp;
        $signature = 't='.$t.',v1='.hash_hmac('sha256', $t.'.'.$payload, 'whsec_test');

        $this->call('POST', '/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $payload)->assertOk();

        $job->refresh();
        $this->assertSame('scheduled', $job->status);
        $this->assertSame('ch_1', $job->stripe_charge_id);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'reserved' && $m->audience === 'provider');

        // Dezelfde webhook nog eens: geen tweede verwerking.
        $this->call('POST', '/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $payload)->assertOk();
        $this->assertSame(1, Mail::sent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'reserved' && $m->audience === 'provider')->count());
        // Een verkeerde handtekening doet niets.
        $this->call('POST', '/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => 't='.$t.',v1=abc', 'CONTENT_TYPE' => 'application/json'], $payload)->assertStatus(400);
    }

    public function test_acceptance_transfers_the_amount_minus_the_fee_to_the_company(): void
    {
        $a = $this->verifiedProvider('Hydro A');
        $this->withUser($a);
        $job = $this->paidJob($a);
        $jobs = app(JobService::class);
        $jobs->start($job);
        $jobs->done($job, 'Gotowe');

        $this->post(route('fachora.job.accept', $job->token))->assertRedirect($job->url())->assertSessionHas('flash');

        $job->refresh();
        $this->assertSame('accepted', $job->status);
        $this->assertEqualsWithDelta(10.0, $job->fee_percent, 0.001);
        $this->assertEqualsWithDelta(48.0, $job->fee_amount, 0.001);
        $this->assertEqualsWithDelta(432.0, $job->payout_amount, 0.001);
        $this->assertSame('tr_1', $job->stripe_transfer_id);
        $this->assertNotNull($job->payout_at);
        $this->assertSame(1, $a->fresh()->jobs_done);

        $this->assertCount(1, $this->transfers());
        $transfer = $this->transfers()->first();
        $this->assertSame(43200, (int) $transfer['amount']);
        $this->assertSame('pln', $transfer['currency']);
        $this->assertSame('acct_1', $transfer['destination']);
        $this->assertSame('ch_1', $transfer['source_transaction']);
        $this->assertSame($job->reference, $transfer['transfer_group']);
        $this->assertSame('payout', $job->events()->where('kind', 'payout')->firstOrFail()->kind);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'accepted' && $m->audience === 'provider' && $m->hasTo($a->email));
        Mail::assertNotSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'payout_failed');

        // Nog eens uitbetalen (knop van de eigenaar) doet niets dubbel.
        app(ProtectService::class)->payout($job);
        $this->assertCount(1, $this->transfers());
    }

    public function test_in_the_first_months_after_verification_the_intro_fee_applies(): void
    {
        $a = $this->verifiedProvider('Hydro Nieuw', ['verified_at' => now()->subDays(10)]);
        $job = $this->paidJob($a);
        app(JobService::class)->done($job, null);

        app(JobService::class)->accept($job, 'customer');

        $job->refresh();
        $this->assertEqualsWithDelta(5.0, $job->fee_percent, 0.001);
        $this->assertEqualsWithDelta(24.0, $job->fee_amount, 0.001);
        $this->assertEqualsWithDelta(456.0, $job->payout_amount, 0.001);
        $this->assertSame(45600, (int) $this->transfers()->first()['amount']);
    }

    public function test_a_failed_transfer_does_not_block_acceptance_and_alerts_the_owner(): void
    {
        $a = $this->verifiedProvider('Hydro A');
        $job = $this->paidJob($a);
        app(JobService::class)->done($job, null);
        // Eerder geregistreerde stubs winnen: een verse Http-fabriek, nu met een weigerende transfer.
        Http::swap(new HttpFactory(app('events')));
        $this->fakeStripe(['api.stripe.com/v1/transfers' => Http::response(['error' => ['message' => 'Insufficient funds']], 400)]);

        app(JobService::class)->accept($job, 'customer');

        $job->refresh();
        $this->assertSame('accepted', $job->status);
        $this->assertNull($job->stripe_transfer_id);
        $this->assertNull($job->payout_at);
        $this->assertNotNull($job->events()->where('kind', 'payout_failed')->first());
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'payout_failed' && $m->audience === 'owner' && $m->hasTo('eigenaar@example.com'));
    }

    public function test_a_dispute_holds_the_money_until_the_owner_refunds(): void
    {
        $a = $this->verifiedProvider('Hydro A');
        $userA = $this->withUser($a);
        $owner = $this->owner();
        $job = $this->paidJob($a);
        app(JobService::class)->done($job, 'Gotowe');

        $this->post(route('fachora.job.dispute', $job->token), ['reason' => 'Nadal kapie, nic nie naprawiono', 'photos' => [UploadedFile::fake()->image('kapie.jpg')]])
            ->assertRedirect($job->url())->assertSessionHas('flash');
        $job->refresh();
        $this->assertSame('disputed', $job->status);
        $this->assertSame('Nadal kapie, nic nie naprawiono', $job->dispute_reason);
        $this->assertSame(1, $job->files()->where('kind', 'dispute')->count());
        $this->assertNull($job->stripe_transfer_id);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'dispute' && $m->audience === 'provider' && $m->hasTo($a->email));
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'dispute' && $m->audience === 'owner' && $m->hasTo('eigenaar@example.com'));
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'dispute_opened' && $m->hasTo('anna@example.com'));
        // Tijdens een geschil: niet annuleren, wel is de klant klaar met goedkeuren via de pagina (dat beslist de eigenaar).
        $this->post(route('fachora.job.cancel', $job->token))->assertSessionHas('error');

        // Het bedrijf reageert.
        $this->actingAs($userA)->post(route('fachora.jobs.dispute', $job), ['text' => 'Byłem, uszczelka wymieniona, proszę o zdjęcie'])->assertSessionHas('flash');
        $this->assertSame('Byłem, uszczelka wymieniona, proszę o zdjęcie', $job->fresh()->dispute_reply);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'dispute_reply' && $m->audience === 'customer');
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'dispute_reply' && $m->audience === 'owner');
        $this->asGuest();

        // De eigenaar beslist: alles terug naar de klant.
        $this->actingAs($owner)->post(route('fachora.admin.jobs.resolve', $job), ['resolution' => 'refund', 'note' => 'Zdjęcia klienta są jednoznaczne'])->assertSessionHas('flash');
        $job->refresh();
        $this->assertSame('cancelled', $job->status);
        $this->assertSame('refund', $job->dispute_resolution);
        $this->assertSame('owner', $job->cancelled_by);
        $this->assertSame('re_1', $job->stripe_refund_id);
        $this->assertNotNull($job->refunded_at);
        $this->assertNotNull($job->resolved_at);
        $this->assertFalse($job->isPaid());
        $this->assertCount(1, $this->refunds());
        $refund = $this->refunds()->first();
        $this->assertSame('pi_1', $refund['payment_intent']);
        $this->assertFalse(isset($refund['amount']), 'Volledige terugbetaling: zonder bedrag');
        $this->assertCount(0, $this->transfers());
        $this->assertSame(0, $a->fresh()->jobs_done);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'resolved' && $m->audience === 'customer' && ($m->extra['resolution'] ?? null) === 'refund');
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'resolved' && $m->audience === 'provider' && $m->hasTo($a->email));

        // Een tweede beslissing kan niet.
        $this->post(route('fachora.admin.jobs.resolve', $job), ['resolution' => 'payout'])->assertSessionHas('error');
    }

    public function test_the_owner_can_split_the_amount_between_customer_and_company(): void
    {
        $a = $this->verifiedProvider('Hydro A');
        $owner = $this->owner();
        $job = $this->paidJob($a);
        app(JobService::class)->done($job, null);
        app(JobService::class)->dispute($job, 'Połowa roboty niezrobiona');

        $this->actingAs($owner)->post(route('fachora.admin.jobs.resolve', $job), ['resolution' => 'split', 'payout_amount' => '200', 'note' => 'Pół na pół'])->assertSessionHas('flash');

        $job->refresh();
        $this->assertSame('accepted', $job->status);
        $this->assertSame('owner', $job->accepted_by);
        $this->assertSame('split', $job->dispute_resolution);
        $this->assertEqualsWithDelta(20.0, $job->fee_amount, 0.001, '10% over de 200 zł voor het bedrijf');
        $this->assertEqualsWithDelta(180.0, $job->payout_amount, 0.001);
        $this->assertSame('tr_1', $job->stripe_transfer_id);
        $this->assertSame('re_1', $job->stripe_refund_id);
        $this->assertNull($job->refunded_at, 'Een deel terug: de betaling blijft als betaald gelden');
        $this->assertSame(28000, (int) $this->refunds()->first()['amount']);
        $this->assertSame(18000, (int) $this->transfers()->first()['amount']);
        $this->assertSame(1, $a->fresh()->jobs_done);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'resolved' && ($m->extra['resolution'] ?? null) === 'split');
    }

    public function test_the_owner_can_decide_in_favour_of_the_company(): void
    {
        $a = $this->verifiedProvider('Hydro A');
        $owner = $this->owner();
        $job = $this->paidJob($a);
        app(JobService::class)->done($job, null);
        app(JobService::class)->dispute($job, 'Nie podoba mi się');

        $this->actingAs($owner)->post(route('fachora.admin.jobs.resolve', $job), ['resolution' => 'payout'])->assertSessionHas('flash');

        $job->refresh();
        $this->assertSame('accepted', $job->status);
        $this->assertSame('owner', $job->accepted_by);
        $this->assertEqualsWithDelta(432.0, $job->payout_amount, 0.001);
        $this->assertSame(43200, (int) $this->transfers()->first()['amount']);
        $this->assertCount(0, $this->refunds());
    }

    public function test_silence_after_done_counts_as_acceptance_after_the_waiting_period(): void
    {
        $a = $this->verifiedProvider('Hydro A');
        $job = $this->paidJob($a);
        app(JobService::class)->done($job, null);

        $this->travelTo(Carbon::parse('2026-10-20 10:00'));
        $this->assertSame(0, app(JobService::class)->autoAccept(), 'Vier dagen: nog niet');
        $this->travelTo(Carbon::parse('2026-10-21 10:01'));
        $this->assertSame(1, app(JobService::class)->autoAccept());

        $job->refresh();
        $this->assertSame('accepted', $job->status);
        $this->assertSame('auto', $job->accepted_by);
        $this->assertSame('tr_1', $job->stripe_transfer_id);
        $this->assertEqualsWithDelta(432.0, $job->payout_amount, 0.001);
        $this->assertSame(0, app(JobService::class)->autoAccept());
    }

    public function test_cancelling_a_paid_job_refunds_the_customer(): void
    {
        $a = $this->verifiedProvider('Hydro A');
        $job = $this->paidJob($a);

        $this->post(route('fachora.job.cancel', $job->token), ['reason' => 'Zmiana planów'])->assertRedirect($job->url())->assertSessionHas('flash');

        $job->refresh();
        $this->assertSame('cancelled', $job->status);
        $this->assertSame('re_1', $job->stripe_refund_id);
        $this->assertNotNull($job->refunded_at);
        $this->assertSame('pi_1', $this->refunds()->first()['payment_intent']);
        $this->assertCount(0, $this->transfers());
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'cancelled' && $m->audience === 'provider' && $m->hasTo($a->email));
        $this->get(route('fachora.job.show', $job->token))->assertInertia(fn (Assert $p) => $p->where('job.status', 'cancelled')->where('job.refunded', true)->where('job.paid', false));
    }

    public function test_account_updated_from_stripe_completes_the_bank_check(): void
    {
        $a = $this->verifiedProvider('Hydro A', ['status' => 'claimed', 'stripe_payouts_enabled' => false, 'stripe_charges_enabled' => false]);
        $this->assertFalse($a->protectReady());
        app(VerificationService::class)->refreshBankCheck($a);
        $this->assertSame('pending', ProviderCheck::where('provider_id', $a->id)->where('kind', 'bank')->firstOrFail()->status);

        app(ProtectService::class)->handleAccountUpdated(['id' => 'acct_1', 'payouts_enabled' => true, 'charges_enabled' => true, 'details_submitted' => true, 'requirements' => ['currently_due' => []]]);

        $a->refresh();
        $this->assertTrue($a->stripe_payouts_enabled);
        $this->assertTrue($a->protectReady());
        $this->assertSame('passed', ProviderCheck::where('provider_id', $a->id)->where('kind', 'bank')->firstOrFail()->status);
        $this->assertNotNull($a->stripe_checked_at);

        // Onbekend account: niets.
        app(ProtectService::class)->handleAccountUpdated(['id' => 'acct_onbekend', 'payouts_enabled' => false]);
        $this->assertTrue($a->fresh()->stripe_payouts_enabled);

        // Via de echte webhook: Stripe meldt dat er nog iets nodig is.
        $payload = json_encode(['type' => 'account.updated', 'data' => ['object' => ['id' => 'acct_1', 'payouts_enabled' => false, 'charges_enabled' => true, 'requirements' => ['currently_due' => ['individual.id_number'], 'disabled_reason' => 'requirements.past_due']]]]);
        $t = now()->timestamp;
        $signature = 't='.$t.',v1='.hash_hmac('sha256', $t.'.'.$payload, 'whsec_test');
        $this->call('POST', '/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $payload)->assertOk();
        $a->refresh();
        $this->assertFalse($a->stripe_payouts_enabled);
        $this->assertSame(['individual.id_number'], $a->stripe_requirements['currently_due']);
        $check = ProviderCheck::where('provider_id', $a->id)->where('kind', 'bank')->firstOrFail();
        $this->assertSame('pending', $check->status);
        $this->assertStringContainsString('individual.id_number', (string) $check->note);
    }

    public function test_the_company_starts_stripe_onboarding_from_the_verification_page(): void
    {
        $a = $this->verifiedProvider('Hydro A', ['status' => 'claimed', 'stripe_account_id' => null, 'stripe_payouts_enabled' => false]);
        $userA = $this->withUser($a);

        $this->actingAs($userA)->get(route('fachora.verification.stripe'))->assertRedirect('https://connect.stripe.com/x');
        $this->assertSame('acct_1', $a->fresh()->stripe_account_id);
        Http::assertSent(fn (ClientRequest $r) => $r->url() === 'https://api.stripe.com/v1/accounts' && $r['country'] === 'PL' && $r['controller[stripe_dashboard][type]'] === 'express' && (int) $r['metadata[provider_id]'] === $a->id);
        Http::assertSent(fn (ClientRequest $r) => $r->url() === 'https://api.stripe.com/v1/account_links' && $r['account'] === 'acct_1' && $r['type'] === 'account_onboarding');

        $this->get(route('fachora.verification.stripe.return'))->assertRedirect(route('fachora.verification'))->assertSessionHas('flash');
        $this->assertTrue($a->fresh()->stripe_payouts_enabled);
        $this->assertSame('passed', ProviderCheck::where('provider_id', $a->id)->where('kind', 'bank')->firstOrFail()->status);
        $this->get(route('fachora.verification'))->assertOk()->assertInertia(fn (Assert $p) => $p->where('provider.stripe_ready', true)->where('protect_enabled', true));
    }
}
