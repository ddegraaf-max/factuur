<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use Illuminate\Support\Facades\DB;

class CreditNoteService
{
    public function __construct(private VatCalculator $calc) {}

    public function createFromInvoice(Invoice $original, string $kind = 'full'): Invoice
    {
        if ($original->is_credit) {
            throw new \DomainException('Kan geen creditnota maken voor een creditnota.');
        }

        return DB::transaction(function () use ($original, $kind) {
            $credit = $original->replicate(['number', 'status', 'paid_total', 'paid_at', 'sent_at', 'first_viewed_at']);
            $credit->is_credit = true;
            $credit->credits_invoice_id = $original->id;
            $credit->status = 'draft';
            $credit->number = null;
            $credit->invoice_date = now();
            $credit->due_date = now();
            $credit->payment_terms = 0;
            $credit->notes = sprintf(
                'Deze creditnota crediteert factuur %s %s.',
                $original->number,
                $kind === 'full' ? 'volledig' : 'gedeeltelijk'
            );
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
                    'line_subtotal' => $line->line_subtotal,
                    'line_vat' => $line->line_vat,
                    'line_total' => $line->line_total,
                    'sort_order' => $line->sort_order,
                ]);
            }

            // Recalculate totals
            $totals = $this->calc->calculateInvoice($credit->lines->map(fn ($l) => [
                'quantity' => $l->quantity,
                'unit_price' => $l->unit_price,
                'vat_rate' => $l->vat_rate,
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
