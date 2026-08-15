<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Company;
use App\Mail\IncassoDossierMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
            'incasso_handler' => config('incasso.partner_name'),
            'incasso_phase' => 'minnelijk',
        ]);

        $fresh = $invoice->fresh();
        $this->emailDossier($fresh);

        return $fresh;
    }

    public function updatePhase(Invoice $invoice, string $phase): Invoice
    {
        if (! in_array($phase, ['minnelijk', 'gerechtelijk', 'executie'])) {
            throw new \InvalidArgumentException("Onbekende incasso-fase: {$phase}");
        }
        $invoice->update(['incasso_phase' => $phase]);
        return $invoice->fresh();
    }

    /**
     * Stuur het complete incasso-dossier per e-mail naar de incassopartner.
     * Faalt dit, dan blijft de factuur wel op 'incasso' staan en loggen we de fout.
     */
    private function emailDossier(Invoice $invoice): void
    {
        try {
            $company = $invoice->company;
            $invoice->load(['lines', 'payments', 'reminderLogs', 'attachments']);

            $template = in_array($company->invoice_template, ['modern', 'classic', 'minimal'], true)
                ? $company->invoice_template
                : 'modern';

            $pdf = Pdf::loadView("pdf.invoice-{$template}", [
                'invoice' => $invoice,
                'company' => $company,
            ])->setPaper('a4')->output();

            $files = [];
            foreach ($invoice->attachments as $att) {
                $contents = $att->contents();
                if ($contents !== null) {
                    $files[] = [
                        'name' => $att->filename,
                        'data' => $contents,
                        'mime' => $att->mime_type ?: 'application/octet-stream',
                    ];
                }
            }

            Mail::to(config('incasso.claims_email'))
                ->cc(config('incasso.cc'))
                ->send(new IncassoDossierMail($invoice, $pdf, $files));
        } catch (\Throwable $e) {
            Log::error('Incasso-dossier versturen mislukt', [
                'invoice' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
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
