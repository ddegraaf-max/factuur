<?php

namespace Tests\Feature;

use App\Mail\PaymentThanksMail;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

class InvoiceFlowTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private function lines(): array
    {
        return [[
            'description' => 'Advies', 'quantity' => 2, 'unit' => 'uur',
            'unit_price' => 100, 'vat_rate' => 21, 'discount_pct' => 0,
        ]];
    }

    public function test_draft_can_be_created_then_edited_with_other_customer_and_cleared_fields(): void
    {
        $this->actingAs($this->demoUser());
        [$first, $second] = Customer::orderBy('id')->take(2)->get();

        $this->post(route('invoices.store'), [
            'customer_id' => $first->id,
            'invoice_date' => now()->toDateString(),
            'payment_terms' => 14,
            'reference' => 'Rookproef',
            'notes' => 'Opmerking voor de klant',
            'lines' => $this->lines(),
            'action' => 'draft',
        ])->assertRedirect();

        $invoice = Invoice::where('reference', 'Rookproef')->latest('id')->firstOrFail();
        $this->assertSame('draft', $invoice->status);
        $this->assertSame($first->name, $invoice->customer_name);
        $this->assertEquals(242.0, (float) $invoice->total);

        // Bug uit 1.36: andere klant + leeggemaakte velden werden genegeerd.
        $this->put(route('invoices.update', $invoice), [
            'customer_id' => $second->id,
            'invoice_date' => now()->toDateString(),
            'payment_terms' => 30,
            'reference' => '',
            'notes' => '',
            'lines' => $this->lines(),
            'action' => 'draft',
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame($second->id, $invoice->customer_id);
        $this->assertSame($second->name, $invoice->customer_name);
        $this->assertNull($invoice->notes);
        $this->assertNull($invoice->reference);
        $this->assertSame(30, (int) $invoice->payment_terms);
    }

    public function test_duplicate_creates_a_fresh_draft_with_the_same_lines(): void
    {
        $this->actingAs($this->demoUser());
        $sent = Invoice::regular()->where('status', 'sent')->firstOrFail();

        $this->post(route('invoices.duplicate', $sent))->assertRedirect();

        $copy = Invoice::where('status', 'draft')->latest('id')->firstOrFail();
        $this->assertNotSame($sent->id, $copy->id);
        $this->assertNull($copy->number);
        $this->assertSame($sent->customer_id, $copy->customer_id);
        $this->assertSame($sent->lines()->count(), $copy->lines()->count());
        $this->assertEquals(0.0, (float) $copy->paid_total);
    }

    public function test_full_payment_marks_invoice_paid_and_sends_thank_you_mail(): void
    {
        Mail::fake();
        $this->actingAs($this->demoUser());
        $invoice = Invoice::regular()->whereIn('status', ['sent', 'overdue'])->whereNotNull('customer_email')->firstOrFail();

        $this->post(route('invoices.payments.store', $invoice), [
            'kind' => 'payment',
            'amount' => $invoice->remaining_amount,
            'paid_on' => now()->toDateString(),
            'method' => 'ideal',
            'send_thanks' => true,
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);
        $this->assertNotNull($invoice->thanks_sent_at);
        Mail::assertSent(PaymentThanksMail::class, fn ($mail) => $mail->hasTo($invoice->customer_email));
    }

    public function test_partial_payment_does_not_send_thank_you_mail(): void
    {
        Mail::fake();
        $this->actingAs($this->demoUser());
        $invoice = Invoice::regular()->where('status', 'sent')->firstOrFail();

        $this->post(route('invoices.payments.store', $invoice), [
            'kind' => 'payment',
            'amount' => round($invoice->remaining_amount / 2, 2),
            'paid_on' => now()->toDateString(),
            'method' => 'bank_transfer',
            'send_thanks' => true,
        ])->assertRedirect();

        $this->assertSame('partial', $invoice->fresh()->status);
        Mail::assertNotSent(PaymentThanksMail::class);
    }
}
