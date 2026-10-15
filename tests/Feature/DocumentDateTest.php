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

    /**
     * De factuurdatum is de dag van uitreiken. Een concept dat weken heeft
     * gewacht, krijgt bij het versturen de dag van vandaag en een verse
     * vervaldatum (15 oktober 2026: een concept van 27 augustus ging anders
     * met die oude datum en een al verstreken betaaltermijn de deur uit).
     */
    public function test_an_old_draft_gets_todays_date_when_sent(): void
    {
        $this->actingAs($this->demoUser());
        $customer = Customer::orderBy('id')->firstOrFail();
        $created = now()->subDays(49)->toDateString();

        $this->post(route('invoices.store'), [
            'customer_id' => $customer->id, 'invoice_date' => $created, 'payment_terms' => 14,
            'reference' => 'Oud concept', 'lines' => $this->lines(), 'action' => 'draft',
        ])->assertRedirect();
        $invoice = Invoice::where('reference', 'Oud concept')->firstOrFail();
        $this->assertSame($created, $invoice->invoice_date->toDateString());

        $this->post(route('invoices.send', $invoice))->assertRedirect();

        $invoice->refresh();
        $this->assertSame('sent', $invoice->status);
        $this->assertSame(now()->toDateString(), $invoice->invoice_date->toDateString());
        $this->assertSame(now()->addDays(14)->toDateString(), $invoice->due_date->toDateString());
        $this->assertStringStartsWith(now()->format('Y'), $invoice->number);
    }

    /** Een bewust in de toekomst gezette datum blijft bij het versturen staan. */
    public function test_a_draft_dated_in_the_future_keeps_that_date_when_sent(): void
    {
        $this->actingAs($this->demoUser());
        $customer = Customer::orderBy('id')->firstOrFail();
        $future = now()->addDays(5)->toDateString();

        $this->post(route('invoices.store'), [
            'customer_id' => $customer->id, 'invoice_date' => $future, 'payment_terms' => 14,
            'reference' => 'Vooruit gedateerd', 'lines' => $this->lines(), 'action' => 'draft',
        ])->assertRedirect();
        $invoice = Invoice::where('reference', 'Vooruit gedateerd')->firstOrFail();

        $this->post(route('invoices.send', $invoice))->assertRedirect();

        $invoice->refresh();
        $this->assertSame($future, $invoice->invoice_date->toDateString());
        $this->assertSame(now()->addDays(19)->toDateString(), $invoice->due_date->toDateString());
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
