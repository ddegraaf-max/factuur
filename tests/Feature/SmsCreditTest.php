<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\PaymentDemand;
use App\Models\SmsCreditEntry;
use App\Models\SmsMessage;
use App\Models\SmsPurchase;
use App\Services\PaymentDemandService;
use App\Services\SmsCreditService;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Sms-tegoed (1.70.0): een administratie koopt een bundel, het tegoed komt er
 * na de betaling bij (één keer) en gaat eraf per verstuurde sms. Met tegoed
 * gaat de laatste aanmaning ook per sms naar het mobiele nummer van de klant.
 */
class SmsCreditTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.smstools.client_id' => 'test-id',
            'services.smstools.client_secret' => 'test-secret',
            // Een administratie die er niet is: niemand verstuurt op kosten van het platform.
            'services.smstools.companies' => '0',
            'services.stripe.secret' => 'sk_test_x',
            'services.stripe.webhook_secret' => 'whsec_test',
            'sms.markup' => 0.05,
        ]);
    }

    private function fakeSms(): void
    {
        // Alles wat verder nog naar buiten wil (op de CI bijvoorbeeld een register), krijgt een leeg antwoord.
        Http::fake(['api.smsgatewayapi.com/*' => Http::response(['messageid' => 'abc123'], 200), '*' => Http::response([], 200)]);
    }

    public function test_bundles_cost_the_purchase_price_plus_the_markup(): void
    {
        $bundles = collect(app(SmsCreditService::class)->bundles())->keyBy('credits');

        $this->assertSame([100, 200, 500, 1000], $bundles->keys()->all());
        $this->assertEqualsWithDelta(0.10, $bundles[200]['per_sms'], 0.0001);
        $this->assertEqualsWithDelta(20.00, $bundles[200]['price_excl'], 0.001);
        $this->assertEqualsWithDelta(4.20, $bundles[200]['vat'], 0.001);
        $this->assertEqualsWithDelta(24.20, $bundles[200]['price_incl'], 0.001);
        $this->assertEqualsWithDelta(0.095, $bundles[500]['per_sms'], 0.0001);
        $this->assertEqualsWithDelta(47.50, $bundles[500]['price_excl'], 0.001);
        $this->assertEqualsWithDelta(90.00, $bundles[1000]['price_excl'], 0.001);

        config(['sms.markup' => 0.02]);
        $this->assertEqualsWithDelta(14.00, collect(app(SmsCreditService::class)->bundles())->firstWhere('credits', 200)['price_excl'], 0.001);
    }

    public function test_buying_a_bundle_goes_through_stripe_and_credits_once(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        Http::fake([
            'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_test_1', 'url' => 'https://checkout.stripe.com/c/pay/cs_test_1'], 200),
            'api.stripe.com/v1/checkout/sessions/cs_test_1' => Http::response([
                'id' => 'cs_test_1', 'payment_status' => 'paid', 'customer' => 'cus_1',
                'metadata' => ['kind' => 'sms_credits', 'sms_purchase_id' => '1', 'company_id' => (string) $user->company_id],
            ], 200),
        ]);

        // De pagina: geen tegoed, vier bundels.
        $this->get(route('settings.sms'))->assertOk()->assertInertia(fn ($page) => $page->component('Settings/Sms')
            ->where('balance', 0)->where('free', false)->has('bundles', 4)->where('bundles.1.credits', 200));
        $this->assertFalse(app(SmsService::class)->available($user->company), 'Zonder tegoed geen sms');

        $this->post(route('settings.sms.buy'), ['credits' => 123])->assertSessionHasErrors('sms');
        $this->post(route('settings.sms.buy'), ['credits' => 200], ['X-Inertia' => 'true'])
            ->assertStatus(409)->assertHeader('X-Inertia-Location', 'https://checkout.stripe.com/c/pay/cs_test_1');

        $purchase = SmsPurchase::firstOrFail();
        $this->assertSame(['pending', 200, 'cs_test_1'], [$purchase->status, $purchase->credits, $purchase->stripe_session_id]);
        $this->assertEqualsWithDelta(24.20, (float) $purchase->price_incl, 0.001);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/checkout/sessions')
            && $request['mode'] === 'payment'
            && (int) $request['line_items[0][price_data][unit_amount]'] === 2420
            && $request['metadata[kind]'] === 'sms_credits'
            && $request['metadata[sms_purchase_id]'] === (string) $purchase->id);
        $this->assertSame(0, app(SmsCreditService::class)->balance($user->company), 'Pas na de betaling');

        // Terug van de betaalpagina: het tegoed staat erbij; nog eens laden telt niet dubbel.
        $this->get(route('settings.sms') . '?betaald=cs_test_1')->assertRedirect(route('settings.sms'));
        $this->get(route('settings.sms') . '?betaald=cs_test_1')->assertRedirect(route('settings.sms'));
        $this->assertSame(200, app(SmsCreditService::class)->balance($user->company));
        $this->assertSame('paid', $purchase->fresh()->status);
        $this->assertSame(1, SmsCreditEntry::where('kind', 'purchase')->count());

        // Ook de melding van Stripe boekt niet nog eens.
        $payload = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => [
            'id' => 'cs_test_1', 'payment_status' => 'paid', 'customer' => 'cus_1', 'client_reference_id' => (string) $user->company_id,
            'metadata' => ['kind' => 'sms_credits', 'sms_purchase_id' => (string) $purchase->id, 'company_id' => (string) $user->company_id],
        ]]]);
        $time = time();
        $signature = 't=' . $time . ',v1=' . hash_hmac('sha256', $time . '.' . $payload, 'whsec_test');
        $this->call('POST', route('stripe.webhook'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $payload)->assertOk();
        $this->assertSame(200, app(SmsCreditService::class)->balance($user->company));
        $this->assertSame('cus_1', $user->company->fresh()->stripe_customer_id);

        // Een andere administratie komt niet bij deze aankoop.
        $other = $this->demoUser();
        $this->assertNull(app(SmsCreditService::class)->fulfilSession($other->company, 'cs_test_1'));

        $this->actingAs($user);
        $this->get(route('settings.sms'))->assertOk()->assertInertia(fn ($page) => $page
            ->where('balance', 200)->has('purchases', 1)->where('purchases.0.credits', 200));
    }

    public function test_the_webhook_alone_credits_too(): void
    {
        $user = $this->demoUser();
        $purchase = SmsPurchase::create([
            'company_id' => $user->company_id, 'user_id' => $user->id, 'credits' => 100, 'price_excl' => 10, 'vat_rate' => 21,
            'price_incl' => 12.10, 'status' => 'pending', 'stripe_session_id' => 'cs_test_2',
        ]);
        $credits = app(SmsCreditService::class);
        $session = ['id' => 'cs_test_2', 'payment_status' => 'unpaid', 'metadata' => ['kind' => 'sms_credits', 'sms_purchase_id' => (string) $purchase->id]];

        $this->assertNull($credits->fulfil($session), 'Niet betaald, geen tegoed');
        $this->assertSame(0, $credits->balance($user->company));

        $credits->fulfil(['payment_status' => 'paid'] + $session);
        $credits->fulfil(['payment_status' => 'paid'] + $session);
        $this->assertSame(100, $credits->balance($user->company));

        // Een sessie met een ander id hoort niet bij deze aankoop.
        $this->assertNull($credits->fulfil(['id' => 'cs_vreemd', 'payment_status' => 'paid'] + $session));
        $this->assertSame(100, $credits->balance($user->company));
    }

    public function test_each_sent_sms_comes_off_the_credit(): void
    {
        $user = $this->demoUser();
        $sms = app(SmsService::class);
        $credits = app(SmsCreditService::class);
        $credits->gift($user->company, 3, 'proef');
        Http::fake(['api.smsgatewayapi.com/*' => Http::sequence()
            ->push(['messageid' => 'a1'], 200)
            ->push(['error' => 1, 'errorMsg' => 'Invalid number'], 400)
            ->push(['messageid' => 'a2'], 200)]);

        $this->assertTrue($sms->available($user->company));
        $this->assertFalse($sms->free($user->company));

        $sms->send($user->company, '06-12345678', 'Een korte sms');
        $this->assertSame(2, $credits->balance($user->company));

        // Een geweigerde sms kost niets.
        try {
            $sms->send($user->company, '06-12345678', 'Wordt geweigerd');
            $this->fail('Een geweigerde sms hoort een melding te geven.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('geen tegoed afgeschreven', $e->getMessage());
        }
        $this->assertSame(2, $credits->balance($user->company));

        // Een lang bericht kost twee sms'en.
        $sms->send($user->company, '06-12345678', str_repeat('Lang bericht. ', 14));
        $this->assertSame(0, $credits->balance($user->company));
        $this->assertSame(2, SmsMessage::where('status', 'sent')->latest('id')->first()->segments);
        $this->assertFalse($sms->available($user->company), 'Tegoed op');

        try {
            $sms->send($user->company, '06-12345678', 'Zonder tegoed');
            $this->fail('Zonder tegoed gaat er niets uit.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('tegoed', $e->getMessage());
        }
        Http::assertSentCount(3);
    }

    public function test_the_last_demand_also_goes_by_sms(): void
    {
        Mail::fake();
        $this->fakeSms();
        $user = $this->demoUser();
        $this->actingAs($user);
        $credits = app(SmsCreditService::class);
        $credits->gift($user->company, 5);

        $invoice = Invoice::where('status', 'overdue')->whereNotNull('customer_email')->firstOrFail();
        $invoice->customer->update(['phone' => '06 1234 5678']);
        $invoice->update(['due_date' => now()->subDays(40)->toDateString()]);

        // Het venster weet dat er een sms bij kan.
        $this->get(route('invoices.show', $invoice))->assertOk()->assertInertia(fn ($page) => $page
            ->where('invoice.demand.sms.available', true)
            ->where('invoice.demand.sms.mobile', '+31 6 12345678')
            ->where('invoice.demand.sms.remaining', 5));

        $this->post(route('demands.store', $invoice), ['debtor_type' => 'business', 'also_sms' => true])
            ->assertRedirect()->assertSessionHasNoErrors();
        $demand = PaymentDemand::firstOrFail();

        // Alleen het verzoek aan Smstools bekijken: op de CI gaan er ook andere verzoeken uit.
        Http::assertSent(fn ($request) => str_contains($request->url(), 'smsgatewayapi.com')
            && $request['to'] === '31612345678'
            && str_contains($request['message'], 'laatste aanmaning voor factuur ' . $invoice->number)
            && str_contains($request['message'], '/u/'));
        $this->assertSame(4, $credits->balance($user->company));
        $this->assertTrue($demand->events()->where('event', 'sms')->exists());

        // Later nog een sms vanaf de lopende aanmaning; de link moet erin blijven.
        $this->post(route('demands.sms', [$invoice, $demand]), ['text' => 'Betaalt u vandaag nog?'])->assertSessionHasErrors('sms');
        $this->post(route('demands.sms', [$invoice, $demand]), [])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(3, $credits->balance($user->company));
        $this->assertSame(2, $demand->events()->where('event', 'sms')->count());

        // Ingetrokken: geen sms meer.
        app(PaymentDemandService::class)->withdraw($demand->fresh());
        $this->post(route('demands.sms', [$invoice, $demand]), [])->assertSessionHasErrors('sms');
        $this->assertSame(3, $credits->balance($user->company));
    }

    public function test_without_a_mobile_number_the_demand_goes_by_mail_only(): void
    {
        Mail::fake();
        $this->fakeSms();
        $user = $this->demoUser();
        $this->actingAs($user);
        app(SmsCreditService::class)->gift($user->company, 5);

        $invoice = Invoice::where('status', 'overdue')->whereNotNull('customer_email')->firstOrFail();
        $invoice->customer->update(['phone' => '0348-416571']);
        $invoice->update(['due_date' => now()->subDays(40)->toDateString()]);

        $this->post(route('demands.store', $invoice), ['debtor_type' => 'business', 'also_sms' => true])
            ->assertRedirect()->assertSessionHasNoErrors();

        Http::assertNothingSent();
        $demand = PaymentDemand::firstOrFail();
        $this->assertSame('sent', $demand->status, 'De aanmaning staat er, ook zonder sms');
        $this->assertTrue($demand->events()->where('event', 'sms_failed')->exists());
        $this->assertSame(5, app(SmsCreditService::class)->balance($user->company));
    }
}
