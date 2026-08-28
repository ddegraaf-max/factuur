<?php

namespace Tests\Feature;

use App\Mail\PaymentReminderMail;
use App\Models\Invoice;
use App\Models\User;
use App\Services\ReminderService;
use App\Support\OwnerAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Geplande taken: automatische post gaat alleen uit naam van administraties
 * die nog toegang hebben (proefperiode of abonnement) — verlopen testaccounts
 * mogen nooit herinneringen naar (echte) klantadressen sturen.
 */
class ScheduledTasksTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private function overdueInvoice(): Invoice
    {
        $invoice = Invoice::regular()->where('status', 'sent')->whereNotNull('customer_email')->firstOrFail();
        $invoice->forceFill(['due_date' => now()->subDays(3)->toDateString(), 'status' => 'overdue'])->save();

        return $invoice;
    }

    public function test_reminders_go_out_for_an_active_administration(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $user->company->forceFill(['is_exempt' => true])->save();
        $invoice = $this->overdueInvoice();

        $sent = app(ReminderService::class)->run();

        $this->assertGreaterThanOrEqual(1, $sent);
        Mail::assertSent(PaymentReminderMail::class, fn ($mail) => $mail->hasTo($invoice->customer_email));
    }

    public function test_reminders_are_skipped_for_an_expired_trial(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $user->company->forceFill(['is_exempt' => false, 'trial_ends_at' => now()->subWeek(), 'subscription_ends_at' => null])->save();
        $this->overdueInvoice();

        $sent = app(ReminderService::class)->run();

        $this->assertSame(0, $sent);
        Mail::assertNothingSent();
    }

    /** De eigenaar is de gebruiker van de vrijgestelde administratie — niet "toevallig id 1". */
    public function test_owner_is_the_user_of_the_exempt_administration(): void
    {
        config(['services.marketing_stats.emails' => '']);
        $first = $this->demoUser();                       // eerste gebruiker: gewoon een klant
        $first->company->forceFill(['is_exempt' => false])->save();

        $company = new \App\Models\Company();
        $company->forceFill(['name' => 'EasyInvoice zelf', 'email' => 'eigenaar@easyinvoice.nl', 'is_exempt' => true])->save();
        $owner = new User();
        $owner->forceFill(['name' => 'Eigenaar', 'email' => 'eigenaar@easyinvoice.nl', 'password' => bcrypt('geheim-wachtwoord'), 'company_id' => $company->id])->save();

        $this->assertSame($owner->id, OwnerAccess::owner()?->id);
        $this->assertTrue(OwnerAccess::allows($owner));
        $this->assertFalse(OwnerAccess::allows($first));
        $this->assertSame(['eigenaar@easyinvoice.nl'], OwnerAccess::emails());
    }
}
