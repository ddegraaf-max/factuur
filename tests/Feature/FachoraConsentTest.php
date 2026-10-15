<?php

namespace Tests\Feature;

use App\Mail\FachoraMail;
use App\Models\Provider;
use App\Models\ServiceJob;
use App\Models\User;
use App\Services\Fachora\ConsentPolicy;
use App\Services\Fachora\JobService;
use App\Services\Fachora\Notifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * De juridische kern: Fachora mailt een bedrijf uit een openbaar register
 * nooit ongevraagd (PKE). Alleen na claimen of met vastgelegde opt-in, en
 * een afmelding wint altijd.
 */
class FachoraConsentTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

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

    private function provider(string $name, array $attrs = [], array $categories = ['hydraulik']): Provider
    {
        $slug = Str::slug($name).'-'.Str::lower(Str::random(5));
        $provider = Provider::create($attrs + [
            'legal_name' => $name, 'display_name' => $name, 'slug' => $slug, 'status' => 'unclaimed', 'source' => 'ceidg',
            'email' => $slug.'@example.com', 'phone' => '600100200', 'city' => 'Łódź', 'postal_code' => '90-001', 'address_line' => 'ul. Prosta 1',
            'lat' => 51.7592, 'lng' => 19.4560, 'radius_km' => 25, 'languages' => ['pl' => 'native'],
            'optout_token' => bin2hex(random_bytes(32)),
        ]);
        $provider->syncCategories($categories);

        return $provider->refresh();
    }

    private function openJob(): ServiceJob
    {
        return ServiceJob::create([
            'reference' => ServiceJob::nextReference(), 'token' => bin2hex(random_bytes(32)), 'status' => 'open', 'confirmed_at' => now(),
            'category' => 'hydraulik', 'title' => 'Wyciek pod zlewem', 'description' => 'Kapie pod zlewem w kuchni, potrzebna szybka naprawa.',
            'urgency' => 'week', 'language' => 'pl', 'customer_name' => 'Anna Kowalska', 'customer_email' => 'anna@example.com',
            'customer_locale' => 'pl', 'postal_code' => '90-001', 'city' => 'Łódź', 'lat' => 51.7592, 'lng' => 19.4560,
        ]);
    }

    private function owner(): User
    {
        $owner = $this->demoUser();
        config(['services.marketing_stats.emails' => $owner->email]);

        return $owner;
    }

    public function test_a_company_from_a_public_register_never_receives_e_mail(): void
    {
        $register = $this->provider('Hydro z CEIDG');
        $this->assertFalse(ConsentPolicy::mayEmail($register));
        $this->assertFalse(ConsentPolicy::maySms($register));
        $job = $this->openJob();

        // Automatisch aanbieden: het bedrijf is geen Verified, dus komt niet eens in aanmerking.
        $this->assertSame(0, app(JobService::class)->dispatch($job));
        $this->assertSame('no_match', $job->fresh()->status);

        // Zelfs als het (per ongeluk) Verified zou zijn, gaat er geen mail zonder toestemming.
        $register->forceFill(['status' => 'verified'])->save();
        $job2 = $this->openJob();
        $this->assertSame(0, app(JobService::class)->dispatch($job2), 'Een aanbieding zonder mail telt niet als verstuurd');
        $this->assertNull($job2->offers()->first()?->sent_at);
        $register->forceFill(['status' => 'unclaimed'])->save();

        // De eigenaar kan het ook niet forceren: handmatig aanbieden en uitnodigen slaan het over.
        $owner = $this->owner();
        $job3 = $this->openJob();
        $this->actingAs($owner)->post(route('fachora.admin.jobs.offer', $job3), ['provider_ids' => [$register->id]])->assertRedirect()->assertSessionHas('error');
        $this->assertSame(0, $job3->offers()->count());
        $this->post(route('fachora.admin.invite'), ['ids' => [$register->id]])->assertRedirect()->assertSessionHas('flash');

        // Direct via de Notifier: nee.
        $this->assertFalse(app(Notifier::class)->provider($register, 'invite'));
        $this->assertFalse(app(Notifier::class)->provider($register, 'job', [], $job));

        Mail::assertNotSent(FachoraMail::class, fn (FachoraMail $m) => $m->hasTo($register->email));
        Mail::assertNotSent(FachoraMail::class, fn (FachoraMail $m) => $m->audience === 'provider');
        $this->expectException(\DomainException::class);
        ConsentPolicy::assertMayEmail($register);
    }

    public function test_a_company_with_recorded_opt_in_may_be_invited_once_it_is_on_the_list(): void
    {
        $optIn = $this->provider('Hydro Opt-in', ['source' => 'import', 'consent_at' => now()->subDay(), 'consent_source' => 'lista-partnerska-2026']);
        $noConsent = $this->provider('Hydro Bez Zgody');
        $claimed = $this->provider('Hydro Claimed', ['status' => 'claimed', 'claimed_at' => now()->subDay(), 'consent_at' => now()->subDay(), 'consent_source' => 'claim']);
        $this->assertTrue(ConsentPolicy::mayEmail($optIn));
        $owner = $this->owner();

        $this->actingAs($owner)->post(route('fachora.admin.invite'), ['ids' => [$optIn->id, $noConsent->id, $claimed->id]])
            ->assertRedirect()->assertSessionHas('flash');

        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'invite' && $m->hasTo($optIn->email)
            && str_contains($m->render(), 'lista-partnerska-2026') && str_contains($m->render(), route('fachora.claim', ['firma' => $optIn->slug])) && str_contains($m->render(), $optIn->optoutUrl()));
        Mail::assertNotSent(FachoraMail::class, fn (FachoraMail $m) => $m->hasTo($noConsent->email));
        // Al geclaimd: geen uitnodiging meer.
        Mail::assertNotSent(FachoraMail::class, fn (FachoraMail $m) => $m->hasTo($claimed->email));
        $this->assertSame(1, Mail::sent(FachoraMail::class)->count());

        // Een geclaimd bedrijf mag wél handmatig een opdracht krijgen; het opt-in-bedrijf nog niet (het is niet Verified en niet geclaimd).
        $job = $this->openJob();
        $this->post(route('fachora.admin.jobs.offer', $job), ['provider_ids' => [$claimed->id, $optIn->id, $noConsent->id]])->assertSessionHas('flash');
        $this->assertSame([$claimed->id], $job->offers()->pluck('provider_id')->all());
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'job' && $m->hasTo($claimed->email));
    }

    public function test_the_opt_out_link_blocks_every_further_e_mail(): void
    {
        $claimed = $this->provider('Hydro Claimed', ['status' => 'verified', 'claimed_at' => now()->subDay(), 'consent_at' => now()->subDay(), 'consent_source' => 'claim']);
        $this->assertTrue(ConsentPolicy::mayEmail($claimed));
        $this->assertTrue(app(Notifier::class)->provider($claimed, 'verified'));

        $this->get(route('fachora.optout', $claimed->optout_token))->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Fachora/Claim')->where('mode', 'optout')->where('valid', true)->where('provider.name', 'Hydro Claimed'));

        $claimed->refresh();
        $this->assertNotNull($claimed->email_blocked_at);
        $this->assertFalse(ConsentPolicy::mayEmail($claimed));
        $this->assertFalse(ConsentPolicy::maySms($claimed));
        $this->assertFalse(app(Notifier::class)->provider($claimed, 'job', [], $this->openJob()));
        $this->assertFalse($claimed->canReceiveJobs());
        $this->assertSame(1, Mail::sent(FachoraMail::class)->count(), 'Alleen de mail van vóór de afmelding');

        // Nog eens klikken verandert de datum niet; een onbekend token toont een nette melding.
        $this->travelTo(now()->addDay());
        $blockedAt = $claimed->email_blocked_at;
        $this->get(route('fachora.optout', $claimed->optout_token))->assertInertia(fn (Assert $p) => $p->where('valid', true));
        $this->assertTrue($blockedAt->equalTo($claimed->fresh()->email_blocked_at));
        $this->get(route('fachora.optout', str_repeat('e', 64)))->assertOk()->assertInertia(fn (Assert $p) => $p->where('valid', false)->where('provider', null));

        // Een afgemeld Verified bedrijf krijgt geen opdrachten meer.
        $this->assertSame(0, app(JobService::class)->dispatch($this->openJob()));
    }

    public function test_the_reason_explains_why_a_company_may_or_may_not_be_mailed(): void
    {
        $this->assertSame('Geen e-mailadres', ConsentPolicy::reason($this->provider('Zonder', ['email' => null])));
        $this->assertStringStartsWith('Geen toestemming', ConsentPolicy::reason($this->provider('Register')));
        $this->assertStringStartsWith('Profiel geclaimd op 15 ', ConsentPolicy::reason($this->provider('Claimed', ['claimed_at' => now()->subDay()])));
        $optIn = ConsentPolicy::reason($this->provider('Opt-in', ['consent_at' => now()->subDays(2), 'consent_source' => 'lista-xyz']));
        $this->assertStringStartsWith('Opt-in (lista-xyz) op 14 ', $optIn);
        $blocked = ConsentPolicy::reason($this->provider('Afgemeld', ['claimed_at' => now()->subDay(), 'email_blocked_at' => now()]));
        $this->assertStringStartsWith('Afgemeld op 16 ', $blocked);
        // Zonder e-mailadres wint de afmelding alsnog (de datum blijft de reden).
        $this->assertStringStartsWith('Afgemeld op', ConsentPolicy::reason($this->provider('Afgemeld2', ['email' => null, 'email_blocked_at' => now()])));
    }
}
