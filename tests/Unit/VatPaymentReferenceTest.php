<?php

namespace Tests\Unit;

use App\Support\VatPaymentReference;
use PHPUnit\Framework\TestCase;

/**
 * Betalingskenmerk omzetbelasting — gecontroleerd tegen het officiële
 * voorbeeld uit "Specificatie Betalingskenmerk bepaling v1.5" (Belastingdienst):
 * aangiftenummer 036000012.B.02 tijdvak 3270 (Q3 2023) → 0036 0000 1130 2270.
 */
class VatPaymentReferenceTest extends TestCase
{
    public function test_official_example_third_quarter_2023(): void
    {
        $this->assertSame('0036000011302270', VatPaymentReference::forPeriod('036000012B02', 2023, 'quarter', 3));
        $this->assertSame('0036 0000 1130 2270', VatPaymentReference::format('0036000011302270'));
    }

    public function test_check_digit_algorithm_matches_specification(): void
    {
        // Tweede voorbeeld uit de spec (aangifte LB, november 2023): zelfde
        // elfproef, andere middelcode → controlecijfer 2.
        $this->assertSame(2, VatPaymentReference::checkDigit('036000016301110'));
        $this->assertSame(0, VatPaymentReference::checkDigit('036000011302270'));
    }

    public function test_period_codes(): void
    {
        $this->assertSame('21', VatPaymentReference::periodCode('quarter', 1));
        $this->assertSame('24', VatPaymentReference::periodCode('quarter', 2));
        $this->assertSame('27', VatPaymentReference::periodCode('quarter', 3));
        $this->assertSame('30', VatPaymentReference::periodCode('quarter', 4));
        $this->assertSame('01', VatPaymentReference::periodCode('month', 1));
        $this->assertSame('12', VatPaymentReference::periodCode('month', 12));
        $this->assertSame('40', VatPaymentReference::periodCode('year', 1));
        $this->assertNull(VatPaymentReference::periodCode('quarter', 5));
        $this->assertNull(VatPaymentReference::periodCode('week', 1));
    }

    public function test_parses_ob_number_in_common_notations(): void
    {
        $this->assertSame(['fiscal' => '123456782', 'sub' => '01'], VatPaymentReference::parseObNumber('NL123456782B01'));
        $this->assertSame(['fiscal' => '123456782', 'sub' => '02'], VatPaymentReference::parseObNumber('1234.56.782.B.02'));
        $this->assertSame(['fiscal' => '123456782', 'sub' => '01'], VatPaymentReference::parseObNumber('123456782'));
        $this->assertNull(VatPaymentReference::parseObNumber('12345'));
        $this->assertNull(VatPaymentReference::parseObNumber(null));
    }

    public function test_validation_and_normalisation(): void
    {
        $this->assertTrue(VatPaymentReference::isValid('0036 0000 1130 2270'));
        $this->assertFalse(VatPaymentReference::isValid('1036000011302270'));
        $this->assertFalse(VatPaymentReference::isValid('123'));
        $this->assertSame('0036000011302270', VatPaymentReference::normalize('0036-0000-1130-2270'));
        $this->assertNull(VatPaymentReference::normalize('0036'));
    }

    public function test_sequence_number_and_year_digit(): void
    {
        $ref = VatPaymentReference::forPeriod('123456782B01', 2026, 'month', 5, 1);
        $this->assertNotNull($ref);
        // pos 2-9 fiscaal (8 cijfers), pos 10 = 1 (OB), pos 11 = jaar '6', 12-13 sub, 14-15 '05', 16 volgnummer.
        $this->assertSame('12345678' . '1' . '6' . '01' . '05' . '1', substr($ref, 1));
        $this->assertTrue(VatPaymentReference::isValid($ref));
    }
}
