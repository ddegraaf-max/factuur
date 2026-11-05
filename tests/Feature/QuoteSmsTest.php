<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Quote;
use App\Models\SmsMessage;
use App\Services\QuoteManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/** 1.101: een offerte per sms versturen (korte link naar het portaal), voor klanten zonder e-mailadres. */
class QuoteSmsTest extends TestCase
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

    private function draftQuote(Customer $customer, string $reference): Quote
    {
        $this->post(route('quotes.store'), [
            'customer_id' => $customer->id, 'quote_date' => now()->toDateString(), 'valid_days' => 30, 'action' => 'draft', 'reference' => $reference,
            'lines' => [['description' => 'Dakgoot vervangen', 'quantity' => 1, 'unit' => 'stuk', 'unit_price' => 450, 'vat_rate' => 21, 'discount_pct' => 0]],
        ])->assertRedirect();

        return Quote::where('reference', $reference)->firstOrFail();
    }

    /** 1.103.6: de klant uit de sms heeft geen e-mailadres; de poort van het portaal stuurt de code dan per sms. */
    public function test_a_customer_without_email_enters_the_portal_with_a_code_by_sms(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        $this->enableSms($user->company_id);
        $customer = Customer::orderBy('id')->firstOrFail();
        $customer->update(['phone' => '06 12345678', 'email' => null]);
        $quote = $this->draftQuote($customer, 'Sms-portaal');
        $this->post(route('quotes.sms', $quote))->assertRedirect();
        $quote->refresh();

        $this->get($quote->portalUrl())->assertRedirect(route('portal.verify.show'));
        $this->assertSame('31612345678', session('portal_pending_phone'));
        $this->assertNull(session('portal_pending_email'));
        $this->get(route('portal.verify.show'))->assertOk()->assertInertia(fn ($page) => $page
            ->where('channel', 'sms')->where('maskedPhone', '+31 •••••678')->where('maskedEmail', null)->where('gate.type', 'offerte'));

        $this->post(route('portal.code.send'))->assertRedirect()->assertSessionHas('flash');
        Mail::assertNothingSent();
        $message = SmsMessage::where('subject_type', $quote->getMorphClass())->where('subject_id', $quote->id)->orderByDesc('id')->firstOrFail();
        $this->assertStringContainsString('toegangscode', $message->body);
        $this->assertSame('31612345678', $message->recipient);
        $this->assertSame(1, preg_match('/\b(\d{6})\b/', $message->body, $m));

        $this->post(route('portal.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post(route('portal.verify'), ['code' => $m[1]])->assertRedirect($quote->portalUrl());
        $this->get($quote->portalUrl())->assertOk()->assertInertia(fn ($page) => $page->component('Portal/QuoteShow')->where('quote.number', $quote->number));
        $this->post(route('portal.quote.sign', $quote->portal_token), ['signed_name' => 'Jan Jansen', 'signature' => 'data:image/png;base64,iVBORw0KGgo=', 'agree' => true])->assertRedirect();
        $this->assertSame('accepted', $quote->fresh()->status);
    }

    public function test_a_quote_goes_out_by_sms_with_a_short_portal_link_and_counts_as_sent(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        $this->enableSms($user->company_id);
        $customer = Customer::orderBy('id')->firstOrFail();
        $customer->update(['phone' => '06 12345678', 'email' => null]);
        $quote = $this->draftQuote($customer, 'Sms-offerte');

        $this->get(route('quotes.show', $quote))->assertOk()->assertInertia(fn ($page) => $page
            ->where('quote.sms.available', true)->where('quote.sms.mobile', '+31 6 12345678')->has('quote.sms.text'));
        $preset = app(QuoteManager::class)->smsText($quote->fresh());
        $this->assertStringNotContainsString('concept', $preset, 'geen "(concept)" in de voorgestelde tekst');

        // Het venster stuurt de voorgestelde tekst mee; na het nummeren staat het echte nummer erin.
        $this->post(route('quotes.sms', $quote), ['text' => $preset])->assertRedirect(route('quotes.show', $quote))->assertSessionHas('flash');
        $quote->refresh();
        $this->assertSame('sent', $quote->status);
        $this->assertNotNull($quote->number);
        $this->assertNotNull($quote->portal_token);
        $message = SmsMessage::where('subject_type', $quote->getMorphClass())->where('subject_id', $quote->id)->firstOrFail();
        $this->assertSame('sent', $message->status);
        $this->assertSame('31612345678', $message->recipient);
        $this->assertStringContainsString('offerte ' . $quote->number, $message->body);
        $this->assertStringContainsString(__('Met vriendelijke groet,') . ' ' . explode(' ', trim($user->name))[0], $message->body, 'ondertekend met de voornaam van de verzender');
        $this->assertMatchesRegularExpression('#https?://\S+#', $message->body, 'korte link naar het portaal');
        Http::assertSent(fn ($r) => str_contains($r->url(), 'message/send') && $r['to'] === '31612345678');
        Mail::assertNothingSent();

        // Een eigen tekst mag ook; te lang niet.
        $this->post(route('quotes.sms', $quote), ['text' => 'Hallo, hierbij de offerte: ' . $quote->portalUrl()])->assertRedirect();
        $this->assertSame(2, SmsMessage::where('subject_id', $quote->id)->count());
        $this->post(route('quotes.sms', $quote), ['text' => str_repeat('x', 501)])->assertSessionHasErrors('text');
    }

    public function test_without_a_mobile_number_the_sms_is_refused_and_a_typed_number_is_kept_on_the_customer(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        $this->enableSms($user->company_id);
        $customer = Customer::orderBy('id')->firstOrFail();
        $customer->update(['phone' => null]);
        $quote = $this->draftQuote($customer, 'Sms-zonder-nummer');

        $this->get(route('quotes.show', $quote))->assertInertia(fn ($page) => $page->where('quote.sms.mobile', null));
        $this->post(route('quotes.sms', $quote))->assertSessionHasErrors('sms');
        $this->assertSame('draft', $quote->fresh()->status);

        $this->post(route('quotes.sms', $quote), ['phone' => '0687654321'])->assertRedirect(route('quotes.show', $quote));
        $this->assertSame('sent', $quote->fresh()->status);
        $this->assertSame('0687654321', $customer->fresh()->phone);
    }

    public function test_sms_is_hidden_when_the_sms_service_is_not_configured(): void
    {
        Mail::fake();
        $this->actingAs($this->demoUser());
        config(['services.smstools.client_id' => null, 'services.smstools.client_secret' => null]);
        $quote = $this->draftQuote(Customer::orderBy('id')->firstOrFail(), 'Sms-uit');
        $this->get(route('quotes.show', $quote))->assertInertia(fn ($page) => $page->where('quote.sms', null));
        $this->post(route('quotes.sms', $quote))->assertSessionHasErrors('sms');
    }
}
