<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\ReminderLog;
use App\Models\User;
use App\Services\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Pauzeknop op de factuur: zolang de pauze loopt gaat er geen herinnering of
 * aanmaning uit (automatisch noch handmatig) en kan de factuur niet naar
 * incasso. Na de pauze schuift het schema op, zodat de klant geen inhaalslag
 * van dagelijkse berichten krijgt.
 */
class ReminderPauseTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->user = $this->demoUser();
        $this->user->company->forceFill(['is_exempt' => true])->save();
    }

    private function overdueInvoice(int $daysOverdue = 3): Invoice
    {
        $invoice = Invoice::regular()->where('company_id', $this->user->company_id)
            ->where('status', 'sent')->whereNotNull('customer_email')->firstOrFail();
        $invoice->forceFill(['due_date' => now()->subDays($daysOverdue)->toDateString(), 'status' => 'overdue'])->save();
        ReminderLog::where('invoice_id', $invoice->id)->delete();

        return $invoice;
    }

    private function remindersSent(Invoice $invoice): int
    {
        return ReminderLog::where('invoice_id', $invoice->id)->count();
    }

    public function test_a_paused_invoice_gets_no_automatic_reminder(): void
    {
        $invoice = $this->overdueInvoice();
        $service = app(ReminderService::class);

        $service->pause($invoice, null, 'Betalingsregeling afgesproken');
        $service->run();

        $this->assertTrue($invoice->fresh()->remindersPaused());
        $this->assertSame(0, $this->remindersSent($invoice));

        // Dezelfde dag hervat: er is niets opgeschoven, de herinnering gaat alsnog uit.
        $service->resume($invoice->fresh());
        $service->run();

        $this->assertFalse($invoice->fresh()->remindersPaused());
        $this->assertSame(0, $invoice->fresh()->reminder_shift_days);
        $this->assertSame(1, $this->remindersSent($invoice));
    }

    public function test_the_schedule_shifts_by_the_length_of_the_pause(): void
    {
        $invoice = $this->overdueInvoice(12);
        $invoice->forceFill(['reminders_paused_at' => now()->subDays(10)])->save();
        $service = app(ReminderService::class);

        $service->resume($invoice->fresh());
        $this->assertSame(10, $invoice->fresh()->reminder_shift_days);

        // Zonder opschuiven zouden de eerste én de tweede herinnering al over tijd
        // zijn en op twee opeenvolgende runs de deur uit gaan.
        $service->run();
        $service->run();
        $this->assertSame(1, $this->remindersSent($invoice));

        $this->travel(3)->days();
        $service->run();
        $this->assertSame(2, $this->remindersSent($invoice));
    }

    public function test_a_pause_with_an_end_date_lifts_itself(): void
    {
        $invoice = $this->overdueInvoice(3);
        $invoice->forceFill([
            'reminders_paused_at' => now()->subDays(5),
            'reminders_paused_until' => now()->subDay()->toDateString(),
            'reminders_pause_reason' => 'Klacht in behandeling',
        ])->save();
        $this->assertFalse($invoice->fresh()->remindersPaused());
        $service = app(ReminderService::class);

        $service->run();

        $invoice->refresh();
        $this->assertNull($invoice->reminders_paused_at);
        $this->assertNull($invoice->reminders_pause_reason);
        $this->assertSame(3, $invoice->reminder_shift_days);
        $this->assertSame(0, $this->remindersSent($invoice), 'De eerste herinnering volgt pas de dag na de pauze.');

        $this->travel(1)->days();
        $service->run();
        $this->assertSame(1, $this->remindersSent($invoice));
    }

    public function test_pausing_through_today_still_counts_as_paused(): void
    {
        $invoice = $this->overdueInvoice();
        app(ReminderService::class)->pause($invoice, now());

        app(ReminderService::class)->run();

        $this->assertTrue($invoice->fresh()->remindersPaused());
        $this->assertSame(0, $this->remindersSent($invoice));
    }

    public function test_pause_and_resume_from_the_invoice_page(): void
    {
        $invoice = $this->overdueInvoice();
        $until = now()->addDays(14)->toDateString();

        $this->actingAs($this->user)
            ->post(route('invoices.pause', $invoice), ['until' => $until, 'reason' => 'Betalingsregeling: 3 termijnen'])
            ->assertSessionHasNoErrors();

        $invoice->refresh();
        $this->assertTrue($invoice->remindersPaused());
        $this->assertSame($until, $invoice->reminders_paused_until->toDateString());
        $this->assertSame('Betalingsregeling: 3 termijnen', $invoice->reminders_pause_reason);

        $this->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('invoice.reminders_paused', true)
                ->where('invoice.reminders_paused_until', $until));

        // Einddatum aanpassen op een lopende pauze: de start blijft staan.
        $startedAt = $invoice->reminders_paused_at;
        $this->post(route('invoices.pause', $invoice), ['until' => null, 'reason' => null])->assertSessionHasNoErrors();
        $invoice->refresh();
        $this->assertNull($invoice->reminders_paused_until);
        $this->assertTrue($startedAt->equalTo($invoice->reminders_paused_at));

        $this->delete(route('invoices.resume', $invoice))->assertSessionHasNoErrors();
        $this->assertFalse($invoice->fresh()->remindersPaused());
    }

    public function test_an_end_date_in_the_past_is_refused(): void
    {
        $invoice = $this->overdueInvoice();

        $this->actingAs($this->user)
            ->post(route('invoices.pause', $invoice), ['until' => now()->subDay()->toDateString()])
            ->assertSessionHasErrors('until');

        $this->assertFalse($invoice->fresh()->remindersPaused());
    }

    public function test_manual_reminder_and_incasso_are_blocked_while_paused(): void
    {
        $invoice = $this->overdueInvoice();
        app(ReminderService::class)->pause($invoice);

        $this->actingAs($this->user)
            ->post(route('invoices.remind', $invoice))
            ->assertSessionHasErrors('reminder');
        $this->assertSame(0, $this->remindersSent($invoice));

        $this->post(route('incasso.send', $invoice))->assertSessionHasErrors('incasso');
        $this->assertSame('overdue', $invoice->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_paused_shows_as_a_status_in_the_lists(): void
    {
        $invoice = $this->overdueInvoice();
        app(ReminderService::class)->pause($invoice, now()->addWeek());
        $overdue = Invoice::regular()->where('company_id', $this->user->company_id)->where('status', 'overdue')->count();

        $this->actingAs($this->user)
            ->get(route('invoices.index', ['status' => 'paused']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('counts.paused', 1)
                ->has('invoices.data', 1)
                ->where('invoices.data.0.id', $invoice->id)
                ->where('invoices.data.0.paused', true)
                ->where('invoices.data.0.status', 'overdue'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('kpis.overdue_count', $overdue)
                ->where('kpis.overdue_paused_count', 1));

        // Betaald is betaald: de pauze telt dan niet meer als status.
        $invoice->forceFill(['status' => 'paid', 'paid_total' => $invoice->total])->save();
        $this->assertFalse($invoice->fresh()->isPaused());
        $this->assertSame(0, Invoice::paused()->where('company_id', $this->user->company_id)->count());
    }

    public function test_a_lapsed_pause_is_no_longer_a_status(): void
    {
        $invoice = $this->overdueInvoice();
        $invoice->forceFill([
            'reminders_paused_at' => now()->subDays(5),
            'reminders_paused_until' => now()->subDay()->toDateString(),
        ])->save();

        $this->assertFalse($invoice->fresh()->isPaused());
        $this->assertSame(0, Invoice::paused()->where('company_id', $this->user->company_id)->count());
    }

    public function test_a_paid_invoice_cannot_be_paused(): void
    {
        $invoice = Invoice::regular()->where('company_id', $this->user->company_id)->where('status', 'paid')->firstOrFail();

        $this->actingAs($this->user)
            ->post(route('invoices.pause', $invoice))
            ->assertSessionHasErrors('reminder');

        $this->assertNull($invoice->fresh()->reminders_paused_at);
    }

    public function test_another_administration_cannot_pause_my_invoice(): void
    {
        $invoice = $this->overdueInvoice();
        $stranger = $this->demoUser();

        $this->actingAs($stranger)->post(route('invoices.pause', $invoice))->assertNotFound();

        $this->assertNull($invoice->fresh()->reminders_paused_at);
    }
}
