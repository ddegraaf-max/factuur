<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quote;
use App\Services\UblGenerator;
use App\Services\VatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Btw verlegd: geen btw op de regels, wel de vermelding met het btw-nummer van
 * de klant op PDF en e-factuur, en de omzet in rubriek 1e van de aangifte.
 */
class VatReverseTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private function customer(?string $vatNumber = 'NL001234567B01'): Customer
    {
        $customer = Customer::orderBy('id')->firstOrFail();
        $customer->forceFill(['vat_number' => $vatNumber, 'country' => 'NL'])->save();

        return $customer;
    }

    private function payload(Customer $customer, array $extra = []): array
    {
        return $extra + [
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'payment_terms' => 30,
            'reference' => 'Onderaanneming',
            'vat_reversed' => true,
            'lines' => [
                ['description' => 'Metselwerk', 'quantity' => 1, 'unit' => 'stuk', 'unit_price' => 1000, 'vat_rate' => 21, 'discount_pct' => 0],
                ['description' => 'Voegwerk', 'quantity' => 2, 'unit' => 'uur', 'unit_price' => 50, 'vat_rate' => 9, 'discount_pct' => 0],
            ],
            'action' => 'draft',
        ];
    }

    public function test_a_reversed_invoice_has_no_vat_and_says_so(): void
    {
        $this->actingAs($this->demoUser());
        $customer = $this->customer();

        $this->post(route('invoices.store'), $this->payload($customer))->assertSessionHasNoErrors()->assertRedirect();

        $invoice = Invoice::where('reference', 'Onderaanneming')->with('lines')->firstOrFail();
        $this->assertTrue($invoice->vat_reversed);
        $this->assertEquals(1100.0, (float) $invoice->subtotal);
        $this->assertEquals(0.0, (float) $invoice->vat_total);
        $this->assertEquals(1100.0, (float) $invoice->total);
        $this->assertSame([0.0, 0.0], $invoice->lines->map(fn ($l) => (float) $l->vat_rate)->all());

        // Alle vier de sjablonen tonen de vermelding met het btw-nummer van de klant.
        foreach (['modern', 'classic', 'minimal', 'stationery'] as $template) {
            $html = view("pdf.invoice-{$template}", ['invoice' => $invoice, 'company' => $invoice->brandedCompany()])->render();
            $this->assertStringContainsString('Btw verlegd', $html, $template);
            $this->assertStringContainsString('De btw is verlegd naar ' . e($customer->name) . ', btw-nummer NL001234567B01.', $html, $template);
            $this->assertStringNotContainsString('BTW 0%', $html, $template);
        }

        $this->get(route('invoices.show', $invoice))->assertOk();
        $this->get(route('invoices.edit', $invoice))->assertOk();
    }

    public function test_the_e_invoice_uses_the_reverse_charge_category(): void
    {
        $this->actingAs($this->demoUser());
        $this->post(route('invoices.store'), $this->payload($this->customer()))->assertRedirect();
        $invoice = Invoice::where('reference', 'Onderaanneming')->firstOrFail();

        $xml = app(UblGenerator::class)->generate($invoice->load('lines'));

        $this->assertStringContainsString('<cbc:ID>AE</cbc:ID>', $xml);
        $this->assertStringContainsString('<cbc:TaxExemptionReasonCode>VATEX-EU-AE</cbc:TaxExemptionReasonCode>', $xml);
        $this->assertStringNotContainsString('<cbc:ID>Z</cbc:ID>', $xml);
        $this->assertStringContainsString('NL001234567B01', $xml);
        $this->assertNotFalse(simplexml_load_string($xml), 'De e-factuur is geldige XML');
    }

    public function test_the_customer_vat_number_is_required_and_can_be_entered_on_the_form(): void
    {
        $this->actingAs($this->demoUser());
        $customer = $this->customer(null);

        $this->post(route('invoices.store'), $this->payload($customer))->assertSessionHasErrors('customer_vat_number');
        $this->post(route('invoices.store'), $this->payload($customer, ['customer_vat_number' => 'geen idee']))
            ->assertSessionHasErrors('customer_vat_number');
        $this->assertSame(0, Invoice::where('reference', 'Onderaanneming')->count());

        $this->post(route('invoices.store'), $this->payload($customer, ['customer_vat_number' => 'nl 0012.34.567.b01']))
            ->assertSessionHasNoErrors();

        $this->assertSame('NL001234567B01', $customer->fresh()->vat_number);
        $this->assertSame('NL001234567B01', Invoice::where('reference', 'Onderaanneming')->firstOrFail()->customer_vat_number);
    }

    public function test_without_the_choice_nothing_changes(): void
    {
        $this->actingAs($this->demoUser());
        $customer = $this->customer(null);

        $this->post(route('invoices.store'), $this->payload($customer, ['vat_reversed' => false]))->assertSessionHasNoErrors();

        $invoice = Invoice::where('reference', 'Onderaanneming')->firstOrFail();
        $this->assertFalse($invoice->vat_reversed);
        $this->assertEquals(1319.0, (float) $invoice->total);   // 1.000 + 21% en 100 + 9%

        // Aanzetten bij het bewerken, en weer uit.
        $customer->forceFill(['vat_number' => 'NL001234567B01'])->save();
        $this->put(route('invoices.update', $invoice), $this->payload($customer))->assertSessionHasNoErrors();
        $this->assertEquals(1100.0, (float) $invoice->fresh()->total);
        $this->put(route('invoices.update', $invoice), $this->payload($customer, ['vat_reversed' => false]))->assertSessionHasNoErrors();
        $this->assertEquals(1319.0, (float) $invoice->fresh()->total);
    }

    public function test_a_reversed_quote_becomes_a_reversed_invoice(): void
    {
        Mail::fake();
        $this->actingAs($this->demoUser());
        $customer = $this->customer();

        $this->post(route('quotes.store'), [
            'customer_id' => $customer->id,
            'quote_date' => now()->toDateString(),
            'valid_days' => 30,
            'reference' => 'Uitbouw',
            'vat_reversed' => true,
            'lines' => $this->payload($customer)['lines'],
            'action' => 'draft',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $quote = Quote::where('reference', 'Uitbouw')->with('lines')->firstOrFail();
        $this->assertTrue($quote->vat_reversed);
        $this->assertEquals(1100.0, (float) $quote->total);

        $html = view('pdf.quote', ['quote' => $quote, 'company' => $quote->brandedCompany()])->render();
        $this->assertStringContainsString('btw-nummer NL001234567B01', $html);

        $this->get(route('quotes.show', $quote))->assertOk();
        $this->post(route('quotes.convert', $quote))->assertRedirect();

        $invoice = Invoice::findOrFail($quote->fresh()->converted_invoice_id);
        $this->assertTrue($invoice->vat_reversed);
        $this->assertEquals(1100.0, (float) $invoice->total);
    }

    public function test_the_vat_return_counts_a_reversed_invoice_in_1e(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        // Een geïmporteerde klant met een landnaam in plaats van een code.
        $customer = $this->customer();
        $customer->forceFill(['country' => 'Nederland'])->save();

        $year = (int) now()->year;
        $row = fn () => collect(app(VatService::class)->overview($user->company->fresh(), $year, false)['periods'])
            ->flatMap(fn ($p) => $p['rubrieken'])->where('key', '1e')->sum('base');
        $before = $row();

        $this->post(route('invoices.store'), $this->payload($customer, ['action' => 'send']))->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta($before + 1100.0, $row(), 0.01);
    }

    public function test_the_assistant_can_create_a_reversed_invoice(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $customer = $this->customer();

        $invoice = app(\App\Services\InvoiceManager::class)->create([
            'customer_id' => $customer->id,
            'vat_reversed' => true,
            'lines' => [['description' => 'Timmerwerk', 'quantity' => 1, 'unit_price' => 500, 'vat_rate' => 21]],
        ]);

        $this->assertTrue($invoice->vat_reversed);
        $this->assertEquals(500.0, (float) $invoice->total);

        // Een kopie en een terugkerend profiel nemen de keuze over.
        $this->post(route('invoices.duplicate', $invoice))->assertRedirect();
        $this->assertSame(2, Invoice::where('vat_reversed', true)->count());
    }
}
