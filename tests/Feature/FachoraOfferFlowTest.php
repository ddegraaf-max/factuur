<?php

namespace Tests\Feature;

use App\Mail\FachoraMail;
use App\Models\Customer;
use App\Models\FachoraFile;
use App\Models\Invoice;
use App\Models\Provider;
use App\Models\ServiceJob;
use App\Models\ServiceJobExtra;
use App\Models\User;
use App\Services\Fachora\JobService;
use App\Services\Fachora\ProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * De opdrachtcyclus zonder Protect: aanbieden, prijs geven, kiezen,
 * uitvoeren met meerwerk, klaarmelden, goedkeuren, conceptfactuur in de
 * administratie van het bedrijf, beoordelen. Plus de randen: gesloten
 * aanbiedingen, kiezen zonder prijs, annuleren.
 */
class FachoraOfferFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Deze tests gaan over de klassieke route (Fachora biedt zelf aan); de klantkeuze van 1.86 staat in FachoraCustomerChoiceTest.
        config(['fachora.customer_choice' => false, 'fachora.lead_pricing' => false]);
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
        ]);
        $provider->syncCategories($categories);

        return $provider->refresh();
    }

    /** Inlog en eigen administratie voor het bedrijf, via de echte claimstap. */
    private function withUser(Provider $provider): User
    {
        $status = $provider->status;
        $provider->forceFill(['claim_email' => $provider->email, 'claim_name' => 'Jan '.$provider->display_name])->save();
        $user = app(ProviderService::class)->confirmClaim($provider, 'tajne-haslo-123');
        $provider->forceFill(['status' => $status])->save();
        $provider->refresh();

        return $user;
    }

    private function openJob(array $attrs = []): ServiceJob
    {
        return ServiceJob::create($attrs + [
            'reference' => ServiceJob::nextReference(), 'token' => bin2hex(random_bytes(32)), 'status' => 'pending',
            'category' => 'hydraulik', 'title' => 'Wyciek pod zlewem', 'description' => 'Kapie pod zlewem w kuchni, potrzebna szybka naprawa.',
            'urgency' => 'week', 'language' => 'pl', 'customer_name' => 'Anna Kowalska', 'customer_email' => 'anna@example.com',
            'customer_locale' => 'pl', 'postal_code' => '90-001', 'city' => 'Łódź', 'lat' => 51.7592, 'lng' => 19.4560,
        ]);
    }

    /** @return array<string, mixed> */
    private function quote(float $price, string $type = 'fixed'): array
    {
        return ['price' => $price, 'price_type' => $type, 'available_at' => '2026-10-17 09:00', 'available_note' => 'rano', 'remarks' => 'Z dojazdem i materiałem'];
    }

    public function test_from_confirmation_to_the_chosen_company(): void
    {
        $a = $this->verifiedProvider('Hydro A');
        $b = $this->verifiedProvider('Hydro B');
        $c = $this->verifiedProvider('Hydro C');
        $userB = $this->withUser($b);
        $job = $this->openJob();

        $this->post(route('fachora.job.confirm', $job->token))->assertRedirect($job->url());
        $job->refresh();
        $this->assertSame('open', $job->status);
        $offers = $job->offers()->get()->keyBy('provider_id');
        $this->assertCount(3, $offers);
        foreach ([$a, $b, $c] as $provider) {
            $offer = $offers[$provider->id];
            Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'job' && $m->hasTo($provider->email) && $m->offer?->token === $offer->token);
        }
        $this->assertSame(3, Mail::sent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'job')->count());

        // A opent de link: geregistreerd. Een onbekend token toont een nette melding.
        $offerA = $offers[$a->id];
        $this->get(route('fachora.offer.show', $offerA->token))->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Fachora/Offer')->where('valid', true)->where('can_respond', true)->where('protect', false)
            ->where('job.reference', $job->reference)->where('provider.name', 'Hydro A')
            ->missing('job.customer_name')->missing('job.customer_email')->missing('job.address_line'));
        $this->assertNotNull($offerA->fresh()->opened_at);
        $this->get(route('fachora.offer.show', str_repeat('d', 64)))->assertOk()->assertInertia(fn (Assert $p) => $p->where('valid', false));

        // A geeft een vaste prijs; de klant hoort het.
        $this->post(route('fachora.offer.respond', $offerA->token), $this->quote(480))->assertRedirect()->assertSessionHasNoErrors();
        $offerA->refresh();
        $job->refresh();
        $this->assertSame('responded', $offerA->status);
        $this->assertEqualsWithDelta(480.0, $offerA->price, 0.001);
        $this->assertSame('fixed', $offerA->price_type);
        $this->assertSame('2026-10-17 09:00', $offerA->available_at->format('Y-m-d H:i'));
        $this->assertSame('quoted', $job->status);
        $this->assertSame(0, $a->fresh()->response_minutes);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'offer' && $m->audience === 'customer' && $m->hasTo('anna@example.com') && ($m->extra['offer_id'] ?? null) === $offerA->id);

        // Een prijs in de toekomst corrigeren is mogelijk; geen tweede klantmail.
        $this->post(route('fachora.offer.respond', $offerA->token), $this->quote(450))->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta(450.0, $offerA->fresh()->price, 0.001);
        $this->assertSame(1, Mail::sent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'offer')->count());

        // Validatie: zonder prijs of met een moment in het verleden.
        $this->post(route('fachora.offer.respond', $offers[$c->id]->token), ['price_type' => 'fixed'])->assertSessionHasErrors(['price']);
        $this->post(route('fachora.offer.respond', $offers[$c->id]->token), ['available_at' => '2026-10-15 09:00'] + $this->quote(520))->assertSessionHasErrors(['available_at']);
        $this->post(route('fachora.offer.respond', $offers[$c->id]->token), $this->quote(520))->assertSessionHasNoErrors();

        // B zegt af via het panel.
        $this->actingAs($userB)->post(route('fachora.offers.decline', $offers[$b->id]), ['reason' => 'Brak terminu'])->assertRedirect(route('fachora.dashboard'));
        $this->assertSame('declined', $offers[$b->id]->fresh()->status);
        $this->assertSame('Brak terminu', $offers[$b->id]->fresh()->decline_reason);
        $this->assertSame(1, $job->fresh()->waves, 'Niet iedereen zegde af: geen nieuwe ronde');
        // Het panel van B toont geen aanbieding van een ander bedrijf.
        $this->get(route('fachora.offers.show', $offerA))->assertForbidden();
        $this->asGuest();

        // De klant ziet de prijzen en kiest A, met adres en telefoonnummer.
        $this->get(route('fachora.job.show', $job->token))->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Fachora/Job')->where('job.status', 'quoted')->has('offers', 3)
            ->where('offers.0.price', fn ($v) => (float) $v === 450.0)->where('offers.0.provider.name', 'Hydro A')->where('offers.1.status', 'declined'));
        $this->post(route('fachora.job.choose', [$job->token, $offerA->id]), ['address_line' => 'ul. Piotrkowska 10/5', 'customer_phone' => '600 100 200', 'note' => 'Domofon 5'])
            ->assertRedirect($job->url())->assertSessionHas('flash');

        $job->refresh();
        $this->assertSame('scheduled', $job->status, 'Zonder Protect gaat de afspraak meteen door');
        $this->assertFalse($job->protect);
        $this->assertSame($a->id, $job->provider_id);
        $this->assertSame($offerA->id, $job->chosen_offer_id);
        $this->assertEqualsWithDelta(450.0, $job->agreed_amount, 0.001);
        $this->assertSame('fixed', $job->agreed_type);
        $this->assertSame('ul. Piotrkowska 10/5', $job->address_line);
        $this->assertSame('600 100 200', $job->customer_phone);
        $this->assertSame('2026-10-17 09:00', $job->scheduled_at->format('Y-m-d H:i'));
        $this->assertSame(12, $job->warranty_months);
        $this->assertSame('chosen', $offerA->fresh()->status);
        $this->assertSame('rejected', $offers[$c->id]->fresh()->status);
        $this->assertSame('declined', $offers[$b->id]->fresh()->status);

        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'chosen' && $m->hasTo($a->email) && str_contains($m->render(), 'ul. Piotrkowska 10/5') && str_contains($m->render(), 'Domofon 5'));
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'rejected' && $m->hasTo($c->email));
        Mail::assertNotSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'rejected' && $m->hasTo($b->email));
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'agreed' && $m->hasTo('anna@example.com'));

        // De aanbieding van C is dicht: geen prijs meer, en de pagina zegt dat.
        $this->post(route('fachora.offer.respond', $offers[$c->id]->token), $this->quote(400))->assertSessionHasErrors(['offer']);
        $this->get(route('fachora.offer.show', $offers[$c->id]->token))->assertInertia(fn (Assert $p) => $p->where('can_respond', false)->where('offer.status', 'rejected'));
        // De klant kan niet nog eens kiezen.
        $this->post(route('fachora.job.choose', [$job->token, $offers[$c->id]->id]), ['address_line' => 'x'])->assertRedirect($job->url())->assertSessionHas('error');
    }

    public function test_execution_with_extra_work_done_acceptance_draft_invoice_and_review(): void
    {
        $a = $this->verifiedProvider('Hydro A');
        $userA = $this->withUser($a);
        $jobs = app(JobService::class);
        $job = $this->openJob(['status' => 'open', 'confirmed_at' => now()]);
        $jobs->dispatch($job);
        $offer = $job->offers()->firstOrFail();
        $jobs->respond($offer, $this->quote(480));
        $jobs->choose($job->fresh(), $offer->fresh(), ['address_line' => 'ul. Piotrkowska 10/5']);
        $job->refresh();
        $this->assertSame('scheduled', $job->status);

        // Het bedrijf start, stelt meerwerk voor.
        $this->actingAs($userA);
        $this->get(route('fachora.dashboard'))->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Fachora/Provider/Dashboard')->where('provider.name', 'Hydro A')->has('active', 1)->where('active.0.reference', $job->reference)->where('active.0.address_line', 'ul. Piotrkowska 10/5'));
        $this->get(route('fachora.jobs.show', $job))->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Fachora/Provider/Job')->where('job.customer_name', 'Anna Kowalska')->where('offer.price', fn ($v) => (float) $v === 480.0)->where('invoice_url', null));
        $this->post(route('fachora.jobs.start', $job))->assertRedirect()->assertSessionHas('flash');
        $this->assertSame('in_progress', $job->fresh()->status);
        $this->post(route('fachora.jobs.start', $job))->assertSessionHas('error', fn ($e) => is_string($e) && $e !== '');

        $this->post(route('fachora.jobs.extra', $job), ['description' => 'Wymiana syfonu', 'amount' => '120'])->assertSessionHas('flash');
        $extra = ServiceJobExtra::firstOrFail();
        $this->assertSame('proposed', $extra->status);
        $this->assertEqualsWithDelta(120.0, $extra->amount, 0.001);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'extra' && $m->hasTo('anna@example.com') && ($m->extra['extra_id'] ?? null) === $extra->id);
        $this->asGuest();

        // De klant keurt het meerwerk goed; het bedrijf hoort het. Nog eens beslissen kan niet.
        $this->post(route('fachora.job.extra', [$job->token, $extra->id]), ['decision' => 'approve'])->assertRedirect($job->url())->assertSessionHas('flash');
        $this->assertSame('approved', $extra->fresh()->status);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'extra_decided' && $m->hasTo($a->email) && ($m->extra['approved'] ?? null) === true);
        $this->post(route('fachora.job.extra', [$job->token, $extra->id]), ['decision' => 'decline'])->assertSessionHas('error');
        $this->assertEqualsWithDelta(600.0, $job->fresh()->totalAmount(), 0.001);
        // Goedkeuren vóór "klaar" kan al; een geschil ook — maar de klant wacht netjes.

        // Klaar, met foto.
        $this->actingAs($userA)->post(route('fachora.jobs.done', $job), ['note' => 'Gotowe, sprawdzone', 'photos' => [UploadedFile::fake()->image('po.jpg')]])->assertSessionHas('flash');
        $job->refresh();
        $this->assertSame('done', $job->status);
        $this->assertSame('Gotowe, sprawdzone', $job->done_note);
        $this->assertSame(1, FachoraFile::where('owner_type', 'job')->where('owner_id', $job->id)->where('kind', 'done')->count());
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'done' && $m->hasTo('anna@example.com'));
        $this->asGuest();

        // De klant keurt goed: afgerond, fee berekend, conceptfactuur in de administratie van A.
        $this->get(route('fachora.job.show', $job->token))->assertInertia(fn (Assert $p) => $p->where('job.status', 'done')->where('job.auto_accept_on', fn ($v) => is_string($v) && $v !== '')->where('job.can_cancel', false)->has('files', 1));
        $this->post(route('fachora.job.accept', $job->token))->assertRedirect($job->url())->assertSessionHas('flash');
        $job->refresh();
        $this->assertSame('accepted', $job->status);
        $this->assertSame('customer', $job->accepted_by);
        $this->assertEqualsWithDelta(10.0, $job->fee_percent, 0.001, 'Honderd dagen na verificatie geldt het gewone tarief');
        $this->assertEqualsWithDelta(60.0, $job->fee_amount, 0.001, '10% over 480 + 120');
        $this->assertNull($job->payout_amount, 'Zonder Protect is er niets uit te betalen');
        $this->assertSame(1, $a->fresh()->jobs_done);
        // De conceptfactuur heeft een eigen test (test_acceptance_puts_a_draft_invoice_in_the_administration_of_the_company).
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'accepted' && $m->audience === 'provider' && $m->hasTo($a->email));
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'accepted' && $m->audience === 'customer' && $m->hasTo('anna@example.com'));

        // Beoordeling: één keer, en het bedrijf mag antwoorden.
        $this->post(route('fachora.job.review', $job->token), ['rating' => 5, 'text' => 'Szybko i czysto'])->assertRedirect($job->url())->assertSessionHas('flash');
        $a->refresh();
        $this->assertEqualsWithDelta(5.0, $a->rating_avg, 0.001);
        $this->assertSame(1, $a->rating_count);
        $this->assertNotNull($job->fresh()->reviewed_at);
        $this->post(route('fachora.job.review', $job->token), ['rating' => 1])->assertSessionHas('error');
        $this->post(route('fachora.job.review', $job->token), ['rating' => 9])->assertSessionHasErrors(['rating']);
        $review = $job->review()->firstOrFail();
        $this->actingAs($userA)->post(route('fachora.reviews.reply', $review), ['text' => 'Dziękujemy!'])->assertSessionHas('flash');
        $this->assertSame('Dziękujemy!', $review->fresh()->reply);
        $this->get(route('fachora.jobs.show', $job))->assertInertia(fn (Assert $p) => $p->where('job.review.rating', 5)->where('job.status', 'accepted'));
        $this->get(route('fachora.dashboard'))->assertInertia(fn (Assert $p) => $p->has('active', 0)->has('closed', 1)->has('reviews', 1)->where('reviews.0.reply', 'Dziękujemy!'));
    }

    /** Opdracht van Hydro A die klaar is gemeld, met goedgekeurd (120) en afgewezen (999) meerwerk. @return array{0: Provider, 1: User, 2: ServiceJob} */
    private function doneJobWithExtras(): array
    {
        $a = $this->verifiedProvider('Hydro A');
        $userA = $this->withUser($a);
        $jobs = app(JobService::class);
        $job = $this->openJob(['status' => 'open', 'confirmed_at' => now()]);
        $jobs->dispatch($job);
        $offer = $job->offers()->firstOrFail();
        $jobs->respond($offer, $this->quote(480));
        $jobs->choose($job->fresh(), $offer->fresh(), ['address_line' => 'ul. Piotrkowska 10/5']);
        $job->refresh();
        $jobs->decideExtra($jobs->proposeExtra($job, 'Wymiana syfonu', 120), true);
        $jobs->decideExtra($jobs->proposeExtra($job, 'Nie trzeba', 999), false);
        $jobs->done($job, null);

        return [$a->fresh(), $userA, $job->fresh()];
    }

    /**
     * Na goedkeuring hoort er een conceptfactuur (incl. btw, KSeF-klaar) in de
     * administratie van het bedrijf te staan. Hier bestaat de klant al in die
     * administratie; het aanmaken van een nieuwe klantkaart heeft een eigen test.
     */
    public function test_acceptance_puts_a_draft_invoice_in_the_administration_of_the_company(): void
    {
        [$a, $userA, $job] = $this->doneJobWithExtras();
        $customer = Customer::create(['company_id' => $a->company_id, 'name' => 'A. Kowalska', 'type' => 'consumer', 'email' => 'Anna@Example.com', 'country' => 'PL', 'language' => 'pl', 'payment_terms' => 14]);

        $this->post(route('fachora.job.accept', $job->token))->assertRedirect($job->url())->assertSessionHas('flash');

        $job->refresh();
        $this->assertSame('accepted', $job->status);
        $this->assertNotNull($job->invoice_id, 'Na goedkeuring hoort er een conceptfactuur in de administratie van het bedrijf te staan');
        $invoice = Invoice::withoutGlobalScope('company')->findOrFail($job->invoice_id);
        $this->assertSame('draft', $invoice->status);
        $this->assertSame($a->company_id, $invoice->company_id);
        $this->assertSame($customer->id, $invoice->customer_id, 'De bestaande klantkaart (op e-mailadres, hoofdletterongevoelig) wordt hergebruikt');
        $this->assertEqualsWithDelta(600.0, (float) $invoice->total, 0.01, '480 + goedgekeurd meerwerk 120, incl. btw; afgewezen meerwerk telt niet');
        $this->assertSame('Fachora '.$job->reference, $invoice->reference);
        $this->assertSame('pl', $invoice->language);
        $this->assertSame(2, $invoice->lines()->count());
        $this->assertSame(1, Customer::withoutGlobalScope('company')->where('company_id', $a->company_id)->count());
        $this->assertNotNull($job->events()->where('kind', 'invoice')->first());

        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'accepted' && $m->audience === 'provider' && str_contains($m->render(), route('invoices.show', $invoice->id)));
        $this->actingAs($userA)->get(route('fachora.jobs.show', $job))->assertInertia(fn (Assert $p) => $p->where('invoice_url', route('invoices.show', $invoice->id)));

        // Een tweede opdracht van dezelfde klant: ook op dezelfde klantkaart, en niet nog een factuur voor de eerste.
        $job2 = $this->openJob(['status' => 'done', 'confirmed_at' => now(), 'provider_id' => $a->id, 'agreed_amount' => 200, 'agreed_type' => 'fixed', 'done_at' => now()]);
        app(JobService::class)->accept($job2, 'customer');
        $this->assertSame($customer->id, Invoice::withoutGlobalScope('company')->findOrFail($job2->fresh()->invoice_id)->customer_id);
        $this->assertSame($invoice->id, $job->fresh()->invoice_id);
    }

    /**
     * Een nieuwe klant krijgt bij de eerste factuur een klantkaart in de
     * administratie van het bedrijf.
     *
     * Valt nu om: JobService::draftInvoice() maakt de klant aan met type
     * 'private', maar customers.type kent alleen 'business' en 'consumer'
     * (enum/CHECK), dus de insert mislukt, de fout wordt alleen gelogd en er
     * komt géén conceptfactuur.
     */
    public function test_acceptance_creates_a_customer_card_for_a_new_customer(): void
    {
        [$a, , $job] = $this->doneJobWithExtras();
        $this->assertSame(0, Customer::withoutGlobalScope('company')->where('company_id', $a->company_id)->count());

        $this->post(route('fachora.job.accept', $job->token))->assertRedirect($job->url())->assertSessionHas('flash');

        $job->refresh();
        $this->assertSame('accepted', $job->status);
        $this->assertNotNull($job->invoice_id, 'Na goedkeuring hoort er een conceptfactuur in de administratie van het bedrijf te staan');
        $customer = Customer::withoutGlobalScope('company')->where('company_id', $a->company_id)->firstOrFail();
        $this->assertSame('anna@example.com', $customer->email);
        $this->assertSame('Anna Kowalska', $customer->name);
        $this->assertSame('consumer', $customer->type);
        $this->assertSame('ul. Piotrkowska 10/5', $customer->address_line);
        $this->assertSame('Łódź', $customer->city);
        $this->assertSame('PL', $customer->country);
        $this->assertSame($customer->id, Invoice::withoutGlobalScope('company')->findOrFail($job->invoice_id)->customer_id);
    }

    public function test_choosing_an_offer_without_a_price_is_refused(): void
    {
        $a = $this->verifiedProvider('Hydro A');
        $job = $this->openJob(['status' => 'open', 'confirmed_at' => now()]);
        app(JobService::class)->dispatch($job);
        $offer = $job->offers()->firstOrFail();

        $this->post(route('fachora.job.choose', [$job->token, $offer->id]), ['address_line' => 'ul. Piotrkowska 10/5'])->assertRedirect($job->url())->assertSessionHas('error');
        $this->assertSame('open', $job->fresh()->status);
        $this->assertSame('sent', $offer->fresh()->status);
        Mail::assertNotSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'chosen');

        // Zonder adres: validatiefout. Een aanbieding van een andere opdracht: geweigerd.
        app(JobService::class)->respond($offer, $this->quote(300));
        $this->post(route('fachora.job.choose', [$job->token, $offer->id]), [])->assertSessionHasErrors(['address_line']);
        $other = $this->openJob(['status' => 'open', 'confirmed_at' => now()]);
        $this->post(route('fachora.job.choose', [$other->token, $offer->id]), ['address_line' => 'x'])->assertRedirect($other->url())->assertSessionHas('error');
        $this->assertSame('responded', $offer->fresh()->status);
    }

    public function test_when_everyone_declines_the_next_round_starts_at_once(): void
    {
        config(['fachora.max_offers' => 1]);
        $a = $this->verifiedProvider('Hydro A', ['rating_avg' => 5.0]);
        $b = $this->verifiedProvider('Hydro B', ['rating_avg' => 4.0]);
        $job = $this->openJob(['status' => 'open', 'confirmed_at' => now()]);
        app(JobService::class)->dispatch($job);
        $this->assertSame([$a->id], $job->offers()->pluck('provider_id')->all());

        $this->post(route('fachora.offer.decline', $job->offers()->firstOrFail()->token), ['reason' => 'Urlop'])->assertSessionHasNoErrors();

        $job->refresh();
        $this->assertSame(2, $job->waves);
        $this->assertSame('open', $job->status);
        $this->assertSame([$a->id, $b->id], $job->offers()->pluck('provider_id')->all());
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'job' && $m->hasTo($b->email));
    }

    public function test_the_customer_can_cancel_before_the_work_is_done_and_the_company_hears_it(): void
    {
        $a = $this->verifiedProvider('Hydro A');
        $this->withUser($a);
        $jobs = app(JobService::class);
        $job = $this->openJob(['status' => 'open', 'confirmed_at' => now()]);
        $jobs->dispatch($job);
        $offer = $job->offers()->firstOrFail();
        $jobs->respond($offer, $this->quote(480));
        $jobs->choose($job->fresh(), $offer->fresh(), ['address_line' => 'ul. Piotrkowska 10/5']);

        $this->post(route('fachora.job.cancel', $job->token), ['reason' => 'Już naprawione'])->assertRedirect($job->url())->assertSessionHas('flash');
        $job->refresh();
        $this->assertSame('cancelled', $job->status);
        $this->assertSame('customer', $job->cancelled_by);
        $this->assertSame('Już naprawione', $job->cancel_reason);
        $this->assertSame('expired', $offer->fresh()->status);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'cancelled' && $m->audience === 'provider' && $m->hasTo($a->email) && ($m->extra['reason'] ?? null) === 'Już naprawione');
        Mail::assertNotSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'cancelled' && $m->audience === 'customer');

        // Daarna kan er niets meer: niet nog eens annuleren, niet goedkeuren.
        $this->post(route('fachora.job.cancel', $job->token))->assertSessionHas('error');
        $this->post(route('fachora.job.accept', $job->token))->assertSessionHas('error');
        $this->assertSame(0, $a->fresh()->jobs_done);

        // Na "klaar" kan de klant niet meer annuleren, alleen goedkeuren of een geschil openen.
        $done = $this->openJob(['status' => 'done', 'confirmed_at' => now(), 'provider_id' => $a->id, 'agreed_amount' => 300, 'agreed_type' => 'fixed', 'done_at' => now()]);
        $this->post(route('fachora.job.cancel', $done->token))->assertSessionHas('error');
        $this->assertSame('done', $done->fresh()->status);
    }

    public function test_the_company_can_cancel_its_own_job_and_the_customer_hears_it(): void
    {
        $a = $this->verifiedProvider('Hydro A');
        $userA = $this->withUser($a);
        $other = $this->verifiedProvider('Hydro B');
        $job = $this->openJob(['status' => 'scheduled', 'confirmed_at' => now(), 'provider_id' => $a->id, 'agreed_amount' => 300, 'agreed_type' => 'fixed']);
        $foreign = $this->openJob(['status' => 'scheduled', 'confirmed_at' => now(), 'provider_id' => $other->id, 'agreed_amount' => 300, 'agreed_type' => 'fixed']);

        $this->actingAs($userA);
        $this->post(route('fachora.jobs.cancel', $foreign), ['reason' => 'Nie moje'])->assertForbidden();
        $this->post(route('fachora.jobs.cancel', $job), ['reason' => 'x'])->assertSessionHasErrors(['reason']);
        $this->post(route('fachora.jobs.cancel', $job), ['reason' => 'Choroba w zespole'])->assertRedirect(route('fachora.dashboard'));

        $this->assertSame('cancelled', $job->fresh()->status);
        $this->assertSame('provider', $job->fresh()->cancelled_by);
        $this->assertSame('scheduled', $foreign->fresh()->status);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'cancelled' && $m->audience === 'customer' && $m->hasTo('anna@example.com'));
        Mail::assertNotSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'cancelled' && $m->audience === 'provider');
    }
}
