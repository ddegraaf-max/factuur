<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export naar boekhouder: alle definitieve facturen als CSV-bestand
 * (puntkomma-gescheiden, decimale komma — opent direct goed in NL-Excel).
 */
class ExportController extends Controller
{
    public function index(): Response
    {
        $company = auth()->user()->company;

        return Inertia::render('Export/Index', [
            'defaults' => [
                'from' => now()->startOfYear()->format('Y-m-d'),
                'to' => now()->format('Y-m-d'),
            ],
            'accountant_email' => $company->accountant_email,
        ]);
    }

    public function download(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'status' => ['required', 'in:all,open,paid'],
            'include_credit' => ['nullable', 'boolean'],
        ]);

        $from = Carbon::parse($data['from'])->startOfDay();
        $to = Carbon::parse($data['to'])->endOfDay();
        $includeCredit = (bool) ($data['include_credit'] ?? true);

        $invoices = Invoice::with('lines')
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereBetween('invoice_date', [$from, $to])
            ->when(! $includeCredit, fn ($q) => $q->where('is_credit', false))
            ->when($data['status'] === 'open', fn ($q) => $q->whereIn('status', ['sent', 'partial', 'overdue', 'incasso']))
            ->when($data['status'] === 'paid', fn ($q) => $q->where('status', 'paid'))
            ->orderBy('invoice_date')
            ->orderBy('number')
            ->get();

        $filename = sprintf('easyinvoice-export-%s-tm-%s.csv', $from->format('Y-m-d'), $to->format('Y-m-d'));

        return response()->streamDownload(function () use ($invoices) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM zodat Excel diakrieten goed leest.
            fwrite($out, "\xEF\xBB\xBF");

            $statusLabels = [
                'sent' => 'Verstuurd', 'partial' => 'Deels betaald', 'overdue' => 'Vervallen',
                'incasso' => 'Incasso', 'paid' => 'Betaald',
            ];

            fputcsv($out, [
                'Factuurnummer', 'Type', 'Status', 'Factuurdatum', 'Vervaldatum', 'Klant',
                'KVK klant', 'BTW-nummer klant', 'Referentie',
                'Bedrag excl. BTW', 'Grondslag 21%', 'BTW 21%', 'Grondslag 9%', 'BTW 9%', 'Grondslag 0%',
                'BTW totaal', 'Bedrag incl. BTW', 'Betaald', 'Openstaand', 'Betaald op',
            ], ';');

            $money = fn ($v) => number_format((float) $v, 2, ',', '');
            $sum = [
                'subtotal' => 0.0, 'base21' => 0.0, 'vat21' => 0.0, 'base9' => 0.0,
                'vat9' => 0.0, 'base0' => 0.0, 'vat_total' => 0.0, 'total' => 0.0,
                'paid' => 0.0, 'open' => 0.0,
            ];

            foreach ($invoices as $invoice) {
                // Grondslag en BTW per tarief uit de factuurregels.
                $buckets = ['21' => ['base' => 0.0, 'vat' => 0.0], '9' => ['base' => 0.0, 'vat' => 0.0], '0' => ['base' => 0.0, 'vat' => 0.0]];
                foreach ($invoice->lines as $line) {
                    $key = (string) (int) (float) $line->vat_rate;
                    if (! isset($buckets[$key])) $key = '0';
                    $buckets[$key]['base'] += (float) $line->line_subtotal;
                    $buckets[$key]['vat'] += (float) $line->line_vat;
                }

                $open = (float) $invoice->total - (float) $invoice->paid_total;

                fputcsv($out, [
                    $invoice->number,
                    $invoice->is_credit ? 'Creditnota' : 'Factuur',
                    $statusLabels[$invoice->status] ?? $invoice->status,
                    $invoice->invoice_date->format('d-m-Y'),
                    $invoice->due_date?->format('d-m-Y') ?? '',
                    $invoice->customer_name,
                    $invoice->customer_kvk_number ?? '',
                    $invoice->customer_vat_number ?? '',
                    $invoice->reference ?? '',
                    $money($invoice->subtotal),
                    $money($buckets['21']['base']),
                    $money($buckets['21']['vat']),
                    $money($buckets['9']['base']),
                    $money($buckets['9']['vat']),
                    $money($buckets['0']['base']),
                    $money($invoice->vat_total),
                    $money($invoice->total),
                    $money($invoice->paid_total),
                    $money($open),
                    $invoice->paid_at?->format('d-m-Y') ?? '',
                ], ';');

                $sum['subtotal'] += (float) $invoice->subtotal;
                $sum['base21'] += $buckets['21']['base'];
                $sum['vat21'] += $buckets['21']['vat'];
                $sum['base9'] += $buckets['9']['base'];
                $sum['vat9'] += $buckets['9']['vat'];
                $sum['base0'] += $buckets['0']['base'];
                $sum['vat_total'] += (float) $invoice->vat_total;
                $sum['total'] += (float) $invoice->total;
                $sum['paid'] += (float) $invoice->paid_total;
                $sum['open'] += $open;
            }

            // Controletotaal voor de boekhouder.
            fputcsv($out, [
                'TOTAAL', '', '', '', '', '', '', '', '',
                $money($sum['subtotal']),
                $money($sum['base21']), $money($sum['vat21']),
                $money($sum['base9']), $money($sum['vat9']),
                $money($sum['base0']),
                $money($sum['vat_total']), $money($sum['total']),
                $money($sum['paid']), $money($sum['open']), '',
            ], ';');

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
