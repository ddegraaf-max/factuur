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
}
