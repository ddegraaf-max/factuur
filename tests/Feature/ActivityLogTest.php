<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/** Logboek: wijzigingen, verzendingen en aanmeldingen worden vastgelegd en zijn per administratie afgeschermd. */
class ActivityLogTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_changes_and_sends_are_logged_with_the_user_and_the_changed_fields(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        $companyId = $user->company_id;

        $customer = Customer::orderBy('id')->firstOrFail();
        $this->patch(route('customers.update', $customer), array_merge($customer->only(['name', 'email', 'address_line', 'postal_code', 'city', 'country']), ['name' => 'Nieuwe Naam BV']))->assertRedirect();

        $log = ActivityLog::withoutGlobalScope('company')->where('company_id', $companyId)->where('action', 'updated')->where('subject_type', 'klant')->latest('id')->first();
        $this->assertNotNull($log, 'klantwijziging niet gelogd');
        $this->assertSame($user->name, $log->user_name);
        $this->assertSame('Nieuwe Naam BV', $log->changes['name']['naar']);

        $this->post(route('invoices.store'), [
            'customer_id' => $customer->id, 'invoice_date' => now()->toDateString(), 'payment_terms' => 14,
            'lines' => [['description' => 'Advies', 'quantity' => 1, 'unit' => 'uur', 'unit_price' => 100, 'vat_rate' => 21, 'discount_pct' => 0]],
            'action' => 'draft',
        ])->assertRedirect();
        $draft = Invoice::where('status', 'draft')->latest('id')->firstOrFail();
        $this->assertTrue(ActivityLog::withoutGlobalScope('company')->where('action', 'created')->where('subject_type', 'factuur')->where('subject_id', $draft->id)->exists());

        $this->post(route('invoices.send', $draft))->assertRedirect();
        $this->assertTrue(ActivityLog::withoutGlobalScope('company')->where('action', 'sent')->where('subject_type', 'factuur')->where('subject_id', $draft->id)->exists());

        $this->get(route('settings.activity'))->assertOk();
        $this->get(route('settings.activity.export'))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_login_is_logged_and_logs_are_scoped_per_administration(): void
    {
        $user = $this->demoUser();
        $user->forceFill(['password' => bcrypt('wachtwoord-123'), 'email_verified_at' => now(), 'two_factor_secret' => null])->save();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'wachtwoord-123']);
        $this->assertTrue(ActivityLog::withoutGlobalScope('company')->where('company_id', $user->company_id)->where('action', 'login')->exists());

        // Een andere administratie ziet dit niet.
        $other = new \App\Models\Company();
        $other->forceFill(['name' => 'Andere BV'])->save();
        $stranger = new \App\Models\User();
        $stranger->forceFill(['name' => 'Vreemde', 'email' => 'vreemde@example.com', 'password' => bcrypt('x-y-z-1234'), 'company_id' => $other->id])->save();
        $this->actingAs($stranger);
        $this->assertSame(0, ActivityLog::where('action', 'login')->where('company_id', $user->company_id)->count());
    }
}
