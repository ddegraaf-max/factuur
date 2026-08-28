<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Overstapwizard: CSV-exports van WeFact, Moneybird, e-Boekhouden, Excel of
 * elk ander pakket inlezen. Herkent scheidingsteken en tekenset, stelt de
 * kolomkoppeling voor op basis van de kopregel en importeert klanten,
 * producten en openstaande facturen zonder dubbelen.
 */
class ImportService
{
    public const TYPES = ['customers' => 'Klanten', 'products' => 'Producten', 'invoices' => 'Openstaande facturen'];

    /** Doelvelden per type: veld => [label, verplicht, synoniemen in kopregels]. */
    public const FIELDS = [
        'customers' => [
            'name' => ['Bedrijfs-/klantnaam', true, ['bedrijfsnaam', 'naam', 'name', 'company', 'bedrijf', 'klant', 'klantnaam', 'organisatie', 'relatie', 'companyname', 'debiteur']],
            'contact_name' => ['Contactpersoon', false, ['contactpersoon', 'contact', 'contactname', 'tav', 'aanspreekpunt', 'voornaamachternaam']],
            'email' => ['E-mailadres', false, ['email', 'emailadres', 'mail', 'emailaddress']],
            'phone' => ['Telefoon', false, ['telefoon', 'telefoonnummer', 'phone', 'tel', 'mobiel']],
            'address_line' => ['Adres (straat + nr)', false, ['adres', 'straat', 'address', 'straatnaam', 'adresregel', 'address1', 'straatennummer']],
            'house_number' => ['Huisnummer (los)', false, ['huisnummer', 'nummer', 'housenumber', 'nr']],
            'postal_code' => ['Postcode', false, ['postcode', 'zip', 'zipcode', 'postalcode']],
            'city' => ['Plaats', false, ['plaats', 'woonplaats', 'city', 'stad', 'vestigingsplaats']],
            'country' => ['Land', false, ['land', 'country', 'landcode', 'countrycode']],
            'kvk_number' => ['KvK-nummer', false, ['kvk', 'kvknummer', 'kvknr', 'chamberofcommerce', 'coc', 'handelsregister']],
            'vat_number' => ['Btw-nummer', false, ['btwnummer', 'btwnr', 'vat', 'vatnumber', 'btw', 'btwid', 'vatid']],
            'payment_terms' => ['Betalingstermijn (dagen)', false, ['betalingstermijn', 'betaaltermijn', 'paymentterms', 'termijn']],
            'notes' => ['Notities', false, ['opmerking', 'opmerkingen', 'notities', 'notes', 'memo']],
        ],
        'products' => [
            'name' => ['Productnaam', true, ['productnaam', 'naam', 'omschrijving', 'product', 'artikel', 'description', 'name', 'artikelnaam', 'dienst']],
            'description' => ['Toelichting', false, ['toelichting', 'details', 'beschrijving', 'extra', 'longdescription']],
            'sku' => ['Artikelnummer', false, ['artikelnummer', 'code', 'sku', 'productcode', 'artikelcode', 'nummer']],
            'unit' => ['Eenheid', false, ['eenheid', 'unit', 'per']],
            'price' => ['Prijs excl. btw', true, ['prijs', 'prijsexcl', 'prijsexclbtw', 'unitprice', 'price', 'bedrag', 'verkoopprijs', 'stuksprijs', 'tarief']],
            'vat_rate' => ['Btw-tarief (%)', false, ['btw', 'btwtarief', 'btwpercentage', 'vat', 'vatrate', 'btwperc']],
        ],
        'invoices' => [
            'number' => ['Factuurnummer', true, ['factuurnummer', 'nummer', 'invoicenumber', 'factuur', 'factuurnr', 'invoice']],
            'customer' => ['Klantnaam', true, ['klant', 'klantnaam', 'bedrijfsnaam', 'debiteur', 'customer', 'naam', 'relatie']],
            'date' => ['Factuurdatum', true, ['factuurdatum', 'datum', 'date', 'invoicedate']],
            'due_date' => ['Vervaldatum', false, ['vervaldatum', 'duedate', 'vervalt', 'betaaldatum', 'uiterlijk']],
            'total' => ['Totaal incl. btw', true, ['totaal', 'bedraginclbtw', 'totaalbedrag', 'total', 'bedrag', 'inclbtw', 'totaalincl', 'brutobedrag', 'incl']],
            'vat_rate' => ['Btw-tarief (%)', false, ['btw', 'btwtarief', 'btwpercentage', 'vat', 'btwperc']],
            'paid' => ['Reeds betaald', false, ['betaald', 'paid', 'ontvangen', 'voldaan']],
            'reference' => ['Referentie/omschrijving', false, ['referentie', 'kenmerk', 'omschrijving', 'reference', 'onderwerp']],
            'email' => ['E-mail klant', false, ['email', 'emailadres', 'mail']],
        ],
    ];

