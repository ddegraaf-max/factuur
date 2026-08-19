<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use App\Models\Trip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Jaaroverzicht: omzet, kosten en resultaat uit de facturatie — de cijfers
 * die EasyInvoice écht kent, als voorbereiding op de aangifte inkomsten-
 * belasting of voor de boekhouder.
 *
 * BEWUST GEEN complete fiscale winst-en-verliesrekening: afschrijvingen,
 * loonkosten, bijtelling privégebruik en ondernemersaftrekken (zelfstandigen-
 * aftrek, MKB-winstvrijstelling) vragen gegevens en keuzes die buiten een
 * facturatiepakket vallen. Het overzicht benoemt dat expliciet, zodat er
 * nooit een half beeld als "aangifteklaar" wordt gepresenteerd.
 *
 * Grondslagen: factuurstelsel (factuurdatum), bedragen exclusief BTW,
 * creditnota's tellen negatief, concepten en geannuleerde facturen niet mee.
 * Kilometeraftrek: alle geregistreerde zakelijke ritten × het tarief
 * (standaard € 0,23/km — de onbelaste vergoeding voor privévervoermiddelen).
 */
class YearReportController extends Controller
{
    public function index(Request $request): Response
    {
        $year = (int) $request->input('year', now()->year);

        return Inertia::render('Reports/Jaaroverzicht', array_merge($this->overview($year), [
            'year' => $year,
            'allYears' => $this->availableYears(),
        ]));
    }

    /** Hetzelfde overzicht als PDF — om mee te sturen naar de boekhouder. */
    public function pdf(Request $request): HttpResponse
    {
        $year = (int) $request->input('year', now()->year);

        $pdf = Pdf::loadView('pdf.jaaroverzicht', array_merge($this->overview($year), [
            'year' => $year,
            'company' => auth()->user()->company,
            'generated_at' => now()->translatedFormat('j F Y, H:i'),
        ]))->setPaper('a4');

        return $pdf->download("jaaroverzicht-{$year}.pdf");
    }

    /* ===================== Berekening ===================== */

    protected function overview(int $year): array
    {
        return [
            'totals' => $this->totalsFor($year),
            'previous' => $this->totalsFor($year - 1),
            'quarters' => $this->quartersFor($year),
            'categories' => $this->categoriesFor($year),
        ];
    }

    /** Omzet/kosten/kilometers/resultaat van één jaar (excl. BTW, gesigneerd). */
    protected function totalsFor(int $year): array
    {
        [$from, $to] = [\Carbon\Carbon::create($year, 1, 1), \Carbon\Carbon::create($year, 12, 31)->endOfDay()];

        // Omzet: verstuurde facturen minus creditnota's, op factuurdatum.
        $revenue = Invoice::whereNotIn('status', ['draft', 'cancelled'])
            ->whereBetween('invoice_date', [$from, $to])
            ->get(['subtotal', 'is_credit'])
            ->sum(fn ($i) => ($i->is_credit ? -1 : 1) * (float) $i->subtotal);

        $purchases = PurchaseInvoice::whereBetween('invoice_date', [$from, $to])->get(['subtotal']);
        $costs = (float) $purchases->sum('subtotal');

        $trips = Trip::whereBetween('trip_date', [$from, $to])->with('company:id,default_km_rate')->get();
        $km = round((float) $trips->sum(fn ($t) => (float) $t->kilometers), 1);
        $kmAmount = round($trips->sum(fn ($t) => $t->amount()), 2);

        return [
            'revenue' => round($revenue, 2),
            'costs' => round($costs, 2),
            'km' => $km,
            'km_amount' => $kmAmount,
            'result' => round($revenue - $costs - $kmAmount, 2),
            'invoice_count' => Invoice::whereNotIn('status', ['draft', 'cancelled'])->whereBetween('invoice_date', [$from, $to])->count(),
            'purchase_count' => $purchases->count(),
            'trip_count' => $trips->count(),
        ];
    }

    /** Omzet/kosten/resultaat per kwartaal. */
    protected function quartersFor(int $year): array
    {
        $quarters = [];
        foreach ([1, 2, 3, 4] as $q) {
            $from = \Carbon\Carbon::create($year, ($q - 1) * 3 + 1, 1);
            $to = $from->copy()->addMonths(3)->subDay()->endOfDay();

            $revenue = Invoice::whereNotIn('status', ['draft', 'cancelled'])
                ->whereBetween('invoice_date', [$from, $to])
                ->get(['subtotal', 'is_credit'])
                ->sum(fn ($i) => ($i->is_credit ? -1 : 1) * (float) $i->subtotal);

            $costs = (float) PurchaseInvoice::whereBetween('invoice_date', [$from, $to])->sum('subtotal');

            $kmAmount = round(Trip::whereBetween('trip_date', [$from, $to])
                ->with('company:id,default_km_rate')->get()
                ->sum(fn ($t) => $t->amount()), 2);

            $quarters[] = [
                'label' => "Q{$q}",
                'revenue' => round($revenue, 2),
                'costs' => round($costs, 2),
                'km_amount' => $kmAmount,
                'result' => round($revenue - $costs - $kmAmount, 2),
            ];
        }

        return $quarters;
    }

    /** Kosten per categorie (inkoopfacturen, excl. BTW), grootste eerst. */
    protected function categoriesFor(int $year): array
    {
        [$from, $to] = [\Carbon\Carbon::create($year, 1, 1), \Carbon\Carbon::create($year, 12, 31)->endOfDay()];

        return PurchaseInvoice::whereBetween('invoice_date', [$from, $to])
            ->get(['category', 'subtotal'])
            ->groupBy(fn ($p) => $p->category ?: 'Overig')
            ->map(fn ($group, $name) => [
                'name' => $name,
                'amount' => round((float) $group->sum('subtotal'), 2),
                'count' => $group->count(),
            ])
            ->sortByDesc('amount')
            ->values()
            ->all();
    }

    /** Jaren met activiteit (databaseonafhankelijk bepaald). */
    protected function availableYears(): array
    {
        $first = collect([
            Invoice::whereNotIn('status', ['draft', 'cancelled'])->min('invoice_date'),
            PurchaseInvoice::min('invoice_date'),
        ])->filter()->map(fn ($d) => \Carbon\Carbon::parse($d)->year)->min() ?? now()->year;

        return range(now()->year, min($first, now()->year));
    }
}
