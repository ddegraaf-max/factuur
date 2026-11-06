<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * 1.106.4: een offerte die in termijnen wordt gefactureerd laat dat in de lijst zien.
 * Voorheen keek de lijst alleen naar converted_invoice_id (in één keer omgezet), zodat een
 * geaccepteerde offerte met 50% gefactureerd er stond als nooit gefactureerd.
 */
class QuoteInstallmentsIndexTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private function sentQuote(): Quote
    {
        Mail::fake();
        $this->actingAs($this->demoUser());
        $customer = Customer::orderBy('id')->firstOrFail();
        $customer->update(['email' => 'klant@example.com']);

        $this->post(route('quotes.store'), [
            'customer_id' => $customer->id, 'quote_date' => now()->toDateString(), 'valid_days' => 30, 'action' => 'draft', 'reference' => 'Termijnen',
            'lines' => [['description' => 'Verbouwing', 'quantity' => 1, 'unit' => 'stuk', 'unit_price' => 6500, 'vat_rate' => 21, 'discount_pct' => 0]],
        ])->assertRedirect();
        $quote = Quote::where('reference', 'Termijnen')->firstOrFail();
        $this->post(route('quotes.send', $quote))->assertRedirect();
        $this->post(route('quotes.installments.store', $quote), ['installments' => [
            ['description' => 'Bij opdracht', 'percentage' => 50],
            ['description' => 'Bij oplevering', 'percentage' => 50],
        ]])->assertSessionDoesntHaveErrors();

        return $quote->refresh();
    }

    /** @return array<string, mixed> */
    private function row(Quote $quote): array
    {
        $props = $this->get(route('quotes.index'))->assertOk()->viewData('page')['props'];

        return collect($props['quotes']['data'])->firstWhere('id', $quote->id);
    }

    public function test_the_list_shows_how_much_of_a_quote_is_invoiced_in_instalments(): void
    {
        $quote = $this->sentQuote();
        [$first, $second] = $quote->installments->all();

        $row = $this->row($quote);
        $this->assertSame(2, $row['installments_total']);
        $this->assertSame(0, $row['installments_done']);
        $this->assertSame(0.0, $row['invoiced_percent']);
        $this->assertFalse($row['converted']);

        // Eerste termijn (50%) gefactureerd: de lijst toont 50%.
        $this->post(route('quotes.installments.invoice', [$quote, $first]))->assertRedirect();
        $row = $this->row($quote);
        $this->assertSame('accepted', $row['status']);
        $this->assertSame(1, $row['installments_done']);
        $this->assertSame(50.0, $row['invoiced_percent']);
        $this->assertFalse($row['converted'], 'converted blijft voor een offerte die in één keer is omgezet');

        // Tweede termijn ook: alles gefactureerd.
        $this->post(route('quotes.installments.invoice', [$quote, $second]))->assertRedirect();
        $row = $this->row($quote);
        $this->assertSame(2, $row['installments_done']);
        $this->assertSame(100.0, $row['invoiced_percent']);
    }
}
