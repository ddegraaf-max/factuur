<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Quote;

/**
 * Aanpasbare e-mailteksten: dezelfde {variabelen} als bij de herinneringen.
 * De teksten komen uit companies.email_texts (Instellingen → E-mailteksten);
 * variabelen worden hier ingevuld. Eigen tekst is bewust letterlijk: die
 * wordt niet vertaald en geldt dus voor alle klanttalen.
 */
class MailText
{
    public static function apply(string $text, array $vars): string
    {
        return strtr($text, $vars);
    }

    /** @return array<string, string> */
    public static function invoiceVars(Invoice $invoice, $company): array
    {
        return [
            '{klant}' => (string) ($invoice->customer_name ?? ''),
            '{bedrijf}' => (string) ($company->name ?? ''),
            '{factuurnummer}' => (string) ($invoice->number ?? ''),
            '{factuurdatum}' => optional($invoice->invoice_date)->translatedFormat('j F Y') ?? '',
            '{vervaldatum}' => optional($invoice->due_date)->translatedFormat('j F Y') ?? '',
            '{bedrag}' => '€ ' . number_format((float) $invoice->total, 2, ',', '.'),
            '{openstaand}' => '€ ' . number_format(max((float) $invoice->total - (float) $invoice->paid_total, 0), 2, ',', '.'),
            '{iban}' => (string) ($company->iban ?? ''),
        ];
    }

    /** @return array<string, string> */
    public static function quoteVars(Quote $quote, $company): array
    {
        return [
            '{klant}' => (string) ($quote->customer_name ?? ''),
            '{bedrijf}' => (string) ($company->name ?? ''),
            '{offertenummer}' => (string) ($quote->number ?? ''),
            '{offertedatum}' => optional($quote->quote_date)->translatedFormat('j F Y') ?? '',
            '{geldigtot}' => optional($quote->valid_until)->translatedFormat('j F Y') ?? '',
            '{bedrag}' => '€ ' . number_format((float) $quote->total, 2, ',', '.'),
        ];
    }
}
