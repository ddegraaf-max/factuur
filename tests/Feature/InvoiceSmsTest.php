<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\SmsMessage;
use App\Services\InvoiceManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * 1.104: een factuur per sms versturen (korte link naar het portaal) aan een klant zonder e-mailadres,
 * die daarna met een toegangscode per sms het portaal in komt om te bekijken en te betalen.
 */
class InvoiceSmsTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private function enableSms(int $companyId): void
    {
        config([
            'services.smstools.client_id' => 'test-id',
            'services.smstools.client_secret' => 'test-secret',
            'services.smstools.companies' => (string) $companyId,
        ]);
        Http::fake(['api.smsgatewayapi.com/*' => Http::response(['messageid' => 'h2md1ewkyzjkuyn9ak7pryw1evtyw3x'], 200)]);
    }

    private function draftInvoice(Customer $customer, string $reference): Invoice
    {
        $this->post(route('invoices.store'), [
            'customer_id' => $customer->id, 'invoice_date' => now()->toDateString(), 'payment_terms' => 14, 'reference' => $reference, 'action' => 'draft',
            'lines' => [['description' => 'Dakgoot vervangen', 'quantity' => 1, 'unit' => 'stuk', 'unit_price' => 200, 'vat_rate' => 21, 'discount_pct' => 0]],
        ])->assertRedirect();

        return Invoice::where('reference', $reference)->latest('id')->firstOrFail();
    }

    public function test_an_invoice_goes_out_by_sms_and_the_customer_pays_via_the_portal_with_a_code_by_sms(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        $this->enableSms($user->company_id);
        $customer = Customer::orderBy('id')->firstOrFail();
        $customer->update(['phone' => '06 12345678', 'email' => null]);
        $invoice = $this->draftInvoice($customer, 'Sms-factuur');

        $this->get(route('invoices.show', $invoice))->assertOk()->assertInertia(fn ($page) => $page
            ->where('invoice.sms_send.available', true)->where('invoice.sms_send.mobile', '+31 6 12345678')->has('invoice.sms_send.text'));
        $preset = app(InvoiceManager::class)->smsText($invoice->fresh());
        $this->assertStringNotContainsString('concept', $preset);

        $this->post(route('invoices.sms', $invoice), ['text' => $preset])->assertRedirect(route('invoices.show', $invoice))->assertSessionHas('flash');
        $invoice->refresh();
        $this->assertSame('sent', $invoice->status);
        $this->assertNotNull($invoice->number);
        $this->assertNotNull($invoice->portal_token);
        $message = SmsMessage::where('subject_type', $invoice->getMorphClass())->where('subject_id', $invoice->id)->firstOrFail();
        $this->assertSame('sent', $message->status);
        $this->assertSame('31612345678', $message->recipient);
        $this->assertStringContainsString('factuur ' . $invoice->number, $message->body);
        $this->assertStringContainsString('betaal online', $message->body);
        $this->assertMatchesRegularExpression('#https?://\S+/u/[A-Za-z0-9]+#', $message->body, 'korte link naar het portaal');
        $this->assertStringContainsString(__('Met vriendelijke groet,') . ' ' . explode(' ', trim($user->name))[0], $message->body);
        Mail::assertNothingSent();

        // De link uit de sms: poort met code per sms (geen e-mailadres), daarna de factuurpagina van het portaal.
        $this->get($invoice->portalUrl())->assertRedirect(route('portal.verify.show'));
        $this->assertSame('31612345678', session('portal_pending_phone'));
        $this->assertSame($invoice->id, session('portal_pending_invoice_id'));
        $this->get(route('portal.verify.show'))->assertOk()->assertInertia(fn ($page) => $page->where('channel', 'sms')->where('gate.type', 'factuur')->where('gate.number', $invoice->number));
        $this->post(route('portal.code.send'))->assertRedirect()->assertSessionHas('flash');
        $code = SmsMessage::where('subject_type', $invoice->getMorphClass())->where('subject_id', $invoice->id)->orderByDesc('id')->firstOrFail();
        $this->assertStringContainsString('toegangscode', $code->body);
        $this->assertSame(1, preg_match('/\b(\d{6})\b/', $code->body, $m));
        $this->post(route('portal.verify'), ['code' => $m[1]])->assertRedirect($invoice->portalUrl());
        $this->get($invoice->portalUrl())->assertOk();
        $this->get(route('portal.invoice.pdf', $invoice->portal_token))->assertOk();

        // Nog eens per sms kan (bijv. als herinnering), zonder nieuw nummer; een betaalde factuur niet meer.
        $this->post(route('invoices.sms', $invoice))->assertRedirect();
        $this->assertSame(3, SmsMessage::where('subject_type', $invoice->getMorphClass())->where('subject_id', $invoice->id)->count());
        $invoice->forceFill(['status' => 'paid'])->save();
        $this->post(route('invoices.sms', $invoice))->assertRedirect()->assertSessionHasErrors('sms');

        // Zonder mobiel nummer: geen knop (mobile null) en een duidelijke fout.
        $customer->update(['phone' => null]);
        $other = $this->draftInvoice($customer, 'Zonder-nummer');
        $this->get(route('invoices.show', $other))->assertOk()->assertInertia(fn ($page) => $page->where('invoice.sms_send.mobile', null));
        $this->post(route('invoices.sms', $other))->assertRedirect()->assertSessionHasErrors('sms');
        $this->assertSame('draft', $other->fresh()->status);
    }

    public function test_sending_by_mail_still_works_and_customers_with_email_use_the_mail_code(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        $customer = Customer::orderBy('id')->firstOrFail();
        $customer->update(['email' => 'klant@example.com', 'phone' => '06 12345678']);
        $invoice = $this->draftInvoice($customer, 'Mail-factuur');
        $this->post(route('invoices.send', $invoice))->assertRedirect();
        $invoice->refresh();
        $this->assertSame('sent', $invoice->status);
        $this->assertNotNull($invoice->number);

        $this->get($invoice->portalUrl())->assertRedirect(route('portal.verify.show'));
        $this->assertSame('klant@example.com', session('portal_pending_email'));
        $this->assertNull(session('portal_pending_phone'));
        $this->get(route('portal.verify.show'))->assertOk()->assertInertia(fn ($page) => $page->where('channel', 'mail')->where('maskedPhone', null));
    }
}
