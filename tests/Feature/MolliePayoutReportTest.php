<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\OnlinePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Rapport Mollie-betalingen (1.81.0): online betaalde facturen per
 * uitbetaling van Mollie, als pagina, pdf en csv, met de uitbetalingsgegevens
 * opgehaald bij Mollie. De boekhouder ziet één bedrag op het bankafschrift
 * en wil weten welke facturen daarin zitten.
 */
class MolliePayoutReportTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private function companyWithPayments(): Company
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $company = $user->company;
        $company->forceFill(['mollie_api_key' => 'live_testkey'])->save();

        $invoices = Invoice::where('company_id', $company->id)->where('is_credit', false)->whereNotNull('number')->orderBy('id')->take(3)->get();
        $this->assertCount(3, $invoices);
        $month = now()->startOfMonth();

        // Twee betalingen in een uitgekeerde uitbetaling, één nog zonder.
        OnlinePayment::create([
            'company_id' => $company->id, 'invoice_id' => $invoices[0]->id, 'mollie_id' => 'tr_aaa111', 'amount' => 121.00,
            'status' => 'paid', 'method' => 'ideal', 'paid_at' => $month->copy()->addDays(1)->setTime(10, 0),
            'settlement_id' => 'stl_111', 'settlement_reference' => '1234567.2610.03', 'settlement_status' => 'paidout',
            'settled_on' => $month->copy()->addDays(5)->toDateString(), 'settlement_total' => 362.41,
        ]);
        OnlinePayment::create([
            'company_id' => $company->id, 'invoice_id' => $invoices[1]->id, 'mollie_id' => 'tr_bbb222', 'amount' => 242.00,
            'status' => 'paid', 'method' => 'ideal', 'paid_at' => $month->copy()->addDays(2)->setTime(11, 0),
            'settlement_id' => 'stl_111', 'settlement_reference' => '1234567.2610.03', 'settlement_status' => 'paidout',
            'settled_on' => $month->copy()->addDays(5)->toDateString(), 'settlement_total' => 362.41,
        ]);
        OnlinePayment::create([
            'company_id' => $company->id, 'invoice_id' => $invoices[2]->id, 'mollie_id' => 'tr_ccc333', 'amount' => 50.00,
            'status' => 'paid', 'method' => 'creditcard', 'paid_at' => $month->copy()->addDays(3)->setTime(12, 0),
        ]);
        // Een open (niet betaalde) poging hoort er niet in.
        OnlinePayment::create([
            'company_id' => $company->id, 'invoice_id' => $invoices[2]->id, 'mollie_id' => 'tr_ddd444', 'amount' => 50.00,
            'status' => 'open', 'paid_at' => null,
        ]);

        return $company;
    }

    public function test_the_report_groups_payments_per_payout(): void
    {
        $this->companyWithPayments();

        $this->get(route('mollie.index'))->assertOk()->assertInertia(fn ($page) => $page
            ->component('Reports/Mollie')
            ->where('enabled', true)
            ->where('totals.count', 3)
            ->where('totals.received', fn ($v) => (float) $v === 413.0)
            ->where('totals.settled', fn ($v) => (float) $v === 363.0)
            ->where('totals.unsettled', fn ($v) => (float) $v === 50.0)
            ->where('totals.payouts', 1)
            ->has('groups', 2)
            ->where('groups.0.reference', '1234567.2610.03')
            ->where('groups.0.gross', fn ($v) => (float) $v === 363.0)
            ->where('groups.0.total', fn ($v) => (float) $v === 362.41)
            ->where('groups.0.difference', fn ($v) => (float) $v === -0.59)
            ->has('groups.0.rows', 2)
            ->where('groups.1.settlement_id', null)
            ->has('groups.1.rows', 1)
            ->where('groups.1.rows.0.method', 'Creditcard'));

        // Een andere maand is leeg.
        $this->get(route('mollie.index', ['month' => now()->subMonths(3)->format('Y-m')]))->assertOk()
            ->assertInertia(fn ($page) => $page->has('groups', 0)->where('totals.count', 0));
    }

    public function test_refresh_fetches_payout_details_from_mollie(): void
    {
        $company = $this->companyWithPayments();
        Http::fake([
            'https://api.mollie.com/v2/payments/tr_ccc333' => Http::response(['id' => 'tr_ccc333', 'status' => 'paid', 'settlementId' => 'stl_222']),
            'https://api.mollie.com/v2/settlements/stl_222' => Http::response([
                'id' => 'stl_222', 'reference' => '1234567.2610.04', 'status' => 'paidout',
                'settledAt' => now()->toIso8601String(), 'amount' => ['value' => '49.71', 'currency' => 'EUR'],
            ]),
            'https://api.mollie.com/v2/settlements/stl_111' => Http::response(['id' => 'stl_111', 'reference' => '1234567.2610.03', 'status' => 'paidout', 'settledAt' => now()->toIso8601String(), 'amount' => ['value' => '362.41', 'currency' => 'EUR']]),
        ]);

        $this->post(route('mollie.refresh'))->assertRedirect()->assertSessionHas('flash');

        $payment = OnlinePayment::where('mollie_id', 'tr_ccc333')->firstOrFail();
        $this->assertSame('stl_222', $payment->settlement_id);
        $this->assertSame('1234567.2610.04', $payment->settlement_reference);
        $this->assertSame('paidout', $payment->settlement_status);
        $this->assertSame(now()->toDateString(), $payment->settled_on->toDateString());
        $this->assertSame(49.71, (float) $payment->settlement_total);
        $this->assertNotNull($payment->settlement_checked_at);

        // De uitgekeerde uitbetaling wordt niet opnieuw opgevraagd; de open betaalpoging ook niet.
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'tr_ddd444') || str_contains($request->url(), 'tr_aaa111'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'settlements/stl_111'));

        $this->get(route('mollie.index'))->assertInertia(fn ($page) => $page->has('groups', 2)->where('totals.unsettled', fn ($v) => (float) $v === 0.0)->where('totals.payouts', 2));
    }

    public function test_a_rejected_api_key_gives_a_clear_message(): void
    {
        $this->companyWithPayments();
        Http::fake(['https://api.mollie.com/*' => Http::response(['status' => 401, 'title' => 'Unauthorized'], 401)]);

        $this->post(route('mollie.refresh'))->assertRedirect()->assertSessionHasErrors('mollie');
        $this->assertNull(OnlinePayment::where('mollie_id', 'tr_ccc333')->value('settlement_id'));
    }

    public function test_pdf_csv_and_payout_pdf(): void
    {
        $company = $this->companyWithPayments();
        $month = now()->format('Y-m');

        $pdf = $this->get(route('mollie.pdf', ['month' => $month]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString("mollie-betalingen-{$month}.pdf", (string) $pdf->headers->get('Content-Disposition'));

        $csv = (string) $this->get(route('mollie.csv', ['month' => $month]))->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')->getContent();
        $this->assertStringContainsString('1234567.2610.03', $csv);
        $this->assertStringContainsString('tr_ccc333', $csv);
        $this->assertSame(4, substr_count($csv, "\n"), 'kopregel plus drie betalingen');

        $payout = $this->get(route('mollie.payout.pdf', 'stl_111'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('mollie-uitbetaling-1234567.2610.03.pdf', (string) $payout->headers->get('Content-Disposition'));

        // Een uitbetaling van een andere administratie bestaat hier niet.
        $other = $this->demoUser()->company;
        OnlinePayment::create([
            'company_id' => $other->id, 'invoice_id' => Invoice::withoutGlobalScope('company')->where('company_id', $other->id)->value('id'), 'mollie_id' => 'tr_other',
            'amount' => 10, 'status' => 'paid', 'paid_at' => now(), 'settlement_id' => 'stl_other',
        ]);
        $this->actingAs($company->users()->firstOrFail());
        $this->get(route('mollie.payout.pdf', 'stl_other'))->assertNotFound();
    }

    public function test_the_nightly_command_checks_every_connected_company(): void
    {
        $this->companyWithPayments();
        Http::fake(['https://api.mollie.com/*' => Http::response(['id' => 'tr_ccc333', 'status' => 'paid'])]);

        $this->artisan('mollie:settlements')->assertExitCode(0);

        $this->assertNotNull(OnlinePayment::where('mollie_id', 'tr_ccc333')->value('settlement_checked_at'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'payments/tr_ccc333'));
    }
}
