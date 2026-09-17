<?php

namespace Tests\Feature;

use App\Mail\InvoiceMail;
use App\Mail\QuoteMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Versturen naar een klant zonder e-mailadres (1.56.6): staat het adres pas
 * ná het aanmaken van het document bij de klant, dan gebruikt de app het
 * alsnog bij het versturen. Blijft het leeg, dan zegt de melding dat er niets
 * gemaild is — tot nu toe leek een offerte gewoon verstuurd.
 */
class SendWithoutEmailTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private const ADDRESS = 'annawinkelmolen8@gmail.com';

    private function customerWithoutEmail(): Customer
    {
        $customer = Customer::orderBy('id')->firstOrFail()->replicate();
        $customer->forceFill(['name' => 'Mevrouw A. Winkelmolen', 'email' => null])->save();

        return $customer;
    }

    private function lines(): array
    {
        return [['description' => 'Advies', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 21]];
    }

    public function test_a_quote_picks_up_the_address_added_after_it_was_drafted(): void
    {
        Mail::fake();
        $this->actingAs($this->demoUser());
        $customer = $this->customerWithoutEmail();

        $this->post(route('quotes.store'), [
            'customer_id' => $customer->id, 'quote_date' => now()->toDateString(), 'valid_days' => 30,
            'lines' => $this->lines(), 'action' => 'draft', 'reference' => 'Zonder adres',
        ])->assertRedirect();
        $quote = Quote::where('reference', 'Zonder adres')->firstOrFail();
        $this->assertNull($quote->customer_email);

        // Nog geen adres: vastgelegd, niet gemaild — en dat staat er ook.
        $this->post(route('quotes.send', $quote))->assertRedirect()
            ->assertSessionHas('flash', fn ($flash) => str_contains($flash, 'geen e-mailadres'));
        Mail::assertNothingSent();
        $this->assertSame('sent', $quote->fresh()->status);
        $this->assertDatabaseHas('activity_logs', ['subject_type' => 'offerte', 'subject_id' => $quote->id, 'action' => 'sent']);

        // Adres alsnog bij de klant gezet: de pagina weet het, en opnieuw versturen gebruikt het.
        $customer->update(['email' => self::ADDRESS]);
        $this->get(route('quotes.show', $quote))->assertOk()
            ->assertInertia(fn ($page) => $page->where('quote.send_email', self::ADDRESS));
        $this->post(route('quotes.send', $quote))->assertRedirect()
            ->assertSessionHas('flash', fn ($flash) => str_contains($flash, 'verstuurd naar ' . self::ADDRESS));
        $this->assertSame(self::ADDRESS, $quote->fresh()->customer_email);
        Mail::assertSent(QuoteMail::class, fn (QuoteMail $mail) => $mail->hasTo(self::ADDRESS));
    }

    public function test_an_invoice_picks_up_the_address_added_after_it_was_drafted(): void
    {
        Mail::fake();
        $this->actingAs($this->demoUser());
        $customer = $this->customerWithoutEmail();

        $this->post(route('invoices.store'), [
            'customer_id' => $customer->id, 'invoice_date' => now()->toDateString(), 'payment_terms' => 14,
            'lines' => $this->lines(), 'action' => 'draft', 'reference' => 'Zonder adres',
        ])->assertRedirect();
        $invoice = Invoice::where('reference', 'Zonder adres')->firstOrFail();
        $this->assertNull($invoice->customer_email);

        $customer->update(['email' => self::ADDRESS]);
        $this->get(route('invoices.show', $invoice))->assertOk()
            ->assertInertia(fn ($page) => $page->where('invoice.send_email', self::ADDRESS));
        $this->post(route('invoices.send', $invoice))->assertRedirect()
            ->assertSessionHas('flash', fn ($flash) => str_contains($flash, 'verstuurd naar ' . self::ADDRESS));
        $this->assertSame(self::ADDRESS, $invoice->fresh()->customer_email);
        Mail::assertSent(InvoiceMail::class, fn (InvoiceMail $mail) => $mail->hasTo(self::ADDRESS));
    }

    public function test_sending_an_invoice_without_any_address_says_so(): void
    {
        Mail::fake();
        $this->actingAs($this->demoUser());
        $customer = $this->customerWithoutEmail();

        $this->post(route('invoices.store'), [
            'customer_id' => $customer->id, 'invoice_date' => now()->toDateString(), 'payment_terms' => 14,
            'lines' => $this->lines(), 'action' => 'send', 'reference' => 'Zonder adres',
        ])->assertRedirect()->assertSessionHas('flash', fn ($flash) => str_starts_with($flash, 'Factuur ') && str_contains($flash, 'geen e-mailadres'));
        Mail::assertNothingSent();

        $invoice = Invoice::where('reference', 'Zonder adres')->firstOrFail();
        $this->assertSame('sent', $invoice->status);
        $this->assertNull($invoice->customer_email);
        $this->get(route('invoices.show', $invoice))->assertOk()
            ->assertInertia(fn ($page) => $page->where('invoice.send_email', null));
    }
}
