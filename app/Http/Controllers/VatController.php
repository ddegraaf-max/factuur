<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * BTW-overzicht per kwartaal — precies de cijfers die de ondernemer nodig
 * heeft voor de kwartaalaangifte omzetbelasting:
 *
 *   rubriek 1a — leveringen/diensten belast met hoog tarief (21%)
 *   rubriek 1b — leveringen/diensten belast met laag tarief (9%)
 *   rubriek 1e — leveringen/diensten belast met 0%
 *   rubriek 5b — voorbelasting (BTW op ingeboekte inkoopfacturen)
 *
 * Grondslag en BTW komen uit de factuurregels (factuurstelsel: geteld in het
 * kwartaal van de factuurdatum). Creditnota's tellen negatief mee. Het saldo
 * (af te dragen minus voorbelasting) is wat er per kwartaal betaald wordt.
 */
class VatController extends Controller
{
    public function index(Request $request): Response
    {
        $year = (int) $request->input('year', now()->year);

        $allYears = Invoice::regular()
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->selectRaw('DISTINCT EXTRACT(YEAR FROM invoice_date) AS yr')
            ->pluck('yr')
            ->map(fn ($y) => (int) $y)
            ->sortDesc()
            ->values()
            ->all();

        if (empty($allYears)) $allYears = [now()->year];

        return Inertia::render('Btw/Index', array_merge($this->overview($year), [
            'year' => $year,
            'allYears' => $allYears,
        ]));
    }

    /** Hetzelfde overzicht, maar als PDF voor de eigen administratie/boekhouder. */
    public function pdf(Request $request): HttpResponse
    {
        $year = (int) $request->input('year', now()->year);
        $company = auth()->user()->company;

        $pdf = Pdf::loadView('pdf.btw-overzicht', array_merge($this->overview($year), [
            'year' => $year,
            'company' => $company,
            'generated_at' => now()->translatedFormat('j F Y, H:i'),
        ]))->setPaper('a4');

        return $pdf->download("btw-overzicht-{$year}.pdf");
    }

    /* ===================== Berekening ===================== */

    protected function overview(int $year): array
    {
        $invoices = Invoice::with('lines')
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereYear('invoice_date', $year)
            ->get();

        $emptyRates = fn () => [
            '21' => ['base' => 0.0, 'vat' => 0.0],
            '9' => ['base' => 0.0, 'vat' => 0.0],
            '0' => ['base' => 0.0, 'vat' => 0.0],
        ];

        $quarters = [];
        for ($q = 1; $q <= 4; $q++) {
            $quarters[$q] = [
                'rates' => $emptyRates(),
                'invoice_count' => 0,
                'credit_count' => 0,
            ];
        }

        foreach ($invoices as $invoice) {
            $q = (int) ceil($invoice->invoice_date->month / 3);
            $sign = $invoice->is_credit ? -1 : 1;

            if ($invoice->is_credit) $quarters[$q]['credit_count']++;
            else $quarters[$q]['invoice_count']++;

            foreach ($invoice->lines as $line) {
                $key = (string) (int) (float) $line->vat_rate;
                if (! isset($quarters[$q]['rates'][$key])) $key = '0';
                $quarters[$q]['rates'][$key]['base'] += $sign * (float) $line->line_subtotal;
                $quarters[$q]['rates'][$key]['vat'] += $sign * (float) $line->line_vat;
            }
        }

        // Voorbelasting (rubriek 5b): BTW op ingeboekte inkoopfacturen,
        // eveneens geteld in het kwartaal van de factuurdatum.
        $inputVat = [1 => 0.0, 2 => 0.0, 3 => 0.0, 4 => 0.0];
        $purchaseCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
        PurchaseInvoice::whereYear('invoice_date', $year)
            ->get(['invoice_date', 'vat_total'])
            ->each(function ($p) use (&$inputVat, &$purchaseCounts) {
                $q = (int) ceil($p->invoice_date->month / 3);
                $inputVat[$q] += (float) $p->vat_total;
                $purchaseCounts[$q]++;
            });

        $now = now();
        $monthsLabels = [1 => 'jan – mrt', 2 => 'apr – jun', 3 => 'jul – sep', 4 => 'okt – dec'];

        $result = [];
        $yearTotals = [
            'rates' => $emptyRates(), 'base' => 0.0, 'vat' => 0.0,
            'input_vat' => 0.0, 'balance' => 0.0,
            'invoice_count' => 0, 'credit_count' => 0, 'purchase_count' => 0,
        ];

        foreach ($quarters as $q => $data) {
            $start = Carbon::create($year, ($q - 1) * 3 + 1, 1)->startOfDay();
            $end = $start->copy()->addMonths(3)->subDay()->endOfDay();
            // Aangifte + betaling moeten binnen zijn vóór het einde van de
            // maand ná het kwartaal (Q1 → 30 april, Q4 → 31 januari).
            $deadline = $end->copy()->addDay()->endOfMonth();

            $status = $now->lt($start) ? 'future' : ($now->lte($end) ? 'current' : 'closed');

            $base = 0.0;
            $vat = 0.0;
            $rates = [];
            foreach ($data['rates'] as $rate => $amounts) {
                $rates[$rate] = [
                    'base' => round($amounts['base'], 2),
                    'vat' => round($amounts['vat'], 2),
                ];
                $base += $amounts['base'];
                $vat += $amounts['vat'];

                $yearTotals['rates'][$rate]['base'] += $amounts['base'];
                $yearTotals['rates'][$rate]['vat'] += $amounts['vat'];
            }
            $yearTotals['base'] += $base;
            $yearTotals['vat'] += $vat;
            $yearTotals['input_vat'] += $inputVat[$q];
            $yearTotals['invoice_count'] += $data['invoice_count'];
            $yearTotals['credit_count'] += $data['credit_count'];
            $yearTotals['purchase_count'] += $purchaseCounts[$q];

            $result[] = [
                'quarter' => $q,
                'label' => "{$q}e kwartaal",
                'months' => $monthsLabels[$q],
                'status' => $status,
                // Kwartaal voorbij maar de aangiftetermijn loopt nog → actie nodig.
                'declaration_due' => $status === 'closed' && $now->lte($deadline),
                'deadline_label' => $deadline->translatedFormat('j F Y'),
                'rates' => $rates,
                'base' => round($base, 2),
                'vat' => round($vat, 2),
                'input_vat' => round($inputVat[$q], 2),
                'balance' => round($vat - $inputVat[$q], 2),
                'invoice_count' => $data['invoice_count'],
                'credit_count' => $data['credit_count'],
                'purchase_count' => $purchaseCounts[$q],
            ];
        }

        foreach ($yearTotals['rates'] as $rate => $amounts) {
            $yearTotals['rates'][$rate] = [
                'base' => round($amounts['base'], 2),
                'vat' => round($amounts['vat'], 2),
            ];
        }
        $yearTotals['base'] = round($yearTotals['base'], 2);
        $yearTotals['vat'] = round($yearTotals['vat'], 2);
        $yearTotals['input_vat'] = round($yearTotals['input_vat'], 2);
        $yearTotals['balance'] = round($yearTotals['vat'] - $yearTotals['input_vat'], 2);

        return [
            'quarters' => $result,
            'totals' => $yearTotals,
        ];
    }
}
