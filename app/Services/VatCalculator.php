<?php

namespace App\Services;

/**
 * VatCalculator
 *
 * Calculates VAT for invoice lines and totals per VAT bucket.
 * All calculations are rounded to 2 decimals using bankers' rounding
 * matching Dutch accounting practice.
 *
 * Supported NL rates: 21% (high), 9% (low/food/books), 0% (export, intra-EU B2B reverse charge).
 */
class VatCalculator
{
    /**
     * Calculate a single line's totals.
     *
     * @param  float  $quantity
     * @param  float  $unitPrice  Excluding VAT
     * @param  float  $vatRate    Percentage, e.g. 21.0
     * @return array{subtotal: float, vat: float, total: float}
     */
    public function calculateLine(float $quantity, float $unitPrice, float $vatRate): array
    {
        $subtotal = $this->round($quantity * $unitPrice);
        $vat = $this->round($subtotal * ($vatRate / 100));
        $total = $this->round($subtotal + $vat);

        return [
            'subtotal' => $subtotal,
            'vat' => $vat,
            'total' => $total,
        ];
    }

    /**
     * Calculate invoice totals from an array of lines.
     * Each line must have: quantity, unit_price, vat_rate.
     *
     * @param  array  $lines
     * @return array{subtotal: float, vat_total: float, total: float, vat_breakdown: array<string,float>}
     */
    public function calculateInvoice(array $lines): array
    {
        $subtotal = 0.0;
        $vatTotal = 0.0;
        $breakdown = []; // rate => vat amount

        foreach ($lines as $line) {
            $qty = (float) ($line['quantity'] ?? 1);
            $price = (float) ($line['unit_price'] ?? 0);
            $rate = (float) ($line['vat_rate'] ?? 0);

            $lineCalc = $this->calculateLine($qty, $price, $rate);
            $subtotal += $lineCalc['subtotal'];
            $vatTotal += $lineCalc['vat'];

            $rateKey = (string) $rate;
            $breakdown[$rateKey] = ($breakdown[$rateKey] ?? 0) + $lineCalc['vat'];
        }

        // Round breakdown amounts
        foreach ($breakdown as $rate => $amount) {
            $breakdown[$rate] = $this->round($amount);
        }

        return [
            'subtotal' => $this->round($subtotal),
            'vat_total' => $this->round($vatTotal),
            'total' => $this->round($subtotal + $vatTotal),
            'vat_breakdown' => $breakdown,
        ];
    }

    /**
     * Round to 2 decimals using Dutch standard (round-half-up).
     */
    public function round(float $value): float
    {
        return round($value, 2, PHP_ROUND_HALF_UP);
    }

    /**
     * Standard NL VAT rates available.
     */
    public static function availableRates(): array
    {
        return [
            ['value' => 21.00, 'label' => '21% (hoog tarief)'],
            ['value' => 9.00, 'label' => '9% (laag tarief)'],
            ['value' => 0.00, 'label' => '0% / vrijgesteld'],
        ];
    }
}
