<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Payment;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;

class CreditNoteService
{
    public function __construct(private VatCalculator $calc) {}

    public function createFromInvoice(Invoice $original, string $kind = 'full'): Invoice
    {
        if ($original->is_credit) {
            throw new \DomainException(__('Kan geen creditnota maken voor een creditnota.'));
        }

        return DB::transaction(function () use ($original, $kind) {
            // portal_token is uniek per factuur en mag dus nooit mee-gekopieerd
            // worden; scheduled_send_on evenmin (een creditnota plan je niet in).
            $credit = $original->replicate([
                'number', 'status', 'paid_total', 'paid_at', 'sent_at', 'first_viewed_at',
                'portal_token', 'scheduled_send_on',
            ]);
            $credit->is_credit = true;
            $credit->credits_invoice_id = $original->id;
            $credit->status = 'draft';
            $credit->number = null;
            $credit->invoice_date = now();
            $credit->due_date = now();
            $credit->payment_terms = 0;
            $credit->notes = $kind === 'full'
                ? __('Deze creditnota crediteert factuur :number volledig.', ['number' => $original->number])
                : __('Deze creditnota crediteert factuur :number gedeeltelijk.', ['number' => $original->number]);
            $credit->save();

            // Copy invoice lines
            foreach ($original->lines as $line) {
                $credit->lines()->create([
                    'product_id' => $line->product_id,
                    'description' => $line->description,
                    'details' => $line->details,
                    'quantity' => $line->quantity,
                    'unit' => $line->unit,
                    'unit_price' => $line->unit_price,
                    'vat_rate' => $line->vat_rate,
                    'discount_pct' => $line->discount_pct,
                    'line_subtotal' => $line->line_subtotal,
                    'line_vat' => $line->line_vat,
                    'line_total' => $line->line_total,
                    'sort_order' => $line->sort_order,
                ]);
            }

            // Recalculate totals (inclusief regelkorting — anders zou er
            // méér gecrediteerd worden dan er ooit is gefactureerd)
            $totals = $this->calc->calculateInvoice($credit->lines->map(fn ($l) => [
                'quantity' => $l->quantity,
                'unit_price' => $l->unit_price,
                'vat_rate' => $l->vat_rate,
                'discount_pct' => (float) ($l->discount_pct ?? 0),
            ])->toArray());

            $credit->update([
                'subtotal' => $totals['subtotal'],
                'vat_total' => $totals['vat_total'],
                'total' => $totals['total'],
                'vat_breakdown' => $totals['vat_breakdown'],
            ]);

            return $credit->fresh(['lines']);
        });
    }

    /**
     * Verrekent een creditnota met de factuur die ze crediteert. Op allebei
     * komt een boeking van soort 'credit' voor hetzelfde bedrag: de factuur is
     * daarmee (deels) voldaan en de creditnota afgewikkeld, zonder dat er geld
     * beweegt. Omzet en btw blijven wat de twee documenten al zeggen.
     *
     * Beide kanten mogen in willekeurige volgorde worden opgegeven. Geeft het
     * verrekende bedrag terug.
     */
    public function settle(Invoice $one, Invoice $other, ?string $paidOn = null, ?string $reference = null): float
    {
        [$invoice, $credit] = $one->is_credit ? [$other, $one] : [$one, $other];

        if (! $credit->is_credit || $invoice->is_credit || (int) $credit->credits_invoice_id !== (int) $invoice->id) {
            throw new \DomainException(__('Deze creditnota hoort niet bij deze factuur.'));
        }
        if ($credit->status === 'draft') {
            throw new \DomainException(__('Maak de creditnota eerst definitief; een concept kun je niet verrekenen.'));
        }
        if (! in_array($invoice->status, ['sent', 'partial', 'overdue'], true)) {
            throw new \DomainException(__('Alleen een openstaande factuur kan worden verrekend.'));
        }

        $amount = round(min((float) $invoice->remaining_amount, (float) $credit->remaining_amount), 2);
        if ($amount < 0.01) {
            throw new \DomainException(__('Er valt niets te verrekenen: de factuur of de creditnota is al voldaan.'));
        }

        $paidOn = $paidOn ?: now()->toDateString();
        $reference = trim((string) $reference);

        DB::transaction(function () use ($invoice, $credit, $amount, $paidOn, $reference) {
            foreach ([
                [$invoice, __('Verrekend met creditnota :number', ['number' => $credit->number])],
                [$credit, __('Verrekend met factuur :number', ['number' => $invoice->number])],
            ] as [$document, $default]) {
                // Het opslaan herrekent paid_total en de status van het document.
                Payment::create([
                    'company_id' => $document->company_id,
                    'invoice_id' => $document->id,
                    'kind' => 'credit',
                    'amount' => $amount,
                    'paid_on' => $paidOn,
                    'method' => 'other',
                    'reference' => $reference !== '' ? $reference : $default,
                ]);
            }
        });

        Audit::log('settled', $invoice, __(':label verrekend met creditnota :number (:amount)', ['label' => Audit::label($invoice), 'number' => $credit->number, 'amount' => money($amount)]));
        Audit::log('settled', $credit, __(':label verrekend met factuur :number (:amount)', ['label' => Audit::label($credit), 'number' => $invoice->number, 'amount' => money($amount)]));

        return $amount;
    }

    public function nextNumber(Company $company): string
    {
        return DB::transaction(function () use ($company) {
            $year = now()->year;
            $seq = DB::table('credit_sequences')
                ->where('company_id', $company->id)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $seq) {
                $next = 1;
                DB::table('credit_sequences')->insert([
                    'company_id' => $company->id,
                    'year' => $year,
                    'current_value' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $next = $seq->current_value + 1;
                DB::table('credit_sequences')
                    ->where('id', $seq->id)
                    ->update(['current_value' => $next, 'updated_at' => now()]);
            }

            return sprintf('C-%d-%04d', $year, $next);
        });
    }
}
