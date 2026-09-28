<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Datums gaan als jjjj-mm-dd naar het formulier. Met de gewone notatie werd
 * 28 september (Nederlandse tijd) in UTC 27 september 22:00 uur: het datumveld
 * toonde de verkeerde dag, en wie daarna opsloeg schoof de datum een dag terug.
 */
class DocumentDateTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private function lines(): array
    {
        return [['description' => 'Advies', 'quantity' => 1, 'unit' => 'uur', 'unit_price' => 100, 'vat_rate' => 21, 'discount_pct' => 0]];
    }

    public function test_an_invoice_keeps_its_date_when_edited(): void
    {
        $this->actingAs($this->demoUser());
        $customer = Customer::orderBy('id')->firstOrFail();

        $this->post(route('invoices.store'), [
            'customer_id' => $customer->id, 'invoice_date' => '2026-09-28', 'payment_terms' => 14,
            'reference' => 'Datumproef', 'lines' => $this->lines(), 'action' => 'draft',
        ])->assertRedirect();
        $invoice = Invoice::where('reference', 'Datumproef')->firstOrFail();

        $shown = null;
        $this->get(route('invoices.edit', $invoice))->assertOk()->assertInertia(function ($page) use (&$shown) {
            $shown = $page->toArray()['props']['invoice']['invoice_date'];

            return $page->where('invoice.invoice_date', '2026-09-28')->where('invoice.due_date', '2026-10-12');
        });

        // Opslaan met precies wat het formulier kreeg.
        $this->put(route('invoices.update', $invoice), [
            'customer_id' => $customer->id, 'invoice_date' => $shown, 'payment_terms' => 14,
            'reference' => 'Datumproef', 'lines' => $this->lines(), 'action' => 'draft',
        ])->assertRedirect();

        $this->assertSame('2026-09-28', $invoice->fresh()->invoice_date->toDateString());
        $this->assertSame('2026-10-12', $invoice->fresh()->due_date->toDateString());
    }

    public function test_a_quote_keeps_its_date_when_edited(): void
    {
        $this->actingAs($this->demoUser());
        $customer = Customer::orderBy('id')->firstOrFail();

        $this->post(route('quotes.store'), [
            'customer_id' => $customer->id, 'quote_date' => '2026-09-28', 'valid_days' => 30,
            'reference' => 'Datumproef', 'lines' => $this->lines(), 'action' => 'draft',
        ])->assertRedirect();
        $quote = Quote::where('reference', 'Datumproef')->firstOrFail();

        $this->get(route('quotes.edit', $quote))->assertOk()->assertInertia(fn ($page) => $page
            ->where('quote.quote_date', '2026-09-28')
            ->where('quote.valid_until', '2026-10-28'));
    }
}
