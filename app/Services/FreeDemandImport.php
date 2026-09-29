<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Support\Brand;
use App\Support\DocumentLocale;
use App\Support\Kor;
use App\Support\Market;
use Illuminate\Support\Carbon;

/**
 * Neemt een aanmaning uit de gratis tool mee naar een nieuw account: de eigen
 * bedrijfsgegevens, de klant en de factuur waar het om gaat. De factuur komt
 * erin zoals hij elders is gemaakt — verstuurd en achterstallig — zodat de
 * aanmaning er meteen bij verstuurd kan worden.
 *
 * Net als bij de gratis factuur gebeurt dit alleen als de bezoeker er zelf
 * voor kiest.
 */
class FreeDemandImport extends FreeInvoiceImport
{
    /** Sleutel in de sessie waar de aanmaning wacht tot het account bestaat. */
    public const SESSION = 'free_demand';

    /** Na het bevestigen van het e-mailadres begint het account op deze factuur. */
    public const WELCOME = 'welcome_demand';

    /**
     * @param  array<string, mixed>  $data  de gevalideerde velden van de gratis tool
     */
    public function apply(Company $company, array $data): ?Invoice
    {
        $this->fillCompany($company, $data);
        if (! empty($data['geen_btw_aftrek']) && Kor::available() && ! $company->kor) {
            $company->forceFill(['kor' => true])->save();
        }

        $number = trim((string) ($data['factuurnummer'] ?? ''));
        $total = round((float) ($data['bedrag'] ?? 0), 2);
        if (blank($data['aan_naam'] ?? null) || $number === '' || $total <= 0) {
            return null;
        }

        $customer = Customer::create([
            'company_id' => $company->id,
            'name' => mb_substr((string) $data['aan_naam'], 0, 255),
            'type' => ($data['klant'] ?? 'zakelijk') === 'particulier' ? 'consumer' : 'business',
            'email' => filter_var($data['aan_email'] ?? '', FILTER_VALIDATE_EMAIL) ? mb_strtolower((string) $data['aan_email']) : null,
            'country' => $company->country ?: Market::country(),
            'language' => DocumentLocale::default(),
        ] + $this->address((string) ($data['aan_adres'] ?? '')));

        $date = Carbon::parse($data['factuurdatum']);
        $due = Carbon::parse($data['vervaldatum']);
        // Onder de kleineondernemersregeling staat er geen btw op de factuur.
        $exempt = Kor::applies($company->fresh());
        $rate = $exempt ? 0 : (int) ($data['btw'] ?? Market::defaultVatRate());
        $subtotal = round($total / (1 + $rate / 100), 2);
        $vat = round($total - $subtotal, 2);

        $invoice = new Invoice();
        $invoice->forceFill([
            'company_id' => $company->id, 'customer_id' => $customer->id, 'number' => mb_substr($number, 0, 50),
            'status' => $due->copy()->endOfDay()->isPast() ? 'overdue' : 'sent',
            'invoice_date' => $date->toDateString(), 'due_date' => $due->toDateString(),
            'payment_terms' => (int) max(0, round($date->diffInDays($due, false))),
            'language' => $customer->language ?: DocumentLocale::default(),
            'customer_name' => $customer->name, 'customer_address_line' => $customer->address_line,
            'customer_postal_code' => $customer->postal_code, 'customer_city' => $customer->city,
            'customer_country' => $customer->country, 'customer_email' => $customer->email,
            'subtotal' => $subtotal, 'vat_total' => $vat, 'total' => $total, 'paid_total' => 0,
            'vat_breakdown' => [(string) $rate => $vat],
            'vat_exempt' => $exempt,
            'notes' => __('Overgenomen uit de gratis aanmaning van :brand. De oorspronkelijke factuur is elders gemaakt.', ['brand' => Brand::name()]),
            'sent_at' => $date->toDateTimeString(),
            'portal_token' => bin2hex(random_bytes(32)),
        ])->save();

        $invoice->lines()->create([
            'sort_order' => 0, 'description' => __('Factuur :number (overgenomen)', ['number' => $number]), 'quantity' => 1, 'unit' => __('stuk'),
            'unit_price' => $subtotal, 'vat_rate' => $rate, 'line_subtotal' => $subtotal, 'line_vat' => $vat, 'line_total' => $total,
        ]);

        return $invoice;
    }
}
