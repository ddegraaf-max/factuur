<?php

namespace Tests\Feature;

use App\Mail\FachoraMail;
use App\Models\Provider;
use App\Models\ProviderCheck;
use App\Models\User;
use App\Services\Fachora\ProviderService;
use App\Services\Fachora\VerificationService;
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
 * Fachora Verified: de stappen die het bedrijf zelf zet (voorwaarden,
 * telefoon per sms), wat automatisch gaat (biała lista), en de goedkeuring
 * door de eigenaar die pas kan als alle verplichte stappen groen zijn.
 */
class FachoraVerificationTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private const NIP = '1234563218';

    protected function setUp(): void
    {
        parent::setUp();
        config(['brand.active' => 'fachora', 'inertia.testing.ensure_pages_exist' => false, 'services.anthropic.key' => null, 'services.stripe.secret' => null]);
        Mail::fake();
        $this->travelTo(Carbon::parse('2026-10-16 10:00'));
    }

    private function fakeRegistry(bool $found = true, array $extra = []): void
    {
        $subject = $found ? [
            'name' => 'HYDRO-MAX JAN NOWAK', 'regon' => '123456789', 'statusVat' => 'Czynny',
            'workingAddress' => 'ul. Prosta 1, 90-001 Łódź', 'accountNumbers' => ['PL61109010140000071219812874', 'PL27114020040000300201355387'],
        ] : null;
        Http::fake($extra + [
            'wl-api.mf.gov.pl/*' => Http::response(['result' => ['subject' => $subject]], 200),
            'nominatim.openstreetmap.org/*' => Http::response([], 200),
            '*' => Http::response([], 200),
        ]);
    }

    /** Een bedrijf dat net zijn profiel claimde (inlog + administratie). @return array{0: Provider, 1: User} */
    private function claimed(array $attrs = []): array
    {
        $name = $attrs['display_name'] ?? 'Hydro-Max';
        $slug = Str::slug($name).'-'.Str::lower(Str::random(5));
        $provider = Provider::create($attrs + [
            'legal_name' => $name, 'display_name' => $name, 'slug' => $slug, 'status' => 'unclaimed', 'source' => 'ceidg',
            'nip' => self::NIP, 'phone' => '600 100 200', 'city' => 'Łódź', 'postal_code' => '90-001', 'address_line' => 'ul. Prosta 1',
            'lat' => 51.7592, 'lng' => 19.4560, 'optout_token' => bin2hex(random_bytes(32)),
        ]);
        $provider->syncCategories(['hydraulik']);
        $provider->forceFill(['claim_email' => $slug.'@example.com', 'claim_name' => 'Jan Nowak'])->save();
        $user = app(ProviderService::class)->confirmClaim($provider, 'tajne-haslo-123');

        return [$provider->fresh(), $user];
    }

    private function owner(): User
    {
        $owner = $this->demoUser();
        config(['services.marketing_stats.emails' => $owner->email]);

        return $owner;
    }

    /** @return array<string, string> kind => status */
    private function checks(Provider $provider): array
    {
        return ProviderCheck::where('provider_id', $provider->id)->orderBy('kind')->pluck('status', 'kind')->all();
    }

    public function test_the_verification_page_lists_every_step_with_its_state(): void
    {
        $this->fakeRegistry();
        [$provider, $user] = $this->claimed();

        $this->actingAs($user)->get(route('fachora.verification'))->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Fachora/Provider/Verification')
            ->has('provider.checks', 7)
            ->where('provider.checks.0.kind', 'registry')->where('provider.checks.0.status', 'passed')->where('provider.checks.0.required', true)
            ->where('provider.checks.1.kind', 'vat')->where('provider.checks.1.required', false)
            ->where('provider.checks.2.kind', 'email')->where('provider.checks.2.status', 'passed')
            ->where('provider.checks.3.kind', 'phone')->where('provider.checks.3.status', 'pending')->where('provider.checks.3.required', true)
            ->where('provider.checks.4.kind', 'terms')->where('provider.checks.4.status', 'pending')->where('provider.checks.4.required', true)
            ->where('provider.checks.5.kind', 'bank')->where('provider.checks.5.required', false)
            ->where('provider.checks.6.kind', 'insurance')->where('provider.checks.6.required', false)
            ->where('provider.ready', false)
            ->where('provider.terms_accepted', false)
            ->where('provider.phone_verified', false)
            ->where('sms_available', false)
            ->where('protect_enabled', false)
            ->where('terms_version', '2026-10-22')
            ->where('fee_percent', fn ($v) => (float) $v === 10.0)
            ->where('intro_fee_percent', fn ($v) => (float) $v === 5.0)
            ->where('intro_days', 90)
            ->where('insurance_files', []));

        // Zonder Protect is de bankstap niet verplicht; met Protect wel.
        $this->assertSame(['registry', 'email', 'phone', 'terms'], app(VerificationService::class)->required());
        config(['services.stripe.secret' => 'sk_test_x', 'fachora.protect' => true]);
        $this->assertSame(['registry', 'email', 'phone', 'terms', 'bank'], app(VerificationService::class)->required());
    }

    public function test_accepting_the_terms_is_tied_to_the_current_version(): void
    {
        $this->fakeRegistry();
        [$provider, $user] = $this->claimed();
        $this->actingAs($user);

        $this->post(route('fachora.verification.terms'), ['terms' => 1])->assertSessionHasErrors(['standard']);
        $this->post(route('fachora.verification.terms'), ['standard' => 1])->assertSessionHasErrors(['terms']);
        $this->assertSame('pending', $this->checks($provider)['terms']);

        $this->post(route('fachora.verification.terms'), ['terms' => 1, 'standard' => 1])->assertRedirect()->assertSessionHas('flash');
        $provider->refresh();
        $this->assertNotNull($provider->terms_accepted_at);
        $this->assertSame('2026-10-22', $provider->terms_version);
        $this->assertSame('passed', $this->checks($provider)['terms']);
        $this->get(route('fachora.verification'))->assertInertia(fn (Assert $p) => $p->where('provider.terms_accepted', true));

        // Een nieuwe versie van het regulamin: opnieuw accepteren.
        config(['fachora.terms_version' => '2027-01']);
        $this->get(route('fachora.verification'))->assertInertia(fn (Assert $p) => $p->where('provider.terms_accepted', false)->where('provider.checks.4.status', 'pending'));
    }

    public function test_the_registry_check_passes_for_an_active_nip_in_the_vat_list(): void
    {
        $this->fakeRegistry();
        [$provider] = $this->claimed(['legal_name' => 'Eigen naam', 'regon' => null]);

        $checks = ProviderCheck::where('provider_id', $provider->id)->get()->keyBy('kind');
        $this->assertSame('passed', $checks['registry']->status);
        $this->assertSame('HYDRO-MAX JAN NOWAK', $checks['registry']->note);
        $this->assertSame('Czynny', $checks['registry']->evidence['vat_status']);
        $this->assertSame('passed', $checks['vat']->status);
        $this->assertSame(2, $checks['vat']->evidence['accounts']);
        $this->assertSame('active', $provider->vat_status);
        $this->assertSame('active', $provider->registry_status);
        $this->assertNotNull($provider->vat_checked_at);
        $this->assertSame('123456789', $provider->regon, 'REGON uit het register');
        $this->assertSame('Eigen naam', $provider->legal_name, 'Een bestaande naam wordt niet overschreven');
        Http::assertSent(fn (ClientRequest $r) => str_contains($r->url(), 'wl-api.mf.gov.pl/api/search/nip/'.self::NIP));
    }

    public function test_an_unknown_nip_leaves_registry_and_vat_pending_for_manual_review(): void
    {
        $this->fakeRegistry(found: false);
        [$provider] = $this->claimed();

        $checks = ProviderCheck::where('provider_id', $provider->id)->get()->keyBy('kind');
        $this->assertSame('pending', $checks['registry']->status);
        $this->assertNotEmpty($checks['registry']->note);
        $this->assertSame('pending', $checks['vat']->status);
        $this->assertSame('none', $provider->vat_status);
        $this->assertNull($provider->registry_status);

        // Zonder NIP: ook handwerk, zonder het register te bevragen.
        [$noNip] = $this->claimed(['nip' => null, 'display_name' => 'Bez NIP']);
        $this->assertSame('pending', $this->checks($noNip)['registry']);
        $this->assertSame(1, Http::recorded(fn (ClientRequest $r) => str_contains($r->url(), 'wl-api.mf.gov.pl'))->count());
    }

    public function test_phone_verification_by_sms_code(): void
    {
        $this->fakeRegistry(true, ['api.smsgatewayapi.com/*' => Http::response(['messageid' => 'm1'], 200)]);
        [$provider, $user] = $this->claimed();
        $this->actingAs($user);

        // Zonder sms-koppeling: geen code, de eigenaar belt.
        $this->post(route('fachora.verification.phone'))->assertRedirect()->assertSessionHas('error');
        $this->assertNull($provider->fresh()->phone_code);
        Http::assertNotSent(fn (ClientRequest $r) => str_contains($r->url(), 'smsgatewayapi'));

        config(['services.smstools.client_id' => 'x', 'services.smstools.client_secret' => 'y']);
        $this->get(route('fachora.verification'))->assertInertia(fn (Assert $p) => $p->where('sms_available', true));
        $this->post(route('fachora.verification.phone'))->assertRedirect()->assertSessionHas('flash');
        $provider->refresh();
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $provider->phone_code);
        $this->assertNotNull($provider->phone_code_sent_at);
        $code = $provider->phone_code;
        Http::assertSent(fn (ClientRequest $r) => str_contains($r->url(), 'api.smsgatewayapi.com') && $r['to'] === '48600100200' && str_contains($r['message'], $code) && $r->hasHeader('X-Client-Id', 'x'));
        $this->get(route('fachora.verification'))->assertInertia(fn (Assert $p) => $p->where('provider.phone_code_sent', true)->missing('provider.phone_code'));

        // Niet twee keer binnen een minuut.
        $this->post(route('fachora.verification.phone'))->assertSessionHas('error');
        $this->assertSame($code, $provider->fresh()->phone_code);

        // Verkeerde code, dan de goede.
        $this->post(route('fachora.verification.phone.verify'), ['code' => '000000'])->assertSessionHasErrors(['code']);
        $this->assertNull($provider->fresh()->phone_verified_at);
        $this->post(route('fachora.verification.phone.verify'), ['code' => ' '.$code.' '])->assertRedirect()->assertSessionHas('flash');
        $provider->refresh();
        $this->assertNotNull($provider->phone_verified_at);
        $this->assertNull($provider->phone_code);
        $this->assertSame('passed', $this->checks($provider)['phone']);

        // Een code van meer dan een half uur oud geldt niet meer.
        $this->travelTo(Carbon::parse('2026-10-16 11:00'));
        $verification = app(VerificationService::class);
        [$late] = $this->claimed(['display_name' => 'Laat', 'nip' => null]);
        $this->assertTrue($verification->sendPhoneCode($late));
        $this->travelTo(Carbon::parse('2026-10-16 11:31'));
        $this->assertFalse($verification->verifyPhone($late, (string) $late->fresh()->phone_code));
        $this->assertNull($late->fresh()->phone_verified_at);
    }

    public function test_requesting_verification_runs_the_checks_and_informs_company_and_owner(): void
    {
        $this->fakeRegistry();
        $owner = $this->owner();
        [$provider, $user] = $this->claimed();

        $this->actingAs($user)->post(route('fachora.verification.request'))->assertRedirect()->assertSessionHas('flash');

        $provider->refresh();
        $this->assertSame('pending', $provider->status);
        $this->assertNotNull($provider->verification_requested_at);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'verification_pending' && $m->audience === 'provider' && $m->hasTo($provider->email));
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'verification_requested' && $m->audience === 'owner' && $m->hasTo($owner->email) && $m->provider?->id === $provider->id);

        // Nog eens aanvragen: de datum blijft staan. Een Verified bedrijf kan niet aanvragen.
        $requested = $provider->verification_requested_at;
        $this->travelTo(now()->addHour());
        $this->post(route('fachora.verification.request'))->assertSessionHas('flash');
        $this->assertTrue($requested->equalTo($provider->fresh()->verification_requested_at));
        $provider->forceFill(['status' => 'verified'])->save();
        $this->post(route('fachora.verification.request'))->assertSessionHas('error');
    }

    public function test_the_owner_can_only_approve_when_every_required_step_is_green(): void
    {
        $this->fakeRegistry();
        $owner = $this->owner();
        [$provider, $user] = $this->claimed();
        app(VerificationService::class)->request($provider);
        $this->assertSame(['bank' => 'pending', 'email' => 'passed', 'insurance' => 'pending', 'phone' => 'pending', 'registry' => 'passed', 'terms' => 'pending', 'vat' => 'passed'], $this->checks($provider));

        $this->actingAs($owner);
        $this->post(route('fachora.admin.providers.approve', $provider))->assertRedirect()->assertSessionHas('error');
        $this->assertSame('pending', $provider->fresh()->status);
        $this->assertNull($provider->fresh()->verified_at);

        // Gebeld: telefoon handmatig afgevinkt; het bedrijf accepteert de voorwaarden.
        $this->post(route('fachora.admin.providers.check', $provider), ['kind' => 'phone', 'note' => 'Gebeld op 16 oktober'])->assertSessionHas('flash');
        $this->post(route('fachora.admin.providers.check', $provider), ['kind' => 'bestaat-niet'])->assertSessionHasErrors(['kind']);
        $check = ProviderCheck::where('provider_id', $provider->id)->where('kind', 'phone')->firstOrFail();
        $this->assertSame('manual', $check->status);
        $this->assertSame('Gebeld op 16 oktober', $check->note);
        $this->assertSame($owner->id, $check->checked_by);
        $this->assertNotNull($provider->fresh()->phone_verified_at);
        $this->post(route('fachora.admin.providers.approve', $provider))->assertSessionHas('error');

        app(VerificationService::class)->acceptTerms($provider->fresh());
        $this->assertTrue(app(VerificationService::class)->ready($provider->fresh()));
        // Opnieuw automatisch controleren laat de handmatige telefoonstap staan.
        $this->post(route('fachora.admin.providers.recheck', $provider))->assertSessionHas('flash');
        $this->assertSame('manual', $this->checks($provider)['phone']);

        $this->post(route('fachora.admin.providers.approve', $provider))->assertRedirect()->assertSessionHas('flash');
        $provider->refresh();
        $this->assertSame('verified', $provider->status);
        $this->assertNotNull($provider->verified_at);
        $this->assertNull($provider->status_reason);
        $this->assertTrue($provider->isVerified());
        $this->assertTrue($provider->canReceiveJobs());
        $this->assertEqualsWithDelta(5.0, $provider->feePercent(), 0.001, 'Net geverifieerd: introductietarief');
        $this->assertTrue($provider->company->fresh()->trial_ends_at->gte(now()->addYears(4)), 'Gratis administratie zolang het bedrijf Verified is');
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'verified' && $m->audience === 'provider' && $m->hasTo($provider->email));

        // Het bedrijf ziet het keurmerk; het publiek ook.
        $this->actingAs($user)->get(route('fachora.verification'))->assertInertia(fn (Assert $p) => $p->where('provider.status', 'verified')->where('provider.verified', true)->where('provider.ready', true));
        $this->assertTrue($provider->publicSummary()['verified']);
    }

    public function test_suspend_block_and_restore(): void
    {
        $this->fakeRegistry();
        $owner = $this->owner();
        [$provider] = $this->claimed();
        $this->actingAs($owner);

        $this->post(route('fachora.admin.providers.suspend', $provider), ['reason' => ''])->assertSessionHasErrors(['reason']);
        $this->post(route('fachora.admin.providers.suspend', $provider), ['reason' => 'Twee klachten in een week', 'until' => '2026-11-01'])->assertSessionHas('flash');
        $provider->refresh();
        $this->assertSame('suspended', $provider->status);
        $this->assertSame('Twee klachten in een week', $provider->status_reason);
        $this->assertSame('2026-11-01', $provider->suspended_until->toDateString());
        $this->assertNotNull($provider->suspended_at);
        $this->assertFalse($provider->canReceiveJobs());
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'suspended' && $m->hasTo($provider->email) && ($m->extra['reason'] ?? null) === 'Twee klachten in een week' && ($m->extra['until'] ?? null) === '2026-11-01');

        // Terugzetten: naar 'claimed', want het keurmerk was nog niet aangevraagd.
        $this->post(route('fachora.admin.providers.restore', $provider))->assertSessionHas('flash');
        $provider->refresh();
        $this->assertSame('claimed', $provider->status);
        $this->assertNull($provider->status_reason);
        $this->assertNull($provider->suspended_at);
        $this->assertNull($provider->suspended_until);

        // Na een aanvraag: terugzetten naar 'pending'.
        $provider->forceFill(['verification_requested_at' => now()])->save();
        $this->post(route('fachora.admin.providers.block', $provider), ['reason' => 'Fraude'])->assertSessionHas('flash');
        $provider->refresh();
        $this->assertSame('blocked', $provider->status);
        $this->assertNotNull($provider->blocked_at);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'blocked' && $m->hasTo($provider->email));
        $this->post(route('fachora.admin.providers.approve', $provider))->assertSessionHas('error');
        $this->post(route('fachora.admin.providers.restore', $provider))->assertSessionHas('flash');
        $provider->refresh();
        $this->assertSame('pending', $provider->status);
        $this->assertNull($provider->blocked_at);
    }

    public function test_the_weekly_recheck_suspends_a_verified_company_that_disappeared_from_the_vat_list(): void
    {
        $this->fakeRegistry(found: false);
        $owner = $this->owner();
        $make = function (string $name, array $attrs) {
            $slug = Str::slug($name);
            $p = Provider::create($attrs + [
                'legal_name' => $name, 'display_name' => $name, 'slug' => $slug, 'status' => 'verified', 'email' => $slug.'@example.com',
                'city' => 'Łódź', 'claimed_at' => now()->subMonth(), 'consent_at' => now()->subMonth(), 'verified_at' => now()->subMonth(),
                'optout_token' => bin2hex(random_bytes(32)),
            ]);

            return $p->refresh();
        };
        $gone = $make('Verdwenen', ['nip' => self::NIP, 'vat_status' => 'active']);
        $exempt = $make('Vrijgesteld', ['nip' => '5260250274', 'vat_status' => 'exempt']);
        $expired = $make('Polis Verlopen', ['nip' => null, 'insurance_until' => '2026-10-01']);
        $fine = $make('Alles Goed', ['nip' => null, 'insurance_until' => '2027-10-01']);

        $this->artisan('fachora:checks')->expectsOutputToContain('Bedrijven met een wijziging: 2')->assertExitCode(0);

        $this->assertSame('suspended', $gone->fresh()->status);
        $this->assertNotNull($gone->fresh()->status_reason);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'suspended' && $m->hasTo($gone->email));
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'provider_suspended' && $m->audience === 'owner' && $m->hasTo($owner->email) && $m->provider?->id === $gone->id);

        $this->assertSame('verified', $exempt->fresh()->status, 'Wie al niet btw-plichtig was, wordt niet geschorst');
        $this->assertSame('verified', $expired->fresh()->status);
        $this->assertSame('pending', $this->checks($expired)['insurance']);
        $this->assertStringContainsString('01.10.2026', (string) ProviderCheck::where('provider_id', $expired->id)->where('kind', 'insurance')->value('note'));
        $this->assertSame([], $this->checks($fine));

        // Onder een ander merk doet de opdracht niets.
        config(['brand.active' => 'lopra_pl']);
        $this->artisan('fachora:checks')->doesntExpectOutputToContain('Bedrijven met een wijziging')->assertExitCode(0);
    }
}
