<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\XafExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Creditnota verrekenen met de factuur (1.55.0): één handeling in de
 * betalingsmodal zet een boeking van soort 'credit' op factuur én creditnota,
 * zodat allebei op nul uitkomen zonder dat er geld beweegt. De creditnota
 * krijgt de status 'settled' (Verrekend); omzet, btw en het auditfile blijven
 * wat de twee documenten al zeggen.
 */
class CreditNoteSettlementTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private function openInvoice(): Invoice
    {
        return Invoice::regular()->whereIn('status', ['sent', 'overdue'])->has('lines')->orderBy('id')->firstOrFail();
    }

    /** Volledige creditnota via "Creditnota maken": staat direct op 'sent'. */
    private function fullCredit(Invoice $invoice): Invoice
    {
        $this->post(route('invoices.credit.store', $invoice), ['kind' => 'full'])->assertRedirect();

        return $invoice->creditNotes()->orderByDesc('id')->firstOrFail();
    }

    /**
     * Sinds 1.55.1 verrekent een definitieve creditnota zichzelf; dit draait
     * dat terug zodat de handmatige verrekening (en oude data) getest wordt.
     */
    private function unsettle(Invoice $credit): Invoice
    {
        Payment::withoutGlobalScope('company')->where('kind', 'credit')
            ->whereIn('invoice_id', [$credit->id, $credit->credits_invoice_id])
            ->get()->each->delete();

        return $credit->fresh();
    }

    private function settle(Invoice $from, Invoice $with, array $extra = [])
    {
        return $this->post(route('invoices.payments.store', $from), $extra + [
            'kind' => 'credit', 'credit_note_id' => $with->id, 'paid_on' => now()->toDateString(),
        ]);
    }

    public function test_settling_from_the_invoice_zeroes_both_documents(): void
    {
        $this->actingAs($this->demoUser());
        $invoice = $this->openInvoice();
        $credit = $this->unsettle($this->fullCredit($invoice));
        $invoice->refresh();
        $this->assertSame('sent', $credit->status);

        // De factuurpagina biedt de creditnota als tegenpost aan, de creditnotapagina de factuur.
        $this->get(route('invoices.show', $invoice))->assertOk()->assertInertia(fn ($page) => $page
            ->where('invoice.settle_options.0.id', $credit->id)
            ->where('invoice.settle_options.0.amount', (float) $invoice->remaining_amount));
        $this->get(route('invoices.show', $credit))->assertOk()->assertInertia(fn ($page) => $page
            ->where('invoice.settle_options.0.id', $invoice->id));

        $this->settle($invoice, $credit)->assertRedirect()->assertSessionHas('flash');

        $invoice->refresh();
        $credit->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertEqualsWithDelta(0, $invoice->remaining_amount, 0.001);
        $this->assertSame('settled', $credit->status);
        $this->assertEqualsWithDelta(0, $credit->remaining_amount, 0.001);

        $this->assertSame(2, Payment::where('kind', 'credit')->count());
        $this->assertSame('Verrekend met creditnota ' . $credit->number, $invoice->payments()->where('kind', 'credit')->value('reference'));
        $this->assertSame('Verrekend met factuur ' . $invoice->number, $credit->payments()->where('kind', 'credit')->value('reference'));

        // Niets meer te verrekenen, en de pagina biedt het ook niet meer aan.
        $this->settle($invoice, $credit)->assertSessionHasErrors('credit_note_id');
        $this->get(route('invoices.show', $invoice))->assertOk()->assertInertia(fn ($page) => $page
            ->where('invoice.settle_options', [])
            ->where('invoice.history', fn ($h) => collect($h)->contains(fn ($e) => str_contains($e['label'], 'Verrekend met creditnota ' . $credit->number))));

        // Het auditfile boekt de verrekening niet als ontvangst: beide documenten staan al in het verkoopboek.
        $xml = app(XafExporter::class)->generate($invoice->company, now()->year);
        $this->assertStringNotContainsString('Ontvangst ' . $invoice->number, $xml);
        $this->assertStringNotContainsString('Ontvangst ' . $credit->number, $xml);
    }

    public function test_settling_from_the_credit_note_covers_only_what_is_still_open(): void
    {
        $this->actingAs($this->demoUser());
        $invoice = $this->openInvoice();
        $half = round((float) $invoice->total / 2, 2);
        $this->post(route('invoices.payments.store', $invoice), ['kind' => 'payment', 'amount' => $half, 'paid_on' => now()->toDateString(), 'method' => 'bank_transfer'])->assertRedirect();
        $credit = $this->unsettle($this->fullCredit($invoice->fresh()));
        $invoice->refresh();

        $this->settle($credit, $invoice, ['reference' => 'Onderling verrekend'])->assertRedirect()->assertSessionHasNoErrors();

        $invoice->refresh();
        $credit->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertEqualsWithDelta((float) $invoice->total - $half, (float) $credit->paid_total, 0.001, 'Alleen het openstaande deel wordt verrekend');
        $this->assertSame('sent', $credit->status, 'De rest van de creditnota blijft open (terug te betalen)');
        $this->assertSame('Onderling verrekend', $invoice->payments()->where('kind', 'credit')->value('reference'));
    }

    public function test_only_a_matching_final_credit_note_can_be_settled(): void
    {
        $this->actingAs($this->demoUser());
        $invoice = $this->openInvoice();
        $other = Invoice::regular()->where('id', '!=', $invoice->id)->whereIn('status', ['sent', 'overdue', 'paid'])->has('lines')->orderBy('id')->firstOrFail();
        $credit = $this->unsettle($this->fullCredit($other));

        $this->settle($invoice, $credit)->assertSessionHasErrors('credit_note_id');
        $this->assertSame('sent', $credit->fresh()->status);

        // Een conceptcreditnota (gedeeltelijk) eerst definitief maken.
        $this->post(route('invoices.credit.store', $invoice), ['kind' => 'partial'])->assertRedirect();
        $draft = $invoice->creditNotes()->where('status', 'draft')->firstOrFail();
        $this->settle($invoice, $draft)->assertSessionHasErrors('credit_note_id');
        $this->assertSame(0, Payment::where('kind', 'credit')->count());
        $this->assertSame(0, Invoice::open()->where('is_credit', true)->count(), "Creditnota's tellen nooit als openstaand");
    }

    /** 1.55.1: een definitieve creditnota verrekent zichzelf met de nog openstaande factuur. */
    public function test_a_full_credit_note_settles_the_open_invoice_at_once(): void
    {
        $this->actingAs($this->demoUser());
        $invoice = $this->openInvoice();
        $openBefore = Invoice::open()->count();

        $this->post(route('invoices.credit.store', $invoice), ['kind' => 'full'])
            ->assertRedirect()
            ->assertSessionHas('flash', fn ($flash) => str_contains($flash, 'verrekend met factuur ' . $invoice->number));
        $credit = $invoice->creditNotes()->orderByDesc('id')->firstOrFail();

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertEqualsWithDelta(0, $invoice->remaining_amount, 0.001);
        $this->assertSame('settled', $credit->status);
        $this->assertEqualsWithDelta(0, $credit->remaining_amount, 0.001);
        $this->assertSame(2, Payment::where('kind', 'credit')->count());
        $this->assertSame($openBefore - 1, Invoice::open()->count(), 'De gecrediteerde factuur telt niet meer als openstaand');
        $this->assertSame(2, ActivityLog::where('action', 'settled')->count());

        // Niets meer te verrekenen: de betalingsmodal biedt het niet meer aan.
        $this->get(route('invoices.show', $invoice))->assertOk()->assertInertia(fn ($page) => $page->where('invoice.settle_options', []));
    }

    public function test_finalizing_a_partial_credit_note_settles_only_its_amount(): void
    {
        $this->actingAs($this->demoUser());
        $invoice = $this->openInvoice();
        $part = round((float) $invoice->total / 4, 2);

        $this->post(route('invoices.credit.store', $invoice), ['kind' => 'partial'])->assertRedirect();
        $draft = $invoice->creditNotes()->where('status', 'draft')->firstOrFail();
        $draft->forceFill(['total' => $part])->save();
        $this->assertSame(0, Payment::where('kind', 'credit')->count(), 'Een concept verrekent nog niets');

        $this->post(route('invoices.credit.finalize', $draft))->assertRedirect()
            ->assertSessionHas('flash', fn ($flash) => str_contains($flash, 'verrekend met factuur ' . $invoice->number));

        $invoice->refresh();
        $draft->refresh();
        $this->assertSame('partial', $invoice->status);
        $this->assertEqualsWithDelta((float) $invoice->total - $part, $invoice->remaining_amount, 0.001);
        $this->assertSame('settled', $draft->status);
        $this->assertNotNull($draft->number);
    }

    public function test_a_credit_note_on_a_paid_invoice_is_not_settled(): void
    {
        $this->actingAs($this->demoUser());
        $invoice = Invoice::regular()->where('status', 'paid')->has('lines')->orderBy('id')->firstOrFail();

        $this->post(route('invoices.credit.store', $invoice), ['kind' => 'full'])->assertRedirect()
            ->assertSessionHas('flash', fn ($flash) => str_contains($flash, 'aangemaakt en verstuurd'));
        $credit = $invoice->creditNotes()->orderByDesc('id')->firstOrFail();

        $this->assertSame('sent', $credit->status, 'Terug te betalen: blijft open');
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(0, Payment::where('kind', 'credit')->count());
    }

    /** Creditnota's van vóór 1.55.1 krijgen de verrekening alsnog via de migratie. */
    public function test_the_migration_settles_credit_notes_from_before_the_fix(): void
    {
        $this->actingAs($this->demoUser());
        $invoice = $this->openInvoice();
        $credit = $this->unsettle($this->fullCredit($invoice));
        $invoice->refresh();
        $this->assertContains($invoice->status, ['sent', 'overdue']);
        $this->assertSame('sent', $credit->status);

        (require base_path('database/migrations/2026_09_17_000001_settle_credited_open_invoices.php'))->up();

        $invoice->refresh();
        $credit->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);
        $this->assertEqualsWithDelta(0, $invoice->remaining_amount, 0.001);
        $this->assertSame('settled', $credit->status);
        $this->assertSame(2, Payment::where('kind', 'credit')->count());
        $payment = $invoice->payments()->where('kind', 'credit')->firstOrFail();
        $this->assertSame('Verrekend met creditnota ' . $credit->number, $payment->reference);
        $this->assertSame($credit->sent_at->toDateString(), \Carbon\Carbon::parse($payment->paid_on)->toDateString(), 'Verrekend op de dag van de creditnota');
        $this->assertSame(2, ActivityLog::where('action', 'settled')->where('user_name', 'Systeem')->count());
    }
}
