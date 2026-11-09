<?php

namespace Tests\Feature;

use App\Mail\IncassoDossierMail;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\PaymentDemand;
use App\Models\ReminderLog;
use App\Models\User;
use App\Services\DailySummaryService;
use App\Services\PaymentDemandService;
use App\Services\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Een pauze loopt niet stilletjes af (1.108.0). Na de einddatum gaat de
 * factuur niet terug in het automatische schema maar naar "Pauze afgelopen":
 * er gaat niets uit (herinnering, aanmaning, incasso) tot de gebruiker hervat
 * of verlengt. De dag ervoor komt één seintje.
 */
class ReminderPauseEndedTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->user = $this->demoUser();
        $this->user->company->forceFill(['is_exempt' => true, 'email' => 'administratie@jansen.test', 'iban' => 'NL91ABNA0417164300'])->save();
    }

    private function overdueInvoice(int $daysOverdue = 3): Invoice
    {
        $invoice = Invoice::regular()->where('company_id', $this->user->company_id)
            ->where('status', 'sent')->whereNotNull('customer_email')->whereNotNull('customer_id')->firstOrFail();
        $invoice->forceFill(['due_date' => now()->subDays($daysOverdue)->toDateString(), 'status' => 'overdue', 'paid_total' => 0])->save();
        ReminderLog::where('invoice_id', $invoice->id)->delete();

        return $invoice->fresh();
    }

    private function remindersSent(Invoice $invoice): int
    {
        return ReminderLog::where('invoice_id', $invoice->id)->count();
    }

    /** Andere demofacturen mogen gewoon post krijgen; deze niet. */
    private function assertNoMailFor(Invoice $invoice): void
    {
        Mail::assertNotSent(\App\Mail\PaymentReminderMail::class, fn ($mail) => $mail->invoice->id === $invoice->id);
        Mail::assertNotSent(\App\Mail\PaymentDemandMail::class);
        Mail::assertNotSent(IncassoDossierMail::class);
    }

    private function logged(Invoice $invoice, string $action): int
    {
        return ActivityLog::withoutGlobalScope('company')
            ->where('subject_type', 'factuur')->where('subject_id', $invoice->id)->where('action', $action)->count();
    }

    public function test_a_pause_without_an_end_date_never_sends_a_reminder(): void
    {
        $invoice = $this->overdueInvoice(3);
        $service = app(ReminderService::class);
        $service->pause($invoice);

        foreach (range(1, 40) as $day) {
            $this->travel(1)->days();
            $service->run();
        }

        $this->assertSame(0, $this->remindersSent($invoice));
        $this->assertTrue($invoice->fresh()->isPaused());
        $this->assertFalse($invoice->fresh()->isPauseEnded());
        $this->assertNoMailFor($invoice);
    }

    public function test_a_pause_that_ended_yesterday_sends_nothing_and_waits_with_a_notice(): void
    {
        $invoice = $this->overdueInvoice(12);
        $invoice->forceFill([
            'reminders_paused_at' => now()->subDays(10),
            'reminders_paused_until' => now()->subDay()->toDateString(),
        ])->save();
        $service = app(ReminderService::class);

        $service->run();
        $service->run();

        $invoice->refresh();
        $this->assertSame(0, $this->remindersSent($invoice), 'Een afgelopen pauze laat geen post vertrekken');
        $this->assertNoMailFor($invoice);
        $this->assertTrue($invoice->isPaused());
        $this->assertTrue($invoice->isPauseEnded());
        $this->assertNotNull($invoice->reminders_pause_ended_at);
        $this->assertSame(1, $this->logged($invoice, 'pause_ended'), 'De melding komt één keer');

        // Zichtbaar: in de lijst (filter Op pauze), op de factuur en op het dashboard.
        $this->actingAs($this->user)
            ->get(route('invoices.index', ['status' => 'paused']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('counts.paused', 1)
                ->where('counts.pause_ended', 1)
                ->where('invoices.data.0.id', $invoice->id)
                ->where('invoices.data.0.paused', true)
                ->where('invoices.data.0.pause_ended', true));

        $this->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('invoice.reminders_paused', true)
                ->where('invoice.reminders_pause_ended', true));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('kpis.pause_ended_count', 1)
                ->where('pause_notices.0.id', $invoice->id)
                ->where('pause_notices.0.ended', true));

        // Ook een dag later nog steeds niets.
        $this->travel(1)->days();
        $service->run();
        $this->assertSame(0, $this->remindersSent($invoice));
        $this->assertSame(1, $this->logged($invoice, 'pause_ended'));
    }

    public function test_after_resume_the_next_step_follows_at_the_same_distance(): void
    {
        $invoice = $this->overdueInvoice(3);
        $invoice->forceFill([
            'reminders_paused_at' => now()->subDays(5),
            'reminders_paused_until' => now()->subDay()->toDateString(),
        ])->save();
        $service = app(ReminderService::class);

        // Vier dagen blijft hij stilstaan; daarna hervat de gebruiker.
        foreach (range(1, 4) as $day) {
            $service->run();
            $this->travel(1)->days();
        }
        $this->assertSame(0, $this->remindersSent($invoice));

        $this->actingAs($this->user)->delete(route('invoices.resume', $invoice))->assertSessionHasNoErrors();
        $invoice->refresh();
        $this->assertFalse($invoice->remindersPaused());
        $this->assertNull($invoice->reminders_pause_ended_at);
        // Van de vervaldatum tot vandaag: 3 + 4 dagen.
        $this->assertSame(7, $invoice->reminder_shift_days);

        // Geen inhaalslag: vandaag niets, de eerste herinnering op dezelfde afstand als vóór de pauze.
        $service->run();
        $this->assertSame(0, $this->remindersSent($invoice));
        $this->travel(1)->days();
        $service->run();
        $service->run();
        $this->assertSame(1, $this->remindersSent($invoice));
    }

    public function test_the_day_before_notice_comes_once(): void
    {
        $invoice = $this->overdueInvoice(3);
        $service = app(ReminderService::class);
        $service->pause($invoice, now()->addDay());

        $service->run();
        $service->run();
        $this->assertSame(1, $this->logged($invoice, 'pause_ending'));
        $this->assertNotNull($invoice->fresh()->reminders_pause_warned_at);
        $this->assertStringContainsString('loopt morgen af', ActivityLog::withoutGlobalScope('company')->where('action', 'pause_ending')->value('description'));

        // In de dagelijkse samenvatting en op de factuur.
        $summary = app(DailySummaryService::class)->gather($this->user->company);
        $this->assertTrue($summary['has_news']);
        $this->assertCount(1, $summary['pauses']);
        $this->assertFalse($summary['pauses'][0]['ended']);
        $this->assertStringContainsString('loopt morgen af', $summary['pauses'][0]['text']);
        $this->actingAs($this->user)->get(route('invoices.show', $invoice))->assertOk()
            ->assertInertia(fn ($page) => $page->where('invoice.reminders_pause_ends_soon', true)->where('invoice.reminders_pause_ends_tomorrow', true));

        // De einddag zelf: geen tweede seintje; de dag erna: afgelopen, nog steeds één seintje.
        $this->travel(1)->days();
        $service->run();
        $this->assertSame(1, $this->logged($invoice, 'pause_ending'));
        $this->assertFalse($invoice->fresh()->isPauseEnded());

        $this->travel(1)->days();
        $service->run();
        $service->run();
        $this->assertSame(1, $this->logged($invoice, 'pause_ending'));
        $this->assertSame(1, $this->logged($invoice, 'pause_ended'));
        $this->assertTrue($invoice->fresh()->isPauseEnded());
        $this->assertSame(0, $this->remindersSent($invoice));

        $summary = app(DailySummaryService::class)->gather($this->user->company);
        $this->assertTrue($summary['pauses'][0]['ended']);
    }

    public function test_extending_an_ended_pause_keeps_it_running_and_gives_a_new_notice(): void
    {
        $invoice = $this->overdueInvoice(3);
        $service = app(ReminderService::class);
        $service->pause($invoice, now()->addDay());
        $service->run();
        $startedAt = $invoice->fresh()->reminders_paused_at;
        $this->travel(2)->days();
        $service->run();
        $this->assertTrue($invoice->fresh()->isPauseEnded());

        $until = now()->addDays(7)->toDateString();
        $this->actingAs($this->user)
            ->post(route('invoices.pause', $invoice), ['until' => $until])
            ->assertSessionHasNoErrors();

        $invoice->refresh();
        $this->assertFalse($invoice->isPauseEnded());
        $this->assertTrue($invoice->isPaused());
        $this->assertNull($invoice->reminders_pause_ended_at);
        $this->assertNull($invoice->reminders_pause_warned_at, 'Nieuwe einddatum, nieuw seintje');
        $this->assertTrue($startedAt->equalTo($invoice->reminders_paused_at), 'Verlengen houdt de start van de pauze vast');

        $this->travel(6)->days();
        $service->run();
        $this->assertSame(2, $this->logged($invoice, 'pause_ending'));
        $this->assertSame(0, $this->remindersSent($invoice));
    }

    public function test_after_an_ended_pause_nothing_goes_to_incasso_or_a_demand_without_approval(): void
    {
        $invoice = $this->overdueInvoice(40);
        $invoice->forceFill([
            'reminders_paused_at' => now()->subDays(10),
            'reminders_paused_until' => now()->subDay()->toDateString(),
        ])->save();
        app(ReminderService::class)->run();

        $this->actingAs($this->user);
        $this->post(route('invoices.remind', $invoice))->assertSessionHasErrors('reminder');
        $this->post(route('incasso.send', $invoice))->assertSessionHasErrors('incasso');
        $this->assertSame('overdue', $invoice->fresh()->status);
        $this->assertNotNull(app(PaymentDemandService::class)->blocker($invoice->fresh()));
        $this->assertStringContainsString('afgelopen', app(PaymentDemandService::class)->blocker($invoice->fresh()));
        $this->assertNoMailFor($invoice);

        // Na akkoord kan het weer.
        $this->delete(route('invoices.resume', $invoice))->assertSessionHasNoErrors();
        $this->assertNull(app(PaymentDemandService::class)->blocker($invoice->fresh()));
    }

    public function test_a_promised_date_ends_without_the_file_going_over_by_itself(): void
    {
        // Akkoord met de toegezegde dag: pauze t/m die dag. Daarna gaat het
        // dossier niet vanzelf naar de deurwaarder, ook niet met automatische overdracht.
        $this->travelTo(Carbon::parse('2026-10-05 09:00'));
        $invoice = $this->overdueInvoice(40);
        $this->actingAs($this->user)->post(route('demands.store', $invoice), ['auto_transfer' => true])->assertSessionHasNoErrors();
        $demand = PaymentDemand::firstOrFail();
        app(ReminderService::class)->pause($invoice->fresh(), Carbon::parse('2026-10-14'), 'Betaling toegezegd via de online aanmaning');

        foreach (['2026-10-13', '2026-10-15', '2026-10-16', '2026-10-20'] as $day) {
            $this->travelTo(Carbon::parse($day . ' 08:00'));
            $this->artisan('invoices:remind')->assertSuccessful();
            $this->travelTo(Carbon::parse($day . ' 08:30'));
            $this->artisan('demands:notify')->assertSuccessful();
        }

        $this->assertTrue($invoice->fresh()->isPauseEnded());
        $this->assertSame('overdue', $invoice->fresh()->status);
        $this->assertSame('sent', $demand->fresh()->status);
        Mail::assertNotSent(IncassoDossierMail::class);
        $this->assertStringContainsString('afgelopen', app(PaymentDemandService::class)->autoTransferBlocker($demand->fresh()));
    }
}
