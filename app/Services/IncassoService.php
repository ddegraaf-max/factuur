<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class IncassoService
{
    public function send(Invoice $invoice): Invoice
    {
        if ($invoice->is_credit) {
            throw new \DomainException('Creditnota kan niet naar incasso.');
        }
        if ($invoice->status === 'paid') {
            throw new \DomainException('Betaalde factuur kan niet naar incasso.');
        }
        if ($invoice->status === 'incasso') {
            throw new \DomainException('Deze factuur is al bij incasso.');
        }

        $reference = $this->nextReference($invoice->company);

        $invoice->update([
            'status' => 'incasso',
            'incasso_sent_at' => now(),
            'incasso_reference' => $reference,
            'incasso_handler' => 'Armaere Gerechtsdeurwaarders',
            'incasso_phase' => 'minnelijk',
        ]);

        return $invoice->fresh();
    }

    public function updatePhase(Invoice $invoice, string $phase): Invoice
    {
        if (! in_array($phase, ['minnelijk', 'gerechtelijk', 'executie'])) {
            throw new \InvalidArgumentException("Onbekende incasso-fase: {$phase}");
        }
        $invoice->update(['incasso_phase' => $phase]);
        return $invoice->fresh();
    }

    private function nextReference(Company $company): string
    {
        return DB::transaction(function () use ($company) {
            $year = now()->year;
            $seq = DB::table('incasso_sequences')
                ->where('company_id', $company->id)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $seq) {
                $next = 1;
                DB::table('incasso_sequences')->insert([
                    'company_id' => $company->id,
                    'year' => $year,
                    'current_value' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $next = $seq->current_value + 1;
                DB::table('incasso_sequences')
                    ->where('id', $seq->id)
                    ->update(['current_value' => $next, 'updated_at' => now()]);
            }

            return sprintf('ARM-%d-%04d', $year, $next);
        });
    }
}
