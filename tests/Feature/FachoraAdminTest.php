<?php

namespace Tests\Feature;

use App\Mail\FachoraMail;
use App\Models\Provider;
use App\Models\ProviderCheck;
use App\Models\ServiceJob;
use App\Models\User;
use App\Services\Fachora\JobService;
use App\Services\Fachora\LetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * De eigenaarspagina's van Fachora (/fachora/admin): bedrijvendatabase
 * (filteren, importeren, brieven, aantekeningen), opdrachten (bellijst,
 * handmatig aanbieden, ingrijpen). Alleen voor de eigenaar.
 */
class FachoraAdminTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private const NIP = '1234563218';

    protected function setUp(): void
    {
        parent::setUp();
        config(['brand.active' => 'fachora', 'inertia.testing.ensure_pages_exist' => false, 'services.anthropic.key' => null, 'services.stripe.secret' => null]);
        Mail::fake();
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([], 200),
            'wl-api.mf.gov.pl/*' => Http::response(['result' => ['subject' => null]], 200),
            '*' => Http::response([], 200),
        ]);
        $this->travelTo(Carbon::parse('2026-10-16 10:00'));
    }

    private function owner(): User
    {
        $owner = $this->demoUser();
        config(['services.marketing_stats.emails' => $owner->email]);
        $this->actingAs($owner);

        return $owner;
    }

    private function provider(string $name, array $attrs = [], array $categories = ['hydraulik']): Provider
    {
        $slug = Str::slug($name).'-'.Str::lower(Str::random(5));
        $provider = Provider::create($attrs + [
            'legal_name' => $name, 'display_name' => $name, 'slug' => $slug, 'status' => 'unclaimed', 'source' => 'ceidg',
            'city' => 'Łódź', 'postal_code' => '90-001', 'address_line' => 'ul. Prosta 1',
            'lat' => 51.7592, 'lng' => 19.4560, 'optout_token' => bin2hex(random_bytes(32)),
        ]);
        $provider->syncCategories($categories);

        return $provider->refresh();
    }

    private function claimedProvider(string $name, array $attrs = [], array $categories = ['hydraulik']): Provider
    {
        return $this->provider($name, $attrs + [
            'status' => 'claimed', 'email' => Str::slug($name).'@example.com', 'claimed_at' => now()->subDay(), 'consent_at' => now()->subDay(), 'consent_source' => 'claim',
        ], $categories);
    }

    private function job(array $attrs = []): ServiceJob
    {
        return ServiceJob::create($attrs + [
            'reference' => ServiceJob::nextReference(), 'token' => bin2hex(random_bytes(32)), 'status' => 'open', 'confirmed_at' => now(),
            'category' => 'hydraulik', 'title' => 'Wyciek pod zlewem', 'description' => 'Kapie pod zlewem w kuchni, potrzebna szybka naprawa.',
            'urgency' => 'week', 'language' => 'pl', 'customer_name' => 'Anna Kowalska', 'customer_email' => 'anna@example.com',
            'customer_locale' => 'pl', 'postal_code' => '90-001', 'city' => 'Łódź', 'lat' => 51.7592, 'lng' => 19.4560,
        ]);
    }

    public function test_the_owner_imports_from_ceidg_with_a_pkd_code_and_an_area(): void
    {
        $this->owner();
        config(['fachora.ceidg_token' => 'jwt-token']);
        // De catch-all van setUp zou vóór deze stub gaan; verse fabriek, dan wint de CEIDG-stub.
        Http::swap(new \Illuminate\Http\Client\Factory(app('events')));
        Http::fake([
            'dane.biznes.gov.pl/*' => fn (\Illuminate\Http\Client\Request $r) => str_contains($r->url(), 'page=0')
                ? Http::response(['firmy' => [[
                    'id' => 'E7854341', 'nazwa' => 'Instalacje Ziemne Konrad Pietrzyk', 'status' => 'AKTYWNY',
                    'adresDzialalnosci' => ['ulica' => 'ul. Rubinowa', 'budynek' => '18', 'miasto' => 'Sadków', 'wojewodztwo' => 'MAZOWIECKIE', 'kod' => '26-613'],
                    'wlasciciel' => ['imie' => 'Konrad', 'nazwisko' => 'Pietrzyk', 'nip' => '7962648883', 'regon' => ''],
                ]], 'count' => 1], 200)
                : Http::response(['firmy' => [], 'count' => 1], 200),
            'nominatim.openstreetmap.org/*' => Http::response([], 200),
            '*' => Http::response([], 200),
        ]);

        $this->get(route('fachora.admin.providers'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('ceidg', true)->has('pkdOptions'));
        $this->post(route('fachora.admin.ceidg'), ['pkd' => '43.22.Z', 'wojewodztwo' => 'mazowieckie', 'pages' => 2])->assertRedirect()->assertSessionHas('flash');

        $imported = Provider::where('nip', '7962648883')->firstOrFail();
        $this->assertSame('unclaimed', $imported->status);
        $this->assertSame('ceidg', $imported->source);
        $this->assertNull($imported->regon);
        $this->assertContains('hydraulik', $imported->categories(), 'Categorie uit de gezochte PKD-code, want de lijst geeft er geen');
        Http::assertSent(fn (\Illuminate\Http\Client\Request $r) => str_contains($r->url(), '/api/ceidg/v3/firmy') && str_contains($r->url(), 'pkd=4322Z') && str_contains($r->url(), 'wojewodztwo=mazowieckie') && str_contains($r->url(), 'page=0'));

        $this->post(route('fachora.admin.ceidg'), ['pkd' => 'abc'])->assertSessionHasErrors('pkd');
    }

    public function test_the_admin_pages_are_for_the_owner_only(): void
    {
        config(['services.marketing_stats.emails' => 'eigenaar@example.com']);
        $customer = $this->demoUser();
        $provider = $this->provider('Hydro');
        $job = $this->job();

        $this->get(route('fachora.admin.providers'))->assertRedirect(route('login'));
        $this->actingAs($customer);
        $this->get(route('fachora.admin.providers'))->assertForbidden();
        $this->get(route('fachora.admin.jobs'))->assertForbidden();
        $this->get(route('fachora.admin.providers.show', $provider))->assertForbidden();
        $this->get(route('fachora.admin.jobs.show', $job))->assertForbidden();
        $this->get(route('fachora.admin.letters'))->assertForbidden();
        $this->post(route('fachora.admin.import'), ['text' => 'x'])->assertForbidden();
        $this->post(route('fachora.admin.ceidg'), ['pkd' => '43.22.Z'])->assertForbidden();
        $this->post(route('fachora.admin.providers.approve', $provider))->assertForbidden();
        $this->post(route('fachora.admin.jobs.cancel', $job))->assertForbidden();
        $this->assertSame('open', $job->fresh()->status);

        $owner = $this->demoUser();
        $owner->forceFill(['email' => 'eigenaar@example.com'])->save();
        $this->actingAs($owner->fresh());
        $this->get(route('fachora.admin.providers'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Fachora/Admin/Providers'));
        $this->get(route('fachora.admin.jobs'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Fachora/Admin/Jobs'));
    }

    public function test_the_company_list_filters_by_status_search_and_category(): void
    {
        $this->owner();
        $this->provider('Hydro Łódź');
        $elektro = $this->provider('Elektro Kraków', ['status' => 'verified', 'city' => 'Kraków', 'verified_at' => now()], ['elektryk']);
        $dach = $this->provider('Dach Gdańsk', ['status' => 'claimed', 'city' => 'Gdańsk', 'nip' => self::NIP, 'address_line' => null], ['dach']);

        $this->get(route('fachora.admin.providers'))->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Fachora/Admin/Providers')
            ->has('providers.data', 3)
            ->where('providers.data.0.name', 'Dach Gdańsk')
            ->where('providers.data.0.consent', false)
            ->where('counts.all', 3)->where('counts.verified', 1)->where('counts.claimed', 1)->where('counts.unclaimed', 1)->where('counts.blocked', 0)
            ->where('filters.status', 'all')
            ->where('letterCandidates', 1)
            ->where('ceidg', false)
            ->where('jobCounts.all', 0));

        $this->get(route('fachora.admin.providers', ['status' => 'verified']))->assertInertia(fn (Assert $p) => $p->has('providers.data', 1)->where('providers.data.0.id', $elektro->id)->where('filters.status', 'verified'));
        $this->get(route('fachora.admin.providers', ['q' => 'dach']))->assertInertia(fn (Assert $p) => $p->has('providers.data', 1)->where('providers.data.0.id', $dach->id));
        $this->get(route('fachora.admin.providers', ['q' => self::NIP]))->assertInertia(fn (Assert $p) => $p->has('providers.data', 1)->where('providers.data.0.nip', self::NIP));
        $this->get(route('fachora.admin.providers', ['q' => 'Kraków']))->assertInertia(fn (Assert $p) => $p->has('providers.data', 1)->where('providers.data.0.city', 'Kraków'));
        $this->get(route('fachora.admin.providers', ['categorie' => 'elektryk']))->assertInertia(fn (Assert $p) => $p->has('providers.data', 1)->where('providers.data.0.categories', ['elektryk']));
        $this->get(route('fachora.admin.providers', ['categorie' => 'astronauta', 'status' => 'onbekend']))->assertInertia(fn (Assert $p) => $p->has('providers.data', 3));
        $this->get(route('fachora.admin.providers', ['q' => 'niets-hiervan']))->assertInertia(fn (Assert $p) => $p->has('providers.data', 0));
    }

    public function test_importing_a_pasted_list_skips_header_and_duplicates_and_records_opt_in(): void
    {
        $this->owner();
        $text = implode("\n", [
            'nazwa;nip;e-mail;telefon;miasto;kod;kategorie;zgoda',
            'Hydro-Max;123-456-32-18;jan@hydromax.pl;600100200;Łódź;90-001;Hydraulik, Ogrzewanie;tak',
            'Hydro-Max Bis;1234563218;;;Łódź;;hydraulik;nie',
            'Elektro Nowak;1234567890;geen-adres;;Kraków;30-001;Elektryk;',
            '',
            'Bez Kategorii;;;;Warszawa;;Astronauta;nie',
        ]);

        $this->post(route('fachora.admin.import'), ['text' => '   '])->assertSessionHasErrors(['text']);
        $this->post(route('fachora.admin.import'), ['text' => $text, 'source' => 'lista-2026', 'consent_source' => 'formularz-partnera'])->assertRedirect()->assertSessionHas('flash');

        $this->assertSame(3, Provider::count());
        $hydro = Provider::where('display_name', 'Hydro-Max')->firstOrFail();
        $this->assertSame(self::NIP, $hydro->nip);
        $this->assertSame('jan@hydromax.pl', $hydro->email);
        $this->assertSame('unclaimed', $hydro->status);
        $this->assertSame('lista-2026', $hydro->source);
        $this->assertNotNull($hydro->consent_at);
        $this->assertSame('formularz-partnera', $hydro->consent_source);
        $this->assertSame(['hydraulik', 'ogrzewanie'], $hydro->categories());
        $this->assertEqualsWithDelta(51.7592, $hydro->lat, 0.001);
        $this->assertSame(64, strlen((string) $hydro->optout_token));

        $this->assertNull(Provider::where('display_name', 'Hydro-Max Bis')->first(), 'Dezelfde NIP: overgeslagen');
        $elektro = Provider::where('display_name', 'Elektro Nowak')->firstOrFail();
        $this->assertNull($elektro->nip, 'Een NIP met fout controlecijfer wordt niet bewaard');
        $this->assertNull($elektro->email);
        $this->assertNull($elektro->consent_at);
        $this->assertSame(['elektryk'], $elektro->categories());
        $bez = Provider::where('display_name', 'Bez Kategorii')->firstOrFail();
        $this->assertSame([], $bez->categories(), 'Onbekende categorie wordt genegeerd');

        // Nog eens dezelfde lijst: alles bestaat al (op NIP, of op naam + plaats).
        // 'source' en 'consent_source' meesturen zoals het formulier doet: AdminController::import leest ze zonder ??.
        $this->post(route('fachora.admin.import'), ['text' => $text, 'source' => null, 'consent_source' => null])->assertSessionHas('flash');
        $this->assertSame(3, Provider::count());
        Mail::assertNothingSent();
    }

    public function test_letters_are_a_pdf_for_unclaimed_companies_with_a_postal_address(): void
    {
        $this->owner();
        $a = $this->provider('Hydro A');
        $b = $this->provider('Hydro B', ['claim_code' => 'ABCD2345']);
        $noAddress = $this->provider('Hydro Bez Adresu', ['address_line' => null]);
        $claimed = $this->claimedProvider('Hydro Claimed');
        $elektro = $this->provider('Elektro', [], ['elektryk']);

        $this->assertEqualsCanonicalizing([$a->id, $b->id, $elektro->id], app(LetterService::class)->candidates(null, null, 100)->pluck('id')->all());
        $this->assertSame([$elektro->id], app(LetterService::class)->candidates('elektryk', null, 100)->pluck('id')->all());
        $this->assertSame([$elektro->id], app(LetterService::class)->candidates(null, null, 1)->pluck('id')->all(), 'Op plaats en dan naam');

        $response = $this->get(route('fachora.admin.letters', ['categorie' => 'hydraulik', 'aantal' => 10]));
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('attachment; filename="fachora-listy-20261016.pdf"', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());

        $this->assertMatchesRegularExpression('/^[A-Z2-9]{8}$/', (string) $a->fresh()->claim_code);
        $this->assertSame('ABCD2345', $b->fresh()->claim_code, 'Een bestaande code blijft');
        $this->assertNull($noAddress->fresh()->claim_code);
        $this->assertNull($claimed->fresh()->claim_code);
        $this->assertNull($elektro->fresh()->claim_code, 'Niet in deze selectie');
        // De code uit de brief werkt op de claimpagina.
        $this->get(route('fachora.claim', ['kod' => $a->fresh()->claim_code]))->assertInertia(fn (Assert $p) => $p->where('provider.id', $a->id)->where('codeInvalid', false));

        $this->get(route('fachora.admin.letters', ['categorie' => 'mechanik']))->assertNotFound();
        Mail::assertNothingSent();
    }

    public function test_the_job_list_groups_by_status_and_the_job_page_shows_call_list_and_claimable_companies(): void
    {
        $this->owner();
        $verified = $this->claimedProvider('Hydro Verified', ['status' => 'verified', 'verified_at' => now()]);
        $nearbyUnclaimed = $this->provider('Hydro Rejestr', ['phone' => '600100200']);
        $claimable = $this->claimedProvider('Hydro Claimed');
        $this->provider('Elektro Rejestr', [], ['elektryk']);

        $open = $this->job();
        app(JobService::class)->dispatch($open);
        $this->assertSame([$verified->id], $open->offers()->pluck('provider_id')->all());
        $active = $this->job(['status' => 'scheduled', 'provider_id' => $verified->id, 'agreed_amount' => 300, 'agreed_type' => 'fixed']);
        $disputed = $this->job(['status' => 'disputed', 'provider_id' => $verified->id, 'agreed_amount' => 300, 'agreed_type' => 'fixed', 'dispute_reason' => 'x']);
        $accepted = $this->job(['status' => 'accepted', 'provider_id' => $verified->id, 'agreed_amount' => 500, 'agreed_type' => 'fixed', 'fee_amount' => 50, 'accepted_at' => now()]);

        $this->get(route('fachora.admin.jobs'))->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Fachora/Admin/Jobs')
            ->where('status', 'open')
            ->has('jobs.data', 1)->where('jobs.data.0.reference', $open->reference)->where('jobs.data.0.offers', 1)->where('jobs.data.0.responded', 0)
            ->where('counts.open', 1)->where('counts.active', 3)->where('counts.disputed', 1)->where('counts.closed', 1)->where('counts.all', 4)
            ->where('stats.accepted', 1)->where('stats.revenue', fn ($v) => (float) $v === 500.0)->where('stats.fees', fn ($v) => (float) $v === 50.0)->where('stats.verified', 1));
        $this->get(route('fachora.admin.jobs', ['status' => 'disputed']))->assertInertia(fn (Assert $p) => $p->has('jobs.data', 1)->where('jobs.data.0.id', $disputed->id));
        $this->get(route('fachora.admin.jobs', ['status' => 'closed']))->assertInertia(fn (Assert $p) => $p->has('jobs.data', 1)->where('jobs.data.0.id', $accepted->id)->where('jobs.data.0.provider', 'Hydro Verified'));
        $this->get(route('fachora.admin.jobs', ['status' => 'active']))->assertInertia(fn (Assert $p) => $p->has('jobs.data', 3));
        $this->get(route('fachora.admin.jobs', ['status' => 'all']))->assertInertia(fn (Assert $p) => $p->has('jobs.data', 4));
        $this->get(route('fachora.admin.jobs', ['status' => 'onzin']))->assertInertia(fn (Assert $p) => $p->has('jobs.data', 1));

        $this->get(route('fachora.admin.jobs.show', $open))->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Fachora/Admin/Job')
            ->where('job.customer_email', 'anna@example.com')
            ->where('job.waves', 1)
            ->has('offers', 1)->where('offers.0.provider.name', 'Hydro Verified')
            ->has('nearby', 2)
            ->where('nearby', fn ($nearby) => collect($nearby)->pluck('phone')->contains('600100200') && collect($nearby)->pluck('id')->contains($nearbyUnclaimed->id))
            ->has('claimable', 1)->where('claimable.0.id', $claimable->id)->where('claimable.0.consent', true)
            ->where('provider', null));
        $this->get(route('fachora.admin.jobs.show', $active))->assertInertia(fn (Assert $p) => $p->where('provider.name', 'Hydro Verified')->where('job.provider_id', $verified->id));
    }

    public function test_the_owner_offers_a_job_by_hand_only_to_companies_that_asked_for_mail(): void
    {
        $this->owner();
        $claimed = $this->claimedProvider('Hydro Claimed');
        $pending = $this->claimedProvider('Hydro Pending', ['status' => 'pending']);
        $blocked = $this->claimedProvider('Hydro Blocked', ['status' => 'blocked']);
        $register = $this->provider('Hydro Rejestr', ['email' => 'rejestr@example.com']);
        $job = $this->job(['status' => 'no_match']);

        $this->post(route('fachora.admin.jobs.offer', $job), ['provider_ids' => []])->assertSessionHasErrors(['provider_ids']);
        $this->post(route('fachora.admin.jobs.offer', $job), ['provider_ids' => [$claimed->id, $pending->id, $blocked->id, $register->id]])->assertRedirect()->assertSessionHas('flash');

        $job->refresh();
        $this->assertSame('open', $job->status);
        $this->assertSame(1, $job->waves);
        $this->assertEqualsCanonicalizing([$claimed->id, $pending->id], $job->offers()->pluck('provider_id')->all());
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'job' && $m->hasTo($claimed->email));
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'job' && $m->hasTo($pending->email));
        Mail::assertNotSent(FachoraMail::class, fn (FachoraMail $m) => $m->hasTo($blocked->email) || $m->hasTo('rejestr@example.com'));
        $this->assertSame('offered', $job->events()->latest('id')->firstOrFail()->kind);
        $this->assertSame('owner', $job->events()->latest('id')->firstOrFail()->actor);

        // Nog eens dezelfde bedrijven: niemand krijgt een tweede aanbieding.
        $this->post(route('fachora.admin.jobs.offer', $job), ['provider_ids' => [$claimed->id]])->assertSessionHas('error');
        $this->assertSame(2, $job->offers()->count());
        $this->assertSame(2, Mail::sent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'job')->count());
    }

    public function test_the_owner_can_cancel_accept_and_retry_but_not_resolve_without_a_dispute(): void
    {
        $this->owner();
        $verified = $this->claimedProvider('Hydro Verified', ['status' => 'verified', 'verified_at' => now()->subDays(100)]);
        $open = $this->job();
        $done = $this->job(['status' => 'done', 'provider_id' => $verified->id, 'agreed_amount' => 300, 'agreed_type' => 'fixed', 'done_at' => now()]);

        $this->post(route('fachora.admin.jobs.cancel', $open), ['reason' => 'Spam'])->assertRedirect()->assertSessionHas('flash');
        $open->refresh();
        $this->assertSame('cancelled', $open->status);
        $this->assertSame('owner', $open->cancelled_by);
        $this->assertSame('Spam', $open->cancel_reason);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'cancelled' && $m->audience === 'customer' && $m->hasTo('anna@example.com'));

        $this->post(route('fachora.admin.jobs.resolve', $done), ['resolution' => 'payout'])->assertSessionHas('error');
        $this->post(route('fachora.admin.jobs.resolve', $done), ['resolution' => 'anders'])->assertSessionHasErrors(['resolution']);
        $this->assertSame('done', $done->fresh()->status);

        $this->post(route('fachora.admin.jobs.accept', $done))->assertSessionHas('flash');
        $done->refresh();
        $this->assertSame('accepted', $done->status);
        $this->assertSame('owner', $done->accepted_by);
        $this->assertEqualsWithDelta(30.0, $done->fee_amount, 0.001);
        $this->assertSame(1, $verified->fresh()->jobs_done);

        // Zonder betaling via Protect is er niets uit te betalen: geen Stripe-aanroep.
        $this->post(route('fachora.admin.jobs.payout', $done))->assertSessionHas('flash');
        Http::assertNotSent(fn (ClientRequest $r) => str_contains($r->url(), 'stripe'));
        $this->post(route('fachora.admin.jobs.cancel', $done))->assertSessionHas('error');
    }

    public function test_the_company_page_shows_consent_and_the_owner_keeps_notes_and_ticks_checks(): void
    {
        $owner = $this->owner();
        $provider = $this->provider('Hydro Rejestr', ['email' => 'rejestr@example.com', 'claim_code' => 'ABCD2345', 'phone' => '600100200']);
        $job = $this->job(['status' => 'scheduled', 'provider_id' => $provider->id, 'agreed_amount' => 300, 'agreed_type' => 'fixed']);

        $this->post(route('fachora.admin.providers.note', $provider), ['notes' => "Gebeld 16/10: wil eerst de brief zien.\nTerugbellen in november."])->assertRedirect()->assertSessionHas('flash');
        $this->assertStringContainsString('Terugbellen in november', (string) $provider->fresh()->notes);

        $this->get(route('fachora.admin.providers.show', $provider))->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Fachora/Admin/Provider')
            ->where('provider.id', $provider->id)
            ->where('provider.consent', false)
            ->where('provider.consent_reason', fn ($v) => is_string($v) && $v !== '')
            ->where('provider.consent_source', null)
            ->where('provider.claim_code', 'ABCD2345')
            ->where('provider.source', 'ceidg')
            ->where('provider.user', null)
            ->where('provider.notes', fn ($v) => str_contains((string) $v, 'Terugbellen'))
            ->has('checkKinds', 7)
            ->has('jobs', 1)->where('jobs.0.reference', $job->reference)
            ->where('protect_enabled', false));

        // Een stap met de hand afvinken (polis gezien), en de automatische controles opnieuw draaien.
        $this->post(route('fachora.admin.providers.check', $provider), ['kind' => 'insurance', 'note' => 'Polis gezien, geldig tot 2027'])->assertSessionHas('flash');
        $check = ProviderCheck::where('provider_id', $provider->id)->where('kind', 'insurance')->firstOrFail();
        $this->assertSame('manual', $check->status);
        $this->assertSame($owner->id, $check->checked_by);
        $this->post(route('fachora.admin.providers.recheck', $provider))->assertSessionHas('flash');
        $this->assertSame('manual', ProviderCheck::where('provider_id', $provider->id)->where('kind', 'insurance')->value('status'), 'Handmatig blijft handmatig');
        $this->assertSame('pending', ProviderCheck::where('provider_id', $provider->id)->where('kind', 'registry')->value('status'));

        $this->post(route('fachora.admin.providers.note', $provider), ['notes' => ''])->assertSessionHas('flash');
        $this->assertNull($provider->fresh()->notes);
        $this->get(route('fachora.admin.providers.show', 999999))->assertNotFound();
    }
}
