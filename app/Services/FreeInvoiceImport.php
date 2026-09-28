<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Support\Carbon;

/**
 * Neemt een factuur uit de gratis tool mee naar een nieuw account: de eigen
 * bedrijfsgegevens, de klant en de factuur zelf als concept.
 *
 * Dit gebeurt alleen als de bezoeker daar na het downloaden zelf voor kiest;
 * wie alleen een PDF maakt, laat niets achter.
 */
class FreeInvoiceImport
{
    /** Sleutel in de sessie waar de gekozen factuur wacht tot het account bestaat. */
    public const SESSION = 'free_invoice';

    public function __construct(protected InvoiceManager $invoices) {}

    /**
     * @param  array<string, mixed>  $data  de gevalideerde velden van de gratis tool
     */
    public function apply(Company $company, array $data): ?Invoice
    {
        $this->fillCompany($company, $data);

        if (blank($data['aan_bedrijf'] ?? null) || empty($data['regels'])) {
            return null;
        }

        $customer = Customer::create([
            'company_id' => $company->id,
            'name' => mb_substr((string) $data['aan_bedrijf'], 0, 255),
            'country' => $company->country ?: 'NL',
        ] + $this->address((string) ($data['aan_adres'] ?? '')));

        $normal = ($data['btw_type'] ?? 'normaal') === 'normaal';
        $date = Carbon::parse($data['factuurdatum'] ?? now());
        $terms = filled($data['vervaldatum'] ?? null)
            ? (int) max(0, min(365, round($date->diffInDays(Carbon::parse($data['vervaldatum']), false))))
            : 14;

        // Zonder btw: de reden hoort op de factuur te staan.
        $reason = match ($data['btw_type'] ?? 'normaal') {
            'verlegd' => __('Btw verlegd. De btw is verlegd naar de afnemer (artikel 12 Wet OB).'),
            'vrijgesteld' => __('Vrijgesteld van btw. Op deze factuur is geen btw van toepassing.'),
            default => null,
        };

        return $this->invoices->create([
            'customer_id' => $customer->id,
            'invoice_date' => $date->toDateString(),
            'payment_terms' => $terms,
            'price_mode' => 'excl',
            'reference' => filled($data['factuurnummer'] ?? null) ? mb_substr((string) $data['factuurnummer'], 0, 255) : null,
            'notes' => trim(implode("\n\n", array_filter([$reason, $data['opmerking'] ?? null]))) ?: null,
            'lines' => array_map(fn (array $line) => [
                'description' => mb_substr((string) $line['omschrijving'], 0, 500),
                'quantity' => (float) $line['aantal'],
                'unit' => __('stuk'),
                'unit_price' => (float) $line['prijs'],
                'vat_rate' => $normal ? (float) $line['btw'] : 0.0,
            ], array_values($data['regels'])),
        ]);
    }

    /** Alleen velden die nog leeg zijn, en alleen waarden die kloppen. */
    protected function fillCompany(Company $company, array $data): void
    {
        $fill = $this->address((string) ($data['van_adres'] ?? ''));

        $kvk = preg_replace('/\D/', '', (string) ($data['van_kvk'] ?? ''));
        if (strlen($kvk) === 8 && ! Company::withoutGlobalScopes()->where('kvk_number', $kvk)->exists()) {
            $fill['kvk_number'] = $kvk;
        }

        $vat = strtoupper(preg_replace('/[\s.\-]/', '', (string) ($data['van_btw'] ?? '')));
        if (preg_match('/^NL\d{9}B\d{2}$/', $vat) && ! Company::withoutGlobalScopes()->where('vat_number', $vat)->exists()) {
            $fill['vat_number'] = $vat;
        }

        $iban = strtoupper(preg_replace('/\s+/', '', (string) ($data['van_iban'] ?? '')));
        if (preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/', $iban)) {
            $fill['iban'] = $iban;
        }

        $fill = array_filter($fill, fn ($value, $field) => filled($value) && blank($company->{$field}), ARRAY_FILTER_USE_BOTH);
        if ($fill) {
            $company->forceFill($fill)->save();
        }
    }

    /**
     * "Straatnaam 1\n1234 AB Plaats" → adres, postcode en plaats. Past de tweede
     * regel niet in dat patroon, dan blijft alles bij elkaar in het adres staan.
     *
     * @return array{address_line?: string, postal_code?: string, city?: string}
     */
    protected function address(string $text): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $text) ?: [])));
        if ($lines === []) {
            return [];
        }

        $last = end($lines);
        if (count($lines) > 1 && preg_match('/^(\d{4})\s?([A-Za-z]{2})\s+(.+)$/', $last, $m)) {
            return [
                'address_line' => mb_substr(implode(', ', array_slice($lines, 0, -1)), 0, 255),
                'postal_code' => $m[1] . ' ' . strtoupper($m[2]),
                'city' => mb_substr($m[3], 0, 120),
            ];
        }

        return ['address_line' => mb_substr(implode(', ', $lines), 0, 255)];
    }
}
