<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Gratis factuur-generator op /gratis-factuur-maken: iedereen maakt zonder
 * account een correcte factuur-PDF. Bedoeld als kennismaking met EasyInvoice —
 * onderaan de PDF staat een bescheiden verwijzing naar easyinvoice.nl.
 *
 * Er wordt niets opgeslagen: de ingevulde gegevens worden alleen gebruikt om
 * de PDF te renderen en daarna vergeten.
 */
class FreeInvoiceController extends Controller
{
    public function show()
    {
        return view('marketing.gratis-factuur');
    }

    public function download(Request $request)
    {
        // Nederlandse invoer gebruikt een decimale komma; normaliseer vóór
        // validatie zodat "12,50" gewoon werkt.
        $lines = collect($request->input('regels', []))
            ->map(function ($line) {
                foreach (['aantal', 'prijs'] as $key) {
                    if (isset($line[$key]) && is_string($line[$key]) && str_contains($line[$key], ',')) {
                        // "1.250,50" → "1250.50"; invoer met alleen een punt
                        // ("12.5") blijft ongemoeid.
                        $line[$key] = str_replace(',', '.', str_replace('.', '', $line[$key]));
                    }
                }

                return $line;
            })
            ->values()
            ->all();

        $request->merge(['regels' => $lines]);

        $data = $request->validate([
            'van_bedrijf' => ['required', 'string', 'max:120'],
            'van_adres' => ['nullable', 'string', 'max:300'],
            'van_kvk' => ['nullable', 'string', 'max:20'],
            'van_btw' => ['nullable', 'string', 'max:25'],
            'van_iban' => ['nullable', 'string', 'max:40'],
            'van_email' => ['nullable', 'string', 'max:120'],
            'aan_bedrijf' => ['required', 'string', 'max:120'],
            'aan_adres' => ['nullable', 'string', 'max:300'],
            'factuurnummer' => ['required', 'string', 'max:40'],
            'factuurdatum' => ['required', 'date'],
            'vervaldatum' => ['nullable', 'date'],
            'btw_type' => ['required', 'in:normaal,verlegd,vrijgesteld'],
            'opmerking' => ['nullable', 'string', 'max:500'],
            'regels' => ['required', 'array', 'min:1', 'max:20'],
            'regels.*.omschrijving' => ['required', 'string', 'max:200'],
            'regels.*.aantal' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'regels.*.prijs' => ['required', 'numeric', 'gte:-1000000', 'max:1000000'],
            'regels.*.btw' => ['required', 'in:21,9,0'],
        ], [], [
            'van_bedrijf' => 'jouw bedrijfsnaam',
            'aan_bedrijf' => 'naam van de klant',
            'factuurnummer' => 'factuurnummer',
            'factuurdatum' => 'factuurdatum',
            'regels.*.omschrijving' => 'omschrijving',
            'regels.*.aantal' => 'aantal',
            'regels.*.prijs' => 'prijs',
        ]);

        // Totalen altijd server-side berekenen; bij verlegd/vrijgesteld telt
        // geen enkele regel btw.
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

        $pdf = Pdf::loadView('pdf.gratis-factuur', [
            'data' => $data,
            'rows' => $rows,
            'subtotal' => round($subtotal, 2),
            'vatTotals' => $vatTotals,
            'total' => round($subtotal + array_sum($vatTotals), 2),
        ]);

        $filename = 'factuur-'.Str::slug($data['factuurnummer'], '-');

        return $pdf->download(($filename ?: 'factuur').'.pdf');
    }
}