    /** @return array{headers: string[], rows: array<int, string[]>, delimiter: string} */
    public function parse(string $contents): array
    {
        if (! mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
        }
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        $lines = preg_split('/\r\n|\r|\n/', trim($contents));
        $first = $lines[0] ?? '';
        $delimiter = collect([';', ',', "\t", '|'])->sortByDesc(fn ($d) => substr_count($first, $d))->first();
        if (substr_count($first, $delimiter) === 0) {
            throw new \DomainException('Dit lijkt geen CSV-bestand: er is geen scheidingsteken (; of ,) gevonden in de eerste regel.');
        }

        $rows = [];
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, implode("\n", $lines));
        rewind($handle);
        while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) continue;
            $rows[] = array_map(fn ($v) => trim((string) $v), $row);
        }
        fclose($handle);
        if (count($rows) < 2) {
            throw new \DomainException('Het bestand bevat geen gegevensregels onder de kopregel.');
        }

        $headers = array_shift($rows);

        return ['headers' => $headers, 'rows' => $rows, 'delimiter' => $delimiter];
    }

    /** Voorgestelde koppeling: kolomindex per doelveld op basis van de kopregel. */
    public function suggestMapping(string $type, array $headers): array
    {
        $mapping = [];
        $normalized = array_map(fn ($h) => $this->key($h), $headers);
        foreach (self::FIELDS[$type] as $field => [, , $synonyms]) {
            foreach ($normalized as $i => $h) {
                if (in_array($i, $mapping, true)) continue;
                if (in_array($h, $synonyms, true) || $h === $this->key($field)) {
                    $mapping[$field] = $i;
                    break;
                }
            }
        }
        // Tweede ronde: gedeeltelijke overeenkomst ("emailadres klant" → email).
        foreach (self::FIELDS[$type] as $field => [, , $synonyms]) {
            if (isset($mapping[$field])) continue;
            foreach ($normalized as $i => $h) {
                if (in_array($i, $mapping, true)) continue;
                foreach ($synonyms as $syn) {
                    if (strlen($syn) >= 4 && str_contains($h, $syn)) {
                        $mapping[$field] = $i;
                        continue 3;
                    }
                }
            }
        }

        return $mapping;
    }

    /** @return array{created: int, skipped: int, errors: string[]} */
    public function import(Company $company, string $type, array $rows, array $mapping): array
    {
        $get = fn (array $row, string $field) => isset($mapping[$field]) && $mapping[$field] !== '' && $mapping[$field] !== null ? trim((string) ($row[(int) $mapping[$field]] ?? '')) : '';
        $result = ['created' => 0, 'skipped' => 0, 'errors' => []];

        DB::transaction(function () use ($company, $type, $rows, $get, &$result) {
            foreach ($rows as $n => $row) {
                try {
                    $done = match ($type) {
                        'customers' => $this->importCustomer($company, $row, $get),
                        'products' => $this->importProduct($company, $row, $get),
                        'invoices' => $this->importInvoice($company, $row, $get),
                    };
                    $done ? $result['created']++ : $result['skipped']++;
                } catch (\Throwable $e) {
                    $result['skipped']++;
                    if (count($result['errors']) < 20) {
                        $result['errors'][] = 'Regel ' . ($n + 2) . ': ' . $e->getMessage();
                    }
                }
            }
        });

        return $result;
    }

    protected function importCustomer(Company $company, array $row, callable $get): bool
    {
        $name = $get($row, 'name');
        if ($name === '') throw new \DomainException('geen naam');
        $email = mb_strtolower($get($row, 'email'));

        $exists = Customer::withoutGlobalScope('company')->where('company_id', $company->id)
            ->where(fn ($q) => $email ? $q->whereRaw('LOWER(email) = ?', [$email])->orWhereRaw('LOWER(name) = ?', [mb_strtolower($name)]) : $q->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]))
            ->exists();
        if ($exists) return false;

        $address = trim($get($row, 'address_line') . ' ' . $get($row, 'house_number'));
        $country = strtoupper(substr($get($row, 'country'), 0, 2));
        $country = in_array($country, ['NL', 'BE', 'DE', 'FR', 'GB', 'ES', 'IT', 'LU', 'AT', 'DK', 'SE', 'PL', 'PT', 'IE', 'US'], true) ? $country : (str_starts_with(mb_strtolower($get($row, 'country')), 'bel') ? 'BE' : (str_starts_with(mb_strtolower($get($row, 'country')), 'duits') ? 'DE' : 'NL'));

        Customer::create([
            'company_id' => $company->id,
            'name' => mb_substr($name, 0, 255),
            'type' => filled($get($row, 'kvk_number')) || filled($get($row, 'vat_number')) || preg_match('/\b(bv|b\.v\.|nv|vof|holding|bedrijf|group|groep|stichting)\b/i', $name) ? 'business' : 'consumer',
            'contact_name' => mb_substr($get($row, 'contact_name'), 0, 255) ?: null,
            'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
            'phone' => mb_substr($get($row, 'phone'), 0, 50) ?: null,
            'kvk_number' => mb_substr($get($row, 'kvk_number'), 0, 20) ?: null,
            'vat_number' => mb_substr(strtoupper(str_replace(' ', '', $get($row, 'vat_number'))), 0, 20) ?: null,
            'address_line' => mb_substr($address, 0, 255) ?: null,
            'postal_code' => mb_substr(strtoupper($get($row, 'postal_code')), 0, 20) ?: null,
            'city' => mb_substr($get($row, 'city'), 0, 100) ?: null,
            'country' => $country,
            'language' => 'nl',
            'payment_terms' => ctype_digit($get($row, 'payment_terms')) ? (int) $get($row, 'payment_terms') : null,
            'notes' => $get($row, 'notes') ?: null,
        ]);

        return true;
    }

    protected function importProduct(Company $company, array $row, callable $get): bool
    {
        $name = $get($row, 'name');
        if ($name === '') throw new \DomainException('geen productnaam');
        if (Product::withoutGlobalScope('company')->where('company_id', $company->id)->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
            return false;
        }
        $rate = $this->number($get($row, 'vat_rate'));
        $rate = in_array((int) round($rate), [21, 9, 0], true) ? (int) round($rate) : 21;

        Product::create([
            'company_id' => $company->id,
            'name' => mb_substr($name, 0, 255),
            'description' => $get($row, 'description') ?: null,
            'sku' => mb_substr($get($row, 'sku'), 0, 50) ?: null,
            'unit' => mb_substr($get($row, 'unit'), 0, 20) ?: 'stuk',
            'price' => round($this->number($get($row, 'price')), 2),
            'vat_rate' => $rate,
            'is_active' => true,
        ]);

        return true;
    }

    protected function importInvoice(Company $company, array $row, callable $get): bool
    {
        $number = $get($row, 'number');
        $customerName = $get($row, 'customer');
        if ($number === '' || $customerName === '') throw new \DomainException('factuurnummer of klant ontbreekt');
        if (Invoice::withoutGlobalScope('company')->where('company_id', $company->id)->where('number', $number)->exists()) {
            return false;
        }

        $date = $this->date($get($row, 'date')) ?? throw new \DomainException('ongeldige factuurdatum');
        $due = $this->date($get($row, 'due_date')) ?? $date->copy()->addDays($company->default_payment_terms ?: 30);
        $total = round($this->number($get($row, 'total')), 2);
        if ($total == 0.0) throw new \DomainException('geen bedrag');
        $rate = (int) round($this->number($get($row, 'vat_rate') ?: '21'));
        $rate = in_array($rate, [21, 9, 0], true) ? $rate : 21;
        $paid = round($this->number($get($row, 'paid')), 2);

        $customer = Customer::withoutGlobalScope('company')->where('company_id', $company->id)->whereRaw('LOWER(name) = ?', [mb_strtolower($customerName)])->first()
            ?? Customer::create(['company_id' => $company->id, 'name' => mb_substr($customerName, 0, 255), 'type' => 'business', 'country' => 'NL', 'language' => 'nl', 'email' => filter_var($get($row, 'email'), FILTER_VALIDATE_EMAIL) ? mb_strtolower($get($row, 'email')) : null]);

        $subtotal = round($total / (1 + $rate / 100), 2);
        $vat = round($total - $subtotal, 2);
        $status = $paid >= $total - 0.004 ? 'paid' : ($paid > 0 ? 'partial' : ($due->isPast() ? 'overdue' : 'sent'));

        $invoice = new Invoice();
        $invoice->forceFill([
            'company_id' => $company->id, 'customer_id' => $customer->id, 'number' => mb_substr($number, 0, 50), 'status' => $status,
            'invoice_date' => $date->toDateString(), 'due_date' => $due->toDateString(), 'payment_terms' => (int) max(0, $date->diffInDays($due, false)),
            'reference' => mb_substr($get($row, 'reference'), 0, 255) ?: null, 'language' => 'nl',
            'customer_name' => $customer->name, 'customer_address_line' => $customer->address_line, 'customer_postal_code' => $customer->postal_code,
            'customer_city' => $customer->city, 'customer_country' => $customer->country, 'customer_vat_number' => $customer->vat_number,
            'customer_kvk_number' => $customer->kvk_number, 'customer_email' => $customer->email,
            'subtotal' => $subtotal, 'vat_total' => $vat, 'total' => $total, 'paid_total' => min($paid, $total),
            'vat_breakdown' => [(string) $rate => $vat], 'notes' => 'Overgenomen uit vorig pakket bij de overstap naar EasyInvoice.',
            'sent_at' => $date->toDateTimeString(), 'paid_at' => $status === 'paid' ? now() : null,
            'portal_token' => bin2hex(random_bytes(32)),
        ])->save();

        $invoice->lines()->create([
            'sort_order' => 0, 'description' => 'Factuur ' . $number . ' (overgenomen)', 'quantity' => 1, 'unit' => 'stuk',
            'unit_price' => $subtotal, 'vat_rate' => $rate, 'line_subtotal' => $subtotal, 'line_vat' => $vat, 'line_total' => $total,
        ]);

        if ($paid > 0) {
            Payment::create(['company_id' => $company->id, 'invoice_id' => $invoice->id, 'kind' => 'payment', 'amount' => min($paid, $total), 'paid_on' => $date->toDateString(), 'method' => 'bank', 'notes' => 'Overgenomen bij import']);
        }

        return true;
    }

    private function key(string $header): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::ascii(mb_strtolower($header)));
    }

    /** "1.234,56" / "1234.56" / "€ 1.234,56" → 1234.56 */
    private function number(string $value): float
    {
        $v = preg_replace('/[^\d,.\-]/', '', $value);
        if ($v === '' || $v === '-') return 0.0;
        if (str_contains($v, ',') && str_contains($v, '.')) {
            $v = strrpos($v, ',') > strrpos($v, '.') ? str_replace('.', '', $v) : str_replace(',', '', $v);
        }
        $v = str_replace(',', '.', $v);

        return (float) $v;
    }

    private function date(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') return null;
        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'd.m.Y', 'Y/m/d', 'd-m-y', 'j-n-Y'] as $format) {
            try {
                $d = Carbon::createFromFormat($format, $value);
                if ($d && $d->year > 1990 && $d->year < 2100) return $d->startOfDay();
            } catch (\Throwable) {
            }
        }
        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
