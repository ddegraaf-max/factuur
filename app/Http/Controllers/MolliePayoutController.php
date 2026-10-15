<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\OnlinePayment;
use App\Services\MollieSettlementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Rapport Mollie-betalingen: welke facturen zijn online (iDEAL en dergelijke)
 * betaald, en in welke uitbetaling van Mollie zit dat geld. Mollie maakt
 * gebundeld over, minus kosten; de boekhouder ziet één bedrag op het
 * bankafschrift en koppelt dat met dit overzicht aan de facturen.
 *
 * Per maand (betaaldatum) als pagina, pdf en csv; per uitbetaling als pdf.
 * De uitbetalingsgegevens komen van App\Services\MollieSettlementService
 * (dagelijks via mollie:settlements, of met de knop "Ophalen bij Mollie").
 */
class MolliePayoutController extends Controller
{
    public function index(Request $request): Response
    {
        $company = $request->user()->company;
        $month = $this->month($request);

        $checked = OnlinePayment::where('company_id', $company->id)->max('settlement_checked_at');

        return Inertia::render('Reports/Mollie', $this->report($company, $month) + [
            'month' => $month->format('Y-m'),
            'month_label' => $month->translatedFormat('F Y'),
            'months' => $this->availableMonths($company, $month),
            'enabled' => filled($company->mollie_api_key),
            'last_checked' => $checked ? Carbon::parse($checked)->translatedFormat('j M Y, H:i') : null,
        ]);
    }

    /** Vraag Mollie nu om de uitbetalingen (anders gebeurt dat elke nacht). */
    public function refresh(Request $request, MollieSettlementService $service): RedirectResponse
    {
        $company = $request->user()->company;
        if (! filled($company->mollie_api_key)) {
            return back()->withErrors(['mollie' => __('Er is geen Mollie-koppeling. Vul je API-key in bij Instellingen → Bedrijfsgegevens.')]);
        }

        $result = $service->sync($company, force: true);
        if ($result['error']) {
            return back()->withErrors(['mollie' => $result['error']]);
        }

        return back()->with('flash', $result['settled'] > 0
            ? __(':n betaling(en) aan een uitbetaling gekoppeld.', ['n' => $result['settled']])
            : __('Bijgewerkt: geen nieuwe uitbetalingen bij Mollie.'));
    }

    /** Het maandoverzicht als pdf, om naar de boekhouder te sturen. */
    public function pdf(Request $request): HttpResponse
    {
        $company = $request->user()->company;
        $month = $this->month($request);

        $pdf = Pdf::loadView('pdf.mollie-betalingen', $this->report($company, $month) + [
            'company' => $company,
            'title' => __('Mollie-betalingen :month', ['month' => $month->translatedFormat('F Y')]),
            'subtitle' => __('Online betaalde facturen, per uitbetaling van Mollie'),
            'generated_at' => now()->translatedFormat('j F Y, H:i'),
        ])->setPaper('a4');

        return $pdf->download('mollie-betalingen-' . $month->format('Y-m') . '.pdf');
    }

    /** Eén uitbetaling als pdf: precies wat er achter dat ene bedrag op het bankafschrift zit. */
    public function payoutPdf(Request $request, string $settlement): HttpResponse
    {
        $company = $request->user()->company;
        $payments = $this->query($company)->where('settlement_id', $settlement)->get();
        abort_if($payments->isEmpty(), 404);

        $report = $this->group($payments);
        $first = $report['groups'][0];
        $label = $first['reference'] ?: $settlement;

        $pdf = Pdf::loadView('pdf.mollie-betalingen', $report + [
            'company' => $company,
            'title' => __('Uitbetaling Mollie :reference', ['reference' => $label]),
            'subtitle' => $first['settled_label']
                ? __('Overgemaakt op :date', ['date' => $first['settled_label']])
                : __('Nog niet overgemaakt door Mollie'),
            'generated_at' => now()->translatedFormat('j F Y, H:i'),
        ])->setPaper('a4');

        return $pdf->download('mollie-uitbetaling-' . preg_replace('/[^A-Za-z0-9.-]+/', '-', $label) . '.pdf');
    }

