<?php

namespace Tests\Feature;

use App\Mail\InvoiceMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\DocumentLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Zelfstandige creditnota (1.56.0): een creditnota zonder factuur in het
 * pakket, via het gewone formulier ("Nieuwe creditnota"). Eigen nummerreeks,
 * PDF en mail als creditnota, nooit openstaand. En een creditnota-concept dat
 * via het formulier wordt verstuurd, krijgt óók een creditnotanummer — tot nu
 * toe kreeg het een factuurnummer.
 */
class StandaloneCreditNoteTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private function payload(array $extra = []): array
    {
        return $extra + [
            'customer_id' => Customer::orderBy('id')->firstOrFail()->id,
            'invoice_date' => now()->toDateString(),
            'payment_terms' => 14,
            'reference' => '2025-0123',
            'lines' => [['description' => 'Retour geleverde goederen', 'quantity' => 2, 'unit_price' => 100, 'vat_rate' => 21]],
            'action' => 'draft',
            'is_credit' => 1,
        ];
    }

    private function draftCredit(): Invoice
    {
        $this->post(route('invoices.store'), $this->payload())->assertRedirect();

        return Invoice::where('reference', '2025-0123')->latest('id')->firstOrFail();
    }

    public function test_the_form_opens_in_credit_mode(): void
    {
        $this->actingAs($this->demoUser());

        $this->get(route('invoices.create', ['credit' => 1]))->assertOk()
            ->assertInertia(fn ($page) => $page->component('Invoices/Form')->where('is_credit', true));
        $this->get(route('invoices.create'))->assertOk()
            ->assertInertia(fn ($page) => $page->where('is_credit', false));
    }

    public function test_a_standalone_credit_note_is_drafted_and_sent_with_its_own_number(): void
    {
        Mail::fake();
        $this->actingAs($this->demoUser());
        $openBefore = Invoice::open()->count();

        $credit = $this->draftCredit();
        $this->assertTrue($credit->is_credit);
        $this->assertNull($credit->credits_invoice_id);
        $this->assertSame('draft', $credit->status);
        $this->assertSame(0, (int) $credit->payment_terms);
        $this->assertSame($credit->invoice_date->toDateString(), $credit->due_date->toDateString(), 'Een creditnota heeft geen betaaltermijn');
        $this->assertEquals(242.0, (float) $credit->total);

        $this->put(route('invoices.update', $credit), $this->payload(['action' => 'send']))->assertRedirect()
            ->assertSessionHas('flash', fn ($flash) => str_starts_with($flash, 'Creditnota C-'));

        $credit->refresh();
        $this->assertStringStartsWith('C-', $credit->number);
        $this->assertSame('sent', $credit->status);
        $this->assertNotNull($credit->sent_at);
        $this->assertNotNull($credit->portal_token);
        $this->assertSame(0, Payment::where('kind', 'credit')->count(), 'Niets te verrekenen: er is geen factuur in het pakket');
        $this->assertSame($openBefore, Invoice::open()->count(), 'Een creditnota is geen vordering');

        Mail::assertSent(InvoiceMail::class, fn (InvoiceMail $mail) => $mail->invoice->is($credit)
            && str_starts_with($mail->envelope()->subject, 'Creditnota ' . $credit->number));

        // Factuurpagina en logboek noemen het een creditnota.
        $this->get(route('invoices.show', $credit))->assertOk()
            ->assertInertia(fn ($page) => $page->where('invoice.is_credit', true)->where('invoice.settle_options', []));
        $this->assertDatabaseHas('activity_logs', ['subject_id' => $credit->id, 'action' => 'sent', 'subject_label' => 'Creditnota ' . $credit->number]);
    }

    public function test_the_pdf_and_mail_present_a_credit_note_as_such(): void
    {
        $this->actingAs($this->demoUser());
        $credit = $this->draftCredit()->load('lines');
        $company = $credit->brandedCompany();

        foreach (['modern', 'classic', 'minimal', 'stationery'] as $template) {
            $html = DocumentLocale::using('nl', fn () => view("pdf.invoice-{$template}", ['invoice' => $credit, 'company' => $company])->render());
            $this->assertMatchesRegularExpression('/creditnota/i', $html, $template);
            $this->assertStringContainsString('Crediteert factuur', $html, $template);
            $this->assertStringContainsString('2025-0123', $html, $template);
            $this->assertStringNotContainsString('Vervaldatum', $html, $template);
            $this->assertStringNotContainsString('Gelieve het bedrag', $html, $template);
            $this->assertStringContainsString('verrekend of aan u terugbetaald', $html, $template);
        }

        // Een gewone factuur blijft een factuur.
        $invoice = Invoice::regular()->whereIn('status', ['sent', 'overdue'])->has('lines')->orderBy('id')->firstOrFail()->load('lines');
        $html = DocumentLocale::using('nl', fn () => view('pdf.invoice-modern', ['invoice' => $invoice, 'company' => $invoice->brandedCompany()])->render());
        $this->assertStringContainsString('FACTUUR', $html);
        $this->assertStringNotContainsString('CREDITNOTA', $html);
        $this->assertStringContainsString('Vervaldatum', $html);

        // De mail: eigen onderwerp en tekst, geen betaalverzoek.
        $mail = new InvoiceMail($credit, 'pdf');
        $body = DocumentLocale::using('nl', fn () => $mail->render());
        $this->assertStringContainsString('Hierbij ontvangt u creditnota', $body);
        $this->assertStringNotContainsString('Wij verzoeken u', $body);
    }

    public function test_a_credit_draft_sent_from_the_form_gets_a_credit_number_and_settles(): void
    {
        Mail::fake();
        $this->actingAs($this->demoUser());
        $invoice = Invoice::regular()->whereIn('status', ['sent', 'overdue'])->has('lines')->orderBy('id')->firstOrFail();

        $this->post(route('invoices.credit.store', $invoice), ['kind' => 'partial'])->assertRedirect();
        $draft = $invoice->creditNotes()->where('status', 'draft')->firstOrFail();

        $this->put(route('invoices.update', $draft), [
            'customer_id' => $draft->customer_id,
            'invoice_date' => now()->toDateString(),
            'payment_terms' => 0,
            'lines' => [['description' => 'Gedeeltelijke correctie', 'quantity' => 1, 'unit_price' => 10, 'vat_rate' => 21]],
            'action' => 'send',
        ])->assertRedirect()->assertSessionHas('flash', fn ($flash) => str_starts_with($flash, 'Creditnota C-'));

        $draft->refresh();
        $invoice->refresh();
        $this->assertTrue($draft->is_credit);
        $this->assertStringStartsWith('C-', $draft->number, 'Kreeg tot 1.56.0 een factuurnummer');
        $this->assertSame('settled', $draft->status);
        $this->assertSame('partial', $invoice->status);
        $this->assertEqualsWithDelta(12.10, (float) $invoice->paid_total, 0.001);
        Mail::assertSent(InvoiceMail::class, fn (InvoiceMail $mail) => $mail->invoice->is($draft));
    }
}
