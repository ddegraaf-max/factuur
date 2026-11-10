<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Support\OwnerAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * 1.111: meerdere administraties onder één inlog kosten niets extra. Een
 * administratie zonder eigen abonnement valt onder het abonnement of de
 * vrijstelling van een zusteradministratie van dezelfde beheerder, en het
 * eigenaarschap van het platform volgt het lidmaatschap (niet de actieve
 * administratie).
 */
class AdministrationCoverageTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    /** Beheerder van een administratie met de gegeven abonnementsvelden; lidmaatschap via de koppeltabel. */
    private function adminOf(array $companyFields, string $email = 'baas@voorbeeld.nl'): User
    {
        $company = new Company();
        $company->forceFill(array_merge(['name' => 'Moederbedrijf BV', 'email' => $email], $companyFields))->save();

        $user = new User();
        $user->forceFill(['name' => 'Baas', 'email' => $email, 'password' => bcrypt('geheim-wachtwoord'), 'company_id' => $company->id, 'role' => 'owner'])->save();
        $user->companies()->attach($company->id, ['role' => 'owner']);

        return $user->fresh();
    }

    private \Illuminate\Testing\TestResponse $storeResponse;

    private function createAdministration(User $user, string $name = 'Dochter BV'): Company
    {
        $this->storeResponse = $this->actingAs($user)
            ->post(route('administrations.store'), ['name' => $name, 'kvk_number' => '87654321', 'vat_number' => '']);
        $this->storeResponse->assertRedirect(route('settings.company'));

        return Company::where('name', $name)->firstOrFail();
    }

    public function test_a_new_administration_of_an_exempt_owner_is_covered_and_shows_no_trial(): void
    {
        config(['services.marketing_stats.emails' => '']);
        $owner = $this->adminOf(['is_exempt' => true]);

        $new = $this->createAdministration($owner);
        $this->storeResponse->assertSessionHas('flash', fn ($flash) => str_contains($flash, 'inbegrepen bij Moederbedrijf BV'));

        $this->assertSame('covered', $new->accessStatus());
        $this->assertTrue($new->hasAccess());
        $this->assertTrue($new->hasAiAccess());
        $this->assertSame('Moederbedrijf BV', $new->coveringCompany()?->name);
        $this->assertNull($new->accessEndsAt(), 'vrijgesteld via de zuster = geen einddatum');
        $this->assertSame('covered', $new->subscriptionSummary()['status']);
        $this->assertSame('Moederbedrijf BV', $new->subscriptionSummary()['covered_by']);

        // De gebruiker is naar de nieuwe administratie gewisseld: geen proefbalk, wel toegang.
        $owner->refresh();
        $this->assertSame($new->id, $owner->company_id);
        $this->get(route('dashboard'))->assertOk()
            ->assertInertia(fn ($page) => $page->where('subscription.status', 'covered')->where('subscription.covered_by', 'Moederbedrijf BV'));

        // En op de administratiespagina heet de moeder niet "verlopen".
        $this->get(route('administrations.index'))->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('administrations.0.subscription.status', 'covered')
                ->where('administrations.1.subscription.status', 'exempt'));
    }

    public function test_owner_pages_stay_reachable_after_switching_to_the_new_administration(): void
    {
        config(['services.marketing_stats.emails' => '']);
        $owner = $this->adminOf(['is_exempt' => true]);

        $this->createAdministration($owner);
        $owner->refresh();

        $this->assertFalse((bool) $owner->company->is_exempt, 'de actieve administratie is de nieuwe, niet-vrijgestelde');
        $this->assertSame($owner->id, OwnerAccess::owner()?->id);
        $this->assertTrue(OwnerAccess::allows($owner));
        $this->actingAs($owner)->get(route('owner.companies.index'))->assertOk();
    }

    public function test_a_paid_subscription_covers_the_sister_and_the_plan_decides_the_ai_access(): void
    {
        $owner = $this->adminOf(['subscription_ends_at' => now()->addMonth(), 'plan' => 'basis', 'trial_ends_at' => now()->subMonth()]);
        $new = $this->createAdministration($owner);
        // Eigen proef voorbij: alleen de dekking telt nog.
        $new->forceFill(['trial_ends_at' => now()->subDay()])->save();
        $new = $new->fresh();

        $this->assertSame('covered', $new->accessStatus());
        $this->assertTrue($new->hasAccess());
        $this->assertFalse($new->hasAiAccess(), 'Basis bij de moeder = geen AI bij de dochter');
        $this->assertTrue($new->accessEndsAt()->isSameDay(now()->addMonth()));

        $owner->company->forceFill(['plan' => 'slim'])->save();
        $this->assertTrue($new->fresh()->hasAiAccess());

        // Loopt het abonnement van de moeder af, dan vervalt de dekking.
        $owner->company->forceFill(['subscription_ends_at' => now()->subDay()])->save();
        $this->assertSame('expired', $new->fresh()->accessStatus());
    }

    public function test_only_the_administrator_role_covers_and_the_trial_reminder_skips_covered_administrations(): void
    {
        Mail::fake();
        $payer = $this->adminOf(['subscription_ends_at' => now()->addMonth(), 'plan' => 'basis', 'trial_ends_at' => now()->subMonth()], 'betaler@voorbeeld.nl');

        // Een boekhouder kijkt mee bij de betaler, maar zijn eigen administratie is daarmee niet gedekt.
        $accountant = $this->adminOf(['trial_ends_at' => now()->addDays(2)], 'boekhouder@voorbeeld.nl');
        $accountant->companies()->attach($payer->company_id, ['role' => 'accountant']);
        $this->assertSame('trialing', $accountant->company->fresh()->accessStatus());
        $this->assertNull($accountant->company->fresh()->coveringCompany());

        // De dochter van de betaler, met een eigen proef die bijna afloopt: geen proefherinnering.
        $daughter = $this->createAdministration($payer);
        $daughter->forceFill(['trial_ends_at' => now()->addDays(2)])->save();

        $this->artisan('trials:remind', ['--days' => 3])->assertSuccessful();

        Mail::assertSent(\App\Mail\TrialEndingMail::class, fn ($mail) => $mail->hasTo('boekhouder@voorbeeld.nl'));
        Mail::assertNotSent(\App\Mail\TrialEndingMail::class, fn ($mail) => $mail->hasTo('betaler@voorbeeld.nl'));
    }
}
