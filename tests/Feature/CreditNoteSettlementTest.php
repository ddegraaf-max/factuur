<?php

namespace Tests\Feature;

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
        $credit = $this->fullCredit($invoice);
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
        $credit = $this->fullCredit($invoice->fresh());

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
        $credit = $this->fullCredit($other);

        $this->settle($invoice, $credit)->assertSessionHasErrors('credit_note_id');
        $this->assertSame('sent', $credit->fresh()->status);

        // Een conceptcreditnota (gedeeltelijk) eerst definitief maken.
        $this->post(route('invoices.credit.store', $invoice), ['kind' => 'partial'])->assertRedirect();
        $draft = $invoice->creditNotes()->where('status', 'draft')->firstOrFail();
        $this->settle($invoice, $draft)->assertSessionHasErrors('credit_note_id');
        $this->assertSame(0, Payment::where('kind', 'credit')->count());
        $this->assertSame(0, Invoice::open()->where('is_credit', true)->count(), "Creditnota's tellen nooit als openstaand");
    }
}
