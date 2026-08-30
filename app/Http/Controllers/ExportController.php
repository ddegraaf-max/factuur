<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Support\Brand;
use App\Support\Market;
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

    /** Auditfile Financieel (XAF 3.2) van één boekjaar — voor accountant en Belastingdienst. */
    public function xaf(Request $request, \App\Services\XafExporter $exporter)
    {
        $data = $request->validate(['year' => ['required', 'integer', 'between:2000,2100']]);
        $company = auth()->user()->company;
        $xml = $exporter->generate($company, (int) $data['year']);

        \App\Support\Audit::log('exported', null, __('Auditfile XAF :year gedownload', ['year' => $data['year']]));

        $slug = preg_replace('/[^A-Za-z0-9]+/', '-', trim($company->name)) ?: 'administratie';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"auditfile-{$slug}-{$data['year']}.xaf\"",
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
            ->withSum(['payments as real_paid' => fn ($q) => $q->where('kind', 'payment')], 'amount')
            ->withSum(['payments as advance_paid' => fn ($q) => $q->where('kind', 'advance')], 'amount')
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereBetween('invoice_date', [$from, $to])
            ->when(! $includeCredit, fn ($q) => $q->where('is_credit', false))
            ->when($data['status'] === 'open', fn ($q) => $q->whereIn('status', ['sent', 'partial', 'overdue', 'incasso']))
            ->when($data['status'] === 'paid', fn ($q) => $q->where('status', 'paid'))
            ->orderBy('invoice_date')
            ->orderBy('number')
            ->get();

        $filename = sprintf('%s-export-%s-tm-%s.csv', Brand::key(), $from->format('Y-m-d'), $to->format('Y-m-d'));

        // Kolommen per btw-tarief van de markt (nl: 21/9/0, pl: 23/8/5/0); bij 0% alleen de grondslag.
        $rates = Market::vatRates();
        if (! in_array(0, $rates, true)) {
            $rates[] = 0;
        }
        $dateFormat = (string) Market::get('date_format', 'd-m-Y');

        return response()->streamDownload(function () use ($invoices, $rates, $dateFormat) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM zodat Excel diakrieten goed leest.
            fwrite($out, "\xEF\xBB\xBF");

            $statusLabels = [
                'sent' => __('Verstuurd'), 'partial' => __('Deels betaald'), 'overdue' => __('Vervallen'),
                'incasso' => __('Incasso'), 'paid' => __('Betaald'),
            ];

            $rateColumns = [];
            foreach ($rates as $rate) {
                $rateColumns[] = __('Grondslag :rate%', ['rate' => $rate]);
                if ($rate > 0) {
                    $rateColumns[] = __('BTW :rate%', ['rate' => $rate]);
                }
            }
            $emptyBuckets = array_fill_keys(array_map('strval', $rates), ['base' => 0.0, 'vat' => 0.0]);
            $rateCells = function (array $buckets) use ($rates): array {
                $cells = [];
                foreach ($rates as $rate) {
                    $cells[] = $buckets[(string) $rate]['base'];
                    if ($rate > 0) {
                        $cells[] = $buckets[(string) $rate]['vat'];
                    }
                }

                return $cells;
            };

            fputcsv($out, [
                __('Factuurnummer'), __('Type'), __('Status'), __('Factuurdatum'), __('Vervaldatum'), __('Klant'),
                __('KVK klant'), __('BTW-nummer klant'), __('Referentie'),
                __('Bedrag excl. BTW'), ...$rateColumns,
                __('BTW totaal'), __('Bedrag incl. BTW'), __('Betaald'), __('Doorgestort/verrekend'), __('Afgeboekt'), __('Openstaand'), __('Betaald op'),
            ], ';');

            $money = fn ($v) => number_format((float) $v, 2, ',', '');
            $sum = [
                'subtotal' => 0.0, 'vat_total' => 0.0, 'total' => 0.0,
                'paid' => 0.0, 'advance' => 0.0, 'written_off' => 0.0, 'open' => 0.0,
            ];
            $sumBuckets = $emptyBuckets;

            foreach ($invoices as $invoice) {
                // Grondslag en BTW per tarief uit de factuurregels.
                $buckets = $emptyBuckets;
                foreach ($invoice->lines as $line) {
                    $key = (string) (int) (float) $line->vat_rate;
                    if (! isset($buckets[$key])) $key = '0';
                    $buckets[$key]['base'] += (float) $line->line_subtotal;
                    $buckets[$key]['vat'] += (float) $line->line_vat;
                    $sumBuckets[$key]['base'] += (float) $line->line_subtotal;
                    $sumBuckets[$key]['vat'] += (float) $line->line_vat;
                }

                $open = (float) $invoice->total - (float) $invoice->paid_total;
                // 'Betaald' = echt ontvangen geld; doorstortingen/verrekeningen
                // en afboekingen staan apart zodat de boekhouder ze ziet.
                $realPaid = (float) ($invoice->real_paid ?? 0);
                $advancePaid = (float) ($invoice->advance_paid ?? 0);
                $writtenOff = round((float) $invoice->paid_total - $realPaid - $advancePaid, 2);

                fputcsv($out, [
                    $invoice->number,
                    $invoice->is_credit ? __('Creditnota') : __('Factuur'),
                    $statusLabels[$invoice->status] ?? $invoice->status,
                    $invoice->invoice_date->format($dateFormat),
                    $invoice->due_date?->format($dateFormat) ?? '',
                    $invoice->customer_name,
                    $invoice->customer_kvk_number ?? '',
                    $invoice->customer_vat_number ?? '',
                    $invoice->reference ?? '',
                    $money($invoice->subtotal),
                    ...array_map($money, $rateCells($buckets)),
                    $money($invoice->vat_total),
                    $money($invoice->total),
                    $money($realPaid),
                    $money($advancePaid),
                    $money($writtenOff),
                    $money($open),
                    $invoice->paid_at?->format($dateFormat) ?? '',
                ], ';');

                $sum['subtotal'] += (float) $invoice->subtotal;
                $sum['vat_total'] += (float) $invoice->vat_total;
                $sum['total'] += (float) $invoice->total;
                $sum['paid'] += $realPaid;
                $sum['advance'] += $advancePaid;
                $sum['written_off'] += $writtenOff;
                $sum['open'] += $open;
            }

            // Controletotaal voor de boekhouder.
            fputcsv($out, [
                __('TOTAAL'), '', '', '', '', '', '', '', '',
                $money($sum['subtotal']),
                ...array_map($money, $rateCells($sumBuckets)),
                $money($sum['vat_total']), $money($sum['total']),
                $money($sum['paid']), $money($sum['advance']), $money($sum['written_off']), $money($sum['open']), '',
            ], ';');

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
