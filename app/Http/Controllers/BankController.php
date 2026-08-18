<?php

namespace App\Http\Controllers;

use App\Models\BankTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Services\BankStatementParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Bank & transacties: bankafschriften importeren (CAMT.053 / MT940) en
 * transacties koppelen aan facturen (ontvangsten) of inkoopfacturen
 * (afschrijvingen). Bij het koppelen wordt automatisch een betaling geboekt.
 */
class BankController extends Controller
{
    public function index(Request $request): Response
    {
        $tab = $request->input('tab', 'open');

        $transactions = BankTransaction::query()
            ->when($tab === 'open', fn ($q) => $q->where('status', 'open'))
            ->when($tab === 'matched', fn ($q) => $q->where('status', 'matched')->with(['matchedInvoice:id,number', 'matchedPurchase:id,supplier_name']))
            ->when($tab === 'ignored', fn ($q) => $q->where('status', 'ignored'))
            ->orderByDesc('booking_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        // Open facturen en inkoopfacturen voor de koppel-suggesties en de keuzelijsten.
        $openInvoices = Invoice::open()
            ->where('is_credit', false)
            ->orderByDesc('invoice_date')
            ->get(['id', 'number', 'customer_name', 'total', 'paid_total']);

        $openPurchases = PurchaseInvoice::open()
            ->orderByDesc('invoice_date')
            ->get(['id', 'supplier_name', 'supplier_reference', 'total']);

        $transactions->getCollection()->transform(fn ($tx) => [
            'id' => $tx->id,
            'booking_date_label' => $tx->booking_date->translatedFormat('j M Y'),
            'amount' => (float) $tx->amount,
            'currency' => $tx->currency,
            'counterparty_name' => $tx->counterparty_name,
            'counterparty_iban' => $tx->counterparty_iban,
            'description' => $tx->description,
            'status' => $tx->status,
            'source' => $tx->source,
            'matched_invoice' => $tx->matchedInvoice?->only(['id', 'number']),
            'matched_purchase' => $tx->matchedPurchase?->only(['id', 'supplier_name']),
            'suggestion' => $tx->status === 'open' ? $this->suggestFor($tx, $openInvoices, $openPurchases) : null,
        ]);

        $counts = [
            'open' => BankTransaction::where('status', 'open')->count(),
            'matched' => BankTransaction::where('status', 'matched')->count(),
            'ignored' => BankTransaction::where('status', 'ignored')->count(),
        ];

        return Inertia::render('Bank/Index', [
            'transactions' => $transactions,
            'tab' => $tab,
            'counts' => $counts,
            'open_invoices' => $openInvoices->map(fn ($i) => [
                'id' => $i->id,
                'label' => trim("{$i->number} · {$i->customer_name} · € " . number_format($i->total - $i->paid_total, 2, ',', '.')),
            ]),
            'open_purchases' => $openPurchases->map(fn ($p) => [
                'id' => $p->id,
                'label' => trim("{$p->supplier_name}" . ($p->supplier_reference ? " · {$p->supplier_reference}" : '') . ' · € ' . number_format($p->total, 2, ',', '.')),
            ]),
        ]);
    }

    /** Bankafschrift uploaden (CAMT.053 of MT940). */
    public function upload(Request $request, BankStatementParser $parser): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ], [
            'file.required' => 'Kies een bankafschrift (CAMT.053 of MT940).',
            'file.max' => 'Het bestand mag maximaal 10 MB zijn.',
        ]);

        try {
            $rows = $parser->parse(file_get_contents($request->file('file')->getRealPath()));
        } catch (\DomainException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $companyId = auth()->user()->company_id;
        $added = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $hash = sha1(implode('|', [
                $row['booking_date'], number_format((float) $row['amount'], 2, '.', ''),
                $row['counterparty_iban'] ?? '', mb_substr($row['description'] ?? '', 0, 140),
            ]));

            $exists = BankTransaction::withoutGlobalScope('company')
                ->where('company_id', $companyId)
                ->where('import_hash', $hash)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            BankTransaction::create(array_merge($row, [
                'company_id' => $companyId,
                'import_hash' => $hash,
            ]));
            $added++;
        }

        $message = "{$added} transactie(s) geïmporteerd.";
        if ($skipped > 0) {
            $message .= " {$skipped} overgeslagen (stonden er al in).";
        }

        return back()->with('flash', $message);
    }

    /** Koppel een ontvangst aan een factuur en boek de betaling. */
    public function matchInvoice(Request $request, BankTransaction $transaction): RedirectResponse
    {
        $data = $request->validate(['invoice_id' => ['required', 'integer']]);

        abort_unless($transaction->status === 'open', 422);

        $invoice = Invoice::open()->where('is_credit', false)->findOrFail($data['invoice_id']);

        $remaining = (float) $invoice->remaining_amount;
        $amount = min(abs((float) $transaction->amount), $remaining);
        if ($amount < 0.01) {
            return back()->withErrors(['match' => 'Er staat niets meer open op deze factuur.']);
        }

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'kind' => 'payment',
            'amount' => $amount,
            'paid_on' => $transaction->booking_date,
            'method' => 'bank_transfer',
            'reference' => trim(($transaction->counterparty_name ? $transaction->counterparty_name . ' · ' : '') . 'bankimport'),
        ]);

        $transaction->update([
            'status' => 'matched',
            'matched_invoice_id' => $invoice->id,
            'payment_id' => $payment->id,
        ]);

        $note = abs((float) $transaction->amount) - $amount > 0.009
            ? ' Let op: de transactie was hoger dan het openstaande bedrag; er is ' . '€ ' . number_format($amount, 2, ',', '.') . ' geboekt.'
            : '';

        return back()->with('flash', "Gekoppeld aan factuur {$invoice->number} — betaling geboekt.{$note}");
    }

    /** Koppel een afschrijving aan een inkoopfactuur en zet die op betaald. */
    public function matchPurchase(Request $request, BankTransaction $transaction): RedirectResponse
    {
        $data = $request->validate(['purchase_id' => ['required', 'integer']]);

        abort_unless($transaction->status === 'open', 422);

        $purchase = PurchaseInvoice::open()->findOrFail($data['purchase_id']);

        $purchase->update([
            'status' => 'paid',
            'paid_at' => $transaction->booking_date,
            'payment_method' => 'bank_transfer',
        ]);

        $transaction->update([
            'status' => 'matched',
            'matched_purchase_id' => $purchase->id,
        ]);

        return back()->with('flash', "Gekoppeld aan inkoopfactuur van {$purchase->supplier_name} — op betaald gezet.");
    }

    /** Transactie negeren (privé-opname, interne overboeking, enz.). */
    public function ignore(BankTransaction $transaction): RedirectResponse
    {
        abort_unless($transaction->status === 'open', 422);
        $transaction->update(['status' => 'ignored']);

        return back()->with('flash', 'Transactie genegeerd.');
    }

    /** Koppeling of negeren ongedaan maken. */
    public function restore(BankTransaction $transaction): RedirectResponse
    {
        if ($transaction->status === 'matched') {
            // Betaling terugdraaien (herrekent de factuur automatisch).
            $transaction->payment?->delete();

            if ($transaction->matched_purchase_id) {
                $transaction->matchedPurchase?->update(['status' => 'open', 'paid_at' => null, 'payment_method' => null]);
            }
        }

        $transaction->update([
            'status' => 'open',
            'matched_invoice_id' => null,
            'matched_purchase_id' => null,
            'payment_id' => null,
        ]);

        return back()->with('flash', 'Transactie staat weer open.');
    }

    /* ===================== Suggesties ===================== */

    /**
     * Beste koppel-suggestie voor een open transactie:
     *  - factuurnummer in de omschrijving  → sterkste signaal
     *  - bedrag gelijk aan openstaand      → sterk
     *  - klantnaam/leverancier in tegenpartij of omschrijving → aanwijzing
     */
    protected function suggestFor(BankTransaction $tx, $openInvoices, $openPurchases): ?array
    {
        $haystack = mb_strtolower(($tx->description ?? '') . ' ' . ($tx->counterparty_name ?? ''));
        $amount = abs((float) $tx->amount);

        if ((float) $tx->amount >= 0) {
            $best = null;
            foreach ($openInvoices as $invoice) {
                $reasons = [];
                if ($invoice->number && str_contains($haystack, mb_strtolower($invoice->number))) {
                    $reasons[] = 'factuurnummer';
                }
                if (abs(((float) $invoice->total - (float) $invoice->paid_total) - $amount) < 0.01) {
                    $reasons[] = 'bedrag';
                }
                if ($invoice->customer_name && mb_strlen($invoice->customer_name) > 3
                    && str_contains($haystack, mb_strtolower($invoice->customer_name))) {
                    $reasons[] = 'naam';
                }
                if (! empty($reasons) && ($best === null || count($reasons) > count($best['reasons']))) {
                    $best = [
                        'type' => 'invoice',
                        'id' => $invoice->id,
                        'label' => $invoice->number,
                        'reasons' => $reasons,
                    ];
                }
            }

            return $best;
        }

        $best = null;
        foreach ($openPurchases as $purchase) {
            $reasons = [];
            if (abs((float) $purchase->total - $amount) < 0.01) {
                $reasons[] = 'bedrag';
            }
            if ($purchase->supplier_name && mb_strlen($purchase->supplier_name) > 3
                && str_contains($haystack, mb_strtolower($purchase->supplier_name))) {
                $reasons[] = 'leverancier';
            }
            if ($purchase->supplier_reference && mb_strlen($purchase->supplier_reference) > 3
                && str_contains($haystack, mb_strtolower($purchase->supplier_reference))) {
                $reasons[] = 'kenmerk';
            }
            if (! empty($reasons) && ($best === null || count($reasons) > count($best['reasons']))) {
                $best = [
                    'type' => 'purchase',
                    'id' => $purchase->id,
                    'label' => $purchase->supplier_name,
                    'reasons' => $reasons,
                ];
            }
        }

        return $best;
    }
}
