<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;

/**
 * De pdf van de gratis factuur (zonder account): één generator voor het
 * formulier op /gratis-factuur-maken en voor de voorbeeldfacturen op
 * /voorbeelden. Totalen worden altijd hier berekend, nooit uit de invoer
 * overgenomen; bij verlegd of vrijgesteld telt geen enkele regel btw.
 */
class FreeInvoicePdf
{
    /**
     * @param  array<string, mixed>  $data  velden van het gratis-factuurformulier (van_*, aan_*, factuurnummer, btw_type, regels, ...)
     */
    public static function make(array $data): \Barryvdh\DomPDF\PDF
    {
        $subtotal = 0.0;
        $vatByRate = [];
        $rows = [];

        foreach ($data['regels'] as $line) {
            $rate = $data['btw_type'] === 'normaal' ? (int) $line['btw'] : 0;
            $amount = round((float) $line['aantal'] * (float) $line['prijs'], 2);
            $subtotal += $amount;
            if ($data['btw_type'] === 'normaal') {
                $vatByRate[$rate] = ($vatByRate[$rate] ?? 0.0) + $amount;
            }

            $rows[] = [
                'omschrijving' => $line['omschrijving'],
                'aantal' => (float) $line['aantal'],
                'prijs' => (float) $line['prijs'],
                'btw' => $rate,
                'bedrag' => $amount,
            ];
        }

        $vatTotals = [];
        foreach ($vatByRate as $rate => $base) {
            if ($rate > 0) {
                $vatTotals[$rate] = round($base * $rate / 100, 2);
            }
        }

        return Pdf::loadView('pdf.gratis-factuur', [
            'data' => $data,
            'rows' => $rows,
            'subtotal' => round($subtotal, 2),
            'vatTotals' => $vatTotals,
            'total' => round($subtotal + array_sum($vatTotals), 2),
        ]);
    }
}
