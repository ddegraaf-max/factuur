<?php

namespace Tests\Feature;

use App\Mail\IncassoDossierMail;
use App\Mail\PaymentDemandMail;
use App\Mail\PaymentDemandNoticeMail;
use App\Models\Invoice;
use App\Models\PaymentDemand;
use App\Models\ReminderLog;
use App\Models\User;
use App\Services\PaymentDemandService;
use App\Services\ReminderService;
use App\Support\LegalInterest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Online aanmaning (1.65.0): de laatste aanmaning met een eigen pagina waarop
 * het bedrag per dag oploopt en de klant reageert; na de termijn gaat het
 * dossier met één klik naar de deurwaarder, met aanmaning en logboek erbij.
 */
class PaymentDemandTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->user = $this->demoUser();
        $this->user->company->forceFill(['is_exempt' => true, 'email' => 'administratie@jansen.test', 'copy_email' => null, 'iban' => 'NL91ABNA0417164300'])->save();
        $this->actingAs($this->user);
    }

    private function overdueInvoice(string $type = 'business', int $daysOverdue = 40, float $total = 1000.0): Invoice
    {
        $invoice = Invoice::regular()->where('company_id', $this->user->company_id)
            ->where('status', 'sent')->whereNotNull('customer_email')->whereNotNull('customer_id')->firstOrFail();
        $invoice->forceFill([
            'due_date' => now()->subDays($daysOverdue)->toDateString(),
            'status' => 'overdue',
            'total' => $total,
            'paid_total' => 0,
            'customer_email' => 'debiteur@klant.test',
        ])->save();
        $invoice->customer->forceFill(['type' => $type])->save();
        ReminderLog::where('invoice_id', $invoice->id)->delete();

        return $invoice->fresh();
    }

    /** Verder als bezoeker zonder inlog (de link uit de mail). */
    private function asGuest(): void
    {
        \Inertia\Inertia::flushShared();
        $this->flushSession();
        $this->app['auth']->forgetGuards();
    }

    public function test_a_business_demand_goes_out_with_interest_and_the_costs_follow_after_the_term(): void
    {
        $invoice = $this->overdueInvoice('business', 40, 1000.0);
        $interest = LegalInterest::interest(1000.0, $invoice->due_date, now(), true)['total'];
        $this->assertGreaterThan(0, $interest);

        // Het venster: berekening vooraf, met de standaardtermijn voor een zakelijke klant.
        $this->getJson(route('demands.preview', $invoice))->assertOk()
            ->assertJson([
                'blocker' => null, 'debtor_type' => 'business', 'term_days' => 7, 'principal' => 1000,
                'interest' => $interest, 'costs' => 150, 'costs_vat' => 0, 'total' => round(1000 + $interest, 2),
                'total_after' => round(1150 + $interest, 2), 'sent_to' => 'debiteur@klant.test',
            ]);

        $this->post(route('demands.store', $invoice), ['term_days' => 10])->assertRedirect()->assertSessionHasNoErrors();
        $demand = PaymentDemand::firstOrFail();
        $this->assertSame('sent', $demand->status);
        $this->assertSame('business', $demand->debtor_type);
        $this->assertSame(now()->addDays(10)->toDateString(), $demand->deadline->toDateString());
        $this->assertEqualsWithDelta(150.0, (float) $demand->costs, 0.001);
        $this->assertSame(64, strlen($demand->token));
        $this->assertSame(['sent'], $demand->events()->pluck('event')->all());
        $this->assertDatabaseHas('reminder_logs', ['invoice_id' => $invoice->id, 'kind' => 'demand', 'sent_to' => 'debiteur@klant.test']);

        Mail::assertSent(PaymentDemandMail::class, function (PaymentDemandMail $mail) {
            return $mail->hasTo('debiteur@klant.test') && count($mail->attachments()) === 2
                && str_starts_with($mail->letterPdf, '%PDF') && str_starts_with($mail->invoicePdf, '%PDF');
        });

        // De mail: bedrag, termijn, gevolgen en de link naar de pagina.
        $service = app(PaymentDemandService::class);
        $html = (new PaymentDemandMail($demand, $service->claimAsSent($demand), '', ''))->render();
        $this->assertStringContainsString('Laatste aanmaning', $html);
        $this->assertStringContainsString($invoice->number, $html);
        $this->assertStringContainsString('aanmaning/' . $demand->token, $html);
        $this->assertStringContainsString(money(150), $html);
        $this->assertStringContainsString($demand->deadline->translatedFormat('j F Y'), $html);
        $this->assertStringContainsString('gerechtsdeurwaarder', $html);
        $this->assertStringContainsString('6:119a', $html);

        // Eén lopende aanmaning per factuur.
        $this->post(route('demands.store', $invoice))->assertSessionHasErrors('demand');
        $this->assertSame(1, PaymentDemand::count());

        // Tot en met de laatste dag zonder incassokosten; daarna komen ze erbij.
        $claim = $service->claim($demand);
        $this->assertFalse($claim['costs_due']);
        $this->assertEqualsWithDelta(1000 + $interest, $claim['total'], 0.001);
        $this->assertGreaterThan(0, $claim['per_day']);

        $this->travelTo(now()->addDays(10)->setTime(12, 0));
        $this->assertFalse($service->claim($demand->fresh())['costs_due'], 'De laatste dag telt nog mee');
        $this->travel(1)->days();
        $later = $service->claim($demand->fresh());
        $this->assertTrue($later['costs_due']);
        $this->assertGreaterThan($interest, $later['interest'], 'De rente loopt door');
        $this->assertEqualsWithDelta(1150 + $later['interest'], $later['total'], 0.001);

        // De factuurpagina laat de stand zien.
        $this->get(route('invoices.show', $invoice))->assertOk()->assertInertia(fn ($page) => $page
            ->where('invoice.demand.current.status', 'sent')
            ->where('invoice.demand.current.due', true)
            ->where('invoice.demand.current.costs_due', true)
            ->has('invoice.demand.current.events', 1));
    }

    public function test_a_consumer_gets_at_least_fourteen_days_counted_from_the_day_after_receipt(): void
    {
        $invoice = $this->overdueInvoice('consumer', 20, 400.0);

        $this->post(route('demands.store', $invoice), ['term_days' => 5])->assertSessionHasNoErrors();
        $demand = PaymentDemand::firstOrFail();
        $this->assertSame('consumer', $demand->debtor_type);
        $this->assertSame(14, $demand->term_days, 'Korter dan veertien dagen kan bij een particulier niet');
        $this->assertSame(now()->addDays(15)->toDateString(), $demand->deadline->toDateString());
        $this->assertEqualsWithDelta(60.0, (float) $demand->costs, 0.001);

        $service = app(PaymentDemandService::class);
        $claim = $service->claimAsSent($demand);
        $this->assertEqualsWithDelta(LegalInterest::interest(400.0, $invoice->due_date, now(), false)['total'], $claim['interest'], 0.001);

        $html = (new PaymentDemandMail($demand, $claim, '', ''))->render();
        $this->assertStringContainsString('binnen 14 dagen nadat u deze aanmaning heeft ontvangen', $html);
        $this->assertStringContainsString('6:119 Burgerlijk Wetboek', $html);

        // Zonder rente: alleen de hoofdsom, en na de termijn de kosten.
        $service->withdraw($demand);
        $this->post(route('demands.store', $invoice), ['with_interest' => false, 'debtor_type' => 'consumer'])->assertSessionHasNoErrors();
        $plain = $service->claim(PaymentDemand::where('status', 'sent')->firstOrFail());
        $this->assertSame(0.0, $plain['interest']);
        $this->assertEqualsWithDelta(400.0, $plain['total'], 0.001);
        $this->assertEqualsWithDelta(460.0, $plain['total_after'], 0.001);
    }

    public function test_the_costs_carry_vat_when_the_creditor_cannot_deduct_it(): void
    {
        $this->user->company->forceFill(['kor' => true])->save();
        $invoice = $this->overdueInvoice('business', 10, 1000.0);

        $this->post(route('demands.store', $invoice))->assertSessionHasNoErrors();
        $demand = PaymentDemand::firstOrFail();
        $this->assertEqualsWithDelta(31.5, (float) $demand->costs_vat, 0.001);
        $html = (new PaymentDemandMail($demand, app(PaymentDemandService::class)->claimAsSent($demand), '', ''))->render();
        $this->assertStringContainsString(money(181.5), $html);
    }

    public function test_it_explains_why_a_demand_cannot_go_out(): void
    {
        $invoice = $this->overdueInvoice();
        $service = app(PaymentDemandService::class);
        $this->assertNull($service->blocker($invoice));

        $invoice->forceFill(['due_date' => now()->addDays(3)->toDateString(), 'status' => 'sent'])->save();
        $this->post(route('demands.store', $invoice))->assertSessionHasErrors('demand');

        $invoice->forceFill(['due_date' => now()->subDays(5)->toDateString(), 'status' => 'overdue', 'customer_email' => null])->save();
        $this->post(route('demands.store', $invoice))->assertSessionHasErrors('demand');

        $invoice->forceFill(['customer_email' => 'debiteur@klant.test'])->save();
        app(ReminderService::class)->pause($invoice->fresh());
        $this->post(route('demands.store', $invoice))->assertSessionHasErrors('demand');

        $this->assertSame(0, PaymentDemand::count());
        Mail::assertNotSent(PaymentDemandMail::class);

        // In Polen is er geen deurwaarder om naar over te dragen: de functie bestaat daar niet.
        config(['brand.active' => 'lopra_pl']);
        $this->assertFalse($service->available());
    }

    public function test_the_customer_sees_the_amount_of_today_and_responds_on_the_page(): void
    {
        $invoice = $this->overdueInvoice('business', 40, 1000.0);
        $this->post(route('demands.store', $invoice))->assertSessionHasNoErrors();
        $demand = PaymentDemand::firstOrFail();

        // De ondernemer kijkt mee zonder spoor, en ziet zijn eigen overzicht in plaats van het formulier.
        $this->get(route('demand.show', $demand->token))->assertOk()->assertInertia(fn ($page) => $page
            ->where('creditor.invoice_url', route('invoices.show', $invoice))
            ->where('creditor.opens', 0)
            ->where('creditor.actions', null));
        $this->assertNull($demand->fresh()->first_opened_at);
        $this->assertSame(['sent'], $demand->events()->pluck('event')->all());

        $this->asGuest();
        $this->get(route('demand.show', str_repeat('a', 64)))->assertOk()->assertInertia(fn ($page) => $page->where('valid', false));
        $this->get(route('demand.show', $demand->token))->assertOk()->assertInertia(fn ($page) => $page
            ->component('Demands/Show')
            ->where('valid', true)
            ->where('invoice.number', $invoice->number)
            ->where('demand.status', 'sent')
            ->where('demand.expired', false)
            ->where('claim.costs_due', false)
            ->where('claim.principal', 1000)
            ->where('company.iban', 'NL91ABNA0417164300')
            ->where('t.opt_promise', 'Ik betaal uiterlijk op…')
            ->where('creditor', null)
            ->where('demand.standalone', false)
            ->where('qr', fn ($qr) => $qr === null || str_starts_with($qr, 'data:image/png')));
        $this->assertNotNull($demand->fresh()->first_opened_at);
        $opened = $demand->events()->where('event', 'opened')->firstOrFail();
        $this->assertNotNull($opened->ip_address);
        $this->assertSame('debtor', $opened->actor);
        $this->get(route('demand.show', $demand->token))->assertOk();
        $this->assertSame(1, $demand->events()->where('event', 'opened')->count(), 'Verversen telt niet als nog een keer openen');
        $this->get(route('demand.pdf', $demand->token))->assertOk()->assertHeader('Content-Type', 'application/pdf');

        // Een toezegging vraagt om een dag, een bezwaar om een reden.
        $this->post(route('demand.respond', $demand->token), ['response' => 'promise'])->assertSessionHasErrors('date');
        $this->post(route('demand.respond', $demand->token), ['response' => 'promise', 'date' => now()->subDay()->toDateString()])->assertSessionHasErrors('date');
        $this->post(route('demand.respond', $demand->token), ['response' => 'dispute'])->assertSessionHasErrors('note');
        $this->post(route('demand.respond', $demand->token), ['response' => 'paid', 'date' => now()->addDays(2)->toDateString()])->assertSessionHasErrors('date');
        $this->post(route('demand.respond', $demand->token), ['response' => 'misschien'])->assertSessionHasErrors('response');
        Mail::assertNotSent(PaymentDemandNoticeMail::class);

        $promised = now()->addDays(5)->toDateString();
        $this->post(route('demand.respond', $demand->token), ['response' => 'promise', 'date' => $promised, 'note' => 'Na de btw-teruggave'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $demand->refresh();
        $this->assertSame('promise', $demand->response);
        $this->assertSame($promised, $demand->response_date->toDateString());
        $this->assertSame('Na de btw-teruggave', $demand->response_note);
        $this->assertSame(1, $demand->events()->where('event', 'promise')->count());
        Mail::assertSent(PaymentDemandNoticeMail::class, fn (PaymentDemandNoticeMail $mail) => $mail->kind === 'response' && $mail->hasTo('administratie@jansen.test'));

        $service = app(PaymentDemandService::class);
        $notice = (new PaymentDemandNoticeMail($demand, 'response', $service->claim($demand), $service->responseLabel($demand)))->render();
        $this->assertStringContainsString('Na de btw-teruggave', $notice);
        $this->assertStringContainsString('stuit de verjaring', $notice);

        // Reageren kan één keer: de eerste reactie blijft staan, de tweede poging komt wel in het logboek.
        $this->post(route('demand.respond', $demand->token), ['response' => 'dispute', 'note' => 'Het werk is niet af.'])->assertSessionHasErrors('demand');
        $this->assertSame('promise', $demand->fresh()->response);
        $this->assertSame('Na de btw-teruggave', $demand->fresh()->response_note);
        $this->assertSame(['sent', 'opened', 'letter', 'promise', 'rejected'], $demand->events()->pluck('event')->all());
        $this->assertSame(1, collect(Mail::sent(PaymentDemandNoticeMail::class))->count());

        // De ondernemer ziet de reactie op de factuur en in het overzicht.
        $this->actingAs($this->user);
        $this->get(route('invoices.show', $invoice))->assertOk()->assertInertia(fn ($page) => $page
            ->where('invoice.demand.current.response', 'promise')
            ->where('invoice.demand.current.response_note', 'Na de btw-teruggave')
            ->where('invoice.demand.blocker', fn ($reason) => filled($reason)));
        $this->get(route('incasso.index'))->assertOk()->assertInertia(fn ($page) => $page
            ->has('demands', 1)
            ->where('demands.0.number', $invoice->number)
            ->where('demands.0.due', false));
    }

    public function test_after_the_term_the_file_goes_to_the_bailiff_with_one_click(): void
    {
        $invoice = $this->overdueInvoice('business', 40, 1000.0);
        $this->post(route('demands.store', $invoice))->assertSessionHasNoErrors();
        $demand = PaymentDemand::firstOrFail();

        $this->asGuest();
        $this->get(route('demand.show', $demand->token))->assertOk();
        $this->post(route('demand.respond', $demand->token), ['response' => 'dispute', 'note' => 'Factuur nooit ontvangen.'])->assertSessionHasNoErrors();
        $this->actingAs($this->user);

        // Zolang de termijn loopt, kan het nog niet.
        $this->post(route('demands.transfer', [$invoice, $demand]))->assertSessionHasErrors('demand');
        $this->assertSame('overdue', $invoice->fresh()->status);

        $this->travel(8)->days();
        $this->artisan('demands:notify')->assertSuccessful();
        Mail::assertSent(PaymentDemandNoticeMail::class, fn (PaymentDemandNoticeMail $mail) => $mail->kind === 'expired' && $mail->hasTo('administratie@jansen.test'));
        $this->artisan('demands:notify')->assertSuccessful();
        $this->assertSame(1, collect(Mail::sent(PaymentDemandNoticeMail::class))->filter(fn ($mail) => $mail->kind === 'expired')->count(), 'Eén melding, niet elke dag');

        $this->post(route('demands.transfer', [$invoice, $demand]))->assertRedirect()->assertSessionHasNoErrors();
        $invoice->refresh();
        $this->assertSame('incasso', $invoice->status);
        $this->assertStringStartsWith('ARM-', $invoice->incasso_reference);
        $demand->refresh();
        $this->assertSame('transferred', $demand->status);
        $this->assertNotNull($demand->closed_at);

        // Het dossier: aanmaning als bijlage, berekening en logboek in de mail.
        Mail::assertSent(IncassoDossierMail::class, function (IncassoDossierMail $mail) use ($demand) {
            $names = array_column($mail->files, 'name');

            return $mail->demand?->id === $demand->id
                && str_starts_with($names[0] ?? '', 'aanmaning-')
                && str_starts_with($mail->files[0]['data'], '%PDF')
                && $mail->claim['costs_due'] === true;
        });
        $sent = Mail::sent(IncassoDossierMail::class)->first();
        $html = (new IncassoDossierMail($invoice->fresh(['reminderLogs', 'payments', 'attachments', 'company']), '', [], $sent->demand, $sent->claim))->render();
        $this->assertStringContainsString('Factuur nooit ontvangen.', $html);
        $this->assertStringContainsString('Pagina van de aanmaning geopend', $html);
        $this->assertStringContainsString(money(150), $html);

        // De pagina van de klant zegt waar de vordering nu ligt, en neemt geen reactie meer aan.
        $this->asGuest();
        $this->get(route('demand.show', $demand->token))->assertOk()->assertInertia(fn ($page) => $page->where('demand.status', 'transferred'));
        $this->post(route('demand.respond', $demand->token), ['response' => 'paid'])->assertSessionHasErrors('demand');
    }

    public function test_a_paid_invoice_closes_the_demand_and_reminders_stay_away_while_it_runs(): void
    {
        $invoice = $this->overdueInvoice('business', 3, 500.0);
        $this->post(route('demands.store', $invoice))->assertSessionHasNoErrors();
        $demand = PaymentDemand::firstOrFail();

        // Na de laatste aanmaning geen gewone herinnering meer erachteraan.
        app(ReminderService::class)->run();
        $this->assertSame(0, ReminderLog::where('invoice_id', $invoice->id)->where('kind', '!=', 'demand')->count());

        $invoice->forceFill(['paid_total' => 500, 'status' => 'paid', 'paid_at' => now()])->save();
        $this->travel(9)->days();
        $this->artisan('demands:notify')->assertSuccessful();
        $this->assertSame('paid', $demand->fresh()->status);
        Mail::assertNotSent(PaymentDemandNoticeMail::class);

        $this->asGuest();
        $this->get(route('demand.show', $demand->token))->assertOk()->assertInertia(fn ($page) => $page->where('demand.status', 'paid')->where('qr', null));
    }

    public function test_on_request_the_file_goes_over_by_itself_three_working_days_after_the_term(): void
    {
        // Maandag 5 oktober 2026: termijn van 7 dagen loopt t/m maandag 12 oktober,
        // drie werkdagen later is donderdag 15 oktober.
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-05 09:00'));
        $invoice = $this->overdueInvoice('business', 40, 1000.0);

        $this->getJson(route('demands.preview', $invoice))->assertOk()->assertJson(['auto_transfer_label' => '15 oktober 2026']);
        $this->post(route('demands.store', $invoice), ['auto_transfer' => true])->assertSessionHasNoErrors();
        $demand = PaymentDemand::firstOrFail();
        $this->assertTrue($demand->auto_transfer);
        $this->assertSame('2026-10-12', $demand->deadline->toDateString());
        $this->assertSame(['sent', 'auto'], $demand->events()->pluck('event')->all());

        // De dag na de termijn: bericht met de dag van overdracht, maar nog geen overdracht.
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-13 08:30'));
        $this->artisan('demands:notify')->assertSuccessful();
        Mail::assertSent(PaymentDemandNoticeMail::class, fn (PaymentDemandNoticeMail $mail) => $mail->kind === 'expired' && $mail->autoDate === '15 oktober 2026');
        $this->assertSame('overdue', $invoice->fresh()->status);
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-14 08:30'));
        $this->artisan('demands:notify')->assertSuccessful();
        $this->assertSame('overdue', $invoice->fresh()->status);

        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-15 08:30'));
        $this->artisan('demands:notify')->assertSuccessful();
        $this->assertSame('incasso', $invoice->fresh()->status);
        $this->assertSame('transferred', $demand->fresh()->status);
        Mail::assertSent(IncassoDossierMail::class, fn (IncassoDossierMail $mail) => $mail->demand?->id === $demand->id);
        Mail::assertSent(PaymentDemandNoticeMail::class, fn (PaymentDemandNoticeMail $mail) => $mail->kind === 'transferred' && $mail->hasTo('administratie@jansen.test'));

        $service = app(PaymentDemandService::class);
        $html = (new PaymentDemandNoticeMail($demand->fresh(), 'transferred', $service->claim($demand->fresh())))->render();
        $this->assertStringContainsString('automatisch overgedragen', $html);
        $this->assertStringContainsString($invoice->fresh()->incasso_reference, $html);
    }

    public function test_a_response_or_a_pause_keeps_the_file_from_going_over_by_itself(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-05 09:00'));
        $invoice = $this->overdueInvoice('business', 40, 1000.0);
        $this->post(route('demands.store', $invoice), ['auto_transfer' => true])->assertSessionHasNoErrors();
        $demand = PaymentDemand::firstOrFail();

        $this->asGuest();
        $this->post(route('demand.respond', $demand->token), ['response' => 'dispute', 'note' => 'Het werk is niet af.'])->assertSessionHasNoErrors();
        $this->actingAs($this->user);

        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-20 08:30'));
        $this->artisan('demands:notify')->assertSuccessful();
        $this->assertSame('overdue', $invoice->fresh()->status, 'Na een bezwaar beslist de ondernemer zelf');
        $this->assertSame('sent', $demand->fresh()->status);
        Mail::assertSent(PaymentDemandNoticeMail::class, fn (PaymentDemandNoticeMail $mail) => $mail->kind === 'expired' && $mail->autoDate === null && filled($mail->autoBlocker));
        Mail::assertNotSent(IncassoDossierMail::class);
        $this->get(route('invoices.show', $invoice))->assertOk()->assertInertia(fn ($page) => $page
            ->where('invoice.demand.current.auto_transfer', true)
            ->where('invoice.demand.current.auto_blocker', fn ($reason) => filled($reason)));

        // Uitzetten en weer aanzetten kan zolang de aanmaning loopt.
        $this->patch(route('demands.auto', [$invoice, $demand]), ['auto_transfer' => false])->assertSessionHasNoErrors();
        $this->assertFalse($demand->fresh()->auto_transfer);

        // Zonder reactie maar op pauze: ook dan blijft het dossier liggen.
        $other = Invoice::regular()->where('company_id', $this->user->company_id)->where('id', '!=', $invoice->id)
            ->whereIn('status', ['sent', 'overdue'])->whereNotNull('customer_email')->firstOrFail();
        $other->forceFill(['due_date' => '2026-09-01', 'status' => 'overdue', 'paid_total' => 0])->save();
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-21 09:00'));
        $this->post(route('demands.store', $other), ['auto_transfer' => true])->assertSessionHasNoErrors();
        app(ReminderService::class)->pause($other->fresh());
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-10 08:30'));
        $this->artisan('demands:notify')->assertSuccessful();
        $this->assertSame('overdue', $other->fresh()->status);
        Mail::assertNotSent(IncassoDossierMail::class);
    }

    public function test_the_owner_withdraws_a_demand_and_the_old_button_still_carries_it_along(): void
    {
        $invoice = $this->overdueInvoice('business', 12, 800.0);
        $this->post(route('demands.store', $invoice))->assertSessionHasNoErrors();
        $first = PaymentDemand::firstOrFail();

        $this->delete(route('demands.withdraw', [$invoice, $first]))->assertSessionHasNoErrors();
        $this->assertSame('withdrawn', $first->fresh()->status);
        $this->delete(route('demands.withdraw', [$invoice, $first]))->assertSessionHasErrors('demand');
        $this->get(route('demands.pdf', [$invoice, $first]))->assertOk()->assertHeader('Content-Type', 'application/pdf');

        // Een aanmaning van een andere factuur hoort niet bij deze.
        $other = Invoice::regular()->where('company_id', $this->user->company_id)->where('id', '!=', $invoice->id)->firstOrFail();
        $this->get(route('demands.pdf', [$other, $first]))->assertNotFound();

        // Opnieuw versturen kan; 'Naar incasso' sluit de lopende aanmaning en neemt haar mee.
        $this->post(route('demands.store', $invoice))->assertSessionHasNoErrors();
        $second = PaymentDemand::where('status', 'sent')->firstOrFail();
        $this->post(route('incasso.send', $invoice))->assertSessionHasNoErrors();
        $this->assertSame('transferred', $second->fresh()->status);
        Mail::assertSent(IncassoDossierMail::class, fn (IncassoDossierMail $mail) => $mail->demand?->id === $second->id);
    }
}
