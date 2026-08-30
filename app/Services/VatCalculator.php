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
     * @param  float   $quantity
     * @param  float   $unitPrice    Excluding VAT (mode 'excl') or including VAT (mode 'incl')
     * @param  float   $vatRate      Percentage, e.g. 21.0
     * @param  string  $mode         'excl' (default) or 'incl'
     * @param  float   $discountPct  Regelkorting in procenten (0–100)
     * @return array{subtotal: float, vat: float, total: float}
     */
    public function calculateLine(float $quantity, float $unitPrice, float $vatRate, string $mode = 'excl', float $discountPct = 0): array
    {
        // Korting vóór BTW: de grondslag daalt, dus de BTW daalt automatisch mee.
        $factor = 1 - min(100, max(0, $discountPct)) / 100;

        if ($mode === 'incl') {
            // De klant typt brutobedragen. Reken vanaf het regeltotaal terug,
            // niet vanaf de stuksprijs: anders loopt het bij meerdere stuks
            // centen uit de pas met wat de klant op de factuur ziet staan.
            $total = $this->round($quantity * $unitPrice * $factor);
            $subtotal = $this->round($total / (1 + $vatRate / 100));
            $vat = $this->round($total - $subtotal);

            return [
                'subtotal' => $subtotal,
                'vat' => $vat,
                'total' => $total,
            ];
        }

        $subtotal = $this->round($quantity * $unitPrice * $factor);
        $vat = $this->round($subtotal * ($vatRate / 100));
        $total = $this->round($subtotal + $vat);

        return [
            'subtotal' => $subtotal,
            'vat' => $vat,
            'total' => $total,
        ];
    }

    /**
     * Zet een brutostuksprijs (incl. btw) om naar de nettoprijs die we opslaan.
     * In de database staat `unit_price` altijd exclusief btw — zo blijven de
     * PDF, de UBL-factuur en de boekhoudexport kloppen, ongeacht hoe de
     * ondernemer zijn prijzen invoert.
     */
    public function netUnitPrice(float $grossUnitPrice, float $vatRate): float
    {
        return $this->round($grossUnitPrice / (1 + $vatRate / 100));
    }

    /**
     * Calculate invoice totals from an array of lines.
     * Each line must have: quantity, unit_price, vat_rate.
     *
     * @param  array   $lines
     * @param  string  $mode  'excl' (prijzen exclusief btw) of 'incl'
     * @return array{subtotal: float, vat_total: float, total: float, vat_breakdown: array<string,float>}
     */
    public function calculateInvoice(array $lines, string $mode = 'excl'): array
    {
        $subtotal = 0.0;
        $vatTotal = 0.0;
        $breakdown = []; // rate => vat amount

        foreach ($lines as $line) {
            $qty = (float) ($line['quantity'] ?? 1);
            $price = (float) ($line['unit_price'] ?? 0);
            $rate = (float) ($line['vat_rate'] ?? 0);
            $discount = (float) ($line['discount_pct'] ?? 0);

            $lineCalc = $this->calculateLine($qty, $price, $rate, $mode, $discount);
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
     * De btw-tarieven van de markt (config/markets.php) als keuzelijst:
     * NL 21/9/0, PL 23/8/5/0 — met een label in de taal van de markt.
     */
    public static function availableRates(): array
    {
        $default = \App\Support\Market::defaultVatRate();

        return array_map(fn (int $rate) => [
            'value' => (float) $rate,
            'label' => match (true) {
                $rate === 0 => __('0% / vrijgesteld'),
                $rate === $default => __(':rate% (hoog tarief)', ['rate' => $rate]),
                default => __(':rate% (laag tarief)', ['rate' => $rate]),
            },
        ], \App\Support\Market::vatRates());
    }
}
