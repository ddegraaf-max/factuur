<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use App\Services\VatPlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Rozliczenie VAT voor de Poolse markt (lopra_pl): kwoty per stawka voor
 * JPK_V7M/JPK_V7K, saldo, termijn, afronding en de Inertia-pagina Btw/IndexPl.
 */
class VatPlTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    /**
     * Bekende facturen in maart 2025 (ver buiten de relatieve demo-data):
     *   A  05.03  1000 @23 + 200 @8 + 100 @5 + 50 @0
     *   B  20.03  400,40 @23
     *   C  25.03  korekta 100 @23 (telt negatief)
     *   D  10.03  concept 999 @23 (telt niet)
     *   E  10.02  500 @23 (wel in I kwartał, niet in maart)
     * Zakupy: 12.03 netto 300 / VAT 69; 28.03 netto 80,60 / VAT 6,45; 02.05 netto 200 / VAT 46.
     */
    private function seedPolish(Company $company): void
    {
        $customer = Customer::withoutGlobalScopes()->where('company_id', $company->id)->firstOrFail();

        $this->invoice($company, $customer, 'PL-A', '2025-03-05', [[1000, 23], [200, 8], [100, 5], [50, 0]]);
        $this->invoice($company, $customer, 'PL-B', '2025-03-20', [[400.40, 23]]);
        $this->invoice($company, $customer, 'PL-C', '2025-03-25', [[100, 23]], isCredit: true);
        $this->invoice($company, $customer, 'PL-D', '2025-03-10', [[999, 23]], status: 'draft');
        $this->invoice($company, $customer, 'PL-E', '2025-02-10', [[500, 23]]);

        foreach ([['2025-03-12', 300, 69], ['2025-03-28', 80.60, 6.45], ['2025-05-02', 200, 46]] as [$date, $net, $vat]) {
            PurchaseInvoice::create([
                'company_id' => $company->id,
                'supplier_name' => 'Dostawca Sp. z o.o.',
                'invoice_date' => $date,
                'status' => 'open',
                'subtotal' => $net,
                'vat_total' => $vat,
                'total' => round($net + $vat, 2),
            ]);
        }
    }

    private function invoice(Company $company, Customer $customer, string $number, string $date, array $lines, bool $isCredit = false, string $status = 'sent'): Invoice
    {
        $subtotal = 0.0;
        $vatTotal = 0.0;
        $rows = [];
        foreach ($lines as $i => [$net, $rate]) {
            $vat = round($net * $rate / 100, 2);
            $subtotal += $net;
            $vatTotal += $vat;
            $rows[] = [
                'sort_order' => $i, 'description' => "Pozycja {$i}", 'quantity' => 1, 'unit_price' => $net,
                'vat_rate' => $rate, 'line_subtotal' => $net, 'line_vat' => $vat, 'line_total' => round($net + $vat, 2),
            ];
        }

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'number' => $number,
            'status' => $status,
            'is_credit' => $isCredit,
            'invoice_date' => $date,
            'due_date' => $date,
            'customer_name' => $customer->name,
            'customer_country' => 'PL',
            'subtotal' => round($subtotal, 2),
            'vat_total' => round($vatTotal, 2),
            'total' => round($subtotal + $vatTotal, 2),
            'paid_total' => 0,
        ]);
        foreach ($rows as $row) {
            $invoice->lines()->create($row);
        }

        return $invoice;
    }

    public function test_summary_per_rate_balance_due_date_and_rounding(): void
    {
        config(['brand.active' => 'lopra_pl']);
        $company = $this->demoUser()->company;
        $this->seedPolish($company);
        $service = app(VatPlService::class);

        $s = $service->summary($company, 'month', 2025, 3);

        $this->assertSame('marzec 2025', $s['period_label']);
        $this->assertSame('JPK_V7M', $s['form']);
        $this->assertSame('25M03', $s['payment_symbol']);
        $this->assertSame('2025-04-25', $s['due_date']);
        $this->assertSame(2, $s['invoice_count']);
        $this->assertSame(1, $s['credit_count']);
        $this->assertSame(2, $s['purchase_count']);

        // Per stawka: 23% = 1000 + 400,40 − 100; VAT 230 + 92,09 − 23.
        $this->assertEquals(1300.40, $s['sales']['23']['net']);
        $this->assertEquals(299.09, $s['sales']['23']['vat']);
        $this->assertSame(1300, $s['sales']['23']['net_rounded']);
        $this->assertSame(299, $s['sales']['23']['vat_rounded']);
        $this->assertEquals(200.0, $s['sales']['8']['net']);
        $this->assertEquals(16.0, $s['sales']['8']['vat']);
        $this->assertEquals(100.0, $s['sales']['5']['net']);
        $this->assertEquals(5.0, $s['sales']['5']['vat']);
        $this->assertEquals(50.0, $s['sales']['0']['net']);
        $this->assertEquals(0.0, $s['sales']['0']['vat']);
        $this->assertTrue($s['sales']['other']['empty']);
        $this->assertArrayNotHasKey('zw', $s['sales']);

        // Należny, naliczony en saldo — exact én in hele złoty (som van afgeronde velden).
        $this->assertEquals(1650.40, $s['sales_net']);
        $this->assertEquals(320.09, $s['output_vat']);
        $this->assertSame(320, $s['output_vat_rounded']);
        $this->assertEquals(380.60, $s['purchases']['net']);
        $this->assertEquals(75.45, $s['purchases']['vat']);
        $this->assertSame(381, $s['purchases']['net_rounded']);
        $this->assertSame(75, $s['purchases']['vat_rounded']);
        $this->assertEquals(75.45, $s['input_vat']);
        $this->assertEquals(244.64, $s['balance']);
        $this->assertSame(245, $s['balance_rounded']);
        $this->assertSame('to_pay', $s['balance_kind']);
        $this->assertSame('closed', $s['status']);
        $this->assertFalse($s['filing']['filed']);
        $this->assertCount(3, $s['documents']['sales']);
        $this->assertCount(2, $s['documents']['purchases']);

        // Kwartaal: factuur E (februari) komt erbij; de termijn is de 25e na het kwartaal.
        $q = $service->summary($company, 'quarter', 2025, 1);
        $this->assertSame('I kwartał 2025', $q['period_label']);
        $this->assertSame('JPK_V7K', $q['form']);
        $this->assertSame('25K01', $q['payment_symbol']);
        $this->assertSame('2025-04-25', $q['due_date']);
        $this->assertEquals(1800.40, $q['sales']['23']['net']);
        $this->assertEquals(414.09, $q['sales']['23']['vat']);
        $this->assertEquals(435.09, $q['output_vat']);
        $this->assertSame(3, $q['invoice_count']);

        // Alleen zakupy → nadwyżka: do zwrotu / do przeniesienia.
        $r = $service->summary($company, 'month', 2025, 5);
        $this->assertEquals(-46.0, $r['balance']);
        $this->assertSame(-46, $r['balance_rounded']);
        $this->assertSame('refund', $r['balance_kind']);
        $this->assertSame('maj 2025', $r['period_label']);

        // Termijn in het weekend schuift naar maandag: 25 april 2026 is een zaterdag.
        $this->assertSame('2026-04-27', $service->periods('month', 2026)[3]['due_date']->toDateString());
        $this->assertSame('2026-10-26', $service->periods('quarter', 2026)[3]['due_date']->toDateString());

        // Afronding art. 63 Ordynacji podatkowej: < 50 gr weg, ≥ 50 gr omhoog.
        $this->assertSame(0, VatPlService::zl(0.49));
        $this->assertSame(1, VatPlService::zl(0.50));
        $this->assertSame(1234, VatPlService::zl(1234.49));
        $this->assertSame(-2, VatPlService::zl(-1.5));
        $this->assertSame(-1, VatPlService::zl(-1.49));

        // Standaard okres-type: 'year' kent Polen niet → maand.
        $company->forceFill(['vat_period' => 'year'])->save();
        $this->assertSame('month', VatPlService::periodType($company->fresh()));
        $company->forceFill(['vat_period' => 'quarter'])->save();
        $this->assertSame('quarter', VatPlService::periodType($company->fresh()));
    }

    public function test_polish_market_renders_jpk_page_with_filing_status(): void
    {
        config(['brand.active' => 'lopra_pl']);
        $user = $this->demoUser();
        $this->seedPolish($user->company);
        $user->company->forceFill(['vat_period' => 'month'])->save();
        $this->actingAs($user);

        $this->get(route('vat.index', ['year' => 2025, 'type' => 'month', 'period' => 3]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Btw/IndexPl')
                ->where('year', 2025)
                ->where('periodType', 'month')
                ->where('period', 3)
                ->where('summary.period_label', 'marzec 2025')
                ->where('summary.balance_rounded', 245)
                ->where('summary.filing.filed', false)
                ->where('settings.vat_period', 'month')
                ->has('periods', 12)
                ->has('allYears'));

        // Kwartaal via de query; een onbekend type/okres valt terug op de standaard.
        $this->get(route('vat.index', ['year' => 2025, 'type' => 'quarter', 'period' => 1]))
            ->assertInertia(fn ($page) => $page->component('Btw/IndexPl')->where('summary.form', 'JPK_V7K')->has('periods', 4));
        $this->get(route('vat.index', ['year' => 2025, 'type' => 'year', 'period' => 99]))
            ->assertInertia(fn ($page) => $page->component('Btw/IndexPl')->where('periodType', 'month')->where('period', 12));

        // Status delen met het VatFiling-model via de bestaande route.
        $this->patch(route('vat.filing.update', ['year' => 2025, 'type' => 'month', 'period' => 3]), ['filed' => true])->assertRedirect();
        $this->get(route('vat.index', ['year' => 2025, 'type' => 'month', 'period' => 3]))
            ->assertInertia(fn ($page) => $page->where('summary.filing.filed', true)->where('periods.2.filed', true));
    }

    public function test_dutch_market_keeps_the_dutch_page(): void
    {
        $this->actingAs($this->demoUser());

        $this->get(route('vat.index'))->assertOk()->assertInertia(fn ($page) => $page->component('Btw/Index'));
    }
}
