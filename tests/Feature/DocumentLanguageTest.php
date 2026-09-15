<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quote;
use App\Support\DocumentLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Taal per document (1.52.0): de klanttaal is de standaard, maar op het
 * factuur- en offerteformulier kies je per document Nederlands, Engels of
 * Pools — ook voor een bestaand concept. PDF en e-mail volgen die keuze.
 */
class DocumentLanguageTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private function lines(): array
    {
        return [[
            'description' => 'Doradztwo', 'quantity' => 1, 'unit' => 'uur',
            'unit_price' => 100, 'vat_rate' => 21, 'discount_pct' => 0,
        ]];
    }

    public function test_invoice_language_follows_the_form_and_can_be_changed_on_a_draft(): void
    {
        $this->actingAs($this->demoUser());
        $customer = Customer::orderBy('id')->firstOrFail();
        $customer->update(['language' => 'nl']);
        $base = ['customer_id' => $customer->id, 'invoice_date' => now()->toDateString(), 'payment_terms' => 14, 'lines' => $this->lines(), 'action' => 'draft'];

        // Zonder keuze: de taal van de klant.
        $this->post(route('invoices.store'), $base + ['reference' => 'Taal-standaard'])->assertRedirect();
        $this->assertSame('nl', Invoice::where('reference', 'Taal-standaard')->firstOrFail()->language);

        // Met keuze: Pools, ook al staat de klant op Nederlands.
        $this->post(route('invoices.store'), $base + ['reference' => 'Taal-pools', 'language' => 'pl'])->assertRedirect();
        $invoice = Invoice::where('reference', 'Taal-pools')->firstOrFail();
        $this->assertSame('pl', $invoice->language);

        // Bestaand concept omzetten naar Engels; zonder taal in het verzoek blijft de keuze staan.
        $this->put(route('invoices.update', $invoice), $base + ['language' => 'en'])->assertRedirect();
        $this->assertSame('en', $invoice->fresh()->language);
        $this->put(route('invoices.update', $invoice), $base)->assertRedirect();
        $this->assertSame('en', $invoice->fresh()->language);

        // Andere klant zonder expliciete taal: de taal van die klant.
        $polish = Customer::where('id', '!=', $customer->id)->orderBy('id')->firstOrFail();
        $polish->update(['language' => 'pl']);
        $this->put(route('invoices.update', $invoice), ['customer_id' => $polish->id] + $base)->assertRedirect();
        $this->assertSame('pl', $invoice->fresh()->language);

        // Een onbekende taal wordt geweigerd.
        $this->from(route('invoices.edit', $invoice))
            ->put(route('invoices.update', $invoice), $base + ['language' => 'de'])
            ->assertSessionHasErrors('language');
        $this->assertSame('pl', $invoice->fresh()->language);

        // Het formulier krijgt de klanttaal en de marktstandaard mee.
        $this->get(route('invoices.edit', $invoice))->assertOk()->assertInertia(fn ($page) => $page
            ->component('Invoices/Form')
            ->where('default_language', 'nl')
            ->where('invoice.language', 'pl')
            ->has('customers.0.language'));
    }

    public function test_polish_invoice_pdf_renders_and_uses_polish_wording(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $invoice = Invoice::withoutGlobalScope('company')->where('company_id', $user->company_id)->where('status', 'sent')->firstOrFail();
        $invoice->forceFill(['language' => 'pl'])->save();

        $response = $this->get(route('invoices.pdf', $invoice));
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));

        $this->assertSame('FAKTURA VAT', DocumentLocale::using('pl', fn () => __('doc.invoice')));
        $this->assertSame('nl', app()->getLocale(), 'De app zelf blijft Nederlands');
    }

    public function test_quote_language_follows_the_form_and_carries_over_to_the_invoice(): void
    {
        Mail::fake();
        $this->actingAs($this->demoUser());
        $customer = Customer::orderBy('id')->firstOrFail();
        $customer->update(['language' => 'nl']);
        $base = ['customer_id' => $customer->id, 'quote_date' => now()->toDateString(), 'valid_days' => 30, 'lines' => $this->lines(), 'action' => 'draft'];

        $this->post(route('quotes.store'), $base + ['reference' => 'Oferta-pl', 'language' => 'pl'])->assertRedirect();
        $quote = Quote::where('reference', 'Oferta-pl')->firstOrFail();
        $this->assertSame('pl', $quote->language);

        $this->put(route('quotes.update', $quote), $base + ['language' => 'en'])->assertRedirect();
        $this->assertSame('en', $quote->fresh()->language);

        $this->from(route('quotes.edit', $quote))
            ->put(route('quotes.update', $quote), $base + ['language' => 'xx'])
            ->assertSessionHasErrors('language');

        // Omzetten naar factuur: de factuur neemt de taal van de offerte over.
        $this->post(route('quotes.send', $quote))->assertRedirect();
        $this->post(route('quotes.convert', $quote))->assertRedirect();
        $invoice = Invoice::findOrFail($quote->fresh()->converted_invoice_id);
        $this->assertSame('en', $invoice->language);
    }
}
