<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * 1.106.5: twee dashboardcijfers die niet klopten.
 * - "Openstaand" telde ook facturen die bij incasso liggen; die staan nu apart ("… bij incasso"),
 *   zodat het cijfer gelijkloopt met de factuurlijst.
 * - Een geaccepteerde offerte met een termijnplan telde volledig als "nog te factureren" en stond
 *   als "Te factureren", ook als de helft al gefactureerd was.
 */
class DashboardFiguresTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    /** @return array<string, mixed> */
    private function props(): array
    {
        return $this->get(route('dashboard'))->assertOk()->viewData('page')['props'];
    }

    public function test_invoices_at_debt_collection_are_shown_apart_from_the_outstanding_amount(): void
    {
        $this->actingAs($this->demoUser());
        $before = $this->props()['kpis'];

        $invoice = Invoice::regular()->where('status', 'sent')->orderBy('id')->firstOrFail();
        $open = round((float) $invoice->total - (float) $invoice->paid_total, 2);
        $invoice->forceFill(['status' => 'incasso'])->saveQuietly();

        $after = $this->props()['kpis'];
        // De demo-administratie heeft al een factuur bij incasso; daarom relatief vergelijken.
        $this->assertEqualsWithDelta($before['outstanding'] - $open, $after['outstanding'], 0.01, 'bij incasso telt niet meer als openstaand');
        $this->assertSame($before['outstanding_count'] - 1, $after['outstanding_count']);
        $this->assertEqualsWithDelta($before['incasso'] + $open, $after['incasso'], 0.01);
        $this->assertSame($before['incasso_count'] + 1, $after['incasso_count']);
    }

    public function test_a_quote_invoiced_in_instalments_counts_only_the_remainder(): void
    {
        Mail::fake();
        $this->actingAs($this->demoUser());
        $customer = Customer::orderBy('id')->firstOrFail();
        $customer->update(['email' => 'klant@example.com']);

        $this->post(route('quotes.store'), [
            'customer_id' => $customer->id, 'quote_date' => now()->toDateString(), 'valid_days' => 30, 'action' => 'draft', 'reference' => 'Termijnen dashboard',
            'lines' => [['description' => 'Verbouwing', 'quantity' => 1, 'unit' => 'stuk', 'unit_price' => 6500, 'vat_rate' => 21, 'discount_pct' => 0]],
        ])->assertRedirect();
        $quote = Quote::where('reference', 'Termijnen dashboard')->firstOrFail();
        $this->post(route('quotes.send', $quote))->assertRedirect();
        $this->post(route('quotes.installments.store', $quote), ['installments' => [
            ['description' => 'Bij opdracht', 'percentage' => 50],
            ['description' => 'Bij oplevering', 'percentage' => 50],
        ]])->assertSessionDoesntHaveErrors();
        $quote->refresh();
        [$first, $second] = $quote->installments->all();

        // Eerste termijn factureren: de offerte wordt geaccepteerd en telt voor de helft als "nog te factureren".
        $this->post(route('quotes.installments.invoice', [$quote, $first]))->assertRedirect();
        $quotes = $this->props()['quotes'];
        $row = collect($quotes['recent'])->firstWhere('id', $quote->id);
        $this->assertSame(1, $row['installments_done']);
        $this->assertSame(50.0, $row['invoiced_percent']);
        $this->assertTrue($row['to_invoice']);
        $this->assertFalse($row['invoiced']);
        $halfTotal = $quotes['to_invoice_total'];

        // Tweede termijn ook: volledig gefactureerd, telt niet meer mee.
        $this->post(route('quotes.installments.invoice', [$quote, $second]))->assertRedirect();
        $quotes = $this->props()['quotes'];
        $row = collect($quotes['recent'])->firstWhere('id', $quote->id);
        $this->assertTrue($row['invoiced']);
        $this->assertFalse($row['to_invoice']);
        $this->assertEqualsWithDelta($halfTotal - (float) $second->amount, $quotes['to_invoice_total'], 0.01);
    }
}
