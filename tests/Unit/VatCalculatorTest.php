<?php

namespace Tests\Unit;

use App\Services\VatCalculator;
use PHPUnit\Framework\TestCase;

class VatCalculatorTest extends TestCase
{
    private VatCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new VatCalculator();
    }

    public function test_calculates_a_simple_line(): void
    {
        $result = $this->calc->calculateLine(2, 100.00, 21);
        $this->assertSame(200.00, $result['subtotal']);
        $this->assertSame(42.00, $result['vat']);
        $this->assertSame(242.00, $result['total']);
    }

    public function test_calculates_low_rate_line(): void
    {
        $result = $this->calc->calculateLine(3, 19.50, 9);
        $this->assertSame(58.50, $result['subtotal']);
        $this->assertSame(5.27, $result['vat']);  // 58.50 * 0.09 = 5.265, rounded up
        $this->assertSame(63.77, $result['total']);
    }

    public function test_calculates_zero_rate_line(): void
    {
        $result = $this->calc->calculateLine(1, 500, 0);
        $this->assertSame(500.00, $result['subtotal']);
        $this->assertSame(0.00, $result['vat']);
        $this->assertSame(500.00, $result['total']);
    }

    public function test_calculates_invoice_with_multiple_rates(): void
    {
        $result = $this->calc->calculateInvoice([
            ['quantity' => 1, 'unit_price' => 1000, 'vat_rate' => 21],
            ['quantity' => 2, 'unit_price' => 50, 'vat_rate' => 9],
            ['quantity' => 1, 'unit_price' => 200, 'vat_rate' => 0],
        ]);

        $this->assertSame(1300.00, $result['subtotal']);
        $this->assertSame(219.00, $result['vat_total']); // 210 + 9 + 0
        $this->assertSame(1519.00, $result['total']);
        $this->assertSame(210.00, $result['vat_breakdown']['21']);
        $this->assertSame(9.00, $result['vat_breakdown']['9']);
        $this->assertSame(0.00, $result['vat_breakdown']['0']);
    }

    public function test_rounds_halves_up(): void
    {
        // 0.005 should round to 0.01
        $this->assertSame(0.01, $this->calc->round(0.005));
        // 1.235 should round to 1.24
        $this->assertSame(1.24, $this->calc->round(1.235));
    }

    public function test_handles_fractional_quantities(): void
    {
        // 1.5 hours at €95 + 21% VAT
        $result = $this->calc->calculateLine(1.5, 95.00, 21);
        $this->assertSame(142.50, $result['subtotal']);
        $this->assertSame(29.93, $result['vat']); // 142.50 * 0.21 = 29.925, rounds up
        $this->assertSame(172.43, $result['total']);
    }

    /* ============ PRIJZEN INCLUSIEF BTW ============ */

    public function test_incl_mode_splits_a_gross_price(): void
    {
        // €121 inclusief 21% btw = €100 netto + €21 btw
        $result = $this->calc->calculateLine(1, 121.00, 21, 'incl');
        $this->assertSame(100.00, $result['subtotal']);
        $this->assertSame(21.00, $result['vat']);
        $this->assertSame(121.00, $result['total']);
    }

    public function test_incl_mode_keeps_the_customer_facing_total_exact(): void
    {
        // 3 × €100 incl. btw moet precies €300 blijven — dát is het bedrag
        // dat met de klant is afgesproken.
        $result = $this->calc->calculateLine(3, 100.00, 21, 'incl');
        $this->assertSame(300.00, $result['total']);
        $this->assertSame(247.93, $result['subtotal']);
        $this->assertSame(52.07, $result['vat']);
        $this->assertSame(
            $result['total'],
            $this->calc->round($result['subtotal'] + $result['vat'])
        );
    }

    public function test_incl_mode_with_low_rate(): void
    {
        // €10,90 inclusief 9% btw
        $result = $this->calc->calculateLine(2, 10.90, 9, 'incl');
        $this->assertSame(21.80, $result['total']);
        $this->assertSame(20.00, $result['subtotal']);
        $this->assertSame(1.80, $result['vat']);
    }

    public function test_incl_mode_with_zero_rate_changes_nothing(): void
    {
        $result = $this->calc->calculateLine(4, 25.00, 0, 'incl');
        $this->assertSame(100.00, $result['subtotal']);
        $this->assertSame(0.00, $result['vat']);
        $this->assertSame(100.00, $result['total']);
    }

    public function test_incl_mode_invoice_totals(): void
    {
        $result = $this->calc->calculateInvoice([
            ['quantity' => 1, 'unit_price' => 121.00, 'vat_rate' => 21],
            ['quantity' => 2, 'unit_price' => 10.90, 'vat_rate' => 9],
        ], 'incl');

        $this->assertSame(142.80, $result['total']); // 121.00 + 21.80
        $this->assertSame(120.00, $result['subtotal']); // 100.00 + 20.00
        $this->assertSame(22.80, $result['vat_total']); // 21.00 + 1.80
    }

    public function test_net_unit_price_conversion(): void
    {
        $this->assertSame(100.00, $this->calc->netUnitPrice(121.00, 21));
        $this->assertSame(82.64, $this->calc->netUnitPrice(100.00, 21));
        $this->assertSame(25.00, $this->calc->netUnitPrice(25.00, 0));
    }

    public function test_excl_stays_the_default(): void
    {
        $withMode = $this->calc->calculateLine(2, 100.00, 21, 'excl');
        $withoutMode = $this->calc->calculateLine(2, 100.00, 21);
        $this->assertSame($withMode, $withoutMode);
    }
}