    /** Het maandoverzicht als csv, voor wie het in een spreadsheet of boekhoudpakket wil inlezen. */
    public function csv(Request $request): HttpResponse
    {
        $company = $request->user()->company;
        $month = $this->month($request);
        $report = $this->report($company, $month);

        $out = fopen('php://temp', 'r+');
        fputcsv($out, [__('Betaaldatum'), __('Factuur'), __('Klant'), __('Methode'), __('Bedrag'), __('Mollie-betaling'), __('Uitbetaling'), __('Uitbetaald op'), __('Totaal uitbetaling')], ';');
        foreach ($report['groups'] as $group) {
            foreach ($group['rows'] as $row) {
                fputcsv($out, [
                    $row['paid_on'], $row['number'], $row['customer'], $row['method'],
                    number_format($row['amount'], 2, ',', ''), $row['mollie_id'],
                    $group['reference'] ?: ($group['settlement_id'] ?: ''), $group['settled_on'] ?: '',
                    $group['total'] !== null ? number_format($group['total'], 2, ',', '') : '',
                ], ';');
            }
        }
        rewind($out);
        $csv = "\xEF\xBB\xBF" . stream_get_contents($out);
        fclose($out);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="mollie-betalingen-' . $month->format('Y-m') . '.csv"',
        ]);
    }

    /* ===================== Opbouw ===================== */

    protected function month(Request $request): Carbon
    {
        $value = (string) $request->input('month', now()->format('Y-m'));

        return preg_match('/^\d{4}-\d{2}$/', $value)
            ? Carbon::createFromFormat('Y-m-d', $value . '-01')->startOfMonth()
            : now()->startOfMonth();
    }

    protected function query(Company $company)
    {
        return OnlinePayment::where('company_id', $company->id)
            ->where('status', 'paid')
            ->with(['invoice' => fn ($q) => $q->select('id', 'number', 'customer_name', 'total')])
            ->orderBy('paid_at');
    }

    /** @return array{groups: list<array<string, mixed>>, totals: array<string, mixed>} */
    protected function report(Company $company, Carbon $month): array
    {
        $payments = $this->query($company)
            ->whereBetween('paid_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get();

        return $this->group($payments);
    }

    /**
     * Betalingen gegroepeerd per uitbetaling; betalingen zonder bekende
     * uitbetaling komen als laatste groep ("nog niet uitbetaald").
     *
     * @param  Collection<int, OnlinePayment>  $payments
     * @return array{groups: list<array<string, mixed>>, totals: array<string, mixed>}
     */
    protected function group(Collection $payments): array
    {
        $groups = [];
        foreach ($payments as $payment) {
            $key = $payment->settlement_id ?: '_open';
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'settlement_id' => $payment->settlement_id,
                    'reference' => $payment->settlement_reference,
                    'status' => $payment->settlement_status,
                    'settled_on' => $payment->settled_on?->toDateString(),
                    'settled_label' => $payment->settled_on?->translatedFormat('j F Y'),
                    'total' => $payment->settlement_total !== null ? (float) $payment->settlement_total : null,
                    'gross' => 0.0,
                    'count' => 0,
                    'rows' => [],
                ];
            }
            $groups[$key]['rows'][] = [
                'id' => $payment->id,
                'paid_on' => $payment->paid_at?->toDateString(),
                'paid_label' => $payment->paid_at?->translatedFormat('j M Y'),
                'invoice_id' => $payment->invoice_id,
                'number' => $payment->invoice?->number ?: __('(concept)'),
                'customer' => $payment->invoice?->customer_name ?? '',
                'method' => $this->methodLabel($payment->method),
                'amount' => round((float) $payment->amount, 2),
                'mollie_id' => $payment->mollie_id,
            ];
            $groups[$key]['gross'] = round($groups[$key]['gross'] + (float) $payment->amount, 2);
            $groups[$key]['count']++;
        }

        // Verschil tussen wat klanten betaalden en wat Mollie overmaakte:
        // negatief zijn de kosten van Mollie, positief zitten er betalingen
        // in de uitbetaling die niet via deze administratie liepen.
        foreach ($groups as &$group) {
            $group['difference'] = $group['total'] !== null ? round($group['total'] - $group['gross'], 2) : null;
        }
        unset($group);

        $sorted = collect($groups)
            ->sortBy(fn ($g) => $g['settlement_id'] === null ? '9999-99-99' : ($g['settled_on'] ?? '9999-99-98'))
            ->values()
            ->all();

        $received = round($payments->sum(fn ($p) => (float) $p->amount), 2);
        $unsettled = round((float) ($groups['_open']['gross'] ?? 0), 2);

        return [
            'groups' => $sorted,
            'totals' => [
                'received' => $received,
                'count' => $payments->count(),
                'settled' => round($received - $unsettled, 2),
                'unsettled' => $unsettled,
                'payouts' => count(array_filter($groups, fn ($g) => $g['settlement_id'] !== null)),
            ],
        ];
    }

    protected function methodLabel(?string $method): string
    {
        return match ($method) {
            'ideal' => 'iDEAL',
            'creditcard' => __('Creditcard'),
            'blik' => 'BLIK',
            'przelewy24' => 'Przelewy24',
            'banktransfer' => __('Overboeking'),
            'directdebit' => __('Incasso'),
            'paypal' => 'PayPal',
            'applepay' => 'Apple Pay',
            null, '' => __('Online'),
            default => ucfirst($method),
        };
    }

    /** Maanden met online betalingen (nieuwste eerst), plus de gekozen en de huidige maand. */
    protected function availableMonths(Company $company, Carbon $selected): array
    {
        $months = OnlinePayment::where('company_id', $company->id)
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->pluck('paid_at')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m'))
            ->push(now()->format('Y-m'))
            ->push($selected->format('Y-m'))
            ->unique()
            ->sortDesc()
            ->values();

        return $months->map(fn ($m) => [
            'value' => $m,
            'label' => Carbon::createFromFormat('Y-m-d', $m . '-01')->translatedFormat('F Y'),
        ])->all();
    }
}
