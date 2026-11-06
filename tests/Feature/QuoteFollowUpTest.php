<?php

namespace Tests\Feature;

use App\Mail\QuoteMail;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\SmsMessage;
use App\Services\QuoteFollowUpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * 1.102: offerteopvolging — tijdlijn op de offerte (verstuurd, bekeken, herinnering 1 en 2, verloopt),
 * automatische herinneringen aan de klant per mail of sms, aan/uit per offerte en "Nu herinneren".
 */
class QuoteFollowUpTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private \App\Models\User $user;

    private function enableSms(int $companyId): void
    {
        config([
            'services.smstools.client_id' => 'test-id',
            'services.smstools.client_secret' => 'test-secret',
            'services.smstools.companies' => (string) $companyId,
        ]);
        Http::fake(['api.smsgatewayapi.com/*' => Http::response(['messageid' => 'h2md1ewkyzjkuyn9ak7pryw1evtyw3x'], 200)]);
    }

    private function draftQuote(Customer $customer, string $reference, int $validDays = 30): Quote
    {
        $this->post(route('quotes.store'), [
            'customer_id' => $customer->id, 'quote_date' => now()->toDateString(), 'valid_days' => $validDays, 'action' => 'draft', 'reference' => $reference,
            'lines' => [['description' => 'Dakgoot vervangen', 'quantity' => 1, 'unit' => 'stuk', 'unit_price' => 450, 'vat_rate' => 21, 'discount_pct' => 0]],
        ])->assertRedirect();

        return Quote::where('reference', $reference)->firstOrFail();
    }

    /** Verstuurde offerte per mail aan een klant met e-mailadres, op een vaste datum. */
    private function sentQuote(string $reference = 'Opvolging'): Quote
    {
        $this->travelTo('2026-11-04 10:00:00');
        $user = $this->user = $this->demoUser();
        $this->actingAs($user);
        $customer = Customer::orderBy('id')->firstOrFail();
        $customer->update(['email' => 'klant@example.com', 'phone' => '06 12345678']);
        $quote = $this->draftQuote($customer, $reference);
        $this->post(route('quotes.send', $quote))->assertRedirect();
        $quote->refresh();
        $this->assertSame('sent', $quote->status);
        $this->onlyThisOne($quote);

        return $quote;
    }

    /** De verstuurde demo-offertes uit de seed doen niet mee met de planner. */
    private function onlyThisOne(Quote $quote): void
    {
        Quote::withoutGlobalScope('company')->where('id', '!=', $quote->id)->update(['reminders_enabled' => false]);
    }

    public function test_the_quote_page_shows_a_timeline_with_two_planned_reminders(): void
    {
        Mail::fake();
        $quote = $this->sentQuote();

        $this->get(route('quotes.show', $quote))->assertOk()->assertInertia(fn ($page) => $page
            ->has('quote.follow_up.steps', 5)
            ->where('quote.follow_up.steps.0.key', 'sent')->where('quote.follow_up.steps.0.state', 'done')
            ->where('quote.follow_up.steps.1.key', 'viewed')->where('quote.follow_up.steps.1.state', 'pending')
            ->where('quote.follow_up.steps.2.state', 'next')->where('quote.follow_up.steps.2.date', '7 nov')
            ->where('quote.follow_up.steps.3.state', 'planned')->where('quote.follow_up.steps.3.date', '2 dec')
            ->where('quote.follow_up.steps.4.key', 'expires')->where('quote.follow_up.steps.4.date', '4 dec')
            ->where('quote.follow_up.enabled', true)->where('quote.follow_up.company_enabled', true)
            ->where('quote.follow_up.next', '7 nov')->where('quote.follow_up.count', 0)->where('quote.follow_up.can_remind', true));

        // Een concept heeft geen opvolging.
        $draft = $this->draftQuote(Customer::orderBy('id')->firstOrFail(), 'Concept');
        $this->get(route('quotes.show', $draft))->assertOk()->assertInertia(fn ($page) => $page->where('quote.follow_up', null));
    }

    public function test_scheduled_reminders_go_out_by_mail_on_the_planned_days_and_then_stop(): void
    {
        Mail::fake();
        $quote = $this->sentQuote();
        Mail::assertSent(QuoteMail::class, 1);

        // Dag 2: nog niets.
        $this->travelTo('2026-11-06 09:30:00');
        $this->artisan('quotes:remind')->assertSuccessful();
        Mail::assertSent(QuoteMail::class, 1);

        // Dag 3: herinnering 1, met de PDF en de herinneringstekst; niet nog eens op dezelfde dag.
        $this->travelTo('2026-11-07 09:30:00');
        $this->artisan('quotes:remind')->assertSuccessful();
        Mail::assertSent(QuoteMail::class, fn (QuoteMail $m) => $m->reminder && $m->hasTo('klant@example.com'));
        Mail::assertSent(QuoteMail::class, 2);
        $this->artisan('quotes:remind')->assertSuccessful();
        Mail::assertSent(QuoteMail::class, 2);
        $quote->refresh();
        $this->assertCount(1, $quote->reminder_log);
        $this->assertSame('mail', $quote->reminder_log[0]['channel']);
        $this->assertSame('auto', $quote->reminder_log[0]['trigger']);
        $this->assertTrue(ActivityLog::withoutGlobalScope('company')->where('action', 'reminded')->where('subject_id', $quote->id)->exists(), 'herinnering in het logboek');

        // Tussendoor niets; 2 dagen vóór de vervaldatum herinnering 2; daarna nooit meer.
        $this->travelTo('2026-11-20 09:30:00');
        $this->artisan('quotes:remind')->assertSuccessful();
        Mail::assertSent(QuoteMail::class, 2);
        $this->travelTo('2026-12-02 09:30:00');
        $this->artisan('quotes:remind')->assertSuccessful();
        Mail::assertSent(QuoteMail::class, 3);
        $this->travelTo('2026-12-03 09:30:00');
        $this->artisan('quotes:remind')->assertSuccessful();
        Mail::assertSent(QuoteMail::class, 3);
        $quote->refresh();
        $this->assertCount(2, $quote->reminder_log);
        $this->assertNull(app(QuoteFollowUpService::class)->nextReminderAt($quote));

        // De mail zelf: ander onderwerp en een herinneringsintro, de offerte als bijlage.
        $this->travelTo('2026-11-07 09:30:00');
        $mail = new QuoteMail($quote->fresh()->load('lines'), 'pdf-bytes', reminder: true);
        $this->assertStringStartsWith('Herinnering: offerte '.$quote->number, $mail->envelope()->subject);
        $html = $mail->render();
        $this->assertStringContainsString('staat nog open', $html);
        $this->assertStringContainsString('4 december 2026', $html);
    }

    public function test_reminders_stop_when_the_customer_decides_and_follow_the_company_settings(): void
    {
        Mail::fake();
        $quote = $this->sentQuote();

        // Instellingen: eerste herinnering na 1 dag, tweede 5 dagen voor het verlopen.
        $this->patch(route('settings.emails.update'), ['reminders_enabled' => true, 'reminder_first_after_days' => 1, 'reminder_second_before_expiry_days' => 5])->assertRedirect();
        $this->assertSame(['enabled' => true, 'first_after_days' => 1, 'second_before_expiry_days' => 5], $quote->company->fresh()->quoteReminderSettings());
        $this->get(route('settings.emails'))->assertOk()->assertInertia(fn ($page) => $page->where('quote_reminders.first_after_days', 1));
        $this->get(route('quotes.show', $quote))->assertOk()->assertInertia(fn ($page) => $page
            ->where('quote.follow_up.steps.2.date', '5 nov')->where('quote.follow_up.steps.3.date', '29 nov'));

        // Geaccepteerd: er gaat niets meer uit.
        $this->post(route('quotes.accept', $quote), ['send_confirmation' => false])->assertRedirect();
        $this->travelTo('2026-11-05 09:30:00');
        $this->artisan('quotes:remind')->assertSuccessful();
        Mail::assertSent(QuoteMail::class, 1);

        // Uitgezet bij de instellingen: ook niets, en het scherm zegt waarom.
        $other = $this->sentQuote('Tweede');
        $this->patch(route('settings.emails.update'), ['reminders_enabled' => false])->assertRedirect();
        $this->travelTo('2026-11-07 09:30:00');
        $this->artisan('quotes:remind')->assertSuccessful();
        Mail::assertSent(QuoteMail::class, 2);
        $this->get(route('quotes.show', $other))->assertOk()->assertInertia(fn ($page) => $page
            ->where('quote.follow_up.company_enabled', false)->where('quote.follow_up.next', null)->where('quote.follow_up.steps.2.state', 'skipped'));
    }

    public function test_manual_reminder_and_switching_reminders_off_per_quote(): void
    {
        Mail::fake();
        $quote = $this->sentQuote();

        // Nu herinneren: direct een mail, geteld als herinnering 1 (handmatig).
        $this->post(route('quotes.remind', $quote))->assertRedirect()->assertSessionHas('flash');
        Mail::assertSent(QuoteMail::class, fn (QuoteMail $m) => $m->reminder);
        $quote->refresh();
        $this->assertCount(1, $quote->reminder_log);
        $this->assertSame('manual', $quote->reminder_log[0]['trigger']);
        $this->get(route('quotes.show', $quote))->assertOk()->assertInertia(fn ($page) => $page
            ->where('quote.follow_up.count', 1)->where('quote.follow_up.steps.2.state', 'done')->where('quote.follow_up.steps.2.trigger', 'manual')
            ->where('quote.follow_up.steps.3.state', 'next')->where('quote.follow_up.next', '2 dec'));

        // Uit voor deze offerte: de planner slaat hem over; weer aan: de tweede komt alsnog.
        $this->post(route('quotes.reminders', $quote), ['enabled' => false])->assertRedirect();
        $this->assertFalse($quote->fresh()->reminders_enabled);
        $this->travelTo('2026-12-02 09:30:00');
        $this->artisan('quotes:remind')->assertSuccessful();
        Mail::assertSent(QuoteMail::class, 2);
        $this->travelTo('2026-11-05 10:00:00'); // de dag erna weer aanzetten (na een maand reizen is de sessie verlopen)
        $this->post(route('quotes.reminders', $quote), ['enabled' => true])->assertRedirect();
        $this->assertTrue($quote->fresh()->reminders_enabled);
        $this->travelTo('2026-12-02 09:30:00');
        $this->artisan('quotes:remind')->assertSuccessful();
        Mail::assertSent(QuoteMail::class, 3);
        $this->assertCount(2, $quote->fresh()->reminder_log);

        // Alleen bij een verstuurde offerte.
        $this->travelTo('2026-11-04 11:00:00');
        $draft = $this->draftQuote(Customer::orderBy('id')->firstOrFail(), 'Concept');
        $this->post(route('quotes.remind', $draft))->assertRedirect()->assertSessionHasErrors('quote');
    }

    public function test_a_customer_with_only_a_mobile_number_is_reminded_by_sms(): void
    {
        Mail::fake();
        $this->travelTo('2026-11-04 10:00:00');
        $user = $this->demoUser();
        $this->actingAs($user);
        $this->enableSms($user->company_id);
        $customer = Customer::orderBy('id')->firstOrFail();
        $customer->update(['phone' => '06 12345678', 'email' => null]);
        $quote = $this->draftQuote($customer, 'Sms-opvolging');
        $this->post(route('quotes.sms', $quote))->assertRedirect();
        $this->assertSame('sent', $quote->fresh()->status);
        $this->onlyThisOne($quote);

        $this->travelTo('2026-11-07 09:30:00');
        $this->artisan('quotes:remind')->assertSuccessful();
        Mail::assertNothingSent();
        $quote->refresh();
        $this->assertCount(1, $quote->reminder_log);
        $this->assertSame('sms', $quote->reminder_log[0]['channel']);
        $messages = SmsMessage::where('subject_type', $quote->getMorphClass())->where('subject_id', $quote->id)->orderBy('id')->get();
        $this->assertCount(2, $messages, 'de offerte zelf en de herinnering');
        $this->assertStringContainsString('herinnering aan offerte '.$quote->number, $messages->last()->body);
        $this->assertStringContainsString('4 december', $messages->last()->body);
        $this->assertMatchesRegularExpression('#https?://\S+#', $messages->last()->body);
    }

    public function test_a_portal_visit_is_recorded_as_viewed(): void
    {
        Mail::fake();
        $quote = $this->sentQuote();
        $this->assertNull($quote->portal_viewed_at);

        $this->withSession(['portal_email' => 'klant@example.com', 'portal_verified_at' => now()->timestamp])
            ->get(route('portal.quote', $quote->portal_token))->assertOk();
        $this->withSession(['portal_email' => 'klant@example.com', 'portal_verified_at' => now()->timestamp])
            ->get(route('portal.quote', $quote->portal_token))->assertOk();
        $quote->refresh();
        $this->assertNotNull($quote->portal_viewed_at);
        $this->assertSame(2, $quote->portal_views);
        $this->assertSame(1, ActivityLog::withoutGlobalScope('company')->where('action', 'viewed')->where('subject_id', $quote->id)->count(), 'alleen de eerste keer in het logboek');
        $this->get(route('quotes.show', $quote))->assertOk()->assertInertia(fn ($page) => $page
            ->where('quote.follow_up.steps.1.state', 'done')->where('quote.follow_up.steps.1.views', 2)->where('quote.follow_up.steps.1.date', '4 nov'));
    }
}
