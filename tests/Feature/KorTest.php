<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\User;
use App\Services\UblGenerator;
use App\Services\VatService;
use App\Support\Kor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Kleineondernemersregeling: geen btw op factuur en offerte, wel de vermelding
 * van de vrijstelling; geen aangifte, en de omzet tegenover de grens.
 */
class KorTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private function korUser(): User
    {
        $user = $this->demoUser();
        $user->company->forceFill(['kor' => true])->save();

        return $user->fresh();
    }

    private function payload(Customer $customer, array $extra = []): array
    {
        return $extra + [
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'payment_terms' => 14,
            'reference' => 'Tuinontwerp',
            'lines' => [
                ['description' => 'Ontwerp', 'quantity' => 1, 'unit' => 'stuk', 'unit_price' => 400, 'vat_rate' => 21, 'discount_pct' => 0],
                ['description' => 'Planten', 'quantity' => 2, 'unit' => 'stuk', 'unit_price' => 50, 'vat_rate' => 9, 'discount_pct' => 0],
            ],
            'action' => 'draft',
        ];
    }

    public function test_the_setting_is_saved_with_the_company_details(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);

        $this->get(route('settings.company'))->assertOk();
        $this->patch(route('settings.company.update'), [
            'name' => $user->company->name, 'country' => 'NL', 'default_payment_terms' => 30, 'kor' => true,
        ])->assertSessionHasNoErrors();
        $this->assertTrue($user->company->fresh()->kor);

        $this->patch(route('settings.company.update'), [
            'name' => $user->company->name, 'country' => 'NL', 'default_payment_terms' => 30, 'kor' => false,
        ])->assertSessionHasNoErrors();
        $this->assertFalse($user->company->fresh()->kor);
    }

    public function test_an_invoice_under_the_scheme_has_no_vat_and_says_why(): void
    {
        $this->actingAs($this->korUser());
        $customer = Customer::orderBy('id')->firstOrFail();

        // Ook als het formulier om btw verlegd vraagt: de vrijstelling gaat voor.
        $this->post(route('invoices.store'), $this->payload($customer, ['vat_reversed' => true]))
            ->assertSessionHasNoErrors()->assertRedirect();

        $invoice = Invoice::where('reference', 'Tuinontwerp')->with('lines')->firstOrFail();
        $this->assertTrue($invoice->vat_exempt);
        $this->assertFalse($invoice->vat_reversed);
        $this->assertSame('exempt', $invoice->vatTreatment());
        $this->assertEquals(500.0, (float) $invoice->total);
        $this->assertEquals(0.0, (float) $invoice->vat_total);
        $this->assertSame([0.0, 0.0], $invoice->lines->map(fn ($l) => (float) $l->vat_rate)->all());

        foreach (['modern', 'classic', 'minimal', 'stationery'] as $template) {
            $html = view("pdf.invoice-{$template}", ['invoice' => $invoice, 'company' => $invoice->brandedCompany()])->render();
            $this->assertStringContainsString('Factuur vrijgesteld van OB op grond van artikel 25 Wet OB (kleineondernemersregeling).', $html, $template);
            $this->assertStringContainsString('Vrijgesteld van btw', $html, $template);
            $this->assertStringNotContainsString('BTW 0%', $html, $template);
            $this->assertStringNotContainsString('Btw verlegd', $html, $template);
        }

        $this->get(route('invoices.show', $invoice))->assertOk()->assertInertia(fn ($page) => $page
            ->where('invoice.vat_exempt', true)->where('invoice.kor_mismatch', false));
        $this->get(route('invoices.edit', $invoice))->assertOk()->assertInertia(fn ($page) => $page->where('vat_exempt', true));
        $this->get(route('invoices.create'))->assertOk()->assertInertia(fn ($page) => $page->where('vat_exempt', true));

        $xml = app(UblGenerator::class)->generate($invoice);
        $this->assertStringContainsString('<cbc:ID>E</cbc:ID>', $xml);
        $this->assertStringContainsString('artikel 25 Wet OB', $xml);
        $this->assertNotFalse(simplexml_load_string($xml));
    }

    public function test_a_quote_under_the_scheme_has_no_vat(): void
    {
        Mail::fake();
        $this->actingAs($this->korUser());
        $customer = Customer::orderBy('id')->firstOrFail();

        $this->post(route('quotes.store'), [
            'customer_id' => $customer->id, 'quote_date' => now()->toDateString(), 'valid_days' => 30,
            'reference' => 'Tuinontwerp', 'lines' => $this->payload($customer)['lines'], 'action' => 'draft',
        ])->assertSessionHasNoErrors();

        $quote = Quote::where('reference', 'Tuinontwerp')->with('lines')->firstOrFail();
        $this->assertTrue($quote->vat_exempt);
        $this->assertEquals(500.0, (float) $quote->total);
        $this->assertStringContainsString('artikel 25 Wet OB', view('pdf.quote', ['quote' => $quote, 'company' => $quote->brandedCompany()])->render());

        $this->get(route('quotes.create'))->assertOk()->assertInertia(fn ($page) => $page->where('vat_exempt', true));
        $this->post(route('quotes.convert', $quote))->assertRedirect();
        $this->assertTrue(Invoice::findOrFail($quote->fresh()->converted_invoice_id)->vat_exempt);
    }

    public function test_a_draft_with_vat_is_flagged_until_it_is_saved_again(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $customer = Customer::orderBy('id')->firstOrFail();

        $this->post(route('invoices.store'), $this->payload($customer))->assertSessionHasNoErrors();
        $invoice = Invoice::where('reference', 'Tuinontwerp')->firstOrFail();
        $this->assertEquals(593.0, (float) $invoice->total);   // 400 + 21% en 100 + 9%

        $user->company->forceFill(['kor' => true])->save();
        $this->actingAs($user->fresh());

        $this->get(route('invoices.show', $invoice))->assertOk()->assertInertia(fn ($page) => $page->where('invoice.kor_mismatch', true));

        $this->put(route('invoices.update', $invoice), $this->payload($customer))->assertSessionHasNoErrors();
        $this->assertEquals(500.0, (float) $invoice->fresh()->total);
        $this->assertTrue($invoice->fresh()->vat_exempt);
        $this->get(route('invoices.show', $invoice))->assertOk()->assertInertia(fn ($page) => $page->where('invoice.kor_mismatch', false));
    }

    public function test_an_invoice_keeps_its_statement_after_leaving_the_scheme(): void
    {
        Mail::fake();
        $user = $this->korUser();
        $this->actingAs($user);
        $customer = Customer::orderBy('id')->firstOrFail();

        $this->post(route('invoices.store'), $this->payload($customer, ['action' => 'send']))->assertSessionHasNoErrors();
        $sent = Invoice::where('reference', 'Tuinontwerp')->firstOrFail();

        $user->company->forceFill(['kor' => false])->save();
        $this->actingAs($user->fresh());

        $this->assertTrue($sent->fresh()->vat_exempt);
        $this->post(route('invoices.store'), $this->payload($customer, ['reference' => 'Na afmelden']))->assertSessionHasNoErrors();
        $after = Invoice::where('reference', 'Na afmelden')->firstOrFail();
        $this->assertFalse($after->vat_exempt);
        $this->assertEquals(593.0, (float) $after->total);
    }

    public function test_no_return_is_due_and_the_turnover_is_set_against_the_limit(): void
    {
        Mail::fake();
        $user = $this->korUser();
        $this->actingAs($user);
        $company = $user->company;

        $attention = app(VatService::class)->attention($company);
        $this->assertNull($attention['due']);
        $this->assertNull($attention['current']);

        $before = Kor::status($company)['revenue'];
        $customer = Customer::orderBy('id')->firstOrFail();
        $this->post(route('invoices.store'), $this->payload($customer, ['action' => 'send']))->assertSessionHasNoErrors();

        $status = Kor::status($company);
        $this->assertEqualsWithDelta($before + 500.0, $status['revenue'], 0.01);
        $this->assertSame(20000.0, $status['limit']);

        $this->get(route('dashboard'))->assertOk()->assertInertia(fn ($page) => $page
            ->where('vat_due', null)
            ->where('kpis.kor.year', (int) now()->year)
            ->where('kpis.kor.state', $status['state']));
        $this->get(route('vat.index'))->assertOk()->assertInertia(fn ($page) => $page->where('kor.state', $status['state']));
    }

    public function test_the_warning_comes_near_and_over_the_limit(): void
    {
        $user = $this->korUser();
        $this->actingAs($user);
        $customer = Customer::orderBy('id')->firstOrFail();
        // Een schone lei: alleen de facturen uit deze test tellen.
        Invoice::query()->update(['status' => 'draft']);

        $add = function (float $amount) use ($customer) {
            $invoice = app(\App\Services\InvoiceManager::class)->create([
                'customer_id' => $customer->id,
                'lines' => [['description' => 'Werk', 'quantity' => 1, 'unit_price' => $amount, 'vat_rate' => 21]],
            ]);
            $invoice->forceFill(['status' => 'sent', 'number' => 'T-' . $invoice->id])->save();
        };

        $add(15000);
        $this->assertSame('ok', Kor::status($user->company)['state']);
        $add(1500);
        $this->assertSame('near', Kor::status($user->company)['state']);
        $add(4000);
        $status = Kor::status($user->company);
        $this->assertSame('over', $status['state']);
        $this->assertSame(100, $status['percent']);
    }

    public function test_without_the_scheme_nothing_changes(): void
    {
        $this->actingAs($this->demoUser());
        $customer = Customer::orderBy('id')->firstOrFail();

        $this->post(route('invoices.store'), $this->payload($customer))->assertSessionHasNoErrors();
        $invoice = Invoice::where('reference', 'Tuinontwerp')->firstOrFail();

        $this->assertFalse($invoice->vat_exempt);
        $this->assertNull($invoice->vatTreatment());
        $this->assertEquals(593.0, (float) $invoice->total);
        $this->get(route('dashboard'))->assertOk()->assertInertia(fn ($page) => $page->where('kpis.kor', null));
    }
}
