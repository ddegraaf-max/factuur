<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\QuoteInstallment;
use App\Services\InvoiceManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Termijnfacturen: een offerte in delen factureren (bijv. 30% bij opdracht,
 * 70% bij oplevering). Het plan bestaat uit percentages die samen 100%
 * vormen; elke termijn wordt op het juiste moment met één klik een gewone
 * conceptfactuur. De laatste termijn is altijd "de rest", zodat de som van
 * alle termijnfacturen tot op de cent gelijk is aan de offertesom.
 */
class QuoteInstallmentController extends Controller
{
    public function __construct(protected InvoiceManager $invoices) {}

    /** Termijnplan aanmaken (of vervangen zolang er niets is gefactureerd). */
    public function store(Request $request, Quote $quote): RedirectResponse
    {
        if (! in_array($quote->status, ['sent', 'accepted'], true)) {
            return back()->withErrors(['installments' => __('Alleen een verstuurde of geaccepteerde offerte kan in termijnen worden gefactureerd.')]);
        }
        if ($quote->converted_invoice_id) {
            return back()->withErrors(['installments' => __('Deze offerte is al volledig omgezet naar een factuur.')]);
        }
        if ($quote->installments()->whereNotNull('invoice_id')->exists()) {
            return back()->withErrors(['installments' => __('Er is al een termijn gefactureerd — het plan ligt vast.')]);
        }

        $data = $request->validate([
            'installments' => ['required', 'array', 'min:2', 'max:12'],
            'installments.*.description' => ['required', 'string', 'max:200'],
            'installments.*.percentage' => ['required', 'numeric', 'min:0.01', 'max:100'],
        ], [
            'installments.min' => __('Een termijnplan bestaat uit minstens twee termijnen.'),
        ]);

        $sum = round(collect($data['installments'])->sum(fn ($i) => (float) $i['percentage']), 2);
        if (abs($sum - 100) > 0.01) {
            return back()->withErrors(['installments' => __('De percentages moeten samen 100% zijn (nu :sum%).', ['sum' => $sum])]);
        }

        DB::transaction(function () use ($quote, $data) {
            $quote->installments()->delete();

            // Bedragen (incl. btw) als momentopname; de laatste termijn krijgt
            // de rest zodat de som exact op de offertesom uitkomt.
            $items = array_values($data['installments']);
            $assigned = 0.0;
            foreach ($items as $index => $item) {
                $isLast = $index === count($items) - 1;
                $amount = $isLast
                    ? round((float) $quote->total - $assigned, 2)
                    : round((float) $quote->total * ((float) $item['percentage']) / 100, 2);
                $assigned = round($assigned + $amount, 2);

                $quote->installments()->create([
                    'sort_order' => $index,
                    'description' => trim($item['description']),
                    'percentage' => round((float) $item['percentage'], 2),
                    'amount' => $amount,
                ]);
            }
        });

        return back()->with('flash', __('Termijnplan opgeslagen.'));
    }

    /** Plan verwijderen — kan alleen zolang er geen termijn is gefactureerd. */
    public function destroy(Quote $quote): RedirectResponse
    {
        if ($quote->installments()->whereNotNull('invoice_id')->exists()) {
            return back()->withErrors(['installments' => __('Er is al een termijn gefactureerd — het plan kan niet meer worden verwijderd.')]);
        }

        $quote->installments()->delete();

        return back()->with('flash', __('Termijnplan verwijderd.'));
    }

    /**
     * Maak de conceptfactuur voor deze termijn. Termijnen worden op volgorde
     * gefactureerd; de laatste is altijd het restant per BTW-tarief.
     */
    public function invoice(Quote $quote, QuoteInstallment $installment): RedirectResponse
    {
        abort_unless($installment->quote_id === $quote->id, 404);

        if ($installment->invoice_id) {
            return back()->withErrors(['installments' => __('Deze termijn is al gefactureerd.')]);
        }

        $plan = $quote->installments()->get();
        $next = $plan->firstWhere('invoice_id', null);
        if (! $next || $next->id !== $installment->id) {
            return back()->withErrors(['installments' => __('Factureer de termijnen op volgorde — de eerstvolgende open termijn eerst.')]);
        }

        $quote->loadMissing('lines');

        // Grondslag per BTW-tarief uit de offerteregels (excl. btw, exact).
        $bases = [];
        foreach ($quote->lines as $line) {
            $rate = (string) (float) $line->vat_rate;
            $bases[$rate] = round(($bases[$rate] ?? 0) + (float) $line->line_subtotal, 2);
        }

        // Deel per tarief voor déze termijn. Eerdere termijnen worden met
        // dezelfde afronding nagerekend; de laatste termijn krijgt de rest.
        $position = $plan->search(fn ($i) => $i->id === $installment->id);
        $isLast = $position === $plan->count() - 1;
        $shares = [];
        foreach ($bases as $rate => $base) {
            if ($isLast) {
                $previous = 0.0;
                foreach ($plan->slice(0, $position) as $earlier) {
                    $previous = round($previous + round($base * ((float) $earlier->percentage) / 100, 2), 2);
                }
                $shares[$rate] = round($base - $previous, 2);
            } else {
                $shares[$rate] = round($base * ((float) $installment->percentage) / 100, 2);
            }
        }
        $shares = array_filter($shares, fn ($v) => abs($v) > 0.005);
        if ($shares === []) {
            return back()->withErrors(['installments' => __('Deze termijn heeft geen bedrag (meer) om te factureren.')]);
        }

        $mode = ($quote->company?->price_mode === 'incl') ? 'incl' : 'excl';
        $count = count($shares);
        $pctLabel = rtrim(rtrim(number_format((float) $installment->percentage, 2, ',', '.'), '0'), ',');
        $quoteLabel = $quote->number ? __('offerte :number', ['number' => $quote->number]) : __('offerte');

        $lines = [];
        foreach ($shares as $rate => $amount) {
            $lines[] = [
                'description' => $installment->description . ' — ' . $quoteLabel . ' (' . $pctLabel . '%)',
                'details' => $count > 1 ? __('Deel tegen :rate% btw', ['rate' => rtrim(rtrim(number_format((float) $rate, 2, ',', '.'), '0'), ',')]) : null,
                'quantity' => 1,
                'unit' => __('stuk'),
                'unit_price' => $mode === 'incl'
                    ? round($amount * (1 + (float) $rate / 100), 2)
                    : $amount,
                'vat_rate' => (float) $rate,
            ];
        }

        $invoice = DB::transaction(function () use ($quote, $installment, $lines) {
            $invoice = $this->invoices->create([
                'customer_id' => $quote->customer_id,
                'brand_profile_id' => $quote->brand_profile_id,
                'language' => $quote->language,
                'invoice_date' => now()->toDateString(),
                'reference' => $quote->reference ?: __('Offerte :number', ['number' => $quote->number]),
                'lines' => $lines,
            ]);

            $installment->update(['invoice_id' => $invoice->id]);

            // Een termijnplan betekent dat de klant akkoord is.
            if ($quote->status === 'sent') {
                $quote->update(['status' => 'accepted', 'accepted_at' => $quote->accepted_at ?? now()]);
            }

            return $invoice;
        });

        return redirect()->route('invoices.edit', $invoice)
            ->with('flash', __('Conceptfactuur voor deze termijn aangemaakt — controleer en verstuur.'));
    }
}
