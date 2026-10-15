<?php

namespace Tests\Feature;

use App\Mail\FachoraMail;
use App\Models\Company;
use App\Models\Provider;
use App\Models\ProviderCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Profiel claimen (/przejmij): vanaf het openbare profiel, met de code van
 * een brief, of als nieuw bedrijf. Eerst een mail naar het zelf opgegeven
 * adres; de link daarin maakt de inlog en de eigen administratie (markt pl).
 */
class FachoraClaimTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private const IP = '198.51.100.7';

    /** Geldige NIP (controlecijfer klopt). */
    private const NIP = '1234563218';

    protected function setUp(): void
    {
        parent::setUp();
        config(['brand.active' => 'fachora', 'inertia.testing.ensure_pages_exist' => false, 'services.anthropic.key' => null, 'services.stripe.secret' => null]);
        Mail::fake();
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([], 200),
            'wl-api.mf.gov.pl/*' => Http::response(['result' => ['subject' => [
                'name' => 'HYDRO-MAX JAN NOWAK', 'regon' => '123456789', 'statusVat' => 'Czynny',
                'workingAddress' => 'ul. Prosta 1, 90-001 Łódź', 'accountNumbers' => ['PL61109010140000071219812874'],
            ]]], 200),
            '*' => Http::response([], 200),
        ]);
        $this->travelTo(Carbon::parse('2026-10-16 10:00'));
        $this->withServerVariables(['REMOTE_ADDR' => self::IP]);
    }

    private function provider(string $name, array $attrs = [], array $categories = ['hydraulik']): Provider
    {
        $slug = Str::slug($name).'-'.Str::lower(Str::random(5));
        $provider = Provider::create($attrs + [
            'legal_name' => $name, 'display_name' => $name, 'slug' => $slug, 'status' => 'unclaimed', 'source' => 'ceidg',
            'email' => 'rejestr-'.$slug.'@example.com', 'city' => 'Łódź', 'postal_code' => '90-001', 'address_line' => 'ul. Prosta 1',
            'lat' => 51.7592, 'lng' => 19.4560, 'optout_token' => bin2hex(random_bytes(32)),
        ]);
        $provider->syncCategories($categories);

        return $provider->refresh();
    }

    /** @return array<string, mixed> */
    private function startData(Provider $provider, array $override = []): array
    {
        // 'code' altijd meesturen (zoals het formulier doet): ClaimController::start leest $data['code'] zonder ??.
        return $override + ['provider_id' => $provider->id, 'code' => null, 'name' => 'Jan Nowak', 'email' => 'Jan@HydroMax.pl', 'phone' => '600 100 200'];
    }

    public function test_the_claim_page_shows_the_profile_from_the_slug_or_the_letter_code(): void
    {
        $provider = $this->provider('Hydro-Max', ['claim_code' => 'ABCD2345']);
        $claimed = $this->provider('Al Geclaimd', ['status' => 'claimed', 'claimed_at' => now()->subDay()]);

        $this->get(route('fachora.claim'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Fachora/Claim')->where('mode', 'start')->where('provider', null)->where('codeInvalid', false)->has('categories', count(config('fachora.categories'))));
        $this->get(route('fachora.claim', ['firma' => $provider->slug]))->assertInertia(fn (Assert $p) => $p->where('provider.id', $provider->id)->where('provider.name', 'Hydro-Max')->where('alreadyClaimed', false));
        $this->get(route('fachora.claim', ['firma' => $claimed->slug]))->assertInertia(fn (Assert $p) => $p->where('provider.id', $claimed->id)->where('alreadyClaimed', true));
        $this->get(route('fachora.claim', ['firma' => 'bestaat-niet']))->assertInertia(fn (Assert $p) => $p->where('provider', null));

        // De code van de brief, ook met streepjes en kleine letters.
        $this->get(route('fachora.claim', ['kod' => 'abcd-2345']))->assertInertia(fn (Assert $p) => $p->where('provider.id', $provider->id)->where('code', 'ABCD-2345')->where('codeInvalid', false));
        $this->get(route('fachora.claim', ['kod' => 'ZZZZ9999']))->assertInertia(fn (Assert $p) => $p->where('provider', null)->where('codeInvalid', true));
    }

    public function test_starting_a_claim_mails_the_typed_address_and_never_the_register_address(): void
    {
        $provider = $this->provider('Hydro-Max');

        $this->post(route('fachora.claim.start'), $this->startData($provider))
            ->assertRedirect(route('fachora.claim', ['wyslano' => 1, 'firma' => $provider->slug]))->assertSessionHasNoErrors();

        $provider->refresh();
        $this->assertSame(64, strlen((string) $provider->claim_token));
        $this->assertSame('jan@hydromax.pl', $provider->claim_email);
        $this->assertSame('Jan Nowak', $provider->claim_name);
        $this->assertSame('600 100 200', $provider->claim_phone);
        $this->assertNotNull($provider->claim_requested_at);
        $this->assertSame('unclaimed', $provider->status, 'Tot de klik verandert er niets aan het profiel');
        $this->assertNull($provider->claimed_at);
        $this->assertStringStartsWith('rejestr-', (string) $provider->email);

        Mail::assertSentCount(1);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'claim' && $m->audience === 'provider' && $m->hasTo('jan@hydromax.pl') && str_contains($m->render(), $provider->fresh()->claimUrl()));
        Mail::assertNotSent(FachoraMail::class, fn (FachoraMail $m) => $m->hasTo($provider->email));

        // Lokveld, onbekend bedrijf, al geclaimd, geblokkeerd.
        $this->post(route('fachora.claim.start'), $this->startData($provider, ['website' => 'spam']))->assertRedirect(route('fachora.claim'));
        $this->post(route('fachora.claim.start'), $this->startData($provider, ['provider_id' => 99999]))->assertNotFound();
        $claimed = $this->provider('Geclaimd', ['status' => 'claimed', 'claimed_at' => now()]);
        $this->post(route('fachora.claim.start'), $this->startData($claimed))->assertSessionHasErrors(['email']);
        $blocked = $this->provider('Geblokkeerd', ['status' => 'blocked']);
        $this->post(route('fachora.claim.start'), $this->startData($blocked))->assertSessionHasErrors(['email']);
        $this->post(route('fachora.claim.start'), $this->startData($provider, ['email' => 'geen-adres', 'name' => '']))->assertSessionHasErrors(['email', 'name']);
        Mail::assertSentCount(1);
    }

    public function test_a_letter_code_must_belong_to_the_company(): void
    {
        $provider = $this->provider('Hydro-Max', ['claim_code' => 'ABCD2345']);
        $other = $this->provider('Inny', ['claim_code' => 'WXYZ6789']);

        $this->post(route('fachora.claim.start'), $this->startData($provider, ['code' => 'WXYZ6789']))->assertSessionHasErrors(['code']);
        $this->assertNull($provider->fresh()->claim_token);
        $this->post(route('fachora.claim.start'), $this->startData($provider, ['code' => 'abcd 2345']))->assertSessionHasNoErrors();
        $this->assertNotNull($provider->fresh()->claim_token);
        $this->assertNull($other->fresh()->claim_token);
    }

    public function test_after_five_attempts_per_hour_the_connection_is_limited(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->post(route('fachora.claim.start'), $this->startData($this->provider("Firma {$i}")))->assertSessionHasNoErrors();
        }
        $sixth = $this->provider('Firma 6');
        $this->post(route('fachora.claim.start'), $this->startData($sixth))->assertSessionHasErrors(['email']);
        $this->assertNull($sixth->fresh()->claim_token);
        Mail::assertSentCount(5);
    }

    public function test_the_confirm_page_only_accepts_a_fresh_unused_token(): void
    {
        $provider = $this->provider('Hydro-Max');
        $this->post(route('fachora.claim.start'), $this->startData($provider))->assertSessionHasNoErrors();
        $token = $provider->fresh()->claim_token;

        $this->get(route('fachora.claim.confirm.show', $token))->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Fachora/Claim')->where('mode', 'confirm')->where('valid', true)->where('email', 'jan@hydromax.pl')->where('existingUser', false)->where('provider.name', 'Hydro-Max'));
        $this->get(route('fachora.claim.confirm.show', str_repeat('f', 64)))->assertOk()->assertInertia(fn (Assert $p) => $p->where('valid', false)->where('provider', null));
        $this->post(route('fachora.claim.confirm', str_repeat('f', 64)), ['password' => 'tajne-haslo-123', 'terms' => 1])->assertNotFound();

        // Na zeven dagen is de link verlopen.
        $this->travelTo(Carbon::parse('2026-10-24 10:00'));
        $this->get(route('fachora.claim.confirm.show', $token))->assertInertia(fn (Assert $p) => $p->where('valid', false));
        $this->post(route('fachora.claim.confirm', $token), ['password' => 'tajne-haslo-123', 'terms' => 1])->assertNotFound();
        $this->assertSame('unclaimed', $provider->fresh()->status);
    }

    public function test_confirming_creates_login_and_administration_and_claims_the_profile(): void
    {
        $provider = $this->provider('Hydro-Max', ['nip' => self::NIP, 'legal_name' => 'HYDRO-MAX JAN NOWAK', 'website' => 'https://hydromax.pl']);
        $this->post(route('fachora.claim.start'), $this->startData($provider))->assertSessionHasNoErrors();
        $token = $provider->fresh()->claim_token;

        $this->post(route('fachora.claim.confirm', $token), ['password' => 'tajne-haslo-123'])->assertSessionHasErrors(['terms']);
        $this->post(route('fachora.claim.confirm', $token), ['password' => 'kort', 'terms' => 1])->assertSessionHasErrors(['password']);
        $this->assertGuest();

        $this->post(route('fachora.claim.confirm', $token), ['password' => 'tajne-haslo-123', 'terms' => 1])
            ->assertRedirect(route('fachora.profile'))->assertSessionHas('flash');

        $user = User::where('email', 'jan@hydromax.pl')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Jan Nowak', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->companies()->whereKey($user->company_id)->exists());

        $company = Company::withoutGlobalScope('company')->findOrFail($user->company_id);
        $this->assertSame('HYDRO-MAX JAN NOWAK', $company->name);
        $this->assertSame('Hydro-Max', $company->trading_name);
        $this->assertSame(self::NIP, $company->vat_number);
        $this->assertSame('PL', $company->country);
        $this->assertSame('PLN', $company->currency);
        $this->assertSame('incl', $company->price_mode);
        $this->assertSame('jan@hydromax.pl', $company->email);
        $this->assertSame('Łódź', $company->city);
        $this->assertTrue($company->trial_ends_at->gte(now()->addDays(364)), 'Gratis zolang het bedrijf op Fachora staat');
        $this->assertFalse((bool) $company->is_demo);

        $provider->refresh();
        $this->assertSame('claimed', $provider->status);
        $this->assertNotNull($provider->claimed_at);
        $this->assertNotNull($provider->consent_at);
        $this->assertSame('claim', $provider->consent_source);
        $this->assertSame('jan@hydromax.pl', $provider->email, 'Het e-mailadres van de eigenaar vervangt dat uit het register');
        $this->assertNotNull($provider->email_verified_at);
        $this->assertSame('600 100 200', $provider->phone);
        $this->assertSame($user->id, $provider->user_id);
        $this->assertSame($company->id, $provider->company_id);
        $this->assertNull($provider->claim_token);
        $this->assertSame('ceidg', $provider->source);
        // De automatische controles zijn meteen gedraaid: register/btw via de biała lista, e-mail bevestigd.
        $checks = ProviderCheck::where('provider_id', $provider->id)->pluck('status', 'kind');
        $this->assertSame('passed', $checks['registry']);
        $this->assertSame('passed', $checks['vat']);
        $this->assertSame('passed', $checks['email']);
        $this->assertSame('pending', $checks['phone']);
        $this->assertSame('pending', $checks['terms']);
        $this->assertSame('active', $provider->vat_status);
        $this->assertSame('123456789', $provider->regon);

        // Het panel werkt; de link is opgebruikt.
        $this->get(route('fachora.profile'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Fachora/Provider/Profile')->where('provider.name', 'Hydro-Max')->where('provider.nip', self::NIP)->where('provider.status', 'claimed'));
        $this->get(route('fachora.dashboard'))->assertOk()->assertInertia(fn (Assert $p) => $p->where('provider.id', $provider->id));
        $this->get(route('fachora.claim.confirm.show', $token))->assertInertia(fn (Assert $p) => $p->where('valid', false));
        // Alleen de claimmail; claimen zelf levert geen extra mail op.
        Mail::assertSentCount(1);
    }

    public function test_registering_a_new_company_creates_the_profile_and_starts_the_claim(): void
    {
        $this->get(route('fachora.apply'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Fachora/Claim')->where('mode', 'apply')->has('legalForms'));

        $data = [
            'nip' => '123-456-32-18', 'company_name' => 'Hydro-Max', 'legal_form' => 'jdg', 'city' => 'Łódź', 'postal_code' => '90-001',
            'categories' => ['hydraulik', 'ogrzewanie'], 'name' => 'Jan Nowak', 'email' => 'jan@hydromax.pl', 'phone' => '600100200', 'site' => 'hydromax.pl',
        ];
        $this->post(route('fachora.apply.store'), ['nip' => '1234567890'] + $data)->assertSessionHasErrors(['nip']);
        $this->post(route('fachora.apply.store'), ['categories' => ['kominiarz']] + $data)->assertSessionHasErrors(['categories.0']);
        $this->post(route('fachora.apply.store'), ['website' => 'spam'] + $data)->assertRedirect(route('fachora.apply'));
        $this->assertSame(0, Provider::count());

        $this->post(route('fachora.apply.store'), $data)->assertSessionHasNoErrors()->assertRedirect(route('fachora.claim', ['wyslano' => 1, 'firma' => 'hydro-max-lodz']));

        $provider = Provider::firstOrFail();
        $this->assertSame('unclaimed', $provider->status);
        $this->assertSame('manual', $provider->source);
        $this->assertSame(self::NIP, $provider->nip);
        $this->assertSame('HYDRO-MAX JAN NOWAK', $provider->legal_name, 'Naam uit de biała lista');
        $this->assertSame('Hydro-Max', $provider->display_name);
        $this->assertSame('jdg', $provider->legal_form);
        $this->assertSame('hydromax.pl', $provider->website);
        $this->assertSame('active', $provider->vat_status);
        $this->assertSame(['hydraulik', 'ogrzewanie'], $provider->categories());
        $this->assertSame('jan@hydromax.pl', $provider->claim_email);
        $this->assertNull($provider->email, 'Het register-e-mailadres blijft leeg tot het claimen rond is');
        $this->assertEqualsWithDelta(51.7592, $provider->lat, 0.001);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'claim' && $m->hasTo('jan@hydromax.pl'));

        // Dezelfde NIP nog eens: zolang het niet geclaimd is, loopt het op hetzelfde profiel door.
        $this->post(route('fachora.apply.store'), $data)->assertSessionHasNoErrors();
        $this->assertSame(1, Provider::count());

        // Claimen afronden, en daarna is aanmelden met die NIP niet meer mogelijk.
        $this->post(route('fachora.claim.confirm', $provider->fresh()->claim_token), ['password' => 'tajne-haslo-123', 'terms' => 1])->assertRedirect(route('fachora.profile'));
        $this->assertSame('claimed', $provider->fresh()->source, 'Zelf aangemeld en geclaimd');
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->post(route('fachora.apply.store'), $data)->assertSessionHasErrors(['nip']);
    }

    public function test_an_existing_user_attaches_the_profile_without_a_new_password_or_company(): void
    {
        $user = $this->demoUser();
        $provider = $this->provider('Hydro-Max');
        $this->post(route('fachora.claim.start'), $this->startData($provider, ['email' => mb_strtoupper($user->email)]))->assertSessionHasNoErrors();
        $token = $provider->fresh()->claim_token;
        $this->app['auth']->forgetGuards();

        $this->get(route('fachora.claim.confirm.show', $token))->assertInertia(fn (Assert $p) => $p->where('existingUser', true)->where('email', mb_strtolower($user->email)));
        $companies = Company::withoutGlobalScope('company')->count();

        $this->post(route('fachora.claim.confirm', $token), ['terms' => 1])->assertRedirect(route('fachora.profile'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame($companies, Company::withoutGlobalScope('company')->count(), 'Geen tweede administratie');
        $this->assertSame(1, User::count());
        $provider->refresh();
        $this->assertSame($user->id, $provider->user_id);
        $this->assertSame($user->company_id, $provider->company_id);
        $this->assertSame('claimed', $provider->status);
        $this->assertSame(mb_strtolower($user->email), $provider->email);
    }
}
