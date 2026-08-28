<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/** Eigenaar: overzicht van alle administraties en het definitief opruimen van (test)accounts. */
class OwnerAdministrationsTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    /** De eigenaar (vrijgestelde administratie) + een verlopen proefaccount met data. */
    private function ownerAndExpiredTrial(): array
    {
        config(['services.marketing_stats.emails' => '']);

        $trial = $this->demoUser();                      // volledige administratie met facturen, klanten, offertes
        $trial->company->forceFill(['is_exempt' => false, 'trial_ends_at' => now()->subMonth(), 'subscription_ends_at' => null])->save();

        $company = new Company();
        $company->forceFill(['name' => 'EasyInvoice zelf', 'email' => 'eigenaar@easyinvoice.nl', 'is_exempt' => true])->save();
        $owner = new User();
        $owner->forceFill(['name' => 'Eigenaar', 'email' => 'eigenaar@easyinvoice.nl', 'password' => bcrypt('geheim-wachtwoord'), 'company_id' => $company->id])->save();

        return [$owner, $trial];
    }

    public function test_owner_sees_all_administrations_and_a_customer_does_not(): void
    {
        [$owner, $trial] = $this->ownerAndExpiredTrial();

        $this->actingAs($trial)->get(route('owner.companies.index'))->assertForbidden();
        $this->actingAs($owner)->get(route('owner.companies.index'))->assertOk();
    }

    public function test_owner_can_purge_an_expired_trial_completely(): void
    {
        [$owner, $trial] = $this->ownerAndExpiredTrial();
        $trialCompanyId = $trial->company_id;
        $this->assertGreaterThan(0, Invoice::withoutGlobalScope('company')->where('company_id', $trialCompanyId)->count());

        // Verkeerde naam → niets verwijderd.
        $this->actingAs($owner)->delete(route('owner.companies.destroy', $trialCompanyId), ['confirm' => 'verkeerd'])->assertRedirect();
        $this->assertNotNull(Company::withoutGlobalScope('company')->find($trialCompanyId));

        $this->actingAs($owner)->delete(route('owner.companies.destroy', $trialCompanyId), ['confirm' => $trial->company->name])->assertRedirect();

        $this->assertNull(Company::withoutGlobalScope('company')->find($trialCompanyId));
        $this->assertSame(0, Invoice::withoutGlobalScope('company')->where('company_id', $trialCompanyId)->count());
        $this->assertSame(0, Customer::withoutGlobalScope('company')->where('company_id', $trialCompanyId)->count());
        $this->assertNull(User::find($trial->id));
        $this->assertNotNull(User::find($owner->id));
    }

    public function test_own_and_exempt_administrations_cannot_be_purged(): void
    {
        [$owner] = $this->ownerAndExpiredTrial();

        $this->actingAs($owner)->delete(route('owner.companies.destroy', $owner->company_id), ['confirm' => 'EasyInvoice zelf'])->assertForbidden();
        $this->assertNotNull(Company::withoutGlobalScope('company')->find($owner->company_id));
    }
}
