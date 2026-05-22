<?php

namespace App\Services;

use App\Models\Company;
use App\Models\InvoiceSequence;
use Illuminate\Support\Facades\DB;

/**
 * Assigns the next invoice number for a company. Uses a row-level lock
 * on invoice_sequences to prevent duplicate numbers under concurrent load.
 *
 * Default format: "{year}-{sequence:4}" → "2026-0142"
 */
class InvoiceNumberGenerator
{
    public function generate(Company $company, ?int $year = null): string
    {
        $year ??= (int) date('Y');

        return DB::transaction(function () use ($company, $year) {
            $sequence = InvoiceSequence::lockForUpdate()
                ->firstOrCreate(
                    ['company_id' => $company->id, 'year' => $year],
                    ['last_number' => 0]
                );

            $sequence->increment('last_number');

            return $this->format(
                $company->invoice_number_format ?: '{year}-{sequence:4}',
                $year,
                $sequence->last_number
            );
        });
    }

    protected function format(string $template, int $year, int $sequence): string
    {
        return preg_replace_callback(
            '/\{(year|sequence)(?::(\d+))?\}/',
            function ($matches) use ($year, $sequence) {
                $type = $matches[1];
                $padding = isset($matches[2]) ? (int) $matches[2] : 0;
                $value = $type === 'year' ? $year : $sequence;
                return str_pad((string) $value, $padding, '0', STR_PAD_LEFT);
            },
            $template
        );
    }
}
